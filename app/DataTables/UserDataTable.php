<?php

namespace App\DataTables;

use App\Models\User;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Button;

class UserDataTable extends BaseDataTable
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
            ->addColumn('roles', function ($user) {
                return $user->roles->pluck('name')->implode(', ') ?: '-';
            })
            ->editColumn('status', function ($user) {
                $color = $user->status === 'active'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-red-100 text-red-800';
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full ' . $color . '">' . ucfirst($user->status) . '</span>';
            })
            ->addColumn('action', function ($user) {
                $editUrl = route('admin.users.edit', $user->id);
                $showUrl = route('admin.users.show', $user->id);
                $deleteUrl = route('admin.users.destroy', $user->id);
                
                $html = '<div class="flex justify-center space-x-2">';
                $html .= '<a href="' . $showUrl . '" class="text-blue-600 hover:text-blue-900" title="View">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>';
                $html .= '</a>';
                $html .= '<a href="' . $editUrl . '" class="text-indigo-600 hover:text-indigo-900" title="Edit">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>';
                $html .= '</a>';
                $html .= '<form action="' . $deleteUrl . '" method="POST" class="inline" data-confirm="true" data-confirm-title="Delete User" data-confirm-message="Deleting ' . e($user->name) . ' will remove their profile permanently. This action cannot be undone." data-confirm-button="Delete User" data-confirm-tone="danger">';
                $html .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
                $html .= '<input type="hidden" name="_method" value="DELETE">';
                $html .= '<button type="submit" class="text-red-600 hover:text-red-900" title="Delete">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
                $html .= '</button></form>';
                $html .= '</div>';
                
                return $html;
            })
            ->rawColumns(['action', 'roles', 'status']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(User $model)
    {
        return $model->newQuery()->with('roles');
    }

    /**
     * Get table ID
     */
    protected function getTableId(): string
    {
        return 'users-table';
    }

    /**
     * Get columns definition
     */
    protected function getColumns(): array
    {
        return [
            Column::make('id')->title('ID')->width('5%'),
            Column::make('name')->title('Name')->width('15%'),
            Column::make('email')->title('Email')->width('20%'),
            Column::make('phone')->title('Phone')->width('12%'),
            Column::make('roles')->title('Roles')->width('15%')->orderable(false)->searchable(false),
            Column::make('status')->title('Status')->width('10%'),
            Column::make('created_at')->title('Created At')->width('13%'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width('20%')
                ->addClass('text-center')
                ->title('Actions'),
        ];
    }
}

