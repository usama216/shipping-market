<script setup>
/**
 * Shipment Requests - Warehouse Operator View
 * 
 * Two tabs: Actionable Items (Ready to Pack + Failed) and Awaiting Pickup
 * Simple status → action workflow for operators
 */
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import { Head, router, Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    shipments: Object,
    counts: Object,
    currentTab: String,
    filters: Object,
});

// State
const searchQuery = ref(props.filters?.search || '');
const isLoading = ref(false);

// Switch tab
const switchTab = (tab) => {
    isLoading.value = true;
    router.get(route('admin.shipments.requests'), {
        tab,
        search: searchQuery.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => isLoading.value = false,
    });
};

// Apply search filter
const applySearch = () => {
    isLoading.value = true;
    router.get(route('admin.shipments.requests'), {
        tab: props.currentTab,
        search: searchQuery.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => isLoading.value = false,
    });
};

// Clear search
const clearSearch = () => {
    searchQuery.value = '';
    applySearch();
};

// Mark as packed
const markPacked = (shipment) => {
    if (!confirm('Mark this shipment as packed?')) return;
    router.post(route('admin.shipments.markPacked', shipment.id), {}, {
        preserveScroll: true,
    });
};

// Mark as picked up
const markPickedUp = (shipment) => {
    if (!confirm('Mark this shipment as picked up by carrier?')) return;
    router.post(route('admin.shipments.markPickedUp', shipment.id), {}, {
        preserveScroll: true,
    });
};

// Get shipment category (for display)
const getCategory = (shipment) => {
    if (shipment.carrier_status === 'failed') return 'failed';
    if (shipment.packed_at) return 'awaiting_pickup';
    if (shipment.carrier_status === 'submitted' && shipment.label_data) return 'ready_to_pack';
    return 'processing';
};

// Format date simply
const formatDate = (date) => {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
    });
};

// Days since request (for priority)
const getDaysAgo = (date) => {
    if (!date) return 0;
    const created = new Date(date);
    const now = new Date();
    return Math.floor((now - created) / (1000 * 60 * 60 * 24));
};

// Format destination
const formatDestination = (address) => {
    if (!address) return { city: 'Not set', country: '' };
    return {
        city: address.city || 'Unknown',
        country: address.country || ''
    };
};

// Get carrier display
const getCarrierLabel = (carrierService) => {
    if (!carrierService) return 'Not selected';
    return carrierService.display_name || carrierService.carrier_code?.toUpperCase() || 'Carrier';
};

// Debounce search
let searchTimeout;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applySearch, 500);
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Shipment Requests" />

        <div class="min-h-screen py-6 bg-gray-50">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="flex items-center gap-3 text-2xl font-bold text-gray-900">
                            <i class="fa-solid fa-clipboard-list text-orange-500"></i>
                            Shipment Requests
                        </h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Operator actions for shipments
                        </p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex gap-2 p-1 mb-6 bg-white border rounded-xl shadow-sm">
                    <button
                        @click="switchTab('actionable')"
                        :class="[
                            'flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg font-medium transition-all',
                            currentTab === 'actionable' 
                                ? 'bg-orange-500 text-white shadow-sm' 
                                : 'text-gray-600 hover:bg-gray-50'
                        ]"
                    >
                        <i class="fa-solid fa-box-open"></i>
                        Actionable Items
                        <span v-if="counts?.actionable" :class="[
                            'px-2 py-0.5 rounded-full text-sm',
                            currentTab === 'actionable' ? 'bg-white/20' : 'bg-orange-100 text-orange-700'
                        ]">
                            {{ counts.actionable }}
                        </span>
                    </button>
                    <button
                        @click="switchTab('awaiting_pickup')"
                        :class="[
                            'flex-1 flex items-center justify-center gap-2 px-4 py-3 rounded-lg font-medium transition-all',
                            currentTab === 'awaiting_pickup' 
                                ? 'bg-blue-500 text-white shadow-sm' 
                                : 'text-gray-600 hover:bg-gray-50'
                        ]"
                    >
                        <i class="fa-solid fa-truck-loading"></i>
                        Awaiting Pickup
                        <span v-if="counts?.awaiting_pickup" :class="[
                            'px-2 py-0.5 rounded-full text-sm',
                            currentTab === 'awaiting_pickup' ? 'bg-white/20' : 'bg-blue-100 text-blue-700'
                        ]">
                            {{ counts.awaiting_pickup }}
                        </span>
                    </button>
                </div>

                <!-- Sub-counts for Actionable tab -->
                <div v-if="currentTab === 'actionable'" class="flex gap-4 mb-4">
                    <span class="flex items-center gap-2 px-3 py-1.5 bg-orange-100 text-orange-700 rounded-lg text-sm font-medium">
                        <i class="fa-solid fa-tag"></i>
                        Ready to Pack: {{ counts?.ready_to_pack || 0 }}
                    </span>
                    <span class="flex items-center gap-2 px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-sm font-medium">
                        <i class="fa-solid fa-exclamation-triangle"></i>
                        Failed: {{ counts?.failed || 0 }}
                    </span>
                </div>

                <!-- Search Bar -->
                <div class="flex items-center gap-4 p-4 mb-6 bg-white border rounded-lg shadow-sm">
                    <div class="flex-1">
                        <div class="relative">
                            <i class="absolute text-gray-400 transform -translate-y-1/2 fa-solid fa-search left-3 top-1/2"></i>
                            <input
                                v-model="searchQuery"
                                @input="onSearchInput"
                                type="text"
                                placeholder="Search by customer name, suite #, or tracking..."
                                class="w-full py-2.5 pl-10 pr-4 text-sm border border-gray-300 rounded-lg focus:ring-orange-500 focus:border-orange-500"
                            />
                        </div>
                    </div>
                    <button
                        v-if="searchQuery"
                        @click="clearSearch"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200"
                    >
                        <i class="mr-1 fa-solid fa-times"></i>
                        Clear
                    </button>
                    <div v-if="isLoading" class="text-orange-500">
                        <i class="fa-solid fa-spinner fa-spin"></i>
                    </div>
                </div>

                <!-- Requests Table -->
                <div class="overflow-hidden bg-white border rounded-xl shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase">Status</th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase">Customer</th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase">Packages</th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase">Weight</th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase">Destination</th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase">Carrier</th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-left text-gray-600 uppercase">Requested</th>
                                    <th class="px-4 py-3 text-xs font-semibold tracking-wider text-center text-gray-600 uppercase">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr 
                                    v-for="shipment in shipments?.data" 
                                    :key="shipment.id"
                                    :class="[
                                        'hover:bg-orange-50/50 transition-colors',
                                        getCategory(shipment) === 'failed' ? 'bg-red-50/30' : '',
                                        getDaysAgo(shipment.created_at) >= 2 && getCategory(shipment) !== 'failed' ? 'bg-yellow-50/30' : ''
                                    ]"
                                >
                                    <!-- Status Badge -->
                                    <td class="px-4 py-4">
                                        <span v-if="getCategory(shipment) === 'ready_to_pack'" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-orange-100 text-orange-700 rounded-lg text-xs font-medium">
                                            <i class="fa-solid fa-tag"></i>
                                            Ready to Pack
                                        </span>
                                        <span v-else-if="getCategory(shipment) === 'failed'" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-red-100 text-red-700 rounded-lg text-xs font-medium">
                                            <i class="fa-solid fa-exclamation-triangle"></i>
                                            Failed
                                        </span>
                                        <span v-else-if="getCategory(shipment) === 'awaiting_pickup'" class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-100 text-blue-700 rounded-lg text-xs font-medium">
                                            <i class="fa-solid fa-truck-loading"></i>
                                            Awaiting Pickup
                                        </span>
                                        <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 text-gray-600 rounded-lg text-xs font-medium">
                                            <i class="fa-solid fa-spinner fa-spin"></i>
                                            Processing
                                        </span>
                                    </td>

                                    <!-- Customer -->
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="font-semibold text-gray-900">{{ shipment.customer?.name || '—' }}</span>
                                            <span v-if="shipment.customer?.suite" class="text-sm text-orange-600 font-medium">
                                                <i class="mr-1 fa-solid fa-inbox text-orange-400"></i>
                                                {{ shipment.customer.suite }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Packages Count -->
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col gap-1">
                                            <span class="text-lg font-bold text-gray-900">
                                                {{ shipment.packages?.length || 0 }}
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                {{ (shipment.packages || []).reduce((sum, p) => sum + (p.items?.length || 0), 0) }} items
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Weight -->
                                    <td class="px-4 py-4">
                                        <span class="text-lg font-semibold text-gray-900">
                                            {{ shipment.total_weight || 0 }}
                                        </span>
                                        <span class="text-sm text-gray-500 ml-1">lbs</span>
                                    </td>

                                    <!-- Destination -->
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="font-medium text-gray-900">{{ formatDestination(shipment.customer_address).city }}</span>
                                            <span class="text-xs text-gray-500">{{ formatDestination(shipment.customer_address).country }}</span>
                                        </div>
                                    </td>

                                    <!-- Carrier -->
                                    <td class="px-4 py-4">
                                        <span :class="[
                                            'px-2.5 py-1 rounded-lg text-sm font-medium',
                                            shipment.carrier_service ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-500'
                                        ]">
                                            {{ getCarrierLabel(shipment.carrier_service) }}
                                        </span>
                                    </td>

                                    <!-- Date Requested -->
                                    <td class="px-4 py-4">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="text-sm text-gray-900">{{ formatDate(shipment.created_at) }}</span>
                                            <span :class="[
                                                'text-xs font-medium',
                                                getDaysAgo(shipment.created_at) >= 2 ? 'text-red-600' : 
                                                getDaysAgo(shipment.created_at) >= 1 ? 'text-orange-600' : 'text-gray-400'
                                            ]">
                                                {{ getDaysAgo(shipment.created_at) === 0 ? 'Today' : 
                                                   getDaysAgo(shipment.created_at) === 1 ? '1 day ago' : 
                                                   `${getDaysAgo(shipment.created_at)} days ago` }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Action -->
                                    <td class="px-4 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Ready to Pack → Mark Packed -->
                                            <button 
                                                v-if="getCategory(shipment) === 'ready_to_pack'"
                                                @click="markPacked(shipment)"
                                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-orange-500 rounded-lg hover:bg-orange-600 transition-colors"
                                            >
                                                <i class="fa-solid fa-check"></i>
                                                Mark Packed
                                            </button>

                                            <!-- Failed → Retry / Enter Manually -->
                                            <template v-if="getCategory(shipment) === 'failed'">
                                                <Link 
                                                    :href="route('admin.shipments.tracking', shipment.id)"
                                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-red-500 rounded-lg hover:bg-red-600 transition-colors"
                                                >
                                                    <i class="fa-solid fa-rotate"></i>
                                                    Retry
                                                </Link>
                                                <Link 
                                                    :href="route('admin.shipments.tracking', shipment.id)"
                                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                                >
                                                    <i class="fa-solid fa-pen"></i>
                                                    Manual
                                                </Link>
                                            </template>

                                            <!-- Awaiting Pickup → Mark Picked Up -->
                                            <button 
                                                v-if="getCategory(shipment) === 'awaiting_pickup'"
                                                @click="markPickedUp(shipment)"
                                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-500 rounded-lg hover:bg-blue-600 transition-colors"
                                            >
                                                <i class="fa-solid fa-truck"></i>
                                                Mark Picked Up
                                            </button>

                                            <!-- Print Label (for ready to pack and awaiting pickup) -->
                                            <a 
                                                v-if="shipment.label_data && (getCategory(shipment) === 'ready_to_pack' || getCategory(shipment) === 'awaiting_pickup')"
                                                :href="route('admin.shipments.label.view', shipment.id)"
                                                target="_blank"
                                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                            >
                                                <i class="fa-solid fa-print"></i>
                                                Print Label
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Empty State -->
                                <tr v-if="!shipments?.data?.length">
                                    <td colspan="8" class="px-6 py-16 text-center">
                                        <i class="mb-4 text-5xl text-green-200 fa-solid fa-check-circle"></i>
                                        <p class="text-lg font-medium text-gray-500">All caught up!</p>
                                        <p class="mt-1 text-sm text-gray-400">
                                            {{ searchQuery ? 'No requests match your search' : 
                                               currentTab === 'actionable' ? 'No actionable shipments at the moment' : 
                                               'No shipments awaiting pickup' }}
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="shipments?.data?.length" class="px-4 py-3 bg-gray-50 border-t">
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
