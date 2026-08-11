<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if ($user === null || ! Hash::check($request->validated('password'), $user->password)) {
            return ApiResponse::error('Invalid credentials.', 401);
        }

        if (! $user->is_active) {
            return ApiResponse::error('This account has been deactivated.', 403);
        }

        $token = $user->createAccessToken();

        $user->forceFill(['last_login_at' => now()])->save();

        return ApiResponse::success([
            'access_token' => $token['token'],
            'token_type' => 'Bearer',
            'expires_at' => $token['expires_at']?->toIso8601String(),
            'user' => new UserResource($user),
        ]);
    }
}
