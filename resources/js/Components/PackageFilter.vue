<template>
    <div class="mb-6">
        <!-- Compact Filter Bar -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-3">
            <div class="flex flex-wrap items-center gap-3">
                
                <!-- Unified Search -->
                <div class="relative flex-1 min-w-[200px]">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input
                        type="text"
                        v-model="search"
                        @input="debouncedFilter"
                        placeholder="Search suite, tracking, customer..."
                        class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                    />
                </div>

                <!-- Status Pills -->
                <div class="flex items-center gap-1 bg-gray-100 p-1 rounded-lg">
                    <button 
                        v-for="status in statusOptions" 
                        :key="status.value"
                        @click="toggleStatus(status.value)"
                        :class="[
                            'px-3 py-1.5 text-xs font-medium rounded-md transition-all whitespace-nowrap',
                            filters.status == status.value 
                                ? `${status.activeBg} ${status.activeText} shadow-sm` 
                                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50'
                        ]"
                    >
                        <i :class="[status.icon, 'mr-1']"></i>
                        {{ status.label }}
                    </button>
                </div>

                <!-- Customer Dropdown (Searchable) -->
                <div class="min-w-[180px]">
                    <SearchableSelect
                        v-model="filters.customer_id"
                        :options="customers"
                        label="suite"
                        :reduce="(c) => c.id"
                        placeholder="All Customers"
                        class="filter-select"
                        @update:modelValue="applyFilters"
                    >
                        <template #option="{ suite, name }">
                            <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded mr-2">{{ suite }}</span>
                            <span class="text-sm">{{ name }}</span>
                        </template>
                        <template #selected-option="{ suite, name }">
                            <span class="font-mono text-xs">{{ suite }}</span>
                        </template>
                    </SearchableSelect>
                </div>

                <!-- Date Range (Simplified) -->
                <div class="flex items-center gap-2">
                    <input
                        type="date"
                        v-model="filters.date_from"
                        @change="applyFilters"
                        class="text-xs border border-gray-200 rounded-lg px-2 py-2 focus:ring-2 focus:ring-blue-500"
                        title="From Date"
                    />
                    <span class="text-gray-400 text-xs">to</span>
                    <input
                        type="date"
                        v-model="filters.date_to"
                        @change="applyFilters"
                        class="text-xs border border-gray-200 rounded-lg px-2 py-2 focus:ring-2 focus:ring-blue-500"
                        title="To Date"
                    />
                </div>

                <!-- Clear Button -->
                <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="flex items-center gap-1 px-3 py-2 text-xs font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all"
                >
                    <i class="fas fa-times"></i>
                    Clear
                </button>
            </div>
        </div>

        <!-- Active Filter Pills -->
        <div v-if="hasActiveFilters" class="flex flex-wrap items-center gap-2 mt-3">
            <span class="text-xs text-gray-500 font-medium">Filters:</span>
            
            <span 
                v-for="(value, key) in activeFilters" 
                :key="key"
                class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-full"
            >
                {{ getFilterLabel(key, value) }}
                <button @click="removeFilter(key)" class="hover:text-blue-900 ml-1">
                    <i class="fas fa-times text-[10px]"></i>
                </button>
            </span>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import SearchableSelect from "vue-select";

// Simple debounce function (replaces @vueuse/core dependency)
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const props = defineProps({
    customers: { type: Array, default: () => [] },
    suites: { type: Array, default: () => [] },
    currentFilters: { type: Object, default: () => ({}) },
});

// Status options with styling
const statusOptions = [
    { value: '', label: 'All', icon: 'fas fa-layer-group', activeBg: 'bg-gray-200', activeText: 'text-gray-700' },
    { value: '1', label: 'Action', icon: 'fas fa-exclamation-circle', activeBg: 'bg-red-100', activeText: 'text-red-700' },
    { value: '2', label: 'Review', icon: 'fas fa-search', activeBg: 'bg-yellow-100', activeText: 'text-yellow-700' },
    { value: '3', label: 'Ready', icon: 'fas fa-paper-plane', activeBg: 'bg-blue-100', activeText: 'text-blue-700' },
];

const search = ref("");
const filters = ref({
    status: "",
    date_from: "",
    date_to: "",
    customer_id: "",
    suite: "",
    tracking_id: "",
});

// Watch for incoming filters
watch(
    () => props.currentFilters,
    (newFilters) => {
        Object.keys(filters.value).forEach((key) => {
            if (newFilters[key] !== undefined) {
                filters.value[key] = newFilters[key];
            }
        });
        // Populate search from tracking_id or suite
        if (newFilters.tracking_id) search.value = newFilters.tracking_id;
        else if (newFilters.suite) search.value = newFilters.suite;
    },
    { immediate: true }
);

const hasActiveFilters = computed(() => {
    return search.value || Object.values(filters.value).some((v) => v !== "" && v !== null);
});

const activeFilters = computed(() => {
    const active = {};
    if (search.value) active.search = search.value;
    Object.entries(filters.value).forEach(([key, value]) => {
        if (value !== "" && value !== null) {
            active[key] = value;
        }
    });
    return active;
});

const getFilterLabel = (key, value) => {
    const labels = {
        search: `"${value}"`,
        status: getStatusName(value),
        date_from: `From: ${value}`,
        date_to: `To: ${value}`,
        customer_id: getCustomerName(value),
        suite: `Suite: ${value}`,
        tracking_id: `Track: ${value}`,
    };
    return labels[key] || value;
};

const getStatusName = (status) => {
    const names = { '1': 'Action Required', '2': 'In Review', '3': 'Ready to Send', '4': 'Consolidated' };
    return names[status] || 'All';
};

const getCustomerName = (id) => {
    const customer = props.customers.find((c) => c.id == id);
    return customer ? customer.suite : id;
};

const toggleStatus = (status) => {
    filters.value.status = filters.value.status == status ? '' : status;
    applyFilters();
};

const removeFilter = (key) => {
    if (key === 'search') {
        search.value = '';
        filters.value.tracking_id = '';
        filters.value.suite = '';
    } else {
        filters.value[key] = "";
    }
    applyFilters();
};

const clearFilters = () => {
    search.value = "";
    Object.keys(filters.value).forEach((key) => {
        filters.value[key] = "";
    });
    applyFilters();
};

const applyFilters = () => {
    const cleanFilters = {};
    
    // Handle unified search - search in both tracking_id and suite
    if (search.value) {
        cleanFilters.tracking_id = search.value;
    }
    
    Object.entries(filters.value).forEach(([key, value]) => {
        if (value !== "" && value !== null) {
            cleanFilters[key] = value;
        }
    });

    router.get(route("admin.packages"), cleanFilters, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Debounced search
const debouncedFilter = debounce(() => {
    applyFilters();
}, 400);
</script>

<style>
/* vue-select customization for compact look */
.filter-select .vs__dropdown-toggle {
    @apply border-gray-200 rounded-lg py-1.5 text-sm min-h-[38px];
}
.filter-select .vs__search::placeholder {
    @apply text-gray-400 text-sm;
}
.filter-select .vs__selected {
    @apply text-sm m-0 p-0;
}
.filter-select .vs__clear {
    @apply text-gray-400;
}
</style>
