<?php

namespace App\Forecast;

/**
 * The plan in one sentence: the biggest few changes against the same weekday last week,
 * then "the rest as usual" or how many smaller changes remain.
 */
final class Headline
{
    public const MENTIONS = 3;

    /**
     * @param  list<array{name: string, change: int}>  $changes  biggest first
     */
    private function __construct(
        public readonly array $changes,
        public readonly int $others,
        public readonly bool $firstPlan,
    ) {}

    /**
     * @param  list<array{name: string, change: ?int}>  $rows
     */
    public static function from(array $rows): self
    {
        $changed = array_values(array_filter($rows, fn (array $r) => $r['change'] !== null && $r['change'] !== 0));
        usort($changed, fn (array $a, array $b) => [abs($b['change']), $a['name']] <=> [abs($a['change']), $b['name']]);
        $firstPlan = $rows !== [] && count(array_filter($rows, fn (array $r) => $r['change'] !== null)) === 0;

        return new self(
            array_map(fn (array $r) => ['name' => $r['name'], 'change' => $r['change']], array_slice($changed, 0, self::MENTIONS)),
            max(0, count($changed) - self::MENTIONS),
            $firstPlan,
        );
    }

    /** "10 more simite me susam, 5 fewer kroasan, the rest as usual." in the given language. */
    public function render(string $locale, string $sameWeekdayLast): string
    {
        if ($this->firstPlan) {
            return trans('plan.first_plan', [], $locale);
        }
        if ($this->changes === []) {
            return trans('plan.all_same', ['when' => $sameWeekdayLast], $locale);
        }
        $parts = array_map(fn (array $c) => trans($c['change'] > 0 ? 'plan.more' : 'plan.fewer', [
            'qty' => abs($c['change']),
            'name' => self::inSentence($c['name']),
        ], $locale), $this->changes);
        $sentence = implode(', ', $parts);
        $sentence .= $this->others > 0
            ? ' '.trans_choice('plan.rest_changes', $this->others, ['count' => $this->others], $locale)
            : ', '.trans('plan.rest_usual', [], $locale);

        return $sentence.'.';
    }

    /** Product names are written like titles; inside a sentence they read better lower-case. */
    private static function inSentence(string $name): string
    {
        $second = mb_substr($name, 1, 1);
        if ($second === '' || mb_strtoupper($second) === $second) {
            return $name;
        }

        return mb_strtolower(mb_substr($name, 0, 1)).mb_substr($name, 1);
    }
}
