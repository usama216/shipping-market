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
    carrierServices: Array,
    specialRequests: {
        type: Array,
        default: () => []
    },
    specialRequestCost: {
        type: Number,
        default: 0
    },
    carrierAddons: {
        type: Array,
        default: () => []
    },
});

const isRefreshing = ref(false);

const refreshStatus = () => {
    isRefreshing.value = true;
    router.post(
        route("admin.shipments.tracking.refresh", { ship: props.ship.id }),
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
    addEventForm.post(route("admin.shipments.tracking.addEvent", { ship: props.ship.id }), {
        preserveScroll: true,
        onSuccess: () => {
            showAddEventModal.value = false;
            addEventForm.reset();
        },
    });
};

const formatCurrency = (amount) => `$${parseFloat(amount || 0).toFixed(2)}`;

// Check if there are any merchant invoices
const hasMerchantInvoices = computed(() => {
    if (!props.ship.packages) return false;
    
    return props.ship.packages.some(pkg => 
        pkg.items && pkg.items.some(item => 
            item.invoice_files && item.invoice_files.length > 0
        )
    );
});

const getCarrierDisplayName = computed(() => {
    // Try to get from carrier service first
    if (props.ship?.carrier_service) {
        return props.ship.carrier_service.display_name;
    }
    // Fallback to legacy carrier_name
    const carrier = props.ship?.carrier_name;
    const names = {
        fedex: "FedEx",
        dhl: "DHL Express",
        ups: "UPS",
        sea_freight: "Sea Freight",
        air_cargo: "Air Cargo",
    };
    return names[carrier] || carrier?.toUpperCase() || "N/A";
});

// Download master PDF - fetches URL from API and opens in new tab
const downloadMasterPDF = async (url) => {
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.url) {
            window.open(data.url, '_blank');
        } else {
            alert(data.message || 'Failed to generate master PDF');
        }
    } catch (error) {
        console.error('Error fetching master PDF:', error);
        alert('Failed to generate master PDF. Please try again.');
    }
};

const getStatusBadgeClass = (status) => {
    const classes = {
        pending: "badge-warning",
        shipped: "badge-info",
        delivered: "badge-success",
        cancelled: "badge-error",
    };
    return classes[status] || "badge-ghost";
};

// Operator-friendly status helpers
const getOperatorStatusLabel = (ship) => {
    const status = ship.operator_status || ship.status;
    const labels = {
        ready_to_pack: 'Ready to Pack',
        awaiting_pickup: 'Awaiting Pickup',
        paid: 'Ready to Prepare',
        failed: 'Needs Attention',
        delivered: 'Delivered',
        in_transit: 'In Transit',
        picked_up: 'Picked Up',
    };
    return labels[status] || (status?.charAt(0).toUpperCase() + status?.slice(1));
};

const getOperatorStatusBadgeClass = (ship) => {
    const status = ship.operator_status || ship.status;
    const classes = {
        ready_to_pack: 'badge-info',
        awaiting_pickup: 'badge-primary',
        paid: 'badge-warning',
        failed: 'badge-error',
        delivered: 'badge-success',
        in_transit: 'badge-info',
        picked_up: 'badge-primary',
    };
    return classes[status] || 'badge-ghost';
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`Tracking #${ship.tracking_number}`" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex flex-wrap justify-between items-center gap-4">
                <div>
                    <Link :href="route('admin.shipments')" class="text-primary hover:underline text-sm mb-2 inline-block">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Shipments
                    </Link>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <i class="fa-solid fa-box text-primary-500"></i> Shipment Tracking #{{ ship.tracking_number }}
                    </h1>
                </div>
                <div class="flex gap-2">
                    <button
                        v-if="can('shipments.tracking.refresh')"
                        @click="refreshStatus"
                        :disabled="isRefreshing || !ship.carrier_tracking_number"
                        class="btn btn-outline btn-sm"
                        :class="{ loading: isRefreshing }"
                    >
                        <i v-if="!isRefreshing" class="fas fa-sync-alt mr-1"></i>
                        Refresh from Carrier
                    </button>
                    <button v-if="can('shipments.tracking.event')" @click="showAddEventModal = true" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus mr-1"></i> Add Event
                    </button>
                </div>
            </div>

            <!-- Status Badges -->
            <div class="flex flex-wrap gap-2">
                <span class="badge px-4 py-2" :class="getOperatorStatusBadgeClass(ship)">
                    {{ getOperatorStatusLabel(ship) }}
                </span>
                <span v-if="ship.operator_status !== ship.status" class="badge badge-ghost px-4 py-2">
                    Raw: {{ ship.status?.charAt(0).toUpperCase() + ship.status?.slice(1) }}
                </span>
                <span class="badge badge-ghost px-4 py-2">
                    Invoice: {{ ship.invoice_status }}
                </span>
                <span v-if="ship.carrier_name || ship.carrier_service" class="badge badge-neutral px-4 py-2">
                    Carrier: {{ getCarrierDisplayName }}
                </span>
                <span v-if="ship.rate_source" class="badge badge-outline px-4 py-2">
                    Rate: {{ ship.rate_source }}
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
                                    <p class="text-sm text-gray-500">Carrier Service</p>
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

                    <!-- Shipping Label Card -->
                    <div class="card bg-white shadow-md">
                        <div class="card-body">
                            <h2 class="card-title"><i class="fa-solid fa-tag text-primary-500 mr-2"></i> Shipping Label</h2>
                            
                            <!-- Label Available (check both label_data and label_url) -->
                            <div v-if="ship.label_data || ship.label_url" class="space-y-4">
                                <div class="flex items-center gap-3 p-4 bg-green-50 rounded-lg border border-green-200">
                                    <i class="fa-solid fa-check-circle text-green-500 text-xl"></i>
                                    <div>
                                        <p class="font-medium text-green-800">Label Available</p>
                                        <p class="text-sm text-green-600">Ready to print and attach to package</p>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <a 
                                        :href="route('admin.shipments.label.view', ship.id)" 
                                        target="_blank" 
                                        class="btn btn-primary gap-2"
                                    >
                                        <i class="fa-solid fa-print"></i>
                                        Print Shipping Label
                                    </a>
                                    <a 
                                        :href="route('admin.shipments.label.zpl', ship.id)" 
                                        target="_blank" 
                                        class="btn btn-outline gap-2"
                                        title="Open label in ZPL format for thermal printers"
                                    >
                                        <i class="fa-solid fa-print"></i>
                                        Print ZPL Label
                                    </a>
                                    <a 
                                        :href="route('admin.shipments.label.download', ship.id)" 
                                        class="btn btn-outline gap-2"
                                    >
                                        <i class="fa-solid fa-download"></i>
                                        Download Label
                                    </a>
                                    <!-- Commercial Invoice Download (same PDF sent to DHL) -->
                                    <a 
                                        v-if="ship.carrier_tracking_number"
                                        :href="route('admin.shipments.invoice.view', ship.id)" 
                                        target="_blank" 
                                        class="btn btn-outline gap-2"
                                    >
                                        <i class="fa-solid fa-file-pdf"></i>
                                        View Invoice PDF
                                    </a>
                                    <a 
                                        v-if="ship.carrier_tracking_number"
                                        :href="route('admin.shipments.invoice.download', ship.id)" 
                                        class="btn btn-outline gap-2"
                                    >
                                        <i class="fa-solid fa-download"></i>
                                        Download Invoice PDF
                                    </a>
                                </div>
                            </div>

                            <!-- Label Not Available -->
                            <div v-else class="space-y-4">
                                <div class="flex items-center gap-3 p-4 bg-amber-50 rounded-lg border border-amber-200">
                                    <i class="fa-solid fa-exclamation-triangle text-amber-500 text-xl"></i>
                                    <div>
                                        <p class="font-medium text-amber-800">Label Not Available</p>
                                        <p class="text-sm text-amber-600">Get label from carrier dashboard or upload manually</p>
                                    </div>
                                </div>

                                <!-- Carrier Dashboard Links -->
                                <div class="flex flex-wrap gap-2">
                                    <a 
                                        href="https://www.fedex.com/shipping/shipEntryAction.do"
                                        target="_blank"
                                        class="btn btn-outline btn-sm gap-2"
                                    >
                                        <i class="fa-solid fa-external-link"></i>
                                        FedEx Ship Manager
                                    </a>
                                    <a 
                                        href="https://mydhl.express.dhl/us/en/ship.html"
                                        target="_blank"
                                        class="btn btn-outline btn-sm gap-2"
                                    >
                                        <i class="fa-solid fa-external-link"></i>
                                        DHL MyShipment
                                    </a>
                                    <a 
                                        href="https://www.ups.com/ship/"
                                        target="_blank"
                                        class="btn btn-outline btn-sm gap-2"
                                    >
                                        <i class="fa-solid fa-external-link"></i>
                                        UPS Shipping
                                    </a>
                                </div>
                            </div>

                            <!-- Commercial Invoice Card -->
                            <div class="card bg-white shadow-md mt-4">
                                <div class="card-body">
                                    <h2 class="card-title"><i class="fa-solid fa-file-invoice text-primary-500 mr-2"></i> Commercial Invoice</h2>
                                    
                                    <!-- Invoice Available -->
                                    <div v-if="ship.carrier_tracking_number" class="space-y-4">
                                        <div class="flex items-center gap-3 p-4 bg-green-50 rounded-lg border border-green-200">
                                            <i class="fa-solid fa-check-circle text-green-500 text-xl"></i>
                                            <div>
                                                <p class="font-medium text-green-800">Invoice Available</p>
                                                <p class="text-sm text-green-600">Commercial invoice for customs purposes</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <a 
                                                :href="route('admin.shipments.invoice.view', ship.id)" 
                                                target="_blank" 
                                                class="btn btn-primary gap-2"
                                            >
                                                <i class="fa-solid fa-file-pdf"></i>
                                                View Invoice
                                            </a>
                                            <a 
                                                :href="route('admin.shipments.invoice.download', ship.id)" 
                                                class="btn btn-outline gap-2"
                                            >
                                                <i class="fa-solid fa-download"></i>
                                                Download Invoice
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Invoice Not Available -->
                                    <div v-else class="space-y-4">
                                        <div class="flex items-center gap-3 p-4 bg-amber-50 rounded-lg border border-amber-200">
                                            <i class="fa-solid fa-exclamation-triangle text-amber-500 text-xl"></i>
                                            <div>
                                                <p class="font-medium text-amber-800">Invoice Not Available</p>
                                                <p class="text-sm text-amber-600">Invoice will be generated after shipment is submitted to carrier</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Merchant Invoices Card -->
                    <div class="card bg-white shadow-md mt-4" v-if="hasMerchantInvoices">
                        <div class="card-body">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h2 class="card-title"><i class="fa-solid fa-file-invoice-dollar text-primary-500 mr-2"></i> Merchant Invoices</h2>
                                    <p class="text-sm text-gray-600">Invoices uploaded by customer during package creation</p>
                                </div>
                                <a 
                                    :href="route('admin.shipments.merchant-invoices.merged', ship.id)" 
                                    class="btn btn-primary gap-2"
                                    title="Download all merchant invoices as a single merged PDF"
                                    @click.prevent="downloadMasterPDF(route('admin.shipments.merchant-invoices.merged', ship.id))"
                                >
                                    <i class="fa-solid fa-file-pdf"></i>
                                    Download Master PDF
                                </a>
                            </div>
                            
                            <div class="space-y-4">
                                <template v-for="pkg in (ship.packages || [])" :key="pkg?.id">
                                    <div 
                                        v-if="pkg && pkg.items && Array.isArray(pkg.items) && pkg.items.some(item => item && item.invoice_files && Array.isArray(item.invoice_files) && item.invoice_files.length > 0)"
                                        class="border rounded-lg p-4"
                                    >
                                        <h3 class="font-semibold text-gray-700 mb-3">
                                            <i class="fa-solid fa-box mr-2"></i>
                                            Package #{{ pkg.id }}
                                            <span class="text-sm text-gray-500">({{ pkg.store_tracking_id || 'N/A' }})</span>
                                        </h3>
                                        
                                        <div class="space-y-3">
                                            <template v-for="item in (pkg.items || [])" :key="item?.id">
                                                <div 
                                                    v-if="item && item.invoice_files && Array.isArray(item.invoice_files) && item.invoice_files.length > 0"
                                                    class="bg-gray-50 rounded-lg p-3"
                                                >
                                                    <h4 class="font-medium text-gray-800 mb-2">
                                                        <i class="fa-solid fa-tag mr-2"></i>
                                                        {{ item.title || 'Item #' + item.id }}
                                                    </h4>
                                                    
                                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                                        <div 
                                                            v-for="invoice in (item.invoice_files || [])" 
                                                            :key="invoice?.id"
                                                            class="flex items-center gap-2 p-2 bg-white rounded border hover:bg-gray-50 transition"
                                                        >
                                                            <i 
                                                                :class="invoice.file_type === 'pdf' ? 'fa-solid fa-file-pdf text-red-500' : 'fa-solid fa-file-image text-blue-500'"
                                                                class="text-lg"
                                                            ></i>
                                                            <div class="flex-1 min-w-0">
                                                                <p class="text-sm font-medium text-gray-800 truncate" :title="invoice.name || 'Invoice'">
                                                                    {{ invoice.name || 'Invoice File' }}
                                                                </p>
                                                                <p class="text-xs text-gray-500">{{ invoice.file_type?.toUpperCase() || 'FILE' }}</p>
                                                            </div>
                                                            <a 
                                                                :href="invoice.file_with_url" 
                                                                target="_blank"
                                                                class="btn btn-sm btn-ghost gap-1"
                                                                :title="'View ' + (invoice.name || 'invoice')"
                                                            >
                                                                <i class="fa-solid fa-eye"></i>
                                                                View
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                
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
                                    <p class="font-medium">{{ ship.customer?.name || ship.user?.name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Email</p>
                                    <p class="font-medium">{{ ship.customer?.email || ship.user?.email }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Suite</p>
                                    <p class="font-medium">{{ ship.customer?.suite || ship.user?.suite || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Phone</p>
                                    <p class="font-medium">{{ ship.customer?.phone || ship.user?.phone || 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address Card -->
                    <div class="card bg-white shadow-md" v-if="ship.customer_address">
                        <div class="card-body">
                            <h2 class="card-title"><i class="fa-solid fa-location-dot text-primary-500 mr-2"></i> Shipping Address</h2>
                            <div class="space-y-1">
                                <p class="font-medium">{{ ship.customer_address.full_name }}</p>
                                <p>{{ ship.customer_address.address_line_1 }}</p>
                                <p v-if="ship.customer_address.address_line_2">{{ ship.customer_address.address_line_2 }}</p>
                                <p>{{ ship.customer_address.city }}, {{ ship.customer_address.state }} {{ ship.customer_address.postal_code }}</p>
                                <p>{{ ship.customer_address.country }}</p>
                                <p class="text-sm text-gray-500"><i class="fa-solid fa-phone mr-1"></i> {{ ship.customer_address.phone_number }}</p>
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
                                    <p class="font-medium">{{ ship.total_weight }} lb</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Total Value</p>
                                    <p class="font-medium">{{ formatCurrency(ship.total_price) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Shipping Cost</p>
                                    <p class="font-medium">{{ formatCurrency(ship.estimated_shipping_charges) }}</p>
                                </div>
                                <div v-if="ship.addon_charges > 0">
                                    <p class="text-sm text-gray-500">Addon Charges</p>
                                    <p class="font-medium">{{ formatCurrency(ship.addon_charges) }}</p>
                                </div>
                                <div v-if="ship.declared_value">
                                    <p class="text-sm text-gray-500">Declared Value</p>
                                    <p class="font-medium">{{ formatCurrency(ship.declared_value) }} {{ ship.declared_value_currency }}</p>
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
                                    <th>Store Tracking ID</th>
                                    <th>From</th>
                                    <th>Weight</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="pkg in ship.packages" :key="pkg.id">
                                    <td>{{ pkg.package_id }}</td>
                                    <td>{{ pkg.store_tracking_id || 'N/A' }}</td>
                                    <td>{{ pkg.from }}</td>
                                    <td>{{ pkg.total_weight }} lb</td>
                                    <td>{{ formatCurrency(pkg.total_value) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Special Requests & Additional Services -->
            <div v-if="(specialRequests && specialRequests.length > 0) || (carrierAddons && carrierAddons.length > 0)" class="card bg-white shadow-md">
                <div class="card-body">
                    <h2 class="card-title">
                        <i class="fa-solid fa-list-check text-primary-500 mr-2"></i>
                        Optional Services & Additional Services
                    </h2>
                    
                    <!-- Special Requests (Optional Services) -->
                    <div v-if="specialRequests && specialRequests.length > 0" class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-star text-amber-500"></i>
                            Optional Services (Special Requests)
                        </h3>
                        <div class="space-y-2">
                            <div 
                                v-for="sr in specialRequests" 
                                :key="sr.id"
                                class="flex items-center justify-between p-3 bg-amber-50 border border-amber-200 rounded-lg"
                            >
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ sr.title }}</p>
                                    <p v-if="sr.description" class="text-sm text-gray-600 mt-1">{{ sr.description }}</p>
                                </div>
                                <span class="ml-4 font-semibold text-amber-700">{{ formatCurrency(sr.price) }}</span>
                            </div>
                        </div>
                        <div v-if="specialRequestCost > 0" class="mt-3 pt-3 border-t border-amber-200">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-700">Total Optional Services:</span>
                                <span class="text-lg font-bold text-amber-700">{{ formatCurrency(specialRequestCost) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Carrier Addons (Additional Services) -->
                    <div v-if="carrierAddons && carrierAddons.length > 0">
                        <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center gap-2">
                            <i class="fa-solid fa-plus-circle text-blue-500"></i>
                            Additional Services (Carrier Add-ons)
                        </h3>
                        <div class="space-y-2">
                            <div 
                                v-for="addon in carrierAddons" 
                                :key="addon.id"
                                class="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-lg"
                            >
                                <div class="flex-1">
                                    <p class="font-medium text-gray-900">{{ addon.display_name }}</p>
                                    <p v-if="addon.description" class="text-sm text-gray-600 mt-1">{{ addon.description }}</p>
                                </div>
                                <span class="ml-4 font-semibold text-blue-700">{{ formatCurrency(addon.price) }}</span>
                            </div>
                        </div>
                        <div v-if="ship.addon_charges > 0" class="mt-3 pt-3 border-t border-blue-200">
                            <div class="flex justify-between items-center">
                                <span class="font-semibold text-gray-700">Total Additional Services:</span>
                                <span class="text-lg font-bold text-blue-700">{{ formatCurrency(ship.addon_charges) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div v-if="(specialRequestCost > 0 || ship.addon_charges > 0)" class="mt-4 pt-4 border-t-2 border-gray-300">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-gray-900">Total Services Cost:</span>
                            <span class="text-xl font-bold text-primary-600">
                                {{ formatCurrency((specialRequestCost || 0) + (ship.addon_charges || 0)) }}
                            </span>
                        </div>
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
