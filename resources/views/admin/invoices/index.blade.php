{{--
 | Invoices Index View
 |
 | Displays a list of all invoices with filtering capabilities.
 | Invoices are backed by payment records and represent billing documents.
 |
 | @var \App\DataTables\InvoiceDataTable $dataTable
 | @var array $filters - Current filter values (status, method, date_from, date_to, search)
 | @var array $statusOptions - Available payment status options
 | @var array $methodOptions - Available payment method options
 |
 | Features:
 | - Filter by status, payment method, and date range
 | - DataTable with server-side processing
 | - Auto-reload on filter changes
--}}
@extends('admin.layouts.app')

@section('page-title', 'Invoices')

@section('content')
@include('admin.components.report-section', [
    'title' => 'Invoices',
    'description' => 'Review generated invoices tied to subscription payments.',
    'exportType' => 'invoices',
    'filters' => $filters,
    'filterOptions' => [
        'statusOptions' => $statusOptions,
        'methodOptions' => $methodOptions,
    ],
    'dataTable' => $dataTable,
    'indexRoute' => 'admin.invoices.index',
    'showExportButton' => true,
])
@endsection

