{{--
 | Filter Section Component
 |
 | Reusable filter section component for DataTable filtering.
 | Provides collapsible filter form with auto-reload functionality.
 |
 | @param string $formId - Form ID (default: 'filter-form')
 | @param string $clearRoute - Route for clearing filters
 | @param array $filters - Current filter values
 | @param array $fields - Filter field definitions (name, label, type, options, placeholder)
 | @param string $localStorageKey - Key for storing collapsed state in localStorage
 | @param string $tableId - DataTable ID for auto-reload
 | @param array $autoReloadSelectors - Field names that trigger auto-reload on change
 |
 | Field types supported: 'text', 'select', 'date'
--}}
@php
    $formId = $formId ?? 'filter-form';
    $clearRoute = $clearRoute ?? '#';
    $filters = $filters ?? [];
    $fields = $fields ?? [];
    $localStorageKey = $localStorageKey ?? ($formId . '-collapsed');
    $tableId = $tableId ?? null;
    $autoReloadSelectors = $autoReloadSelectors ?? [];
@endphp

{{-- Filters --}}
<div class="admin-card">
    <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Filters</h3>
        <button type="button" 
                id="filters-toggle-btn-{{ $formId }}" 
                class="flex items-center gap-2 text-sm text-gray-600 hover:text-gray-900 transition-colors">
            <span id="filters-toggle-text-{{ $formId }}">Hide Filters</span>
            <svg id="filters-toggle-icon-{{ $formId }}" class="w-5 h-5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
            </svg>
        </button>
    </div>
    <div id="filters-content-{{ $formId }}">
        <form method="GET" id="{{ $formId }}" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach($fields as $field)
                <div>
                    <label class="form-label" for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                    @if($field['type'] === 'select')
                        <select name="{{ $field['name'] }}" id="{{ $field['name'] }}" class="form-select w-full">
                            <option value="">{{ $field['placeholder'] ?? 'All ' . strtolower($field['label']) }}</option>
                            @foreach($field['options'] as $optionValue => $optionLabel)
                                @php
                                    // Handle both indexed and associative arrays
                                    if (is_numeric($optionValue)) {
                                        $actualValue = $optionLabel;
                                        $actualLabel = $optionLabel;
                                    } else {
                                        $actualValue = $optionValue;
                                        $actualLabel = $optionLabel;
                                    }
                                    $selected = ($filters[$field['name']] ?? '') == $actualValue;
                                @endphp
                                <option value="{{ $actualValue }}" {{ $selected ? 'selected' : '' }}>
                                    {{ $actualLabel }}
                                </option>
                            @endforeach
                        </select>
                    @elseif($field['type'] === 'text')
                        <input type="text"
                               name="{{ $field['name'] }}"
                               id="{{ $field['name'] }}"
                               value="{{ $filters[$field['name']] ?? '' }}"
                               class="form-input w-full"
                               placeholder="{{ $field['placeholder'] ?? '' }}">
                    @elseif($field['type'] === 'date')
                        <input type="date"
                               name="{{ $field['name'] }}"
                               id="{{ $field['name'] }}"
                               value="{{ $filters[$field['name']] ?? '' }}"
                               class="form-input w-full">
                    @endif
                </div>
            @endforeach
            <div class="md:col-span-2 xl:col-span-4 flex gap-2 items-end">
                <button type="submit" class="btn btn-primary">
                    Apply Filters
                </button>
                <a href="{{ $clearRoute }}" class="btn btn-secondary">
                    Clear
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const formId = '{{ $formId }}';
        
        // Prevent multiple initializations
        if (window['filterSectionInitialized_' + formId]) {
            return;
        }
        window['filterSectionInitialized_' + formId] = true;
        
        // Initialize filter toggle
        function initFilterToggle() {
            const formId = '{{ $formId }}';
            const localStorageKey = '{{ $localStorageKey }}';
            const toggleBtn = document.getElementById('filters-toggle-btn-' + formId);
            const toggleIcon = document.getElementById('filters-toggle-icon-' + formId);
            const toggleText = document.getElementById('filters-toggle-text-' + formId);
            const filtersContent = document.getElementById('filters-content-' + formId);
            
            if (!toggleBtn || !filtersContent) return;
            
            // Check localStorage for saved state (default: show filters)
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
            
            // Set initial state
            toggleFilters(isCollapsed);
            
            // Handle toggle button click
            toggleBtn.addEventListener('click', function() {
                const isCurrentlyCollapsed = filtersContent.style.display === 'none';
                toggleFilters(!isCurrentlyCollapsed);
            });
        }

        // Initialize filter form integration with DataTable
        function initFilterForm() {
            if (typeof window.$ === 'undefined') {
                return setTimeout(initFilterForm, 100);
            }

            const formId = '{{ $formId }}';
            const tableId = '{{ $tableId }}';
            const autoReloadSelectors = {!! json_encode($autoReloadSelectors) !!};
            
            if (!tableId) return;

            const $form = window.$('#' + formId);
            if (!$form.length) return;

            // Wait for DataTable to be initialized
            function waitForDataTable() {
                const $table = window.$('#' + tableId);
                
                // Check if DataTable is already initialized
                if ($table.length && window.$.fn.DataTable.isDataTable('#' + tableId)) {
                    const table = $table.DataTable();
                    
                    // Remove existing handler to prevent duplicates
                    $form.off('submit.filter-reload').on('submit.filter-reload', function (event) {
                        event.preventDefault();
                        // Reload table - filters will be automatically included via ajax.data
                        table.ajax.reload(null, false);
                    });

                    // Auto-reload on filter change
                    if (autoReloadSelectors && autoReloadSelectors.length > 0) {
                        // Handle text inputs with debounce
                        const textInputs = autoReloadSelectors.filter(s => {
                            const input = document.getElementById(s);
                            return input && input.type === 'text';
                        });
                        
                        if (textInputs.length > 0) {
                            let searchTimeout;
                            textInputs.forEach(inputId => {
                                window.$('#' + inputId).off('input.filter-reload').on('input.filter-reload', function () {
                                    clearTimeout(searchTimeout);
                                    searchTimeout = setTimeout(function() {
                                        table.ajax.reload(null, false);
                                    }, 500);
                                });
                            });
                        }
                        
                        // Immediate reload for selects and date inputs
                        const nonTextSelectors = autoReloadSelectors
                            .filter(s => {
                                const input = document.getElementById(s);
                                return !input || input.type !== 'text';
                            })
                            .map(s => '#' + s)
                            .join(', ');
                        
                        if (nonTextSelectors) {
                            window.$(nonTextSelectors).off('change.filter-reload').on('change.filter-reload', function () {
                                table.ajax.reload(null, false);
                            });
                        }
                    }
                } else {
                    // DataTable not ready yet, try again
                    setTimeout(waitForDataTable, 100);
                }
            }
            
            waitForDataTable();
        }

        initFilterToggle();
        initFilterForm();
    });
</script>
@endpush

