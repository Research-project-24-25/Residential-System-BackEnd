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
    public function handle(Request $request, Closure $next, string $role = 'admin'): Response
    {
        if (!$request->user() || !$request->user() instanceof \App\Models\Admin) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($role === 'super_admin' && !$request->user()->isSuperAdmin()) {
            return response()->json(['message' => 'This action requires super admin privileges.'], 403);
        }

        return $next($request);
    }
}
