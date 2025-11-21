<?php

namespace App\DataTables;

use App\Models\Announcement;
use App\Repositories\Interfaces\AnnouncementRepositoryInterface;
use Yajra\DataTables\Html\Column;

class AnnouncementDataTable extends BaseDataTable
{
    public function __construct(
        private readonly AnnouncementRepositoryInterface $announcementRepository
    ) {
    }

    public function dataTable($query)
    {
        $dataTable = datatables()->eloquent($query);

        $this->autoFormatDates($dataTable, ['created_at', 'updated_at', 'published_at']);

        return $dataTable
            ->editColumn('status', fn ($announcement) => ucfirst($announcement->status))
            ->editColumn('audience_type', fn ($announcement) => ucfirst($announcement->audience_type))
            ->addColumn('creator_name', fn ($announcement) => $announcement->creator?->name ?? 'System')
            ->addColumn('action', function ($announcement) {
                $editUrl = route('admin.announcements.edit', $announcement);
                $deleteUrl = route('admin.announcements.destroy', $announcement);

                $html = '<div class="flex justify-end space-x-2">';
                if (auth()->user()->can('edit announcements')) {
                    $html .= '<a href="' . $editUrl . '" class="btn btn-xs btn-secondary">Edit</a>';
                }
                if (auth()->user()->can('delete announcements')) {
                    $html .= '<form action="' . $deleteUrl . '" method="POST" data-confirm="true" data-confirm-title="Delete Announcement" data-confirm-message="Deleting this announcement removes it from everyone\'s feed. Continue?" data-confirm-button="Delete Announcement" data-confirm-tone="danger">';
                    $html .= csrf_field();
                    $html .= method_field('DELETE');
                    $html .= '<button type="submit" class="btn btn-xs btn-danger">Delete</button>';
                    $html .= '</form>';
                }
                $html .= '</div>';

                return $html;
            })
            ->rawColumns(['action']);
    }

    public function query(Announcement $model)
    {
        $filters = request()->only(['status', 'audience_type', 'published_from', 'published_to', 'search']);

        return $this->announcementRepository->queryForDataTable($filters);
    }

    protected function getTableId(): string
    {
        return 'announcements-table';
    }

    protected function getFilterFormId(): string
    {
        return 'announcements-filter-form';
    }

    protected function getColumns(): array
    {
        return [
            Column::make('id')->title('ID')->width('5%'),
            Column::make('title')->title('Title')->width('20%'),
            Column::make('audience_type')->title('Audience')->width('15%'),
            Column::make('status')->title('Status')->width('10%'),
            Column::make('published_at')->title('Published At')->width('20%'),
            Column::make('creator_name')->title('Created By')->orderable(false)->searchable(false)->width('15%'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->title('Actions')
                ->width('15%')
                ->addClass('text-right'),
        ];
    }
}

