@extends('admin.layouts.app')

@section('page-title', 'Edit Announcement')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Engagement</p>
            <h1 class="text-2xl font-bold text-gray-900">Edit Announcement</h1>
            <p class="text-sm text-gray-500 mt-1">Update details and visibility.</p>
        </div>
        <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">
            Back to list
        </a>
    </div>

    <div class="admin-card">
        <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="space-y-6">
            @method('PUT')
            @include('admin.announcements.partials.form', [
                'announcement' => $announcement,
                'submitLabel' => 'Save Changes',
            ])
        </form>
    </div>
</div>
@endsection

