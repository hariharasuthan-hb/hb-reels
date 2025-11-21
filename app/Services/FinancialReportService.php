<?php

namespace App\Services;

use App\Repositories\Interfaces\ExpenseRepositoryInterface;
use App\Repositories\Interfaces\IncomeRepositoryInterface;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class FinancialReportService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly ExpenseRepositoryInterface $expenseRepository,
        private readonly IncomeRepositoryInterface $incomeRepository
    ) {
    }

    /**
     * Build a normalized monthly overview for the requested range.
     */
    public function getMonthlyOverview(int $months = 6): Collection
    {
        $months = $this->sanitizeRange($months);

        $rangeEnd = now()->endOfMonth();
        $rangeStart = (clone $rangeEnd)->subMonths($months - 1)->startOfMonth();

        $payments = $this->paymentRepository->getCompletedBetweenDates($rangeStart, $rangeEnd);
        $expenses = $this->expenseRepository->getBetweenDates($rangeStart, $rangeEnd);
        $incomes = $this->incomeRepository->getBetweenDates($rangeStart, $rangeEnd);

        // Get the date column name for payments (paid_at or created_at)
        $paymentDateColumn = \App\Models\Payment::getDateColumn();
        $paymentRevenueByMonth = $this->groupTotalsByMonth($payments, $paymentDateColumn, 'final_amount');
        $incomeByMonth = $this->groupTotalsByMonth($incomes, 'received_at', 'amount');
        $expenseByMonth = $this->groupTotalsByMonth($expenses, 'spent_at', 'amount');

        $period = CarbonPeriod::create($rangeStart, '1 month', $rangeEnd);

        return collect($period)->map(function (Carbon $month) use ($paymentRevenueByMonth, $incomeByMonth, $expenseByMonth) {
            $key = $month->format('Y-m');
            $paymentRevenue = (float) ($paymentRevenueByMonth[$key] ?? 0);
            $income = (float) ($incomeByMonth[$key] ?? 0);
            $revenue = $paymentRevenue + $income;
            $expenses = (float) ($expenseByMonth[$key] ?? 0);
            $netProfit = $revenue - $expenses;
            $margin = $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : null;

            return [
                'key' => $key,
                'label' => $month->format('F Y'),
                'revenue' => round($revenue, 2),
                'expenses' => round($expenses, 2),
                'net_profit' => round($netProfit, 2),
                'margin' => $margin,
            ];
        })->values();
    }

    /**
     * Build aggregate totals for a collection of monthly rows.
     */
    public function summarize(Collection $monthlyOverview): array
    {
        $revenue = round($monthlyOverview->sum('revenue'), 2);
        $expenses = round($monthlyOverview->sum('expenses'), 2);
        $netProfit = round($monthlyOverview->sum('net_profit'), 2);

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'net_profit' => $netProfit,
            'margin' => $revenue > 0 ? round(($netProfit / $revenue) * 100, 2) : null,
        ];
    }

    /**
     * Get a single-month snapshot (usually latest month in overview).
     */
    public function latestMonth(Collection $monthlyOverview): array
    {
        $latest = $monthlyOverview->last() ?? [];

        return [
            'label' => $latest['label'] ?? now()->format('F Y'),
            'revenue' => $latest['revenue'] ?? 0,
            'expenses' => $latest['expenses'] ?? 0,
            'net_profit' => $latest['net_profit'] ?? 0,
            'margin' => $latest['margin'] ?? null,
        ];
    }

    /**
     * Normalize a dataset into a month => total map.
     */
    private function groupTotalsByMonth(Collection $records, string $dateField, string $amountField): array
    {
        return $records
            ->filter(fn ($record) => !empty($record->{$dateField}))
            ->groupBy(fn ($record) => Carbon::parse($record->{$dateField})->format('Y-m'))
            ->map(function ($items) use ($amountField) {
                return (float) $items->sum(function ($item) use ($amountField) {
                    // For payments, use final_amount if exists, otherwise use amount
                    if ($amountField === 'final_amount' && (!isset($item->final_amount) || $item->final_amount === null)) {
                        return (float) ($item->amount ?? 0);
                    }
                    return (float) ($item->{$amountField} ?? 0);
                });
            })
            ->all();
    }

    private function sanitizeRange(int $months): int
    {
        $allowed = [3, 6, 12];
        if (!in_array($months, $allowed, true)) {
            return 6;
        }

        return $months;
    }
}

