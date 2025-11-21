<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ExportActivityLogsJob;
use App\Jobs\ExportExpensesJob;
use App\Jobs\ExportIncomesJob;
use App\Jobs\ExportInvoicesJob;
use App\Jobs\ExportPaymentsJob;
use App\Jobs\ExportSubscriptionsJob;
use App\Jobs\ExportFinancesJob;
use App\Models\Export;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller for managing data exports in the admin panel.
 * 
 * Handles asynchronous export generation for various data types including
 * payments, invoices, expenses, incomes, subscriptions, and activity logs.
 * Exports are processed in the background via queue jobs and can be
 * downloaded once ready. Supports CSV and XLSX formats.
 */
class ExportController extends Controller
{
    /**
     * Create a new export job.
     */
    public function export(Request $request, string $type): JsonResponse
    {
        $request->validate([
            'filters' => 'nullable|array',
            'format' => 'nullable|in:csv,xlsx',
        ]);

        $filters = $request->input('filters', []);
        $format = $request->input('format', 'csv');
        $userId = auth()->id();

        // Create pending export record
        $export = Export::create([
            'user_id' => $userId,
            'export_type' => $type,
            'format' => $format,
            'filters' => $filters,
            'status' => Export::STATUS_PENDING,
        ]);

        // Dispatch appropriate job
        $jobClass = $this->getJobClass($type);
        if (!$jobClass) {
            $export->update([
                'status' => Export::STATUS_FAILED,
                'error_message' => "Invalid export type: {$type}",
            ]);

            return response()->json([
                'success' => false,
                'message' => "Invalid export type: {$type}",
            ], 400);
        }

        $jobClass::dispatch($type, $filters, $userId, $format);

        $export->update(['status' => Export::STATUS_PROCESSING]);

        return response()->json([
            'success' => true,
            'message' => 'Export queued successfully. You will be notified when it\'s ready.',
            'export_id' => $export->id,
        ]);
    }

    /**
     * Get export status.
     */
    public function status(Export $export): JsonResponse
    {
        // Ensure user can only access their own exports
        if ($export->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'status' => $export->status,
            'download_url' => $export->download_url,
            'error' => $export->error_message,
        ]);
    }

    /**
     * Download export file.
     */
    public function download(Export $export): BinaryFileResponse
    {
        // Ensure user can only download their own exports
        if ($export->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        if (!$export->isReady()) {
            abort(404, 'Export not ready');
        }

        $filepath = storage_path('app/' . $export->filepath);

        if (!file_exists($filepath)) {
            abort(404, 'File not found');
        }

        return response()->download($filepath, $export->filename);
    }

    /**
     * Get job class for export type.
     */
    protected function getJobClass(string $type): ?string
    {
        return match ($type) {
            Export::TYPE_PAYMENTS => ExportPaymentsJob::class,
            Export::TYPE_INVOICES => ExportInvoicesJob::class,
            Export::TYPE_EXPENSES => ExportExpensesJob::class,
            Export::TYPE_INCOMES => ExportIncomesJob::class,
            Export::TYPE_SUBSCRIPTIONS => ExportSubscriptionsJob::class,
            Export::TYPE_ACTIVITY_LOGS => ExportActivityLogsJob::class,
            Export::TYPE_FINANCES => ExportFinancesJob::class,
            default => null,
        };
    }
}

