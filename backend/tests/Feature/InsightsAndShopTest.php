<?php

namespace Tests\Feature;

use App\Models\DailyRecord;
use App\Models\DayClosing;
use App\Models\Product;

class InsightsAndShopTest extends BakeryTestCase
{
    private Product $rolls;

    protected function setUp(): void
    {
        parent::setUp();
        $this->at('2026-09-21 21:00'); // Monday
        $this->rolls = $this->product(['name' => 'Simite me susam', 'unit_price_cents' => 40, 'unit_cost_cents' => 15]);
    }

    public function test_waste_is_measured_on_counted_days_only(): void
    {
        $this->record('2026-09-20', 40, 0, '10:15');
        $this->record('2026-09-19', 40, 10);
        $this->record('2026-09-18', 40, 30); // skipped below: ignored
        DayClosing::create(['organization_id' => $this->org->id, 'date' => '2026-09-18', 'status' => DayClosing::SKIPPED, 'skip_reason' => 'Harruam']);
        $this->record('2026-09-17', 40, null); // never counted

        $response = $this->actingAs($this->owner)->getJson('/api/insights?days=7')->assertOk();

        $response->assertJsonPath('current.bakedUnits', 80)
            ->assertJsonPath('current.leftUnits', 10)
            ->assertJsonPath('current.wasteCents', 150)
            ->assertJsonPath('current.soldOuts', 1)
            ->assertJsonPath('current.countedDays', 2)
            ->assertJsonPath('current.wastePct', 0.125);
        $statuses = collect($response->json('daily'))->pluck('status', 'date');
        $this->assertSame('SKIPPED', $statuses['2026-09-18']);
        $this->assertSame('MISSED', $statuses['2026-09-17']);
        $this->assertSame('COUNTED', $statuses['2026-09-20']);
        $this->assertSame('TODAY', $statuses['2026-09-21']);
    }

    public function test_repeated_sell_outs_become_a_plain_sentence(): void
    {
        foreach (['2026-09-19' => '10:40', '2026-09-12' => '10:05', '2026-09-05' => null, '2026-08-29' => '09:50'] as $date => $time) {
            $this->record($date, 40, $time === null ? 3 : 0, $time);
        }

        $this->actingAs($this->owner)->getJson('/api/insights?days=30')
            ->assertOk()
            ->assertJsonPath('patterns.0.text', 'Simite me susam sold out before 11:00 on 3 of the last 4 Saturdays.');
    }

    public function test_insights_and_shop_settings_are_for_the_owner(): void
    {
        $staff = $this->staff();

        $this->switchTo($staff)->getJson('/api/insights')->assertForbidden();
        $this->switchTo($staff)->putJson('/api/shop', [])->assertForbidden();
        $this->switchTo($staff)->getJson('/api/shop')->assertOk()->assertJsonCount(7, 'hours');
    }

    public function test_opening_hours_and_plan_time_are_saved(): void
    {
        $hours = array_map(fn (int $d) => ['weekday' => $d, 'open' => $d !== 7, 'opensAt' => $d !== 7 ? '06:30' : null, 'closesAt' => $d !== 7 ? '20:30' : null], range(1, 7));

        $this->actingAs($this->owner)->putJson('/api/shop', [
            'hours' => $hours,
            'planTime' => '03:45',
            'countReminderOffset' => 20,
            'planPhone' => '069 555 1234',
        ])->assertOk()
            ->assertJsonPath('planTime', '03:45')
            ->assertJsonPath('planPhone', '355695551234')
            ->assertJsonPath('hours.6.open', false)
            ->assertJsonPath('hours.0.closesAt', '20:30');
    }

    public function test_closing_before_opening_is_refused(): void
    {
        $hours = array_map(fn (int $d) => ['weekday' => $d, 'open' => true, 'opensAt' => '07:00', 'closesAt' => $d === 3 ? '06:00' : '19:00'], range(1, 7));

        $this->actingAs($this->owner)->putJson('/api/shop', ['hours' => $hours, 'planTime' => '04:00', 'countReminderOffset' => 15])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hours.2.closesAt']);
    }

    private function record(string $date, int $baked, ?int $left, ?string $soldOutAt = null): void
    {
        DailyRecord::create([
            'organization_id' => $this->org->id,
            'product_id' => $this->rolls->id,
            'date' => $date,
            'planned_qty' => $baked,
            'left_qty' => $left,
            'sold_out_at' => $soldOutAt,
        ]);
    }
}
