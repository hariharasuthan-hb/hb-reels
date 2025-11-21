<x-guest-layout>
    <!-- Logo Section -->
    <div class="mb-8 text-center">
        @php
            $siteSettings = \App\Models\SiteSetting::getSettings();
            $landingPage = \App\Models\LandingPageContent::getActive();
            // Priority: Site Settings Logo > Landing Page Logo > Site Title
            $logo = $siteSettings->logo ?? ($landingPage->logo ?? null);
            $siteTitle = $siteSettings->site_title ?? config('app.name', 'Gym Management');
        @endphp
        <a href="{{ route('frontend.home') }}" class="inline-block group">
            @if($logo)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($logo) }}" 
                     alt="{{ $siteTitle }}" 
                     class="h-12 w-auto object-contain transition-transform duration-300 group-hover:scale-105 mx-auto">
            @else
                <div class="text-2xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-indigo-600 bg-clip-text text-transparent">
                    {{ $siteTitle }}
                </div>
            @endif
        </a>
    </div>

    <div class="mb-8 text-center">
        <h2 class="text-2xl font-semibold text-gray-900 mb-2">Verify Email</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            Please verify your email address by clicking the link we sent to your inbox
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-6 p-4 bg-green-50 border-2 border-green-200 rounded-xl">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-600 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="font-semibold text-sm text-green-800">
                    {{ __('A new verification link has been sent to the email address you provided during registration.') }}
                </p>
            </div>
        </div>
    @endif

    <div class="space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full py-3 text-sm font-semibold bg-gradient-to-r from-blue-600 via-blue-700 to-purple-600 hover:from-blue-700 hover:via-blue-800 hover:to-purple-700 transition-all duration-300 shadow-md hover:shadow-lg rounded-xl">
                <span class="flex items-center justify-center">
                    <span>{{ __('Resend Verification Email') }}</span>
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </span>
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-center text-sm font-semibold text-gray-600 hover:text-gray-900 py-3 transition-colors duration-200 rounded-xl hover:bg-gray-50">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
