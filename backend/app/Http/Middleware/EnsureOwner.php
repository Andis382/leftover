<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Settings, products and plan changes are the owner's; counter staff count and read plans. */
class EnsureOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user()?->hasRole(User::OWNER), 403, __('errors.forbidden'));

        return $next($request);
    }
}
