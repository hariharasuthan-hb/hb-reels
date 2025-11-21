<?php

namespace App\DataTables;

use App\Models\CmsContent;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\Storage;

class CmsContentDataTable extends BaseDataTable
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
            ->addColumn('image_preview', function ($content) {
                if ($content->image) {
                    $imageUrl = Storage::url($content->image);
                    return '<img src="' . $imageUrl . '" alt="' . htmlspecialchars($content->title) . '" class="h-10 w-10 rounded object-cover">';
                }
                return '<span class="text-gray-400">No image</span>';
            })
            ->addColumn('type_badge', function ($content) {
                return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">' . ucfirst($content->type) . '</span>';
            })
            ->addColumn('status', function ($content) {
                if ($content->is_active) {
                    return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>';
                }
                return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>';
            })
            ->addColumn('action', function ($content) {
                $editUrl = route('admin.cms.content.edit', $content->id);
                $showUrl = route('admin.cms.content.show', $content->id);
                $deleteUrl = route('admin.cms.content.destroy', $content->id);
                
                $html = '<div class="flex justify-center space-x-2">';
                $html .= '<a href="' . $showUrl . '" class="text-blue-600 hover:text-blue-900" title="View">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>';
                $html .= '</a>';
                $html .= '<a href="' . $editUrl . '" class="text-indigo-600 hover:text-indigo-900" title="Edit">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>';
                $html .= '</a>';
                $html .= '<form action="' . $deleteUrl . '" method="POST" class="inline" onsubmit="return confirm(\'Are you sure?\');">';
                $html .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
                $html .= '<input type="hidden" name="_method" value="DELETE">';
                $html .= '<button type="submit" class="text-red-600 hover:text-red-900" title="Delete">';
                $html .= '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
                $html .= '</button></form>';
                $html .= '</div>';
                
                return $html;
            })
            ->editColumn('title', function ($content) {
                $title = htmlspecialchars($content->title);
                if ($content->description) {
                    $description = htmlspecialchars(\Illuminate\Support\Str::limit($content->description, 50));
                    return '<div><div class="font-medium">' . $title . '</div><div class="text-xs text-gray-500">' . $description . '</div></div>';
                }
                return '<div class="font-medium">' . $title . '</div>';
            })
            ->rawColumns(['action', 'status', 'type_badge', 'image_preview', 'title']);
    }

    /**
     * Get query source of dataTable.
     */
    public function query(CmsContent $model)
    {
        return $model->newQuery();
    }

    /**
     * Get table ID
     */
    protected function getTableId(): string
    {
        return 'cms-content-table';
    }

    /**
     * Get columns definition
     */
    protected function getColumns(): array
    {
        return [
            Column::make('id')->title('ID')->width('5%'),
            Column::make('image_preview')->title('Image')->width('8%')->orderable(false)->searchable(false),
            Column::make('title')->title('Title')->width('20%'),
            Column::make('key')->title('Key')->width('12%'),
            Column::make('type_badge')->title('Type')->width('10%')->orderable(false)->searchable(false),
            Column::make('status')->title('Status')->width('10%')->orderable(false)->searchable(false),
            Column::make('order')->title('Order')->width('8%'),
            Column::make('created_at')->title('Created At')->width('12%'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width('15%')
                ->addClass('text-center')
                ->title('Actions'),
        ];
    }
}

