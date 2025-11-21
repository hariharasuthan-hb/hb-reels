{{--
 | Expenses Index View
 |
 | Displays a list of all expense records with filtering capabilities.
 | Expenses represent outgoing financial transactions for the gym business.
 |
 | @var \App\DataTables\ExpenseDataTable $dataTable
 | @var array $filters - Current filter values (category, vendor, date_from, date_to, search)
 | @var array $categoryOptions - Available expense category options
 | @var array $methodOptions - Available payment method options
 |
 | Features:
 | - Filter by category, vendor, and date range
 | - Create new expense button (if user has permission)
 | - DataTable with server-side processing
 | - Auto-reload on filter changes
--}}
@extends('admin.layouts.app')

@section('page-title', 'Expenses')

@section('content')
<div class="space-y-6">
    @include('admin.components.report-section', [
        'title' => 'Expenses',
        'description' => 'Record and review operational spending.',
        'exportType' => 'expenses',
        'filters' => $filters,
        'filterOptions' => [
            'categoryOptions' => $categoryOptions,
            'vendor' => true,
        ],
        'dataTable' => $dataTable,
        'indexRoute' => 'admin.expenses.index',
        'showExportButton' => true,
        'headerActions' => [
            [
                'label' => 'Add Expense',
                'url' => route('admin.expenses.create'),
                'class' => 'btn btn-primary flex items-center gap-2',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>',
                'can' => 'create expenses',
            ],
        ],
    ])
</div>
@endsection

