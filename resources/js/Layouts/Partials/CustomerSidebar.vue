<script setup>
import SidebarItem from "@/Components/SidebarItem.vue";

const props = defineProps({
    isSidebarOpen: Boolean,
    isMobile: Boolean,
    activeDropdown: String,
    toggleDropdown: Function,
    isActiveDropdown: Function,
});
</script>

<template>
    <aside
        :class="{
            'w-64': isSidebarOpen,
            'w-20': !isSidebarOpen,
            'hidden md:block': false, // Always show unless handled by mobile toggle logic in parent
            '-translate-x-full md:translate-x-0': !isSidebarOpen && isMobile,
            'translate-x-0': isSidebarOpen && isMobile,
        }"
        class="fixed left-0 top-0 h-full pt-[60px] bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 border-r border-slate-800 text-white shadow-xl z-20 overflow-y-auto transition-all duration-300 ease-in-out md:relative md:h-full md:top-0"
    >
        <nav class="p-3 space-y-1">
            <div class="mb-2" :class="{ 'px-4': isSidebarOpen, 'px-0 text-center': !isSidebarOpen }">
                <p 
                    v-if="isSidebarOpen"
                    class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2 transition-opacity duration-300"
                >
                    Main Menu
                </p>
                <div v-else class="h-4 border-b border-slate-700 mb-2 mx-2"></div>
            </div>

            <SidebarItem
                :href="route('customer.dashboard')"
                icon="fa-solid fa-house"
                label="My Suite"
                :active="$page.url.startsWith('/customer/suite') || route().current('customer.dashboard')"
                :isCollapsed="!isSidebarOpen"
            />
            
            <SidebarItem
                :href="route('customer.shipment.myShipments')"
                icon="fa-solid fa-box-open"
                label="Shipments & Tracking"
                :active="$page.url.startsWith('/customer/shipment/') || $page.url.startsWith('/customer/order-tracking')"
                :isCollapsed="!isSidebarOpen"
            />
            
            <SidebarItem
                :href="route('customer.account.profile')"
                icon="fa-solid fa-gear"
                label="Account Settings"
                :active="$page.url.startsWith('/customer/account-setting')"
                :isCollapsed="!isSidebarOpen"
            />
            
            <SidebarItem
                :href="route('customer.loyalty.index')"
                icon="fa-solid fa-star"
                label="Loyalty Points"
                :active="$page.url.startsWith('/customer/loyalty')"
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
                class="mt-auto text-red-400 hover:text-red-300 hover:bg-red-500/10"
                :isCollapsed="!isSidebarOpen"
            />
        </nav>
    </aside>
</template>
