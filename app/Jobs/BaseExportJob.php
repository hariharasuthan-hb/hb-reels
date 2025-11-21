<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

abstract class BaseExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    protected string $exportType;
    protected array $filters;
    protected int $userId;
    protected string $format;

    /**
     * Create a new job instance.
     */
    public function __construct(string $exportType, array $filters, int $userId, string $format = 'csv')
    {
        $this->exportType = $exportType;
        $this->filters = $filters;
        $this->userId = $userId;
        $this->format = $format;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Starting export job", [
                'type' => $this->exportType,
                'user_id' => $this->userId,
                'format' => $this->format,
            ]);

            $data = $this->getData();
            $filename = $this->generateFilename();
            $filepath = $this->generateFile($data, $filename);

            // Store export record
            $this->storeExportRecord($filename, $filepath);

            Log::info("Export job completed", [
                'type' => $this->exportType,
                'filename' => $filename,
                'filepath' => $filepath,
            ]);
        } catch (\Exception $e) {
            Log::error("Export job failed", [
                'type' => $this->exportType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the data to export.
     * Must be implemented by child classes.
     */
    abstract protected function getData(): \Illuminate\Support\Collection;

    /**
     * Get the column headers for the export.
     * Must be implemented by child classes.
     */
    abstract protected function getHeaders(): array;

    /**
     * Format a single row for export.
     * Must be implemented by child classes.
     */
    abstract protected function formatRow($row): array;

    /**
     * Generate filename for the export.
     */
    protected function generateFilename(): string
    {
        $timestamp = Carbon::now()->format('YmdHis');
        return "exports/{$this->exportType}_{$timestamp}.{$this->format}";
    }

    /**
     * Generate the export file.
     */
    protected function generateFile(\Illuminate\Support\Collection $data, string $filename): string
    {
        $headers = $this->getHeaders();
        $rows = $data->map(fn($row) => $this->formatRow($row))->toArray();

        if ($this->format === 'csv') {
            return $this->generateCsv($headers, $rows, $filename);
        } elseif ($this->format === 'xlsx') {
            return $this->generateXlsx($headers, $rows, $filename);
        }

        throw new \InvalidArgumentException("Unsupported format: {$this->format}");
    }

    /**
     * Generate CSV file.
     */
    protected function generateCsv(array $headers, array $rows, string $filename): string
    {
        $filepath = storage_path('app/' . $filename);
        $directory = dirname($filepath);
        
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = fopen($filepath, 'w');

        // Add UTF-8 BOM for Excel compatibility
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

        // Write headers
        fputcsv($file, $headers);

        // Write rows in chunks to handle large datasets
        $chunkSize = 1000;
        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            foreach ($chunk as $row) {
                fputcsv($file, $row);
            }
        }

        fclose($file);

        return $filename;
    }

    /**
     * Generate XLSX file.
     */
    protected function generateXlsx(array $headers, array $rows, string $filename): string
    {
        // For XLSX, we'll use a simple CSV approach or you can integrate PhpSpreadsheet
        // For now, using CSV with .xlsx extension (Excel will open it)
        // In production, consider using PhpSpreadsheet for proper XLSX generation
        return $this->generateCsv($headers, $rows, str_replace('.xlsx', '.csv', $filename));
    }

    /**
     * Store export record in database.
     */
    protected function storeExportRecord(string $filename, string $filepath): void
    {
        // Update existing export record (created in controller)
        \App\Models\Export::where('user_id', $this->userId)
            ->where('export_type', $this->exportType)
            ->where('status', \App\Models\Export::STATUS_PROCESSING)
            ->latest()
            ->first()
            ?->update([
                'filename' => basename($filename),
                'filepath' => $filepath,
                'status' => \App\Models\Export::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Export job failed permanently", [
            'type' => $this->exportType,
            'user_id' => $this->userId,
            'error' => $exception->getMessage(),
        ]);

        // Update existing export record
        \App\Models\Export::where('user_id', $this->userId)
            ->where('export_type', $this->exportType)
            ->where('status', \App\Models\Export::STATUS_PROCESSING)
            ->latest()
            ->first()
            ?->update([
                'status' => \App\Models\Export::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);
    }
}

