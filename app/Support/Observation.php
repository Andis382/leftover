<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * One counted day for one product.
 *
 * Deliberately a value object rather than a model row: the forecaster should be
 * testable with a handful of made-up days and no database at all, because the
 * arithmetic is the part of this product that can quietly be wrong for months.
 */
final class Observation
{
    public function __construct(
        public readonly CarbonInterface $date,
        public readonly int $baked,
        public readonly int $left,
        public readonly ?string $soldOutAt = null,
    ) {
    }

    /** What actually went over the counter. */
    public function sold(): int
    {
        return max(0, $this->baked - $this->left);
    }

    public function soldOut(): bool
    {
        return $this->soldOutAt !== null;
    }

    /**
     * How much of the trading day had passed when the shelf emptied, 0 to 1.
     *
     * Selling out at one minute to closing is nearly a perfect day. Selling out
     * at nine in the morning means eleven hours of people being turned away,
     * and the till has no idea any of them existed.
     */
    public function sellOutFraction(string $opensAt, string $closesAt): float
    {
        if (! $this->soldOut()) {
            return 1.0;
        }

        $open = self::minutes($opensAt);
        $close = self::minutes($closesAt);
        $out = self::minutes($this->soldOutAt);

        $span = $close - $open;
        if ($span <= 0) {
            return 1.0;
        }

        return max(0.0, min(1.0, ($out - $open) / $span));
    }

    private static function minutes(string $time): int
    {
        [$h, $m] = array_pad(array_map('intval', explode(':', $time)), 2, 0);

        return $h * 60 + $m;
    }
}
