<?php

use App\Http\Controllers\Admin\ActivityEventController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OfferController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ServiceRequestController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\Handyman\DashboardController as HandymanDashboardController;
use App\Http\Controllers\HandymanDirectoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Homeowner\DashboardController as HomeownerDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/services', [CatalogController::class, 'index'])->name('services.index');
Route::get('/services/{service}', [CatalogController::class, 'show'])->name('services.show');
Route::get('/handymen', [HandymanDirectoryController::class, 'index'])->name('handymen.index');
Route::get('/handymen/{handyman}', [HandymanDirectoryController::class, 'show'])->name('handymen.show');
Route::view('/about', 'pages.about')->name('about');
Route::view('/contact', 'pages.contact')->name('contact');

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
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])->middleware('throttle:6,1')->name('verification.send');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'active', 'verified'])->group(function (): void {
    Route::get('/homeowner/dashboard', HomeownerDashboardController::class)->middleware('role:homeowner')->name('homeowner.dashboard');
    Route::get('/handyman/dashboard', HandymanDashboardController::class)->middleware('role:handyman')->name('handyman.dashboard');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function (): void {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/skills', [SkillController::class, 'index'])->name('skills.index');
        Route::get('/requests', [ServiceRequestController::class, 'index'])->name('requests.index');
        Route::get('/offers', [OfferController::class, 'index'])->name('offers.index');
        Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::get('/activity', [ActivityEventController::class, 'index'])->name('activity.index');
    });
});
