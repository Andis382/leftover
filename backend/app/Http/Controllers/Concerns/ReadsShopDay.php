<?php

namespace App\Http\Controllers\Concerns;

use App\Bakery\ShopClock;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Controllers that work on one day of the shop, given as "2026-09-23" in the URL. */
trait ReadsShopDay
{
    protected function clock(Request $request): ShopClock
    {
        return ShopClock::for($request->user()->organization);
    }

    /** A real calendar day, or a 404 (the URL is wrong, not the input). */
    protected function day(?string $date, ShopClock $clock): string
    {
        if ($date === null) {
            return $clock->today();
        }
        abort_unless(preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]), 404);

        return $date;
    }

    protected function notInFuture(string $date, ShopClock $clock, string $field = 'date'): void
    {
        if ($date > $clock->today()) {
            throw ValidationException::withMessages([$field => [__('errors.day_in_future')]]);
        }
    }

    protected function notInPast(string $date, ShopClock $clock, string $field = 'date'): void
    {
        if ($date < $clock->today()) {
            throw ValidationException::withMessages([$field => [__('errors.day_in_past')]]);
        }
    }
}
