<?php

namespace App\DataTables;

use App\Models\Payment;
use Yajra\DataTables\Html\Column;

class PaymentDataTable extends BaseDataTable
{
    public function dataTable($query)
    {
        $dataTable = datatables()->eloquent($query);

        $this->autoFormatDates($dataTable, ['paid_at', 'created_at']);

        return $dataTable
            ->addColumn('payment_reference', function ($payment) {
                return view('admin.payments.partials.reference', compact('payment'))->render();
            })
            ->addColumn('user_info', function ($payment) {
                return view('admin.payments.partials.user', compact('payment'))->render();
            })
            ->addColumn('plan_name', function ($payment) {
                return $payment->subscription->subscriptionPlan->plan_name ?? '—';
            })
            ->editColumn('final_amount', function ($payment) {
                $amount = $payment->final_amount ?? $payment->amount ?? 0;
                return '$' . number_format((float) $amount, 2);
            })
            ->editColumn('payment_method', function ($payment) {
                return $payment->readable_payment_method;
            })
            ->editColumn('status', function ($payment) {
                $label = ucfirst(str_replace('_', ' ', $payment->status ?? 'unknown'));
                return '<span class="badge badge-' . e($payment->status ?? 'unknown') . '">' . e($label) . '</span>';
            })
            ->addColumn('action', function ($payment) {
                $viewUrl = route('admin.payments.show', $payment);

                return '<div class="flex justify-center">
                    <a href="' . $viewUrl . '" class="btn btn-link text-sm">View</a>
                </div>';
            })
            ->rawColumns(['payment_reference', 'user_info', 'status', 'action']);
    }

    public function query(Payment $model)
    {
        $query = $model->newQuery()->with(['user:id,name,email', 'subscription.subscriptionPlan:id,plan_name']);

        // Apply custom filters from filter form
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

        $dateColumn = Payment::getDateColumn();
        $dateFrom = request()->input('date_from');
        if (!empty($dateFrom)) {
            $query->whereDate($dateColumn, '>=', $dateFrom);
        }

        $dateTo = request()->input('date_to');
        if (!empty($dateTo)) {
            $query->whereDate($dateColumn, '<=', $dateTo);
        }

        // Custom search from filter form (not DataTables built-in search)
        // DataTables sends search[value] for its own search box, we use 'search' for our custom filter
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

        // Handle DataTables built-in advanced search (search[value])
        $datatableSearch = request()->input('search.value');
        if (!empty($datatableSearch) && trim($datatableSearch) !== '') {
            $searchValue = trim($datatableSearch);
            $query->where(function ($q) use ($searchValue) {
                // Only search transaction_id if column exists
                if (Payment::hasTransactionIdColumn()) {
                    $q->where('transaction_id', 'like', "%{$searchValue}%")
                        ->orWhere('id', 'like', "%{$searchValue}%")
                        ->orWhere('amount', 'like', "%{$searchValue}%");
                } else {
                    $q->where('id', 'like', "%{$searchValue}%")
                        ->orWhere('amount', 'like', "%{$searchValue}%");
                }
                $q->orWhereHas('user', function ($userQuery) use ($searchValue) {
                    $userQuery->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%");
                })
                ->orWhereHas('subscription.subscriptionPlan', function ($planQuery) use ($searchValue) {
                    $planQuery->where('plan_name', 'like', "%{$searchValue}%");
                });
            });
        }

        return $query;
    }

    protected function getTableId(): string
    {
        return 'payments-table';
    }

    protected function getFilterFormId(): string
    {
        return 'payments-filter-form';
    }

    protected function getColumns(): array
    {
        return [
            Column::make('payment_reference')->title('Payment')->orderable(false)->searchable(false),
            Column::make('user_info')->title('User')->orderable(false)->searchable(false),
            Column::make('plan_name')->title('Plan')->orderable(false)->searchable(true),
            Column::make('final_amount')->title('Amount')->searchable(true),
            Column::make('payment_method')->title('Method')->orderable(false)->searchable(true),
            Column::make('status')->title('Status')->orderable(false)->searchable(false),
            Column::make('paid_at')->title('Paid At')->searchable(true),
            Column::computed('action')
                ->exportable(false)
            ->printable(false)
            ->width('10%')
            ->addClass('text-center')
            ->title('Actions'),
        ];
    }
}


