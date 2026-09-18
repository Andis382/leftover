<?php

namespace App\Console\Commands;

use App\Models\Plan;
use App\Models\User;
use App\Services\Notify\NotifierFactory;
use App\Services\PlanBuilder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Writes tomorrow's numbers for every shop, and delivers them if asked to.
 *
 * Run it hourly. Each shop has its own plan hour and its own timezone, and a
 * shop is only served when its local clock has reached that hour, so one cron
 * line covers a bakery in Tirana and one in Hamburg without either of them
 * getting up at the wrong time.
 */
class MakePlans extends Command
{
    protected $signature = 'leftover:plan
        {--date= : the day to plan for, defaults to each shop\'s tomorrow}
        {--shop= : one shop id, instead of all of them}
        {--now : ignore the plan hour and do it regardless}
        {--dry-run : write nothing, send nothing, just show it}';

    protected $description = "Write each bakery's bake plan for tomorrow and deliver it";

    public function handle(PlanBuilder $builder, NotifierFactory $notifiers): int
    {
        $dry = (bool) $this->option('dry-run');
        $shops = User::query()
            ->when($this->option('shop'), fn ($q, $id) => $q->whereKey($id))
            ->get();

        $written = 0;
        $sent = 0;
        $skipped = 0;

        foreach ($shops as $shop) {
            $local = now($shop->timezone ?: config('app.timezone'));

            if (! $this->option('now') && ! $this->option('date') && ! $this->isPlanHour($shop, $local)) {
                $skipped++;

                continue;
            }

            $forDate = $this->option('date')
                ? Carbon::parse($this->option('date'))->startOfDay()
                : $local->copy()->addDay()->startOfDay();

            if ($shop->activeProducts()->count() === 0) {
                $this->line("  {$shop->displayName()}: no products yet");
                $skipped++;

                continue;
            }

            $plan = $builder->build($shop, $forDate, persist: ! $dry);
            $written++;

            $this->line("  {$shop->displayName()} → {$forDate->toDateString()}");
            if ($dry || $this->output->isVerbose()) {
                $this->line(collect(explode("\n", (string) $plan->body))->map(fn ($l) => '    '.$l)->implode("\n"));
            }

            if ($dry || $plan->status === Plan::SENT) {
                continue;
            }

            if ($notifiers->for($shop)->send($shop, $plan)) {
                $plan->forceFill(['status' => Plan::SENT, 'sent_at' => now()])->save();
                $sent++;
            }
        }

        $this->info($dry
            ? "Would write {$written} plan(s). Nothing was saved or sent."
            : "Wrote {$written} plan(s), delivered {$sent}, skipped {$skipped}.");

        return self::SUCCESS;
    }

    /**
     * Within the hour the shop asked for, in the shop's own time.
     *
     * An hourly cron fires once per hour, so matching the hour is enough and
     * matching the minute would mean missing the plan whenever cron ran at
     * :01 — which is how a "reliable" scheduled job quietly stops existing.
     */
    private function isPlanHour(User $shop, Carbon $local): bool
    {
        return $local->format('H') === substr((string) $shop->plan_at, 0, 2);
    }
}
