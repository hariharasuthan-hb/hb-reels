{{--
 | Banners Index View
 |
 | Displays a list of all banner images with management capabilities.
 | Banners are displayed on the frontend website and can be activated/deactivated.
 |
 | @var \App\DataTables\BannerDataTable $dataTable
 |
 | Features:
 | - Create new banner button
 | - DataTable with server-side processing
 | - View, edit, and delete banner actions
--}}
@extends('admin.layouts.app')

@section('page-title', 'Banners Management')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 mb-1">Banners</h1>
        </div>
        <a href="{{ route('admin.banners.create') }}" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create New Banner
        </a>
    </div>

    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- DataTable Card --}}
    <div class="admin-card">
        <div class="admin-table-wrapper">
            {!! $dataTable->html()->table(['class' => 'admin-table', 'id' => $dataTable->getTableIdPublic()]) !!}
        </div>
    </div>
</div>
@endsection

@push('styles')
    {{-- DataTables CSS is imported via Vite in resources/js/admin/app.js --}}
@endpush

@push('scripts')
    {!! $dataTable->scripts() !!}
@endpush
