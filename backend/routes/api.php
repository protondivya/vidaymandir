<?php

use App\Http\Controllers\Api\Auth\EmailVerificationController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\MeController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Author\AuthorController;
use App\Http\Controllers\Api\Book\BookController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\LicenseType\LicenseTypeController;
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

    Route::get('books', [BookController::class, 'index']);
    Route::get('books/{book}', [BookController::class, 'show']);

    Route::get('admin/books', [BookController::class, 'adminIndex'])
        ->middleware(['auth:sanctum', 'role:librarian']);

    Route::post('books', [BookController::class, 'store'])
        ->middleware(['auth:sanctum', 'role:librarian']);

    Route::put('books/{book}', [BookController::class, 'update'])
        ->middleware(['auth:sanctum', 'role:librarian']);

    Route::delete('books/{book}', [BookController::class, 'destroy'])
        ->middleware(['auth:sanctum', 'role:librarian']);

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}/books', [CategoryController::class, 'books']);

    Route::post('categories', [CategoryController::class, 'store'])
        ->middleware(['auth:sanctum', 'role:librarian']);

    Route::put('categories/{category}', [CategoryController::class, 'update'])
        ->middleware(['auth:sanctum', 'role:librarian']);

    Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
        ->middleware(['auth:sanctum', 'role:admin']);

    Route::get('authors', [AuthorController::class, 'index']);
    Route::post('authors', [AuthorController::class, 'store'])
        ->middleware(['auth:sanctum', 'role:librarian']);

    Route::put('authors/{author}', [AuthorController::class, 'update'])
        ->middleware(['auth:sanctum', 'role:librarian']);

    Route::delete('authors/{author}', [AuthorController::class, 'destroy'])
        ->middleware(['auth:sanctum', 'role:librarian']);

    Route::get('license-types', [LicenseTypeController::class, 'index']);
});
