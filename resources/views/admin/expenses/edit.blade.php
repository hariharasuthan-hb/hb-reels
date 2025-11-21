@extends('admin.layouts.app')

@section('page-title', 'Edit Expense')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Expenses</p>
            <h1 class="text-2xl font-bold text-gray-900">Edit Expense</h1>
            <p class="text-sm text-gray-500 mt-1">
                Update spending information.
            </p>
        </div>
        <a href="{{ route('admin.expenses.index') }}" class="btn btn-secondary">
            Back to list
        </a>
    </div>

    <div class="admin-card">
        <form method="POST" action="{{ route('admin.expenses.update', $expense) }}" class="space-y-6" enctype="multipart/form-data">
            @method('PUT')
            @include('admin.expenses.partials.form', [
                'expense' => $expense,
                'submitLabel' => 'Update Expense',
            ])
        </form>
    </div>
</div>
@endsection


