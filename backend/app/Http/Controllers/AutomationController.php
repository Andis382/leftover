<?php

namespace App\Http\Controllers;

use App\Bakery\CountReminderJob;
use App\Bakery\MorningPlanJob;
use App\Bakery\PlanSender;
use App\Bakery\ShopClock;
use App\Models\DayClosing;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** What the two daily jobs will do and did today; in demo mode they can be run on demand. */
class AutomationController extends Controller
{
    public function show(Request $request, PlanSender $sender, CountReminderJob $reminders): JsonResponse
    {
        $organization = $request->user()->organization;
        $clock = ShopClock::for($organization);
        $today = $clock->today();
        $weekday = ShopClock::weekdayOf($today);
        $plan = Plan::where('date', $today)->first();
        $closing = DayClosing::where('date', $today)->first();
        $closingAt = $clock->closingAt($today);

        return response()->json([
            'demo' => (bool) config('product.demo'),
            'timezone' => $clock->timezone(),
            'today' => $today,
            'openToday' => $clock->isOpenOn($weekday),
            'planTime' => $organization->planTime(),
            'planAt' => $clock->planTimeOn($today)->toIso8601String(),
            'planGeneratedAt' => $plan?->generated_at?->toIso8601String(),
            'planSentAt' => $plan?->sent_at?->toIso8601String(),
            'planRecipient' => $sender->recipient($clock)['phone'],
            'reminderAt' => $closingAt?->addMinutes($organization->count_reminder_offset)->toIso8601String(),
            'reminderSentAt' => $closing?->reminded_at?->toIso8601String(),
            'countStatus' => $closing->status ?? DayClosing::OPEN,
            'reminderRecipients' => $reminders->recipients($organization)->map(fn (array $r) => ['name' => $r['name'], 'phone' => $r['phone']])->all(),
        ]);
    }

    public function runMorningPlan(Request $request, MorningPlanJob $job, PlanSender $sender, CountReminderJob $reminders): JsonResponse
    {
        abort_unless(config('product.demo'), 404);
        $outcome = $job->run($request->user()->organization, now: true);

        return response()->json(['outcome' => $outcome] + $this->show($request, $sender, $reminders)->getData(true));
    }

    public function runCountReminder(Request $request, CountReminderJob $job, PlanSender $sender): JsonResponse
    {
        abort_unless(config('product.demo'), 404);
        $outcome = $job->run($request->user()->organization, now: true);

        return response()->json(['outcome' => $outcome] + $this->show($request, $sender, $job)->getData(true));
    }
}
