<?php

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\BroadcastAuthController;
use App\Http\Controllers\Api\Mobile\CatalogController;
use App\Http\Controllers\Api\Mobile\ConversationController;
use App\Http\Controllers\Api\Mobile\Handyman\OfferController as HandymanOfferController;
use App\Http\Controllers\Api\Mobile\Handyman\WorkController;
use App\Http\Controllers\Api\Mobile\HandymanDirectoryController;
use App\Http\Controllers\Api\Mobile\HomeController;
use App\Http\Controllers\Api\Mobile\Homeowner\FavoriteController;
use App\Http\Controllers\Api\Mobile\Homeowner\OfferDecisionController;
use App\Http\Controllers\Api\Mobile\Homeowner\RequestController;
use App\Http\Controllers\Api\Mobile\Homeowner\ReviewController;
use App\Http\Controllers\Api\Mobile\MessageController;
use App\Http\Controllers\Api\Mobile\ProfileController;
use App\Http\Controllers\Api\Mobile\RealtimeController;
use App\Http\Controllers\Api\Mobile\RegistrationController;
use App\Http\Controllers\PhotoController;
use Illuminate\Support\Facades\Route;

/*
| BreIn mobile API, version 1 (/api/v1/mobile/...). Homeowners and handymen only: administration
| stays on the website, and the website keeps its WhatsApp contact workflow unchanged.
| Private WebSocket channels are authorized at POST /api/v1/mobile/broadcasting/auth.
*/

Route::prefix('v1/mobile')->name('api.mobile.')->group(function (): void {
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:mobile-login')->name('auth.login');
    Route::post('/auth/register', [RegistrationController::class, 'store'])->middleware('throttle:6,1')->name('auth.register');

    Route::middleware(['auth:sanctum', 'mobile', 'throttle:mobile-api'])->group(function (): void {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/me', [ProfileController::class, 'show'])->name('me');
        Route::post('/me', [ProfileController::class, 'update'])->name('me.update');
        Route::put('/me/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:6,1')->name('me.password');
        Route::get('/home', HomeController::class)->name('home');
        Route::get('/realtime', RealtimeController::class)->name('realtime');
        Route::post('/broadcasting/auth', BroadcastAuthController::class)->name('broadcasting.auth');

        // Catalog and public handyman directory (both roles).
        Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
        Route::get('/catalog/services/{service}', [CatalogController::class, 'show'])->name('catalog.services.show');
        Route::get('/handymen', [HandymanDirectoryController::class, 'index'])->name('handymen.index');
        Route::get('/handymen/{handyman}', [HandymanDirectoryController::class, 'show'])->name('handymen.show');

        // Private photos, authorized by the same policies as on the website.
        Route::get('/photos/requests/{photo}', [PhotoController::class, 'requestPhoto'])->name('photos.request');
        Route::get('/photos/offers/{photo}', [PhotoController::class, 'offerPhoto'])->name('photos.offer');

        Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::get('/conversations/unread-count', [ConversationController::class, 'unreadCount'])->name('conversations.unread');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->whereNumber('conversation')->name('conversations.show');
        Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index'])->whereNumber('conversation')->name('messages.index');
        Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->whereNumber('conversation')->middleware('throttle:mobile-messages')->name('messages.store');
        Route::post('/conversations/{conversation}/read', [MessageController::class, 'markRead'])->whereNumber('conversation')->name('messages.read');

        Route::prefix('homeowner')->name('homeowner.')->middleware('role:homeowner')->group(function (): void {
            Route::get('/requests', [RequestController::class, 'index'])->name('requests.index');
            Route::post('/requests', [RequestController::class, 'store'])->name('requests.store');
            Route::get('/requests/{serviceRequest}', [RequestController::class, 'show'])->whereNumber('serviceRequest')->name('requests.show');
            Route::post('/requests/{serviceRequest}', [RequestController::class, 'update'])->name('requests.update');
            Route::post('/requests/{serviceRequest}/cancel', [RequestController::class, 'cancel'])->name('requests.cancel');
            Route::post('/requests/{serviceRequest}/confirm-completion', [RequestController::class, 'confirmCompletion'])->name('requests.confirm');
            Route::delete('/requests/{serviceRequest}/photos/{photo}', [RequestController::class, 'destroyPhoto'])->name('requests.photos.destroy');
            Route::post('/requests/{serviceRequest}/review', [ReviewController::class, 'store'])->name('requests.review.store');
            Route::put('/requests/{serviceRequest}/review', [ReviewController::class, 'update'])->name('requests.review.update');
            Route::post('/offers/{offer}/accept', [OfferDecisionController::class, 'accept'])->name('offers.accept');
            Route::post('/offers/{offer}/decline', [OfferDecisionController::class, 'decline'])->name('offers.decline');
            Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
            Route::post('/favorites/{handyman}', [FavoriteController::class, 'store'])->name('favorites.store');
            Route::delete('/favorites/{handyman}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
        });

        Route::prefix('handyman')->name('handyman.')->middleware('role:handyman')->group(function (): void {
            Route::get('/skills', [ProfileController::class, 'skills'])->name('skills.index');
            Route::put('/skills', [ProfileController::class, 'updateSkills'])->name('skills.update');
            Route::get('/offers', [HandymanOfferController::class, 'index'])->name('offers.index');
            Route::post('/offers/{offer}', [HandymanOfferController::class, 'update'])->name('offers.update');
            Route::post('/offers/{offer}/withdraw', [HandymanOfferController::class, 'withdraw'])->name('offers.withdraw');
            Route::post('/offers/{offer}/complete', [HandymanOfferController::class, 'markCompleted'])->name('offers.complete');
            Route::get('/requests', [WorkController::class, 'index'])->name('requests.index');
            Route::get('/requests/{serviceRequest}', [WorkController::class, 'show'])->name('requests.show');
            Route::post('/requests/{serviceRequest}/offers', [HandymanOfferController::class, 'store'])->name('offers.store');
        });
    });
});
