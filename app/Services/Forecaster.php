<?php

namespace App\Services;

use App\Support\Observation;
use App\Support\Suggestion;
use Carbon\CarbonInterface;

/**
 * Tomorrow's number, from the last few of the same weekday.
 *
 * There is no machine learning here and there should not be. A bakery has
 * twenty to forty products and maybe forty observations each; anything clever
 * would be fitting noise. What it does have is one idea that the clever systems
 * are sold on and that a spreadsheet never captures:
 *
 *   A sell-out is not a good day.
 *
 * If the shelf emptied at ten in the morning, the number sold is not the number
 * wanted — it is a floor, and everyone who came at eleven is invisible. So a
 * sold-out day's demand is lifted, in proportion to how much of the trading day
 * was spent with nothing to sell. That is the entire difference between this
 * and averaging the till.
 *
 * Two safety rails matter as much as the arithmetic:
 *
 *   Nothing is said until there is something to say. Under two counted days of
 *   the same weekday, the line is silent rather than confident.
 *
 *   No suggestion may move production by more than a fifth. Three weeks of data
 *   is not entitled to tell someone to halve their Saturday. It is a nudge, and
 *   a nudge that gets to be right slowly is worth more than an order that is
 *   spectacularly wrong once.
 */
class Forecaster
{
    /** How far back to look. Beyond about two months the shop has changed. */
    public const HISTORY_WEEKS = 8;

    /** Below this many counted days of the same weekday, say nothing. */
    public const MIN_OBSERVATIONS = 2;

    /** A suggestion may never move production by more than this fraction. */
    public const MAX_MOVE_FRACTION = 0.20;

    /** The most a sell-out may inflate a day's demand. */
    public const MAX_SELLOUT_UPLIFT = 0.30;

    /** Each week back counts for this much of the week in front of it. */
    public const RECENCY_DECAY = 0.85;

    /**
     * @param  iterable<Observation>  $observations  counted days of the same weekday, any order
     */
    public function suggest(
        iterable $observations,
        CarbonInterface $forDate,
        string $opensAt = '06:00',
        string $closesAt = '20:00',
        int $roundTo = 1,
        ?int $fallbackBatch = null,
    ): Suggestion {
        $days = collect($observations)
            ->filter(fn (Observation $o) => $o->date->lt($forDate))
            ->sortByDesc(fn (Observation $o) => $o->date->timestamp)
            ->take(self::HISTORY_WEEKS)
            ->values();

        $previous = $days->first()?->baked ?? $fallbackBatch;

        if ($days->count() < self::MIN_OBSERVATIONS) {
            return new Suggestion(
                quantity: null,
                previous: $previous,
                observations: $days->count(),
                confidence: 'none',
                reason: 'not_enough_yet',
            );
        }

        $weightedDemand = 0.0;
        $weightTotal = 0.0;
        $leftTotal = 0;
        $soldOutDays = 0;

        foreach ($days as $index => $day) {
            $uplift = 1.0;

            if ($day->soldOut()) {
                $soldOutDays++;
                // Empty shelf early in the day means a lot of unserved demand;
                // empty at closing time means almost none.
                $uplift = 1.0 + self::MAX_SELLOUT_UPLIFT * (1.0 - $day->sellOutFraction($opensAt, $closesAt));
            }

            $weight = self::RECENCY_DECAY ** $index;
            $weightedDemand += $day->sold() * $uplift * $weight;
            $weightTotal += $weight;
            $leftTotal += $day->left;
        }

        $target = $weightTotal > 0 ? $weightedDemand / $weightTotal : 0.0;
        $quantity = (int) round($target);

        // The nudge rail.
        $capped = false;
        if ($previous !== null && $previous > 0) {
            $room = max(1, (int) round($previous * self::MAX_MOVE_FRACTION));
            $bounded = max($previous - $room, min($previous + $room, $quantity));
            $capped = $bounded !== $quantity;
            $quantity = $bounded;
        }

        $quantity = $this->roundToPack(max(0, $quantity), $roundTo);
        $averageLeft = $days->count() > 0 ? $leftTotal / $days->count() : 0.0;

        return new Suggestion(
            quantity: $quantity,
            previous: $previous,
            observations: $days->count(),
            confidence: $this->confidence($days->count()),
            reason: $this->reason($quantity, $previous, $averageLeft, $soldOutDays, $days->count()),
            averageLeft: round($averageLeft, 2),
            soldOutDays: $soldOutDays,
            rawTarget: round($target, 2),
            capped: $capped,
        );
    }

    /** Things come out of an oven in trays. Respect the tray. */
    private function roundToPack(int $quantity, int $roundTo): int
    {
        if ($roundTo < 2) {
            return $quantity;
        }

        return (int) max($roundTo, round($quantity / $roundTo) * $roundTo);
    }

    private function confidence(int $observations): string
    {
        return match (true) {
            $observations >= 6 => 'good',
            $observations >= 4 => 'fair',
            $observations >= self::MIN_OBSERVATIONS => 'low',
            default => 'none',
        };
    }

    /**
     * Why, in one key. The screen has to be able to say what it noticed, or the
     * number is just an oracle and nobody follows an oracle for long.
     */
    private function reason(int $quantity, ?int $previous, float $averageLeft, int $soldOutDays, int $observations): string
    {
        $delta = $previous === null ? 0 : $quantity - $previous;

        if ($soldOutDays >= max(2, (int) ceil($observations / 2)) && $delta >= 0) {
            return 'sold_out_often';
        }

        if ($delta === 0) {
            return $averageLeft >= 1 ? 'steady_with_waste' : 'steady';
        }

        if ($delta < 0) {
            return $averageLeft >= 1 ? 'left_over' : 'demand_down';
        }

        return $soldOutDays > 0 ? 'sold_out_sometimes' : 'demand_up';
    }
}
