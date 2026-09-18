<?php

namespace App\Http\Controllers;

use App\Models\DaySheet;
use App\Services\WasteReport;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function landing()
    {
        return auth()->check() ? redirect()->route('dashboard') : view('landing');
    }

    public function index(Request $request, WasteReport $waste)
    {
        $user = $request->user();
        $today = $user->today();

        $sheet = $user->daySheets()->with('counts')->firstWhere('on_date', $today->toDateString());
        $yesterday = $user->daySheets()->with('counts')->firstWhere('on_date', $today->copy()->subDay()->toDateString());

        return view('dashboard', [
            'user' => $user,
            'today' => $today,
            'sheet' => $sheet,

            // The one nag this product is allowed: yesterday is still blank and
            // it is now tomorrow. Shown once, on the front page, never sent.
            'unfinished' => $yesterday && ! $yesterday->isCounted() && $yesterday->status === DaySheet::OPEN
                ? $yesterday
                : null,

            'plan' => $user->plans()->with('lines.product')->firstWhere('for_date', $today->toDateString()),
            'tomorrow' => $user->plans()->with('lines.product')->firstWhere('for_date', $today->copy()->addDay()->toDateString()),
            'week' => $waste->between($user, $today->copy()->subDays(6), $today),
            'previousWeek' => $waste->between($user, $today->copy()->subDays(13), $today->copy()->subDays(7)),
            'products' => $user->activeProducts()->count(),
        ]);
    }
}
