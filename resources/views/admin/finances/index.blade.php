{{--
 | Finances Overview Index View
 |
 | Displays financial overview with monthly breakdowns of income, expenses, and net profit.
 | Provides financial analytics with configurable time ranges (3, 6, or 12 months).
 |
 | @var int $range - Selected time range in months (3, 6, or 12)
 | @var array $rangeOptions - Available range options [3, 6, 12]
 | @var array $monthlyOverview - Monthly financial data
 | @var array $currentMonth - Current month's financial summary
 | @var array $trailingTotals - Totals for the selected range
 | @var \App\DataTables\MonthlyBreakdownDataTable $monthlyDataTable
 |
 | Features:
 | - Monthly revenue, expenses, and margin breakdown
 | - Configurable time range selector
 | - DataTable with monthly breakdown
 | - Quick access to create expenses
--}}
@extends('admin.layouts.app')

@section('page-title', 'Finances Overview')

@section('content')
<div class="space-y-6">
    {{-- Page Heading --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <p class="text-sm text-gray-500 uppercase tracking-wide">Financial Health</p>
            <h1 class="text-2xl font-bold text-gray-900">Finances Overview</h1>
            <p class="text-sm text-gray-500 mt-1">
                Track monthly expenses, revenue, and profitability at a glance.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <form method="GET" class="flex items-center gap-3">
                <label for="range" class="text-sm font-medium text-gray-600">Range</label>
                <select id="range" name="range"
                        class="form-select min-w-[140px]"
                        onchange="this.form.submit()">
                    @foreach($rangeOptions as $option)
                        <option value="{{ $option }}" {{ (int)$range === $option ? 'selected' : '' }}>
                            Last {{ $option }} months
                        </option>
                    @endforeach
                </select>
            </form>
            <div class="flex flex-wrap gap-3">
                <button type="button" 
                        id="finance-export-btn" 
                        class="btn btn-secondary flex items-center gap-2"
                        data-export-route="{{ route('admin.exports.export', ['type' => \App\Models\Export::TYPE_FINANCES]) }}"
                        data-export-type="{{ \App\Models\Export::TYPE_FINANCES }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Data
                </button>
            @can('create expenses')
                    <a href="{{ route('admin.expenses.create') }}" class="btn btn-primary flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Expense
                </a>
            @endcan
        </div>
        </div>
    </div>

    {{-- Export Status Notification --}}
    <div id="finance-export-status" class="hidden alert alert-info">
        <svg class="w-5 h-5 flex-shrink-0 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span id="finance-export-status-message">Preparing export...</span>
    </div>

    {{-- KPI Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
        <div class="admin-card">
            <p class="text-sm text-gray-500 mb-1">Current Month Revenue</p>
            <p class="text-3xl font-bold text-gray-900">${{ number_format($currentMonth['revenue'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-2">{{ $currentMonth['label'] }}</p>
        </div>
        <div class="admin-card">
            <p class="text-sm text-gray-500 mb-1">Current Month Expenses</p>
            <p class="text-3xl font-bold text-gray-900">${{ number_format($currentMonth['expenses'], 2) }}</p>
            <p class="text-xs text-gray-500 mt-2">{{ $currentMonth['label'] }}</p>
        </div>
        <div class="admin-card">
            <p class="text-sm text-gray-500 mb-1">Current Month Net Profit</p>
            <p class="text-3xl font-bold {{ $currentMonth['net_profit'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
                ${{ number_format($currentMonth['net_profit'], 2) }}
            </p>
            <p class="text-xs text-gray-500 mt-2">{{ $currentMonth['label'] }}</p>
        </div>
        <div class="admin-card">
            <p class="text-sm text-gray-500 mb-1">Profit Margin</p>
            @if(!is_null($currentMonth['margin']))
                <p class="text-3xl font-bold text-gray-900">{{ number_format($currentMonth['margin'], 2) }}%</p>
            @else
                <p class="text-3xl font-bold text-gray-900">—</p>
            @endif
            <p class="text-xs text-gray-500 mt-2">{{ $currentMonth['label'] }}</p>
        </div>
    </div>

    {{-- Trailing Totals --}}
    <div class="admin-card">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Trailing {{ $range }} Month Totals</h2>
                <p class="text-sm text-gray-500">Aggregated performance for the selected period.</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 rounded-xl bg-blue-50 border border-blue-100">
                <p class="text-sm text-blue-600 mb-1">Revenue</p>
                <p class="text-2xl font-bold text-blue-900">${{ number_format($trailingTotals['revenue'], 2) }}</p>
            </div>
            <div class="p-4 rounded-xl bg-rose-50 border border-rose-100">
                <p class="text-sm text-rose-600 mb-1">Expenses</p>
                <p class="text-2xl font-bold text-rose-900">${{ number_format($trailingTotals['expenses'], 2) }}</p>
            </div>
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-100">
                <p class="text-sm text-emerald-600 mb-1">Net Profit</p>
                <p class="text-2xl font-bold text-emerald-900">${{ number_format($trailingTotals['net_profit'], 2) }}</p>
                <p class="text-xs text-emerald-600 mt-1">
                    Margin: {{ $trailingTotals['margin'] !== null ? number_format($trailingTotals['margin'], 2) . '%' : '—' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Monthly Breakdown --}}
    <div class="admin-card">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Monthly Breakdown</h2>
                <p class="text-sm text-gray-500">Detailed view of revenue, expenses, and profit by month.</p>
            </div>
        </div>
        <div class="admin-table-wrapper">
            {!! $monthlyDataTable->html()->table(['class' => 'admin-table', 'id' => $monthlyDataTable->getTableIdPublic()]) !!}
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {!! $monthlyDataTable->scripts() !!}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const exportBtn = document.getElementById('finance-export-btn');
            const exportStatus = document.getElementById('finance-export-status');
            const exportStatusMessage = document.getElementById('finance-export-status-message');
            const rangeInput = document.getElementById('range');
            const tableId = '{{ $monthlyDataTable->getTableIdPublic() }}';

            if (!exportBtn) {
                return;
            }

            exportBtn.addEventListener('click', function () {
                const exportRoute = this.dataset.exportRoute;

                const filters = {};
                if (rangeInput && rangeInput.value) {
                    filters.range = rangeInput.value;
                }

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

                exportBtn.disabled = true;
                exportStatus.classList.remove('hidden', 'alert-danger', 'alert-success');
                exportStatus.classList.add('alert-info');
                exportStatusMessage.textContent = 'Preparing export... This may take a few moments for large datasets.';

                fetch(exportRoute, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        filters: filters,
                        format: 'csv'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        exportStatusMessage.textContent = 'Export queued successfully! You will be notified when it\'s ready.';
                        exportStatus.classList.remove('alert-info');
                        exportStatus.classList.add('alert-success');
                        checkFinanceExportStatus(data.export_id);
                    } else {
                        throw new Error(data.message || 'Export failed');
                    }
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

            function checkFinanceExportStatus(exportId) {
                const interval = setInterval(() => {
                    fetch(`{{ route('admin.exports.status', ['export' => '__ID__']) }}`.replace('__ID__', exportId))
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.status === 'completed') {
                                clearInterval(interval);
                                exportStatusMessage.innerHTML = 'Export completed! <a href=\"' + data.download_url + '\" class=\"underline font-semibold\">Download File</a>';
                                exportStatus.classList.remove('alert-info', 'alert-danger');
                                exportStatus.classList.add('alert-success');
                            } else if (data.success && data.status === 'failed') {
                                clearInterval(interval);
                                exportStatusMessage.textContent = 'Export failed: ' + (data.error || 'Unknown error');
                                exportStatus.classList.remove('alert-info', 'alert-success');
                                exportStatus.classList.add('alert-danger');
                            }
                        })
                        .catch(error => {
                            clearInterval(interval);
                            exportStatusMessage.textContent = 'Export status check failed: ' + error.message;
                            exportStatus.classList.remove('alert-info', 'alert-success');
                            exportStatus.classList.add('alert-danger');
                        });
                }, 5000);
            }
        });
    </script>
@endpush

