@extends('admin.layouts.app')

@section('page-title', 'Add Expense')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Expenses</p>
            <h1 class="text-2xl font-bold text-gray-900">Add Expense</h1>
            <p class="text-sm text-gray-500 mt-1">
                Log a new operational expense.
            </p>
        </div>
        <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary">
            Back to list
        </a>
    </div>

    <div class="admin-card">
        <form method="POST" action="{{ route('admin.expenses.store') }}" class="space-y-6" enctype="multipart/form-data">
            @include('admin.expenses.partials.form', [
                'expense' => null,
                'submitLabel' => 'Save Expense',
            ])
        </form>
    </div>
</div>
@endsection


