<?php

namespace Database\Seeders;

use App\Bakery\PlanSender;
use App\Bakery\PlanService;
use App\Bakery\ShopClock;
use App\Messaging\Messenger;
use App\Models\DailyRecord;
use App\Models\DayClosing;
use App\Models\Invitation;
use App\Models\Organization;
use App\Models\OutboundMessage;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ShopHour;
use App\Models\User;
use App\Support\Tenant;
use App\Support\Tokens;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Furra e Lagjes, a neighbourhood bakery in Blloku, ten weeks after it started counting.
 *
 * Every past day is replayed: at 04:00 the real forecast makes the plan from the counts so far,
 * then a hidden true demand (Saturdays strong, Mondays weak) decides what sold, what was left
 * and when things ran out. For four weeks the baker only counted and baked by habit (Monday
 * leftovers, Saturday sell-outs before eleven); since then he follows the plan, and the last
 * weeks show it correcting itself.
 */
class DemoSeeder extends Seeder
{
    /** Whole weeks of history before the current one. */
    private const WEEKS = 10;

    /** Weeks the baker only counted and kept baking by habit, before following the plan. */
    private const HABIT_WEEKS = 4;

    /** name, category, price, cost (cents), tray, usual pieces a day, weekdays baked, demand pattern */
    private const PRODUCTS = [
        ['Bukë e bardhë', 'BREAD', 80, 28, 10, 120, [1, 2, 3, 4, 5, 6, 7], 'bread'],
        ['Bukë fshati', 'BREAD', 130, 48, 6, 36, [1, 2, 3, 4, 5, 6, 7], 'bread'],
        ['Bukë misri', 'BREAD', 100, 34, 6, 24, [1, 2, 3, 4, 5, 6, 7], 'bread'],
        ['Bukë integrale', 'BREAD', 120, 44, 6, 30, [1, 2, 3, 4, 5, 6], 'bread'],
        ['Bukë me ullinj', 'BREAD', 150, 62, 4, 16, [1, 2, 3, 4, 5, 6], 'bread'],
        ['Simite me susam', 'BREAD', 40, 14, 12, 144, [1, 2, 3, 4, 5, 6, 7], 'rush'],
        ['Bagetë', 'BREAD', 70, 24, 8, 48, [1, 2, 3, 4, 5, 6, 7], 'bread'],
        ['Kroasan i thjeshtë', 'PASTRY', 70, 26, 12, 84, [1, 2, 3, 4, 5, 6, 7], 'rush'],
        ['Kroasan me çokollatë', 'PASTRY', 90, 34, 12, 72, [1, 2, 3, 4, 5, 6, 7], 'rush'],
        ['Kanellë', 'PASTRY', 90, 32, 6, 30, [1, 2, 3, 4, 5, 6, 7], 'sweet'],
        ['Pogaçe', 'PASTRY', 50, 17, 12, 72, [1, 2, 3, 4, 5, 6, 7], 'bread'],
        ['Brioshe me marmelatë', 'PASTRY', 80, 30, 6, 36, [1, 2, 3, 4, 5, 6, 7], 'sweet'],
        ['Byrek me spinaq', 'SAVORY', 120, 44, 8, 64, [1, 2, 3, 4, 5, 6, 7], 'rush'],
        ['Byrek me gjizë', 'SAVORY', 120, 46, 8, 64, [1, 2, 3, 4, 5, 6, 7], 'rush'],
        ['Byrek me mish', 'SAVORY', 150, 64, 8, 40, [1, 2, 3, 4, 5, 6], 'bread'],
        ['Pite me qepë', 'SAVORY', 100, 34, 6, 24, [1, 2, 3, 4, 5, 6], 'bread'],
        ['Lakror me presh', 'SAVORY', 130, 50, 6, 24, [4, 5, 6], 'bread'],
        ['Petulla', 'SAVORY', 30, 8, 10, 100, [1, 2, 3, 4, 5, 6, 7], 'rush'],
        ['Pica copë', 'SAVORY', 100, 38, 6, 42, [1, 2, 3, 4, 5, 6], 'bread'],
        ['Ëmbëlsirë me arra', 'SWEET', 150, 58, 6, 18, [1, 2, 3, 4, 5, 6, 7], 'sweet'],
        ['Bakllava copë', 'SWEET', 120, 50, 6, 30, [1, 2, 3, 4, 5, 6, 7], 'sweet'],
        ['Kadaif', 'SWEET', 120, 46, 6, 18, [5, 6, 7], 'sweet'],
        ['Revani', 'SWEET', 100, 34, 6, 18, [6, 7], 'sweet'],
        ['Grisina (paketë)', 'OTHER', 60, 20, 5, 25, [1, 2, 3, 4, 5, 6, 7], 'bread'],
    ];

    /** How busy each weekday really is, Monday first. Nobody ever wrote this down. */
    private const DEMAND = [
        'bread' => [0.72, 0.86, 0.9, 0.92, 1.0, 1.3, 0.78],
        'rush' => [0.7, 0.85, 0.88, 0.9, 1.04, 2.3, 1.0],
        'sweet' => [0.6, 0.8, 0.84, 0.9, 1.1, 1.5, 1.3],
    ];

    /** The old habit: the same numbers every day, a little less on Sunday. */
    private const HABIT = [1, 1, 1, 1, 1, 1, 0.75];

    /** Share of a day's customers who have come in by each point of the opening hours. */
    private const ARRIVALS = [[0, 0], [0.107, 0.17], [0.179, 0.29], [0.25, 0.4], [0.321, 0.46], [0.393, 0.51], [0.464, 0.55], [0.607, 0.66], [0.75, 0.79], [1, 1]];

    /** Days ago => why the count was skipped. */
    private const SKIPPED = [45 => 'Festë zyrtare, mbyllëm herët', 26 => 'Harruam të numëronim', 12 => 'Erisa mungonte, s\'kishte kush të numëronte'];

    /** Days ago the count was simply never done (the reminder went out, nobody counted). */
    private const MISSED = 19;

    /** Days ago the count came in late, after the WhatsApp reminder. */
    private const LATE = [33, 5];

    public function run(PlanService $plans, PlanSender $sender, Messenger $messenger): void
    {
        if (User::where('email', config('product.demo_email'))->exists()) {
            return;
        }
        mt_srand(5150);

        $org = Organization::create([
            'name' => 'Furra e Lagjes',
            'phone' => '355692345678',
            'locale' => 'sq',
            'timezone' => 'Europe/Tirane',
            'currency' => 'EUR',
            'plan_time' => '04:00',
            'count_reminder_offset' => 15,
            'plan_phone' => '355692345678',
        ]);
        $owner = User::create([
            'organization_id' => $org->id,
            'name' => 'Ilir Muça',
            'email' => config('product.demo_email'),
            'password' => config('product.demo_password'),
            'role' => User::OWNER,
            'locale' => 'sq',
            'phone' => '355692345678',
        ]);
        $staff = User::create([
            'organization_id' => $org->id,
            'name' => 'Erisa Hoxha',
            'email' => 'staff@leftover.test',
            'password' => config('product.demo_password'),
            'role' => User::STAFF,
            'locale' => 'sq',
            'phone' => '355683456789',
        ]);

        Tenant::run($org->id, function () use ($org, $owner, $staff, $plans, $sender, $messenger) {
            Invitation::create([
                'token' => Tokens::urlToken(),
                'role' => User::STAFF,
                'name' => 'Blerina Kola',
                'invited_by' => $owner->id,
                'expires_at' => now()->addDays(11),
            ]);
            $this->hours($org);
            $products = $this->products();
            $clock = ShopClock::for($org);
            $today = CarbonImmutable::parse($clock->today());
            $start = $today->startOfWeek()->subWeeks(self::WEEKS);
            $followsPlanFrom = $start->addWeeks(self::HABIT_WEEKS);

            for ($day = $start; $day < $today; $day = $day->addDay()) {
                $ago = (int) $day->diffInDays($today);
                $this->pastDay($day, $ago, $day >= $followsPlanFrom, $products, $clock, $plans, $sender, $messenger, $owner, $staff);
            }
            $this->today($today, $clock, $plans, $sender, $owner);
        });

        $this->command?->info('Demo data ready. Sign in with '.config('product.demo_email').' / '.config('product.demo_password').' (staff: staff@leftover.test)');
    }

    private function hours(Organization $org): void
    {
        foreach (range(1, 7) as $weekday) {
            ShopHour::create([
                'organization_id' => $org->id,
                'weekday' => $weekday,
                'opens_at' => $weekday === 7 ? '07:00' : '06:30',
                'closes_at' => $weekday === 7 ? '14:00' : '20:30',
            ]);
        }
    }

    /** @return Collection<int, array{product: Product, usual: int, pattern: string}> */
    private function products(): Collection
    {
        return collect(self::PRODUCTS)->map(function (array $p, int $i) {
            [$name, $category, $price, $cost, $tray, $usual, $weekdays, $pattern] = $p;
            $product = Product::create([
                'name' => $name,
                'category' => $category,
                'unit_price_cents' => $price,
                'unit_cost_cents' => $cost,
                'tray_size' => $tray,
                'baselines' => array_map(fn (float $h) => (int) (ceil($usual * $h / $tray) * $tray), self::HABIT),
                'active_weekdays' => $weekdays,
                'shelf_order' => $i + 1,
            ]);

            return ['product' => $product, 'usual' => $usual, 'pattern' => $pattern];
        })->keyBy(fn (array $p) => $p['product']->id);
    }

    private function pastDay(CarbonImmutable $day, int $ago, bool $followsPlan, Collection $products, ShopClock $clock, PlanService $plans, PlanSender $sender, Messenger $messenger, User $owner, User $staff): void
    {
        $date = $day->toDateString();
        $weekday = $day->isoWeekday();
        $plan = $plans->generate($clock, $date);
        $records = DailyRecord::where('date', $date)->get();

        // The first weeks Ilir only counted and kept baking his usual numbers; since then he
        // follows the plan, now and then trusting his nose over it the evening before.
        $overridden = false;
        foreach ($records as $record) {
            $product = $products[$record->product_id]['product'];
            if (! $followsPlan) {
                $usual = $product->baselineFor($weekday);
                $record->baked_qty = $usual === $record->planned_qty ? null : $usual;
                $record->save();
            } elseif (mt_rand(1, 100) <= 4) {
                $record->baked_qty = max(0, $record->planned_qty + (mt_rand(0, 1) ? $product->tray_size : -$product->tray_size));
                $record->save();
                $overridden = true;
            }
        }
        $plan->forceFill([
            'generated_at' => $overridden ? $this->at($day->subDay(), 21 * 60 + mt_rand(0, 50)) : $this->at($day, 4 * 60),
            'generated_by' => $overridden ? $owner->id : null,
        ])->save();
        $this->send($sender, $clock, $plan, $this->at($day, 4 * 60));
        if (mt_rand(1, 100) <= 88) {
            $plan->forceFill(['baked_confirmed_at' => $this->at($day, 5 * 60 + mt_rand(2, 25)), 'baked_confirmed_by' => $owner->id])->save();
        }

        $opens = $clock->opensMinute($weekday);
        $closes = $clock->closesMinute($weekday);
        $skipReason = self::SKIPPED[$ago] ?? null;
        $counted = $skipReason === null && $ago !== self::MISSED;
        $late = in_array($ago, self::LATE, true);
        $countStart = $closes + ($late ? 22 : mt_rand(4, 12));
        $counter = mt_rand(1, 100) <= 80 ? $staff : $owner;
        $mood = $this->gauss(1.0, 0.04);

        foreach ($records as $i => $record) {
            $info = $products[$record->product_id];
            $baked = $record->effectiveBaked();
            $demand = max(0, (int) round($info['usual'] * self::DEMAND[$info['pattern']][$weekday - 1] * 0.97 * $mood * $this->gauss(1.0, 0.06)));
            if (! $counted || $baked === 0) {
                continue;
            }
            $soldOutMinute = null;
            if ($demand >= $baked) {
                $share = $demand === 0 ? 1.0 : $baked / $demand;
                $soldOutMinute = min($closes - 5, $opens + (int) (round($this->arrivalFraction($share) * ($closes - $opens) / 5) * 5));
            }
            $record->fill([
                'left_qty' => max(0, $baked - $demand),
                'sold_out_at' => $soldOutMinute === null ? null : ShopClock::clock($soldOutMinute),
                'counted_by' => $counter->id,
                'counted_at' => $this->at($day, $countStart + intdiv($i, 3)),
            ])->save();
        }

        $closing = match (true) {
            $skipReason !== null => DayClosing::create(['date' => $date, 'status' => DayClosing::SKIPPED, 'skip_reason' => $skipReason, 'closed_by' => $owner->id, 'closed_at' => $this->at($day, $closes + 40)]),
            $counted => DayClosing::create(['date' => $date, 'status' => DayClosing::COUNTED, 'closed_by' => $counter->id, 'closed_at' => $this->at($day, $countStart + 9)]),
            default => DayClosing::create(['date' => $date, 'status' => DayClosing::OPEN]),
        };
        if ($late || $ago === self::MISSED) {
            $remindedAt = $this->at($day, $closes + 15);
            $closing->forceFill(['reminded_at' => $remindedAt])->save();
            $name = strtok($staff->name, ' ');
            $message = $messenger->send($clock->organization->id, $staff->phone, $name, 'count_reminder', $staff->locale, ['name' => $name, 'time' => ShopClock::clock($closes)], config('product.public_url').'/count', 'day_closing', $closing->id);
            $this->backdate($message, $remindedAt);
        }
    }

    /** Today: the 04:00 plan and the 05:00 confirmation, if the clock has got that far. */
    private function today(CarbonImmutable $today, ShopClock $clock, PlanService $plans, PlanSender $sender, User $owner): void
    {
        $date = $today->toDateString();
        $minute = $clock->minuteOfDay();
        if ($minute < 4 * 60) {
            return;
        }
        $plan = $plans->generate($clock, $date);
        $plan->forceFill(['generated_at' => $this->at($today, 4 * 60)])->save();
        $this->send($sender, $clock, $plan, $this->at($today, 4 * 60));
        if ($minute < 5 * 60 + 30) {
            return;
        }
        $baked = DailyRecord::where('date', $date)->whereNotNull('planned_qty')->with('product')->get()
            ->mapWithKeys(fn (DailyRecord $r) => [$r->product_id => $r->product->name === 'Bukë misri' ? max(0, $r->planned_qty - 2) : $r->planned_qty])
            ->all();
        $plans->confirmBaked($clock, $date, $baked, $owner);
        $plan->refresh()->forceFill(['baked_confirmed_at' => $this->at($today, 5 * 60 + 12)])->save();
    }

    private function send(PlanSender $sender, ShopClock $clock, Plan $plan, CarbonImmutable $when): void
    {
        $message = $sender->send($clock, $plan);
        if ($message !== null) {
            $this->backdate($message, $when);
            $plan->forceFill(['sent_at' => $when])->save();
        }
    }

    private function backdate(OutboundMessage $message, CarbonImmutable $when): void
    {
        $message->forceFill(['created_at' => $when, 'updated_at' => $when, 'sent_at' => $when])->save();
    }

    /** A wall-clock minute of a shop day, as an instant. */
    private function at(CarbonImmutable $day, int $minute): CarbonImmutable
    {
        return CarbonImmutable::parse($day->toDateString(), 'Europe/Tirane')->addMinutes($minute)->utc();
    }

    /** When, as a fraction of the opening hours, a given share of the day's customers has come. */
    private function arrivalFraction(float $share): float
    {
        $share = max(0.0, min(1.0, $share));
        for ($i = 1; $i < count(self::ARRIVALS); $i++) {
            [$t1, $s1] = self::ARRIVALS[$i];
            [$t0, $s0] = self::ARRIVALS[$i - 1];
            if ($share <= $s1) {
                return $t0 + ($share - $s0) / ($s1 - $s0) * ($t1 - $t0);
            }
        }

        return 1.0;
    }

    private function gauss(float $mean, float $sd): float
    {
        $u1 = max(mt_rand() / mt_getrandmax(), 1e-9);
        $u2 = mt_rand() / mt_getrandmax();

        return $mean + $sd * sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);
    }
}
