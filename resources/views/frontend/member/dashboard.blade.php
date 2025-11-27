@extends('frontend.layouts.app')

@php use Illuminate\Support\Facades\Storage; @endphp

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page Header --}}
        <div class="mb-8">
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Member Dashboard</h1>
                    <p class="mt-2 text-gray-600">Welcome back! Here's your overview.</p>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-3">
                    @if($activeSubscription)
                        <a href="{{ route('eventreel.index') }}" class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors font-semibold flex items-center shadow-md">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            Generate Video
                        </a>
                    @else
                        <div class="px-6 py-3 bg-gray-400 text-white rounded-lg font-semibold flex items-center shadow-md cursor-not-allowed opacity-50">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            <span title="Subscribe to unlock video generation">Generate Video</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        </div>

        {{-- Dashboard Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            {{-- Active Subscription Card --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 {{ $activeSubscription ? 'bg-blue-100' : 'bg-gray-100' }} rounded-lg p-3">
                        <svg class="w-6 h-6 {{ $activeSubscription ? 'text-blue-600' : 'text-gray-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Subscription</p>
                        @if($activeSubscription && $activeSubscription->subscriptionPlan)
                            <p class="text-2xl font-semibold text-gray-900">{{ $activeSubscription->subscriptionPlan->plan_name }}</p>
                            @if($activeSubscription->next_billing_at)
                                <p class="text-xs text-gray-500">Next billing: {{ $activeSubscription->next_billing_at->format('M d, Y') }}</p>
                            @elseif($activeSubscription->trial_end_at)
                                <p class="text-xs text-gray-500">Trial ends: {{ $activeSubscription->trial_end_at->format('M d, Y') }}</p>
                            @endif
                        @else
                            <p class="text-2xl font-semibold text-gray-900">None</p>
                            <p class="text-xs text-red-500">No active plan</p>
                        @endif
                    </div>
                </div>
            </div>

{{-- Activities Card --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-orange-100 rounded-lg p-3">
                        <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Activities</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $totalActivities ?? 0 }}</p>
                        <p class="text-xs text-gray-500 mt-1">Total check-ins</p>
                    </div>
                </div>
            </div>
        </div>

{{-- Subscription Plans Section (Show if user has no active subscription) --}}
        @if(!$activeSubscription && $subscriptionPlans && $subscriptionPlans->count() > 0)
        <div id="subscription-plans" class="bg-white rounded-lg shadow mb-8 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Choose Your Plan</h2>
                    <p class="mt-1 text-gray-600">Select a subscription plan to get started</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($subscriptionPlans as $plan)
                <div class="border-2 rounded-xl p-6 hover:shadow-lg transition-all duration-300 {{ $plan->price == $subscriptionPlans->min('price') ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                    @if($plan->price == $subscriptionPlans->min('price'))
                    <div class="inline-block bg-blue-600 text-white text-xs font-semibold px-3 py-1 rounded-full mb-4">
                        Most Popular
                    </div>
                    @endif
                    
                    @if($plan->image)
                    <div class="mb-4">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($plan->image) }}" 
                             alt="{{ $plan->plan_name }}" 
                             class="w-full h-32 object-cover rounded-lg">
                    </div>
                    @endif
                    
                    <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $plan->plan_name }}</h3>
                    
                    <div class="mb-4">
                        <span class="text-3xl font-bold text-gray-900">${{ $plan->formatted_price }}</span>
                        <span class="text-gray-600">/{{ $plan->formatted_duration }}</span>
                    </div>
                    
                    @if($plan->description)
                    <p class="text-gray-600 text-sm mb-4">{{ Str::limit($plan->description, 100) }}</p>
                    @endif
                    
                    @if($plan->features && count($plan->features) > 0)
                    <ul class="space-y-2 mb-6">
                        @foreach(array_slice($plan->features, 0, 5) as $feature)
                            @if(!empty($feature))
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-sm text-gray-700">{{ $feature }}</span>
                            </li>
                            @endif
                        @endforeach
                    </ul>
                    @endif
                    
                    <a href="{{ route('member.subscription.checkout', $plan->id) }}" class="block w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors text-center">
                        Subscribe Now
                    </a>
                </div>
                @endforeach
            </div>
        </div>
        @endif

{{-- Quick Actions --}}
        <div class="bg-white rounded-lg shadow mb-8 p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Quick Actions</h2>
            @php
                $subscriptionAnchor = route('member.dashboard') . '#subscription-plans';
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="{{ route('member.activities') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 text-blue-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span class="font-medium text-gray-900">View Activities</span>
                </a>
                <a href="{{ route('member.subscriptions') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-medium text-gray-900">My Subscriptions</span>
                </a>
                <a href="{{ route('member.profile') }}" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="font-medium text-gray-900">Edit Profile</span>
                </a>
            </div>
        </div>

        {{-- Recent Activities --}}
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Recent Activities</h2>
            <div class="space-y-4">
                @if($recentActivities && $recentActivities->count() > 0)
                    @foreach($recentActivities as $activity)
                        <div class="flex items-center p-4 border border-gray-200 rounded-lg">
                            <div class="flex-shrink-0 w-10 h-10 {{ $activity->activity_type === 'event_reel_generation' ? 'bg-purple-100' : 'bg-blue-100' }} rounded-full flex items-center justify-center">
                                @if($activity->activity_type === 'event_reel_generation')
                                    <svg class="w-5 h-5 {{ $activity->activity_type === 'event_reel_generation' ? 'text-purple-600' : 'text-blue-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                @else
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm font-medium text-gray-900">
                                    @if($activity->activity_type === 'event_reel_generation')
                                        Video Generated
                                    @else
                                        {{ ucfirst(str_replace('_', ' ', $activity->activity_type ?? 'Activity logged')) }}
                                    @endif
                                </p>
                                <p class="text-sm text-gray-500">{{ $activity->created_at->diffForHumans() }}</p>
                                @if($activity->activity_type === 'event_reel_generation' && $activity->workout_summary)
                                    <p class="text-xs text-gray-400 mt-1">{{ Str::limit($activity->workout_summary, 60) }}</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No activities yet</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            @if($hasActiveSubscription)
                                Start by generating a video or checking in to see your activities here.
                            @else
                                Subscribe to unlock activity tracking and video generation features.
                            @endif
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
async function checkIn() {
    const btn = document.getElementById('check-in-btn');
    if (!btn) return;
    
    // Disable button
    btn.disabled = true;
    btn.innerHTML = `
        <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Checking In...
    `;
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const response = await fetch('{{ route("member.check-in") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show success message with SweetAlert
            await SwalHelper.success(
                'Check-in Successful!',
                'You have been checked in for today.'
            );
            // Reload page to update UI
            window.location.reload();
        } else {
            await SwalHelper.warning(
                'Check-in Failed',
                data.message || 'Check-in failed. Please try again.'
            );
            // Re-enable button
            btn.disabled = false;
            btn.innerHTML = `
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Check In
            `;
        }
    } catch (error) {
        console.error('Check-in error:', error);
        await SwalHelper.error(
            'Error',
            'An error occurred. Please try again.'
        );
        // Re-enable button
        btn.disabled = false;
        btn.innerHTML = `
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Check In
        `;
    }
}

async function checkOut() {
    const btn = document.getElementById('check-out-btn');
    if (!btn) return;

    btn.disabled = true;
    const originalContent = btn.innerHTML;
    btn.innerHTML = `
        <svg class="animate-spin w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Checking Out...
    `;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const response = await fetch('{{ route("member.check-out") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (data.success) {
            await SwalHelper.success(
                'Checkout Complete',
                'You have been checked out. See you soon!'
            );
            window.location.reload();
        } else {
            await SwalHelper.warning(
                'Checkout Failed',
                data.message || 'Checkout failed. Please try again.'
            );
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
    } catch (error) {
        console.error('Checkout error:', error);
        await SwalHelper.error(
            'Error',
            'An error occurred. Please try again.'
        );
        btn.disabled = false;
        btn.innerHTML = originalContent;
    }
}
</script>
@endpush
@endsection

