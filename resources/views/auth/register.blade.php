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

    <!-- Welcome Section -->
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-semibold text-gray-900 mb-2">Create Account</h2>
        <p class="text-sm text-gray-600 leading-relaxed">
            Register to create your account
        </p>
    </div>

    <form method="POST" action="{{ route('frontend.register.store') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Full Name')" class="mb-2.5 text-sm font-semibold text-gray-700" />
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-600">
                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <x-text-input id="name" 
                              class="block w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-gray-50 focus:bg-white text-gray-900 placeholder-gray-400" 
                              type="text" 
                              name="name" 
                              :value="old('name')" 
                              required 
                              autofocus 
                              autocomplete="name"
                              placeholder="John Doe" />
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-2.5" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" class="mb-2.5 text-sm font-semibold text-gray-700" />
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-600">
                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                    </svg>
                </div>
                <x-text-input id="email" 
                              class="block w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-gray-50 focus:bg-white text-gray-900 placeholder-gray-400" 
                              type="email" 
                              name="email" 
                              :value="old('email')" 
                              required 
                              autocomplete="username"
                              placeholder="you@example.com" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2.5" />
        </div>

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
                              autocomplete="new-password"
                              placeholder="••••••••" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2.5" />
            <p class="mt-2 text-xs font-medium text-gray-500 flex items-center">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Must be at least 8 characters with letters and numbers
            </p>
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" class="mb-2.5 text-sm font-semibold text-gray-700" />
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none transition-colors group-focus-within:text-blue-600">
                    <svg class="h-5 w-5 text-gray-400 group-focus-within:text-blue-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <x-text-input id="password_confirmation" 
                              class="block w-full pl-12 pr-4 py-3.5 border-2 border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 bg-gray-50 focus:bg-white text-gray-900 placeholder-gray-400"
                              type="password"
                              name="password_confirmation" 
                              required 
                              autocomplete="new-password"
                              placeholder="••••••••" />
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2.5" />
        </div>

        <div class="pt-2">
            <x-primary-button class="w-full py-3 text-sm font-semibold bg-gradient-to-r from-blue-600 via-blue-700 to-purple-600 hover:from-blue-700 hover:via-blue-800 hover:to-purple-700 transition-all duration-300 shadow-md hover:shadow-lg rounded-xl">
                <span class="flex items-center justify-center">
                    <span>{{ __('Create Account') }}</span>
                    <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </span>
            </x-primary-button>
        </div>
    </form>

    <!-- Divider -->
    <div class="relative my-8">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-200"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-4 bg-white text-gray-500 font-medium">Already have an account?</span>
        </div>
    </div>

    <div class="text-center">
        <p class="text-sm text-gray-600 mb-0">
            Already registered?
            <a href="{{ route('login') }}" class="font-bold text-blue-600 hover:text-blue-700 transition-colors duration-200 hover:underline ml-1">
                {{ __('Sign in here') }}
            </a>
        </p>
    </div>
</x-guest-layout>
