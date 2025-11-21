{{--
 | Incomes Index View
 |
 | Displays a list of all income records with filtering capabilities.
 | Income represents incoming financial transactions for the gym business.
 |
 | @var \App\DataTables\IncomeDataTable $dataTable
 | @var array $filters - Current filter values (category, source, date_from, date_to, search)
 | @var array $categoryOptions - Available income category options
 | @var array $methodOptions - Available payment method options
 |
 | Features:
 | - Filter by category, source, and date range
 | - Create new income button (if user has permission)
 | - DataTable with server-side processing
 | - Auto-reload on filter changes
--}}
@extends('admin.layouts.app')

@section('page-title', 'Incomes')

@section('content')
<div class="space-y-6">
    @include('admin.components.report-section', [
        'title' => 'Incomes',
        'description' => 'Record and review non-subscription income.',
        'exportType' => 'incomes',
        'filters' => $filters,
        'filterOptions' => [
            'categoryOptions' => $categoryOptions,
            'source' => true,
        ],
        'dataTable' => $dataTable,
        'indexRoute' => 'admin.incomes.index',
        'showExportButton' => true,
        'headerActions' => [
            [
                'label' => 'Add Income',
                'url' => route('admin.incomes.create'),
                'class' => 'btn btn-primary flex items-center gap-2',
                'icon' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>',
                'can' => 'create incomes',
            ],
        ],
    ])
</div>
@endsection

