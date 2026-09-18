<?php

namespace App\Services\Notify;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/** Writes the plan to the log and calls it delivered. For demos and tests. */
class LogNotifier implements PlanNotifier
{
    public function name(): string
    {
        return 'log';
    }

    public function send(User $user, Plan $plan): bool
    {
        Log::info('leftover: plan for '.$user->displayName()."\n".$plan->body);

        return true;
    }
}
