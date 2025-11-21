<?php

namespace App\DataTables;

use App\Models\ActivityLog;
use Yajra\DataTables\Html\Column;

class ActivityLogDataTable extends BaseDataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable($query)
    {
        $dataTable = datatables()
            ->eloquent($query);
        
        // Automatically format date columns
        $this->autoFormatDates($dataTable);
        
        return $dataTable
            ->addColumn('member_name', function ($log) {
                return $log->user->name ?? '-';
            })
            ->addColumn('check_in_time_formatted', function ($log) {
                if (!$log->check_in_time) {
                    return '-';
                }
                // Handle both time and datetime formats
                if ($log->check_in_time instanceof \Carbon\Carbon) {
                    return $log->check_in_time->format('H:i');
                }
                return $log->check_in_time;
            })
            ->addColumn('check_out_time_formatted', function ($log) {
                if (!$log->check_out_time) {
                    return '-';
                }
                // Handle both time and datetime formats
                if ($log->check_out_time instanceof \Carbon\Carbon) {
                    return $log->check_out_time->format('H:i');
                }
                return $log->check_out_time;
            })
            ->addColumn('duration_formatted', function ($log) {
                return $log->duration_minutes ? $log->duration_minutes . ' min' : '-';
            })
            ->addColumn('calories_formatted', function ($log) {
                return $log->calories_burned ? number_format($log->calories_burned, 0) . ' cal' : '-';
            })
            ->addColumn('check_in_method_badge', function ($log) {
                if (!$log->check_in_method) {
                    return '-';
                }
                $methodColors = [
                    'qr_code' => 'bg-blue-100 text-blue-800',
                    'rfid' => 'bg-purple-100 text-purple-800',
                    'manual' => 'bg-orange-100 text-orange-800',
                ];
                $color = $methodColors[$log->check_in_method] ?? 'bg-gray-100 text-gray-800';
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $color . '">' . ucfirst(str_replace('_', ' ', $log->check_in_method)) . '</span>';
            })
            ->addColumn('actions', function ($log) {
                return view('admin.activities.partials.actions', [
                    'log' => $log,
                    'canReviewVideos' => auth()->user()->hasAnyRole(['admin', 'trainer'])
                ])->render();
            })
            ->rawColumns(['check_in_method_badge', 'actions']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(ActivityLog $model)
    {
        $query = $model->newQuery()->with(['user']);
        
        // If user is a trainer, filter by their members' activities
        if (auth()->user()->hasRole('trainer')) {
            $query->forTrainerMembers(auth()->id());
        }

        if ($method = request('check_in_method')) {
            $query->where('check_in_method', $method);
        }

        if ($dateFrom = request('date_from')) {
            $query->whereDate('date', '>=', $dateFrom);
        }

        if ($dateTo = request('date_to')) {
            $query->whereDate('date', '<=', $dateTo);
        }

        $customSearch = request()->input('search');
        if (!empty($customSearch) && !is_array($customSearch) && trim($customSearch) !== '') {
            $searchValue = trim($customSearch);
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('user', function ($userQuery) use ($searchValue) {
                    $userQuery->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%");
                })
                ->orWhere('workout_summary', 'like', "%{$searchValue}%");
            });
        }

        $datatableSearch = request()->input('search.value');
        if (!empty($datatableSearch) && trim($datatableSearch) !== '') {
            $searchValue = trim($datatableSearch);
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhere('check_in_method', 'like', "%{$searchValue}%")
                    ->orWhere('duration_minutes', 'like', "%{$searchValue}%")
                    ->orWhereHas('user', function ($userQuery) use ($searchValue) {
                        $userQuery->where('name', 'like', "%{$searchValue}%")
                            ->orWhere('email', 'like', "%{$searchValue}%");
                    });
            });
        }
        
        return $query;
    }

    protected function getFilterFormId(): string
    {
        return 'activity_logs-filter-form';
    }

    /**
     * Get table ID
     */
    protected function getTableId(): string
    {
        return 'activity-logs-table';
    }

    /**
     * Get columns definition
     */
    protected function getColumns(): array
    {
        return [
            Column::make('id')->title('ID')->width('5%'),
            Column::make('member_name')->title('Member')->width('15%')->orderable(false)->searchable(false),
            Column::make('date')->title('Date')->width('12%'),
            Column::make('check_in_time_formatted')->title('Check In')->width('10%')->orderable(false)->searchable(false),
            Column::make('check_out_time_formatted')->title('Check Out')->width('10%')->orderable(false)->searchable(false),
            Column::make('duration_formatted')->title('Duration')->width('10%')->orderable(false)->searchable(false),
            Column::make('calories_formatted')->title('Calories')->width('10%')->orderable(false)->searchable(false),
            Column::make('check_in_method_badge')->title('Method')->width('12%')->orderable(false)->searchable(false),
            Column::make('created_at')->title('Created At')->width('16%'),
            Column::computed('actions')
                ->title('Actions')
                ->orderable(false)
                ->searchable(false)
                ->exportable(false)
                ->printable(false)
                ->width('12%'),
        ];
    }
}

