<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\DataTables\UserDataTable;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\EntityIntegrityService;

/**
 * Controller for managing users in the admin panel.
 * 
 * Handles CRUD operations for users including creation, updating, deletion,
 * and role assignment. Accessible only to admin users.
 */
class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EntityIntegrityService $entityIntegrityService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(UserDataTable $dataTable)
    {
        if (request()->ajax() || request()->wantsJson()) {
            return $dataTable->dataTable($dataTable->query(new \App\Models\User))->toJson();
        }
        
        return view('admin.users.index', [
            'dataTable' => $dataTable
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->userRepository->createWithRole($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user): View
    {
        $user->load('roles');
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): View
    {
        $roles = Role::all();
        $user->load('roles');
        return view('admin.users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $this->userRepository->updateWithRole($user, $validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        if ($reason = $this->entityIntegrityService->firstUserDeletionBlocker($user)) {
            return redirect()->route('admin.users.index')
                ->with('error', $reason);
        }

        $this->userRepository->deleteByModel($user);

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
