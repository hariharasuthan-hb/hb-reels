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
        <h2 class="text-2xl font-semibold text-gray-900 mb-2">Confirm Password</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            Please confirm your password to continue
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Password')" class="mb-2.5 text-sm font-semibold text-gray-700" />
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-600">
                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <x-text-input id="password" 
                              class="block w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-gray-50 focus:bg-white text-gray-900 placeholder-gray-400"
                              type="password"
                              name="password"
                              required 
                              autocomplete="current-password"
                              placeholder="••••••••" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2.5" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full py-3 text-sm font-semibold bg-gradient-to-r from-blue-600 via-blue-700 to-purple-600 hover:from-blue-700 hover:via-blue-800 hover:to-purple-700 transition-all duration-300 shadow-md hover:shadow-lg rounded-xl">
                <span class="flex items-center justify-center">
                    <span>{{ __('Confirm') }}</span>
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </span>
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
