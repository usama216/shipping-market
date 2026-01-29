<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    shipments: Object,
    filters: Object,
    statuses: Array,
});

const searchForm = useForm({
    search: props.filters?.search || "",
    status: props.filters?.status || "",
});

const applyFilters = () => {
    router.get(route("customer.tracking.index"), {
        search: searchForm.search || undefined,
        status: searchForm.status || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    searchForm.reset();
    router.get(route("customer.tracking.index"));
};

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: "bg-yellow-100 text-yellow-800",
        shipped: "bg-blue-100 text-blue-800",
        delivered: "bg-green-100 text-green-800",
        cancelled: "bg-red-100 text-red-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const getCarrierDisplayName = (carrier) => {
    const names = {
        fedex: "FedEx",
        dhl: "DHL Express",
        ups: "UPS",
    };
    return names[carrier] || carrier?.toUpperCase() || "—";
};

const getProgressPercentage = (status) => {
    const percentages = {
        pending: 25,
        shipped: 50,
        delivered: 100,
        cancelled: 0,
    };
    return percentages[status] || 0;
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Track My Orders" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-box text-primary-500 mr-2"></i>Track My Orders</h1>
            </div>

            <!-- Search Bar -->
            <div class="bg-white p-4 rounded-lg shadow-md">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <input
                            v-model="searchForm.search"
                            type="text"
                            placeholder="Enter tracking number..."
                            class="input input-bordered w-full"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <div>
                        <select v-model="searchForm.status" class="select select-bordered w-full">
                            <option value="">All Statuses</option>
                            <option v-for="status in statuses" :key="status" :value="status">
                                {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                            </option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button @click="applyFilters" class="btn btn-primary">
                            <i class="fas fa-search mr-2"></i> Search
                        </button>
                        <button @click="clearFilters" class="btn btn-outline">
                            Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Shipments Grid -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6" v-if="shipments.data.length > 0">
                <div
                    v-for="shipment in shipments.data"
                    :key="shipment.id"
                    class="card bg-white shadow-md hover:shadow-lg transition-shadow"
                >
                    <div class="card-body">
                        <!-- Header -->
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm text-gray-500">Tracking #</p>
                                <p class="font-mono font-bold">{{ shipment.tracking_number }}</p>
                            </div>
                            <span
                                :class="getStatusBadgeClass(shipment.status)"
                                class="px-2 py-1 text-xs font-medium rounded-full"
                            >
                                {{ shipment.status?.charAt(0).toUpperCase() + shipment.status?.slice(1) }}
                            </span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="flex justify-between text-xs text-gray-500 mb-1">
                                <span>Pending</span>
                                <span>Shipped</span>
                                <span>Delivered</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div
                                    class="bg-primary h-2 rounded-full transition-all duration-500"
                                    :style="{ width: getProgressPercentage(shipment.status) + '%' }"
                                ></div>
                            </div>
                        </div>

                        <!-- Carrier Info -->
                        <div class="mt-4 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Carrier:</span>
                                <span class="font-medium">{{ getCarrierDisplayName(shipment.carrier_name) }}</span>
                            </div>
                            <div class="flex justify-between" v-if="shipment.carrier_tracking_number">
                                <span class="text-gray-500">Carrier Tracking:</span>
                                <a
                                    v-if="shipment.carrier_tracking_url"
                                    :href="shipment.carrier_tracking_url"
                                    target="_blank"
                                    class="text-primary hover:underline font-mono text-xs"
                                >
                                    {{ shipment.carrier_tracking_number.slice(0, 12) }}...
                                    <i class="fas fa-external-link-alt ml-1"></i>
                                </a>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Date:</span>
                                <span>{{ new Date(shipment.created_at).toLocaleDateString() }}</span>
                            </div>
                        </div>

                        <!-- Latest Event -->
                        <div
                            v-if="shipment.latest_tracking_event"
                            class="mt-4 p-3 bg-gray-50 rounded-lg text-sm"
                        >
                            <p class="font-medium">{{ shipment.latest_tracking_event.status_label }}</p>
                            <p class="text-gray-500 text-xs">
                                {{ shipment.latest_tracking_event.event_time_formatted }}
                            </p>
                        </div>

                        <!-- Actions -->
                        <div class="mt-4">
                            <Link
                                :href="route('customer.tracking.show', { ship: shipment.id })"
                                class="btn btn-primary btn-block btn-sm"
                            >
                                <i class="fas fa-eye mr-2"></i> View Details
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-lg shadow-md p-12 text-center">
                <i class="fas fa-box-open text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-600 mb-2">No shipments found</h3>
                <p class="text-gray-500">
                    You don't have any shipments matching your search criteria.
                </p>
            </div>

            <!-- Pagination -->
            <div v-if="shipments.data.length > 0">
                <Pagination :links="shipments.links" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
