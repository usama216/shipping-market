<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const props = defineProps({
    shipments: Object,
    filters: Object,
    statuses: Array,
    carriers: Array,
    carrierStatuses: Array,
});

const searchForm = useForm({
    search: props.filters?.search || "",
    status: props.filters?.status || "",
    carrier: props.filters?.carrier || "",
    carrier_status: props.filters?.carrier_status || "",
    date_from: props.filters?.date_from || "",
    date_to: props.filters?.date_to || "",
});

const applyFilters = () => {
    router.get(route("admin.tracking.index"), {
        search: searchForm.search || undefined,
        status: searchForm.status || undefined,
        carrier: searchForm.carrier || undefined,
        carrier_status: searchForm.carrier_status || undefined,
        date_from: searchForm.date_from || undefined,
        date_to: searchForm.date_to || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    searchForm.reset();
    router.get(route("admin.tracking.index"));
};

const formatCurrency = (amount) => `$${parseFloat(amount || 0).toFixed(2)}`;

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: "bg-yellow-100 text-yellow-800",
        shipped: "bg-blue-100 text-blue-800",
        delivered: "bg-green-100 text-green-800",
        cancelled: "bg-red-100 text-red-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const getCarrierStatusBadgeClass = (status) => {
    const classes = {
        pending: "bg-gray-100 text-gray-800",
        submitted: "bg-green-100 text-green-800",
        failed: "bg-red-100 text-red-800",
    };
    return classes[status] || "bg-gray-100 text-gray-800";
};

const getCarrierDisplayName = (carrier) => {
    const names = {
        fedex: "FedEx",
        dhl: "DHL Express",
        ups: "UPS",
    };
    return names[carrier] || carrier?.toUpperCase() || "N/A";
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Order Tracking" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold"><i class="fa-solid fa-box text-primary-500 mr-2"></i>Order Tracking</h1>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-lg shadow-md">
                <div class="grid grid-cols-1 md:grid-cols-6 gap-4">
                    <!-- Search -->
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search Tracking #</label>
                        <input
                            v-model="searchForm.search"
                            type="text"
                            placeholder="Enter tracking number..."
                            class="input input-bordered w-full"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select v-model="searchForm.status" class="select select-bordered w-full">
                            <option value="">All Statuses</option>
                            <option v-for="status in statuses" :key="status" :value="status">
                                {{ status.charAt(0).toUpperCase() + status.slice(1) }}
                            </option>
                        </select>
                    </div>

                    <!-- Carrier Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Carrier</label>
                        <select v-model="searchForm.carrier" class="select select-bordered w-full">
                            <option value="">All Carriers</option>
                            <option v-for="carrier in carriers" :key="carrier" :value="carrier">
                                {{ getCarrierDisplayName(carrier) }}
                            </option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input v-model="searchForm.date_from" type="date" class="input input-bordered w-full" />
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input v-model="searchForm.date_to" type="date" class="input input-bordered w-full" />
                    </div>
                </div>

                <div class="flex gap-2 mt-4">
                    <button @click="applyFilters" class="btn btn-primary">
                        <i class="fas fa-search mr-2"></i> Search
                    </button>
                    <button @click="clearFilters" class="btn btn-outline">
                        <i class="fas fa-times mr-2"></i> Clear
                    </button>
                </div>
            </div>

            <!-- Shipments Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table w-full">
                        <thead class="bg-gray-100">
                            <tr>
                                <th>Tracking #</th>
                                <th>Customer</th>
                                <th>Suite</th>
                                <th>Carrier</th>
                                <th>Carrier Tracking</th>
                                <th>Status</th>
                                <th>Carrier Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="shipment in shipments.data"
                                :key="shipment.id"
                                class="hover:bg-gray-50"
                            >
                                <td class="font-mono">{{ shipment.tracking_number }}</td>
                                <td>
                                    <div>{{ shipment.user?.name }}</div>
                                    <div class="text-xs text-gray-500">{{ shipment.user?.email }}</div>
                                </td>
                                <td>{{ shipment.user?.suite || "N/A" }}</td>
                                <td>
                                    <span v-if="shipment.carrier_name" class="font-medium">
                                        {{ getCarrierDisplayName(shipment.carrier_name) }}
                                    </span>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td>
                                    <a
                                        v-if="shipment.carrier_tracking_number && shipment.carrier_tracking_url"
                                        :href="shipment.carrier_tracking_url"
                                        target="_blank"
                                        class="text-primary hover:underline font-mono text-sm"
                                    >
                                        {{ shipment.carrier_tracking_number }}
                                        <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                    </a>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td>
                                    <span
                                        :class="getStatusBadgeClass(shipment.status)"
                                        class="px-2 py-1 text-xs font-medium rounded-full"
                                    >
                                        {{ shipment.status?.charAt(0).toUpperCase() + shipment.status?.slice(1) }}
                                    </span>
                                </td>
                                <td>
                                    <span
                                        :class="getCarrierStatusBadgeClass(shipment.carrier_status)"
                                        class="px-2 py-1 text-xs font-medium rounded-full"
                                    >
                                        {{ shipment.carrier_status?.charAt(0).toUpperCase() + shipment.carrier_status?.slice(1) }}
                                    </span>
                                </td>
                                <td class="text-sm text-gray-600">
                                    {{ new Date(shipment.created_at).toLocaleDateString() }}
                                </td>
                                <td>
                                    <Link
                                        :href="route('admin.tracking.show', { ship: shipment.id })"
                                        class="btn btn-sm btn-primary"
                                    >
                                        <i class="fas fa-eye mr-1"></i> View
                                    </Link>
                                </td>
                            </tr>
                            <tr v-if="shipments.data.length === 0">
                                <td colspan="9" class="text-center py-8 text-gray-500">
                                    No shipments found matching your criteria.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-4 border-t" v-if="shipments.data.length > 0">
                    <Pagination :links="shipments.links" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
