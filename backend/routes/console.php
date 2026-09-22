<?php

use App\Bakery\CountReminderJob;
use App\Bakery\MorningPlanJob;
use App\Models\Organization;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Both jobs run every minute and decide per organisation, in its own time zone, whether it is
// time. Each does its work at most once a day, so a missed or doubled run is harmless.

Artisan::command('leftover:morning-plans', function (MorningPlanJob $job) {
    Organization::query()->orderBy('id')->each(function (Organization $organization) use ($job) {
        $outcome = $job->run($organization);
        if ($outcome === MorningPlanJob::SENT) {
            $this->info("{$organization->name}: bake plan sent");
        }
    });
})->purpose('Make and send today\'s bake plan where it is plan time');

Artisan::command('leftover:count-reminders', function (CountReminderJob $job) {
    Organization::query()->orderBy('id')->each(function (Organization $organization) use ($job) {
        $outcome = $job->run($organization);
        if ($outcome === CountReminderJob::SENT) {
            $this->info("{$organization->name}: count reminder sent");
        }
    });
})->purpose('Remind staff to count what is left where the shop has closed');

Schedule::command('leftover:morning-plans')->everyMinute()->withoutOverlapping();
Schedule::command('leftover:count-reminders')->everyMinute()->withoutOverlapping();
