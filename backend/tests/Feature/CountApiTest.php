<?php

namespace Tests\Feature;

use App\Models\DailyRecord;
use App\Models\DayClosing;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;

class CountApiTest extends BakeryTestCase
{
    private Product $bread;

    private Product $rolls;

    protected function setUp(): void
    {
        parent::setUp();
        $this->at('2026-09-21 19:10');
        $this->bread = $this->product(['name' => 'Bukë e bardhë', 'shelf_order' => 1]);
        $this->rolls = $this->product(['name' => 'Simite me susam', 'shelf_order' => 2]);
        $this->actingAs($this->owner)->postJson('/api/plans/2026-09-21/baked', ['items' => [
            ['productId' => $this->bread->id, 'baked' => 30],
            ['productId' => $this->rolls->id, 'baked' => 24],
        ]])->assertOk();
    }

    public function test_each_change_is_saved_on_its_own(): void
    {
        $this->patchJson("/api/count/2026-09-21/items/{$this->bread->id}", ['left' => 4])
            ->assertOk()
            ->assertJsonPath('left', 4)
            ->assertJsonPath('baked', 30)
            ->assertJsonPath('countedBy', $this->owner->name);
        $this->patchJson("/api/count/2026-09-21/items/{$this->bread->id}", ['left' => 3])->assertOk()->assertJsonPath('left', 3);

        $this->getJson('/api/count/2026-09-21')
            ->assertOk()
            ->assertJsonPath('status', DayClosing::OPEN)
            ->assertJsonPath('summary.counted', 1)
            ->assertJsonPath('summary.total', 2)
            ->assertJsonPath('summary.leftUnits', 3)
            ->assertJsonPath('items.0.name', 'Bukë e bardhë')
            ->assertJsonPath('items.1.left', null);
        $this->assertDatabaseHas('daily_records', ['product_id' => $this->bread->id, 'left_qty' => 3, 'counted_by' => $this->owner->id]);
    }

    public function test_today_is_the_default_sheet(): void
    {
        $this->getJson('/api/count')->assertOk()->assertJsonPath('date', '2026-09-21')->assertJsonPath('isToday', true);
    }

    public function test_a_sell_out_means_nothing_was_left(): void
    {
        $this->patchJson("/api/count/2026-09-21/items/{$this->rolls->id}", ['left' => 5, 'soldOutAt' => '10:30'])
            ->assertOk()
            ->assertJsonPath('left', 0)
            ->assertJsonPath('soldOutAt', '10:30');

        $this->patchJson("/api/count/2026-09-21/items/{$this->rolls->id}", ['left' => 2, 'soldOutAt' => null])
            ->assertOk()
            ->assertJsonPath('soldOutAt', null);
    }

    public function test_a_count_can_be_cleared_again(): void
    {
        $this->patchJson("/api/count/2026-09-21/items/{$this->bread->id}", ['left' => 4])->assertOk();

        $this->patchJson("/api/count/2026-09-21/items/{$this->bread->id}", ['left' => null])
            ->assertOk()
            ->assertJsonPath('left', null)
            ->assertJsonPath('countedAt', null);
    }

    public function test_more_than_was_baked_is_refused(): void
    {
        $this->patchJson("/api/count/2026-09-21/items/{$this->bread->id}", ['left' => 31])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['left']);
    }

    public function test_a_day_that_has_not_come_cannot_be_counted(): void
    {
        $this->patchJson("/api/count/2026-09-22/items/{$this->bread->id}", ['left' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date']);
    }

    public function test_finishing_closes_the_day_and_leaves_uncounted_products_uncounted(): void
    {
        $this->patchJson("/api/count/2026-09-21/items/{$this->bread->id}", ['left' => 4])->assertOk();

        $this->postJson('/api/count/2026-09-21/finish')
            ->assertOk()
            ->assertJsonPath('status', DayClosing::COUNTED)
            ->assertJsonPath('closedBy', $this->owner->name)
            ->assertJsonPath('summary.wasteCents', 4 * 30);

        $this->assertNull(DailyRecord::where('product_id', $this->rolls->id)->value('left_qty'));
    }

    public function test_finishing_needs_at_least_one_count(): void
    {
        $this->postJson('/api/count/2026-09-21/finish')->assertUnprocessable()->assertJsonValidationErrors(['count']);
    }

    public function test_a_skipped_day_is_not_counted_until_the_skip_is_undone(): void
    {
        $this->postJson('/api/count/2026-09-21/skip', ['reason' => 'Mbyllur për festë'])
            ->assertOk()
            ->assertJsonPath('status', DayClosing::SKIPPED)
            ->assertJsonPath('skipReason', 'Mbyllur për festë');

        $this->patchJson("/api/count/2026-09-21/items/{$this->bread->id}", ['left' => 2])->assertStatus(409);

        $this->postJson('/api/count/2026-09-21/reopen')->assertOk()->assertJsonPath('status', DayClosing::OPEN);
        $this->patchJson("/api/count/2026-09-21/items/{$this->bread->id}", ['left' => 2])->assertOk();
    }

    public function test_skipping_needs_a_reason(): void
    {
        $this->postJson('/api/count/2026-09-21/skip', ['reason' => ''])->assertUnprocessable()->assertJsonValidationErrors(['reason']);
    }

    public function test_counter_staff_can_count(): void
    {
        $staff = $this->staff();

        $this->switchTo($staff)
            ->patchJson("/api/count/2026-09-21/items/{$this->bread->id}", ['left' => 6])
            ->assertOk()
            ->assertJsonPath('countedBy', $staff->name);
    }

    public function test_another_bakery_cannot_see_or_count_these_products(): void
    {
        $other = Organization::factory()->create();
        $this->hours($other);
        $stranger = User::factory()->create(['organization_id' => $other->id]);

        $this->switchTo($stranger)->patchJson("/api/count/2026-09-21/items/{$this->bread->id}", ['left' => 1])->assertNotFound();
        $this->switchTo($stranger)->getJson('/api/count/2026-09-21')->assertOk()->assertJsonCount(0, 'items');
    }
}
