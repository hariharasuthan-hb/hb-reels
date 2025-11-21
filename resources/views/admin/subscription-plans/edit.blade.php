@extends('admin.layouts.app')

@section('page-title', 'Edit Subscription Plan')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-lg font-semibold text-gray-900">Edit Subscription Plan</h1>
        <a href="{{ route('admin.subscription-plans.index') }}" class="btn btn-secondary">
            Back to Plans
        </a>
    </div>

    <div class="admin-card">
        <form action="{{ route('admin.subscription-plans.update', $subscriptionPlan->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.subscription-plans._form', ['subscriptionPlan' => $subscriptionPlan, 'isEdit' => true])
            
            <div class="flex justify-end gap-3 mt-6">
                <a href="{{ route('admin.subscription-plans.index') }}" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Update Plan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

