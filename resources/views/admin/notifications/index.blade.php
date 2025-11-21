@extends('admin.layouts.app')

@section('page-title', 'Notifications')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Engagement</p>
            <h1 class="text-2xl font-bold text-gray-900">In-App Notifications</h1>
            <p class="text-sm text-gray-500 mt-1">Send targeted alerts to trainers and members.</p>
        </div>
        @can('create notifications')
            <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">
                New Notification
            </a>
        @endcan
    </div>

    <div class="admin-card">
        <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Filters</h3>
            <button type="button"
                    id="filters-toggle-btn-notifications"
                    class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                <span id="filters-toggle-text-notifications">Hide Filters</span>
                <svg id="filters-toggle-icon-notifications"
                     class="w-5 h-5 transition-transform duration-200"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                </svg>
            </button>
        </div>
        <div id="filters-content-notifications">
            <form id="notifications-filter-form"
                  method="GET"
                  action="{{ route('admin.notifications.index') }}"
                  class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="form-label" for="search">Search</label>
                    <input type="text"
                           name="search"
                           id="search"
                           value="{{ $filters['search'] ?? '' }}"
                           class="form-input w-full"
                           placeholder="Search by title or message">
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
                    <label class="form-label" for="requires_acknowledgement">Requires Acknowledgement</label>
                    <select name="requires_acknowledgement" id="requires_acknowledgement" class="form-select w-full">
                        <option value="">Any</option>
                        <option value="1" @selected(($filters['requires_acknowledgement'] ?? '') === '1')>Yes</option>
                        <option value="0" @selected(($filters['requires_acknowledgement'] ?? '') === '0')>No</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="scheduled_from">Scheduled From</label>
                    <input type="date"
                           name="scheduled_from"
                           id="scheduled_from"
                           value="{{ $filters['scheduled_from'] ?? '' }}"
                           class="form-input w-full">
                </div>
                <div>
                    <label class="form-label" for="scheduled_to">Scheduled To</label>
                    <input type="date"
                           name="scheduled_to"
                           id="scheduled_to"
                           value="{{ $filters['scheduled_to'] ?? '' }}"
                           class="form-input w-full">
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
                    <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">Reset</a>
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
            const localStorageKey = 'notifications-filters-collapsed';
            const toggleBtn = document.getElementById('filters-toggle-btn-notifications');
            const toggleIcon = document.getElementById('filters-toggle-icon-notifications');
            const toggleText = document.getElementById('filters-toggle-text-notifications');
            const filtersContent = document.getElementById('filters-content-notifications');

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

