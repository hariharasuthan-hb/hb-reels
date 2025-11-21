@extends('admin.layouts.app')

@section('page-title', 'Add Income')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Incomes</p>
            <h1 class="text-2xl font-bold text-gray-900">Add Income</h1>
            <p class="text-sm text-gray-500 mt-1">
                Log a new income entry.
            </p>
        </div>
        <a href="{{ route('admin.incomes.index') }}" class="btn btn-secondary">
            Back to list
        </a>
    </div>

    <div class="admin-card">
        <form method="POST" action="{{ route('admin.incomes.store') }}" class="space-y-6" enctype="multipart/form-data">
            @include('admin.incomes.partials.form', [
                'income' => null,
                'submitLabel' => 'Save Income',
            ])
        </form>
    </div>
</div>
@endsection

