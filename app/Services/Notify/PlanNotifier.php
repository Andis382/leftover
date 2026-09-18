<?php

namespace App\Services\Notify;

use App\Models\Plan;
use App\Models\User;

/**
 * How a plan gets in front of the baker.
 *
 * The default is "it does not": the plan is a page, and opening it while the
 * oven heats costs nothing and needs no account anywhere. Everything beyond
 * that is opt-in, per shop.
 */
interface PlanNotifier
{
    public function name(): string;

    /** True when something was actually delivered. */
    public function send(User $user, Plan $plan): bool;
}
