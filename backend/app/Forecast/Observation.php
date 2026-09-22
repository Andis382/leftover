<?php

namespace App\Forecast;

/**
 * One counted day of one product: what went into the oven, what was left at closing and,
 * if it ran out, when. Times are minutes after midnight in the shop's time zone.
 */
final class Observation
{
    public function __construct(
        public readonly string $date,
        public readonly int $baked,
        public readonly int $left,
        public readonly ?int $soldOutMinute = null,
        public readonly ?int $opensMinute = null,
        public readonly ?int $closesMinute = null,
    ) {}

    public function sold(): int
    {
        return max(0, $this->baked - $this->left);
    }

    public function soldOut(): bool
    {
        return $this->soldOutMinute !== null;
    }
}
