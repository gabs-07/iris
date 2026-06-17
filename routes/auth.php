<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Breeze authentication routes
|--------------------------------------------------------------------------
|
| IRIS now uses Laravel Breeze-style authentication controllers and route
| organization, while preserving the Spanish URLs and the custom IRIS views.
|
*/

Route::middleware('guest')->group(function () {
    Route::get('registro', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('registro', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('recuperar', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('recuperar', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('email/verify', EmailVerificationPromptController::class)->name('verification.notice');

    Route::get('email/verify/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

Route::redirect('register', '/registro');
Route::redirect('forgot-password', '/recuperar');
