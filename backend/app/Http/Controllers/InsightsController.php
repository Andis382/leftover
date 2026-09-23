<?php

namespace App\Http\Controllers;

use App\Bakery\InsightsService;
use App\Bakery\ShopClock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InsightsController extends Controller
{
    public function show(Request $request, InsightsService $insights): JsonResponse
    {
        $data = $request->validate(['days' => ['nullable', 'integer', Rule::in([7, 30])]]);
        $clock = ShopClock::for($request->user()->organization);

        return response()->json($insights->build($clock, (int) ($data['days'] ?? 30), app()->getLocale()));
    }
}
