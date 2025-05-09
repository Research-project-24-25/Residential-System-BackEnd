<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class EnsureUserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role = ""): Response
    {
        if (!$request->user() || !$this->checkUserType($request->user(), $role)) {
            return response()->json(['message' => $this->getErrorMessage($role)], 403);
        }

        return $next($request);
    }

    /**
     * Check if the user is of the required type
     */
    abstract protected function checkUserType($user, ?string $role): bool;

    /**
     * Get the error message for unauthorized access
     */
    abstract protected function getErrorMessage(?string $role): string;
}
