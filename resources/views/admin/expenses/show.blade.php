@extends('admin.layouts.app')

@section('page-title', 'Expense Details')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Expenses</p>
            <h1 class="text-2xl font-bold text-gray-900">{{ $expense->category }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                Logged on {{ $expense->spent_at?->format('M d, Y') ?? '—' }}
            </p>
        </div>
        <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary">
            Back to list
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="admin-card space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Amount</p>
                    <p class="text-3xl font-bold text-gray-900">${{ number_format($expense->amount, 2) }}</p>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-3 text-sm text-gray-700">
                <div>
                    <dt class="text-gray-500">Category</dt>
                    <dd>{{ $expense->category }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Vendor</dt>
                    <dd>{{ $expense->vendor ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Payment Method</dt>
                    <dd>{{ $expense->readable_payment_method }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Reference</dt>
                    <dd>{{ $expense->reference ?? '—' }}</dd>
                </div>
                @if($expense->reference_document_url)
                    <div>
                        <dt class="text-gray-500">Reference Document</dt>
                        <dd>
                            <a href="{{ $expense->reference_document_url }}"
                               target="_blank"
                               class="text-primary-600 hover:underline">
                                View file
                            </a>
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-gray-500">Spent At</dt>
                    <dd>{{ $expense->spent_at?->format('M d, Y') ?? '—' }}</dd>
                </div>
            </dl>
        </div>
        <div class="admin-card space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Notes</h2>
            <p class="text-sm text-gray-700 whitespace-pre-line">
                {{ $expense->notes ?? 'No additional notes provided.' }}
            </p>
        </div>
    </div>
</div>
@endsection


