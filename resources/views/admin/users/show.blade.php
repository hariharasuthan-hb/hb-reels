@extends('admin.layouts.app')

@section('page-title', 'View User')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-lg font-semibold">{{ $user->name }}</h1>
        <div class="flex space-x-3">
            <a href="{{ route('admin.users.edit', $user->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Edit User
            </a>
            <a href="{{ route('admin.users.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                Back to Users
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Personal Information Card --}}
        <div class="admin-card">
            <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <h2 class="text-base font-semibold text-gray-900">Personal Information</h2>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Name</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $user->name }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Email</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $user->email }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Phone</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $user->phone ?? '-' }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Age</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $user->age ?? '-' }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Gender</p>
                        <p class="text-sm text-gray-900 font-medium">{{ ucfirst($user->gender ?? '-') }}</p>
                    </div>
                </div>
                
                <div class="flex items-start col-span-2">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Address</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $user->address ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Account Information Card --}}
        <div class="admin-card">
            <div class="flex items-center mb-4 pb-3 border-b border-gray-200">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <h2 class="text-base font-semibold text-gray-900">Account Information</h2>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="flex items-start col-span-2">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Roles</p>
                        <div class="mt-1">
                            @if($user->roles->count() > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($user->roles as $role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                            {{ $role->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-sm text-gray-500">No roles assigned</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Status</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $user->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ ucfirst($user->status) }}
                        </span>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Created At</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $user->created_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
                
                <div class="flex items-start">
                    <div class="flex-shrink-0 w-8">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Updated At</p>
                        <p class="text-sm text-gray-900 font-medium">{{ $user->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

