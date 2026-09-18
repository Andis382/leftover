<?php

namespace App\Services;

use App\Models\Count;
use App\Models\DaySheet;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Turns the last few weeks of counting into one short message.
 *
 * The rule the wording follows: say the change, then the number, then why.
 * "Five fewer croissants, so forty-five. You had six left on each of the last
 * three Tuesdays." Anyone can act on that while the oven heats. Nobody acts on
 * "croissant demand index −11%".
 *
 * Products with nothing worth saying are listed at the end under their own
 * heading rather than padded out with invented numbers, because a plan that
 * admits what it does not know is one a baker can keep trusting in week three.
 */
class PlanBuilder
{
    public function __construct(private readonly Forecaster $forecaster)
    {
    }

    public function build(User $user, CarbonInterface $forDate, bool $persist = true): Plan
    {
        $weekday = (int) $forDate->isoWeekday();
        $products = $user->activeProducts()->get();
        $history = $this->history($user, $weekday, $products->pluck('id')->all());

        $plan = $persist
            ? $user->plans()->firstOrNew(['for_date' => $forDate->toDateString()])
            : new Plan(['for_date' => $forDate->toDateString()]);

        $plan->weekday = $weekday;
        $plan->status ??= Plan::DRAFT;

        $rows = [];

        foreach ($products as $product) {
            $observations = ($history[$product->id] ?? collect())
                ->map(fn (Count $count) => $count->toObservation());

            $suggestion = $this->forecaster->suggest(
                observations: $observations,
                forDate: $forDate,
                opensAt: $user->opensAt(),
                closesAt: $user->closesAt(),
                roundTo: (int) $product->round_to,
                fallbackBatch: $product->typical_batch,
            );

            $rows[$product->id] = [
                'product_id' => $product->id,
                'suggested_qty' => $suggestion->quantity,
                'previous_qty' => $suggestion->previous,
                'delta' => $suggestion->delta(),
                'observations' => $suggestion->observations,
                'confidence' => $suggestion->confidence,
                'reason' => $suggestion->reason,
                'avg_left' => $suggestion->averageLeft,
                'sold_out_days' => $suggestion->soldOutDays,
            ];
        }

        if (! $persist) {
            $plan->setRelation('lines', collect($rows)->map(function (array $row) use ($products) {
                $line = new \App\Models\PlanLine($row);
                $line->setRelation('product', $products->firstWhere('id', $row['product_id']));

                return $line;
            })->values());

            $plan->body = $this->compose($user, $plan);

            return $plan;
        }

        DB::transaction(function () use ($plan, $rows, $user, $products) {
            $plan->save();

            foreach ($rows as $row) {
                $plan->lines()->updateOrCreate(['product_id' => $row['product_id']], $row);
            }

            $plan->load(['lines.product']);
            $plan->body = $this->compose($user, $plan);
            $plan->save();
        });

        return $plan->fresh(['lines.product']);
    }

    /**
     * The same weekday, counted days only, newest first.
     *
     * Days nobody counted are simply not here. That is the difference between a
     * forgiving product and a punishing one: a missed Thursday makes the sample
     * smaller, it does not make Thursday look like a day of zero sales.
     *
     * @return array<int, \Illuminate\Support\Collection<int, Count>>
     */
    private function history(User $user, int $weekday, array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $counts = Count::query()
            ->with('daySheet')
            ->whereIn('product_id', $productIds)
            ->whereHas('daySheet', function ($query) use ($user, $weekday) {
                $query->where('user_id', $user->id)
                    ->where('weekday', $weekday)
                    ->where('status', DaySheet::COUNTED);
            })
            ->get()
            ->sortByDesc(fn (Count $count) => $count->daySheet->on_date->timestamp);

        return $counts->groupBy('product_id')->all();
    }

    /**
     * The message itself, kept verbatim on the plan once written.
     *
     * Always composed in the shop's own language, never the application's. The
     * scheduler runs with whatever locale the server booted in, and a plan that
     * arrives in English at an Albanian bakery because a cron job ran is the
     * kind of bug nobody reports and everybody notices.
     */
    public function compose(User $user, Plan $plan): string
    {
        $was = app()->getLocale();
        app()->setLocale($user->locale ?: $was);

        try {
            return $this->composeLines($user, $plan);
        } finally {
            app()->setLocale($was);
        }
    }

    private function composeLines(User $user, Plan $plan): string
    {
        $lines = [];
        $lines[] = $user->displayName();
        $lines[] = __('plan.heading', [
            'weekday' => $plan->for_date->locale($user->locale)->translatedFormat('l'),
            'date' => $plan->for_date->format('d/m'),
        ]);

        $changes = $plan->changes();
        if ($changes->isNotEmpty()) {
            $lines[] = '';
            $lines[] = __('plan.section.changes');
            foreach ($changes as $line) {
                $lines[] = '• '.$line->sentence();
            }
        }

        $unchanged = $plan->unchanged();
        if ($unchanged->isNotEmpty()) {
            $lines[] = '';
            $lines[] = __('plan.section.same').' '.$unchanged
                ->map(fn ($line) => $line->product?->name.' '.$line->suggested_qty)
                ->implode(', ');
        }

        $silent = $plan->silent();
        if ($silent->isNotEmpty()) {
            $lines[] = '';
            $lines[] = __('plan.section.unknown').' '.$silent
                ->map(fn ($line) => $line->product?->name)
                ->implode(', ');
        }

        $lines[] = '';
        $lines[] = __('plan.footer');

        return implode("\n", $lines);
    }

    /** What the same product did on the last few of this weekday, for the screen. */
    public function recentFor(Product $product, int $weekday, int $limit = 6): \Illuminate\Support\Collection
    {
        return Count::query()
            ->with('daySheet')
            ->where('product_id', $product->id)
            ->whereHas('daySheet', fn ($query) => $query->where('weekday', $weekday)->where('status', DaySheet::COUNTED))
            ->get()
            ->sortByDesc(fn (Count $count) => $count->daySheet->on_date->timestamp)
            ->take($limit)
            ->values();
    }
}
