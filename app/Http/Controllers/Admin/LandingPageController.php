<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateLandingPageRequest;
use App\Models\LandingPageContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Controller for managing landing page content in the admin panel.
 * 
 * Handles viewing and updating the landing page content including welcome
 * sections, about sections, services, and images. The landing page is the
 * main frontend page displayed to visitors. Requires 'view landing page' and
 * 'edit landing page' permissions.
 */
class LandingPageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view landing page');
        $this->middleware('permission:edit landing page')->only('update');
    }

    /**
     * Display the landing page content editor.
     */
    public function index(): View
    {
        $content = LandingPageContent::first();
        
        if (!$content) {
            $content = LandingPageContent::create([
                'welcome_title' => 'Welcome to Our Gym',
                'about_title' => 'About Us',
                'services_title' => 'Our Services',
                'is_active' => true,
            ]);
        }
        
        return view('admin.landing-page.edit', compact('content'));
    }

    /**
     * Update the landing page content.
     */
    public function update(UpdateLandingPageRequest $request, LandingPageContent $landingPage): RedirectResponse
    {
        $validated = $request->validated();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($landingPage->logo && Storage::disk('public')->exists($landingPage->logo)) {
                Storage::disk('public')->delete($landingPage->logo);
            }
            
            $logoPath = $request->file('logo')->store('landing-page', 'public');
            $validated['logo'] = $logoPath;
        } else {
            unset($validated['logo']);
        }

        // Handle hero background image upload
        if ($request->hasFile('hero_background_image')) {
            // Delete old image if exists
            if ($landingPage->hero_background_image && Storage::disk('public')->exists($landingPage->hero_background_image)) {
                Storage::disk('public')->delete($landingPage->hero_background_image);
            }
            
            $bgPath = $request->file('hero_background_image')->store('landing-page', 'public');
            $validated['hero_background_image'] = $bgPath;
        } else {
            unset($validated['hero_background_image']);
        }

        $landingPage->update($validated);

        return redirect()->route('admin.landing-page.index')
            ->with('success', 'Landing page content updated successfully.');
    }
}
