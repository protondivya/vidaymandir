<?php

use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register', RegisterController::class)
            ->middleware('throttle:10,1');

        Route::post('login', LoginController::class)
            ->middleware('throttle:10,1');

        Route::post('logout', LogoutController::class)
            ->middleware('auth:sanctum');

        Route::post('email/verification-notification', [EmailVerificationController::class, 'sendNotification'])
            ->middleware(['auth:sanctum', 'throttle:5,1']);

        Route::get('email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->name('verification.verify')
            ->middleware('signed');

        Route::post('password/reset', [PasswordResetController::class, 'sendResetLink'])
            ->middleware('throttle:5,1');

        Route::post('password/reset/confirm', [PasswordResetController::class, 'reset'])
            ->middleware('throttle:5,1');

        Route::get('me', MeController::class)
            ->middleware('auth:sanctum');
    });
});
