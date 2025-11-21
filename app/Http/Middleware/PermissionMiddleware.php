<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Parse permissions - support both pipe-separated and multiple parameters
        $allPermissions = [];
        foreach ($permissions as $permission) {
            // If permission contains pipe, split it
            if (strpos($permission, '|') !== false) {
                $allPermissions = array_merge($allPermissions, explode('|', $permission));
            } else {
                $allPermissions[] = $permission;
            }
        }
        
        // Remove duplicates and trim whitespace
        $allPermissions = array_unique(array_map('trim', $allPermissions));
        
        // Check if user has any of the required permissions
        foreach ($allPermissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized access. You do not have the required permission.');
    }
}

