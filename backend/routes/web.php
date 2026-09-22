<?php

use Illuminate\Support\Facades\Route;

/*
 * Production serves the built Vue app from public/ (see README). Every non-API path returns
 * its index.html so client-side routes survive a reload. In development Vite serves the SPA.
 */
Route::get('/{any?}', function () {
    $index = public_path('index.html');
    abort_unless(is_file($index), 404, 'Build the frontend first (npm run build in frontend/).');

    return response()->file($index, ['Cache-Control' => 'no-cache']);
})->where('any', '^(?!api/|up$).*');
