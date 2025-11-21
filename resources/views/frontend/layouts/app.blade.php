<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Gym Management') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/frontend/app.css', 'resources/js/frontend/app.js'])
    
    <!-- SweetAlert2 Component -->
    @include('frontend.components.swal')
    
    @stack('styles')
</head>
<body class="font-sans antialiased">
    {{-- Header --}}
    @include('frontend.components.header')
    
    {{-- Main Content --}}
    <main>
        @if(request()->routeIs('member.*') && !request()->routeIs('frontend.home'))
            <x-back-button />
        @endif
        @yield('content')
    </main>
    
    {{-- Footer --}}
    @include('frontend.components.footer')
    
    @stack('scripts')
</body>
</html>

