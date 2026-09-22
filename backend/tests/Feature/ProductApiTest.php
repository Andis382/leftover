<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;

class ProductApiTest extends BakeryTestCase
{
    private array $payload = [
        'name' => 'Simite me susam',
        'category' => 'BREAD',
        'unitPriceCents' => 40,
        'unitCostCents' => 14,
        'traySize' => 12,
        'baselines' => [36, 36, 36, 36, 48, 60, 24],
        'activeWeekdays' => [6, 1, 2, 3, 4, 5],
    ];

    public function test_the_owner_adds_a_product_at_the_end_of_the_shelf(): void
    {
        $this->product(['shelf_order' => 4]);

        $this->actingAs($this->owner)->postJson('/api/products', $this->payload)
            ->assertCreated()
            ->assertJsonPath('name', 'Simite me susam')
            ->assertJsonPath('shelfOrder', 5)
            ->assertJsonPath('activeWeekdays', [1, 2, 3, 4, 5, 6])
            ->assertJsonPath('active', true);
    }

    public function test_the_owner_edits_moves_archives_and_restores(): void
    {
        $first = $this->product(['name' => 'Bukë e bardhë', 'shelf_order' => 1]);
        $second = $this->product(['name' => 'Bukë misri', 'shelf_order' => 2]);
        $this->actingAs($this->owner);

        $this->putJson("/api/products/{$first->id}", ['name' => 'Bukë e bardhë e madhe'] + $this->payload)
            ->assertOk()
            ->assertJsonPath('traySize', 12);

        $this->postJson("/api/products/{$second->id}/move", ['direction' => 'up'])
            ->assertOk()
            ->assertJsonPath('0.id', $second->id)
            ->assertJsonPath('1.id', $first->id);

        $this->postJson("/api/products/{$second->id}/archive")->assertOk()->assertJsonPath('active', false);
        $this->postJson("/api/products/{$second->id}/restore")->assertOk()->assertJsonPath('active', true)->assertJsonPath('shelfOrder', 3);
    }

    public function test_invalid_products_are_refused_field_by_field(): void
    {
        $this->actingAs($this->owner)->postJson('/api/products', [
            'name' => '',
            'category' => 'CAKE',
            'unitPriceCents' => -1,
            'traySize' => 0,
            'baselines' => [1, 2, 3],
            'activeWeekdays' => [8],
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'category', 'unitPriceCents', 'traySize', 'baselines', 'activeWeekdays.0']);
    }

    public function test_counter_staff_cannot_edit_products(): void
    {
        $product = $this->product();
        $staff = $this->staff();

        $this->switchTo($staff)->getJson('/api/products')->assertOk()->assertJsonCount(1);
        $this->switchTo($staff)->postJson('/api/products', $this->payload)->assertForbidden();
        $this->switchTo($staff)->putJson("/api/products/{$product->id}", $this->payload)->assertForbidden();
        $this->switchTo($staff)->postJson("/api/products/{$product->id}/move", ['direction' => 'down'])->assertForbidden();
        $this->switchTo($staff)->postJson("/api/products/{$product->id}/archive")->assertForbidden();
    }

    public function test_products_stay_inside_their_bakery(): void
    {
        $mine = $this->product(['name' => 'Bukë e bardhë']);
        $other = Organization::factory()->create();
        $theirs = $this->product(['name' => 'Pogaçe'], $other);
        $stranger = User::factory()->create(['organization_id' => $other->id]);

        $this->actingAs($this->owner)->getJson('/api/products')->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $mine->id);
        $this->actingAs($this->owner)->putJson("/api/products/{$theirs->id}", $this->payload)->assertNotFound();
        $this->switchTo($stranger)->postJson("/api/products/{$mine->id}/archive")->assertNotFound();
    }
}
