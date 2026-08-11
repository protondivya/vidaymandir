<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Verify a user's email via a signed link.
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return ApiResponse::error('The verification link is invalid.', 403);
        }

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success(['message' => 'Your email address is already verified.']);
        }

        $user->markEmailAsVerified();

        return ApiResponse::success(['message' => 'Your email address has been verified.']);
    }

    /**
     * Re-send the verification notification to the authenticated user.
     */
    public function sendNotification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return ApiResponse::success(['message' => 'Your email address is already verified.']);
        }

        $user->notify(new VerifyEmailNotification());

        return ApiResponse::success(['message' => 'A new verification link has been sent to your email address.']);
    }
}
