<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - Admin Portal</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/admin/app.css', 'resources/js/admin/app.js'])
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @stack('styles')
</head>
<body class="font-sans antialiased custom-scrollbar" x-data="{ 
    sidebarOpen: false, 
    sidebarCollapsed: (() => {
        try {
            return localStorage.getItem('sidebarCollapsed') === 'true';
        } catch(e) {
            return false;
        }
    })()
}" 
x-init="$watch('sidebarCollapsed', value => {
    try {
        localStorage.setItem('sidebarCollapsed', value);
    } catch(e) {
        console.error('Failed to save sidebar state:', e);
    }
})">
    <div class="h-screen flex overflow-hidden">
        {{-- Mobile Overlay --}}
        <div x-show="sidebarOpen" 
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 bg-gray-900/50 z-40 lg:hidden"></div>
        
        {{-- Sidebar --}}
        <div :class="{ '-translate-x-full lg:translate-x-0': !sidebarOpen, 'translate-x-0': sidebarOpen }"
             class="fixed lg:static inset-y-0 left-0 z-50 transition-transform duration-300 ease-in-out lg:transition-none">
            @include('admin.layouts.sidebar')
        </div>
        
        {{-- Main Content --}}
        <div class="flex-1 flex flex-col w-full overflow-hidden">
            {{-- Header --}}
            @include('admin.layouts.header')
            
            {{-- Page Content --}}
            <main class="flex-1 p-4 lg:p-6 overflow-y-auto">
                @yield('content')
            </main>
        </div>
    </div>
    
    @include('admin.components.confirm-modal')
    @stack('scripts')
</body>
</html>

