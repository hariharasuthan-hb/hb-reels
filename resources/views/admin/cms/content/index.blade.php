{{--
 | CMS Content Index View
 |
 | Displays a list of all CMS content items with management capabilities.
 | CMS content is used for managing reusable content blocks (hero, features, testimonials).
 |
 | @var \App\DataTables\CmsContentDataTable $dataTable
 |
 | Features:
 | - Create new CMS content button
 | - DataTable with server-side processing
 | - View, edit, and delete content actions
--}}
@extends('admin.layouts.app')

@section('page-title', 'CMS Content')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 mb-1">CMS Content</h1>
        </div>
        <a href="{{ route('admin.cms.content.create') }}" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create New Content
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
