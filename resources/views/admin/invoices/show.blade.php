@extends('admin.layouts.app')

@section('page-title', 'Invoice Details')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Invoice</p>
            <h1 class="text-2xl font-bold text-gray-900">Invoice #INV-{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                Transaction {{ $invoice->transaction_id ?? 'Not provided' }}
            </p>
        </div>
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-secondary">
            Back to invoices
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="admin-card space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Amount</p>
                    <p class="text-3xl font-bold text-gray-900">
                        ${{ number_format($invoice->final_amount ?? $invoice->amount ?? 0, 2) }}
                    </p>
                </div>
                <span class="badge badge-{{ $invoice->status }}">
                    {{ ucfirst(str_replace('_', ' ', $invoice->status ?? 'unknown')) }}
                </span>
            </div>
            <dl class="grid grid-cols-1 gap-3 text-sm text-gray-700">
                <div>
                    <dt class="text-gray-500">Issued At</dt>
                    <dd>{{ $invoice->paid_at ? $invoice->paid_at->format('M d, Y h:i A') : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Payment Method</dt>
                    <dd>{{ $invoice->readable_payment_method }}</dd>
                </div>
            </dl>
        </div>

        <div class="admin-card space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Customer</h2>
            <dl class="grid grid-cols-1 gap-3 text-sm text-gray-700">
                <div>
                    <dt class="text-gray-500">Name</dt>
                    <dd>{{ $invoice->user->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Email</dt>
                    <dd>{{ $invoice->user->email ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="admin-card space-y-4">
        <h2 class="text-lg font-semibold text-gray-900">Subscription</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
            <div>
                <dt class="text-gray-500">Plan</dt>
                <dd>{{ $invoice->subscription->subscriptionPlan->plan_name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">Subscription Status</dt>
                <dd>{{ ucfirst($invoice->subscription->status ?? '—') }}</dd>
            </div>
        </dl>
    </div>

    @if($invoice->payment_details)
        <div class="admin-card space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Gateway Payload</h2>
            <pre class="bg-gray-900 text-gray-100 text-xs rounded-lg p-4 overflow-auto">{{ json_encode($invoice->payment_details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    @endif
</div>
@endsection


