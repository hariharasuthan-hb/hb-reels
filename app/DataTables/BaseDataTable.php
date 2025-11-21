<?php

namespace App\DataTables;

use App\DataTables\Traits\AutoFormatsDates;
use Yajra\DataTables\Html\Builder;
use Yajra\DataTables\Html\Column;

abstract class BaseDataTable
{
    use AutoFormatsDates;
    /**
     * Build DataTable html.
     */
    public function html(): Builder
    {
        $formId = $this->getFilterFormId();
        
        $builder = $this->builder()
            ->setTableId($this->getTableId())
            ->columns($this->getColumns());
        
        // Add filter form data if form exists
        if ($formId) {
            $builder->ajax([
                'data' => "function(d) {
                    var form = document.getElementById('{$formId}');
                    if (form) {
                        var inputs = form.querySelectorAll('input, select, textarea');
                        for (var i = 0; i < inputs.length; i++) {
                            var input = inputs[i];
                            var name = input.name;
                            var value = input.value;
                            if (!name) continue;
                            if (input.type === 'date') {
                                d[name] = value || '';
                            } else if (value && value.toString().trim() !== '') {
                                d[name] = value;
                            }
                        }
                    }
                    return d;
                }"
            ]);
        }
        
        return $builder
            ->orderBy(0, 'asc')
            ->buttons($this->getButtons())
            ->parameters([
                'dom' => "<'dt-toolbar flex flex-col md:flex-row md:items-center md:justify-between gap-4'<'dt-toolbar-left flex items-center gap-3'lB><'dt-toolbar-right'f>>" .
                    "<'dt-table'rt>" .
                    "<'dt-footer flex flex-col md:flex-row md:items-center md:justify-between gap-4'<'dt-info'i><'dt-pagination'p>>",
                'language' => [
                    'search' => '',
                    'searchPlaceholder' => 'Search...',
                    'lengthMenu' => '_MENU_',
                ],
                'responsive' => true,
                'autoWidth' => false,
                'pageLength' => 5,
                'lengthMenu' => [[5, 10, 25, 50, 100, -1], [5, 10, 25, 50, 100, "All"]],
            ]);
    }

    /**
     * Get filter form ID for this DataTable.
     * Override in child classes if needed.
     */
    protected function getFilterFormId(): string
    {
        // Default pattern: {table-id}-filter-form
        return str_replace('_', '-', $this->getTableId()) . '-filter-form';
    }

    /**
     * Get HTML builder instance.
     */
    protected function builder(): Builder
    {
        return app(Builder::class);
    }

    /**
     * Get the table ID for this DataTable
     */
    abstract protected function getTableId(): string;

    /**
     * Public method to get table ID
     */
    public function getTableIdPublic(): string
    {
        return $this->getTableId();
    }

    /**
     * Get columns definition
     */
    abstract protected function getColumns(): array;

    /**
     * Get buttons configuration
     */
    protected function getButtons(): array
    {
        return [
            'excel',
            'pdf',
            'reload',
        ];
    }

    /**
     * Get filename for export.
     */
    protected function filename(): string
    {
        return $this->getTableId() . '_' . date('YmdHis');
    }

    /**
     * Get scripts for DataTable
     * Uses Yajra's built-in scripts() method
     */
    public function scripts(): string
    {
        return $this->html()->scripts();
    }
}
