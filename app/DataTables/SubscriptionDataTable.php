<?php

namespace App\DataTables;

use App\Models\Subscription;
use Yajra\DataTables\Html\Column;

class SubscriptionDataTable extends BaseDataTable
{
    /**
     * Build DataTable class.
     */
    public function dataTable($query)
    {
        $dataTable = datatables()
            ->eloquent($query);
        
        // Automatically format date columns
        $this->autoFormatDates($dataTable);
        
        return $dataTable
            ->addColumn('user_name', function ($subscription) {
                return $subscription->user?->name ?? 'N/A';
            })
            ->addColumn('user_email', function ($subscription) {
                return $subscription->user?->email ?? 'N/A';
            })
            ->addColumn('plan_name', function ($subscription) {
                return $subscription->subscriptionPlan?->plan_name ?? 'N/A';
            })
            ->addColumn('plan_price', function ($subscription) {
                if (!$subscription->subscriptionPlan) {
                    return 'N/A';
                }
                return '$' . number_format($subscription->subscriptionPlan->price ?? 0, 2);
            })
            ->addColumn('gateway_badge', function ($subscription) {
                if (!$subscription->gateway) {
                    return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">N/A</span>';
                }
                $color = $subscription->gateway === 'stripe' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800';
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $color . '">' . ucfirst($subscription->gateway) . '</span>';
            })
            ->addColumn('status_badge', function ($subscription) {
                $statusColors = [
                    'trialing' => 'bg-yellow-100 text-yellow-800',
                    'active' => 'bg-green-100 text-green-800',
                    'canceled' => 'bg-red-100 text-red-800',
                    'past_due' => 'bg-orange-100 text-orange-800',
                    'expired' => 'bg-gray-100 text-gray-800',
                    'pending' => 'bg-blue-100 text-blue-800',
                ];
                $statusColor = $statusColors[$subscription->status] ?? 'bg-gray-100 text-gray-800';
                $statusLabel = ucfirst(str_replace('_', ' ', $subscription->status));
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $statusColor . '">' . $statusLabel . '</span>';
            })
            ->addColumn('next_billing', function ($subscription) {
                return $subscription->next_billing_at ? format_date($subscription->next_billing_at) : 'N/A';
            })
            ->addColumn('action', function ($subscription) {
                $showUrl = route('admin.subscriptions.show', $subscription->id);
                $editUrl = route('admin.subscriptions.edit', $subscription->id);
                
                $html = '<div class="flex justify-center space-x-2">';
                $html .= '<a href="' . $showUrl . '" class="text-blue-600 hover:text-blue-900" title="View">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>';
                $html .= '</a>';
                $html .= '<a href="' . $editUrl . '" class="text-indigo-600 hover:text-indigo-900" title="Edit">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>';
                $html .= '</a>';
                $html .= '</div>';
                
                return $html;
            })
            ->filterColumn('user_name', function ($query, $keyword) {
                if (empty($keyword)) {
                    return;
                }
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('user_email', function ($query, $keyword) {
                if (empty($keyword)) {
                    return;
                }
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->where('email', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('plan_name', function ($query, $keyword) {
                if (empty($keyword)) {
                    return;
                }
                $query->whereHas('subscriptionPlan', function ($q) use ($keyword) {
                    $q->where('plan_name', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['action', 'status_badge', 'gateway_badge']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(Subscription $model)
    {
        $query = $model->newQuery()->with(['user', 'subscriptionPlan']);

        // Advanced search filters
        if (request()->filled('status')) {
            $query->where('status', request('status'));
        }

        if (request()->filled('gateway')) {
            $query->where('gateway', request('gateway'));
        }

        // Handle custom search parameter from filter form (not DataTables search)
        // DataTables sends search[value] for its own search box, we use 'search' for our custom filter
        $customSearch = request('search');
        if (!empty($customSearch) && !is_array($customSearch)) {
            $searchValue = (string) $customSearch;
            $query->where(function ($q) use ($searchValue) {
                $q->whereHas('user', function ($userQuery) use ($searchValue) {
                    $userQuery->where(function ($uq) use ($searchValue) {
                        $uq->where('name', 'like', "%{$searchValue}%")
                           ->orWhere('email', 'like', "%{$searchValue}%");
                    });
                })
                ->orWhereHas('subscriptionPlan', function ($planQuery) use ($searchValue) {
                    $planQuery->where('plan_name', 'like', "%{$searchValue}%");
                });
            });
        }

        return $query;
    }

    /**
     * Get table ID
     */
    protected function getTableId(): string
    {
        return 'subscriptions-table';
    }

    protected function getFilterFormId(): string
    {
        return 'filter-form';
    }

    /**
     * Get columns definition
     */
    protected function getColumns(): array
    {
        return [
            Column::make('id')->title('ID')->width('5%'),
            Column::make('user_name')->title('User Name')->width('12%')->orderable(false),
            Column::make('user_email')->title('User Email')->width('15%')->orderable(false),
            Column::make('plan_name')->title('Plan')->width('12%')->orderable(false),
            Column::make('plan_price')->title('Price')->width('8%')->orderable(false)->searchable(false),
            Column::make('gateway_badge')->title('Gateway')->width('10%')->orderable(false)->searchable(false),
            Column::make('status_badge')->title('Status')->width('10%')->orderable(false)->searchable(false),
            Column::make('next_billing')->title('Next Billing')->width('12%')->orderable(false)->searchable(false),
            Column::make('created_at')->title('Created At')->width('10%'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width('6%')
                ->addClass('text-center')
                ->title('Actions'),
        ];
    }

}

