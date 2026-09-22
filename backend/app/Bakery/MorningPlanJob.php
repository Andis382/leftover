<?php

namespace App\Bakery;

use App\Models\Organization;
use App\Support\Tenant;

/**
 * At plan time (04:00 by default, shop time) make today's plan if nobody did yet and send it
 * to the baker. Runs every minute; making and sending both happen at most once a day.
 */
final class MorningPlanJob
{
    public const SENT = 'sent';

    public const ALREADY_SENT = 'already_sent';

    public const NO_RECIPIENT = 'no_recipient';

    public const CLOSED = 'closed';

    public const NOT_YET = 'not_yet';

    public const TOO_LATE = 'too_late';

    public function __construct(
        private readonly PlanService $plans,
        private readonly PlanSender $sender,
    ) {}

    /** @param  bool  $now  run as if it were plan time (the demo's "run now" button) */
    public function run(Organization $organization, bool $now = false): string
    {
        return Tenant::run($organization->id, function () use ($organization, $now) {
            $clock = ShopClock::for($organization);
            $today = $clock->today();
            $weekday = ShopClock::weekdayOf($today);
            if (! $clock->isOpenOn($weekday)) {
                return self::CLOSED;
            }
            if (! $now) {
                $minute = $clock->minuteOfDay();
                if ($minute < ShopClock::minutes($organization->planTime())) {
                    return self::NOT_YET;
                }
                if ($minute >= $clock->closesMinute($weekday)) {
                    return self::TOO_LATE;
                }
            }
            $plan = $this->plans->generate($clock, $today);
            if ($plan->sent_at !== null) {
                return self::ALREADY_SENT;
            }
            if ($this->sender->recipient($clock)['phone'] === null) {
                return self::NO_RECIPIENT;
            }

            return $this->sender->sendOnce($clock, $plan) !== null ? self::SENT : self::ALREADY_SENT;
        });
    }
}
