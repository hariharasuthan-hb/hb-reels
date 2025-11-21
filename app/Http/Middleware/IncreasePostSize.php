<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IncreasePostSize
{
    /**
     * Handle an incoming request.
     * 
     * Temporarily increase PHP post_max_size and upload_max_filesize
     * for routes that handle large file uploads.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Increase limits for this request
        // NOTE: post_max_size and upload_max_filesize cannot be changed with ini_set()
        // They must be set in php.ini or .htaccess
        ini_set('max_execution_time', '300');
        ini_set('max_input_time', '300');
        ini_set('memory_limit', '256M');
        
        return $next($request);
    }
}

