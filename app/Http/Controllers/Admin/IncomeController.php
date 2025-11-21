<?php

namespace App\Http\Controllers\Admin;

use App\DataTables\IncomeDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreIncomeRequest;
use App\Http\Requests\Admin\UpdateIncomeRequest;
use App\Models\Income;
use App\Repositories\Interfaces\IncomeRepositoryInterface;
use App\Services\EntityIntegrityService;
use App\Services\ImageUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Controller for managing income records in the admin panel.
 * 
 * Handles CRUD operations for income records including creation, updating,
 * deletion, and viewing. Income represents incoming financial transactions
 * for the gym business. Requires appropriate permissions for each operation.
 */
class IncomeController extends Controller
{
    public function __construct(
        private readonly IncomeRepositoryInterface $incomeRepository,
        private readonly EntityIntegrityService $integrityService,
        private readonly ImageUploadService $uploadService
    ) {
        $this->middleware('permission:view incomes')->only(['index', 'show']);
        $this->middleware('permission:create incomes')->only(['create', 'store']);
        $this->middleware('permission:edit incomes')->only(['edit', 'update']);
        $this->middleware('permission:delete incomes')->only('destroy');
    }

    /**
     * Display a listing of incomes with filters.
     */
    public function index(IncomeDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new Income()))->toJson();
        }

        return view('admin.incomes.index', [
            'dataTable' => $dataTable,
            'filters' => request()->only(['category', 'source', 'date_from', 'date_to', 'search']),
            'categoryOptions' => $this->incomeRepository->getDistinctCategories(),
            'methodOptions' => \App\Models\Income::getPaymentMethodOptions(),
        ]);
    }

    /**
     * Show the form for creating a new income.
     */
    public function create(): View
    {
        return view('admin.incomes.create');
    }

    /**
     * Store a newly created income in storage.
     */
    public function store(StoreIncomeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('reference_document')) {
            $data['reference_document_path'] = $this->uploadService->upload(
                $request->file('reference_document'),
                'finance/incomes'
            );
        }

        $this->incomeRepository->createIncome($data);

        return redirect()
            ->route('admin.incomes.index')
            ->with('success', 'Income recorded successfully.');
    }

    /**
     * Show the form for editing the specified income.
     */
    public function edit(Income $income): View
    {
        return view('admin.incomes.edit', compact('income'));
    }

    /**
     * Display the specified income.
     */
    public function show(Income $income): View
    {
        return view('admin.incomes.show', compact('income'));
    }

    /**
     * Update the specified income in storage.
     */
    public function update(UpdateIncomeRequest $request, Income $income): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('reference_document')) {
            $data['reference_document_path'] = $this->uploadService->upload(
                $request->file('reference_document'),
                'finance/incomes',
                $income->reference_document_path
            );
        } elseif ($request->boolean('remove_reference_document')) {
            $this->uploadService->delete($income->reference_document_path);
            $data['reference_document_path'] = null;
        }

        $this->incomeRepository->updateIncome($income, $data);

        return redirect()
            ->route('admin.incomes.index')
            ->with('success', 'Income updated successfully.');
    }

    /**
     * Remove the specified income from storage.
     */
    public function destroy(Income $income): RedirectResponse
    {
        $blocker = $this->integrityService->firstIncomeDeletionBlocker($income);

        if ($blocker) {
            return redirect()
                ->route('admin.incomes.index')
                ->with('error', $blocker);
        }

        $this->uploadService->delete($income->reference_document_path);

        $this->incomeRepository->deleteIncome($income);

        return redirect()
            ->route('admin.incomes.index')
            ->with('success', 'Income deleted successfully.');
    }
}

