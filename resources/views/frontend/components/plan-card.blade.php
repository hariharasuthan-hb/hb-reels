@php
    /**
     * Reusable Plan Card Component
     * 
     * Displays a workout plan in a consistent card format.
     * 
     * @param object $plan - The plan object
     * @param string|null $viewRoute - Route name for viewing the plan (optional)
     */
    $plan = $plan ?? null;
    $viewRoute = $viewRoute ?? null;
    
    // Determine colors and icons based on type
    $theme = [
            'bg' => 'bg-gradient-to-br from-green-50 to-emerald-50',
            'border' => 'border-green-200',
            'icon' => 'text-green-600',
            'badge' => 'bg-green-100 text-green-800',
            'iconBg' => 'bg-green-100',
    ];
    
    // Status colors
    $statusColors = [
        'active' => 'bg-green-100 text-green-800',
        'completed' => 'bg-blue-100 text-blue-800',
        'paused' => 'bg-yellow-100 text-yellow-800',
        'cancelled' => 'bg-red-100 text-red-800',
    ];
    $statusColor = $statusColors[$plan->status ?? 'active'] ?? 'bg-gray-100 text-gray-800';
@endphp

@if($plan)
<div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-200 border {{ $theme['border'] }} overflow-hidden">
    <div class="p-6">
        {{-- Header --}}
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-start space-x-3 flex-1">
                <div class="flex-shrink-0 {{ $theme['iconBg'] }} rounded-lg p-3">
                        <svg class="w-6 h-6 {{ $theme['icon'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <h3 class="text-lg font-semibold text-gray-900 truncate">{{ $plan->plan_name ?? 'Untitled Plan' }}</h3>
                </div>
            </div>
            <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ $statusColor }} whitespace-nowrap ml-2">
                {{ ucfirst($plan->status ?? 'active') }}
            </span>
        </div>
        
        {{-- Description --}}
        @if($plan->description)
        <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ Str::limit($plan->description, 100) }}</p>
        @endif
        
        {{-- Plan Details --}}
        <div class="space-y-3 mb-4">
            {{-- Dates --}}
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <span class="font-medium">Start:</span>
                <span class="ml-2">{{ format_date($plan->start_date) }}</span>
                @if($plan->end_date)
                    <span class="mx-2">-</span>
                    <span>{{ format_date($plan->end_date) }}</span>
                @endif
            </div>
            
            {{-- Duration --}}
            @if($plan->duration_weeks)
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium">Duration:</span>
                <span class="ml-2">{{ $plan->duration_weeks }} {{ $plan->duration_weeks == 1 ? 'week' : 'weeks' }}</span>
            </div>
            @endif
            
            {{-- Exercises Count --}}
            @if($plan->exercises && is_array($plan->exercises))
            <div class="flex items-center text-sm text-gray-600">
                <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                </svg>
                <span class="font-medium">Exercises:</span>
                <span class="ml-2">{{ count($plan->exercises) }} {{ count($plan->exercises) == 1 ? 'exercise' : 'exercises' }}</span>
            </div>
            @endif
        </div>
        
        {{-- Action Button --}}
        @if($viewRoute)
        <a href="{{ route($viewRoute, $plan->id) }}" 
           class="block w-full text-center px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200">
            View Details
        </a>
        @endif
    </div>
</div>
@endif

