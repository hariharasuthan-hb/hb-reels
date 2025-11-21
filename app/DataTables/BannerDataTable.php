<?php

namespace App\DataTables;

use App\Models\Banner;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\Storage;

class BannerDataTable extends BaseDataTable
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
            ->addColumn('image_preview', function ($banner) {
                if ($banner->image) {
                    $imageUrl = Storage::url($banner->image);
                    return '<img src="' . $imageUrl . '" alt="' . htmlspecialchars($banner->title ?? 'Banner') . '" class="h-16 w-32 object-cover rounded">';
                }
                return '<span class="text-gray-400">No image</span>';
            })
            ->addColumn('status', function ($banner) {
                if ($banner->is_active) {
                    return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Active</span>';
                }
                return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inactive</span>';
            })
            ->addColumn('action', function ($banner) {
                $editUrl = route('admin.banners.edit', $banner->id);
                $deleteUrl = route('admin.banners.destroy', $banner->id);
                
                $html = '<div class="flex justify-center space-x-2">';
                $html .= '<a href="' . $editUrl . '" class="text-indigo-600 hover:text-indigo-900" title="Edit">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>';
                $html .= '</a>';
                $html .= '<form action="' . $deleteUrl . '" method="POST" class="inline" onsubmit="return confirm(\'Are you sure you want to delete this banner?\');">';
                $html .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
                $html .= '<input type="hidden" name="_method" value="DELETE">';
                $html .= '<button type="submit" class="text-red-600 hover:text-red-900" title="Delete">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
                $html .= '</button></form>';
                $html .= '</div>';
                
                return $html;
            })
            ->editColumn('title', function ($banner) {
                $title = htmlspecialchars($banner->title ?? 'Untitled');
                if ($banner->subtitle) {
                    $subtitle = htmlspecialchars(\Illuminate\Support\Str::limit($banner->subtitle, 50));
                    return '<div><div class="font-medium">' . $title . '</div><div class="text-sm text-gray-500">' . $subtitle . '</div></div>';
                }
                return '<div class="font-medium">' . $title . '</div>';
            })
            ->rawColumns(['action', 'status', 'image_preview', 'title']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(Banner $model)
    {
        return $model->newQuery()->orderBy('order', 'asc')->orderBy('created_at', 'desc');
    }

    /**
     * Get table ID
     */
    protected function getTableId(): string
    {
        return 'banners-table';
    }

    /**
     * Get columns definition
     */
    protected function getColumns(): array
    {
        return [
            Column::make('id')->title('ID')->width('5%'),
            Column::make('image_preview')->title('Image')->width('15%')->orderable(false)->searchable(false),
            Column::make('title')->title('Title')->width('25%'),
            Column::make('order')->title('Order')->width('10%'),
            Column::make('status')->title('Status')->width('10%')->orderable(false)->searchable(false),
            Column::make('created_at')->title('Created At')->width('15%'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width('20%')
                ->addClass('text-center')
                ->title('Actions'),
        ];
    }
}

