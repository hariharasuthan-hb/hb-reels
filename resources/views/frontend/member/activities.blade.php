@extends('frontend.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Activities</h1>
            <p class="mt-2 text-gray-600">View your check-in/check-out history and generated videos</p>
        </div>

        {{-- Success Message --}}
        @if(session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <div class="flex">
                    <svg class="h-5 w-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-green-700">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        {{-- Error Message --}}
        @if(session('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <p class="text-sm text-red-700">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        {{-- Activities Table --}}
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Activity History</h2>
            </div>

            @if($activities->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check In</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Check Out</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Summary</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($activities as $activity)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $activity->date->format('M d, Y') }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $activity->date->format('l') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($activity->check_in_time)
                                            <div class="text-sm text-gray-900">
                                                {{ $activity->check_in_time->format('h:i A') }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $activity->check_in_time->diffForHumans() }}
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($activity->check_out_time)
                                            <div class="text-sm text-gray-900">
                                                {{ $activity->check_out_time->format('h:i A') }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $activity->check_out_time->diffForHumans() }}
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($activity->duration_minutes)
                                            <div class="text-sm text-gray-900">
                                                {{ floor($activity->duration_minutes / 60) }}h {{ $activity->duration_minutes % 60 }}m
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            {{ $activity->check_in_method === 'manual' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800' }}">
                                            {{ ucfirst($activity->check_in_method ?? 'N/A') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $activity->workout_summary ?? 'Manual check-in' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $activities->links() }}
                </div>
            @else
                {{-- No Activities --}}
                <div class="px-6 py-12 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No Activities Yet</h3>
                    <p class="text-gray-600 mb-6">You haven't checked in to any activities yet.</p>
                    <a href="{{ route('member.dashboard') }}" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                        Go to Dashboard
                    </a>
                </div>
            @endif
        </div>

        {{-- Generated Videos --}}
        @if($videos->count() > 0)
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Generated Videos</h2>
                <p class="text-sm text-gray-600 mt-1">Videos you have created using the reel generator</p>
            </div>

            <div class="divide-y divide-gray-200">
                @foreach($videos as $video)
                <div class="px-6 py-4 hover:bg-gray-50 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <svg class="w-10 h-10 text-red-500" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M2 6H0v5h.01L0 20c0 1.1.89 2 2 2h17c.53 0 1.04-.21 1.41-.59.78-.78.78-2.05 0-2.83L13.84 6H2zm0 5h14.68l1.47 1.47c.39.39.39 1.02 0 1.41-.2.2-.45.3-.71.3H2V11zm11.5-5l-1.5-1.5L13.5 2l.5.5 1.5 1.5L13.5 6z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-900">{{ $video['filename'] }}</h3>
                                <p class="text-sm text-gray-500">
                                    Generated {{ $video['created_at_relative'] }} • {{ $video['size'] }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <a href="{{ $video['url'] }}"
                               class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Download
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
