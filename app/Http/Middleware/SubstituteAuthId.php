<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubstituteAuthId
{
    public function handle(Request $request, Closure $next): Response
    {
        // If user is authenticated and this is a resident user
        if ($request->user() && $request->user()->getTable() === 'residents') {
            // Replace the residentId parameter with the authenticated user's ID
            $request->route()->setParameter('residentId', $request->user()->id);
        }

        return $next($request);
    }
}
