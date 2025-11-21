@extends('admin.layouts.app')

@section('page-title', 'Edit Subscription')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Subscription</h1>
            <p class="text-sm text-gray-600">Update subscription status and review details.</p>
        </div>
        <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">
            ← Back to Subscriptions
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg shadow p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Member & Plan</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Member</span>
                    <span class="font-semibold text-gray-900">{{ $subscription->user->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Email</span>
                    <span class="font-semibold text-gray-900">{{ $subscription->user->email ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Plan</span>
                    <span class="font-semibold text-gray-900">{{ $subscription->subscriptionPlan->plan_name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Gateway</span>
                    <span class="font-semibold text-gray-900">{{ strtoupper($subscription->gateway) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Started</span>
                    <span class="font-semibold text-gray-900">{{ optional($subscription->started_at)->format('M d, Y H:i') ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Next Billing</span>
                    <span class="font-semibold text-gray-900">{{ optional($subscription->next_billing_at)->format('M d, Y H:i') ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Update Status</h2>
            <form action="{{ route('admin.subscriptions.update', $subscription) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select id="status" name="status" class="admin-input" required>
                        @foreach(['trialing','active','past_due','pending','canceled','expired'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $subscription->status) === $status)>
                                {{ ucfirst(str_replace('_', ' ', $status)) }}
                            </option>
                        @endforeach
                    </select>
                    @error('status')
                        <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-full">
                    Save Changes
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

