<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\InvoiceDataTable;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Repositories\Interfaces\PaymentRepositoryInterface;
use Illuminate\Contracts\View\View;

/**
 * Controller for managing invoices in the admin panel.
 * 
 * Handles viewing and displaying invoice records. Invoices are backed by
 * payment records and represent billing documents for subscriptions.
 * Requires 'view invoices' permission.
 */
class InvoiceController extends Controller
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository
    ) {
        $this->middleware('permission:view invoices');
    }

    /**
     * Display a listing of invoices (backed by payment records).
     */
    public function index(InvoiceDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new Payment()))->toJson();
        }

        return view('admin.invoices.index', [
            'dataTable' => $dataTable,
            'filters' => request()->only(['status', 'method', 'date_from', 'date_to', 'search']),
            'statusOptions' => \App\Models\Payment::getStatusOptions(),
            'methodOptions' => $this->paymentRepository->getDistinctMethods(),
        ]);
    }

    /**
     * Display the specified invoice.
     */
    public function show(Payment $invoice): View
    {
        $invoice->load(['user', 'subscription.subscriptionPlan']);

        return view('admin.invoices.show', [
            'invoice' => $invoice,
        ]);
    }
}


