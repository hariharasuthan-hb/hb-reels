<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     * 
     * Check if the user has an active subscription or trial period.
     * Admins can always access.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if user is authenticated
        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'Please login to access the Event Reel Generator.');
        }

        // Admins can always access (check for 'admin' role using Spatie)
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // Check if user account is active
        if (!$user->isActive()) {
            return redirect()->back()
                ->withErrors(['access' => 'Your account is not active. Please contact support.']);
        }

        // Get the active subscription
        $subscription = $user->activeSubscription;

        // Check if user has no subscription
        if (!$subscription) {
            return redirect()->back()
                ->withErrors(['access' => 'You need an active subscription to access the Event Reel Generator. Please subscribe to continue.']);
        }

        // Check if subscription has access (active, trialing, or canceled but still within period)
        if (!$subscription->hasAccess()) {
            $message = 'Your subscription has expired. Please renew your subscription to continue.';
            
            // Provide more specific message based on status
            if ($subscription->status === 'canceled') {
                $message = 'Your subscription has been canceled and the access period has ended. Please subscribe again to continue.';
            } elseif ($subscription->status === 'past_due') {
                $message = 'Your subscription payment is past due. Please update your payment method to continue.';
            } elseif ($subscription->status === 'trialing' && $subscription->trial_end_at && $subscription->trial_end_at->isPast()) {
                $message = 'Your trial period has ended. Please subscribe to a paid plan to continue.';
            }

            return redirect()->back()
                ->withErrors(['access' => $message]);
        }

        // Check trial period specifically
        if ($subscription->isTrialing()) {
            // Trial is valid, allow access
            // You can add a flash message to remind them about trial
            if ($subscription->trial_end_at) {
                $daysLeft = now()->diffInDays($subscription->trial_end_at, false);
                if ($daysLeft <= 3 && $daysLeft > 0) {
                    session()->flash('trial_warning', "Your trial ends in {$daysLeft} day(s). Subscribe now to continue access!");
                }
            }
        }

        // Check subscription dates
        if ($subscription->started_at && $subscription->started_at->isFuture()) {
            return redirect()->back()
                ->withErrors(['access' => 'Your subscription has not started yet. Start date: ' . $subscription->started_at->format('M d, Y')]);
        }

        if ($subscription->next_billing_at && $subscription->next_billing_at->isPast() && $subscription->status !== 'trialing') {
            return redirect()->back()
                ->withErrors(['access' => 'Your subscription billing period has ended. Please renew to continue.']);
        }

        // All checks passed - user has valid subscription access
        return $next($request);
    }
}

