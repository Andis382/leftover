<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * The interface speaks the shop's language, not the server's.
 *
 * It runs per request rather than in a provider because the session, and
 * therefore the signed-in bakery, does not exist yet when providers boot.
 */
class SetShopLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->user()?->locale ?: config('leftover.defaults.locale');

        if (array_key_exists($locale, config('leftover.locales'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
