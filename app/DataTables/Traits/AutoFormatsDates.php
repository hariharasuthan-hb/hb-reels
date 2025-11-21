<?php

namespace App\DataTables\Traits;

/**
 * Trait to automatically format date columns in DataTables
 * 
 * This trait automatically detects and formats common date column names
 * without needing to manually add editColumn for each one.
 */
trait AutoFormatsDates
{
    /**
     * Common date column names that should be automatically formatted
     */
    protected function getDateColumns(): array
    {
        return [
            'created_at',
            'updated_at',
            'deleted_at',
            'start_date',
            'end_date',
            'date',
            'next_billing_at',
            'trial_end_at',
            'started_at',
            'expires_at',
            'published_at',
            'scheduled_at',
            'check_in_time',
            'check_out_time',
            'spent_at',
            'received_at',
            'paid_at',
        ];
    }

    /**
     * Automatically format date columns in the dataTable
     * Call this method in your dataTable() method after eloquent()
     * 
     * Only formats columns that exist in the model and haven't been manually formatted
     * 
     * @param mixed $dataTable
     * @param array|null $columns Optional array of specific columns to format. If null, uses getDateColumns()
     */
    protected function autoFormatDates($dataTable, ?array $columns = null)
    {
        $dateColumns = $columns ?? $this->getDateColumns();
        
        foreach ($dateColumns as $column) {
            // Only format if column exists and hasn't been manually edited
            // Check if the column exists by trying to access it from the first row
            $dataTable->editColumn($column, function ($row) use ($column) {
                // Skip if column doesn't exist or is null
                if (!isset($row->{$column}) || is_null($row->{$column})) {
                    return '-';
                }
                
                $value = $row->{$column};
                
                // If it's already a string (manually formatted), return as is
                if (is_string($value) && !($value instanceof \Carbon\Carbon)) {
                    return $value;
                }
                
                // Format the date
                return format_date($value);
            });
        }
        
        return $dataTable;
    }
}

