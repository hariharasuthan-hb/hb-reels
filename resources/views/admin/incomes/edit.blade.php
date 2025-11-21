@extends('admin.layouts.app')

@section('page-title', 'Edit Income')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Incomes</p>
            <h1 class="text-2xl font-bold text-gray-900">Edit Income</h1>
            <p class="text-sm text-gray-500 mt-1">
                Update income information.
            </p>
        </div>
        <a href="{{ route('admin.incomes.index') }}" class="btn btn-secondary">
            Back to list
        </a>
    </div>

    <div class="admin-card">
        <form method="POST" action="{{ route('admin.incomes.update', $income) }}" class="space-y-6" enctype="multipart/form-data">
            @method('PUT')
            @include('admin.incomes.partials.form', [
                'income' => $income,
                'submitLabel' => 'Update Income',
            ])
        </form>
    </div>
</div>
@endsection

