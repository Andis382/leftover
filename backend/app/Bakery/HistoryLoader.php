<?php

namespace App\Bakery;

use App\Forecast\ForecastService;
use App\Forecast\Observation;
use App\Models\DailyRecord;
use App\Models\DayClosing;
use App\Models\Product;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Reads the same-weekday history the forecast learns from, for many products in two queries.
 * A day counts as an observation only if it was baked and counted and not explicitly skipped.
 */
final class HistoryLoader
{
    /** How far back to look for six counted same weekdays. */
    public const WEEKS_BACK = 26;

    /**
     * @param  Collection<int, Product>  $products
     * @return array<int, array{observations: list<Observation>, lastBaked: ?int}>
     */
    public function load(ShopClock $clock, Collection $products, string $date): array
    {
        $target = CarbonImmutable::parse($date);
        $weekday = $target->isoWeekday();
        $dates = array_map(fn (int $w) => $target->subWeeks($w)->toDateString(), range(1, self::WEEKS_BACK));

        $skipped = DayClosing::whereIn('date', $dates)->where('status', DayClosing::SKIPPED)->get()
            ->mapWithKeys(fn (DayClosing $d) => [$d->date->toDateString() => true]);
        $records = DailyRecord::whereIn('product_id', $products->modelKeys())->whereIn('date', $dates)
            ->orderByDesc('date')->get()->groupBy('product_id');

        $opens = $clock->opensMinute($weekday);
        $closes = $clock->closesMinute($weekday);
        $history = [];
        foreach ($products as $product) {
            $observations = [];
            $lastBaked = null;
            foreach ($records->get($product->id, collect()) as $record) {
                $baked = $record->effectiveBaked();
                if ($baked === null) {
                    continue;
                }
                $lastBaked ??= $baked;
                $day = $record->date->toDateString();
                if ($baked === 0 || ! $record->isCounted() || isset($skipped[$day]) || count($observations) >= count(ForecastService::WEIGHTS)) {
                    continue;
                }
                $observations[] = new Observation(
                    $day,
                    $baked,
                    min($record->left_qty, $baked),
                    ShopClock::minutes($record->sold_out_at),
                    $opens,
                    $closes,
                );
            }
            $history[$product->id] = ['observations' => $observations, 'lastBaked' => $lastBaked];
        }

        return $history;
    }
}
