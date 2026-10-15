<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if authenticated and user role is admin
        if (! $user || ! $user->isAdmin()) {
            return response()->json([
                'message' => 'Unauthorized. Administrator access required.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
