<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TrackingTimeline from "@/Components/TrackingTimeline.vue";
import CarrierActionsCard from "@/Components/OrderTracking/CarrierActionsCard.vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { usePermissions } from "@/Composables/usePermissions";

const { can } = usePermissions();

const props = defineProps({
    ship: Object,
    timeline: Array,
    carrierTrackingUrl: String,
    statuses: Object,
});

const isRefreshing = ref(false);

const refreshStatus = () => {
    isRefreshing.value = true;
    router.post(
        route("admin.tracking.refresh", { ship: props.ship.id }),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isRefreshing.value = false;
            },
        }
    );
};

const addEventForm = useForm({
    status: "",
    description: "",
    location: "",
});

const showAddEventModal = ref(false);

const submitEvent = () => {
    addEventForm.post(route("admin.tracking.addEvent", { ship: props.ship.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showAddEventModal.value = false;
            addEventForm.reset();
        },
    });
};

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
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`Tracking #${ship.tracking_number}`" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div>
                    <Link :href="route('admin.tracking.index')" class="text-primary hover:underline text-sm mb-2 inline-block">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Tracking
                    </Link>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <i class="fa-solid fa-box text-primary-500"></i> Shipment #{{ ship.tracking_number }}
                    </h1>
                </div>
                <div class="flex gap-2">
                    <button
                        v-if="can('order-tracking.refresh')"
                        @click="refreshStatus"
                        :disabled="isRefreshing || !ship.carrier_tracking_number"
                        class="btn btn-outline btn-sm"
                        :class="{ loading: isRefreshing }"
                    >
                        <i v-if="!isRefreshing" class="fas fa-sync-alt mr-1"></i>
                        Refresh from Carrier
                    </button>
                    <button v-if="can('order-tracking.events.create')" @click="showAddEventModal = true" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> Add Event
                    </button>
                </div>
            </div>

            <!-- Status Badges -->
            <div class="flex flex-wrap gap-2">
                <span class="badge px-4 py-2" :class="getStatusBadgeClass(ship.status)">
                    Status: {{ ship.status?.charAt(0).toUpperCase() + ship.status?.slice(1) }}
                </span>
                <span class="badge badge-ghost px-4 py-2">
                    Invoice: {{ ship.invoice_status }}
                </span>
                <span v-if="ship.carrier_name" class="badge badge-neutral px-4 py-2">
                    Carrier: {{ getCarrierDisplayName }}
                </span>
            </div>

            <!-- Carrier Actions Card (shown when submission failed or pending) -->
            <CarrierActionsCard :ship="ship" />

            <div class="grid md:grid-cols-3 gap-6">
                <!-- Left Column: Info Cards -->
                <div class="md:col-span-2 space-y-6">
                    <!-- Carrier Tracking Card -->
                    <div class="card bg-white shadow-md">
                        <div class="card-body">
                            <h2 class="card-title"><i class="fa-solid fa-truck text-primary-500 mr-2"></i> Carrier Tracking</h2>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Internal Tracking #</p>
                                    <p class="font-mono font-medium">{{ ship.tracking_number }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Carrier Tracking #</p>
                                    <p v-if="ship.carrier_tracking_number" class="font-mono font-medium">
                                        <a
                                            v-if="carrierTrackingUrl"
                                            :href="carrierTrackingUrl"
                                            target="_blank"
                                            class="text-primary hover:underline"
                                        >
                                            {{ ship.carrier_tracking_number }}
                                            <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                        </a>
                                        <span v-else>{{ ship.carrier_tracking_number }}</span>
                                    </p>
                                    <p v-else class="text-gray-400">Not yet assigned</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Carrier</p>
                                    <p class="font-medium">{{ getCarrierDisplayName }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Carrier Status</p>
                                    <p class="font-medium">{{ ship.carrier_status || 'Pending' }}</p>
                                </div>
                            </div>
                            <div v-if="carrierTrackingUrl" class="mt-4">
                                <a :href="carrierTrackingUrl" target="_blank" class="btn btn-outline btn-sm">
                                    Track on {{ getCarrierDisplayName }} <i class="fas fa-external-link-alt ml-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Info Card -->
                    <div class="card bg-white shadow-md">
                        <div class="card-body">
                            <h2 class="card-title"><i class="fa-solid fa-user text-primary-500 mr-2"></i> Customer Information</h2>
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Name</p>
                                    <p class="font-medium">{{ ship.user?.name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Email</p>
                                    <p class="font-medium">{{ ship.user?.email }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Suite</p>
                                    <p class="font-medium">{{ ship.user?.suite || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Phone</p>
                                    <p class="font-medium">{{ ship.user?.phone || 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address Card -->
                    <div class="card bg-white shadow-md" v-if="ship.user_address">
                        <div class="card-body">
                            <h2 class="card-title"><i class="fa-solid fa-location-dot text-primary-500 mr-2"></i> Shipping Address</h2>
                            <div class="space-y-1">
                                <p class="font-medium">{{ ship.user_address.full_name }}</p>
                                <p>{{ ship.user_address.address_line_1 }}</p>
                                <p v-if="ship.user_address.address_line_2">{{ ship.user_address.address_line_2 }}</p>
                                <p>{{ ship.user_address.city }}, {{ ship.user_address.state }} {{ ship.user_address.postal_code }}</p>
                                <p>{{ ship.user_address.country }}</p>
                                <p class="text-sm text-gray-500"><i class="fa-solid fa-phone mr-1"></i> {{ ship.user_address.phone_number }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Shipment Details Card -->
                    <div class="card bg-white shadow-md">
                        <div class="card-body">
                            <h2 class="card-title"><i class="fa-solid fa-box-open text-primary-500 mr-2"></i> Shipment Details</h2>
                            <div class="grid md:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">Total Weight</p>
                                    <p class="font-medium">{{ ship.total_weight }} kg</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Total Value</p>
                                    <p class="font-medium">{{ formatCurrency(ship.total_price) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Shipping Cost</p>
                                    <p class="font-medium">{{ formatCurrency(ship.estimated_shipping_charges) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Tracking Timeline -->
                <div class="space-y-6">
                    <div class="card bg-white shadow-md">
                        <div class="card-body">
                            <h2 class="card-title"><i class="fa-solid fa-clipboard-list text-primary-500 mr-2"></i> Tracking Timeline</h2>
                            <TrackingTimeline :events="timeline" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Packages Table -->
            <div class="card bg-white shadow-md" v-if="ship.packages?.length > 0">
                <div class="card-body">
                    <h2 class="card-title"><i class="fa-solid fa-boxes-stacked text-primary-500 mr-2"></i> Packages ({{ ship.packages.length }})</h2>
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th>Package ID</th>
                                    <th>Tracking ID</th>
                                    <th>From</th>
                                    <th>Weight</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="pkg in ship.packages" :key="pkg.id">
                                    <td>{{ pkg.package_id }}</td>
                                    <td>{{ pkg.tracking_id || 'N/A' }}</td>
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

        <!-- Add Event Modal -->
        <div v-if="showAddEventModal" class="modal modal-open">
            <div class="modal-box">
                <h3 class="font-bold text-lg">Add Tracking Event</h3>
                <form @submit.prevent="submitEvent" class="space-y-4 mt-4">
                    <div>
                        <label class="label">Status</label>
                        <select v-model="addEventForm.status" class="select select-bordered w-full" required>
                            <option value="">Select status...</option>
                            <option v-for="(label, key) in statuses" :key="key" :value="key">
                                {{ label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Description</label>
                        <textarea
                            v-model="addEventForm.description"
                            class="textarea textarea-bordered w-full"
                            rows="2"
                            placeholder="Optional description..."
                        ></textarea>
                    </div>
                    <div>
                        <label class="label">Location</label>
                        <input
                            v-model="addEventForm.location"
                            type="text"
                            class="input input-bordered w-full"
                            placeholder="City, State (optional)"
                        />
                    </div>
                    <div class="modal-action">
                        <button type="button" @click="showAddEventModal = false" class="btn">Cancel</button>
                        <button type="submit" class="btn btn-primary" :disabled="addEventForm.processing">
                            Add Event
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-backdrop" @click="showAddEventModal = false"></div>
        </div>
    </AuthenticatedLayout>
</template>
