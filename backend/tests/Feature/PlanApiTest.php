<?php

namespace Tests\Feature;

use App\Models\DailyRecord;
use App\Models\DayClosing;
use App\Models\OutboundMessage;
use App\Models\Plan;
use App\Models\Product;

class PlanApiTest extends BakeryTestCase
{
    private Product $bread;

    protected function setUp(): void
    {
        parent::setUp();
        $this->at('2026-09-21 20:00'); // Monday evening
        $this->bread = $this->product(['name' => 'Bukë e bardhë', 'baselines' => [30, 30, 30, 30, 30, 40, 20]]);
    }

    public function test_a_plan_is_a_live_preview_until_it_is_made(): void
    {
        $this->actingAs($this->owner)->getJson('/api/plans/2026-09-22')
            ->assertOk()
            ->assertJsonPath('generated', false)
            ->assertJsonPath('rows.0.suggested', 30)
            ->assertJsonPath('rows.0.reasonCode', 'baseline')
            ->assertJsonPath('rows.0.confidence', 'low');

        $this->assertSame(0, Plan::count());
    }

    public function test_skipped_and_uncounted_days_are_ignored_not_read_as_zero(): void
    {
        $this->record('2026-09-14', baked: 30, left: 10); // counted
        $this->record('2026-09-07', baked: 30, left: 30); // counted, but the day was skipped
        DayClosing::create(['organization_id' => $this->org->id, 'date' => '2026-09-07', 'status' => DayClosing::SKIPPED, 'skip_reason' => 'Harruam']);
        $this->record('2026-08-31', baked: 30, left: null); // never counted

        // one counted Monday (20 sold) blended with the usual 30: 1/3·20 + 2/3·30 = 26.7
        $this->actingAs($this->owner)->getJson('/api/plans/2026-09-28')
            ->assertOk()
            ->assertJsonPath('rows.0.suggested', 27)
            ->assertJsonPath('rows.0.observations', 1)
            ->assertJsonPath('rows.0.lastBaked', 30)
            ->assertJsonPath('rows.0.change', -3);
    }

    public function test_changing_what_will_be_baked_fixes_the_plan_and_keeps_the_suggestion(): void
    {
        $this->actingAs($this->owner)->putJson("/api/plans/2026-09-22/items/{$this->bread->id}", ['willBake' => 24])
            ->assertOk()
            ->assertJsonPath('generated', true)
            ->assertJsonPath('rows.0.suggested', 30)
            ->assertJsonPath('rows.0.willBake', 24)
            ->assertJsonPath('rows.0.overridden', true)
            ->assertJsonPath('totals.units', 24);

        $this->assertDatabaseHas('daily_records', ['product_id' => $this->bread->id, 'planned_qty' => 30, 'baked_qty' => 24]);

        $this->putJson("/api/plans/2026-09-22/items/{$this->bread->id}", ['willBake' => null])
            ->assertOk()
            ->assertJsonPath('rows.0.willBake', 30)
            ->assertJsonPath('rows.0.overridden', false);
    }

    public function test_a_past_plan_can_no_longer_change(): void
    {
        $this->actingAs($this->owner)->putJson("/api/plans/2026-09-20/items/{$this->bread->id}", ['willBake' => 10])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['willBake']);
    }

    public function test_the_morning_confirmation_records_what_was_baked(): void
    {
        $this->at('2026-09-22 05:10');

        $this->actingAs($this->owner)->postJson('/api/plans/2026-09-22/baked', ['items' => [['productId' => $this->bread->id, 'baked' => 28]]])
            ->assertOk()
            ->assertJsonPath('bakedConfirmedBy', $this->owner->name)
            ->assertJsonPath('rows.0.willBake', 28);

        $this->putJson("/api/plans/2026-09-22/items/{$this->bread->id}", ['willBake' => 40])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['willBake']);
    }

    public function test_the_plan_goes_to_whatsapp_in_the_bakers_language(): void
    {
        $this->owner->update(['locale' => 'sq', 'name' => 'Ilir Muça']);

        $this->actingAs($this->owner)->getJson('/api/plans/2026-09-22/message')
            ->assertOk()
            ->assertJsonPath('phone', '355691112233')
            ->assertJsonPath('locale', 'sq');

        $this->postJson('/api/plans/2026-09-22/send')->assertOk()->assertJsonPath('plan.sentTo', '355691112233');

        $message = OutboundMessage::sole();
        $this->assertSame('bake_plan', $message->template_key);
        $this->assertStringStartsWith('Mirëmëngjes Ilir. Plani i pjekjes për të martën, 22 shtator:', $message->body);
        $this->assertStringContainsString('/plan/2026-09-22', $message->body);
        $this->assertNotNull(Plan::sole()->sent_at);
    }

    public function test_counter_staff_read_plans_but_do_not_change_them(): void
    {
        $staff = $this->staff();

        $this->switchTo($staff)->getJson('/api/plans/2026-09-22')->assertOk();
        $this->switchTo($staff)->putJson("/api/plans/2026-09-22/items/{$this->bread->id}", ['willBake' => 5])->assertForbidden();
        $this->switchTo($staff)->postJson('/api/plans/2026-09-22/send')->assertForbidden();
        $this->switchTo($staff)->postJson('/api/plans/2026-09-21/baked', ['items' => []])->assertForbidden();
    }

    private function record(string $date, int $baked, ?int $left): void
    {
        DailyRecord::create([
            'organization_id' => $this->org->id,
            'product_id' => $this->bread->id,
            'date' => $date,
            'planned_qty' => $baked,
            'left_qty' => $left,
        ]);
    }
}
