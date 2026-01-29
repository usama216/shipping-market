<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

/**
 * SystemUserController
 * 
 * Handles CRUD operations for system users (admins, operators, etc.).
 * Each system user has exactly one role assigned.
 */
class SystemUserController extends Controller
{
    /**
     * Display a listing of system users with their roles.
     */
    public function index(Request $request)
    {
        return redirect()->route('admin.user-management.index');
    }

    /**
     * Show the form for creating a new system user.
     */
    public function create()
    {
        $roles = Role::where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/SystemUsers/Create', [
            'roles' => $roles,
            'userTypes' => $this->getUserTypes(),
        ]);
    }

    /**
     * Store a newly created system user.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'type' => [
                'required',
                'integer',
                Rule::in([
                    User::USER_TYPE_ADMIN,
                    User::USER_TYPE_OPERATOR,
                    User::USER_TYPE_WAREHOUSE,
                    User::USER_TYPE_SUPPORT,
                    User::USER_TYPE_SALES,
                ])
            ],
            'role' => ['required', 'string', 'exists:roles,name'],
            'is_active' => ['boolean'],
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'type' => $validated['type'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Assign the single role
            $user->assignRole($validated['role']);

            DB::commit();

            return redirect()
                ->route('admin.user-management.index')
                ->with('success', 'System user created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withErrors(['message' => 'Failed to create user: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified system user.
     */
    public function edit(User $user)
    {
        if (!$user->isSystemUser()) {
            return redirect()
                ->route('admin.user-management.index')
                ->withErrors(['message' => 'This user is not a system user.']);
        }

        $roles = Role::where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Admin/SystemUsers/Edit', [
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'type' => $user->type,
                'role' => $user->roles->first()?->name,
                'is_active' => $user->is_active,
                'avatar' => $user->avatar,
            ],
            'roles' => $roles,
            'userTypes' => $this->getUserTypes(),
        ]);
    }

    /**
     * Update the specified system user.
     */
    public function update(Request $request, User $user)
    {
        if (!$user->isSystemUser()) {
            return redirect()
                ->back()
                ->withErrors(['message' => 'This user is not a system user.']);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'type' => [
                'required',
                'integer',
                Rule::in([
                    User::USER_TYPE_ADMIN,
                    User::USER_TYPE_OPERATOR,
                    User::USER_TYPE_WAREHOUSE,
                    User::USER_TYPE_SUPPORT,
                    User::USER_TYPE_SALES,
                ])
            ],
            'role' => ['required', 'string', 'exists:roles,name'],
            'is_active' => ['boolean'],
        ]);

        try {
            DB::beginTransaction();

            $updateData = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'type' => $validated['type'],
                'is_active' => $validated['is_active'] ?? true,
            ];

            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            // Sync the single role (removes old, assigns new)
            $user->syncRoles([$validated['role']]);

            DB::commit();

            return redirect()
                ->route('admin.user-management.index')
                ->with('success', 'System user updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withErrors(['message' => 'Failed to update user: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle the active status of a system user.
     */
    public function toggleStatus(User $user)
    {
        if (!$user->isSystemUser()) {
            return redirect()
                ->back()
                ->withErrors(['message' => 'This user is not a system user.']);
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->back()
            ->with('success', "User {$status} successfully.");
    }

    /**
     * Remove the specified system user.
     */
    public function destroy(User $user)
    {
        if (!$user->isSystemUser()) {
            return redirect()
                ->back()
                ->withErrors(['message' => 'This user is not a system user.']);
        }

        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()
                ->back()
                ->withErrors(['message' => 'You cannot delete your own account.']);
        }

        try {
            $user->delete();

            return redirect()
                ->route('admin.user-management.index')
                ->with('success', 'System user deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['message' => 'Failed to delete user: ' . $e->getMessage()]);
        }
    }

    /**
     * Get available user types for the form.
     */
    private function getUserTypes(): array
    {
        return [
            ['value' => User::USER_TYPE_ADMIN, 'label' => 'Admin'],
            ['value' => User::USER_TYPE_OPERATOR, 'label' => 'Operator'],
            ['value' => User::USER_TYPE_WAREHOUSE, 'label' => 'Warehouse'],
            ['value' => User::USER_TYPE_SUPPORT, 'label' => 'Support'],
            ['value' => User::USER_TYPE_SALES, 'label' => 'Sales'],
        ];
    }
}
