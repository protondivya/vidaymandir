<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\VerifyEmailNotification;

class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request): \Illuminate\Http\JsonResponse
    {
        $user = User::create([
            'display_name' => $request->validated('display_name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => UserRole::Reader,
        ])->refresh();

        $user->notify(new VerifyEmailNotification());

        return ApiResponse::success([
            'user' => new UserResource($user),
            'message' => 'Registration successful. Please check your email to verify your account.',
        ], 201);
    }
}
