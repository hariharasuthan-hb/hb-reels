@extends('frontend.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <svg class="w-20 h-20 text-green-500 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            
            <h1 class="text-3xl font-bold text-gray-900 mb-4">Subscription Successful!</h1>
            <p class="text-lg text-gray-600 mb-8">
                Thank you for subscribing. Your subscription has been activated successfully.
            </p>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <p class="text-sm text-blue-800">
                    <strong>What's next?</strong><br>
                    You can now access all the features included in your subscription plan. 
                    If you have a trial period, you'll be charged automatically after the trial ends.
                </p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('member.dashboard') }}" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    Go to Dashboard
                </a>
                <a href="{{ route('member.subscription.index') }}" class="inline-block px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                    View Subscription
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

