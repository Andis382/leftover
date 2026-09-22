<?php

namespace App\Forecast;

/**
 * Why a suggestion is what it is, as a code plus the numbers behind it. Kept structured so
 * it can be stored with the plan and phrased later in whichever language is reading it.
 */
final class Reason
{
    public const BASELINE = 'baseline';

    public const BLENDED = 'blended';

    public const SOLD_OUT = 'soldOut';

    public const SOLD_OUT_OFTEN = 'soldOutOften';

    public const LEFT_EACH = 'leftEach';

    public const LEFT_TWO = 'leftTwo';

    public const LEFT_LAST = 'leftLast';

    public const AVERAGE = 'average';

    public const STEADY = 'steady';

    /** @param  array<string, int|string>  $params */
    public function __construct(
        public readonly string $code,
        public readonly array $params = [],
    ) {}

    public function toArray(): array
    {
        return ['code' => $this->code, 'params' => $this->params];
    }

    public static function fromArray(array $data): self
    {
        return new self($data['code'] ?? self::BASELINE, $data['params'] ?? []);
    }
}
