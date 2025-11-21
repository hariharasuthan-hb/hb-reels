{{-- Header/Navigation --}}
@php
    $menus = \App\Models\Menu::getActiveMenus();
@endphp

<header class="bg-white shadow-md sticky top-0 z-50">
    <nav class="container mx-auto px-4 py-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                @php
                    $siteSettings = \App\Models\SiteSetting::getSettings();
                    $landingPage = \App\Models\LandingPageContent::getActive();
                    // Priority: Site Settings Logo > Landing Page Logo
                    $logo = $siteSettings->logo ?? ($landingPage->logo ?? null);
                    $siteTitle = $siteSettings->site_title ?? 'Gym Management';
                @endphp
                <a href="{{ route('frontend.home') }}" class="flex items-center gap-3 hover:opacity-80 transition-opacity">
                    @if($logo)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($logo) }}" alt="{{ $siteTitle }}" class="h-12 object-contain">
                    @endif
                    <span class="text-2xl font-bold bg-gradient-to-r from-orange-500 to-black bg-clip-text text-transparent">{{ $siteTitle }}</span>
                </a>
            </div>
            
            <div class="hidden md:flex space-x-6 items-center">
                @include('frontend.components.navigation-links', ['class' => 'text-gray-700 hover:text-blue-600 transition'])
                
                @if(auth()->check())
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-700 hover:text-blue-600 transition">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-gray-700 hover:text-blue-600 transition">Login</a>
                    <a href="{{ route('frontend.register') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">Register</a>
                @endif
            </div>
            
            {{-- Mobile Menu Button --}}
            <div class="md:hidden relative">
                <button class="text-gray-700" id="mobile-menu-btn" type="button">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                
                {{-- Mobile Menu Dropdown --}}
                <div id="mobile-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg py-2 z-50 border border-gray-200">
                    @include('frontend.components.navigation-links', ['class' => 'block px-4 py-2 text-gray-700 hover:bg-gray-100'])
                    
                    <div class="border-t border-gray-200 mt-2 pt-2">
                        @if(auth()->check())
                            <form method="POST" action="{{ route('logout') }}" class="px-4">
                                @csrf
                                <button type="submit" class="w-full text-left text-gray-700 hover:bg-gray-100 py-2">Logout</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Login</a>
                            <a href="{{ route('frontend.register') }}" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Register</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>
