<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";

const props = defineProps({
    activeTab: {
        type: String,
        default: 'users'
    },
    users: Object,
    roles: Array,
    allRoles: Array,
    filters: Object,
});

const currentView = ref(props.activeTab || 'users');
const search = ref(props.filters?.search || '');
const selectedRole = ref(props.filters?.role || '');
const selectedStatus = ref(props.filters?.status || '');
let searchTimeout = null;

// -- Actions --

const switchView = (view) => {
    currentView.value = view;
    // Update URL without reload to keep state but allow sharing/bookmarking
    router.get(route('admin.user-management.index'), { tab: view }, { 
        preserveState: true, 
        preserveScroll: true,
        replace: true 
    });
};

const applyFilters = () => {
    router.get(route('admin.user-management.index'), {
        tab: 'users',
        search: search.value || undefined,
        role: selectedRole.value || undefined,
        status: selectedStatus.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const debounceSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
};

const clearFilters = () => {
    search.value = '';
    selectedRole.value = '';
    selectedStatus.value = '';
    applyFilters();
};

const toggleUserStatus = (user) => {
    router.put(route('admin.system-users.toggle-status', { user: user.id }));
};

const confirmDeleteUser = (user) => {
    if (confirm(`Are you sure you want to delete "${user.name}"?`)) {
        router.delete(route('admin.system-users.delete', { user: user.id }));
    }
};

const confirmDeleteRole = (role) => {
    if (role.users_count > 0) return;
    if (confirm(`Are you sure you want to delete the role "${role.name}"?`)) {
        router.delete(route('admin.roles.delete', { role: role.id }));
    }
};

// -- Helpers --

const formatNumber = (num) => new Intl.NumberFormat('en-US').format(num || 0);

const getRoleBadgeStyle = (roleName) => {
    const styles = {
        'super-admin': 'bg-purple-100 text-purple-700 border-purple-200',
        'operator': 'bg-blue-100 text-blue-700 border-blue-200',
        'warehouse': 'bg-orange-100 text-orange-700 border-orange-200',
        'support': 'bg-green-100 text-green-700 border-green-200',
        'sales': 'bg-rose-100 text-rose-700 border-rose-200',
    };
    return styles[roleName] || 'bg-gray-100 text-gray-700 border-gray-200';
};

// -- Computed Stats --
const totalUsers = computed(() => props.users?.total || 0);
const activeUsers = computed(() => props.users?.data?.filter(u => u.is_active).length || 0);
const totalRoles = computed(() => props.roles?.length || 0);
const systemRoles = computed(() => props.roles?.filter(r => r.is_system).length || 0);

</script>

<template>
    <Head title="User Management" />
    <AuthenticatedLayout>
        <div class="space-y-8 max-w-[1600px] mx-auto">
            
            <!-- Header & Stats Row -->
            <div class="space-y-6">
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Access Control</h1>
                        <p class="text-gray-500 mt-1 text-lg">Manage your team, define roles, and secure your platform.</p>
                    </div>
                </div>

                <!-- Premium Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Stat Card 1 -->
                    <div class="bg-white/60 backdrop-blur-md border border-white/20 shadow-sm rounded-2xl p-5 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <i class="fa-solid fa-users text-4xl text-blue-600"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-500">Total System Users</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ formatNumber(totalUsers) }}</p>
                        <div class="mt-4 flex items-center gap-2 text-xs font-medium text-blue-600 bg-blue-50 w-fit px-2 py-1 rounded-lg">
                            <i class="fa-solid fa-chart-line"></i> Active Directory
                        </div>
                    </div>

                    <!-- Stat Card 2 -->
                    <div class="bg-white/60 backdrop-blur-md border border-white/20 shadow-sm rounded-2xl p-5 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <i class="fa-solid fa-user-check text-4xl text-emerald-600"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-500">Active Sessions</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ formatNumber(activeUsers) }}</p>
                         <div class="mt-4 flex items-center gap-2 text-xs font-medium text-emerald-600 bg-emerald-50 w-fit px-2 py-1 rounded-lg">
                            <i class="fa-solid fa-circle-check"></i> Verified Access
                        </div>
                    </div>

                    <!-- Stat Card 3 -->
                    <div class="bg-white/60 backdrop-blur-md border border-white/20 shadow-sm rounded-2xl p-5 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <i class="fa-solid fa-shield-halved text-4xl text-purple-600"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-500">Defined Roles</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ formatNumber(totalRoles) }}</p>
                        <div class="mt-4 flex items-center gap-2 text-xs font-medium text-purple-600 bg-purple-50 w-fit px-2 py-1 rounded-lg">
                            <i class="fa-solid fa-layer-group"></i> Permission Sets
                        </div>
                    </div>

                    <!-- Stat Card 4 -->
                    <div class="bg-white/60 backdrop-blur-md border border-white/20 shadow-sm rounded-2xl p-5 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                            <i class="fa-solid fa-lock text-4xl text-rose-600"></i>
                        </div>
                        <p class="text-sm font-medium text-gray-500">Admin Privileges</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ formatNumber(systemRoles) }}</p>
                        <div class="mt-4 flex items-center gap-2 text-xs font-medium text-rose-600 bg-rose-50 w-fit px-2 py-1 rounded-lg">
                            <i class="fa-solid fa-key"></i> System Critical
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Control Area -->
            <div>
                <!-- Segmented Control Navigation -->
                <div class="flex justify-center mb-8">
                    <div class="bg-gray-100/80 p-1.5 rounded-full inline-flex items-center relative shadow-inner">
                         <!-- Sliding Background (Visual Only - simplified implementation) -->
                        <button 
                            @click="switchView('users')"
                            :class="['px-8 py-2.5 rounded-full text-sm font-semibold transition-all duration-300 flex items-center gap-2', 
                                currentView === 'users' ? 'bg-white shadow-md text-gray-900' : 'text-gray-500 hover:text-gray-700']"
                        >
                            <i class="fa-solid fa-users"></i> System Users
                        </button>
                        <button 
                            @click="switchView('roles')"
                             :class="['px-8 py-2.5 rounded-full text-sm font-semibold transition-all duration-300 flex items-center gap-2', 
                                currentView === 'roles' ? 'bg-white shadow-md text-gray-900' : 'text-gray-500 hover:text-gray-700']"
                        >
                            <i class="fa-solid fa-shield-halved"></i> Roles & Access
                        </button>
                    </div>
                </div>

                <!-- Users View -->
                <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-if="currentView === 'users'" class="space-y-6">
                        <!-- Toolbar -->
                        <div class="flex flex-col lg:flex-row gap-4 items-center justify-between bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
                            <div class="flex flex-1 w-full gap-3">
                                <div class="relative flex-1 max-w-md">
                                    <i class="fa-solid fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                    <input 
                                        type="text" 
                                        v-model="search"
                                        @input="debounceSearch"
                                        placeholder="Find user by name or email..." 
                                        class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border-transparent focus:bg-white focus:border-blue-500 focus:ring-0 rounded-lg transition-all"
                                    >
                                </div>
                                <select v-model="selectedRole" @change="applyFilters" class="bg-gray-50 border-transparent focus:bg-white focus:border-blue-500 rounded-lg py-2.5 px-4 text-sm text-gray-600">
                                    <option value="">All Roles</option>
                                    <option v-for="role in allRoles" :key="role" :value="role">{{ role }}</option>
                                </select>
                                <button @click="clearFilters" v-if="search || selectedRole || selectedStatus" class="text-gray-400 hover:text-gray-600 px-3">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                            <Link :href="route('admin.system-users.create')" class="btn bg-gray-900 hover:bg-black text-white px-6 py-2.5 rounded-lg font-medium shadow-lg shadow-gray-200 transition-all flex items-center gap-2">
                                <i class="fa-solid fa-plus"></i> Add User
                            </Link>
                        </div>

                        <!-- Rich Table -->
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50/50">
                                        <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Identity</th>
                                        <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Access Role</th>
                                        <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Account Status</th>
                                        <th class="px-6 py-4 font-semibold text-xs text-gray-500 uppercase tracking-wider">Joined</th>
                                        <th class="px-6 py-4 text-right font-semibold text-xs text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    <tr v-for="user in users.data" :key="user.id" class="group hover:bg-gray-50/50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-4">
                                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 flex items-center justify-center text-blue-700 font-bold text-sm shadow-sm ring-2 ring-white">
                                                    {{ user.name.charAt(0).toUpperCase() }}
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-gray-900">{{ user.name }}</p>
                                                    <p class="text-sm text-gray-500">{{ user.email }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span :class="['px-3 py-1 rounded-full text-xs font-semibold border', getRoleBadgeStyle(user.role)]">
                                                {{ user.role }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                             <button 
                                                @click="toggleUserStatus(user)"
                                                :class="['flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium border transition-all', user.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-gray-50 text-gray-500 border-gray-100']"
                                            >
                                                <span :class="['w-2 h-2 rounded-full', user.is_active ? 'bg-emerald-500' : 'bg-gray-400']"></span>
                                                {{ user.is_active ? 'Active' : 'Inactive' }}
                                            </button>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ user.created_at }}
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="opacity-0 group-hover:opacity-100 transition-opacity flex justify-end gap-2">
                                                <Link :href="route('admin.system-users.edit', { user: user.id })" class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg">
                                                    <i class="fa-solid fa-pen"></i>
                                                </Link>
                                                <button @click="confirmDeleteUser(user)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!users.data.length">
                                        <td colspan="5" class="px-6 py-12 text-center">
                                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-50 mb-4">
                                                <i class="fa-solid fa-users-slash text-2xl text-gray-400"></i>
                                            </div>
                                            <h3 class="text-lg font-medium text-gray-900">No users found</h3>
                                            <p class="text-gray-500 mt-1">Try adjusting your filters or add a new user.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                         <!-- Pagination -->
                        <div v-if="users?.links?.length > 3" class="flex justify-center mt-6">
                             <div class="flex gap-1 bg-white p-1 rounded-xl shadow-sm border border-gray-100">
                                <template v-for="link in users.links" :key="link.label">
                                    <Link v-if="link.url" :href="link.url" v-html="link.label" :class="['px-3 py-2 text-xs font-medium rounded-lg transition-colors', link.active ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-50']" />
                                    <span v-else v-html="link.label" class="px-3 py-2 text-xs text-gray-400"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </transition>

                <!-- Roles View -->
                <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
                    <div v-if="currentView === 'roles'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        
                        <!-- Create New Role Card -->
                        <Link :href="route('admin.roles.create')" class="group border-2 border-dashed border-gray-300 hover:border-blue-500 rounded-2xl p-6 flex flex-col items-center justify-center min-h-[220px] transition-all bg-gray-50 hover:bg-blue-50/50">
                            <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform duration-300 mb-4">
                                <i class="fa-solid fa-plus text-2xl text-blue-500"></i>
                            </div>
                            <h3 class="font-bold text-gray-900 group-hover:text-blue-600 transition-colors">Create Custom Role</h3>
                            <p class="text-xs text-center text-gray-500 mt-2 px-4">Define granular permissions for specialized staff.</p>
                        </Link>

                        <!-- Role Cards -->
                        <div v-for="role in roles" :key="role.id" class="group bg-white rounded-2xl border border-gray-200 p-6 hover:shadow-lg hover:border-blue-200 transition-all duration-300 relative">
                             <!-- System Badge -->
                            <div v-if="role.is_system" class="absolute top-4 right-4 text-xs font-bold text-gray-400 bg-gray-100 px-2 py-1 rounded">
                                SYSTEM
                            </div>

                            <div class="mb-5">
                                <h3 class="text-xl font-bold text-gray-900">{{ role.name }}</h3>
                                <p class="text-sm text-gray-500 mt-1 line-clamp-2 h-10">{{ role.description || `Custom role with ${role.permissions_count} active permissions.` }}</p>
                            </div>

                            <div class="flex items-center justify-between mt-6 pt-6 border-t border-gray-100">
                                <div class="flex -space-x-2">
                                     <!-- Mock Users Avatars (Visual Only) -->
                                    <div v-if="role.users_count > 0" class="w-8 h-8 rounded-full bg-gray-200 border-2 border-white flex items-center justify-center text-xs font-medium text-gray-600">
                                        {{ role.users_count }}
                                    </div>
                                    <div v-else class="text-xs text-gray-400 italic pl-1">No users assigned</div>
                                </div>
                                
                                <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <Link :href="route('admin.roles.edit', { role: role.id })" class="p-2 bg-gray-50 hover:bg-blue-50 text-gray-600 hover:text-blue-600 rounded-lg transition-colors">
                                        <i class="fa-solid fa-pen"></i>
                                    </Link>
                                    <button 
                                        @click="confirmDeleteRole(role)" 
                                        :disabled="role.users_count > 0 || role.is_system" 
                                        :class="['p-2 rounded-lg transition-colors', (role.users_count > 0 || role.is_system) ? 'bg-gray-50 text-gray-300 cursor-not-allowed' : 'bg-gray-50 hover:bg-red-50 text-gray-600 hover:text-red-600']"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </transition>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
