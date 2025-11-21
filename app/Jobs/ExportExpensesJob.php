<?php

namespace App\Jobs;

use App\Models\Expense;
use Illuminate\Support\Collection;

class ExportExpensesJob extends BaseExportJob
{
    /**
     * Get the data to export.
     */
    protected function getData(): Collection
    {
        $query = Expense::query();

        // Note: Expenses are not trainer-specific, they are business expenses
        // No trainer filtering needed for expenses

        // Apply filters
        if (!empty($this->filters['category'])) {
            $query->where('category', $this->filters['category']);
        }

        if (!empty($this->filters['vendor'])) {
            $query->where('vendor', 'like', "%{$this->filters['vendor']}%");
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('spent_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('spent_at', '<=', $this->filters['date_to']);
        }

        // Handle search filters (both filter form search and DataTables search)
        $searchValue = $this->filters['datatable_search'] ?? $this->filters['search'] ?? null;
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('category', 'like', "%{$searchValue}%")
                    ->orWhere('vendor', 'like', "%{$searchValue}%")
                    ->orWhere('notes', 'like', "%{$searchValue}%");
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
            'Category',
            'Vendor',
            'Amount',
            'Spent At',
            'Payment Method',
            'Reference',
            'Notes',
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
            $row->category,
            $row->vendor ?? '—',
            number_format((float) ($row->amount ?? 0), 2),
            $row->spent_at ? $row->spent_at->format('M d, Y') : '—',
            $row->readable_payment_method,
            $row->reference ?? '—',
            $row->notes ?? '—',
            $row->created_at ? $row->created_at->format('M d, Y h:i A') : '—',
        ];
    }
}

