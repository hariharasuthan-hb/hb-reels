{{--
 | Activity Logs Index View
 |
 | Displays a list of all activity logs (member check-ins) with filtering capabilities.
 | Activity logs record when members visit the gym and check in.
 | For trainers: shows only their assigned members' activities.
 |
 | @var \App\DataTables\ActivityLogDataTable $dataTable
 |
 | Features:
 | - DataTable with server-side processing
 | - Role-based filtering (trainers see only their members)
 | - View activity details
--}}
@extends('admin.layouts.app')

@section('page-title', 'Attendance & Activity')

@section('content')
@php
    $activityDescription = auth()->user()->hasRole('trainer')
        ? 'Monitor check-ins and workout activities of your assigned members.'
        : 'Monitor all member check-ins and workout activities across the gym.';
@endphp

@include('admin.components.report-section', [
    'title' => 'Attendance & Activity',
    'description' => $activityDescription,
    'categoryLabel' => 'Attendance',
    'exportType' => \App\Models\Export::TYPE_ACTIVITY_LOGS,
    'filters' => $filters ?? [],
    'filterOptions' => $filterOptions ?? [],
    'dataTable' => $dataTable,
    'indexRoute' => 'admin.activities.index',
])
@endsection

