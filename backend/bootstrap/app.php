<?php

use App\Http\Middleware\EnsureOwner;
use App\Http\Middleware\SetLocaleFromRequest;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Session cookies + CSRF for requests coming from the SPA (Sanctum "stateful" API).
        $middleware->statefulApi();
        $middleware->validateCsrfTokens(except: ['api/webhooks/*']);
        $middleware->api(prepend: [SetLocaleFromRequest::class]);
        $middleware->alias(['owner' => EnsureOwner::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // The SPA always gets JSON: {"message": "...", "errors": {...}}.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => $request->is('api/*') || $request->expectsJson());
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => __('errors.validation'), 'errors' => $e->errors()], $e->status);
            }
        });
    })->create();
