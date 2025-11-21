<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Illuminate\Support\Collection;

class ExportActivityLogsJob extends BaseExportJob
{
    /**
     * Get the data to export.
     */
    protected function getData(): Collection
    {
        $query = ActivityLog::query()->with(['user']);

        // Apply filters
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('date', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('date', '<=', $this->filters['date_to']);
        }

        if (!empty($this->filters['check_in_method'])) {
            $query->where('check_in_method', $this->filters['check_in_method']);
        }

        // Handle search filters (both filter form search and DataTables search)
        $searchValue = $this->filters['datatable_search'] ?? $this->filters['search'] ?? null;
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('user', function ($userQuery) use ($searchValue) {
                    $userQuery->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%");
                });
            });
        }

        // Process in chunks to handle large datasets
        $allData = collect();
        $query->chunk(1000, function ($chunk) use (&$allData) {
            $allData = $allData->merge($chunk);
        });

        return $allData;
    }

    /**
     * Get the column headers for the export.
     */
    protected function getHeaders(): array
    {
        return [
            'ID',
            'User Name',
            'User Email',
            'Date',
            'Check-in Time',
            'Check-in Method',
            'Created At',
        ];
    }

    /**
     * Format a single row for export.
     */
    protected function formatRow($row): array
    {
        return [
            $row->id,
            $row->user->name ?? '—',
            $row->user->email ?? '—',
            $row->date ? $row->date->format('M d, Y') : '—',
            $row->check_in_time ? $row->check_in_time->format('h:i A') : '—',
            $row->check_in_method ? ucwords(str_replace('_', ' ', $row->check_in_method)) : '—',
            $row->created_at ? $row->created_at->format('M d, Y h:i A') : '—',
        ];
    }
}

