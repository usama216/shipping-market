<script setup>
import Alert from "@/Components/Alert.vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted, computed } from "vue";
import AdminSidebar from "./Partials/AdminSidebar.vue";
import CustomerSidebar from "./Partials/CustomerSidebar.vue";

const props = usePage().props;
const isSidebarOpen = ref(window.innerWidth >= 768);
const activeDropdown = ref(null);
const isMobile = ref(window.innerWidth < 768);

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

const toggleDropdown = (dropdownName) => {
    activeDropdown.value =
        activeDropdown.value === dropdownName ? null : dropdownName;
};

const handleResize = () => {
    isMobile.value = window.innerWidth < 768;
    if (!isMobile.value) {
        isSidebarOpen.value = true;
    } else {
        isSidebarOpen.value = false;
    }
};

onMounted(() => {
    window.addEventListener("resize", handleResize);
    handleResize();
});

onUnmounted(() => {
    window.removeEventListener("resize", handleResize);
});

const isActiveDropdown = (prefix) => {
    return route().current().startsWith(prefix);
};

// Mark all notifications as read
const markAllAsRead = () => {
    if (props?.auth?.userType === 'customer') {
        router.post(route('customer.notifications.read-all'), {}, {
            preserveScroll: true,
            onSuccess: () => {
                activeDropdown.value = null;
            }
        });
    }
};
</script>

<template>
    <header
        class="fixed top-0 left-0 right-0 bg-white/90 backdrop-blur-md shadow-sm border-b border-gray-100 z-30 flex items-center justify-between px-4 py-2.5 transition-all duration-300"
    >
        <!-- Left: Logo + Sidebar Toggle -->
        <div class="flex items-center gap-4">
            <!-- Sidebar Toggle Button -->
            <button
                @click="toggleSidebar"
                class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
            >
                <i class="fa-solid fa-bars text-lg"></i>
            </button>

            <!-- Logo -->
            <div class="w-32 transition-all duration-300" :class="{ 'w-8 opacity-0 md:opacity-100': !isSidebarOpen && !isMobile }">
                 <Link href="/">
                    <img
                        src="/assets/image/logo-original.svg"
                        class="h-8 w-auto object-contain"
                        alt="Logo"
                    />
                </Link>
            </div>
            
             <!-- Breadcrumbs Placeholder (Can be dynamic later) -->
             <div class="hidden md:flex items-center text-sm text-slate-500 ml-4 border-l border-slate-200 pl-4 h-6">
                <span class="font-medium text-slate-700">Dashboard</span>
            </div>
        </div>

        <!-- Center: Global Search (Command Bar) - Hidden until implemented -->
        <div v-if="false" class="hidden md:flex flex-1 max-w-xl mx-4">
            <div class="relative w-full group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                     <i class="fa-solid fa-magnifying-glass text-slate-400 group-focus-within:text-primary-500 transition-colors"></i>
                </div>
                <input
                    type="text"
                    class="block w-full pl-10 pr-3 py-2 border border-slate-200 rounded-lg leading-5 bg-slate-50 text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 sm:text-sm transition-all shadow-sm"
                    placeholder="Search packages, customers, or commands... (Cmd+K)"
                />
                 <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <span class="text-xs text-slate-400 border border-slate-200 rounded px-1.5 py-0.5">⌘K</span>
                </div>
            </div>
        </div>

        <!-- Right: Actions -->
        <div class="flex items-center gap-3">

             <!-- Notifications -->
            <div class="relative">
                <button
                    @click="toggleDropdown('notifications')"
                    class="p-2 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors relative"
                >
                    <i class="fa-regular fa-bell text-lg"></i>
                    <span
                        v-if="props?.notifications?.length > 0"
                        class="absolute top-1.5 right-1.5 flex h-2.5 w-2.5"
                    >
                         <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                         <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-red-500"></span>
                    </span>
                </button>

                 <!-- Notifications Dropdown -->
                <div
                    v-if="activeDropdown === 'notifications'"
                    class="absolute right-0 mt-3 w-80 bg-white rounded-xl shadow-xl ring-1 ring-black ring-opacity-5 z-50 transform origin-top-right transition-all"
                >
                     <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center">
                         <h3 class="text-sm font-semibold text-gray-900">Notifications</h3>
                         <div class="flex items-center gap-2">
                             <span v-if="props?.notifications?.length" class="text-xs text-primary-600 bg-primary-50 px-2 py-0.5 rounded-full font-medium">{{ props.notifications.length }} New</span>
                             <button 
                                 v-if="props?.notifications?.length > 0 && props?.auth?.userType === 'customer'"
                                 @click="markAllAsRead"
                                 class="text-xs text-gray-500 hover:text-primary-600 transition-colors"
                             >
                                 Mark all read
                             </button>
                         </div>
                     </div>
                    <ul class="max-h-96 overflow-y-auto py-1">
                        <li
                            v-for="(note, index) in props?.notifications"
                            :key="note.id || index"
                            class="px-4 py-3 hover:bg-slate-50 cursor-pointer border-b border-gray-50 last:border-0 transition-colors"
                        >
                             <div class="flex gap-3">
                                 <div class="mt-1">
                                     <div class="h-2 w-2 rounded-full bg-primary-500"></div>
                                 </div>
                                 <div class="flex-1">
                                     <p class="text-sm text-gray-600">{{ note?.message }}</p>
                                     <p v-if="note?.created_at" class="text-xs text-gray-400 mt-1">{{ note.created_at }}</p>
                                 </div>
                             </div>
                        </li>
                        <li
                            v-if="!props?.notifications || props.notifications.length === 0"
                            class="px-4 py-8 text-center text-gray-400"
                        >
                            <div class="flex flex-col items-center">
                                <i class="fa-regular fa-bell-slash text-2xl mb-2 text-gray-300"></i>
                                <span class="text-sm">No new notifications</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

             <!-- User Menu -->
            <div class="dropdown dropdown-end relative">
                <button tabindex="0" class="flex items-center gap-2 p-1 pl-2 rounded-lg hover:bg-slate-100 transition-colors border border-transparent hover:border-slate-200">
                     <div class="text-right hidden sm:block">
                        <div class="text-xs font-semibold text-slate-700 leading-tight">{{ $page.props.auth.user.name }}</div>
                        <div class="text-[10px] text-slate-500 font-medium uppercase tracking-wide">{{ $page.props.auth.userType }}</div>
                    </div>
                    <img
                        :src="`https://ui-avatars.com/api/?name=${$page.props.auth.user.name}&background=random`"
                        class="w-8 h-8 rounded-lg shadow-sm ring-2 ring-white"
                        alt="Avatar"
                    />
                     <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-1"></i>
                </button>

                <ul
                    tabindex="0"
                    class="dropdown-content menu bg-white rounded-xl z-50 w-60 p-2 shadow-xl ring-1 ring-black ring-opacity-5 mt-2"
                >
                     <div class="px-3 py-3 mb-2 border-b border-gray-100 sm:hidden">
                        <div class="font-semibold text-gray-900">{{ $page.props.auth.user.name }}</div>
                        <div class="text-xs text-gray-500">{{ $page.props.auth.user.email }}</div>
                    </div>
                    
                    <li v-if="$page.props.auth.userType === 'system'">
                        <Link :href="route('profile.edit')" class="rounded-lg py-2 text-sm">
                            <i class="fa-regular fa-id-badge mr-2 text-slate-400"></i> Profile
                        </Link>
                    </li>
                    <li v-if="$page.props.auth.userType === 'customer'">
                        <Link :href="route('customer.account.profile')" class="rounded-lg py-2 text-sm">
                            <i class="fa-regular fa-id-badge mr-2 text-slate-400"></i> Profile
                        </Link>
                    </li>
                    <div class="my-1 border-t border-gray-100"></div>
                    <li>
                        <Link :href="route('logout')" method="post" as="button" class="rounded-lg py-2 text-sm text-red-600 hover:bg-red-50">
                            <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i> Log Out
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <div class="flex pt-[60px] h-screen overflow-hidden bg-slate-50">
        <CustomerSidebar
            v-if="props?.auth.userType === 'customer'"
            :isSidebarOpen="isSidebarOpen"
            :isMobile="isMobile"
            :activeDropdown="activeDropdown"
            :toggleDropdown="toggleDropdown"
            :isActiveDropdown="isActiveDropdown"
        />
        <AdminSidebar
            v-if="props?.auth.userType === 'system'"
            :isSidebarOpen="isSidebarOpen"
            :isMobile="isMobile"
            :activeDropdown="activeDropdown"
            :toggleDropdown="toggleDropdown"
            :isActiveDropdown="isActiveDropdown"
        />
        <main
            :class="{ 
                'md:ml-0': !isSidebarOpen,
                'md:ml-0': isSidebarOpen // Logic handled by sidebar width transition now pushing content
            }"
            class="flex-1 transition-all duration-300 ease-in-out overflow-y-auto scrollable-content p-6"
        >
             <!-- Content Container with max-width for large screens -->
            <div class="max-w-7xl mx-auto w-full">
                 <slot />
            </div>
        </main>
    </div>
    <Alert :pageProps="$page.props" />
</template>

<style>
@import "../../css/custom.css";
@import "@vuepic/vue-datepicker/dist/main.css";
@import "vue-select/dist/vue-select.css";
</style>
