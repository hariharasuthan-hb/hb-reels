<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DataTables\SubscriptionDataTable;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * Controller for managing subscriptions in the admin panel.
 * 
 * Handles viewing, updating, and canceling user subscriptions. Subscriptions
 * represent active membership plans for gym members. Includes functionality
 * to cancel subscriptions through payment gateways.
 */
class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(SubscriptionDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new Subscription))->toJson();
        }
        
        return view('admin.subscriptions.index', [
            'dataTable' => $dataTable,
            'filters' => request()->only(['status', 'gateway', 'search']),
            'statusOptions' => \App\Models\Subscription::getStatusOptions(),
            'gatewayOptions' => \App\Models\Subscription::getGatewayOptions(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Subscription $subscription): View
    {
        $subscription->load(['user', 'subscriptionPlan']);

        return view('admin.subscriptions.show', compact('subscription'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Subscription $subscription): View
    {
        $subscription->load(['user', 'subscriptionPlan']);

        return view('admin.subscriptions.edit', compact('subscription'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:trialing,active,canceled,past_due,expired,pending'],
        ]);

        $subscription->update($validated);

        Log::info('Admin updated subscription status', [
            'subscription_id' => $subscription->id,
            'old_status' => $subscription->getOriginal('status'),
            'new_status' => $validated['status'],
            'admin_id' => auth()->id(),
        ]);

        return redirect()->route('admin.subscriptions.show', $subscription)
            ->with('success', 'Subscription updated successfully.');
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Subscription $subscription): RedirectResponse
    {
        if ($subscription->isCanceled()) {
            return redirect()->route('admin.subscriptions.show', $subscription)
                ->with('info', 'This subscription is already canceled.');
        }

        try {
            // Cancel via payment gateway if possible
            $paymentGatewayService = app(\App\Services\PaymentGateway\PaymentGatewayService::class);
            $canceled = $paymentGatewayService->cancelSubscription($subscription);

            if ($canceled) {
                $subscription->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                ]);

                Log::info('Admin canceled subscription', [
                    'subscription_id' => $subscription->id,
                    'admin_id' => auth()->id(),
                ]);

                return redirect()->route('admin.subscriptions.show', $subscription)
                    ->with('success', 'Subscription canceled successfully.');
            } else {
                // Still update status locally even if gateway cancel fails
                $subscription->update([
                    'status' => 'canceled',
                    'canceled_at' => now(),
                ]);

                return redirect()->route('admin.subscriptions.show', $subscription)
                    ->with('warning', 'Subscription marked as canceled locally, but gateway cancellation may have failed.');
            }
        } catch (\Exception $e) {
            Log::error('Admin subscription cancellation failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
                'admin_id' => auth()->id(),
            ]);

            return redirect()->route('admin.subscriptions.show', $subscription)
                ->with('error', 'Failed to cancel subscription: ' . $e->getMessage());
        }
    }
}

