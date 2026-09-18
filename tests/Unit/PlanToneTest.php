<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * The promise the plan makes about how it talks, enforced instead of described.
 *
 * This product asks a tired person to do something every single night. It gets
 * exactly one chance to sound like a colleague rather than a supervisor. A
 * translation that drifts into "you must" or "you wasted" fails the build here
 * rather than being discovered by a baker who quietly stops counting.
 */
class PlanToneTest extends TestCase
{
    /** Words that turn a suggestion into an instruction. */
    private const BOSSY = [
        'must ', 'you should', 'required', 'immediately', 'optimal', 'optimise', 'optimize',
        'duhet ', 'detyrimisht', 'e detyrueshme', 'menjëherë', 'optimale',
    ];

    /** Words that turn a fact into an accusation. */
    private const SCOLDING = [
        'you wasted', 'too much', 'you failed', 'mistake', 'wrong again', 'poor',
        'shpërdorove', 'gabim', 'dështove', 'shumë keq', 'faji',
    ];

    /** Nothing here is for sale. */
    private const PROMOTIONAL = [
        'upgrade', 'premium', 'unlock', 'trial', 'offer', 'discount',
        'ofertë', 'zbritje', 'provë falas', 'abonohu',
    ];

    private function locales(): array
    {
        return array_map('basename', glob(dirname(__DIR__, 2).'/lang/*', GLOB_ONLYDIR));
    }

    private function planStrings(string $locale): array
    {
        $file = dirname(__DIR__, 2)."/lang/{$locale}/plan.php";
        $this->assertFileExists($file, "Every shipped language needs a plan.php: {$locale}");

        $strings = require $file;
        $flat = [];
        array_walk_recursive($strings, function ($value, $key) use (&$flat) {
            $flat[$key] = $value;
        });

        return $flat;
    }

    public function test_the_plan_never_gives_an_order(): void
    {
        foreach ($this->locales() as $locale) {
            foreach ($this->planStrings($locale) as $key => $text) {
                foreach (self::BOSSY as $word) {
                    $this->assertStringNotContainsStringIgnoringCase(
                        $word,
                        $text,
                        "plan.{$key} in {$locale} gives an order: \"{$text}\""
                    );
                }
            }
        }
    }

    public function test_the_plan_never_scolds(): void
    {
        foreach ($this->locales() as $locale) {
            foreach ($this->planStrings($locale) as $key => $text) {
                foreach (self::SCOLDING as $word) {
                    $this->assertStringNotContainsStringIgnoringCase(
                        $word,
                        $text,
                        "plan.{$key} in {$locale} blames the reader: \"{$text}\""
                    );
                }
            }
        }
    }

    public function test_the_plan_never_sells_anything(): void
    {
        foreach ($this->locales() as $locale) {
            foreach ($this->planStrings($locale) as $key => $text) {
                foreach (self::PROMOTIONAL as $word) {
                    $this->assertStringNotContainsStringIgnoringCase(
                        $word,
                        $text,
                        "plan.{$key} in {$locale} is selling something: \"{$text}\""
                    );
                }
            }
        }
    }

    /**
     * A change has to arrive as a change and then as a total. "Five fewer, so
     * forty-five" is actionable standing at an oven; a percentage is not.
     */
    public function test_a_change_states_both_the_move_and_the_total(): void
    {
        foreach ($this->locales() as $locale) {
            $strings = $this->planStrings($locale);

            foreach (['more', 'fewer'] as $key) {
                $this->assertArrayHasKey($key, $strings);
                $this->assertStringContainsString(':count', $strings[$key], "plan.line.{$key} in {$locale} must say how many");
                $this->assertStringContainsString(':total', $strings[$key], "plan.line.{$key} in {$locale} must say the new total");
                $this->assertStringContainsString('|', $strings[$key], "plan.line.{$key} in {$locale} must handle one as well as many");
            }
        }
    }

    /** Every language ships every key, or a plan silently prints "plan.reason.left_over". */
    public function test_the_languages_have_not_drifted_apart(): void
    {
        $locales = $this->locales();
        $this->assertGreaterThan(1, count($locales));

        $reference = array_keys($this->planStrings($locales[0]));
        sort($reference);

        foreach (array_slice($locales, 1) as $locale) {
            $keys = array_keys($this->planStrings($locale));
            sort($keys);
            $this->assertSame($reference, $keys, "lang/{$locale}/plan.php does not match lang/{$locales[0]}/plan.php");
        }
    }

    /** It has to be able to say that it does not know. */
    public function test_it_can_admit_it_has_nothing_to_say(): void
    {
        foreach ($this->locales() as $locale) {
            $strings = $this->planStrings($locale);
            $this->assertArrayHasKey('not_enough_yet', $strings);
            $this->assertArrayHasKey('unknown', $strings);
            $this->assertNotSame('', trim($strings['not_enough_yet']));
        }
    }
}
