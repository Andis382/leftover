<?php

namespace App\Forecast;

use DateTimeImmutable;

/**
 * Tomorrow's bake number for one product, from the same weekday in past weeks.
 *
 * Demand on a counted day is what sold (baked − left). A sell-out hides demand, so a day that
 * ran out early is scaled up by how much of the day was still ahead. Recent weeks weigh more.
 * With fewer than three counted days the result leans on the baker's usual number, and it is
 * always rounded up to whole trays. Days that were skipped or never counted are simply absent
 * from the input: they are never read as zero.
 */
final class ForecastService
{
    /** Weight of the most recent counted same weekday first. */
    public const WEIGHTS = [0.35, 0.25, 0.17, 0.11, 0.07, 0.05];

    public const MIN_UPLIFT = 1.05;

    public const MAX_UPLIFT = 1.6;

    /** Counted days needed before the usual number stops mattering. */
    public const FULL_HISTORY = 3;

    public function suggest(ForecastInput $input): ?Suggestion
    {
        if (! $input->bakedThatDay) {
            return null;
        }
        $observations = array_slice($input->observations, 0, count(self::WEIGHTS));
        $estimate = $this->estimate($observations, $input->baseline);
        $quantity = $this->roundUpToTray($estimate, $input->traySize);
        $change = $input->lastBaked === null ? null : $quantity - $input->lastBaked;

        return new Suggestion(
            $quantity,
            $estimate,
            $input->lastBaked,
            $this->confidence(count($observations)),
            count($observations),
            $this->reason($observations, $change, $input->date),
        );
    }

    /** @param  list<Observation>  $observations  most recent first, at most six */
    public function estimate(array $observations, int $baseline): float
    {
        $count = count($observations);
        if ($count === 0) {
            return (float) $baseline;
        }
        $average = $this->weightedDemand($observations);
        if ($count >= self::FULL_HISTORY) {
            return $average;
        }
        $share = $count / self::FULL_HISTORY;

        return $share * $average + (1 - $share) * $baseline;
    }

    /** @param  list<Observation>  $observations */
    public function weightedDemand(array $observations): float
    {
        $weights = array_slice(self::WEIGHTS, 0, count($observations));
        $total = array_sum($weights);
        $sum = 0.0;
        foreach (array_values($observations) as $i => $observation) {
            $sum += $weights[$i] * $this->demand($observation);
        }

        return $total > 0 ? $sum / $total : 0.0;
    }

    /** What would have sold on that day had there been enough. */
    public function demand(Observation $observation): float
    {
        $sold = $observation->sold();

        return $observation->soldOut() ? $sold * $this->uplift($observation) : (float) $sold;
    }

    /** Minutes open that day divided by minutes open until the sell-out, kept within bounds. */
    public function uplift(Observation $observation): float
    {
        $opens = $observation->opensMinute;
        $closes = $observation->closesMinute;
        if ($opens === null || $closes === null || $closes <= $opens) {
            return self::MIN_UPLIFT;
        }
        $untilSoldOut = $observation->soldOutMinute - $opens;
        if ($untilSoldOut <= 0) {
            return self::MAX_UPLIFT;
        }

        return max(self::MIN_UPLIFT, min(self::MAX_UPLIFT, ($closes - $opens) / $untilSoldOut));
    }

    public function roundUpToTray(float $estimate, int $traySize): int
    {
        if ($estimate <= 0) {
            return 0;
        }
        $tray = max(1, $traySize);

        // round() first so 36.0000001 / 12 does not become four trays
        return (int) (ceil(round($estimate / $tray, 6)) * $tray);
    }

    public function confidence(int $observations): string
    {
        return match (true) {
            $observations >= 5 => Suggestion::HIGH,
            $observations >= 3 => Suggestion::MEDIUM,
            default => Suggestion::LOW,
        };
    }

    /**
     * The one sentence that best explains the number, chosen to agree with the direction of the
     * change: a bigger number is explained by sell-outs, a smaller one by leftovers.
     *
     * @param  list<Observation>  $observations
     */
    private function reason(array $observations, ?int $change, string $date): Reason
    {
        $count = count($observations);
        if ($count === 0) {
            return new Reason(Reason::BASELINE);
        }
        $specific = match (true) {
            $change > 0 => $this->soldOutReason($observations, $date),
            $change < 0 => $this->leftoverReason($observations, $date),
            default => null,
        };
        if ($specific !== null) {
            return $specific;
        }
        if ($count < self::FULL_HISTORY) {
            return new Reason(Reason::BLENDED, ['count' => $count]);
        }
        $latest = $observations[0];
        if ($change === 0) {
            return new Reason(Reason::STEADY, [
                'sold' => $latest->sold(),
                'baked' => $latest->baked,
                'weeksAgo' => $this->weeksBetween($latest->date, $date),
                'date' => $latest->date,
            ]);
        }

        return new Reason(Reason::AVERAGE, ['sold' => (int) round($this->weightedDemand($observations))]);
    }

    /** @param  list<Observation>  $observations */
    private function soldOutReason(array $observations, string $date): ?Reason
    {
        $recent = array_slice($observations, 0, 4);
        $soldOut = array_values(array_filter($recent, fn (Observation $o) => $o->soldOut()));
        if (count($soldOut) >= 2) {
            $latestMinute = max(array_map(fn (Observation $o) => $o->soldOutMinute, $soldOut));
            $counts = ['times' => count($soldOut), 'of' => count($recent)];
            if (self::inLastHour($latestMinute, $recent[0]->closesMinute)) {
                return new Reason(Reason::SOLD_OUT_LATE, $counts);
            }

            return new Reason(Reason::SOLD_OUT_OFTEN, $counts + ['before' => self::clock((intdiv($latestMinute, 60) + 1) * 60)]);
        }
        $latest = $observations[0];
        if ($latest->soldOut()) {
            return new Reason(Reason::SOLD_OUT, [
                'time' => self::clock($latest->soldOutMinute),
                'weeksAgo' => $this->weeksBetween($latest->date, $date),
                'date' => $latest->date,
            ]);
        }

        return null;
    }

    /** @param  list<Observation>  $observations */
    private function leftoverReason(array $observations, string $date): ?Reason
    {
        $latest = $observations[0];
        if (! $this->notableLeftover($latest)) {
            return null;
        }
        $before = $observations[1] ?? null;
        if ($before !== null && $this->notableLeftover($before)) {
            return $latest->left === $before->left
                ? new Reason(Reason::LEFT_EACH, ['left' => $latest->left])
                : new Reason(Reason::LEFT_TWO, ['recent' => $latest->left, 'before' => $before->left]);
        }

        return new Reason(Reason::LEFT_LAST, [
            'left' => $latest->left,
            'weeksAgo' => $this->weeksBetween($latest->date, $date),
            'date' => $latest->date,
        ]);
    }

    /** Worth mentioning: at least two pieces and a tenth of the batch. */
    private function notableLeftover(Observation $observation): bool
    {
        return $observation->left >= max(2, (int) ceil($observation->baked * 0.1));
    }

    private function weeksBetween(string $from, string $to): int
    {
        $days = (int) (new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->days;

        return max(1, intdiv($days, 7));
    }

    /** A sell-out in the last hour before closing: "before 21:00" would say nothing there. */
    public static function inLastHour(int $minute, ?int $closesMinute): bool
    {
        return $closesMinute !== null && $minute >= $closesMinute - 60;
    }

    public static function clock(int $minute): string
    {
        return sprintf('%02d:%02d', intdiv($minute, 60) % 24, $minute % 60);
    }
}
