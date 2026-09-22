<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Answers (validation messages, errors) in the language the web app is shown in. */
class SetLocaleFromRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $preferred = $request->getPreferredLanguage(['en', 'sq']);
        app()->setLocale($preferred ?: config('app.locale', 'en'));

        return $next($request);
    }
}
