<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base Model with automatic date formatting
 * All models should extend this instead of Model directly
 */
abstract class BaseModel extends Model
{
    /**
     * Prepare a date for array / JSON serialization.
     * This automatically formats all date attributes when serialized.
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return format_date($date);
    }

    /**
     * Get the format for database stored dates.
     * This ensures dates are stored correctly in the database.
     */
    public function getDateFormat()
    {
        return 'Y-m-d H:i:s';
    }
}

