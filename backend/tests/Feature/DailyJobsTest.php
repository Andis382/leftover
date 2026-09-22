<?php

namespace Tests\Feature;

use App\Models\DailyRecord;
use App\Models\OutboundMessage;
use App\Models\Plan;
use App\Models\ShopHour;

/** The 4 a.m. plan and the closing reminder, driven through the scheduled commands. */
class DailyJobsTest extends BakeryTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->product(['name' => 'Bukë e bardhë']);
        $this->product(['name' => 'Kroasan i thjeshtë']);
    }

    public function test_the_morning_plan_waits_for_plan_time_then_is_made_and_sent_once(): void
    {
        $this->at('2026-09-22 03:59');
        $this->artisan('leftover:morning-plans')->assertSuccessful();
        $this->assertSame(0, Plan::withoutGlobalScopes()->count());

        $this->at('2026-09-22 04:00');
        $this->artisan('leftover:morning-plans')->assertSuccessful();
        $this->at('2026-09-22 04:01');
        $this->artisan('leftover:morning-plans')->assertSuccessful();

        $plan = Plan::withoutGlobalScopes()->sole();
        $this->assertSame('2026-09-22', $plan->date->toDateString());
        $this->assertNull($plan->generated_by);
        $this->assertSame(2, DailyRecord::withoutGlobalScopes()->whereNotNull('planned_qty')->count());
        $message = OutboundMessage::withoutGlobalScopes()->sole();
        $this->assertSame('bake_plan', $message->template_key);
        $this->assertSame('355691112233', $message->recipient);
    }

    public function test_a_plan_the_baker_already_changed_is_kept_and_still_sent(): void
    {
        $bread = $this->product(['name' => 'Bukë fshati', 'baselines' => [10, 10, 10, 10, 10, 10, 10]]);
        $this->at('2026-09-21 21:00');
        $this->actingAs($this->owner)->putJson("/api/plans/2026-09-22/items/{$bread->id}", ['willBake' => 14])->assertOk();

        $this->at('2026-09-22 04:00');
        $this->artisan('leftover:morning-plans')->assertSuccessful();

        $this->assertSame($this->owner->id, Plan::withoutGlobalScopes()->sole()->generated_by);
        $this->assertSame(14, DailyRecord::withoutGlobalScopes()->where('product_id', $bread->id)->value('baked_qty'));
        $this->assertSame(1, OutboundMessage::withoutGlobalScopes()->count());
    }

    public function test_no_plan_on_a_day_the_shop_is_closed(): void
    {
        ShopHour::withoutGlobalScopes()->where('weekday', 7)->update(['opens_at' => null, 'closes_at' => null]);
        $this->at('2026-09-27 04:30'); // Sunday

        $this->artisan('leftover:morning-plans')->assertSuccessful();

        $this->assertSame(0, Plan::withoutGlobalScopes()->count());
    }

    public function test_the_count_reminder_goes_to_staff_once_after_closing(): void
    {
        $staff = $this->staff(phone: '355682223344');

        $this->at('2026-09-21 19:14'); // closes 19:00, reminder offset 15 minutes
        $this->artisan('leftover:count-reminders')->assertSuccessful();
        $this->assertSame(0, OutboundMessage::withoutGlobalScopes()->count());

        $this->at('2026-09-21 19:15');
        $this->artisan('leftover:count-reminders')->assertSuccessful();
        $this->artisan('leftover:count-reminders')->assertSuccessful();

        $message = OutboundMessage::withoutGlobalScopes()->sole();
        $this->assertSame('count_reminder', $message->template_key);
        $this->assertSame('355682223344', $message->recipient);
        $this->assertStringContainsString(strtok($staff->name, ' '), $message->body);
        $this->assertStringContainsString('/count', $message->body);
    }

    public function test_no_reminder_once_the_count_is_finished(): void
    {
        $this->staff(phone: '355682223344');
        $bread = $this->product(['name' => 'Bukë misri']);
        $this->at('2026-09-21 18:50');
        $this->actingAs($this->owner)->patchJson("/api/count/2026-09-21/items/{$bread->id}", ['left' => 2])->assertOk();
        $this->postJson('/api/count/2026-09-21/finish')->assertOk();

        $this->at('2026-09-21 19:30');
        $this->artisan('leftover:count-reminders')->assertSuccessful();

        $this->assertSame(0, OutboundMessage::withoutGlobalScopes()->count());
    }

    public function test_without_staff_phones_the_owner_is_reminded(): void
    {
        $this->staff();
        $this->at('2026-09-21 19:20');

        $this->artisan('leftover:count-reminders')->assertSuccessful();

        $this->assertSame('355691112233', OutboundMessage::withoutGlobalScopes()->sole()->recipient);
    }

    public function test_demo_buttons_run_the_jobs_now(): void
    {
        $this->at('2026-09-21 11:00');

        $this->actingAs($this->owner)->postJson('/api/automation/morning-plan')
            ->assertOk()
            ->assertJsonPath('outcome', 'sent')
            ->assertJsonPath('planSentAt', fn ($v) => $v !== null);
        $this->postJson('/api/automation/morning-plan')->assertOk()->assertJsonPath('outcome', 'already_sent');
        $this->postJson('/api/automation/count-reminder')->assertOk()->assertJsonPath('outcome', 'sent');
    }
}
