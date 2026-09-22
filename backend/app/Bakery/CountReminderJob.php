<?php

namespace App\Bakery;

use App\Messaging\Messenger;
use App\Models\DayClosing;
use App\Models\Organization;
use App\Models\User;
use App\Support\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Some minutes after closing, if today's count is neither finished nor skipped, ask the counter
 * staff on WhatsApp to count what is left (the owner when no staff has a phone). Once a day.
 */
final class CountReminderJob
{
    public const SENT = 'sent';

    public const ALREADY_SENT = 'already_sent';

    public const DONE = 'done';

    public const NO_RECIPIENT = 'no_recipient';

    public const CLOSED = 'closed';

    public const NOT_YET = 'not_yet';

    public function __construct(private readonly Messenger $messenger) {}

    /** @param  bool  $now  run as if the reminder time had come (the demo's "run now" button) */
    public function run(Organization $organization, bool $now = false): string
    {
        return Tenant::run($organization->id, function () use ($organization, $now) {
            $clock = ShopClock::for($organization);
            $today = $clock->today();
            $weekday = ShopClock::weekdayOf($today);
            if (! $clock->isOpenOn($weekday)) {
                return self::CLOSED;
            }
            if (! $now && $clock->minuteOfDay() < $clock->closesMinute($weekday) + $organization->count_reminder_offset) {
                return self::NOT_YET;
            }

            return DB::transaction(function () use ($clock, $organization, $today, $weekday) {
                DayClosing::createOrFirst(['date' => $today], ['status' => DayClosing::OPEN]);
                $closing = DayClosing::where('date', $today)->lockForUpdate()->first();
                if ($closing->isDone()) {
                    return self::DONE;
                }
                if ($closing->reminded_at !== null) {
                    return self::ALREADY_SENT;
                }
                $recipients = $this->recipients($organization);
                if ($recipients->isEmpty()) {
                    return self::NO_RECIPIENT;
                }
                foreach ($recipients as $recipient) {
                    $this->messenger->send(
                        $organization->id,
                        $recipient['phone'],
                        $recipient['name'],
                        'count_reminder',
                        $recipient['locale'],
                        ['name' => $recipient['name'], 'time' => ShopClock::clock($clock->closesMinute($weekday))],
                        config('product.public_url').'/count',
                        'day_closing',
                        $closing->id,
                    );
                }
                $closing->forceFill(['reminded_at' => now()])->save();

                return self::SENT;
            });
        });
    }

    /**
     * Staff with a phone; otherwise the owners with a phone; otherwise the plan phone.
     *
     * @return Collection<int, array{phone: string, name: string, locale: string}>
     */
    public function recipients(Organization $organization): Collection
    {
        $people = User::where('organization_id', $organization->id)->whereNotNull('phone')->orderBy('id')->get();
        $chosen = $people->where('role', User::STAFF);
        if ($chosen->isEmpty()) {
            $chosen = $people->where('role', User::OWNER);
        }
        $recipients = $chosen->map(fn (User $u) => ['phone' => $u->phone, 'name' => strtok($u->name, ' '), 'locale' => $u->locale])->values();
        if ($recipients->isEmpty() && $organization->plan_phone) {
            $recipients->push(['phone' => $organization->plan_phone, 'name' => $organization->name, 'locale' => $organization->locale]);
        }

        return $recipients;
    }
}
