@php
    /**
     * Reusable Report Section Component
     * 
     * @param string $title - Report title
     * @param string $description - Report description
     * @param string $exportType - Export type (payments, invoices, expenses, incomes, subscriptions, activity_logs)
     * @param array $filters - Current filter values
     * @param array $filterOptions - Available filter options (statusOptions, methodOptions, etc.)
     * @param string $indexRoute - Route name for index (e.g., 'admin.payments.index')
     * @param bool $showExportButton - Whether to show export button (default: true)
     * @param array $headerActions - Additional action buttons to display next to export
     * @param object $dataTable - DataTable instance for displaying data
     * 
     * Note: Export functionality uses queue jobs and includes:
     * - All filter form values (status, method, category, date_from, date_to, etc.)
     * - DataTables search value (from the search box)
     */
    $showExportButton = $showExportButton ?? true;
    $indexRoute = $indexRoute ?? 'admin.' . $exportType . '.index';
    $headerActions = collect($headerActions ?? []);
    $categoryLabel = $categoryLabel ?? 'Financial';
@endphp

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">{{ $categoryLabel }}</p>
            <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $description }}
            </p>
        </div>
        @if($showExportButton || $headerActions->isNotEmpty())
            <div class="flex items-center gap-3 flex-wrap justify-start md:justify-end">
                @foreach($headerActions as $action)
                    @php
                        $can = $action['can'] ?? null;
                        $shouldShow = !$can || auth()->user()->can($can);
                    @endphp
                    @if($shouldShow)
                        @if(($action['as'] ?? 'link') === 'button')
                            <button type="button"
                                    class="{{ $action['class'] ?? 'btn btn-secondary' }}"
                                    @if(isset($action['onclick'])) onclick="{{ $action['onclick'] }}" @endif>
                                @if(!empty($action['icon']))
                                    {!! $action['icon'] !!}
                                @endif
                                {{ $action['label'] ?? 'Action' }}
                            </button>
                        @else
                            <a href="{{ $action['url'] ?? '#' }}"
                               class="{{ $action['class'] ?? 'btn btn-primary' }}"
                               @if(isset($action['target'])) target="{{ $action['target'] }}" @endif>
                                @if(!empty($action['icon']))
                                    {!! $action['icon'] !!}
                                @endif
                                {{ $action['label'] ?? 'Action' }}
                            </a>
                        @endif
                    @endif
                @endforeach

        @if($showExportButton && $exportType)
                <button type="button" 
                        id="export-btn" 
                        class="btn btn-secondary"
                        data-export-type="{{ $exportType }}"
                        data-export-route="{{ route('admin.exports.export', ['type' => $exportType]) }}">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Data
                </button>
                @endif
            </div>
        @endif
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="alert alert-success animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>{{ session('warning') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Export Status Notification --}}
    <div id="export-status" class="hidden alert alert-info">
        <svg class="w-5 h-5 flex-shrink-0 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span id="export-status-message">Preparing export...</span>
    </div>

    {{-- Filters Section --}}
    @if(isset($filters) && isset($filterOptions))
        <div class="admin-card">
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Filters</h3>
                <button type="button" 
                        id="filters-toggle-btn-{{ $exportType }}" 
                        class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors">
                    <span id="filters-toggle-text-{{ $exportType }}">Hide Filters</span>
                    <svg id="filters-toggle-icon-{{ $exportType }}" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                </button>
            </div>
            <div id="filters-content-{{ $exportType }}">
            <form method="GET" id="{{ $exportType }}-filter-form" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                @if(isset($filterOptions['search']) && $filterOptions['search'] === true)
                    <div>
                        <label class="form-label" for="search">Search</label>
                        <input type="text"
                               name="search"
                               id="search"
                               value="{{ $filters['search'] ?? '' }}"
                               class="form-input w-full"
                               placeholder="{{ $filterOptions['searchPlaceholder'] ?? 'Search...' }}">
                    </div>
                @endif
                
                @if(isset($filterOptions['statusOptions']))
                    <div>
                        <label class="form-label" for="status">Status</label>
                        <select name="status" id="status" class="form-select w-full">
                            <option value="">All statuses</option>
                            @foreach($filterOptions['statusOptions'] as $status)
                                <option value="{{ $status }}" {{ ($filters['status'] ?? '') === $status ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(isset($filterOptions['methodOptions']))
                    @php
                        $methodFieldName = $filterOptions['methodFieldName'] ?? 'method';
                        $methodLabel = $filterOptions['methodLabel'] ?? 'Payment Method';
                    @endphp
                    <div>
                        <label class="form-label" for="{{ $methodFieldName }}">{{ $methodLabel }}</label>
                        <select name="{{ $methodFieldName }}" id="{{ $methodFieldName }}" class="form-select w-full">
                            <option value="">All methods</option>
                            @foreach($filterOptions['methodOptions'] as $method)
                                <option value="{{ $method }}" {{ ($filters[$methodFieldName] ?? '') === $method ? 'selected' : '' }}>
                                    {{ ucwords(str_replace('_', ' ', $method)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(isset($filterOptions['categoryOptions']))
                    <div>
                        <label class="form-label" for="category">Category</label>
                        <select name="category" id="category" class="form-select w-full">
                            <option value="">All categories</option>
                            @foreach($filterOptions['categoryOptions'] as $category)
                                <option value="{{ $category }}" {{ ($filters['category'] ?? '') === $category ? 'selected' : '' }}>
                                    {{ $category }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(isset($filterOptions['gatewayOptions']))
                    <div>
                        <label class="form-label" for="gateway">Payment Gateway</label>
                        <select name="gateway" id="gateway" class="form-select w-full">
                            <option value="">All gateways</option>
                            @foreach($filterOptions['gatewayOptions'] as $gateway)
                                <option value="{{ $gateway }}" {{ ($filters['gateway'] ?? '') === $gateway ? 'selected' : '' }}>
                                    {{ ucfirst($gateway) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(isset($filterOptions['vendor']))
                    <div>
                        <label class="form-label" for="vendor">Vendor</label>
                        <input type="text"
                               name="vendor"
                               id="vendor"
                               value="{{ $filters['vendor'] ?? '' }}"
                               class="form-input w-full"
                               placeholder="Vendor name">
                    </div>
                @endif

                @if(isset($filterOptions['source']))
                    <div>
                        <label class="form-label" for="source">Source</label>
                        <input type="text"
                               name="source"
                               id="source"
                               value="{{ $filters['source'] ?? '' }}"
                               class="form-input w-full"
                               placeholder="Source name">
                    </div>
                @endif

                <div>
                    <label class="form-label" for="date_from">Date From</label>
                    <input type="date"
                           name="date_from"
                           id="date_from"
                           value="{{ $filters['date_from'] ?? '' }}"
                           class="form-input w-full">
                </div>

                <div>
                    <label class="form-label" for="date_to">Date To</label>
                    <input type="date"
                           name="date_to"
                           id="date_to"
                           value="{{ $filters['date_to'] ?? '' }}"
                           class="form-input w-full">
                </div>

                <div class="md:col-span-2 xl:col-span-4 flex gap-2 items-end">
                    <button type="submit" class="btn btn-primary">
                        Apply Filters
                    </button>
                    <a href="{{ route($indexRoute) }}" class="btn btn-secondary">
                        Clear
                    </a>
                </div>
            </form>
            </div>
        </div>
    @endif

    {{-- DataTable Section --}}
    @if(isset($dataTable))
        <div class="admin-card">
            <div class="admin-table-wrapper">
                {!! $dataTable->html()->table(['class' => 'admin-table', 'id' => $dataTable->getTableIdPublic()]) !!}
            </div>
        </div>
    @endif
</div>

@push('scripts')
    @if(isset($dataTable))
        {!! $dataTable->scripts() !!}
    @endif

    @if(isset($filters) && isset($filterOptions))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Initialize filter toggle
                function initFilterToggle() {
                    const exportType = '{{ $exportType }}';
                    const localStorageKey = exportType + '-filters-collapsed';
                    const toggleBtn = document.getElementById('filters-toggle-btn-' + exportType);
                    const toggleIcon = document.getElementById('filters-toggle-icon-' + exportType);
                    const toggleText = document.getElementById('filters-toggle-text-' + exportType);
                    const filtersContent = document.getElementById('filters-content-' + exportType);
                    
                    if (!toggleBtn || !filtersContent) return;
                    
                    const isCollapsed = localStorage.getItem(localStorageKey) === 'true';
                    
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
                    
                    toggleFilters(isCollapsed);
                    
                    toggleBtn.addEventListener('click', function() {
                        const isCurrentlyCollapsed = filtersContent.style.display === 'none';
                        toggleFilters(!isCurrentlyCollapsed);
                    });
                }
                
                initFilterToggle();
            });
        </script>
    @endif

    @if($showExportButton && $exportType)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const exportBtn = document.getElementById('export-btn');
                const exportStatus = document.getElementById('export-status');
                const exportStatusMessage = document.getElementById('export-status-message');
                const filterForm = document.getElementById('{{ $exportType }}-filter-form');
                const tableId = '{{ $dataTable->getTableIdPublic() ?? '' }}';

                if (exportBtn) {
                    exportBtn.addEventListener('click', function () {
                        const exportType = this.dataset.exportType;
                        const exportRoute = this.dataset.exportRoute;
                        
                        // Get current filter values from form
                        const filters = {};
                        if (filterForm) {
                            const formData = new FormData(filterForm);
                            for (let [key, value] of formData.entries()) {
                                if (value && value.toString().trim() !== '') {
                                    filters[key] = value;
                                }
                            }
                        }
                        
                        // Get DataTables search value if table exists
                        if (tableId && typeof window.$ !== 'undefined') {
                            try {
                                const table = window.$('#' + tableId).DataTable();
                                if (table) {
                                    const searchValue = table.search();
                                    if (searchValue && searchValue.trim() !== '') {
                                        filters['datatable_search'] = searchValue;
                                    }
                                }
                            } catch (e) {
                                console.warn('Could not get DataTable search value:', e);
                            }
                        }

                        // Disable button and show status
                        exportBtn.disabled = true;
                        exportStatus.classList.remove('hidden');
                        exportStatusMessage.textContent = 'Preparing export... This may take a few moments for large datasets.';

                        // Make export request
                        fetch(exportRoute, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify({
                                filters: filters,
                                format: 'csv'
                            })
                        })
                        .then(async response => {
                            const rawBody = await response.text();
                            let data = {};
                            try {
                                data = rawBody ? JSON.parse(rawBody) : {};
                            } catch (parseError) {
                                throw new Error('Unexpected response. Please try again.');
                            }

                            if (!response.ok || !data.success) {
                                throw new Error(data.message || 'Export failed');
                            }

                            return data;
                        })
                        .then(data => {
                            exportStatusMessage.textContent = 'Export queued successfully! You will be notified when it\'s ready.';
                            exportStatus.classList.remove('alert-info');
                            exportStatus.classList.add('alert-success');
                            
                            // Check export status periodically
                            checkExportStatus(data.export_id);
                        })
                        .catch(error => {
                            exportStatusMessage.textContent = 'Export failed: ' + error.message;
                            exportStatus.classList.remove('alert-info');
                            exportStatus.classList.add('alert-danger');
                        })
                        .finally(() => {
                            exportBtn.disabled = false;
                        });
                    });
                }

                function checkExportStatus(exportId) {
                    // Poll export status every 5 seconds
                    const interval = setInterval(() => {
                        fetch(`{{ route('admin.exports.status', ['export' => '__ID__']) }}`.replace('__ID__', exportId))
                            .then(async response => {
                                const body = await response.text();
                                let data = {};
                                try {
                                    data = body ? JSON.parse(body) : {};
                                } catch {
                                    throw new Error('Invalid status response');
                                }

                                if (!response.ok) {
                                    throw new Error(data.message || 'Export status check failed');
                                }

                                return data;
                            })
                            .then(data => {
                                if (data.success && data.status === 'completed') {
                                    clearInterval(interval);
                                    exportStatusMessage.innerHTML = 'Export completed! <a href="' + data.download_url + '" class="underline font-semibold">Download File</a>';
                                    exportStatus.classList.remove('alert-info', 'alert-danger');
                                    exportStatus.classList.add('alert-success');
                                } else if (data.success && data.status === 'failed') {
                                    clearInterval(interval);
                                    exportStatusMessage.textContent = 'Export failed: ' + (data.error || 'Unknown error');
                                    exportStatus.classList.remove('alert-info', 'alert-success');
                                    exportStatus.classList.add('alert-danger');
                                } else if (data.success && data.status === 'processing') {
                                    exportStatusMessage.textContent = 'Export in progress... Please wait.';
                                }
                            })
                            .catch(() => {
                                // Silently fail status checks
                            });
                    }, 5000);

                    // Stop checking after 5 minutes
                    setTimeout(() => clearInterval(interval), 300000);
                }

                // Initialize filter form if exists
                if (filterForm && typeof window.$ !== 'undefined' && tableId) {
                    function waitForDataTableAndInit() {
                        const $table = window.$('#' + tableId);
                        
                        if ($table.length && window.$.fn.DataTable.isDataTable('#' + tableId)) {
                            const table = $table.DataTable();
                            
                            filterForm.addEventListener('submit', function (event) {
                                event.preventDefault();
                                const formData = new FormData(this);
                                const params = new URLSearchParams(formData);
                                table.ajax.url('{{ route($indexRoute) }}?' + params.toString()).load();
                            });

                            // Auto-submit on filter change for selects and dates
                            const filterSelects = filterForm.querySelectorAll('select, input[type="date"]');
                            filterSelects.forEach(input => {
                                input.addEventListener('change', () => {
                                    filterForm.dispatchEvent(new Event('submit'));
                                });
                            });
                            
                            // Auto-submit on input for text fields (with debounce)
                            const filterTextInputs = filterForm.querySelectorAll('input[type="text"]');
                            let searchTimeout;
                            filterTextInputs.forEach(input => {
                                input.addEventListener('input', () => {
                                    clearTimeout(searchTimeout);
                                    searchTimeout = setTimeout(() => {
                                        filterForm.dispatchEvent(new Event('submit'));
                                    }, 500);
                                });
                            });
                        } else {
                            setTimeout(waitForDataTableAndInit, 100);
                        }
                    }
                    
                    waitForDataTableAndInit();
                }
            });
        </script>
    @endif
@endpush

