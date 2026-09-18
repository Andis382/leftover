<?php

namespace Tests\Feature;

use App\Models\DaySheet;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use App\Services\PlanBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PlanBuildingTest extends TestCase
{
    use RefreshDatabase;

    private function shop(string $email = 'p@example.test'): User
    {
        return User::create([
            'name' => 'Petrit',
            'shop_name' => 'Furra Petrela',
            'email' => $email,
            'password' => 'secret-secret',
            'locale' => 'en',
            'currency' => 'EUR',
            'timezone' => 'Europe/Tirane',
            'opens_at' => '06:00',
            'closes_at' => '20:00',
            'plan_at' => '04:00',
        ]);
    }

    private function product(User $shop, string $name, int $batch = 50): Product
    {
        return $shop->products()->create([
            'name' => $name, 'unit' => 'piece', 'category' => 'bread',
            'price' => 1.00, 'cost' => 0.40, 'typical_batch' => $batch,
            'round_to' => 1, 'active' => true,
        ]);
    }

    /** Record one counted day, weeks back, on the same weekday as the target. */
    private function counted(User $shop, Carbon $date, array $entries, string $status = DaySheet::COUNTED): DaySheet
    {
        $sheet = $shop->daySheets()->create([
            'on_date' => $date->toDateString(),
            'weekday' => (int) $date->isoWeekday(),
            'status' => $status,
            'counted_at' => $status === DaySheet::COUNTED ? $date->copy()->setTime(20, 15) : null,
        ]);

        foreach ($entries as $productId => [$baked, $left, $soldOut]) {
            $sheet->counts()->create([
                'product_id' => $productId,
                'baked_qty' => $baked,
                'left_qty' => $left,
                'sold_out_at' => $soldOut,
            ]);
        }

        return $sheet;
    }

    public function test_a_plan_says_the_change_the_total_and_the_reason(): void
    {
        $shop = $this->shop();
        $croissant = $this->product($shop, 'Croissant');

        $target = Carbon::parse('2026-09-22');   // a Tuesday
        foreach ([1, 2, 3, 4] as $weeksBack) {
            $this->counted($shop, $target->copy()->subWeeks($weeksBack), [
                $croissant->id => [50, 9, null],
            ]);
        }

        $plan = app(PlanBuilder::class)->build($shop, $target);
        $line = $plan->lines->first();

        $this->assertLessThan(50, $line->suggested_qty);
        $this->assertSame($line->suggested_qty - 50, $line->delta);
        $this->assertSame('left_over', $line->reason);
        $this->assertSame(4, $line->observations);

        $this->assertStringContainsString('fewer Croissant', $plan->body);
        $this->assertStringContainsString('so '.$line->suggested_qty, $plan->body);
    }

    public function test_a_product_with_one_counted_day_is_listed_but_not_guessed_at(): void
    {
        $shop = $this->shop();
        $newThing = $this->product($shop, 'Apple pie', 8);

        $target = Carbon::parse('2026-09-22');
        $this->counted($shop, $target->copy()->subWeek(), [$newThing->id => [8, 2, null]]);

        $plan = app(PlanBuilder::class)->build($shop, $target);
        $line = $plan->lines->first();

        $this->assertNull($line->suggested_qty);
        $this->assertSame('not_enough_yet', $line->reason);
        $this->assertStringContainsString('Not enough counts yet', $plan->body);
        $this->assertStringContainsString('Apple pie', $plan->body);
    }

    /** Only the same weekday. A Saturday tells you nothing about a Tuesday. */
    public function test_only_the_same_weekday_is_read(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf', 100);

        $target = Carbon::parse('2026-09-22');   // Tuesday

        // Quiet Tuesdays…
        foreach ([1, 2, 3] as $weeksBack) {
            $this->counted($shop, $target->copy()->subWeeks($weeksBack), [$bread->id => [100, 30, null]]);
        }
        // …and busy Saturdays, which must not be allowed to vote.
        foreach ([1, 2, 3] as $weeksBack) {
            $this->counted($shop, $target->copy()->subWeeks($weeksBack)->next(Carbon::SATURDAY), [
                $bread->id => [100, 0, '09:00'],
            ]);
        }

        $line = app(PlanBuilder::class)->build($shop, $target)->lines->first();

        $this->assertSame(3, $line->observations);
        $this->assertLessThan(100, $line->suggested_qty);
    }

    public function test_days_that_were_never_counted_are_left_out(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf', 100);
        $target = Carbon::parse('2026-09-22');

        $this->counted($shop, $target->copy()->subWeeks(1), [$bread->id => [100, 10, null]]);
        $this->counted($shop, $target->copy()->subWeeks(2), [$bread->id => [100, 10, null]]);
        // The shop was shut, and another day nobody counted. Neither is a zero.
        $this->counted($shop, $target->copy()->subWeeks(3), [], DaySheet::SHUT);
        $this->counted($shop, $target->copy()->subWeeks(4), [], DaySheet::SKIPPED);

        $line = app(PlanBuilder::class)->build($shop, $target)->lines->first();

        $this->assertSame(2, $line->observations);
        $this->assertGreaterThan(80, $line->suggested_qty);
    }

    public function test_building_the_same_plan_twice_replaces_it_rather_than_stacking_lines(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf');
        $target = Carbon::parse('2026-09-22');

        foreach ([1, 2] as $weeksBack) {
            $this->counted($shop, $target->copy()->subWeeks($weeksBack), [$bread->id => [50, 5, null]]);
        }

        app(PlanBuilder::class)->build($shop, $target);
        $plan = app(PlanBuilder::class)->build($shop, $target);

        $this->assertSame(1, Plan::where('user_id', $shop->id)->count());
        $this->assertCount(1, $plan->lines);
    }

    public function test_the_plan_is_written_in_the_shops_language_whatever_the_server_is_doing(): void
    {
        $shop = $this->shop();
        $shop->update(['locale' => 'sq']);
        $bread = $this->product($shop, 'Bukë e bardhë', 100);
        $target = Carbon::parse('2026-09-22');

        foreach ([1, 2, 3] as $weeksBack) {
            $this->counted($shop, $target->copy()->subWeeks($weeksBack), [$bread->id => [100, 20, null]]);
        }

        app()->setLocale('en');
        $plan = app(PlanBuilder::class)->build($shop, $target);

        $this->assertStringContainsString('Plani i pjekjes', $plan->body);
        $this->assertSame('en', app()->getLocale(), 'the locale must be put back where it was found');
    }

    public function test_the_command_skips_shops_outside_their_own_plan_hour(): void
    {
        $shop = $this->shop();
        $this->product($shop, 'White loaf');
        $shop->update(['plan_at' => '04:00']);

        // Nowhere near four in the morning in Tirana.
        Carbon::setTestNow(Carbon::parse('2026-09-21 13:00', 'Europe/Tirane'));

        $this->artisan('leftover:plan')->assertSuccessful();
        $this->assertSame(0, $shop->plans()->count());

        Carbon::setTestNow(Carbon::parse('2026-09-21 04:20', 'Europe/Tirane'));
        $this->artisan('leftover:plan')->assertSuccessful();
        $this->assertSame(1, $shop->plans()->count());

        Carbon::setTestNow();
    }

    public function test_a_dry_run_writes_nothing(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf');
        $target = Carbon::parse('2026-09-22');

        foreach ([1, 2] as $weeksBack) {
            $this->counted($shop, $target->copy()->subWeeks($weeksBack), [$bread->id => [50, 5, null]]);
        }

        $this->artisan('leftover:plan --now --dry-run')->assertSuccessful();

        $this->assertSame(0, $shop->plans()->count());
    }

    public function test_a_shop_with_no_products_is_passed_over_quietly(): void
    {
        $shop = $this->shop();

        $this->artisan('leftover:plan --now')->assertSuccessful();

        $this->assertSame(0, $shop->plans()->count());
    }

    public function test_the_plan_page_builds_one_on_the_spot_if_the_job_has_not_run(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf');
        $tomorrow = $shop->today()->copy()->addDay();

        foreach ([1, 2, 3] as $weeksBack) {
            $this->counted($shop, $tomorrow->copy()->subWeeks($weeksBack), [$bread->id => [50, 6, null]]);
        }

        $this->actingAs($shop)->get(route('plan.show'))->assertOk()->assertSee('White loaf');
        $this->assertSame(1, $shop->plans()->count());
    }

    public function test_another_bakerys_plan_is_never_visible(): void
    {
        $mine = $this->shop();
        $theirs = $this->shop('other@example.test');
        $theirLoaf = $this->product($theirs, 'Their secret loaf');

        $target = $theirs->today()->copy()->addDay();
        foreach ([1, 2] as $weeksBack) {
            $this->counted($theirs, $target->copy()->subWeeks($weeksBack), [$theirLoaf->id => [50, 5, null]]);
        }
        app(PlanBuilder::class)->build($theirs, $target);

        $this->product($mine, 'My loaf');
        $this->actingAs($mine)->get(route('plan.show'))->assertOk()->assertDontSee('Their secret loaf');
    }
}
