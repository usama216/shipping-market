/**
 * usePermissions Composable
 * 
 * A Vue 3 composable for checking user permissions in the frontend.
 * Works with the RBAC system by reading permissions from Inertia shared props.
 * 
 * Usage:
 *   import { usePermissions } from '@/Composables/usePermissions';
 *   const { can, canAny, canAll } = usePermissions();
 * 
 *   // In template:
 *   <button v-if="can('packages.create')">Create Package</button>
 *   <div v-if="canAny(['coupons.view', 'loyalty.view'])">Marketing Section</div>
 */

import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();

    // Get permissions array from Inertia shared props
    const permissions = computed(() => page.props.auth?.permissions || []);

    // Check if user is a super-admin (bypasses all permission checks)
    const isSuperAdmin = computed(() => page.props.auth?.isSuperAdmin || false);

    /**
     * Check if user has a specific permission
     * @param {string} permission - Permission name (e.g., 'packages.view')
     * @returns {boolean}
     */
    const can = (permission) => {
        // Super admins have all permissions
        if (isSuperAdmin.value) return true;

        return permissions.value.includes(permission);
    };

    /**
     * Check if user has ANY of the specified permissions
     * @param {string[]} perms - Array of permission names
     * @returns {boolean}
     */
    const canAny = (perms) => {
        if (isSuperAdmin.value) return true;
        return perms.some(p => permissions.value.includes(p));
    };

    /**
     * Check if user has ALL of the specified permissions
     * @param {string[]} perms - Array of permission names
     * @returns {boolean}
     */
    const canAll = (perms) => {
        if (isSuperAdmin.value) return true;
        return perms.every(p => permissions.value.includes(p));
    };

    /**
     * Check if user can access a module (has view permission)
     * @param {string} module - Module name (e.g., 'packages', 'shipments')
     * @returns {boolean}
     */
    const canViewModule = (module) => {
        return can(`${module}.view`);
    };

    /**
     * Check if user can manage a module (has create, update, or delete)
     * @param {string} module - Module name
     * @returns {boolean}
     */
    const canManageModule = (module) => {
        return canAny([
            `${module}.create`,
            `${module}.update`,
            `${module}.delete`
        ]);
    };

    return {
        permissions,
        isSuperAdmin,
        can,
        canAny,
        canAll,
        canViewModule,
        canManageModule
    };
}
