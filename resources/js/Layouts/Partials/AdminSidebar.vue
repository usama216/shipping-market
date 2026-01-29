<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { usePermissions } from "@/Composables/usePermissions";
import SidebarItem from "@/Components/SidebarItem.vue";
import SidebarGroup from "@/Components/SidebarGroup.vue";

const props = defineProps({
    isSidebarOpen: Boolean,
    isMobile: Boolean,
    activeDropdown: String,
    toggleDropdown: Function,
    isActiveDropdown: Function,
});

const { can, canAny } = usePermissions();
const page = usePage();

// Permission-based visibility for sidebar sections
const canViewDashboard = computed(() => can('dashboard.view'));
const canViewShipments = computed(() => can('shipments.view'));
const canCreateShipments = computed(() => can('shipments.create'));
const canViewPackages = computed(() => can('packages.view'));
const canCreatePackages = computed(() => can('packages.create'));
const canViewPackageKanban = computed(() => can('packages.kanban.view'));
const canViewCustomers = computed(() => can('customers.view'));
const canViewTransactions = computed(() => can('transactions.view'));
const canViewMarketing = computed(() => canAny(['coupons.view', 'loyalty.view']));
const canViewCoupons = computed(() => can('coupons.view'));
const canViewLoyalty = computed(() => can('loyalty.view'));
const canAccessRoles = computed(() => canAny(['system-users.view', 'roles.view']));
const canViewSystemUsers = computed(() => can('system-users.view'));
// Commission is only accessible by super-admin or admin role
const canViewCommission = computed(() => {
    const isSuperAdmin = page.props.auth?.isSuperAdmin || false;
    const user = page.props.auth?.user;
    if (!user) return false;
    // Check if user is super-admin
    if (isSuperAdmin) return true;
    // Check if user has admin role (check roles relationship if loaded)
    if (user.roles && Array.isArray(user.roles) && user.roles.length > 0) {
        return user.roles.some(role => role.name === 'admin');
    }
    // Fallback: check if role_name property exists
    return user.role_name === 'admin';
});
</script>

<template>
    <aside
        :class="{
            'w-64': isSidebarOpen,
            'w-20': !isSidebarOpen,
            'hidden md:block': false, // Always show unless handled by mobile logic
            '-translate-x-full md:translate-x-0': !isSidebarOpen && isMobile,
            'translate-x-0': isSidebarOpen && isMobile,
        }"
        class="fixed left-0 top-0 h-full pt-[60px] bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 border-r border-slate-800 text-white shadow-2xl z-20 overflow-y-auto overflow-x-hidden transition-all duration-300 ease-in-out md:relative md:h-full md:top-0 scrollbar-thin"
    >
        <nav class="p-3 space-y-1">
            <!-- Main Navigation -->
            <div class="mb-2" :class="{ 'px-4': isSidebarOpen, 'px-0 text-center': !isSidebarOpen }">
                <p 
                    v-if="isSidebarOpen"
                    class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 transition-opacity duration-300"
                >
                    Operations
                </p>
                <div v-else class="h-4 border-b border-slate-700 mb-2 mx-2"></div>
            </div>

            <SidebarItem
                v-if="canViewDashboard"
                :href="route('dashboard')"
                icon="fa-solid fa-gauge-high"
                label="Dashboard"
                :active="route().current('dashboard')"
                :isCollapsed="!isSidebarOpen"
            />

            <!-- Shipments Group -->
            <SidebarGroup
                v-if="canViewShipments"
                label="Shipments"
                icon="fa-solid fa-plane-departure"
                :active="isActiveDropdown('admin.shipments')"
                :isCollapsed="!isSidebarOpen"
            >
                <SidebarItem
                    v-if="canCreateShipments"
                    :href="route('admin.shipments.create')"
                    icon="fa-solid fa-plus"
                    label="Create Shipment"
                    :active="route().current('admin.shipments.create')"
                    :isCollapsed="false"
                />
                <SidebarItem
                    :href="route('admin.shipments.requests')"
                    icon="fa-solid fa-clipboard-list"
                    label="Shipment Requests"
                    :active="route().current('admin.shipments.requests')"
                    :isCollapsed="false" 
                />
                <SidebarItem
                    :href="route('admin.shipments')"
                    icon="fa-solid fa-list-ul"
                    label="All Shipments"
                    :active="route().current('admin.shipments') && !$page.url.includes('/requests') && !$page.url.includes('/create')"
                    :isCollapsed="false" 
                />
            </SidebarGroup>

            <!-- Packages Group -->
            <SidebarGroup
                v-if="canViewPackages"
                label="Packages"
                icon="fa-solid fa-box"
                :active="isActiveDropdown('admin.packages')"
                :isCollapsed="!isSidebarOpen"
            >
                <SidebarItem
                    v-if="canCreatePackages"
                    :href="route('admin.packages.create')"
                    icon="fa-solid fa-plus"
                    label="Create Package"
                    :active="route().current('admin.packages.create')"
                    :isCollapsed="false"
                />
                <SidebarItem
                    :href="route('admin.packages')"
                    icon="fa-solid fa-boxes-stacked"
                    label="All Packages"
                    :active="route().current('admin.packages')"
                    :isCollapsed="false"
                />

            </SidebarGroup>

            <!-- Customers Group -->
            <SidebarGroup
                v-if="canViewCustomers"
                label="Customers"
                icon="fa-solid fa-users"
                :active="isActiveDropdown('admin.customers')"
                :isCollapsed="!isSidebarOpen"
            >
                <SidebarItem
                    :href="route('admin.customers')"
                    icon="fa-solid fa-user-group"
                    label="All Customers"
                    :active="route().current('admin.customers')"
                    :isCollapsed="false"
                />
            </SidebarGroup>

            <SidebarItem
                v-if="canViewTransactions"
                :href="route('admin.transactions.allTransactions')"
                icon="fa-solid fa-receipt"
                label="Transactions"
                :active="route().current('admin.transactions.allTransactions')"
                :isCollapsed="!isSidebarOpen"
            />

            <!-- Marketing Section -->
            <div v-if="canViewMarketing" class="mt-6 mb-2" :class="{ 'px-4': isSidebarOpen, 'px-0 text-center': !isSidebarOpen }">
                <p 
                    v-if="isSidebarOpen"
                    class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2"
                >
                    Growth
                </p>
                <div v-else class="h-4 border-b border-slate-700 mb-2 mx-2"></div>
            </div>

            <SidebarItem
                v-if="canViewCoupons"
                :href="route('admin.coupons.index')"
                icon="fa-solid fa-ticket"
                label="Coupons"
                :active="route().current('admin.coupons')"
                :isCollapsed="!isSidebarOpen"
            />

            <SidebarItem
                v-if="canViewLoyalty"
                :href="route('admin.loyalty.index')"
                icon="fa-solid fa-medal"
                label="Loyalty"
                :active="route().current('admin.loyalty')"
                :isCollapsed="!isSidebarOpen"
            />

            <!-- Access Control Section -->
            <div v-if="canAccessRoles" class="mt-6 mb-2" :class="{ 'px-4': isSidebarOpen, 'px-0 text-center': !isSidebarOpen }">
                <p 
                    v-if="isSidebarOpen"
                    class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2"
                >
                    System
                </p>
                <div v-else class="h-4 border-b border-slate-700 mb-2 mx-2"></div>
            </div>

            <SidebarItem
                v-if="canViewSystemUsers"
                :href="route('admin.user-management.index')"
                icon="fa-solid fa-users-gear"
                label="User Management"
                :active="route().current('admin.user-management.index')"
                :isCollapsed="!isSidebarOpen"
            />

            <SidebarItem
                v-if="canViewCommission"
                :href="route('admin.commission.index')"
                icon="fa-solid fa-percent"
                label="Commission"
                :active="route().current('admin.commission.index')"
                :isCollapsed="!isSidebarOpen"
            />

            <!-- Divider -->
            <div class="my-4 border-t border-slate-700/50 mx-2"></div>

            <SidebarItem
                :href="route('logout')"
                icon="fa-solid fa-arrow-right-from-bracket"
                label="Logout"
                method="post"
                as="button"
                class="text-red-400 hover:text-white hover:bg-red-500/20"
                :isCollapsed="!isSidebarOpen"
            />
        </nav>
    </aside>
</template>

<style scoped>
/* Custom scrollbar for sidebar */
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
}
.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(148, 163, 184, 0.2);
    border-radius: 2px;
}
.scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(148, 163, 184, 0.4);
}
</style>
