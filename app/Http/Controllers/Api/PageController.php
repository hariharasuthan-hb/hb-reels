<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    /**
     * Get CMS page content by slug.
     * Used by frontend to fetch dynamic content.
     */
    public function show(string $slug): JsonResponse
    {
        // TODO: Fetch page from CMS
        // This will be used to fetch dynamic content for the frontend
        
        return response()->json([
            'slug' => $slug,
            'title' => 'Page Title',
            'content' => 'Page content...',
        ]);
    }
}

