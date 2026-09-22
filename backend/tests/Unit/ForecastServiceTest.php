<?php

namespace Tests\Unit;

use App\Forecast\ForecastInput;
use App\Forecast\ForecastService;
use App\Forecast\Observation;
use App\Forecast\Reason;
use App\Forecast\Suggestion;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ForecastServiceTest extends TestCase
{
    private const OPENS = 7 * 60; // 07:00

    private const CLOSES = 19 * 60; // 19:00, twelve hours open

    private ForecastService $forecast;

    protected function setUp(): void
    {
        parent::setUp();
        $this->forecast = new ForecastService;
    }

    public function test_without_counts_it_suggests_the_usual_number(): void
    {
        $s = $this->suggest(baseline: 40, observations: []);

        $this->assertSame(40, $s->quantity);
        $this->assertSame(Reason::BASELINE, $s->reason->code);
        $this->assertSame(Suggestion::LOW, $s->confidence);
        $this->assertSame(0, $s->observations);
    }

    public function test_recent_weeks_weigh_more(): void
    {
        // sold 60, 50, 40, 30, 20, 10 from the most recent week back
        $observations = array_map(fn (int $sold, int $weeks) => $this->day($weeks, baked: $sold + 5, left: 5), [60, 50, 40, 30, 20, 10], range(1, 6));

        // 0.35·60 + 0.25·50 + 0.17·40 + 0.11·30 + 0.07·20 + 0.05·10 = 45.5
        $this->assertEqualsWithDelta(45.5, $this->forecast->estimate($observations, 999), 1e-9);
        $this->assertSame(46, $this->suggest(baseline: 999, observations: $observations)->quantity);
    }

    public function test_weights_are_renormalised_over_the_days_available(): void
    {
        $observations = [$this->day(1, 45, 5), $this->day(2, 25, 5), $this->day(3, 25, 5), $this->day(4, 25, 5)];

        // (0.35·40 + 0.25·20 + 0.17·20 + 0.11·20) / 0.88
        $this->assertEqualsWithDelta(24.6 / 0.88, $this->forecast->estimate($observations, 999), 1e-9);
    }

    public function test_only_the_last_six_counted_days_are_used(): void
    {
        $six = array_map(fn (int $w) => $this->day($w, baked: 30, left: 0), range(1, 6));
        $seventh = $this->day(7, baked: 500, left: 0);

        $this->assertSame(30, $this->suggest(baseline: 0, observations: [...$six, $seventh])->quantity);
    }

    public function test_a_sell_out_is_scaled_up_by_the_share_of_the_day_still_ahead(): void
    {
        // sold out at 17:00: 720 minutes open, 600 of them before the sell-out
        $late = $this->day(1, baked: 30, left: 0, soldOutAt: '17:00');

        $this->assertEqualsWithDelta(1.2, $this->forecast->uplift($late), 1e-9);
        $this->assertEqualsWithDelta(36.0, $this->forecast->demand($late), 1e-9);
    }

    #[DataProvider('upliftBounds')]
    public function test_the_sell_out_uplift_is_clamped(?string $soldOutAt, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, $this->forecast->uplift($this->day(1, 30, 0, $soldOutAt)), 1e-9);
    }

    public static function upliftBounds(): array
    {
        return [
            'gone by 10:00, a quarter of the day' => ['10:00', ForecastService::MAX_UPLIFT],
            'gone at opening' => ['07:00', ForecastService::MAX_UPLIFT],
            'gone five minutes before closing' => ['18:55', ForecastService::MIN_UPLIFT],
        ];
    }

    public function test_without_opening_hours_a_sell_out_gets_the_smallest_uplift(): void
    {
        $observation = new Observation('2026-09-14', 30, 0, 10 * 60);

        $this->assertEqualsWithDelta(ForecastService::MIN_UPLIFT, $this->forecast->uplift($observation), 1e-9);
    }

    public function test_a_day_that_did_not_sell_out_is_taken_as_it_is(): void
    {
        $this->assertEqualsWithDelta(24.0, $this->forecast->demand($this->day(1, baked: 30, left: 6)), 1e-9);
    }

    public function test_with_one_counted_day_the_usual_number_still_weighs_two_thirds(): void
    {
        $observations = [$this->day(1, baked: 40, left: 10)];

        // 1/3 · 30 + 2/3 · 60
        $this->assertEqualsWithDelta(50.0, $this->forecast->estimate($observations, 60), 1e-9);
        $this->assertSame(Reason::BLENDED, $this->suggest(baseline: 60, observations: $observations, lastBaked: 50)->reason->code);
    }

    public function test_with_two_counted_days_the_usual_number_weighs_one_third(): void
    {
        $observations = [$this->day(1, baked: 40, left: 10), $this->day(2, baked: 40, left: 10)];

        // 2/3 · 30 + 1/3 · 60
        $this->assertEqualsWithDelta(40.0, $this->forecast->estimate($observations, 60), 1e-9);
    }

    public function test_from_three_counted_days_the_usual_number_no_longer_matters(): void
    {
        $observations = array_map(fn (int $w) => $this->day($w, baked: 40, left: 10), [1, 2, 3]);

        $this->assertEqualsWithDelta(30.0, $this->forecast->estimate($observations, 500), 1e-9);
    }

    #[DataProvider('trays')]
    public function test_it_rounds_up_to_whole_trays(float $estimate, int $tray, int $expected): void
    {
        $this->assertSame($expected, $this->forecast->roundUpToTray($estimate, $tray));
    }

    public static function trays(): array
    {
        return [
            'just over two trays' => [24.2, 12, 36],
            'exactly three trays' => [36.0, 12, 36],
            'float noise is not a fourth tray' => [36.000000001, 12, 36],
            'single pieces' => [17.1, 1, 18],
            'nothing' => [0.0, 12, 0],
            'a tray size of zero counts as one' => [3.5, 0, 4],
        ];
    }

    public function test_it_never_suggests_below_zero(): void
    {
        $observations = array_map(fn (int $w) => $this->day($w, baked: 20, left: 20), [1, 2, 3]);

        $this->assertSame(0, $this->suggest(baseline: 20, observations: $observations, traySize: 6)->quantity);
    }

    public function test_nothing_is_suggested_on_a_weekday_the_product_is_not_baked(): void
    {
        $input = new ForecastInput('2026-09-21', 30, 1, false, [$this->day(1, 30, 2)], 30);

        $this->assertNull($this->forecast->suggest($input));
    }

    public function test_the_change_is_against_what_was_baked_on_the_last_same_weekday(): void
    {
        $observations = array_map(fn (int $w) => $this->day($w, baked: 40, left: 8), [1, 2, 3]);
        $s = $this->suggest(baseline: 40, observations: $observations, lastBaked: 40, traySize: 4);

        $this->assertSame(32, $s->quantity);
        $this->assertSame(-8, $s->change());
    }

    #[DataProvider('confidence')]
    public function test_confidence_grows_with_counted_days(int $days, string $expected): void
    {
        $this->assertSame($expected, $this->forecast->confidence($days));
    }

    public static function confidence(): array
    {
        return [[0, Suggestion::LOW], [2, Suggestion::LOW], [3, Suggestion::MEDIUM], [4, Suggestion::MEDIUM], [5, Suggestion::HIGH], [6, Suggestion::HIGH]];
    }

    public function test_the_same_leftover_on_the_last_two_weeks_is_the_reason_for_baking_less(): void
    {
        $observations = [$this->day(1, 36, 7), $this->day(2, 36, 7), $this->day(3, 36, 6)];
        $s = $this->suggest(baseline: 36, observations: $observations, lastBaked: 36);

        $this->assertSame(Reason::LEFT_EACH, $s->reason->code);
        $this->assertSame(['left' => 7], $s->reason->params);
    }

    public function test_different_leftovers_on_the_last_two_weeks_are_both_named(): void
    {
        $observations = [$this->day(1, 36, 9), $this->day(2, 36, 6), $this->day(3, 36, 6)];

        $reason = $this->suggest(baseline: 36, observations: $observations, lastBaked: 36)->reason;

        $this->assertSame(Reason::LEFT_TWO, $reason->code);
        $this->assertSame(['recent' => 9, 'before' => 6], $reason->params);
    }

    public function test_one_notable_leftover_names_the_day(): void
    {
        $observations = [$this->day(1, 36, 10), $this->day(2, 30, 0), $this->day(3, 30, 1)];

        $reason = $this->suggest(baseline: 36, observations: $observations, lastBaked: 36)->reason;

        $this->assertSame(Reason::LEFT_LAST, $reason->code);
        $this->assertSame(10, $reason->params['left']);
        $this->assertSame(1, $reason->params['weeksAgo']);
    }

    public function test_a_recent_sell_out_is_the_reason_for_baking_more(): void
    {
        $observations = [$this->day(1, 30, 0, '10:20'), $this->day(2, 30, 3), $this->day(3, 30, 2)];

        $reason = $this->suggest(baseline: 30, observations: $observations, lastBaked: 30)->reason;

        $this->assertSame(Reason::SOLD_OUT, $reason->code);
        $this->assertSame('10:20', $reason->params['time']);
        $this->assertSame(1, $reason->params['weeksAgo']);
    }

    public function test_repeated_sell_outs_are_summed_up_with_the_hour_they_happen_before(): void
    {
        $observations = [$this->day(1, 30, 0, '10:40'), $this->day(2, 30, 0, '09:55'), $this->day(3, 30, 1), $this->day(4, 30, 0, '10:05')];

        $reason = $this->suggest(baseline: 30, observations: $observations, lastBaked: 30)->reason;

        $this->assertSame(Reason::SOLD_OUT_OFTEN, $reason->code);
        $this->assertSame(['times' => 3, 'of' => 4, 'before' => '11:00'], $reason->params);
    }

    public function test_a_steady_product_says_how_it_sold(): void
    {
        $observations = array_map(fn (int $w) => $this->day($w, baked: 30, left: 1), [1, 2, 3]);

        $reason = $this->suggest(baseline: 30, observations: $observations, lastBaked: 29)->reason;

        $this->assertSame(Reason::STEADY, $reason->code);
        $this->assertSame(29, $reason->params['sold']);
        $this->assertSame(30, $reason->params['baked']);
    }

    public function test_more_without_a_sell_out_is_explained_by_the_average(): void
    {
        $observations = [$this->day(1, 40, 2), $this->day(2, 44, 1), $this->day(3, 44, 0)];

        $reason = $this->suggest(baseline: 30, observations: $observations, lastBaked: 30)->reason;

        // (0.35·38 + 0.25·43 + 0.17·44) / 0.77 ≈ 40.9
        $this->assertSame(Reason::AVERAGE, $reason->code);
        $this->assertSame(41, $reason->params['sold']);
    }

    /** @param  list<Observation>  $observations */
    private function suggest(int $baseline, array $observations, ?int $lastBaked = null, int $traySize = 1): Suggestion
    {
        return $this->forecast->suggest(new ForecastInput('2026-09-21', $baseline, $traySize, true, $observations, $lastBaked));
    }

    /** A counted Monday, $weeksAgo weeks before Monday 21 September 2026. */
    private function day(int $weeksAgo, int $baked, int $left, ?string $soldOutAt = null): Observation
    {
        $minute = $soldOutAt === null ? null : (int) substr($soldOutAt, 0, 2) * 60 + (int) substr($soldOutAt, 3, 2);

        return new Observation(date('Y-m-d', strtotime("2026-09-21 -{$weeksAgo} weeks")), $baked, $left, $minute, self::OPENS, self::CLOSES);
    }
}
