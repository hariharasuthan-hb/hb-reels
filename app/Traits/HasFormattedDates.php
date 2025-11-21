<?php

namespace App\Traits;

/**
 * Trait for models that need formatted date accessors.
 * 
 * To use this trait, add it to your model:
 * use HasFormattedDates;
 * 
 * Then define accessors in your model like:
 * 
 * public function getFormattedStartDateAttribute()
 * {
 *     return format_date($this->start_date);
 * }
 * 
 * Usage: $model->formatted_start_date
 */
trait HasFormattedDates
{
    // This trait serves as documentation and can be extended
    // with model-specific accessors as needed
}

