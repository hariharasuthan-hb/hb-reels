<?php

namespace App\Repositories\Eloquent;

use App\Models\Payment;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PaymentRepository extends BaseRepository implements PaymentRepositoryInterface
{
    public function __construct(Payment $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritdoc}
     */
    public function getCompletedBetweenDates(Carbon $startDate, Carbon $endDate): Collection
    {
        $dateColumn = Payment::getDateColumn();
        $query = $this->model->newQuery();
        
        // Only filter by status if the column exists (accounting system may not have it)
        if (Payment::hasStatusColumn()) {
            $query->where('status', 'completed');
        }
        
        return $query
            ->whereBetween($dateColumn, [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->orderBy($dateColumn)
            ->get(['id', 'amount', 'final_amount', 'discount_amount', $dateColumn])
            ->map(function ($payment) use ($dateColumn) {
                // Ensure we have a final_amount value (use amount as fallback)
                if (!isset($payment->final_amount) || $payment->final_amount === null) {
                    $payment->final_amount = $payment->amount ?? 0;
                }
                // Map the date column to paid_at for consistency
                if ($dateColumn !== 'paid_at' && isset($payment->{$dateColumn})) {
                    $payment->paid_at = $payment->{$dateColumn};
                }
                return $payment;
            });
    }

    /**
     * {@inheritdoc}
     */
    public function getDistinctMethods(): Collection
    {
        // If payment_method column doesn't exist, return empty collection
        if (!Payment::hasPaymentMethodColumn()) {
            return collect();
        }
        
        return $this->model
            ->newQuery()
            ->select('payment_method')
            ->whereNotNull('payment_method')
            ->distinct()
            ->orderBy('payment_method')
            ->pluck('payment_method')
            ->filter()
            ->values();
    }

    /**
     * Apply filters for status and date range.
     */
    protected function applyFilters($query, array $filters)
    {
        // Only filter by status if the column exists and filter is provided
        if (!empty($filters['status']) && Payment::hasStatusColumn()) {
            $query->where('status', $filters['status']);
        }

        // Only filter by payment_method if the column exists and filter is provided
        if (!empty($filters['method']) && Payment::hasPaymentMethodColumn()) {
            $query->where('payment_method', $filters['method']);
        }

        $dateColumn = Payment::getDateColumn();
        if (!empty($filters['date_from'])) {
            $query->whereDate($dateColumn, '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate($dateColumn, '<=', $filters['date_to']);
        }

        return $query;
    }

    /**
     * Enable searching by transaction id or user info.
     */
    protected function applySearch($query, mixed $search)
    {
        if (is_array($search)) {
            $search = $search['value'] ?? null;
        }

        if (!is_string($search) || trim($search) === '') {
            return $query;
        }

        $search = trim($search);

        return $query->where(function ($q) use ($search) {
            $q->where('transaction_id', 'like', "%{$search}%")
                ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
        });
    }
}
