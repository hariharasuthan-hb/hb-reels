<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DataTables\BannerDataTable;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Http\Requests\Admin\UpdateBannerRequest;
use App\Models\Banner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Controller for managing banners in the admin panel.
 * 
 * Handles CRUD operations for banner images displayed on the frontend.
 * Banners can be activated/deactivated and ordered for display purposes.
 */
class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(BannerDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new Banner))->toJson();
        }
        
        return view('admin.banners.index', [
            'dataTable' => $dataTable
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.banners.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBannerRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('banners', 'public');
            $validated['image'] = $imagePath;
        }

        // Set default values
        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $validated['order'] ?? Banner::max('order') + 1;

        Banner::create($validated);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Banner $banner): View
    {
        return view('admin.banners.edit', compact('banner'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBannerRequest $request, Banner $banner): RedirectResponse
    {
        $validated = $request->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($banner->image && Storage::disk('public')->exists($banner->image)) {
                Storage::disk('public')->delete($banner->image);
            }
            
            $imagePath = $request->file('image')->store('banners', 'public');
            $validated['image'] = $imagePath;
        } else {
            unset($validated['image']);
        }

        // Set is_active flag
        $validated['is_active'] = $request->has('is_active');

        $banner->update($validated);

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Banner $banner): RedirectResponse
    {
        // Delete image if exists
        if ($banner->image && Storage::disk('public')->exists($banner->image)) {
            Storage::disk('public')->delete($banner->image);
        }

        $banner->delete();

        return redirect()->route('admin.banners.index')
            ->with('success', 'Banner deleted successfully.');
    }
}
