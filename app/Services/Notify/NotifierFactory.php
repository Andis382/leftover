<?php

namespace App\Services\Notify;

use App\Models\User;

/**
 * Picks a delivery route per shop, falling back to the page.
 *
 * Per shop rather than per install, because one bakery on a server may have a
 * bot and the next may not, and the one without must still get a plan.
 */
class NotifierFactory
{
    public function for(User $user): PlanNotifier
    {
        return match ($user->notify_driver ?: config('leftover.notify.driver')) {
            'telegram' => new TelegramNotifier(config('leftover.notify.telegram_token')),
            'log' => new LogNotifier(),
            default => new PageNotifier(),
        };
    }
}
