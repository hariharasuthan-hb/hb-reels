@extends('frontend.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Subscriptions</h1>
            <p class="mt-2 text-gray-600">Manage your subscription plans</p>
        </div>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        {{-- Error Message --}}
        @if(session('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if($activeSubscription)
            {{-- Active Subscription Card --}}
            <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Current Subscription</h2>
                        <p class="text-gray-600">{{ $activeSubscription->subscriptionPlan->plan_name }}</p>
                    </div>
                    <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium">
                        {{ ucfirst($activeSubscription->status) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Plan</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $activeSubscription->subscriptionPlan->plan_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Price</p>
                        <p class="text-lg font-semibold text-gray-900">₹{{ number_format($activeSubscription->subscriptionPlan->price, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Payment Gateway</p>
                        <p class="text-lg font-semibold text-gray-900">{{ ucfirst($activeSubscription->gateway ?? 'N/A') }}</p>
                    </div>
                </div>

                @if($activeSubscription->isTrialing())
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-sm text-blue-800">
                                <strong>Trial Period Active</strong> - Your trial ends on {{ $activeSubscription->trial_end_at->format('F d, Y') }}.
                                After that, you'll be charged ₹{{ number_format($activeSubscription->subscriptionPlan->price, 2) }}.
                            </p>
                        </div>
                    </div>
                @endif

                @if($activeSubscription->next_billing_at)
                    <div class="mb-6">
                        <p class="text-sm text-gray-500 mb-1">Next Billing Date</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $activeSubscription->next_billing_at->format('F d, Y') }}</p>
                    </div>
                @endif

                @if(!$activeSubscription->isCanceled())
                    <form action="{{ route('member.subscription.cancel', $activeSubscription->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this subscription? It will remain active until the end of the current billing period.');">
                        @csrf
                        <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">
                            Cancel Subscription
                        </button>
                    </form>
                @endif
            </div>
        @else
            {{-- No Active Subscription --}}
            <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No Active Subscription</h3>
                <p class="text-gray-600 mb-6">You don't have an active subscription plan.</p>
                <a href="{{ route('member.dashboard') }}" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    Browse Plans
                </a>
            </div>
        @endif

        {{-- Subscription History --}}
        @if($subscriptions->count() > 0)
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">Subscription History</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gateway</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Started</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Billing</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($subscriptions as $subscription)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">{{ $subscription->subscriptionPlan->plan_name }}</div>
                                        <div class="text-sm text-gray-500">₹{{ number_format($subscription->subscriptionPlan->price, 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $subscription->status === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                                {{ $subscription->status === 'trialing' ? 'bg-blue-100 text-blue-800' : '' }}
                                                {{ $subscription->status === 'canceled' ? 'bg-gray-100 text-gray-800' : '' }}
                                                {{ $subscription->status === 'past_due' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $subscription->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                                {{ ucfirst($subscription->status) }}
                                            </span>
                                            @if($subscription->status === 'pending' && $subscription->gateway === 'stripe')
                                                <form action="{{ route('member.subscription.refresh', $subscription->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 underline" title="Refresh status from payment gateway">
                                                        Refresh
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ ucfirst($subscription->gateway ?? 'N/A') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $subscription->started_at ? $subscription->started_at->format('M d, Y') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $subscription->next_billing_at ? $subscription->next_billing_at->format('M d, Y') : 'N/A' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

