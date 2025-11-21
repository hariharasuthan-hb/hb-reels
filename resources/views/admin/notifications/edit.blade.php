@extends('admin.layouts.app')

@section('page-title', 'Edit Notification')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Engagement</p>
            <h1 class="text-2xl font-bold text-gray-900">Edit Notification</h1>
            <p class="text-sm text-gray-500 mt-1">Update the message or targeting.</p>
        </div>
        <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">
            Back to list
        </a>
    </div>

    <div class="admin-card">
        <form method="POST" action="{{ route('admin.notifications.update', $notification) }}" class="space-y-6">
            @method('PUT')
            @include('admin.notifications.partials.form', [
                'notification' => $notification,
                'users' => $users,
                'submitLabel' => 'Save Changes',
            ])
        </form>
    </div>
</div>
@endsection

