<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SheetController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'landing'])->name('home');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
});

Route::post('logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('today', [DashboardController::class, 'index'])->name('dashboard');

    // The closing count. `count/{date?}` so yesterday can still be filled in
    // this morning, which is what actually happens after a hard Saturday.
    Route::get('count/{date?}', [SheetController::class, 'edit'])->name('sheet.edit');
    Route::post('count/{date}', [SheetController::class, 'update'])->name('sheet.update');
    Route::post('count/{date}/mark', [SheetController::class, 'mark'])->name('sheet.mark');

    Route::get('plan/{date?}', [PlanController::class, 'show'])->name('plan.show');
    Route::post('plan/{date}/rebuild', [PlanController::class, 'rebuild'])->name('plan.rebuild');

    Route::get('history', [HistoryController::class, 'index'])->name('history');

    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
});
