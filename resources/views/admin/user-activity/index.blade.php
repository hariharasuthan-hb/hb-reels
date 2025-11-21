{{--
 | User Activity Overview Index View
 |
 | Displays a summary of member activities including total check-ins, today's check-ins,
 | and last activity date. Provides an overview of member engagement.
 | For trainers: shows only their assigned members.
 |
 | @var \App\Models\User[] $members - Collection of members with activity summary
 |
 | Features:
 | - Activity summary for each member
 | - Total check-ins count
 | - Today's check-ins count
 | - Last activity date
 | - Role-based filtering (trainers see only their members)
--}}
@extends('admin.layouts.app')

@section('page-title', 'Member Activity Overview')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 mb-1">Member Activity Overview</h1>
            <p class="text-sm text-gray-600">
                @if(auth()->user()->hasRole('trainer'))
                    View activity summary for all your assigned members
                @else
                    View activity summary for all members
                @endif
            </p>
        </div>
    </div>

    {{-- Members Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($members as $member)
            <div class="admin-card">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">{{ $member->name }}</h3>
                        <p class="text-sm text-gray-600">{{ $member->email }}</p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Total Check-ins</span>
                        <span class="text-sm font-semibold text-gray-900">{{ $member->total_check_ins }}</span>
                    </div>
                    
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Today's Check-ins</span>
                        <span class="text-sm font-semibold {{ $member->today_check_ins > 0 ? 'text-green-600' : 'text-gray-400' }}">
                            {{ $member->today_check_ins }}
                        </span>
                    </div>
                    
                    @if($member->last_activity)
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">Last Activity</span>
                            <span class="text-sm font-semibold text-gray-900">
                                {{ $member->last_activity->date->format('M d, Y') }}
                            </span>
                        </div>
                        @if($member->last_activity->check_in_time)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Check-in Time</span>
                                <span class="text-sm font-semibold text-gray-900">
                                    @if($member->last_activity->check_in_time instanceof \Carbon\Carbon)
                                        {{ $member->last_activity->check_in_time->format('H:i') }}
                                    @else
                                        {{ $member->last_activity->check_in_time }}
                                    @endif
                                </span>
                            </div>
                        @endif
                    @else
                        <div class="text-sm text-gray-400">No activity recorded</div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full">
                <div class="admin-card text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <p class="text-gray-600">
                        @if(auth()->user()->hasRole('trainer'))
                            No members assigned yet
                        @else
                            No members found
                        @endif
                    </p>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection

