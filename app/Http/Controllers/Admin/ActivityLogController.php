<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\ActivityLogDataTable;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\View\View;

/**
 * Controller for managing activity logs in the admin panel.
 * 
 * Handles viewing activity logs which record member check-ins and gym
 * attendance. Activity logs show when members visit the gym and can be
 * filtered by date and member. Accessible by both admin and trainer roles
 * with 'view activities' permission.
 */
class ActivityLogController extends Controller
{
    /**
     * Display a listing of activity logs.
     * Accessible by both admin and trainer (filtered by permission).
     */
    public function index(ActivityLogDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new ActivityLog()))->toJson();
        }

        $filters = request()->only(['search', 'check_in_method', 'date_from', 'date_to']);

        $methodOptions = ActivityLog::query()
            ->select('check_in_method')
            ->whereNotNull('check_in_method')
            ->distinct()
            ->orderBy('check_in_method')
            ->pluck('check_in_method')
            ->filter()
            ->values()
            ->all();

        return view('admin.activities.index', [
            'dataTable' => $dataTable,
            'filters' => $filters,
            'filterOptions' => [
                'search' => true,
                'searchPlaceholder' => 'Search by member name or email',
                'methodOptions' => $methodOptions,
                'methodLabel' => 'Check-in Method',
                'methodFieldName' => 'check_in_method',
            ],
        ]);
    }
}

