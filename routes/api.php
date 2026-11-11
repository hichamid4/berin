<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\BroadcastAuthController;
use App\Http\Controllers\Api\Mobile\ConversationController;
use App\Http\Controllers\Api\Mobile\Handyman\OfferController as HandymanOfferController;
use App\Http\Controllers\Api\Mobile\Handyman\WorkController;
use App\Http\Controllers\Api\Mobile\HomeController;
use App\Http\Controllers\Api\Mobile\Homeowner\OfferDecisionController;
use App\Http\Controllers\Api\Mobile\Homeowner\RequestController;
use App\Http\Controllers\Api\Mobile\MessageController;
use App\Http\Controllers\Api\Mobile\ProfileController;
use App\Http\Controllers\Api\Mobile\RealtimeController;
use Illuminate\Support\Facades\Route;

/*
| BreIn mobile API, version 1 (/api/v1/mobile/...). Homeowners and handymen only: administration
| stays on the website, and the website keeps its WhatsApp contact workflow unchanged.
| Private WebSocket channels are authorized at POST /api/v1/mobile/broadcasting/auth.
*/

Route::prefix('v1/mobile')->name('api.mobile.')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:mobile-login')->name('auth.login');

    Route::middleware(['auth:sanctum', 'mobile', 'throttle:mobile-api'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [ProfileController::class, 'show'])->name('me');
        Route::get('/home', HomeController::class)->name('home');
        Route::get('/realtime', RealtimeController::class)->name('realtime');
        Route::post('/broadcasting/auth', BroadcastAuthController::class)->name('broadcasting.auth');

        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('/conversations/unread-count', [ConversationController::class, 'unreadCount'])->name('conversations.unread');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->whereNumber('conversation')->name('conversations.show');
        Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index'])->whereNumber('conversation')->name('messages.index');
        Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->whereNumber('conversation')->middleware('throttle:mobile-messages')->name('messages.store');
        Route::post('/conversations/{conversation}/read', [MessageController::class, 'markRead'])->whereNumber('conversation')->name('messages.read');

        Route::prefix('homeowner')->name('homeowner.')->middleware('role:homeowner')->group(function (): void {
            Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
            Route::get('/requests/{serviceRequest}', [RequestController::class, 'show'])->whereNumber('serviceRequest')->name('requests.show');
            Route::post('/offers/{offer}/accept', [OfferDecisionController::class, 'accept'])->name('offers.accept');
            Route::post('/offers/{offer}/decline', [OfferDecisionController::class, 'decline'])->name('offers.decline');
        });

        Route::prefix('handyman')->name('handyman.')->middleware('role:handyman')->group(function (): void {
            Route::get('/offers', [HandymanOfferController::class, 'index'])->name('offers.index');
            Route::get('/requests', [WorkController::class, 'index'])->name('requests.index');
        });
    });
});
