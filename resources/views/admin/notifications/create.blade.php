@extends('admin.layouts.app')

@section('page-title', 'Create Notification')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Engagement</p>
            <h1 class="text-2xl font-bold text-gray-900">Create Notification</h1>
            <p class="text-sm text-gray-500 mt-1">Send targeted alerts to users.</p>
        </div>
        <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">
            Back to list
        </a>
    </div>

    <div class="admin-card">
        <form method="POST" action="{{ route('admin.notifications.store') }}" class="space-y-6">
            @include('admin.notifications.partials.form', [
                'notification' => null,
                'users' => $users,
                'submitLabel' => 'Send Notification',
            ])
        </form>
    </div>
</div>
@endsection

