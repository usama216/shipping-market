<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";

const props = defineProps({
    warehouses: {
        type: Object,
        default: () => ({ data: [] })
    },
    filters: {
        type: Object,
        default: () => ({ search: '' })
    }
});

const search = ref(props.filters.search || '');

const handleSearch = () => {
    router.get(route('admin.warehouses.index'), { search: search.value }, { preserveState: true });
};

const setDefault = (warehouse) => {
    if (confirm(`Set "${warehouse.name}" as the default warehouse?`)) {
        router.put(route('admin.warehouses.set-default', { warehouse: warehouse.id }));
    }
};

const toggleActive = (warehouse) => {
    const action = warehouse.is_active ? 'deactivate' : 'activate';
    if (confirm(`Are you sure you want to ${action} "${warehouse.name}"?`)) {
        router.put(route('admin.warehouses.toggle-active', { warehouse: warehouse.id }));
    }
};

const stats = computed(() => ({
    total: props.warehouses.data?.length || 0,
    active: props.warehouses.data?.filter(w => w.is_active).length || 0,
    inactive: props.warehouses.data?.filter(w => !w.is_active).length || 0,
}));
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Warehouse Management" />
        
        <div class="min-h-screen">
            <!-- Header Section -->
            <div class="mb-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">
                            Warehouse Management
                        </h1>
                        <p class="text-base-content/60 mt-1">
                            Configure warehouse locations for shipping origins
                        </p>
                    </div>
                    <Link 
                        :href="route('admin.warehouses.create')"
                        class="btn btn-primary gap-2 shadow-lg hover:shadow-primary/25 transition-all"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        Add Warehouse
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="stats shadow-lg bg-base-100">
                    <div class="stat">
                        <div class="stat-figure text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div class="stat-title">Total Warehouses</div>
                        <div class="stat-value text-primary">{{ stats.total }}</div>
                    </div>
                </div>
                <div class="stats shadow-lg bg-base-100">
                    <div class="stat">
                        <div class="stat-figure text-success">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="stat-title">Active</div>
                        <div class="stat-value text-success">{{ stats.active }}</div>
                    </div>
                </div>
                <div class="stats shadow-lg bg-base-100">
                    <div class="stat">
                        <div class="stat-figure text-warning">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="stat-title">Inactive</div>
                        <div class="stat-value text-warning">{{ stats.inactive }}</div>
                    </div>
                </div>
            </div>

            <!-- Search -->
            <div class="mb-6">
                <div class="join w-full max-w-md">
                    <input 
                        v-model="search"
                        type="text"
                        placeholder="Search warehouses..."
                        class="input input-bordered join-item w-full"
                        @keyup.enter="handleSearch"
                    />
                    <button @click="handleSearch" class="btn btn-primary join-item">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Warehouses Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="warehouse in warehouses.data" 
                    :key="warehouse.id"
                    class="card bg-base-100 shadow-xl hover:shadow-2xl transition-all duration-300 border border-base-200 hover:border-primary/30"
                    :class="{ 'opacity-60': !warehouse.is_active }"
                >
                    <div class="card-body">
                        <!-- Header -->
                        <div class="flex items-start justify-between">
                            <div>
                                <h2 class="card-title text-lg">
                                    {{ warehouse.name }}
                                    <span class="badge badge-sm font-mono">{{ warehouse.code }}</span>
                                </h2>
                                <div class="flex gap-2 mt-2">
                                    <span 
                                        v-if="warehouse.is_default"
                                        class="badge badge-primary badge-sm gap-1"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        Default
                                    </span>
                                    <span 
                                        :class="warehouse.is_active ? 'badge-success' : 'badge-error'"
                                        class="badge badge-sm"
                                    >
                                        {{ warehouse.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="mt-4 text-sm text-base-content/70">
                            <p>{{ warehouse.address }}</p>
                            <p v-if="warehouse.address_line_2">{{ warehouse.address_line_2 }}</p>
                            <p>{{ warehouse.city }}, {{ warehouse.state }} {{ warehouse.zip }}</p>
                            <p>{{ warehouse.country }}</p>
                            <p v-if="warehouse.phone_number" class="mt-1 text-primary">
                                <i class="fa-solid fa-phone mr-1"></i> {{ warehouse.phone_number }}
                            </p>
                        </div>

                        <!-- Stats -->
                        <div class="flex gap-6 mt-4 pt-4 border-t border-base-200">
                            <div class="text-center">
                                <div class="text-xl font-bold text-primary">{{ warehouse.customers_count || 0 }}</div>
                                <div class="text-xs text-base-content/60">Customers</div>
                            </div>
                            <div class="text-center">
                                <div class="text-xl font-bold text-secondary">{{ warehouse.packages_count || 0 }}</div>
                                <div class="text-xs text-base-content/60">Packages</div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="card-actions justify-end mt-4 pt-4 border-t border-base-200">
                            <button 
                                v-if="!warehouse.is_default && warehouse.is_active"
                                @click="setDefault(warehouse)"
                                class="btn btn-sm btn-ghost text-primary gap-1"
                                title="Set as default"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </button>
                            <Link 
                                :href="route('admin.warehouses.edit', { warehouse: warehouse.id })"
                                class="btn btn-sm btn-ghost gap-1"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Edit
                            </Link>
                            <button 
                                v-if="!warehouse.is_default"
                                @click="toggleActive(warehouse)"
                                :class="warehouse.is_active ? 'text-warning' : 'text-success'"
                                class="btn btn-sm btn-ghost gap-1"
                            >
                                <svg v-if="warehouse.is_active" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ warehouse.is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="warehouses.data?.length === 0" class="text-center py-16">
                <div class="mx-auto w-24 h-24 bg-base-200 rounded-full flex items-center justify-center mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">No warehouses found</h3>
                <p class="text-base-content/60 mb-4">Get started by adding your first warehouse location.</p>
                <Link :href="route('admin.warehouses.create')" class="btn btn-primary">
                    Add Warehouse
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="warehouses.links && warehouses.links.length > 3" class="flex justify-center mt-8">
                <div class="join">
                    <Link 
                        v-for="link in warehouses.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="join-item btn btn-sm"
                        :class="{ 'btn-active': link.active, 'btn-disabled': !link.url }"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
