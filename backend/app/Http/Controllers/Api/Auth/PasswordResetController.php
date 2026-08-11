<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Responses\ApiResponse;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    /**
     * Send a password reset link. Always returns a success response to
     * prevent account enumeration.
     */
    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        Password::broker()->sendResetLink(
            $request->only('email'),
            function ($user, $token): void {
                $user->notify(new ResetPasswordNotification($token));
            },
        );

        return ApiResponse::success([
            'message' => 'If that email address exists, a password reset link has been sent.',
        ]);
    }

    /**
     * Reset the password using the token from the reset email.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => null,
                ])->save();

                $user->revokeAllTokens();
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return ApiResponse::success(['message' => 'Your password has been reset. Please sign in.']);
        }

        return ApiResponse::error(
            __('passwords.'.$status) ?: 'The password reset token is invalid or has expired.',
            422,
        );
    }
}
