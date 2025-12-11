<?php

namespace HbReels\EventReelGenerator\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\ActivityLog;

class CleanupDownloadedVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 30; // 30 seconds delay between retries

    protected array $cleanupInfo;

    /**
     * Create a new job instance.
     */
    public function __construct(array $cleanupInfo)
    {
        $this->cleanupInfo = $cleanupInfo;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $activityLogId = $this->cleanupInfo['activity_log_id'];
            $disk = $this->cleanupInfo['disk'];
            $filePath = $this->cleanupInfo['file_path'];

            // Find the activity log
            $activityLog = ActivityLog::find($activityLogId);

            if ($activityLog) {
                // Store user_id for logging before deletion
                $userId = $activityLog->user_id;

                // Delete the activity log record
                $activityLog->delete();

                Log::info('Activity log deleted after video download', [
                    'activity_log_id' => $activityLogId,
                    'user_id' => $userId,
                    'job_id' => $this->job->getJobId()
                ]);
            } else {
                Log::info('Activity log already deleted or not found', [
                    'activity_log_id' => $activityLogId,
                    'job_id' => $this->job->getJobId()
                ]);
            }

            // Delete the video file if it still exists
            if (Storage::disk($disk)->exists($filePath)) {
                Storage::disk($disk)->delete($filePath);

                Log::info('Video file deleted after download', [
                    'disk' => $disk,
                    'file_path' => $filePath,
                    'job_id' => $this->job->getJobId()
                ]);
            } else {
                Log::info('Video file already deleted or not found', [
                    'disk' => $disk,
                    'file_path' => $filePath,
                    'job_id' => $this->job->getJobId()
                ]);
            }

            Log::info('Video cleanup completed successfully', [
                'activity_log_id' => $activityLogId,
                'disk' => $disk,
                'file_path' => $filePath,
                'job_id' => $this->job->getJobId()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to cleanup downloaded video', [
                'cleanup_info' => $this->cleanupInfo,
                'error' => $e->getMessage(),
                'job_id' => $this->job->getJobId(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Video cleanup job failed permanently', [
            'cleanup_info' => $this->cleanupInfo,
            'error' => $exception->getMessage(),
            'job_id' => $this->job->getJobId(),
            'attempts' => $this->attempts()
        ]);
    }
}