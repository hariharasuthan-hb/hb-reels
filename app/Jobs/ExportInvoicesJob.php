<?php

namespace App\Jobs;

use App\Models\Payment;
use Illuminate\Support\Collection;

class ExportInvoicesJob extends BaseExportJob
{
    /**
     * Get the data to export.
     */
    protected function getData(): Collection
    {
        $dateColumn = Payment::getDateColumn();
        $query = Payment::query()
            ->with(['user', 'subscription.subscriptionPlan'])
            ->whereNotNull($dateColumn);

        // Apply filters
        // Only filter by status if the column exists (accounting system may not have it)
        if (!empty($this->filters['status']) && Payment::hasStatusColumn()) {
            $query->where('status', $this->filters['status']);
        }

        // Only filter by payment_method if the column exists (accounting system may not have it)
        if (!empty($this->filters['method']) && Payment::hasPaymentMethodColumn()) {
            $query->where('payment_method', $this->filters['method']);
        }

        $dateColumn = Payment::getDateColumn();
        if (!empty($this->filters['date_from'])) {
            $query->whereDate($dateColumn, '>=', $this->filters['date_from']);
        }

        if (!empty($this->filters['date_to'])) {
            $query->whereDate($dateColumn, '<=', $this->filters['date_to']);
        }

        // Handle search filters (both filter form search and DataTables search)
        $searchValue = $this->filters['datatable_search'] ?? $this->filters['search'] ?? null;
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('transaction_id', 'like', "%{$searchValue}%")
                    ->orWhere('id', 'like', "%{$searchValue}%")
                    ->orWhereHas('user', function ($userQuery) use ($searchValue) {
                        $userQuery->where('name', 'like', "%{$searchValue}%")
                            ->orWhere('email', 'like', "%{$searchValue}%");
                    });
            });
        }

        // Process in chunks to handle large datasets
        $allData = collect();
        $dateColumn = Payment::getDateColumn();
        $query->chunk(1000, function ($chunk) use (&$allData, $dateColumn) {
            // Map the date column to paid_at for consistency
            $chunk->each(function ($payment) use ($dateColumn) {
                if ($dateColumn !== 'paid_at' && isset($payment->{$dateColumn})) {
                    $payment->paid_at = $payment->{$dateColumn};
                }
            });
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
            'Invoice #',
            'Transaction ID',
            'User Name',
            'User Email',
            'Plan Name',
            'Amount',
            'Discount Amount',
            'Final Amount',
            'Payment Method',
            'Status',
            'Issued At',
            'Created At',
        ];
    }

    /**
     * Format a single row for export.
     */
    protected function formatRow($row): array
    {
        return [
            'INV-' . str_pad($row->id, 5, '0', STR_PAD_LEFT),
            $row->transaction_id ?? '—',
            $row->user->name ?? '—',
            $row->user->email ?? '—',
            $row->subscription->subscriptionPlan->plan_name ?? '—',
            number_format((float) ($row->amount ?? 0), 2),
            number_format((float) ($row->discount_amount ?? 0), 2),
            number_format((float) ($row->final_amount ?? 0), 2),
            $row->readable_payment_method,
            ucfirst(str_replace('_', ' ', $row->status ?? 'unknown')),
            $row->paid_at ? $row->paid_at->format('M d, Y h:i A') : '—',
            $row->created_at ? $row->created_at->format('M d, Y h:i A') : '—',
        ];
    }
}

