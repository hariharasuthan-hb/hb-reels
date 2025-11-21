<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSiteSettingsRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Controller for managing site-wide settings in the admin panel.
 * 
 * Handles viewing and updating general site settings such as site name,
 * contact information, and logo. Site settings affect the entire application
 * and are displayed across the frontend. Requires appropriate permissions.
 */
class SiteSettingsController extends Controller
{
    /**
     * Display the site settings editor.
     */
    public function index(): View
    {
        $settings = SiteSetting::getSettings();
        
        return view('admin.site-settings.edit', compact('settings'));
    }

    /**
     * Update the site settings.
     */
    public function update(UpdateSiteSettingsRequest $request, SiteSetting $siteSetting): RedirectResponse
    {
        $validated = $request->validated();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($siteSetting->logo && Storage::disk('public')->exists($siteSetting->logo)) {
                Storage::disk('public')->delete($siteSetting->logo);
            }
            
            $logoPath = $request->file('logo')->store('site-settings', 'public');
            $validated['logo'] = $logoPath;
        } else {
            unset($validated['logo']);
        }

        $siteSetting->update($validated);

        return redirect()->route('admin.site-settings.index')
            ->with('success', 'Site settings updated successfully.');
    }
}
