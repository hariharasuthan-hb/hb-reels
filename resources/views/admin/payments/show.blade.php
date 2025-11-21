@extends('admin.layouts.app')

@section('page-title', 'Payment Details')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Payment</p>
            <h1 class="text-2xl font-bold text-gray-900">Payment #{{ $payment->id }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                Transaction {{ $payment->transaction_id ?? 'Not provided' }}
            </p>
        </div>
        <a href="{{ route('admin.payments.index') }}" class="btn btn-secondary">
            Back to payments
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="admin-card space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Amount</p>
                    <p class="text-3xl font-bold text-gray-900">
                        ${{ number_format($payment->final_amount ?? $payment->amount ?? 0, 2) }}
                    </p>
                </div>
                <span class="badge badge-{{ $payment->status }}">
                    {{ ucfirst(str_replace('_', ' ', $payment->status ?? 'unknown')) }}
                </span>
            </div>
            <dl class="grid grid-cols-1 gap-3 text-sm text-gray-700">
                <div>
                    <dt class="text-gray-500">Paid At</dt>
                    <dd>{{ $payment->paid_at ? $payment->paid_at->format('M d, Y h:i A') : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Payment Method</dt>
                    <dd>{{ $payment->readable_payment_method }}</dd>
                </div>
            </dl>
        </div>

        <div class="admin-card space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Customer</h2>
            <dl class="grid grid-cols-1 gap-3 text-sm text-gray-700">
                <div>
                    <dt class="text-gray-500">Name</dt>
                    <dd>{{ $payment->user->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Email</dt>
                    <dd>{{ $payment->user->email ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="admin-card space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">Subscription</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
            <div>
                <dt class="text-gray-500">Plan</dt>
                <dd>{{ $payment->subscription->subscriptionPlan->plan_name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Status</dt>
                <dd>{{ ucfirst($payment->subscription->status ?? '—') }}</dd>
            </div>
        </dl>
    </div>

    @if($payment->payment_details)
        <div class="admin-card space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Gateway Payload</h2>
            <pre class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-auto">{{ json_encode($payment->payment_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif
</div>
@endsection


