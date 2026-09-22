<?php

namespace App\Forecast;

/** The forecast's answer for one product and day: a number, how it compares, and why. */
final class Suggestion
{
    public const LOW = 'low';

    public const MEDIUM = 'medium';

    public const HIGH = 'high';

    public function __construct(
        public readonly int $quantity,
        public readonly float $estimate,
        public readonly ?int $lastBaked,
        public readonly string $confidence,
        public readonly int $observations,
        public readonly Reason $reason,
    ) {}

    public function change(): ?int
    {
        return $this->lastBaked === null ? null : $this->quantity - $this->lastBaked;
    }

    /** What is stored with the plan, so a past plan still explains itself. */
    public function snapshot(): array
    {
        return [
            'estimate' => round($this->estimate, 2),
            'lastBaked' => $this->lastBaked,
            'confidence' => $this->confidence,
            'observations' => $this->observations,
            'reason' => $this->reason->toArray(),
        ];
    }

    public static function fromSnapshot(int $quantity, array $snapshot): self
    {
        return new self(
            $quantity,
            (float) ($snapshot['estimate'] ?? $quantity),
            $snapshot['lastBaked'] ?? null,
            $snapshot['confidence'] ?? self::LOW,
            (int) ($snapshot['observations'] ?? 0),
            Reason::fromArray($snapshot['reason'] ?? []),
        );
    }
}
