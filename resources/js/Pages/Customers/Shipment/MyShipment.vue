<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    shipments: Array,
});

// Filter state
const activeFilter = ref('all');

const filters = [
    { key: 'all', label: 'All', icon: 'fa-boxes-stacked' },
    { key: 'in_progress', label: 'In Progress', icon: 'fa-truck' },
    { key: 'delivered', label: 'Delivered', icon: 'fa-check-circle' },
];

const filteredShipments = computed(() => {
    if (activeFilter.value === 'all') return props.shipments;
    
    return props.shipments?.filter(s => {
        if (activeFilter.value === 'in_progress') {
            // All non-delivered statuses are "In Progress"
            return !['delivered', 'cancelled'].includes(s.status);
        }
        return s.status === activeFilter.value;
    });
});

const getStatusConfig = (status) => {
    // Simplified status display for customers - only "In Progress" or "Delivered"
    if (status === 'delivered') {
        return { label: 'Delivered', color: 'bg-green-100 text-green-700 border-green-200', icon: 'fa-check-circle' };
    }
    // All other statuses (including cancelled/failed) show as "In Progress"
    return { label: 'In Progress', color: 'bg-blue-100 text-blue-700 border-blue-200', icon: 'fa-truck' };
};

const getCarrierName = (shipment) => {
    const carrier = shipment.carrier_name || shipment.carrier_service?.carrier_code;
    if (!carrier) return null;
    const names = { fedex: 'FedEx', dhl: 'DHL', ups: 'UPS' };
    return names[carrier.toLowerCase()] || carrier.toUpperCase();
};

const getTrackingUrl = (shipment) => {
    const tracking = shipment.carrier_tracking_number;
    if (!tracking) return null;
    const carrier = shipment.carrier_name;
    const urls = {
        fedex: `https://www.fedex.com/fedextrack/?trknbr=${tracking}`,
        dhl: `https://www.dhl.com/en/express/tracking.html?AWB=${tracking}`,
        ups: `https://www.ups.com/track?tracknum=${tracking}`,
    };
    return urls[carrier] || null;
};

const getDestination = (shipment) => {
    const addr = shipment.customer_address || shipment.user_address;
    if (!addr) return 'N/A';
    return `${addr.city || ''}, ${addr.country || addr.country_code || ''}`.replace(/^, |, $/g, '') || 'N/A';
};

const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric' 
    });
};

const formatCurrency = (amount) => 
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);

// Get shipment counts per filter
const filterCounts = computed(() => {
    const counts = { all: props.shipments?.length || 0 };
    filters.forEach(f => {
        if (f.key === 'all') return;
        counts[f.key] = props.shipments?.filter(s => {
            if (f.key === 'in_progress') return !['delivered', 'cancelled'].includes(s.status);
            return s.status === f.key;
        }).length || 0;
    });
    return counts;
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="My Shipments" />

        <div class="min-h-screen bg-gray-50 py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                        <i class="fa-solid fa-boxes-stacked text-primary-500"></i>
                        My Shipments
                    </h1>
                    <p class="text-sm text-gray-500 mt-1">Track and manage your shipments</p>
                </div>

                <!-- Filter Tabs -->
                <div class="flex flex-wrap gap-2 mb-6">
                    <button
                        v-for="filter in filters"
                        :key="filter.key"
                        @click="activeFilter = filter.key"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all"
                        :class="activeFilter === filter.key 
                            ? 'bg-primary-500 text-white shadow-md' 
                            : 'bg-white text-gray-600 hover:bg-gray-100 border'"
                    >
                        <i :class="['fa-solid', filter.icon]"></i>
                        {{ filter.label }}
                        <span 
                            class="px-2 py-0.5 rounded-full text-xs"
                            :class="activeFilter === filter.key ? 'bg-white/20' : 'bg-gray-100'"
                        >
                            {{ filterCounts[filter.key] }}
                        </span>
                    </button>
                </div>

                <!-- Shipments List -->
                <div v-if="filteredShipments?.length" class="space-y-4">
                    <div 
                        v-for="shipment in filteredShipments" 
                        :key="shipment.id"
                        class="bg-white rounded-xl border shadow-sm hover:shadow-md transition-all overflow-hidden"
                    >
                        <!-- Card Header -->
                        <div class="p-4 sm:p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                                <!-- Tracking & Carrier -->
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-bold text-gray-900">
                                            #{{ shipment.tracking_number || shipment.id }}
                                        </span>
                                        <span 
                                            v-if="getCarrierName(shipment)"
                                            class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-600 font-medium"
                                        >
                                            {{ getCarrierName(shipment) }}
                                        </span>
                                    </div>
                                    <p v-if="shipment.carrier_tracking_number" class="text-sm text-blue-600 font-medium">
                                        <i class="fa-solid fa-barcode mr-1"></i>
                                        {{ shipment.carrier_tracking_number }}
                                    </p>
                                </div>
                                
                                <!-- Status Badge -->
                                <span 
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold border"
                                    :class="getStatusConfig(shipment.status).color"
                                >
                                    <i :class="['fa-solid', getStatusConfig(shipment.status).icon]"></i>
                                    {{ getStatusConfig(shipment.status).label }}
                                </span>
                            </div>

                            <!-- Details Row -->
                            <div class="flex flex-wrap gap-4 text-sm text-gray-600 mb-4">
                                <span class="flex items-center gap-1">
                                    <i class="fa-solid fa-box text-gray-400"></i>
                                    {{ shipment.packages?.length || 0 }} package{{ (shipment.packages?.length || 0) !== 1 ? 's' : '' }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="fa-solid fa-weight-scale text-gray-400"></i>
                                    {{ shipment.total_weight || 0 }} lbs
                                </span>
                                <span class="flex items-center gap-1 font-medium text-gray-900">
                                    {{ formatCurrency(shipment.estimated_shipping_charges) }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i class="fa-solid fa-location-dot text-gray-400"></i>
                                    {{ getDestination(shipment) }}
                                </span>
                            </div>

                            <!-- Status Message - Only show for delivered -->
                            <div 
                                v-if="shipment.status === 'delivered'"
                                class="flex items-center gap-2 text-sm text-green-600 bg-green-50 px-3 py-2 rounded-lg"
                            >
                                <i class="fa-solid fa-check-circle"></i>
                                <span>Delivered on {{ formatDate(shipment.delivered_at || shipment.updated_at) }}</span>
                            </div>
                        </div>

                        <!-- Card Footer -->
                        <div class="px-4 sm:px-5 py-3 bg-gray-50 border-t flex flex-wrap items-center justify-between gap-3">
                            <span class="text-xs text-gray-500">
                                Created {{ formatDate(shipment.created_at) }}
                            </span>
                            <div class="flex gap-2">
                                <Link
                                    :href="route('customer.shipment.details', { ship: shipment.id })"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-primary-600 bg-primary-50 rounded-lg hover:bg-primary-100 transition"
                                >
                                    <i class="fa-solid fa-eye mr-1.5"></i>
                                    View Details
                                </Link>
                                <a
                                    v-if="getTrackingUrl(shipment)"
                                    :href="getTrackingUrl(shipment)"
                                    target="_blank"
                                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-white border rounded-lg hover:bg-gray-50 transition"
                                >
                                    <i class="fa-solid fa-external-link mr-1.5"></i>
                                    Track
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div 
                    v-else 
                    class="bg-white rounded-xl border shadow-sm p-12 text-center"
                >
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gray-100 mb-4">
                        <i class="fa-solid fa-box-open text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">
                        {{ activeFilter === 'all' ? 'No shipments yet' : 'No shipments found' }}
                    </h3>
                    <p class="text-gray-500 mb-6">
                        {{ activeFilter === 'all' 
                            ? 'Once you create a shipment from Ready to Send, it will appear here.' 
                            : 'Try changing your filter to see more shipments.' }}
                    </p>
                    <button
                        v-if="activeFilter !== 'all'"
                        @click="activeFilter = 'all'"
                        class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition"
                    >
                        <i class="fa-solid fa-filter-circle-xmark mr-2"></i>
                        Clear Filter
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
