<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * RoleController
 * 
 * Handles CRUD operations for roles and their permissions.
 * Admins can create custom roles with any combination of permissions.
 */
class RoleController extends Controller
{
    /**
     * Display a listing of all roles with permission counts.
     */
    public function index(Request $request)
    {
        $roles = Role::where('guard_name', 'web')
            ->withCount('permissions', 'users')
            ->orderBy('name')
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions_count' => $role->permissions_count,
                    'users_count' => $role->users_count,
                    'is_system' => in_array($role->name, ['super-admin', 'operator', 'warehouse', 'support', 'sales']),
                    'created_at' => $role->created_at?->format('M d, Y'),
                    'description' => $this->getRoleDescription($role->name),
                ];
            });

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create()
    {
        $permissions = $this->getGroupedPermissions();
        $presets = $this->getRolePresets();

        return Inertia::render('Admin/Roles/Create', [
            'permissionGroups' => $permissions,
            'rolePresets' => $presets,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => strtolower(str_replace(' ', '-', $validated['name'])),
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($validated['permissions']);

            DB::commit();

            return redirect()
                ->route('admin.user-management.index', ['tab' => 'roles'])
                ->with('success', 'Role created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withErrors(['message' => 'Failed to create role: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Role $role)
    {
        $permissions = $this->getGroupedPermissions();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $presets = $this->getRolePresets();

        return Inertia::render('Admin/Roles/Edit', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'is_system' => in_array($role->name, ['super-admin', 'operator', 'warehouse', 'support', 'sales']),
            ],
            'permissionGroups' => $permissions,
            'rolePermissions' => $rolePermissions,
            'rolePresets' => $presets,
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        try {
            DB::beginTransaction();

            $role->update([
                'name' => strtolower(str_replace(' ', '-', $validated['name'])),
            ]);

            $role->syncPermissions($validated['permissions']);

            DB::commit();

            return redirect()
                ->route('admin.user-management.index', ['tab' => 'roles'])
                ->with('success', 'Role updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->withErrors(['message' => 'Failed to update role: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role)
    {
        // Prevent deletion if users are assigned
        if ($role->users()->count() > 0) {
            return redirect()
                ->back()
                ->withErrors(['message' => 'Cannot delete role with assigned users.']);
        }

        try {
            $role->delete();

            return redirect()
                ->route('admin.user-management.index', ['tab' => 'roles'])
                ->with('success', 'Role deleted successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withErrors(['message' => 'Failed to delete role: ' . $e->getMessage()]);
        }
    }

    /**
     * Get permissions grouped by module for the UI.
     */
    private function getGroupedPermissions(): array
    {
        $permissions = Permission::where('guard_name', 'web')
            ->orderBy('name')
            ->get();

        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $module = $parts[0];

            if (!isset($grouped[$module])) {
                $grouped[$module] = [
                    'name' => $this->formatModuleName($module),
                    'handle' => $module,
                    'permissions' => [],
                    'subModules' => [],
                ];
            }

            if (count($parts) === 2) {
                // Module-level permission: module.action
                $grouped[$module]['permissions'][] = [
                    'name' => $permission->name,
                    'action' => $parts[1],
                    'label' => ucfirst($parts[1]),
                ];
            } elseif (count($parts) === 3) {
                // Sub-module permission: module.submodule.action
                $subModule = $parts[1];
                if (!isset($grouped[$module]['subModules'][$subModule])) {
                    $grouped[$module]['subModules'][$subModule] = [
                        'name' => $this->formatModuleName($subModule),
                        'handle' => $subModule,
                        'permissions' => [],
                    ];
                }
                $grouped[$module]['subModules'][$subModule]['permissions'][] = [
                    'name' => $permission->name,
                    'action' => $parts[2],
                    'label' => ucfirst($parts[2]),
                ];
            }
        }

        // Convert subModules from associative to indexed array
        foreach ($grouped as $module => $data) {
            $grouped[$module]['subModules'] = array_values($data['subModules']);
        }

        return array_values($grouped);
    }

    /**
     * Get Role Presets for the wizard.
     */
    private function getRolePresets(): array
    {
        // Define common presets to help users start quickly
        return [
            [
                'name' => 'Warehouse Staff',
                'description' => 'Can view orders and manage shipments/inventory.',
                'access_level' => [
                    'shipments' => 'manage', // Full control
                    'orders' => 'view',      // View only
                    'inventory' => 'manage',
                    'users' => 'none',
                ]
            ],
            [
                'name' => 'Support Agent',
                'description' => 'Can view user details, orders, and shipments to help customers.',
                'access_level' => [
                    'shipments' => 'view',
                    'orders' => 'view',
                    'users' => 'view',
                    'tickets' => 'manage',
                ]
            ],
            [
                'name' => 'Sales Manager',
                'description' => 'Can view all sales data, reports, and customer profiles.',
                'access_level' => [
                    'orders' => 'view',
                    'reports' => 'view',
                    'users' => 'view',
                ]
            ],
        ];
    }

    private function getRoleDescription($name): string
    {
        return match ($name) {
            'super-admin' => 'Full system access with no restrictions.',
            'operator' => 'Can manage day-to-day operations.',
            'warehouse' => 'Focus on inventory and fulfillment.',
            'support' => 'Customer assistance and order viewing.',
            'sales' => 'Sales reporting and customer insight.',
            default => 'Custom role with specific permissions.',
        };
    }

    /**
     * Format module name for display.
     */
    private function formatModuleName(string $name): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $name));
    }
}
