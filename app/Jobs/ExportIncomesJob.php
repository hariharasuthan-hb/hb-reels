<?php

namespace App\Jobs;

use App\Models\Income;
use Illuminate\Support\Collection;

class ExportIncomesJob extends BaseExportJob
{
    /**
     * Get the data to export.
     */
    protected function getData(): Collection
    {
        $query = Income::query();

        // Note: Incomes are not trainer-specific, they are business income
        // No trainer filtering needed for incomes

        // Apply filters
        if (!empty($this->filters['category'])) {
            $query->where('category', $this->filters['category']);
        }

        if (!empty($this->filters['source'])) {
            $query->where('source', 'like', "%{$this->filters['source']}%");
        }

        if (!empty($this->filters['date_from'])) {
            $query->whereDate('received_at', '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate('received_at', '<=', $this->filters['date_to']);
        }

        // Handle search filters (both filter form search and DataTables search)
        $searchValue = $this->filters['datatable_search'] ?? $this->filters['search'] ?? null;
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('category', 'like', "%{$searchValue}%")
                    ->orWhere('source', 'like', "%{$searchValue}%")
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
            'Source',
            'Amount',
            'Received At',
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
            $row->source ?? '—',
            number_format((float) ($row->amount ?? 0), 2),
            $row->received_at ? $row->received_at->format('M d, Y') : '—',
            $row->readable_payment_method,
            $row->reference ?? '—',
            $row->notes ?? '—',
            $row->created_at ? $row->created_at->format('M d, Y h:i A') : '—',
        ];
    }
}

