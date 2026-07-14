<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Allow the request through only when the authenticated user is an admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->user_type !== 'admin') {
            return response()->json([
                'message' => 'Accès réservé aux administrateurs.',
            ], 403);
        }

        return $next($request);
    }
}
