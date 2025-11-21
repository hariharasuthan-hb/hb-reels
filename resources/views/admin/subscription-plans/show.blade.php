@extends('admin.layouts.app')

@section('page-title', 'View Subscription Plan')

@php use Illuminate\Support\Facades\Storage; @endphp

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-lg font-semibold">{{ $subscriptionPlan->plan_name }}</h1>
        <div class="flex space-x-3">
            <a href="{{ route('admin.subscription-plans.edit', $subscriptionPlan->id) }}" class="btn btn-primary">
                Edit Plan
            </a>
            <a href="{{ route('admin.subscription-plans.index') }}" class="btn btn-secondary">
                Back to Plans
            </a>
        </div>
    </div>

    @if($subscriptionPlan->image)
    <div class="mb-6">
        <div class="admin-card">
            <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <h2 class="text-base font-semibold text-gray-900">Plan Image</h2>
            </div>
            <div class="flex justify-center">
                <img src="{{ Storage::disk('public')->url($subscriptionPlan->image) }}" 
                     alt="{{ $subscriptionPlan->plan_name }}" 
                     class="max-w-full h-auto rounded-lg border border-gray-300 shadow-md">
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Plan Information Card --}}
        <div class="admin-card">
            <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h2 class="text-base font-semibold text-gray-900">Plan Information</h2>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Plan Name</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $subscriptionPlan->plan_name }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Price</p>
                        <p class="text-sm text-gray-900 font-medium">${{ number_format($subscriptionPlan->price, 2) }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Duration Type</p>
                        <p class="text-sm text-gray-900 font-medium">{{ ucfirst($subscriptionPlan->duration_type) }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Duration</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $subscriptionPlan->duration }} {{ ucfirst($subscriptionPlan->duration_type) }}{{ $subscriptionPlan->duration > 1 ? 's' : '' }}</p>
                    </div>
                </div>
                
                <div class="flex items-start col-span-2">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Status</p>
                        <p class="text-sm text-gray-900 font-medium">
                            @if($subscriptionPlan->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">Inactive</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Additional Information Card --}}
        <div class="admin-card">
            <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-base font-semibold text-gray-900">Additional Information</h2>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="flex items-start col-span-2">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Description</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $subscriptionPlan->description ?? '-' }}</p>
                    </div>
                </div>
                
                @if($subscriptionPlan->features && count($subscriptionPlan->features) > 0)
                <div class="flex items-start col-span-2">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Features</p>
                        <ul class="mt-2 space-y-1">
                            @foreach($subscriptionPlan->features as $feature)
                                <li class="text-sm text-gray-900 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Created At</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $subscriptionPlan->created_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Updated At</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $subscriptionPlan->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

