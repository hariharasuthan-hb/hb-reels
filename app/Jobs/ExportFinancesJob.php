<?php

namespace App\Jobs;

use App\Services\FinancialReportService;
use Illuminate\Support\Collection;

class ExportFinancesJob extends BaseExportJob
{
    /**
     * Get the data to export.
     */
    protected function getData(): Collection
    {
        /** @var FinancialReportService $financialReportService */
        $financialReportService = app(FinancialReportService::class);

        $range = (int) ($this->filters['range'] ?? 6);

        return $financialReportService->getMonthlyOverview($range);
    }

    /**
     * Get the column headers for the export.
     */
    protected function getHeaders(): array
    {
        return [
            'Month',
            'Revenue',
            'Expenses',
            'Net Profit',
            'Margin (%)',
        ];
    }

    /**
     * Format a single row for export.
     */
    protected function formatRow($row): array
    {
        return [
            $row['label'] ?? '—',
            number_format((float) ($row['revenue'] ?? 0), 2),
            number_format((float) ($row['expenses'] ?? 0), 2),
            number_format((float) ($row['net_profit'] ?? 0), 2),
            isset($row['margin']) ? number_format((float) $row['margin'], 2) : '—',
        ];
    }
}


