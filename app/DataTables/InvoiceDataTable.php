<?php

namespace App\DataTables;

use App\Models\Payment;
use Yajra\DataTables\Html\Column;

class InvoiceDataTable extends BaseDataTable
{
    public function dataTable($query)
    {
        $dataTable = datatables()->eloquent($query);

        $this->autoFormatDates($dataTable, ['paid_at']);

        return $dataTable
            ->addColumn('invoice_reference', function ($invoice) {
                return view('admin.invoices.partials.reference', compact('invoice'))->render();
            })
            ->addColumn('user_info', function ($invoice) {
                return view('admin.invoices.partials.user', compact('invoice'))->render();
            })
            ->addColumn('plan_name', function ($invoice) {
                return $invoice->subscription->subscriptionPlan->plan_name ?? '—';
            })
            ->editColumn('final_amount', function ($invoice) {
                $amount = $invoice->final_amount ?? $invoice->amount ?? 0;
                return '$' . number_format((float) $amount, 2);
            })
            ->editColumn('payment_method', function ($invoice) {
                return $invoice->readable_payment_method;
            })
            ->editColumn('status', function ($invoice) {
                $label = ucfirst(str_replace('_', ' ', $invoice->status ?? 'unknown'));
                return '<span class="badge badge-' . e($invoice->status ?? 'unknown') . '">' . e($label) . '</span>';
            })
            ->addColumn('action', function ($invoice) {
                $viewUrl = route('admin.invoices.show', $invoice);
                return '<div class="flex justify-center">
                    <a href="' . $viewUrl . '" class="btn btn-link text-sm">View</a>
                </div>';
            })
            ->rawColumns(['invoice_reference', 'user_info', 'status', 'action']);
    }

    public function query(Payment $model)
    {
        $dateColumn = Payment::getDateColumn();
        $query = $model->newQuery()
            ->with(['user:id,name,email', 'subscription.subscriptionPlan:id,plan_name'])
            ->whereNotNull($dateColumn);

        // Only filter by status if the column exists (accounting system may not have it)
        if ($status = request('status')) {
            if (Payment::hasStatusColumn()) {
                $query->where('status', $status);
            }
        }

        // Only filter by payment_method if the column exists (accounting system may not have it)
        if ($method = request('method')) {
            if (Payment::hasPaymentMethodColumn()) {
                $query->where('payment_method', $method);
            }
        }

        $dateFrom = request()->input('date_from');
        if (!empty($dateFrom)) {
            $query->whereDate($dateColumn, '>=', $dateFrom);
        }

        $dateTo = request()->input('date_to');
        if (!empty($dateTo)) {
            $query->whereDate($dateColumn, '<=', $dateTo);
        }

        // Custom search from filter form (not DataTables built-in search)
        $customSearch = request()->input('search');
        if (!empty($customSearch) && !is_array($customSearch) && trim($customSearch) !== '') {
            $searchValue = trim($customSearch);
            $query->where(function ($q) use ($searchValue) {
                // Only search transaction_id if column exists
                if (Payment::hasTransactionIdColumn()) {
                    $q->where('transaction_id', 'like', "%{$searchValue}%")
                        ->orWhere('id', 'like', "%{$searchValue}%");
                } else {
                    $q->where('id', 'like', "%{$searchValue}%");
                }
                $q->orWhereHas('user', function ($userQuery) use ($searchValue) {
                    $userQuery->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%");
                });
            });
        }

        return $query;
    }

    protected function getTableId(): string
    {
        return 'invoices-table';
    }

    protected function getFilterFormId(): string
    {
        return 'invoices-filter-form';
    }

    protected function getColumns(): array
    {
        return [
            Column::make('invoice_reference')->title('Invoice')->orderable(false)->searchable(false),
            Column::make('user_info')->title('User')->orderable(false)->searchable(false),
            Column::make('plan_name')->title('Plan')->orderable(false),
            Column::make('final_amount')->title('Amount'),
            Column::make('status')->title('Status')->orderable(false)->searchable(false),
            Column::make('payment_method')->title('Method')->orderable(false),
            Column::make('paid_at')->title('Issued At'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width('10%')
                ->addClass('text-center')
                ->title('Actions'),
        ];
    }
}


