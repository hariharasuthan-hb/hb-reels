<?php

namespace App\Models\Pivots;

use Illuminate\Database\Eloquent\Relations\Pivot;

class NotificationRecipient extends Pivot
{
    protected $table = 'notification_user';

    protected $casts = [
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];
}

