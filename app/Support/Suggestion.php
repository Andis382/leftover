<?php

namespace App\Support;

/**
 * What the forecaster has to say about one product for one day.
 *
 * It carries the working as well as the answer. A number built on two Tuesdays
 * and a number built on eight are not the same kind of number, and the screen
 * says which one it is holding.
 */
final class Suggestion
{
    public function __construct(
        public readonly ?int $quantity,
        public readonly ?int $previous,
        public readonly int $observations,
        public readonly string $confidence,  // none | low | fair | good
        public readonly string $reason,      // a key in lang/*/plan.php
        public readonly float $averageLeft = 0.0,
        public readonly int $soldOutDays = 0,
        public readonly ?float $rawTarget = null,
        public readonly bool $capped = false,
    ) {
    }

    public function delta(): int
    {
        if ($this->quantity === null || $this->previous === null) {
            return 0;
        }

        return $this->quantity - $this->previous;
    }

    /** True when there is nothing worth saying yet. */
    public function isSilent(): bool
    {
        return $this->quantity === null;
    }

    public function isUnchanged(): bool
    {
        return ! $this->isSilent() && $this->delta() === 0;
    }
}
