<?php

namespace App\DataTables;

use App\Models\SubscriptionPlan;
use Yajra\DataTables\Html\Column;

class SubscriptionPlanDataTable extends BaseDataTable
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
            ->addColumn('formatted_price', function ($plan) {
                return '$' . number_format($plan->price, 2);
            })
            ->addColumn('formatted_duration', function ($plan) {
                return $plan->duration . ' ' . ucfirst($plan->duration_type) . ($plan->duration > 1 ? 's' : '');
            })
            ->addColumn('status', function ($plan) {
                if ($plan->is_active) {
                    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>';
                }
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">Inactive</span>';
            })
            ->addColumn('action', function ($plan) {
                $editUrl = route('admin.subscription-plans.edit', $plan->id);
                $showUrl = route('admin.subscription-plans.show', $plan->id);
                $deleteUrl = route('admin.subscription-plans.destroy', $plan->id);
                
                $html = '<div class="flex justify-center space-x-2">';
                $html .= '<a href="' . $showUrl . '" class="text-blue-600 hover:text-blue-900" title="View">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>';
                $html .= '</a>';
                $html .= '<a href="' . $editUrl . '" class="text-indigo-600 hover:text-indigo-900" title="Edit">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>';
                $html .= '</a>';
                $html .= '<form action="' . $deleteUrl . '" method="POST" class="inline" data-confirm="true" data-confirm-title="Delete Subscription Plan" data-confirm-message="Deleting ' . e($plan->plan_name) . ' removes it permanently. Make sure no members rely on this plan." data-confirm-button="Delete Plan" data-confirm-tone="danger">';
                $html .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
                $html .= '<input type="hidden" name="_method" value="DELETE">';
                $html .= '<button type="submit" class="text-red-600 hover:text-red-900" title="Delete">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
                $html .= '</button></form>';
                $html .= '</div>';
                
                return $html;
            })
            ->rawColumns(['action', 'status']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(SubscriptionPlan $model)
    {
        return $model->newQuery();
    }

    /**
     * Get table ID
     */
    protected function getTableId(): string
    {
        return 'subscription-plans-table';
    }

    /**
     * Get columns definition
     */
    protected function getColumns(): array
    {
        return [
            Column::make('id')->title('ID')->width('5%'),
            Column::make('plan_name')->title('Plan Name')->width('20%'),
            Column::make('duration_type')->title('Duration Type')->width('12%'),
            Column::make('duration')->title('Duration')->width('10%'),
            Column::make('formatted_price')->title('Price')->width('12%')->orderable(false)->searchable(false),
            Column::make('status')->title('Status')->width('10%')->orderable(false)->searchable(false),
            Column::make('created_at')->title('Created At')->width('13%'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width('18%')
                ->addClass('text-center')
                ->title('Actions'),
        ];
    }
}

