@extends('admin.layouts.app')

@section('page-title', 'Create Announcement')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Engagement</p>
            <h1 class="text-2xl font-bold text-gray-900">Create Announcement</h1>
            <p class="text-sm text-gray-500 mt-1">Share important updates with trainers and members.</p>
        </div>
        <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">
            Back to list
        </a>
    </div>

    <div class="admin-card">
        <form method="POST" action="{{ route('admin.announcements.store') }}" class="space-y-6">
            @include('admin.announcements.partials.form', [
                'announcement' => null,
                'submitLabel' => 'Publish Announcement',
            ])
        </form>
    </div>
</div>
@endsection

