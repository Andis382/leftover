<?php

namespace Tests\Unit;

use App\Services\Forecaster;
use App\Support\Observation;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * The arithmetic, on made-up days, with no database anywhere near it.
 *
 * This is the part of the product that can be quietly wrong for months: a
 * suggestion that is ten per cent too low every Tuesday looks exactly like a
 * suggestion that is right, and the only thing that would catch it is a baker
 * losing faith in week five. So every rule it promises is pinned here.
 */
class ForecasterTest extends TestCase
{
    private Forecaster $forecaster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forecaster = new Forecaster();
    }

    private function day(string $date, int $baked, int $left, ?string $soldOut = null): Observation
    {
        return new Observation(Carbon::parse($date), $baked, $left, $soldOut);
    }

    private function target(): Carbon
    {
        return Carbon::parse('2026-09-22');   // a Tuesday
    }

    public function test_it_says_nothing_at_all_on_one_counted_day(): void
    {
        $suggestion = $this->forecaster->suggest(
            [$this->day('2026-09-15', 50, 8)],
            $this->target(),
        );

        $this->assertTrue($suggestion->isSilent());
        $this->assertSame('not_enough_yet', $suggestion->reason);
        $this->assertSame('none', $suggestion->confidence);

        // It still knows what was baked last time, which is worth showing even
        // when there is nothing to suggest.
        $this->assertSame(50, $suggestion->previous);
    }

    public function test_consistent_leftovers_pull_the_number_down(): void
    {
        $suggestion = $this->forecaster->suggest([
            $this->day('2026-09-15', 50, 8),
            $this->day('2026-09-08', 50, 7),
            $this->day('2026-09-01', 50, 9),
        ], $this->target());

        $this->assertNotNull($suggestion->quantity);
        $this->assertLessThan(50, $suggestion->quantity);
        $this->assertSame('left_over', $suggestion->reason);
        $this->assertEqualsWithDelta(8.0, $suggestion->averageLeft, 0.01);
    }

    /**
     * The idea the whole product turns on. Three days that sold out are not
     * three days that were judged correctly; they are three days when demand
     * was higher than the shelf and nobody counted the people who left.
     */
    public function test_a_sell_out_counts_as_more_demand_than_it_served(): void
    {
        $days = [
            $this->day('2026-09-15', 40, 0, '10:00'),
            $this->day('2026-09-08', 40, 0, '10:30'),
            $this->day('2026-09-01', 40, 0, '11:00'),
        ];

        $withSellOut = $this->forecaster->suggest($days, $this->target());

        $sameButExactlyEmptyAtClosing = $this->forecaster->suggest([
            $this->day('2026-09-15', 40, 0),
            $this->day('2026-09-08', 40, 0),
            $this->day('2026-09-01', 40, 0),
        ], $this->target());

        $this->assertGreaterThan(40, $withSellOut->quantity, 'an early sell-out should raise tomorrow');
        $this->assertSame(40, $sameButExactlyEmptyAtClosing->quantity, 'selling the last one at closing is a perfect day');
        $this->assertSame(3, $withSellOut->soldOutDays);
        $this->assertSame('sold_out_often', $withSellOut->reason);
    }

    public function test_selling_out_early_counts_for_more_than_selling_out_late(): void
    {
        $early = $this->forecaster->suggest([
            $this->day('2026-09-15', 100, 0, '07:00'),
            $this->day('2026-09-08', 100, 0, '07:00'),
        ], $this->target());

        $late = $this->forecaster->suggest([
            $this->day('2026-09-15', 100, 0, '19:00'),
            $this->day('2026-09-08', 100, 0, '19:00'),
        ], $this->target());

        $this->assertGreaterThan($late->quantity, $early->quantity);
    }

    /**
     * The rail that makes this safe to ship. Three weeks of data is not
     * entitled to tell anyone to halve their production, however confident the
     * arithmetic feels.
     */
    public function test_no_suggestion_may_move_production_by_more_than_a_fifth(): void
    {
        // A wild week: baked 100, sold 20. The raw target is nowhere near 80.
        $suggestion = $this->forecaster->suggest([
            $this->day('2026-09-15', 100, 80),
            $this->day('2026-09-08', 100, 78),
            $this->day('2026-09-01', 100, 82),
        ], $this->target());

        $this->assertSame(80, $suggestion->quantity, 'a fifth below 100 is the floor for one step');
        $this->assertTrue($suggestion->capped);
        $this->assertLessThan(40, $suggestion->rawTarget, 'the raw target really was much lower');
    }

    public function test_the_cap_still_allows_movement_on_small_numbers(): void
    {
        // A fifth of 4 rounds to 1, and a product that never moves is useless.
        $suggestion = $this->forecaster->suggest([
            $this->day('2026-09-15', 4, 3),
            $this->day('2026-09-08', 4, 3),
        ], $this->target());

        $this->assertSame(3, $suggestion->quantity);
    }

    public function test_things_come_out_of_the_oven_in_trays(): void
    {
        $suggestion = $this->forecaster->suggest([
            $this->day('2026-09-15', 60, 7),
            $this->day('2026-09-08', 60, 6),
            $this->day('2026-09-01', 60, 8),
        ], $this->target(), roundTo: 12);

        $this->assertSame(0, $suggestion->quantity % 12, 'a tray of twelve should never be asked for in sevens');
    }

    public function test_recent_weeks_count_for_more_than_old_ones(): void
    {
        // Four quiet weeks and then two busy ones. The answer should lean
        // towards the busy ones without ignoring the quiet ones.
        $suggestion = $this->forecaster->suggest([
            $this->day('2026-09-15', 100, 0, '12:00'),
            $this->day('2026-09-08', 100, 0, '12:00'),
            $this->day('2026-09-01', 100, 40),
            $this->day('2026-08-25', 100, 40),
            $this->day('2026-08-18', 100, 40),
            $this->day('2026-08-11', 100, 40),
        ], $this->target());

        $flat = (2 * 100 * 1.15 + 4 * 60) / 6;   // roughly, with no decay
        $this->assertGreaterThan($flat, $suggestion->rawTarget);
    }

    /**
     * A tired week must cost a smaller sample, never a wrong one. This is the
     * difference between a product that survives February and one that gets
     * deleted after the first time somebody forgets.
     */
    public function test_a_skipped_day_is_absent_rather_than_zero(): void
    {
        $withGap = $this->forecaster->suggest([
            $this->day('2026-09-15', 50, 5),
            // nothing at all for the 8th: nobody counted
            $this->day('2026-09-01', 50, 5),
        ], $this->target());

        $this->assertSame(2, $withGap->observations);
        $this->assertGreaterThan(40, $withGap->quantity, 'a missing day must not look like a day of no sales');
    }

    public function test_a_steady_product_is_left_alone(): void
    {
        $suggestion = $this->forecaster->suggest([
            $this->day('2026-09-15', 30, 0),
            $this->day('2026-09-08', 30, 0),
            $this->day('2026-09-01', 30, 0),
            $this->day('2026-08-25', 30, 0),
        ], $this->target());

        $this->assertSame(30, $suggestion->quantity);
        $this->assertTrue($suggestion->isUnchanged());
        $this->assertSame('steady', $suggestion->reason);
    }

    public function test_confidence_grows_with_the_number_of_counted_days(): void
    {
        $days = [];
        $confidences = [];

        foreach (range(1, 7) as $weeksBack) {
            $days[] = $this->day(Carbon::parse('2026-09-15')->subWeeks($weeksBack - 1)->toDateString(), 40, 4);
            $confidences[] = $this->forecaster->suggest($days, $this->target())->confidence;
        }

        $this->assertSame(['none', 'low', 'low', 'fair', 'fair', 'good', 'good'], $confidences);
    }

    public function test_it_never_looks_further_back_than_the_history_window(): void
    {
        $days = [];
        foreach (range(0, 20) as $weeksBack) {
            $days[] = $this->day(Carbon::parse('2026-09-15')->subWeeks($weeksBack)->toDateString(), 40, 4);
        }

        $this->assertSame(
            Forecaster::HISTORY_WEEKS,
            $this->forecaster->suggest($days, $this->target())->observations
        );
    }

    public function test_a_day_on_or_after_the_target_is_not_history(): void
    {
        $suggestion = $this->forecaster->suggest([
            $this->day('2026-09-29', 999, 0),   // the week after the target
            $this->day('2026-09-15', 50, 5),
            $this->day('2026-09-08', 50, 5),
        ], $this->target());

        $this->assertSame(2, $suggestion->observations);
        $this->assertLessThan(100, $suggestion->quantity);
    }
}
