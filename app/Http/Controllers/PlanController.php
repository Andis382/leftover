<?php

namespace App\Http\Controllers;

use App\Services\PlanBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PlanController extends Controller
{
    public function __construct(private readonly PlanBuilder $builder)
    {
    }

    public function show(Request $request, ?string $date = null)
    {
        $user = $request->user();
        $on = $this->date($user->today(), $date);

        $plan = $user->plans()->with('lines.product')->firstWhere('for_date', $on->toDateString());

        // No plan written yet — because the shop signed up this afternoon, or
        // because the hourly job has not come round. Build one now rather than
        // showing an empty page and an explanation nobody wants to read.
        if (! $plan && $user->activeProducts()->exists()) {
            $plan = $this->builder->build($user, $on);
        }

        return view('plan', [
            'user' => $user,
            'date' => $on,
            'plan' => $plan,
            'weekdayName' => $on->locale($user->locale)->translatedFormat('l'),
        ]);
    }

    public function rebuild(Request $request, string $date)
    {
        $user = $request->user();
        $on = $this->date($user->today(), $date);

        $this->builder->build($user, $on);

        return redirect()->route('plan.show', $on->toDateString())->with('status', __('flash.plan_rebuilt'));
    }

    private function date(\Carbon\CarbonInterface $today, ?string $value): \Carbon\CarbonInterface
    {
        if ($value === null) {
            // The useful default is the next day you will be baking for, which
            // before closing time is today and after it is tomorrow. Tomorrow
            // is the honest default: you plan the bake the night before.
            return $today->copy()->addDay();
        }

        try {
            // The shop's timezone, compared as a calendar date. See the same
            // note in SheetController: midnight in Tirana is the previous day
            // in UTC, and comparing instants makes today look like the future.
            $on = Carbon::parse($value, $today->getTimezone())->startOfDay();
        } catch (\Throwable) {
            throw new NotFoundHttpException();
        }

        if ($on->toDateString() > $today->copy()->addDays(14)->toDateString()
            || $on->toDateString() < $today->copy()->subYears(2)->toDateString()) {
            throw new NotFoundHttpException();
        }

        return $on;
    }
}
