<?php

namespace Tests\Feature;

use App\Models\DaySheet;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CountingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function shop(): User
    {
        return User::create([
            'name' => 'Petrit',
            'shop_name' => 'Furra Petrela',
            'email' => 'p@example.test',
            'password' => 'secret-secret',
            'locale' => 'en',
            'currency' => 'EUR',
            'timezone' => 'Europe/Tirane',
            'opens_at' => '06:00',
            'closes_at' => '20:00',
            'plan_at' => '04:00',
        ]);
    }

    private function product(User $shop, string $name, array $attributes = []): Product
    {
        return $shop->products()->create($attributes + [
            'name' => $name,
            'unit' => 'piece',
            'category' => 'bread',
            'price' => 1.00,
            'typical_batch' => 50,
            'round_to' => 1,
            'active' => true,
        ]);
    }

    /**
     * The rule the whole screen is built on. Twenty-eight blanks and two
     * numbers is a real record; the count that never happened is not.
     */
    public function test_a_sheet_saves_with_two_products_out_of_thirty(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf');
        $this->product($shop, 'Croissant');
        $this->product($shop, 'Burek');

        $today = $shop->today()->toDateString();

        $this->actingAs($shop)->post(route('sheet.update', $today), [
            'entries' => [
                $bread->id => ['baked' => 100, 'left' => 12],
            ],
        ])->assertRedirect();

        $sheet = $shop->daySheets()->firstWhere('on_date', $today);

        $this->assertSame(DaySheet::COUNTED, $sheet->status);
        $this->assertCount(1, $sheet->counts, 'the two blank products must not become rows of zero');
        $this->assertSame(12, $sheet->counts->first()->left_qty);
    }

    public function test_counting_the_same_day_again_corrects_it_rather_than_duplicating_it(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf');
        $today = $shop->today()->toDateString();

        $this->actingAs($shop)->post(route('sheet.update', $today), [
            'entries' => [$bread->id => ['baked' => 100, 'left' => 12]],
        ]);

        // Somebody finds a tray in the back.
        $this->actingAs($shop)->post(route('sheet.update', $today), [
            'entries' => [$bread->id => ['baked' => 100, 'left' => 20]],
        ]);

        $sheet = $shop->daySheets()->firstWhere('on_date', $today);
        $this->assertCount(1, $sheet->counts);
        $this->assertSame(20, $sheet->counts->first()->left_qty);
    }

    /**
     * Sold out and "six left" cannot both be true, and the sell-out is the one
     * that has to give: the count is what somebody actually saw.
     */
    public function test_a_leftover_cancels_a_mis_tapped_sell_out(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf');
        $today = $shop->today()->toDateString();

        $this->actingAs($shop)->post(route('sheet.update', $today), [
            'entries' => [$bread->id => ['baked' => 100, 'left' => 6, 'sold_out_at' => '11:00']],
        ]);

        $count = $shop->daySheets()->firstWhere('on_date', $today)->counts->first();
        $this->assertNull($count->sold_out_at);
        $this->assertSame(6, $count->left_qty);
    }

    public function test_a_sell_out_with_nothing_left_is_kept(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf');
        $today = $shop->today()->toDateString();

        $this->actingAs($shop)->post(route('sheet.update', $today), [
            'entries' => [$bread->id => ['baked' => 100, 'left' => 0, 'sold_out_at' => '11:00']],
        ]);

        $count = $shop->daySheets()->firstWhere('on_date', $today)->counts->first();
        $this->assertSame('11:00', substr((string) $count->sold_out_at, 0, 5));
    }

    public function test_more_cannot_be_left_than_was_baked(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf');
        $today = $shop->today()->toDateString();

        $this->actingAs($shop)->post(route('sheet.update', $today), [
            'entries' => [$bread->id => ['baked' => 40, 'left' => 90]],
        ]);

        $count = $shop->daySheets()->firstWhere('on_date', $today)->counts->first();
        $this->assertSame(40, $count->left_qty);
    }

    public function test_clearing_a_number_removes_it_from_the_history(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf');
        $today = $shop->today()->toDateString();

        $this->actingAs($shop)->post(route('sheet.update', $today), [
            'entries' => [$bread->id => ['baked' => 100, 'left' => 12]],
        ]);
        $this->actingAs($shop)->post(route('sheet.update', $today), [
            'entries' => [$bread->id => ['baked' => null, 'left' => null]],
        ]);

        $sheet = $shop->daySheets()->firstWhere('on_date', $today);
        $this->assertCount(0, $sheet->counts);
        $this->assertSame(DaySheet::OPEN, $sheet->status, 'an emptied sheet is uncounted again, not counted-as-nothing');
    }

    public function test_a_day_the_shop_was_shut_is_recorded_as_such(): void
    {
        $shop = $this->shop();
        $this->product($shop, 'White loaf');
        $today = $shop->today()->toDateString();

        $this->actingAs($shop)->post(route('sheet.mark', $today), ['status' => 'shut'])->assertRedirect();

        $this->assertSame(DaySheet::SHUT, $shop->daySheets()->firstWhere('on_date', $today)->status);
    }

    public function test_a_sheet_cannot_be_dated_in_the_future(): void
    {
        $shop = $this->shop();
        $this->product($shop, 'White loaf');
        $tomorrow = $shop->today()->copy()->addDay()->toDateString();

        $this->actingAs($shop)->get(route('sheet.edit', $tomorrow))->assertNotFound();
    }

    public function test_yesterday_can_still_be_filled_in_this_morning(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf');
        $yesterday = $shop->today()->copy()->subDay()->toDateString();

        $this->actingAs($shop)->get(route('sheet.edit', $yesterday))->assertOk();
        $this->actingAs($shop)->post(route('sheet.update', $yesterday), [
            'entries' => [$bread->id => ['baked' => 90, 'left' => 4]],
        ])->assertRedirect();

        $this->assertSame(DaySheet::COUNTED, $shop->daySheets()->firstWhere('on_date', $yesterday)->status);
    }

    public function test_a_product_belonging_to_another_bakery_is_ignored(): void
    {
        $mine = $this->shop();
        $theirs = User::create([
            'name' => 'Other', 'email' => 'o@example.test', 'password' => 'secret-secret',
            'locale' => 'en', 'currency' => 'EUR', 'timezone' => 'Europe/Tirane',
            'opens_at' => '06:00', 'closes_at' => '20:00', 'plan_at' => '04:00',
        ]);

        $theirLoaf = $this->product($theirs, 'Their loaf');
        $today = $mine->today()->toDateString();

        $this->actingAs($mine)->post(route('sheet.update', $today), [
            'entries' => [$theirLoaf->id => ['baked' => 100, 'left' => 10]],
        ]);

        $this->assertSame(0, $mine->daySheets()->firstWhere('on_date', $today)->counts()->count());
        $this->assertSame(0, $theirLoaf->counts()->count());
    }

    public function test_a_product_that_has_been_counted_is_retired_rather_than_deleted(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf');
        $today = $shop->today()->toDateString();

        $this->actingAs($shop)->post(route('sheet.update', $today), [
            'entries' => [$bread->id => ['baked' => 100, 'left' => 10]],
        ]);

        $this->actingAs($shop)->delete(route('products.destroy', $bread))->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $bread->id, 'active' => false]);
        $this->assertSame(1, $bread->counts()->count(), 'the history must survive a product leaving the list');
    }

    public function test_a_product_never_counted_can_simply_go(): void
    {
        $shop = $this->shop();
        $typo = $this->product($shop, 'Croisant');

        $this->actingAs($shop)->delete(route('products.destroy', $typo))->assertRedirect();

        $this->assertDatabaseMissing('products', ['id' => $typo->id]);
    }

    public function test_another_bakerys_product_does_not_exist(): void
    {
        $mine = $this->shop();
        $theirs = User::create([
            'name' => 'Other', 'email' => 'o2@example.test', 'password' => 'secret-secret',
            'locale' => 'en', 'currency' => 'EUR', 'timezone' => 'Europe/Tirane',
            'opens_at' => '06:00', 'closes_at' => '20:00', 'plan_at' => '04:00',
        ]);
        $theirLoaf = $this->product($theirs, 'Their loaf');

        $this->actingAs($mine)->get(route('products.edit', $theirLoaf))->assertNotFound();
        $this->actingAs($mine)->delete(route('products.destroy', $theirLoaf))->assertNotFound();
    }

    public function test_a_guest_is_sent_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('sheet.edit'))->assertRedirect(route('login'));
        $this->get(route('plan.show'))->assertRedirect(route('login'));
    }

    public function test_the_counting_screen_shows_what_happened_last_week(): void
    {
        $shop = $this->shop();
        $bread = $this->product($shop, 'White loaf');

        $lastWeek = $shop->today()->copy()->subWeek();
        $sheet = $shop->daySheets()->create([
            'on_date' => $lastWeek->toDateString(),
            'weekday' => (int) $lastWeek->isoWeekday(),
            'status' => DaySheet::COUNTED,
            'counted_at' => Carbon::parse($lastWeek)->setTime(20, 10),
        ]);
        $sheet->counts()->create(['product_id' => $bread->id, 'baked_qty' => 88, 'left_qty' => 7]);

        $this->actingAs($shop)->get(route('sheet.edit'))
            ->assertOk()
            ->assertSee('88')
            ->assertSee('White loaf');
    }
}
