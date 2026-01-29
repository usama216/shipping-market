<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TrackingTimeline from "@/Components/TrackingTimeline.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    ship: Object,
    timeline: Array,
    carrierTrackingUrl: String,
});

const formatCurrency = (amount) => `$${parseFloat(amount || 0).toFixed(2)}`;

const getCarrierDisplayName = computed(() => {
    const carrier = props.ship?.carrier_name;
    const names = {
        fedex: "FedEx",
        dhl: "DHL Express",
        ups: "UPS",
    };
    return names[carrier] || carrier?.toUpperCase() || "N/A";
});

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: "badge-warning",
        shipped: "badge-info",
        delivered: "badge-success",
        cancelled: "badge-error",
    };
    return classes[status] || "badge-ghost";
};

const getProgressPercentage = computed(() => {
    const percentages = {
        pending: 25,
        shipped: 50,
        delivered: 100,
        cancelled: 0,
    };
    return percentages[props.ship?.status] || 0;
});

// Check if label is available
const hasLabel = computed(() => {
    return props.ship?.label_url || props.ship?.label_data;
});
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`Tracking #${ship.tracking_number}`" />

        <div class="p-6 space-y-6 max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div>
                    <Link :href="route('customer.tracking.index')" class="text-primary hover:underline text-sm mb-2 inline-block">
                        <i class="fas fa-arrow-left mr-1"></i> Back to My Orders
                    </Link>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <i class="fa-solid fa-box text-primary-500"></i> Shipment #{{ ship.tracking_number }}
                    </h1>
                </div>
                <span class="badge px-4 py-2 text-sm" :class="getStatusBadgeClass(ship.status)">
                    {{ ship.status?.charAt(0).toUpperCase() + ship.status?.slice(1) }}
                </span>
            </div>

            <!-- Progress Bar Card -->
            <div class="card bg-white shadow-md">
                <div class="card-body">
                    <h2 class="card-title mb-4"><i class="fa-solid fa-route text-primary-500 mr-2"></i> Shipment Progress</h2>
                    <div class="flex justify-between text-sm text-gray-500 mb-2">
                        <span class="font-medium" :class="{ 'text-primary': getProgressPercentage >= 25 }">Pending</span>
                        <span class="font-medium" :class="{ 'text-primary': getProgressPercentage >= 50 }">Shipped</span>
                        <span class="font-medium" :class="{ 'text-primary': getProgressPercentage >= 100 }">Delivered</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div
                            class="bg-primary h-3 rounded-full transition-all duration-700 ease-out"
                            :style="{ width: getProgressPercentage + '%' }"
                        ></div>
                    </div>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-6">
                    <!-- Carrier Tracking Card -->
                    <div class="card bg-white shadow-md">
                        <div class="card-body">
                            <h2 class="card-title"><i class="fa-solid fa-truck text-primary-500 mr-2"></i> Carrier Information</h2>
                            <div class="space-y-3 mt-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Carrier:</span>
                                    <span class="font-medium">{{ getCarrierDisplayName }}</span>
                                </div>
                                <div class="flex justify-between" v-if="ship.carrier_tracking_number">
                                    <span class="text-gray-500">Tracking Number:</span>
                                    <span class="font-mono font-medium">{{ ship.carrier_tracking_number }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Status:</span>
                                    <span class="font-medium capitalize">{{ ship.carrier_status || 'Processing' }}</span>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2 mt-4" v-if="carrierTrackingUrl || hasLabel">
                                <a
                                    v-if="carrierTrackingUrl"
                                    :href="carrierTrackingUrl"
                                    target="_blank"
                                    class="btn btn-primary btn-sm"
                                >
                                    <i class="fas fa-search mr-1"></i>
                                    Track on {{ getCarrierDisplayName }}
                                </a>
                                <Link
                                    v-if="hasLabel"
                                    :href="route('customer.shipment.label.download', { ship: ship.id })"
                                    class="btn btn-outline btn-sm"
                                >
                                    <i class="fas fa-download mr-1"></i>
                                    Download Label
                                </Link>
                            </div>

                            <!-- Processing Message -->
                            <div
                                v-if="ship.invoice_status === 'paid' && !ship.carrier_tracking_number"
                                class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-700 text-sm"
                            >
                                <i class="fas fa-clock mr-1"></i>
                                Your shipment is being processed. Tracking info will appear shortly.
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address Card -->
                    <div class="card bg-white shadow-md" v-if="ship.user_address">
                        <div class="card-body">
                            <h2 class="card-title"><i class="fa-solid fa-location-dot text-primary-500 mr-2"></i> Shipping To</h2>
                            <div class="space-y-1 mt-2">
                                <p class="font-medium">{{ ship.user_address.full_name }}</p>
                                <p>{{ ship.user_address.address_line_1 }}</p>
                                <p v-if="ship.user_address.address_line_2">{{ ship.user_address.address_line_2 }}</p>
                                <p>{{ ship.user_address.city }}, {{ ship.user_address.state }} {{ ship.user_address.postal_code }}</p>
                                <p>{{ ship.user_address.country }}</p>
                                <p class="text-sm text-gray-500"><i class="fa-solid fa-phone mr-1"></i> {{ ship.user_address.phone_number }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Shipment Summary Card -->
                    <div class="card bg-white shadow-md">
                        <div class="card-body">
                            <h2 class="card-title"><i class="fa-solid fa-box-open text-primary-500 mr-2"></i> Shipment Summary</h2>
                            <div class="space-y-3 mt-2">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Total Weight:</span>
                                    <span class="font-medium">{{ ship.total_weight }} kg</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Packages:</span>
                                    <span class="font-medium">{{ ship.packages?.length || 0 }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Shipping Cost:</span>
                                    <span class="font-medium">{{ formatCurrency(ship.estimated_shipping_charges) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Created:</span>
                                    <span class="font-medium">{{ new Date(ship.created_at).toLocaleDateString() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Tracking Timeline -->
                <div>
                    <div class="card bg-white shadow-md h-full">
                        <div class="card-body">
                            <h2 class="card-title"><i class="fa-solid fa-clipboard-list text-primary-500 mr-2"></i> Tracking History</h2>
                            <TrackingTimeline :events="timeline" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Packages List -->
            <div class="card bg-white shadow-md" v-if="ship.packages?.length > 0">
                <div class="card-body">
                    <h2 class="card-title"><i class="fa-solid fa-boxes-stacked text-primary-500 mr-2"></i> Packages in this Shipment</h2>
                    <div class="overflow-x-auto mt-4">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Package ID</th>
                                    <th>From</th>
                                    <th>Weight</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(pkg, index) in ship.packages" :key="pkg.id">
                                    <td>{{ index + 1 }}</td>
                                    <td class="font-mono">{{ pkg.package_id }}</td>
                                    <td>{{ pkg.from }}</td>
                                    <td>{{ pkg.total_weight }} kg</td>
                                    <td>{{ formatCurrency(pkg.total_value) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
