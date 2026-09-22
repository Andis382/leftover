<?php

namespace App\Forecast;

/** Everything the forecast needs to know about one product for one target day. */
final class ForecastInput
{
    /**
     * @param  list<Observation>  $observations  counted same-weekday days before the target, most recent first
     * @param  ?int  $lastBaked  what went into the oven on the last same weekday, counted or not
     */
    public function __construct(
        public readonly string $date,
        public readonly int $baseline,
        public readonly int $traySize,
        public readonly bool $bakedThatDay,
        public readonly array $observations,
        public readonly ?int $lastBaked,
    ) {}
}
