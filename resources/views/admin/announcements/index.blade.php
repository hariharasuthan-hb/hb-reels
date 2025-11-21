@extends('admin.layouts.app')

@section('page-title', 'Announcements')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Engagement</p>
            <h1 class="text-2xl font-bold text-gray-900">Announcements</h1>
            <p class="text-sm text-gray-500 mt-1">Manage portal-wide announcements for trainers and members.</p>
        </div>
        @can('create announcements')
            <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
                New Announcement
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="admin-card">
        <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Filters</h3>
            <button type="button"
                    id="filters-toggle-btn-announcements"
                    class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                <span id="filters-toggle-text-announcements">Hide Filters</span>
                <svg id="filters-toggle-icon-announcements"
                     class="w-5 h-5 transition-transform duration-200"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                </svg>
            </button>
        </div>
        <div id="filters-content-announcements">
            <form id="announcements-filter-form"
                  method="GET"
                  action="{{ route('admin.announcements.index') }}"
                  class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="form-label" for="search">Search</label>
                    <input type="text"
                           name="search"
                           id="search"
                           value="{{ $filters['search'] ?? '' }}"
                           class="form-input w-full"
                           placeholder="Search by title or body">
                </div>
                <div>
                    <label class="form-label" for="status">Status</label>
                    <select name="status" id="status" class="form-select w-full">
                        <option value="">All statuses</option>
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="audience_type">Audience</label>
                    <select name="audience_type" id="audience_type" class="form-select w-full">
                        <option value="">All audiences</option>
                        @foreach($audienceOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['audience_type'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label" for="published_from">Published From</label>
                    <input type="date"
                           name="published_from"
                           id="published_from"
                           value="{{ $filters['published_from'] ?? '' }}"
                           class="form-input w-full">
                </div>
                <div>
                    <label class="form-label" for="published_to">Published To</label>
                    <input type="date"
                           name="published_to"
                           id="published_to"
                           value="{{ $filters['published_to'] ?? '' }}"
                           class="form-input w-full">
                </div>
                <div class="md:col-span-2 xl:col-span-4 flex gap-2">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-table-wrapper">
            {!! $dataTable->html()->table(['class' => 'admin-table w-full'], true) !!}
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $dataTable->scripts() !!}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const localStorageKey = 'announcements-filters-collapsed';
            const toggleBtn = document.getElementById('filters-toggle-btn-announcements');
            const toggleIcon = document.getElementById('filters-toggle-icon-announcements');
            const toggleText = document.getElementById('filters-toggle-text-announcements');
            const filtersContent = document.getElementById('filters-content-announcements');

            if (!toggleBtn || !filtersContent) {
                return;
            }

            function toggleFilters(collapsed) {
                if (collapsed) {
                    filtersContent.style.display = 'none';
                    toggleIcon.style.transform = 'rotate(180deg)';
                    toggleText.textContent = 'Show Filters';
                    localStorage.setItem(localStorageKey, 'true');
                } else {
                    filtersContent.style.display = 'block';
                    toggleIcon.style.transform = 'rotate(0deg)';
                    toggleText.textContent = 'Hide Filters';
                    localStorage.setItem(localStorageKey, 'false');
                }
            }

            const isCollapsed = localStorage.getItem(localStorageKey) === 'true';
            toggleFilters(isCollapsed);

            toggleBtn.addEventListener('click', function () {
                const currentlyCollapsed = filtersContent.style.display === 'none';
                toggleFilters(!currentlyCollapsed);
            });
        });
    </script>
@endpush

