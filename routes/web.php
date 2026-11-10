<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\ActivityEventController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OfferController as AdminOfferController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ServiceRequestController as AdminServiceRequestController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\Handyman\DashboardController as HandymanDashboardController;
use App\Http\Controllers\Handyman\OfferController;
use App\Http\Controllers\Handyman\RequestBrowserController;
use App\Http\Controllers\HandymanDirectoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Homeowner\DashboardController as HomeownerDashboardController;
use App\Http\Controllers\Homeowner\FavoriteController;
use App\Http\Controllers\Homeowner\OfferDecisionController;
use App\Http\Controllers\Homeowner\ReviewController;
use App\Http\Controllers\Homeowner\ServiceRequestController;
use App\Http\Controllers\PhotoController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/services', [CatalogController::class, 'index'])->name('services.index');
Route::get('/services/{service}', [CatalogController::class, 'show'])->name('services.show');
Route::get('/handymen', [HandymanDirectoryController::class, 'index'])->name('handymen.index');
Route::get('/handymen/{handyman}', [HandymanDirectoryController::class, 'show'])->name('handymen.show');
Route::get('/handymen/{handyman}/photo', [PhotoController::class, 'handymanPhoto'])->name('handymen.photo');
Route::view('/about', 'pages.about')->name('about');
Route::get('/contact', [ContactMessageController::class, 'create'])->name('contact');
Route::post('/contact', [ContactMessageController::class, 'store'])->middleware('throttle:contact')->name('contact.store');
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

Route::middleware('guest')->group(function (): void {
    Route::get('/register/{role}', [RegisteredUserController::class, 'create'])->whereIn('role', ['homeowner', 'handyman'])->name('register');
    Route::post('/register/{role}', [RegisteredUserController::class, 'store'])->whereIn('role', ['homeowner', 'handyman'])->middleware('throttle:6,1')->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->defaults('role', 'homeowner')->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->defaults('role', 'homeowner')->middleware('throttle:login')->name('login.store');
    Route::get('/handyman/login', [AuthenticatedSessionController::class, 'create'])->defaults('role', 'handyman')->name('handyman.login');
    Route::post('/handyman/login', [AuthenticatedSessionController::class, 'store'])->defaults('role', 'handyman')->middleware('throttle:login')->name('handyman.login.store');
    Route::get('/admin/login', [AuthenticatedSessionController::class, 'create'])->defaults('role', 'admin')->name('admin.login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->defaults('role', 'admin')->middleware('throttle:login')->name('admin.login.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.store');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'active', 'verified'])->group(function (): void {
    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])->middleware('throttle:6,1')->name('account.password.update');
    Route::put('/account/skills', [AccountController::class, 'updateSkills'])->middleware('role:handyman')->name('account.skills.update');
    Route::delete('/account', [AccountController::class, 'destroy'])->middleware('throttle:6,1')->name('account.destroy');

    Route::get('/photos/requests/{photo}', [PhotoController::class, 'requestPhoto'])->name('photos.request');
    Route::get('/photos/offers/{photo}', [PhotoController::class, 'offerPhoto'])->name('photos.offer');

    Route::prefix('homeowner')->name('homeowner.')->middleware('role:homeowner')->group(function (): void {
        Route::get('/dashboard', HomeownerDashboardController::class)->name('dashboard');
        Route::get('/requests', [ServiceRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/create', [ServiceRequestController::class, 'create'])->name('requests.create');
        Route::post('/requests', [ServiceRequestController::class, 'store'])->name('requests.store');
        Route::get('/requests/{serviceRequest}', [ServiceRequestController::class, 'show'])->name('requests.show');
        Route::get('/requests/{serviceRequest}/edit', [ServiceRequestController::class, 'edit'])->name('requests.edit');
        Route::put('/requests/{serviceRequest}', [ServiceRequestController::class, 'update'])->name('requests.update');
        Route::post('/requests/{serviceRequest}/cancel', [ServiceRequestController::class, 'cancel'])->name('requests.cancel');
        Route::post('/requests/{serviceRequest}/confirm-completion', [ServiceRequestController::class, 'confirmCompletion'])->name('requests.confirm');
        Route::delete('/requests/{serviceRequest}/photos/{photo}', [ServiceRequestController::class, 'destroyPhoto'])->name('requests.photos.destroy');
        Route::post('/requests/{serviceRequest}/review', [ReviewController::class, 'store'])->name('requests.review.store');
        Route::put('/requests/{serviceRequest}/review', [ReviewController::class, 'update'])->name('requests.review.update');
        Route::post('/offers/{offer}/accept', [OfferDecisionController::class, 'accept'])->name('offers.accept');
        Route::post('/offers/{offer}/decline', [OfferDecisionController::class, 'decline'])->name('offers.decline');
        Route::post('/favorites/{handyman}', [FavoriteController::class, 'store'])->name('favorites.store');
        Route::delete('/favorites/{handyman}', [FavoriteController::class, 'destroy'])->name('favorites.destroy');
    });

    Route::prefix('handyman')->name('handyman.')->middleware('role:handyman')->group(function (): void {
        Route::get('/dashboard', HandymanDashboardController::class)->name('dashboard');
        Route::get('/requests', [RequestBrowserController::class, 'index'])->name('requests.index');
        Route::get('/requests/{serviceRequest}', [RequestBrowserController::class, 'show'])->name('requests.show');
        Route::post('/requests/{serviceRequest}/offers', [OfferController::class, 'store'])->name('offers.store');
        Route::get('/offers/{offer}/edit', [OfferController::class, 'edit'])->name('offers.edit');
        Route::put('/offers/{offer}', [OfferController::class, 'update'])->name('offers.update');
        Route::post('/offers/{offer}/withdraw', [OfferController::class, 'withdraw'])->name('offers.withdraw');
        Route::post('/offers/{offer}/complete', [OfferController::class, 'markCompleted'])->name('offers.complete');
        Route::delete('/offers/{offer}/photos/{photo}', [OfferController::class, 'destroyPhoto'])->name('offers.photos.destroy');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->withTrashed()->name('users.show');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/block', [UserController::class, 'block'])->name('users.block');
        Route::post('/users/{user}/unblock', [UserController::class, 'unblock'])->name('users.unblock');
        Route::post('/users/{user}/anonymize', [UserController::class, 'anonymize'])->name('users.anonymize');

        foreach (['categories' => [CategoryController::class, 'category'], 'services' => [ServiceController::class, 'service'], 'skills' => [SkillController::class, 'skill']] as $uri => [$controller, $parameter]) {
            Route::get("/{$uri}/{{$parameter}}/delete", [$controller, 'confirmDelete'])->name("{$uri}.delete");
            Route::patch("/{$uri}/{{$parameter}}/toggle", [$controller, 'toggle'])->name("{$uri}.toggle");
            Route::resource($uri, $controller)->except('show');
        }

        Route::get('/requests', [AdminServiceRequestController::class, 'index'])->name('requests.index');
        Route::get('/offers', [AdminOfferController::class, 'index'])->name('offers.index');
        Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::get('/activity', [ActivityEventController::class, 'index'])->name('activity.index');

        Route::get('/contact-messages', [AdminContactMessageController::class, 'index'])->name('contact-messages.index');
        Route::get('/contact-messages/{contactMessage}', [AdminContactMessageController::class, 'show'])->name('contact-messages.show');
        Route::patch('/contact-messages/{contactMessage}/status', [AdminContactMessageController::class, 'updateStatus'])->name('contact-messages.status');
    });
});
