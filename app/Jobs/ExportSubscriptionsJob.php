<?php

namespace App\Jobs;

use App\Models\Subscription;
use Illuminate\Support\Collection;

class ExportSubscriptionsJob extends BaseExportJob
{
    /**
     * Get the data to export.
     */
    protected function getData(): Collection
    {
        $query = Subscription::query()->with(['user', 'subscriptionPlan']);

        // Apply filters
        if (!empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (!empty($this->filters['gateway'])) {
            $query->where('gateway', $this->filters['gateway']);
        }

        // Handle search filters (both filter form search and DataTables search)
        $searchValue = $this->filters['datatable_search'] ?? $this->filters['search'] ?? null;
        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('user', function ($userQuery) use ($searchValue) {
                    $userQuery->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%");
                })
                ->orWhereHas('subscriptionPlan', function ($planQuery) use ($searchValue) {
                    $planQuery->where('plan_name', 'like', "%{$searchValue}%");
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
            'Subscription ID',
            'User Name',
            'User Email',
            'Plan Name',
            'Status',
            'Gateway',
            'Trial End At',
            'Next Billing At',
            'Started At',
            'Canceled At',
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
            $row->subscriptionPlan->plan_name ?? '—',
            ucfirst(str_replace('_', ' ', $row->status ?? 'unknown')),
            ucfirst($row->gateway ?? '—'),
            $row->trial_end_at ? $row->trial_end_at->format('M d, Y h:i A') : '—',
            $row->next_billing_at ? $row->next_billing_at->format('M d, Y h:i A') : '—',
            $row->started_at ? $row->started_at->format('M d, Y h:i A') : '—',
            $row->canceled_at ? $row->canceled_at->format('M d, Y h:i A') : '—',
            $row->created_at ? $row->created_at->format('M d, Y h:i A') : '—',
        ];
    }
}

