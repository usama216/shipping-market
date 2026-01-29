<script setup>
/**
 * Unified Shipments Dashboard
 * 
 * Modern dashboard combining shipment management with tracking integration.
 * Features: stats bar, status filters, search, expandable rows with tracking.
 */
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import { Head, router, Link } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import { useToast } from "vue-toastification";
import { usePermissions } from "@/Composables/usePermissions";

const { can } = usePermissions();
const toast = useToast();

const props = defineProps({
    shipments: Object,
    stats: Object,
    statusOptions: Array,
    statusGroups: Object,
    carrierServices: Array,
    filters: Object,
});

// State
const expandedRow = ref(null);
const searchQuery = ref(props.filters?.search || '');
const selectedCategory = ref(props.filters?.category || '');
const isLoading = ref(false);

// Simplified category tabs with counts - aligned with operator workflow
const categoryTabs = computed(() => {
    const cats = props.stats?.categories || {};
    return [
        { key: '', label: 'All', count: props.stats?.total || 0, icon: 'fa-list', color: 'gray' },
        { key: 'ready_to_prepare', label: 'Ready to Prepare', count: cats.ready_to_prepare || 0, icon: 'fa-box-open', color: 'orange' },
        { key: 'ready_to_pack', label: 'Ready to Pack', count: cats.ready_to_pack || 0, icon: 'fa-tag', color: 'teal' },
        { key: 'awaiting_pickup', label: 'Awaiting Pickup', count: cats.awaiting_pickup || 0, icon: 'fa-truck-loading', color: 'blue' },
        { key: 'in_transit', label: 'In Transit', count: cats.in_transit || 0, icon: 'fa-truck', color: 'purple' },
        { key: 'completed', label: 'Completed', count: cats.completed || 0, icon: 'fa-check-circle', color: 'green' },
        { key: 'needs_attention', label: 'Needs Attention', count: cats.needs_attention || 0, icon: 'fa-exclamation-triangle', color: 'red' },
    ];
});

// Toggle row expansion
const toggleRow = (id) => {
    expandedRow.value = expandedRow.value === id ? null : id;
};

// Apply filters
const applyFilters = () => {
    isLoading.value = true;
    router.get(route('admin.shipments'), {
        search: searchQuery.value || undefined,
        category: selectedCategory.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => isLoading.value = false,
    });
};

// Filter by category from tab
const filterByCategory = (category) => {
    selectedCategory.value = category;
    applyFilters();
};

// Clear filters
const clearFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = '';
    applyFilters();
};

// Update status
const updateStatus = (shipment, newStatus) => {
    router.post(route('admin.shipments.update-status', shipment.id), { status: newStatus }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success(`Status updated to ${newStatus}`),
        onError: () => toast.error('Failed to update status'),
    });
};

// Get operator-friendly status display using the new operator_status attribute
const getStatusDisplay = (shipment) => {
    const operatorStatus = shipment.operator_status || shipment.status;
    // Map operator_status to display info
    const categoryMap = {
        ready_to_pack: { label: 'Ready to Pack', icon: 'fa-tag', color: 'teal' },
        awaiting_pickup: { label: 'Awaiting Pickup', icon: 'fa-truck-loading', color: 'blue' },
        paid: { label: 'Ready to Prepare', icon: 'fa-box-open', color: 'orange' },
        processing: { label: 'Processing', icon: 'fa-cog', color: 'blue' },
        picked_up: { label: 'In Transit', icon: 'fa-truck-pickup', color: 'purple' },
        shipped: { label: 'In Transit', icon: 'fa-truck', color: 'purple' },
        in_transit: { label: 'In Transit', icon: 'fa-truck-fast', color: 'purple' },
        out_for_delivery: { label: 'Out for Delivery', icon: 'fa-truck-ramp-box', color: 'purple' },
        customs_pending: { label: 'Customs Pending', icon: 'fa-passport', color: 'purple' },
        customs_cleared: { label: 'Customs Cleared', icon: 'fa-check', color: 'purple' },
        delivered: { label: 'Completed', icon: 'fa-check-circle', color: 'green' },
        failed: { label: 'Needs Attention', icon: 'fa-exclamation-triangle', color: 'red' },
        cancelled: { label: 'Cancelled', icon: 'fa-times-circle', color: 'red' },
        returned: { label: 'Returned', icon: 'fa-undo', color: 'red' },
        on_hold: { label: 'On Hold', icon: 'fa-pause-circle', color: 'red' },
        customs_hold: { label: 'Customs Hold', icon: 'fa-ban', color: 'red' },
    };
    return categoryMap[operatorStatus] || { label: operatorStatus, icon: 'fa-box', color: 'gray' };
};

// Get status badge classes
const getStatusClass = (shipment) => {
    const display = getStatusDisplay(shipment);
    const colorClasses = {
        orange: 'bg-orange-100 text-orange-700 border-orange-200',
        teal: 'bg-teal-100 text-teal-700 border-teal-200',
        blue: 'bg-blue-100 text-blue-700 border-blue-200',
        purple: 'bg-purple-100 text-purple-700 border-purple-200',
        green: 'bg-green-100 text-green-700 border-green-200',
        red: 'bg-red-100 text-red-700 border-red-200',
        gray: 'bg-gray-100 text-gray-700 border-gray-200',
    };
    return colorClasses[display.color] || colorClasses.gray;
};

// Format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);
};

// Format date
const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

// Debounce search
let searchTimeout;
watch(searchQuery, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 500);
});

// Get shipment age in days (for urgency highlighting)
const getShipmentAge = (createdAt) => {
    if (!createdAt) return 0;
    const created = new Date(createdAt);
    const now = new Date();
    const diffTime = Math.abs(now - created);
    return Math.floor(diffTime / (1000 * 60 * 60 * 24));
};

// Format age for display
const formatAge = (createdAt) => {
    const days = getShipmentAge(createdAt);
    if (days === 0) return 'Today';
    if (days === 1) return '1d ago';
    return `${days}d ago`;
};

// Get carrier icon and color
const getCarrierDisplay = (carrierService) => {
    const code = carrierService?.carrier_code?.toLowerCase() || 'other';
    const carriers = {
        fedex: { icon: 'fa-box', color: 'text-purple-600 bg-purple-100', label: 'FedEx' },
        dhl: { icon: 'fa-plane', color: 'text-yellow-600 bg-yellow-100', label: 'DHL' },
        ups: { icon: 'fa-truck', color: 'text-amber-700 bg-amber-100', label: 'UPS' },
        usps: { icon: 'fa-envelope', color: 'text-blue-600 bg-blue-100', label: 'USPS' },
    };
    return carriers[code] || { icon: 'fa-shipping-fast', color: 'text-gray-600 bg-gray-100', label: carrierService?.display_name || 'Carrier' };
};

// Format destination (City, State, Country)
const formatDestination = (shipment) => {
    const addr = shipment.customer_address;
    if (!addr) return { city: 'Not set', region: '' };
    const city = addr.city || '';
    const state = addr.state || addr.province || '';
    const country = addr.country || '';
    return {
        city: city || 'Unknown',
        region: [state, country].filter(Boolean).join(', ')
    };
};

// Check if row should show urgency (needs action > 1 day)
const isUrgent = (shipment) => {
    const urgentStatuses = ['paid', 'failed', 'on_hold'];
    return urgentStatuses.includes(shipment.status) && getShipmentAge(shipment.created_at) >= 1;
};

// Process shipment - update status to processing (carrier job will run automatically)
const processShipment = (shipment) => {
    updateStatus(shipment, 'processing');
};

// Mark as packed (operator action for ready_to_pack shipments)
const markPacked = (shipment) => {
    if (!confirm('Mark this shipment as packed?')) return;
    router.post(route('admin.shipments.markPacked', shipment.id), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Shipment marked as packed'),
        onError: (errors) => toast.error(errors?.message || 'Failed to mark as packed'),
    });
};

// Mark as picked up (operator action for awaiting_pickup shipments)
const markPickedUp = (shipment) => {
    if (!confirm('Mark this shipment as picked up by carrier?')) return;
    router.post(route('admin.shipments.markPickedUp', shipment.id), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Shipment marked as picked up'),
        onError: (errors) => toast.error(errors?.message || 'Failed to mark as picked up'),
    });
};

// Retry carrier submission for failed shipments
const retryCarrier = (shipment) => {
    router.post(route('admin.shipments.retryCarrier', shipment.id), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Shipment resubmitted to carrier'),
        onError: (errors) => toast.error(errors?.message || 'Failed to submit. Try manual tracking.'),
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Shipments Dashboard" />

        <div class="min-h-screen py-6 bg-gray-50">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="flex items-center gap-3 text-2xl font-bold text-gray-900">
                            <i class="fa-solid fa-truck-fast text-primary-500"></i>
                            All Shipments
                        </h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Manage and track all shipments
                        </p>
                    </div>
                </div>

                <!-- Category Tabs (Simplified Filter) -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <button
                        v-for="tab in categoryTabs"
                        :key="tab.key"
                        @click="filterByCategory(tab.key)"
                        :class="[
                            'flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium transition-all',
                            selectedCategory === tab.key
                                ? `bg-${tab.color}-500 text-white shadow-md`
                                : `bg-white text-gray-600 border hover:bg-gray-50`
                        ]"
                    >
                        <i :class="['fa-solid', tab.icon]"></i>
                        {{ tab.label }}
                        <span :class="[
                            'px-2 py-0.5 text-xs rounded-full',
                            selectedCategory === tab.key ? 'bg-white/20' : 'bg-gray-100'
                        ]">
                            {{ tab.count }}
                        </span>
                    </button>
                </div>

                <!-- Search Bar (Simple) -->
                <div class="flex items-center gap-4 p-4 mb-6 bg-white border rounded-lg shadow-sm">
                    <div class="flex-1">
                        <div class="relative">
                            <i class="absolute text-gray-400 transform -translate-y-1/2 fa-solid fa-search left-3 top-1/2"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search by tracking #, customer name, suite..."
                                class="w-full py-2.5 pl-10 pr-4 text-sm border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                            />
                        </div>
                    </div>

                    <!-- Clear Filters -->
                    <button
                        v-if="searchQuery || selectedCategory"
                        @click="clearFilters"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200"
                    >
                        <i class="mr-1 fa-solid fa-times"></i>
                        Clear
                    </button>

                    <!-- Loading indicator -->
                    <div v-if="isLoading" class="text-primary-500">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                    </div>
                </div>

                <!-- Shipments Table -->
                <div class="overflow-hidden bg-white border rounded-xl shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="w-10 px-3 py-3"></th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase">Shipment</th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase">Customer</th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase">Destination</th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase">Carrier</th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase">Status</th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-center text-gray-600 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <template v-for="shipment in shipments?.data" :key="shipment.id">
                                    <!-- Main Row -->
                                    <tr 
                                        @click="toggleRow(shipment.id)" 
                                        :class="[
                                            'cursor-pointer transition-colors',
                                            expandedRow === shipment.id ? 'bg-primary-50' : 'hover:bg-gray-50',
                                            isUrgent(shipment) ? 'bg-orange-50/50 hover:bg-orange-50' : ''
                                        ]"
                                    >
                                        <!-- Expand Toggle -->
                                        <td class="px-3 py-4">
                                            <button class="text-gray-400 hover:text-gray-600">
                                                <i :class="['fa-solid text-sm', expandedRow === shipment.id ? 'fa-chevron-down' : 'fa-chevron-right']"></i>
                                            </button>
                                        </td>

                                        <!-- Shipment Info -->
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col gap-0.5">
                                                <Link 
                                                    v-if="can('shipments.view')"
                                                    :href="route('admin.shipments.tracking', shipment.id)"
                                                    class="font-semibold text-primary-600 hover:text-primary-800 hover:underline"
                                                    title="View Tracking Details"
                                                >
                                                    {{ shipment.tracking_number || `#${shipment.id}` }}
                                                    <i class="ml-1 text-xs fa-solid fa-arrow-up-right-from-square"></i>
                                                </Link>
                                                <span v-else class="font-semibold text-gray-900">
                                                    {{ shipment.tracking_number || `#${shipment.id}` }}
                                                </span>
                                                <span v-if="shipment.carrier_tracking_number" class="text-xs text-gray-500 font-mono">
                                                    {{ shipment.carrier_tracking_number }}
                                                </span>
                                                <div class="flex items-center gap-2 text-xs text-gray-400">
                                                    <span>{{ formatDate(shipment.created_at) }}</span>
                                                    <span class="px-1.5 py-0.5 bg-gray-100 rounded text-gray-500">{{ shipment.packages_count ?? shipment.packages?.length ?? 0 }} pkg</span>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Customer -->
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col gap-0.5">
                                                <span class="font-medium text-gray-900 truncate max-w-32">{{ shipment.customer?.name || '—' }}</span>
                                                <span v-if="shipment.customer?.suite" class="text-xs text-gray-500">
                                                    <i class="mr-1 fa-solid fa-inbox text-gray-400"></i>{{ shipment.customer.suite }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Destination -->
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col gap-0.5">
                                                <span class="font-medium text-gray-900">{{ formatDestination(shipment).city }}</span>
                                                <span class="text-xs text-gray-500">{{ formatDestination(shipment).region }}</span>
                                            </div>
                                        </td>

                                        <!-- Carrier -->
                                        <td class="px-4 py-4">
                                            <div v-if="shipment.carrier_service" class="flex items-center gap-2">
                                                <span :class="['inline-flex items-center justify-center w-8 h-8 rounded-lg', getCarrierDisplay(shipment.carrier_service).color]">
                                                    <i :class="['fa-solid', getCarrierDisplay(shipment.carrier_service).icon]"></i>
                                                </span>
                                                <div class="flex flex-col">
                                                    <span class="text-sm font-medium text-gray-900">{{ getCarrierDisplay(shipment.carrier_service).label }}</span>
                                                    <span class="text-xs text-gray-500">{{ formatCurrency(shipment.total_price) }}</span>
                                                </div>
                                            </div>
                                            <div v-else class="flex items-center gap-2 text-gray-400">
                                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100">
                                                    <i class="fa-solid fa-question text-gray-400"></i>
                                                </span>
                                                <span class="text-sm">Not assigned</span>
                                            </div>
                                        </td>

                                        <!-- Status with Age -->
                                        <td class="px-4 py-4">
                                            <div class="flex flex-col gap-1">
                                                <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium border w-fit', getStatusClass(shipment)]">
                                                    <i :class="['fa-solid', getStatusDisplay(shipment).icon]"></i>
                                                    {{ getStatusDisplay(shipment).label }}
                                                </span>
                                                <span :class="['text-xs', isUrgent(shipment) ? 'text-orange-600 font-medium' : 'text-gray-400']">
                                                    {{ formatAge(shipment.created_at) }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- Contextual Actions -->
                                        <td class="px-4 py-4" @click.stop>
                                            <div class="flex items-center justify-center gap-1.5">
                                                <!-- Ready to Prepare: Prepare Shipment -->
                                                <button 
                                                    v-if="shipment.operator_status === 'paid' && can('shipments.status.update')"
                                                    @click="processShipment(shipment)"
                                                    class="px-2.5 py-1.5 text-xs font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-colors"
                                                    title="Prepare this shipment for pickup"
                                                >
                                                    <i class="mr-1 fa-solid fa-box-open"></i>
                                                    Prepare
                                                </button>

                                                <!-- Ready to Pack: Mark Packed + Print Label -->
                                                <template v-else-if="shipment.operator_status === 'ready_to_pack'">
                                                    <button 
                                                        v-if="can('shipments.update')"
                                                        @click="markPacked(shipment)"
                                                        class="px-2.5 py-1.5 text-xs font-medium text-white bg-teal-500 rounded-lg hover:bg-teal-600 transition-colors"
                                                        title="Mark shipment as packed"
                                                    >
                                                        <i class="mr-1 fa-solid fa-check"></i>
                                                        Mark Packed
                                                    </button>
                                                    <a 
                                                        v-if="shipment.label_data || shipment.label_url"
                                                        :href="shipment.label_url || route('admin.shipments.label.view', shipment.id)"
                                                        target="_blank"
                                                        class="px-2.5 py-1.5 text-xs font-medium text-teal-600 bg-teal-50 rounded-lg hover:bg-teal-100 transition-colors"
                                                        title="Print Shipping Label"
                                                    >
                                                        <i class="mr-1 fa-solid fa-print"></i>
                                                        Print
                                                    </a>
                                                </template>

                                                <!-- Awaiting Pickup: Mark Picked Up + Print Label -->
                                                <template v-else-if="shipment.operator_status === 'awaiting_pickup'">
                                                    <button 
                                                        v-if="can('shipments.update')"
                                                        @click="markPickedUp(shipment)"
                                                        class="px-2.5 py-1.5 text-xs font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors"
                                                        title="Carrier has picked up this shipment"
                                                    >
                                                        <i class="mr-1 fa-solid fa-truck"></i>
                                                        Picked Up
                                                    </button>
                                                    <a 
                                                        v-if="shipment.label_data || shipment.label_url"
                                                        :href="shipment.label_url || route('admin.shipments.label.view', shipment.id)"
                                                        target="_blank"
                                                        class="px-2.5 py-1.5 text-xs font-medium text-blue-600 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
                                                        title="Print Shipping Label"
                                                    >
                                                        <i class="mr-1 fa-solid fa-print"></i>
                                                        Print
                                                    </a>
                                                </template>

                                                <!-- In Transit: Track -->
                                                <a 
                                                    v-else-if="['picked_up', 'shipped', 'in_transit', 'out_for_delivery'].includes(shipment.status) && shipment.carrier_tracking_number"
                                                    :href="`https://www.google.com/search?q=${shipment.carrier_tracking_number}`"
                                                    target="_blank"
                                                    class="px-2.5 py-1.5 text-xs font-medium text-purple-600 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors"
                                                    title="Track Shipment"
                                                >
                                                    <i class="mr-1 fa-solid fa-location-dot"></i>
                                                    Track
                                                </a>

                                                <!-- Failed (operator_status or carrier_status): Retry or Manual Entry -->
                                                <template v-else-if="shipment.operator_status === 'failed' || shipment.carrier_status === 'failed'">
                                                    <button 
                                                        v-if="can('shipments.status.update')"
                                                        @click="retryCarrier(shipment)"
                                                        class="px-2.5 py-1.5 text-xs font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 transition-colors"
                                                        title="Retry Carrier Submission"
                                                    >
                                                        <i class="mr-1 fa-solid fa-rotate"></i>
                                                        Retry
                                                    </button>
                                                    <Link 
                                                        :href="route('admin.shipments.tracking', shipment.id)"
                                                        class="px-2.5 py-1.5 text-xs font-medium text-amber-600 bg-amber-50 rounded-lg hover:bg-amber-100 transition-colors"
                                                        title="Add Manual Tracking"
                                                    >
                                                        <i class="mr-1 fa-solid fa-keyboard"></i>
                                                        Manual
                                                    </Link>
                                                </template>

                                                <!-- Always show Track and Edit -->
                                                <Link 
                                                    v-if="can('shipments.view')"
                                                    :href="route('admin.shipments.tracking', shipment.id)"
                                                    class="px-3 py-1.5 text-xs font-medium text-primary-600 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100 transition-colors"
                                                    title="View Full Tracking Details"
                                                >
                                                    <i class="mr-1 fa-solid fa-location-crosshairs"></i>
                                                    Track
                                                </Link>
                                                <Link 
                                                    v-if="can('shipments.update')"
                                                    :href="route('admin.shipments.edit', shipment.id)"
                                                    class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                                    title="Edit"
                                                >
                                                    <i class="fa-solid fa-pen"></i>
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Expanded Details Row (Simplified) -->
                                    <tr v-if="expandedRow === shipment.id" class="bg-gray-50/80">
                                        <td colspan="7" class="px-6 py-4">
                                            <div class="flex items-center justify-between mb-4">
                                                <h3 class="text-lg font-semibold text-gray-900">Shipment Details</h3>
                                                <Link 
                                                    v-if="can('shipments.view')"
                                                    :href="route('admin.shipments.tracking', shipment.id)"
                                                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors"
                                                >
                                                    <i class="fa-solid fa-location-crosshairs"></i>
                                                    View Full Tracking
                                                </Link>
                                            </div>
                                            <div class="grid gap-4 md:grid-cols-3">
                                                <!-- Packages -->
                                                <div class="p-4 bg-white rounded-xl border">
                                                    <h4 class="flex items-center gap-2 mb-3 text-sm font-semibold text-gray-900">
                                                        <i class="fa-solid fa-box text-primary-500"></i>
                                                        Packages ({{ shipment.packages?.length || 0 }})
                                                    </h4>
                                                    <div v-if="shipment.packages?.length" class="space-y-2">
                                                        <div v-for="pkg in shipment.packages.slice(0, 4)" :key="pkg.id" 
                                                             class="flex flex-col p-2.5 text-sm bg-gray-50 rounded-lg border border-gray-100">
                                                            <div class="flex items-center justify-between mb-1">
                                                                <span class="font-mono text-xs font-bold text-primary-600">PKG-{{ pkg.id }}</span>
                                                                <span class="font-medium text-gray-900">{{ pkg.total_weight || 0 }} lbs</span>
                                                            </div>
                                                            <div class="mb-1 text-xs text-gray-600 truncate" :title="pkg.from">
                                                                <i class="mr-1 text-gray-400 fa-solid fa-store"></i>{{ pkg.from || 'Unknown Sender' }}
                                                            </div>
                                                            <div v-if="pkg.store_tracking_id" class="flex items-center justify-between">
                                                                <span class="text-[10px] text-gray-400 font-mono truncate max-w-24" :title="pkg.store_tracking_id">
                                                                    {{ pkg.store_tracking_id }}
                                                                </span>
                                                                <span class="text-xs text-gray-500">{{ pkg.items?.length || 0 }} items</span>
                                                            </div>
                                                            <div v-else class="flex justify-end">
                                                                <span class="text-xs text-gray-500">{{ pkg.items?.length || 0 }} items</span>
                                                            </div>
                                                        </div>
                                                        <Link 
                                                            v-if="shipment.packages.length > 4"
                                                            :href="route('admin.shipments.packages', shipment.id)"
                                                            class="block text-center text-xs text-primary-600 hover:text-primary-700 py-1"
                                                        >
                                                            +{{ shipment.packages.length - 4 }} more packages →
                                                        </Link>
                                                    </div>
                                                    <p v-else class="text-sm text-gray-500">No packages</p>
                                                </div>

                                                <!-- Billing & Shipping Summary -->
                                                <div class="p-4 bg-white rounded-xl border">
                                                    <h4 class="flex items-center gap-2 mb-3 text-sm font-semibold text-gray-900">
                                                        <i class="fa-solid fa-receipt text-primary-500"></i>
                                                        Billing Summary
                                                    </h4>
                                                    <div class="space-y-2 text-sm">
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-500">Carrier Service:</span>
                                                            <span :class="['font-medium', shipment.carrier_service ? 'text-gray-900' : 'text-gray-400']">{{ shipment.carrier_service?.display_name || 'Not assigned' }}</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-500">Total Weight:</span>
                                                            <span class="font-medium text-gray-900">{{ shipment.total_weight || 0 }} lbs</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-500">Shipping Charges:</span>
                                                            <span class="font-medium text-gray-900">{{ formatCurrency(shipment.estimated_shipping_charges) }}</span>
                                                        </div>
                                                        <div class="flex justify-between pt-2 border-t">
                                                            <span class="text-gray-500">Invoice Status:</span>
                                                            <span :class="['font-medium', shipment.invoice_status === 'paid' ? 'text-green-600' : 'text-yellow-600']">
                                                                {{ shipment.invoice_status || 'Pending' }}
                                                            </span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-500">Total Paid:</span>
                                                            <span class="font-bold text-gray-900">{{ formatCurrency(shipment.total_price) }}</span>
                                                        </div>
                                                    </div>
                                                </div>


                                                <!-- Packing Instructions -->
                                                <div class="p-4 bg-white rounded-xl border">
                                                    <h4 class="flex items-center gap-2 mb-3 text-sm font-semibold text-gray-900">
                                                        <i class="fa-solid fa-tape text-primary-500"></i>
                                                        Packing Instructions
                                                    </h4>
                                                    <div class="space-y-3 text-sm">
                                                        <div v-if="shipment.packing_options?.length">
                                                            <p class="text-xs font-semibold text-gray-500 uppercase">Packing Options</p>
                                                            <ul class="mt-1 space-y-1">
                                                                <li v-for="opt in shipment.packing_options" :key="opt.id" class="flex items-center gap-2">
                                                                    <i class="fa-solid fa-check text-green-500 text-xs"></i>
                                                                    <span>{{ opt.title }}</span>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <div v-if="shipment.shipping_preference_options?.length">
                                                            <p class="text-xs font-semibold text-gray-500 uppercase">Preferences</p>
                                                            <ul class="mt-1 space-y-1">
                                                                <li v-for="opt in shipment.shipping_preference_options" :key="opt.id" class="flex items-center gap-2">
                                                                    <i class="fa-solid fa-check text-blue-500 text-xs"></i>
                                                                    <span>{{ opt.title }}</span>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                        <p v-if="(!shipment.packing_options?.length) && (!shipment.shipping_preference_options?.length)" class="text-gray-400 italic">
                                                            No specific packing instructions
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <!-- Empty State -->
                                <tr v-if="!shipments?.data?.length">
                                    <td colspan="7" class="px-6 py-16 text-center">
                                        <i class="mb-4 text-5xl text-gray-200 fa-solid fa-box-open"></i>
                                        <p class="text-lg font-medium text-gray-500">No shipments found</p>
                                        <p class="mt-1 text-sm text-gray-400">
                                            {{ searchQuery || selectedStatus ? 'Try adjusting your filters' : 'Shipments will appear here when paid' }}
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-4 py-3 bg-gray-50 border-t">
                        <Pagination
                            :links="shipments?.links"
                            :from="shipments?.from"
                            :to="shipments?.to"
                            :total="shipments?.total"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
