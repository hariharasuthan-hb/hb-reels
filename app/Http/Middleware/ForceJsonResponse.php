<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ForceJsonResponse
{
    /**
     * Ensure API routes always expect JSON responses.
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->is('api/*')) {
            $request->headers->set('Accept', 'application/json');
        }

        $response = $next($request);

        if ($request->expectsJson() && !$response instanceof JsonResponse) {
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }
}

