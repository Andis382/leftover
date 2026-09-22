<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Auth;

/**
 * Which organisation the current code runs for. Normally the signed-in user's;
 * scheduled jobs and webhooks set it explicitly with Tenant::run().
 */
class Tenant
{
    private static ?int $forced = null;

    public static function id(): ?int
    {
        if (self::$forced !== null) {
            return self::$forced;
        }

        return Auth::hasUser() ? Auth::user()->organization_id : null;
    }

    /** @template T  @param  Closure(): T  $callback  @return T */
    public static function run(int $organizationId, Closure $callback): mixed
    {
        $previous = self::$forced;
        self::$forced = $organizationId;
        try {
            return $callback();
        } finally {
            self::$forced = $previous;
        }
    }
}
