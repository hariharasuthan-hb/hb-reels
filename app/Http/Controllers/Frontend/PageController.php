<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\CmsPageRepositoryInterface;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class PageController extends Controller
{
    protected CmsPageRepositoryInterface $repository;

    public function __construct(CmsPageRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a CMS page by slug.
     */
    public function show(string $slug): View|RedirectResponse
    {
        $page = $this->repository->findBySlug($slug);

        if (!$page) {
            abort(404, 'Page not found');
        }

        return view('frontend.pages.show', compact('page'));
    }
}
