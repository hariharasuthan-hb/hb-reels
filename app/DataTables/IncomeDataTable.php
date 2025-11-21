<?php

namespace App\DataTables;

use App\Models\Income;
use Yajra\DataTables\Html\Column;

class IncomeDataTable extends BaseDataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable($query)
    {
        $dataTable = datatables()->eloquent($query);

        $this->autoFormatDates($dataTable);

        return $dataTable
            ->editColumn('amount', function ($income) {
                return '$' . number_format((float) $income->amount, 2);
            })
            ->editColumn('payment_method', function ($income) {
                return $income->readable_payment_method;
            })
            ->editColumn('source', function ($income) {
                return $income->source ?: '—';
            })
            ->editColumn('notes', function ($income) {
                return $income->notes ? str($income->notes)->limit(40) : '—';
            })
            ->addColumn('action', function ($income) {
                $viewUrl = route('admin.incomes.show', $income->id);
                $editUrl = route('admin.incomes.edit', $income->id);
                $deleteUrl = route('admin.incomes.destroy', $income->id);

                $html = '<div class="flex justify-center space-x-2">';
                $html .= '<a href="' . $viewUrl . '" class="text-blue-600 hover:text-blue-900" title="View">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>';
                $html .= '</a>';
                $html .= '<a href="' . $editUrl . '" class="text-indigo-600 hover:text-indigo-900" title="Edit">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>';
                $html .= '</a>';
                $html .= '<form action="' . $deleteUrl . '" method="POST" class="inline delete-form" data-confirm="true" data-confirm-title="Delete Income" data-confirm-message="Are you sure you want to delete this income record? This action cannot be undone.">';
                $html .= csrf_field();
                $html .= method_field('DELETE');
                $html .= '<button type="submit" class="text-red-600 hover:text-red-900" title="Delete">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
                $html .= '</button></form>';
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['action']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(Income $model)
    {
        $query = $model->newQuery();

        if ($category = request('category')) {
            $query->where('category', $category);
        }

        if ($source = request('source')) {
            $query->where('source', 'like', '%' . $source . '%');
        }

        $dateFrom = request()->input('date_from');
        if (!empty($dateFrom)) {
            $query->whereDate('received_at', '>=', $dateFrom);
        }

        $dateTo = request()->input('date_to');
        if (!empty($dateTo)) {
            $query->whereDate('received_at', '<=', $dateTo);
        }

        // Custom search from filter form (not DataTables built-in search)
        $customSearch = request()->input('search');
        if (!empty($customSearch) && !is_array($customSearch) && trim($customSearch) !== '') {
            $searchValue = trim($customSearch);
            $query->where(function ($q) use ($searchValue) {
                $q->where('category', 'like', "%{$searchValue}%")
                    ->orWhere('source', 'like', "%{$searchValue}%")
                    ->orWhere('notes', 'like', "%{$searchValue}%");
            });
        }

        return $query;
    }

    protected function getTableId(): string
    {
        return 'incomes-table';
    }

    protected function getFilterFormId(): string
    {
        return 'income-filter-form';
    }

    protected function getColumns(): array
    {
        return [
            Column::make('id')->title('ID')->width('5%'),
            Column::make('category')->title('Category')->width('15%'),
            Column::make('source')->title('Source')->width('15%')->orderable(false),
            Column::make('amount')->title('Amount')->width('10%'),
            Column::make('payment_method')->title('Payment Method')->width('15%')->orderable(false),
            Column::make('received_at')->title('Received At')->width('15%'),
            Column::make('notes')->title('Notes')->width('20%')->orderable(false)->searchable(false),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width('10%')
                ->addClass('text-center')
                ->title('Actions'),
        ];
    }
}

