<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * RolePermissionSeeder
 * 
 * Seeds all module and sub-module permissions, and creates default system roles.
 * Permissions follow the pattern: {module}.{action} or {module}.{submodule}.{action}
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Define all modules with their sub-modules and actions
     */
    private function getModulePermissions(): array
    {
        return [
            // Dashboard Module
            'dashboard' => [
                'actions' => ['view'],
                'sub_modules' => [
                    'stats' => ['view'],
                    'reports' => ['view', 'export'],
                ]
            ],

            // Package Management Module
            'packages' => [
                'actions' => ['view', 'create', 'update', 'delete'],
                'sub_modules' => [
                    'kanban' => ['view', 'update'],
                    'items' => ['view', 'create', 'update', 'delete'],
                    'files' => ['view', 'upload', 'delete'],
                    'notes' => ['view', 'create', 'update'],
                    'rates' => ['manage'],
                ]
            ],

            // Shipment Module
            'shipments' => [
                'actions' => ['view', 'create', 'update', 'delete'],
                'sub_modules' => [
                    'status' => ['view', 'update'],
                    'outbound' => ['view', 'process'],
                    'labels' => ['view', 'generate', 'download'],
                    'tracking' => ['view', 'refresh', 'event'],
                ]
            ],

            // Transaction Module
            'transactions' => [
                'actions' => ['view'],
                'sub_modules' => [
                    'refunds' => ['view', 'process'],
                ]
            ],

            // Coupon Module
            'coupons' => [
                'actions' => ['view', 'create', 'update', 'delete'],
                'sub_modules' => [
                    'stats' => ['view'],
                    'toggle' => ['update'],
                ]
            ],

            // Loyalty Module
            'loyalty' => [
                'actions' => ['view'],
                'sub_modules' => [
                    'rules' => ['view', 'create', 'update', 'delete'],
                    'transactions' => ['view'],
                    'users' => ['view', 'adjust'],
                    'tiers' => ['view', 'create', 'update', 'delete'],
                    'points' => ['adjust'],
                    'referrals' => ['view'],
                ]
            ],

            // Order Tracking Module
            'order-tracking' => [
                'actions' => ['view'],
                'sub_modules' => [
                    'events' => ['view', 'create'],
                    'refresh' => ['execute'],
                    'carrier' => ['retry', 'manual', 'sync'],
                ]
            ],

            // Change Requests Module
            'change-requests' => [
                'actions' => ['view'],
                'sub_modules' => [
                    'approve' => ['execute'],
                    'reject' => ['execute'],
                    'bulk' => ['execute'],
                ]
            ],

            // Customer Management Module (Admin viewing customers)
            'customers' => [
                'actions' => ['view', 'create', 'update', 'delete'],
                'sub_modules' => [
                    'packages' => ['view'],
                    'transactions' => ['view'],
                    'addresses' => ['view', 'update'],
                    'loyalty' => ['view', 'adjust'],
                ]
            ],

            // System Users Module (Access Control)
            'system-users' => [
                'actions' => ['view', 'create', 'update', 'delete'],
                'sub_modules' => [
                    'status' => ['toggle'],
                    'roles' => ['assign'],
                ]
            ],

            // Role Management Module (Access Control)
            'roles' => [
                'actions' => ['view', 'create', 'update', 'delete'],
                'sub_modules' => [
                    'permissions' => ['view', 'assign'],
                ]
            ],

            // Settings Module
            'settings' => [
                'actions' => ['view', 'update'],
                'sub_modules' => [
                    'shipping-pricing' => ['view', 'update'],
                    'general' => ['view', 'update'],
                ]
            ],

            // Import Module
            'imports' => [
                'actions' => ['view', 'execute'],
            ],
        ];
    }

    /**
     * Define default system roles with their permissions
     */
    private function getDefaultRoles(): array
    {
        return [
            'super-admin' => [
                'description' => 'Full system access with all permissions',
                'is_system' => true,
                'permissions' => '*', // All permissions
            ],
            'operator' => [
                'description' => 'Day-to-day operations management',
                'is_system' => true,
                'permissions' => [
                    'dashboard.*',
                    'packages.*',
                    'shipments.*',
                    'order-tracking.*',
                    'change-requests.*',
                    'customers.view',
                    'customers.packages.view',
                    'customers.transactions.view',
                ],
            ],
            'warehouse' => [
                'description' => 'Warehouse staff for package handling',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'packages.view',
                    'packages.update',
                    'packages.kanban.*',
                    'packages.items.*',
                    'packages.files.*',
                    'shipments.view',
                    'shipments.status.*',
                    'shipments.labels.*',
                ],
            ],
            'support' => [
                'description' => 'Customer support team',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'packages.view',
                    'packages.notes.*',
                    'shipments.view',
                    'shipments.tracking.*',
                    'transactions.view',
                    'customers.view',
                    'customers.packages.view',
                    'customers.transactions.view',
                    'customers.addresses.view',
                    'order-tracking.*',
                    'change-requests.view',
                ],
            ],
            'sales' => [
                'description' => 'Sales team for promotions and customer acquisition',
                'is_system' => true,
                'permissions' => [
                    'dashboard.view',
                    'dashboard.stats.view',
                    'customers.view',
                    'customers.create',
                    'coupons.*',
                    'loyalty.*',
                ],
            ],
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $allPermissions = [];

        // Create all permissions from module definitions
        foreach ($this->getModulePermissions() as $module => $config) {
            // Module-level permissions
            foreach ($config['actions'] as $action) {
                $permissionName = "{$module}.{$action}";
                $allPermissions[] = $permissionName;
                Permission::firstOrCreate(
                    ['name' => $permissionName, 'guard_name' => 'web']
                );
            }

            // Sub-module permissions
            if (isset($config['sub_modules'])) {
                foreach ($config['sub_modules'] as $subModule => $actions) {
                    foreach ($actions as $action) {
                        $permissionName = "{$module}.{$subModule}.{$action}";
                        $allPermissions[] = $permissionName;
                        Permission::firstOrCreate(
                            ['name' => $permissionName, 'guard_name' => 'web']
                        );
                    }
                }
            }
        }

        // Create roles and assign permissions
        foreach ($this->getDefaultRoles() as $roleName => $roleConfig) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );

            // Determine which permissions to assign
            if ($roleConfig['permissions'] === '*') {
                // Super admin gets all permissions
                $role->syncPermissions($allPermissions);
            } else {
                $rolePermissions = [];
                foreach ($roleConfig['permissions'] as $permissionPattern) {
                    if (str_ends_with($permissionPattern, '.*')) {
                        // Wildcard pattern - match all permissions starting with prefix
                        $prefix = rtrim($permissionPattern, '.*');
                        foreach ($allPermissions as $permission) {
                            if (str_starts_with($permission, $prefix)) {
                                $rolePermissions[] = $permission;
                            }
                        }
                    } else {
                        // Exact permission
                        if (in_array($permissionPattern, $allPermissions)) {
                            $rolePermissions[] = $permissionPattern;
                        }
                    }
                }
                $role->syncPermissions(array_unique($rolePermissions));
            }
        }

        $this->command->info('✅ Created ' . count($allPermissions) . ' permissions');
        $this->command->info('✅ Created ' . count($this->getDefaultRoles()) . ' default roles');
    }
}
