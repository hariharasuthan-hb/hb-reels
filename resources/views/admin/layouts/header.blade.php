{{-- Admin Header --}}
<header class="admin-header">
    <div class="px-4 py-4">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-4">
                {{-- Sidebar Toggle Button --}}
                <button @click="sidebarOpen = !sidebarOpen" 
                        class="lg:hidden p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all duration-200 hover-lift focus-ring">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                
                {{-- Collapse Toggle Button (Desktop) --}}
                <button @click="sidebarCollapsed = !sidebarCollapsed" 
                        class="hidden lg:flex p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all duration-200 hover-lift focus-ring"
                        title="Toggle Sidebar">
                    <svg x-show="!sidebarCollapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                    </svg>
                    <svg x-show="sidebarCollapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                    </svg>
                </button>
                
                <div class="page-header">
                    <h1 class="page-title">
                        @yield('page-title', 'Admin Dashboard')
                    </h1>
                </div>
            </div>
            
            <div class="flex items-center space-x-4">
                {{-- Notifications --}}
                @php
                    $notificationRepository = app(\App\Repositories\Interfaces\InAppNotificationRepositoryInterface::class);
                    $notificationCount = $notificationRepository->getUnreadCountForUser(Auth::user());
                    $notificationBadge = $notificationCount > 99 ? '99+' : $notificationCount;
                @endphp
                <a href="{{ route('admin.notification-center.index') }}"
                   class="relative p-2.5 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-xl transition-all duration-200 hover-lift focus-ring"
                   title="Notification Center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    @if($notificationCount > 0)
                        <span class="absolute -top-1 -right-1 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-gradient-to-r from-red-500 to-red-600 text-xs font-semibold text-white ring-2 ring-white shadow-sm" style="padding-left:0.30rem;padding-right:0.30rem;">
                            {{ $notificationBadge }}
                        </span>
                    @endif
                </a>

                {{-- User Menu --}}
                <div class="relative">
                    <div class="flex items-center space-x-3">
                        <div class="text-right hidden sm:block">
                            <p class="text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                        </div>
                        <div class="h-11 w-11 rounded-xl bg-gradient-to-br from-primary-600 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-primary-500/30 hover:shadow-xl transition-shadow duration-200">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>



