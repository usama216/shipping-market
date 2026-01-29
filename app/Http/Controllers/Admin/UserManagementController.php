<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserManagementController extends Controller
{
    /**
     * Display unified user management page with tabs for users and roles.
     */
    public function index(Request $request)
    {
        $activeTab = $request->get('tab', 'users');

        // Get all roles for both tabs
        $roles = Role::withCount(['users', 'permissions'])
            ->get()
            ->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions_count' => $role->permissions_count,
                    'users_count' => $role->users_count,
                    'is_system' => in_array($role->name, ['super-admin', 'operator', 'warehouse', 'support', 'sales']),
                    'created_at' => $role->created_at->format('M d, Y'),
                ];
            });

        // Get role names for filter dropdown
        $allRoles = Role::pluck('name')->toArray();

        // Get system users with optional filtering
        $query = User::whereIn('type', [
            User::USER_TYPE_ADMIN,
            User::USER_TYPE_OPERATOR,
            User::USER_TYPE_WAREHOUSE,
            User::USER_TYPE_SUPPORT,
            User::USER_TYPE_SALES,
        ])->with('roles');

        // Apply search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Apply role filter
        if ($role = $request->get('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $role));
        }

        // Apply status filter
        if ($request->has('status') && $request->get('status') !== '') {
            $query->where('is_active', $request->get('status'));
        }

        $users = $query->latest()
            ->paginate(10)
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'type' => $user->type,
                    'type_label' => $this->getUserTypeLabel($user->type),
                    'role' => $user->getRoleName(),
                    'is_active' => (bool) $user->is_active,
                    'status' => $user->is_active ? 'Active' : 'Inactive',
                    'created_at' => $user->created_at->format('M d, Y'),
                ];
            });

        return Inertia::render('Admin/UserManagement/Index', [
            'activeTab' => $activeTab,
            'users' => $users,
            'roles' => $roles,
            'allRoles' => $allRoles,
            'filters' => $request->only(['search', 'role', 'status']),
        ]);
    }

    /**
     * Get user type label.
     */
    private function getUserTypeLabel(int $type): string
    {
        return match ($type) {
            User::USER_TYPE_ADMIN => 'Admin',
            User::USER_TYPE_OPERATOR => 'Operator',
            User::USER_TYPE_WAREHOUSE => 'Warehouse',
            User::USER_TYPE_SUPPORT => 'Support',
            User::USER_TYPE_SALES => 'Sales',
            default => 'Unknown',
        };
    }
}
