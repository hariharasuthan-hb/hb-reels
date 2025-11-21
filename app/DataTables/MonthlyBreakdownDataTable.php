<?php

namespace App\DataTables;

use Illuminate\Support\Collection;
use Yajra\DataTables\Html\Column;

class MonthlyBreakdownDataTable extends BaseDataTable
{
    private Collection $monthlyOverview;

    /**
     * Build DataTable class.
     */
    public function dataTable($query)
    {
        return datatables()
            ->collection($query)
            ->addColumn('month', fn ($row) => $row['label'])
            ->addColumn('revenue_display', function ($row) {
                $status = $row['revenue'] >= $row['expenses'] ? 'Above expenses' : 'Below expenses';
                return view('admin.finances.partials.revenue', [
                    'amount' => $row['revenue'],
                    'status' => $status,
                ])->render();
            })
            ->addColumn('expenses_display', function ($row) {
                $daysInMonth = now()->parse($row['key'] . '-01')->daysInMonth;
                $avgDaily = $daysInMonth > 0 ? $row['expenses'] / $daysInMonth : 0;

                return view('admin.finances.partials.expenses', [
                    'amount' => $row['expenses'],
                    'avgDaily' => $avgDaily,
                ])->render();
            })
            ->addColumn('net_profit_display', function ($row) {
                $netProfit = (float) ($row['net_profit'] ?? 0);
                $class = $netProfit >= 0 ? 'text-emerald-600' : 'text-red-600';
                $formatted = '$' . number_format($netProfit, 2);
                return '<span class="' . $class . ' font-semibold">' . $formatted . '</span>';
            })
            ->addColumn('margin_display', function ($row) {
                if (is_null($row['margin'])) {
                    return '—';
                }

                $label = $row['margin'] >= 50 ? 'Healthy' : ($row['margin'] >= 0 ? 'Watch' : 'Negative');
                $class = $row['margin'] >= 50 ? 'text-emerald-600' : ($row['margin'] >= 0 ? 'text-amber-500' : 'text-red-600');

                return view('admin.finances.partials.margin', [
                    'margin' => $row['margin'],
                    'label' => $label,
                    'class' => $class,
                ])->render();
            })
            ->rawColumns(['revenue_display', 'expenses_display', 'net_profit_display', 'margin_display']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query($model = null)
    {
        return $this->monthlyOverview;
    }

    /**
     * Set monthly overview data.
     */
    public function setMonthlyOverview(Collection $monthlyOverview): self
    {
        $this->monthlyOverview = $monthlyOverview;
        return $this;
    }

    protected function getTableId(): string
    {
        return 'monthly-breakdown-table';
    }

    protected function getColumns(): array
    {
        return [
            Column::make('month')->title('Month')->orderable(false)->searchable(false),
            Column::make('revenue_display')->title('Revenue')->addClass('text-right')->orderable(false)->searchable(false),
            Column::make('expenses_display')->title('Expenses')->addClass('text-right')->orderable(false)->searchable(false),
            Column::make('net_profit_display')->title('Net Profit')->addClass('text-right')->orderable(false)->searchable(false),
            Column::make('margin_display')->title('Margin')->addClass('text-right')->orderable(false)->searchable(false),
        ];
    }
}


