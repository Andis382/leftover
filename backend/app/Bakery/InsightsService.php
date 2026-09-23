<?php

namespace App\Bakery;

use App\Forecast\ForecastService;
use App\Forecast\Observation;
use App\Forecast\ReasonText;
use App\Models\DailyRecord;
use App\Models\DayClosing;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * The honest history: how much was left, what it cost, what sold out, and whether it is getting
 * better. Only counted days are measured; skipped and missed days are shown, never guessed.
 */
final class InsightsService
{
    public const WEEKS = 10;

    public const CALENDAR_DAYS = 35;

    public function __construct(private readonly ForecastService $forecast) {}

    public function build(ShopClock $clock, int $days, string $locale): array
    {
        $today = CarbonImmutable::parse($clock->today());
        $from = $today->subDays($days - 1);
        $previousFrom = $from->subDays($days);
        $weeksFrom = $today->startOfWeek()->subWeeks(self::WEEKS - 1);
        $calendarFrom = $today->startOfWeek()->subDays(self::CALENDAR_DAYS - 7);
        $earliest = min($previousFrom, $weeksFrom, $calendarFrom)->toDateString();

        $closings = DayClosing::where('date', '>=', $earliest)->get()->keyBy(fn (DayClosing $c) => $c->date->toDateString());
        $products = Product::shelf()->get()->keyBy('id');
        $records = DailyRecord::where('date', '>=', $earliest)->where('date', '<=', $today->toDateString())
            ->whereNotNull('left_qty')->orderByDesc('date')->get()
            ->filter(fn (DailyRecord $r) => $this->measured($r, $closings, $today->toDateString()) && $products->has($r->product_id));

        $current = $this->between($records, $from, $today);
        $previous = $this->between($records, $previousFrom, $from->subDay());

        return [
            'days' => $days,
            'from' => $from->toDateString(),
            'to' => $today->toDateString(),
            'current' => $this->totals($current, $products, $clock),
            'previous' => $this->totals($previous, $products, $clock),
            'daily' => $this->daily($current, $closings, $clock, $from, $today),
            'weekly' => $this->weekly($records, $products, $weeksFrom),
            'products' => $this->perProduct($current, $previous, $products, $days),
            'patterns' => $this->patterns($records, $products, $clock, $today, $locale),
            'sellouts' => $current->filter(fn (DailyRecord $r) => $r->sold_out_at !== null)->take(12)->map(fn (DailyRecord $r) => [
                'date' => $r->date->toDateString(),
                'time' => $r->soldOutTime(),
                'productId' => $r->product_id,
                'name' => $products[$r->product_id]->name,
            ])->values()->all(),
            'calendar' => $this->calendar($closings, $records, $clock, $calendarFrom, $today),
        ];
    }

    /** Counted, actually baked, on a day that was not skipped, and not today's count still under way. */
    private function measured(DailyRecord $record, Collection $closings, string $today): bool
    {
        $day = $record->date->toDateString();
        $status = $closings->get($day)?->status;
        if ($status === DayClosing::SKIPPED || ($record->effectiveBaked() ?? 0) <= 0) {
            return false;
        }

        return $day < $today || $status === DayClosing::COUNTED;
    }

    private function between(Collection $records, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $a = $from->toDateString();
        $b = $to->toDateString();

        return $records->filter(fn (DailyRecord $r) => ($d = $r->date->toDateString()) >= $a && $d <= $b)->values();
    }

    private function totals(Collection $records, Collection $products, ShopClock $clock): array
    {
        $totals = ['bakedUnits' => 0, 'soldUnits' => 0, 'leftUnits' => 0, 'wasteCents' => 0, 'soldOuts' => 0, 'missedCents' => 0, 'countedDays' => 0, 'wastePct' => null];
        foreach ($records as $record) {
            $product = $products[$record->product_id];
            $baked = $record->effectiveBaked();
            $left = min($record->left_qty, $baked);
            $totals['bakedUnits'] += $baked;
            $totals['soldUnits'] += $baked - $left;
            $totals['leftUnits'] += $left;
            $totals['wasteCents'] += $left * $product->wasteValueCents();
            if ($record->sold_out_at !== null) {
                $totals['soldOuts']++;
                $demand = $this->forecast->demand($this->observation($record, $clock));
                $totals['missedCents'] += (int) round(max(0, $demand - $baked) * $product->unit_price_cents);
            }
        }
        $totals['countedDays'] = $records->map(fn (DailyRecord $r) => $r->date->toDateString())->unique()->count();
        $totals['wastePct'] = $totals['bakedUnits'] > 0 ? round($totals['leftUnits'] / $totals['bakedUnits'], 4) : null;

        return $totals;
    }

    private function daily(Collection $records, Collection $closings, ShopClock $clock, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $byDay = $records->groupBy(fn (DailyRecord $r) => $r->date->toDateString());
        $days = [];
        for ($day = $from; $day <= $to; $day = $day->addDay()) {
            $key = $day->toDateString();
            $dayRecords = $byDay->get($key, collect());
            $sold = $dayRecords->sum(fn (DailyRecord $r) => max(0, $r->effectiveBaked() - $r->left_qty));
            $days[] = [
                'date' => $key,
                'status' => $this->status($key, $closings, $dayRecords->isNotEmpty(), $clock, $to->toDateString()),
                'sold' => $sold,
                'left' => $dayRecords->sum(fn (DailyRecord $r) => min($r->left_qty, $r->effectiveBaked())),
                'soldOuts' => $dayRecords->whereNotNull('sold_out_at')->count(),
            ];
        }

        return $days;
    }

    /** Waste as a share of production, week by week: the line that should go down. */
    private function weekly(Collection $records, Collection $products, CarbonImmutable $from): array
    {
        $weeks = [];
        for ($i = 0; $i < self::WEEKS; $i++) {
            $start = $from->addWeeks($i);
            $weeks[$start->toDateString()] = ['weekStart' => $start->toDateString(), 'bakedUnits' => 0, 'leftUnits' => 0, 'wasteCents' => 0, 'wastePct' => null];
        }
        foreach ($records as $record) {
            $key = $record->date->startOfWeek()->toDateString();
            if (! isset($weeks[$key])) {
                continue;
            }
            $left = min($record->left_qty, $record->effectiveBaked());
            $weeks[$key]['bakedUnits'] += $record->effectiveBaked();
            $weeks[$key]['leftUnits'] += $left;
            $weeks[$key]['wasteCents'] += $left * $products[$record->product_id]->wasteValueCents();
        }
        foreach ($weeks as &$week) {
            $week['wastePct'] = $week['bakedUnits'] > 0 ? round($week['leftUnits'] / $week['bakedUnits'], 4) : null;
        }

        return array_values($weeks);
    }

    private function perProduct(Collection $current, Collection $previous, Collection $products, int $days): array
    {
        $now = $current->groupBy('product_id');
        $before = $previous->groupBy('product_id');
        $rows = [];
        foreach ($products as $product) {
            $records = $now->get($product->id);
            if ($records === null) {
                continue;
            }
            $counted = $records->count();
            $left = $records->sum(fn (DailyRecord $r) => min($r->left_qty, $r->effectiveBaked()));
            $baked = $records->sum(fn (DailyRecord $r) => $r->effectiveBaked());
            $avgLeft = $left / $counted;
            $earlier = $before->get($product->id);
            $avgBefore = $earlier === null ? null : $earlier->sum(fn (DailyRecord $r) => min($r->left_qty, $r->effectiveBaked())) / $earlier->count();
            $rows[] = [
                'productId' => $product->id,
                'name' => $product->name,
                'category' => $product->category,
                'countedDays' => $counted,
                'avgLeft' => round($avgLeft, 1),
                'wastePct' => $baked > 0 ? round($left / $baked, 4) : null,
                'soldOutDays' => $records->whereNotNull('sold_out_at')->count(),
                'wasteCents' => $left * $product->wasteValueCents(),
                'previousAvgLeft' => $avgBefore === null ? null : round($avgBefore, 1),
                'trend' => $this->trend($avgLeft, $avgBefore),
            ];
        }
        usort($rows, fn (array $a, array $b) => $b['wasteCents'] <=> $a['wasteCents']);

        return $rows;
    }

    private function trend(float $now, ?float $before): string
    {
        if ($before === null) {
            return 'new';
        }
        $difference = $now - $before;
        if (abs($difference) < max(0.5, $before * 0.1)) {
            return 'flat';
        }

        return $difference < 0 ? 'down' : 'up';
    }

    /**
     * "Simite me susam sold out before 11:00 on 3 of the last 4 Saturdays": products that ran out
     * on at least two of the last four counted same weekdays.
     */
    private function patterns(Collection $records, Collection $products, ShopClock $clock, CarbonImmutable $today, string $locale): array
    {
        $patterns = [];
        foreach ($records->groupBy(fn (DailyRecord $r) => $r->product_id.'-'.$r->date->isoWeekday()) as $group) {
            $recent = $group->take(4);
            $soldOut = $recent->whereNotNull('sold_out_at');
            $first = $group->first();
            if ($soldOut->count() < 2 || ! $products[$first->product_id]->isActive()) {
                continue;
            }
            $weekday = $first->date->isoWeekday();
            $latest = $soldOut->max(fn (DailyRecord $r) => ShopClock::minutes($r->sold_out_at));
            $late = ForecastService::inLastHour($latest, $clock->closesMinute($weekday));
            $before = ForecastService::clock((intdiv($latest, 60) + 1) * 60);
            $params = [
                'product' => $products[$first->product_id]->name,
                'before' => $before,
                'times' => $soldOut->count(),
                'of' => $recent->count(),
                'weekdays' => ReasonText::weekday('plural', $weekday, $locale),
            ];
            $patterns[] = [
                'productId' => $first->product_id,
                'weekday' => $weekday,
                'times' => $soldOut->count(),
                'of' => $recent->count(),
                'before' => $late ? null : $before,
                'text' => trans($late ? 'insights.pattern_late' : 'insights.pattern', $params, $locale),
            ];
        }
        // Early sell-outs first (the lost sales worth fixing), then the more frequent.
        usort($patterns, fn (array $a, array $b) => [$a['before'] === null, $a['before'], $b['times'] / $b['of']] <=> [$b['before'] === null, $b['before'], $a['times'] / $a['of']]);

        return array_slice($patterns, 0, 8);
    }

    private function calendar(Collection $closings, Collection $records, ShopClock $clock, CarbonImmutable $from, CarbonImmutable $today): array
    {
        $counted = $records->map(fn (DailyRecord $r) => $r->date->toDateString())->flip();
        $days = [];
        for ($day = $from; $day < $from->addDays(self::CALENDAR_DAYS); $day = $day->addDay()) {
            $key = $day->toDateString();
            $days[] = ['date' => $key, 'status' => $this->status($key, $closings, $counted->has($key), $clock, $today->toDateString())];
        }

        return $days;
    }

    /** COUNTED, SKIPPED, MISSED (open, past, nothing counted), CLOSED, TODAY or FUTURE. */
    private function status(string $day, Collection $closings, bool $hasCounts, ShopClock $clock, string $today): string
    {
        $closing = $closings->get($day);
        if ($closing?->status === DayClosing::SKIPPED) {
            return 'SKIPPED';
        }
        if ($closing?->status === DayClosing::COUNTED || ($hasCounts && $day < $today)) {
            return 'COUNTED';
        }
        if ($day > $today) {
            return 'FUTURE';
        }
        if ($day === $today) {
            return 'TODAY';
        }

        return $clock->isOpenOn(ShopClock::weekdayOf($day)) ? 'MISSED' : 'CLOSED';
    }

    private function observation(DailyRecord $record, ShopClock $clock): Observation
    {
        $weekday = $record->date->isoWeekday();

        return new Observation(
            $record->date->toDateString(),
            $record->effectiveBaked(),
            $record->left_qty,
            ShopClock::minutes($record->sold_out_at),
            $clock->opensMinute($weekday),
            $clock->closesMinute($weekday),
        );
    }
}
