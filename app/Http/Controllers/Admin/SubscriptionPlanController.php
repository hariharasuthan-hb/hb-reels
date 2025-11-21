<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DataTables\SubscriptionPlanDataTable;
use App\Http\Requests\Admin\StoreSubscriptionPlanRequest;
use App\Http\Requests\Admin\UpdateSubscriptionPlanRequest;
use App\Models\SubscriptionPlan;
use App\Repositories\Interfaces\SubscriptionPlanRepositoryInterface;
use App\Services\EntityIntegrityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Controller for managing subscription plans in the admin panel.
 * 
 * Handles CRUD operations for subscription plans including creation, updating,
 * deletion, and viewing. Subscription plans define the membership packages
 * available for purchase by gym members.
 */
class SubscriptionPlanController extends Controller
{
    public function __construct(
        private readonly SubscriptionPlanRepositoryInterface $subscriptionPlanRepository,
        private readonly EntityIntegrityService $entityIntegrityService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(SubscriptionPlanDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new SubscriptionPlan))->toJson();
        }
        
        return view('admin.subscription-plans.index', [
            'dataTable' => $dataTable
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('admin.subscription-plans.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubscriptionPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('subscription-plans', 'public');
            $validated['image'] = $imagePath;
        }
        
        // Convert features array to JSON if provided
        if (isset($validated['features']) && is_array($validated['features'])) {
            $validated['features'] = array_filter($validated['features']); // Remove empty values
        }

        $this->subscriptionPlanRepository->create($validated);

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', 'Subscription plan created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SubscriptionPlan $subscriptionPlan): View
    {
        return view('admin.subscription-plans.show', compact('subscriptionPlan'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubscriptionPlan $subscriptionPlan): View
    {
        return view('admin.subscription-plans.edit', compact('subscriptionPlan'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        $validated = $request->validated();
        
        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($subscriptionPlan->image && Storage::disk('public')->exists($subscriptionPlan->image)) {
                Storage::disk('public')->delete($subscriptionPlan->image);
            }
            
            $imagePath = $request->file('image')->store('subscription-plans', 'public');
            $validated['image'] = $imagePath;
        } else {
            unset($validated['image']);
        }
        
        // Convert features array to JSON if provided
        if (isset($validated['features']) && is_array($validated['features'])) {
            $validated['features'] = array_filter($validated['features']); // Remove empty values
        }

        $this->subscriptionPlanRepository->update($subscriptionPlan->id, $validated);

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', 'Subscription plan updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubscriptionPlan $subscriptionPlan): RedirectResponse
    {
        if ($reason = $this->entityIntegrityService->firstSubscriptionPlanDeletionBlocker($subscriptionPlan)) {
            return redirect()->route('admin.subscription-plans.index')
                ->with('error', $reason);
        }

        // Delete image if exists
        if ($subscriptionPlan->image && Storage::disk('public')->exists($subscriptionPlan->image)) {
            Storage::disk('public')->delete($subscriptionPlan->image);
        }

        $this->subscriptionPlanRepository->delete($subscriptionPlan->id);

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', 'Subscription plan deleted successfully.');
    }
}

