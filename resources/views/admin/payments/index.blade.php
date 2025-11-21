{{--
 | Payments Index View
 |
 | Displays a list of all payment transactions with filtering capabilities.
 | Payments represent completed transactions from subscription purchases.
 |
 | @var \App\DataTables\PaymentDataTable $dataTable
 | @var array $filters - Current filter values (status, method, search, date_from, date_to)
 | @var array $statusOptions - Available payment status options
 | @var array $methodOptions - Available payment method options
 |
 | Features:
 | - Filter by status, payment method, and date range
 | - DataTable with server-side processing
 | - Auto-reload on filter changes
--}}
@extends('admin.layouts.app')

@section('page-title', 'Payments')

@section('content')
@include('admin.components.report-section', [
    'title' => 'Payments',
    'description' => 'Review all payment transactions with quick filtering.',
    'exportType' => 'payments',
    'filters' => $filters,
    'filterOptions' => [
        'statusOptions' => $statusOptions,
        'methodOptions' => $methodOptions,
    ],
    'dataTable' => $dataTable,
    'indexRoute' => 'admin.payments.index',
    'showExportButton' => true,
])
@endsection

