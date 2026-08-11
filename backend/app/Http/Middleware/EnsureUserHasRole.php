<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Restrict a route to a given role ('reader', 'librarian' or 'admin').
     *
     * Usage: ->middleware('role:librarian')
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasRole($role)) {
            return ApiResponse::error('You do not have permission to perform this action.', 403);
        }

        return $next($request);
    }
}
