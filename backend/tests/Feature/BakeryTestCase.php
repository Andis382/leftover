<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Product;
use App\Models\ShopHour;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** A small bakery in Tirana, open 07:00–19:00 every day, and helpers to move its clock. */
abstract class BakeryTestCase extends TestCase
{
    use RefreshDatabase;

    protected Organization $org;

    protected User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->org = Organization::factory()->create(['timezone' => 'Europe/Tirane', 'plan_phone' => '355691112233']);
        $this->owner = User::factory()->create(['organization_id' => $this->org->id, 'phone' => '355691112233']);
        $this->hours($this->org);
    }

    protected function hours(Organization $org, string $opens = '07:00', string $closes = '19:00'): void
    {
        foreach (range(1, 7) as $weekday) {
            ShopHour::withoutGlobalScope('organization')->create(['organization_id' => $org->id, 'weekday' => $weekday, 'opens_at' => $opens, 'closes_at' => $closes]);
        }
    }

    protected function staff(?Organization $org = null, ?string $phone = null): User
    {
        return User::factory()->staff()->create(['organization_id' => ($org ?? $this->org)->id, 'phone' => $phone]);
    }

    protected function product(array $attributes = [], ?Organization $org = null): Product
    {
        return Product::factory()->create(['organization_id' => ($org ?? $this->org)->id] + $attributes);
    }

    /** Another person on another phone: a fresh session, then signed in as them. */
    protected function switchTo(User $user): static
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();

        return $this->actingAs($user);
    }

    /** Sets "now" as a wall-clock time in Tirana, e.g. '2026-09-21 20:00' (a Monday). */
    protected function at(string $localTime): void
    {
        $this->travelTo(CarbonImmutable::parse($localTime, 'Europe/Tirane'));
    }
}
