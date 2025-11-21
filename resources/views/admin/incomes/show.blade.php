@extends('admin.layouts.app')

@section('page-title', 'Income Details')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Incomes</p>
            <h1 class="text-2xl font-bold text-gray-900">{{ $income->category }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                Received on {{ $income->received_at?->format('M d, Y') ?? '—' }}
            </p>
        </div>
        <a href="{{ route('admin.incomes.index') }}" class="btn btn-secondary">
            Back to list
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="admin-card space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500">Amount</p>
                    <p class="text-3xl font-bold text-gray-900">${{ number_format($income->amount, 2) }}</p>
                </div>
            </div>
            <dl class="grid grid-cols-1 gap-3 text-sm text-gray-700">
                <div>
                    <dt class="text-gray-500">Category</dt>
                    <dd>{{ $income->category }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Source</dt>
                    <dd>{{ $income->source ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Payment Method</dt>
                    <dd>{{ $income->readable_payment_method }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Reference</dt>
                    <dd>{{ $income->reference ?? '—' }}</dd>
                </div>
                @if($income->reference_document_url)
                    <div>
                        <dt class="text-gray-500">Reference Document</dt>
                        <dd>
                            <a href="{{ $income->reference_document_url }}"
                               target="_blank"
                               class="text-primary-600 hover:underline">
                                View file
                            </a>
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-gray-500">Received At</dt>
                    <dd>{{ $income->received_at?->format('M d, Y') ?? '—' }}</dd>
                </div>
            </dl>
        </div>
        <div class="admin-card space-y-4">
            <h2 class="text-lg font-semibold text-gray-900">Notes</h2>
            <p class="text-sm text-gray-700 whitespace-pre-line">
                {{ $income->notes ?? 'No additional notes provided.' }}
            </p>
        </div>
    </div>
</div>
@endsection

