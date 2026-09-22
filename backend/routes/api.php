<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AutomationController;
use App\Http\Controllers\CountController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\MessagesController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/auth/csrf', [AuthController::class, 'csrf']);
Route::post('/auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/auth/invitations/{token}', [AuthController::class, 'invitation']);
Route::post('/auth/join', [AuthController::class, 'join'])->middleware('throttle:10,1');
Route::get('/public/files/{file}', [FilesController::class, 'publicShow'])->name('files.public')->middleware('signed:relative');
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify']);
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive']);

// Signed in
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'show']);
    Route::put('/auth/me', [AuthController::class, 'update']);

    Route::get('/organization', [OrganizationController::class, 'show']);
    Route::put('/organization', [OrganizationController::class, 'update']);

    Route::get('/team', [TeamController::class, 'index']);
    Route::post('/team/invitations', [TeamController::class, 'invite']);
    Route::delete('/team/invitations/{id}', [TeamController::class, 'revoke'])->whereNumber('id');
    Route::delete('/team/members/{id}', [TeamController::class, 'remove'])->whereNumber('id');

    Route::get('/messages', [MessagesController::class, 'outbox']);
    Route::get('/messages/inbound', [MessagesController::class, 'inbox']);
    Route::post('/messages/{id}/retry', [MessagesController::class, 'retry'])->whereNumber('id');
    Route::post('/dev/inbound', [MessagesController::class, 'simulate']);

    Route::get('/files/{id}', [FilesController::class, 'show']);

    // Everyone in the shop: count at closing, read the plans.
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/shop', [ShopController::class, 'show']);
    Route::get('/count/{date?}', [CountController::class, 'show']);
    Route::patch('/count/{date}/items/{product}', [CountController::class, 'update'])->whereNumber('product');
    Route::post('/count/{date}/finish', [CountController::class, 'finish']);
    Route::post('/count/{date}/skip', [CountController::class, 'skip']);
    Route::post('/count/{date}/reopen', [CountController::class, 'reopen']);
    Route::get('/plans/{date}', [PlanController::class, 'show']);

    // The owner: products, plan changes, the morning confirmation, insights and settings.
    Route::middleware('owner')->group(function () {
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update'])->whereNumber('id');
        Route::post('/products/{id}/move', [ProductController::class, 'move'])->whereNumber('id');
        Route::post('/products/{id}/archive', [ProductController::class, 'archive'])->whereNumber('id');
        Route::post('/products/{id}/restore', [ProductController::class, 'restore'])->whereNumber('id');

        Route::put('/plans/{date}/items/{product}', [PlanController::class, 'setWillBake'])->whereNumber('product');
        Route::post('/plans/{date}/baked', [PlanController::class, 'confirmBaked']);
        Route::get('/plans/{date}/message', [PlanController::class, 'message']);
        Route::post('/plans/{date}/send', [PlanController::class, 'send']);

        Route::get('/insights', [InsightsController::class, 'show']);
        Route::put('/shop', [ShopController::class, 'update']);

        Route::get('/automation', [AutomationController::class, 'show']);
        Route::post('/automation/morning-plan', [AutomationController::class, 'runMorningPlan']);
        Route::post('/automation/count-reminder', [AutomationController::class, 'runCountReminder']);
    });
});
