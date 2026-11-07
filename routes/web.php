<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

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
    Route::get('/homeowner/dashboard', [DashboardController::class, 'show'])->defaults('role', 'homeowner')->middleware('role:homeowner')->name('homeowner.dashboard');
    Route::get('/handyman/dashboard', [DashboardController::class, 'show'])->defaults('role', 'handyman')->middleware('role:handyman')->name('handyman.dashboard');
    Route::get('/admin/dashboard', [DashboardController::class, 'show'])->defaults('role', 'admin')->middleware('role:admin')->name('admin.dashboard');
});
