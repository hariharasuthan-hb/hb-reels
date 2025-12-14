<?php

namespace HbReels\EventReelGenerator\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DeleteVideoFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60; // 1 minute delay between retries

    protected string $disk;
    protected string $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(string $disk, string $filePath)
    {
        $this->disk = $disk;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if (Storage::disk($this->disk)->exists($this->filePath)) {
                Storage::disk($this->disk)->delete($this->filePath);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete video file via queued job', [
                'disk' => $this->disk,
                'path' => $this->filePath,
                'error' => $e->getMessage(),
                'job_id' => $this->job->getJobId()
            ]);

            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Video file deletion job failed permanently', [
            'disk' => $this->disk,
            'path' => $this->filePath,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}