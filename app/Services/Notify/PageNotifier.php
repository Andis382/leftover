<?php

namespace App\Services\Notify;

use App\Models\Plan;
use App\Models\User;

/**
 * The default. The plan is written, and it waits on its page.
 *
 * This is not a stub. For a one-person bakery, a page that is already correct
 * when you unlock your phone at four is the entire feature; a push notification
 * is a convenience on top of it. Shipping this as the default keeps the product
 * usable by someone who will never create a bot token, and keeps the app free
 * of any third-party account it does not truly need.
 */
class PageNotifier implements PlanNotifier
{
    public function name(): string
    {
        return 'none';
    }

    public function send(User $user, Plan $plan): bool
    {
        return false;
    }
}
