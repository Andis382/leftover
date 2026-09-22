<?php

namespace App\Forecast;

use Carbon\CarbonImmutable;

/** Phrases a Reason for people, in English or Albanian (texts in lang/{locale}/forecast.php). */
final class ReasonText
{
    public static function render(Reason $reason, int $weekday, string $locale): string
    {
        $p = $reason->params;
        $replace = [
            'name' => self::weekday('name', $weekday, $locale),
            'weekdays' => self::weekday('plural', $weekday, $locale),
            'when' => isset($p['weeksAgo']) ? self::when((int) $p['weeksAgo'], (string) ($p['date'] ?? ''), $weekday, $locale) : '',
        ] + array_map(fn ($v) => (string) $v, $p);

        if ($reason->code === Reason::BLENDED) {
            return trans_choice('forecast.reason.blended', (int) ($p['count'] ?? 1), $replace, $locale);
        }

        return trans('forecast.reason.'.$reason->code, $replace, $locale);
    }

    /** "last Monday" for the week before, otherwise the date: "on 8 Sep". */
    public static function when(int $weeksAgo, string $date, int $weekday, string $locale): string
    {
        if ($weeksAgo <= 1 || $date === '') {
            return self::weekday('last', $weekday, $locale);
        }
        $formatted = CarbonImmutable::parse($date)->locale($locale)->isoFormat($locale === 'sq' ? 'D MMMM' : 'D MMM');

        return trans('forecast.on_date', ['date' => $formatted], $locale);
    }

    /** @param  'name'|'last'|'plural'  $form */
    public static function weekday(string $form, int $weekday, string $locale): string
    {
        return trans("forecast.weekday.{$form}.{$weekday}", [], $locale);
    }
}
