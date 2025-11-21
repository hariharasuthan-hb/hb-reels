{{--
 | Subscriptions Index View
 |
 | Displays a list of all user subscriptions with filtering capabilities.
 | Subscriptions represent active membership plans for gym members.
 |
 | @var \App\DataTables\SubscriptionDataTable $dataTable
 | @var array $filters - Current filter values (status, gateway, search)
 | @var array $statusOptions - Available subscription status options
 | @var array $gatewayOptions - Available payment gateway options
 |
 | Features:
 | - Filter by status, payment gateway, and search
 | - DataTable with server-side processing
 | - Auto-reload on filter changes
 | - View, edit, and cancel subscription actions
--}}
@extends('admin.layouts.app')

@section('page-title', 'Subscriptions Management')

@section('content')
@include('admin.components.report-section', [
    'title' => 'Subscriptions',
    'description' => 'Manage all user subscriptions with advanced filtering.',
    'exportType' => 'subscriptions',
    'filters' => $filters,
    'filterOptions' => [
        'search' => true,
        'searchPlaceholder' => 'User name, email, or plan...',
        'statusOptions' => $statusOptions,
        'gatewayOptions' => $gatewayOptions,
    ],
    'dataTable' => $dataTable,
    'indexRoute' => 'admin.subscriptions.index',
    'showExportButton' => true,
])
@endsection
