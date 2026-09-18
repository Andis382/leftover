<?php

namespace App\Http\Controllers;

use App\Services\WasteReport;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(Request $request, WasteReport $waste)
    {
        $user = $request->user();
        $today = $user->today();

        $days = (int) $request->integer('days', 28);
        $days = in_array($days, [7, 28, 90], true) ? $days : 28;

        $from = $today->copy()->subDays($days - 1);

        return view('history', [
            'user' => $user,
            'days' => $days,
            'from' => $from,
            'to' => $today,
            'totals' => $waste->between($user, $from, $today),
            'daily' => $waste->daily($user, $from, $today),
            'worst' => $waste->worstProducts($user, $from, $today),
        ]);
    }
}
