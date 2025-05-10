<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class EnsureUserType
{
    public function handle(Request $request, Closure $next, string $role = ""): Response
    {
        if (!$request->user() || !$this->checkUserType($request->user(), $role)) {
            return response()->json(['message' => $this->getErrorMessage($role)], 403);
        }

        return $next($request);
    }

    abstract protected function checkUserType($user, ?string $role): bool;

    abstract protected function getErrorMessage(?string $role): string;
}
