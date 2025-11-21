<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoCheckoutMemberJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $activityLogId)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $activityLog = ActivityLog::find($this->activityLogId);

        if (!$activityLog) {
            return;
        }

        // If already checked out or never checked in, no action needed
        if (!$activityLog->check_in_time || $activityLog->check_out_time) {
            return;
        }

        $checkoutTime = Carbon::now();
        $activityLog->check_out_time = $checkoutTime;

        if ($activityLog->check_in_time) {
            $activityLog->duration_minutes = $activityLog->check_in_time->diffInMinutes($checkoutTime);
        }

        $activityLog->save();
    }
}
