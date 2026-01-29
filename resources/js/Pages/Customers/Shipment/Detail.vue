<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    shipDetails: Object,
    packingOptions: Array,
    shippingPreferenceOption: Array,
});

const formatCurrency = (amount) => 
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

// Carrier helpers
const carrierDisplayName = computed(() => {
    const carrier = props.shipDetails?.carrier_name || props.shipDetails?.carrier_service?.carrier_code;
    const names = { fedex: 'FedEx', dhl: 'DHL Express', ups: 'UPS' };
    return names[carrier?.toLowerCase()] || carrier?.toUpperCase() || 'Carrier';
});

const getTrackingUrl = () => {
    const tracking = props.shipDetails?.carrier_tracking_number;
    if (!tracking) return '#';
    const carrier = props.shipDetails?.carrier_name;
    const urls = {
        fedex: `https://www.fedex.com/fedextrack/?trknbr=${tracking}`,
        dhl: `https://www.dhl.com/en/express/tracking.html?AWB=${tracking}`,
        ups: `https://www.ups.com/track?tracknum=${tracking}`,
    };
    return urls[carrier] || '#';
};

const hasLabel = computed(() => 
    props.shipDetails?.label_url || props.shipDetails?.label_data
);

// Check if shipment has merchant invoices
const hasMerchantInvoices = computed(() => {
    if (!props.shipDetails?.packages) return false;
    return props.shipDetails.packages.some(pkg => 
        pkg.items?.some(item => {
            const invoiceFiles = item.invoice_files || item.invoiceFiles || [];
            return Array.isArray(invoiceFiles) && invoiceFiles.length > 0;
        })
    );
});

// Status configuration - Simplified for customers
const getStatusConfig = (status) => {
    // Only show "In Progress" or "Delivered" labels
    if (status === 'delivered') {
        return { label: 'Delivered', color: 'bg-green-100 text-green-700 border-green-200', icon: 'fa-check-circle' };
    }
    // All other statuses (including cancelled/failed) show as "In Progress"
    return { label: 'In Progress', color: 'bg-blue-100 text-blue-700 border-blue-200', icon: 'fa-truck' };
};

// Progress timeline - Simplified to 2 phases
const timelineSteps = [
    { key: 'in_progress', label: 'In Progress', icon: 'fa-truck' },
    { key: 'delivered', label: 'Delivered', icon: 'fa-flag-checkered' },
];

const currentStepIndex = computed(() => {
    const status = props.shipDetails?.status || 'pending';
    // Simplified: 0 = In Progress, 1 = Delivered
    if (status === 'delivered') return 1;
    // All other active statuses are "In Progress"
    if (['cancelled', 'failed'].includes(status)) return -1;
    return 0;
});

// Address
const address = computed(() => {
    const addr = props.shipDetails?.customer_address || props.shipDetails?.user_address;
    if (!addr) return null;
    return addr;
});

// Customer
const customer = computed(() => {
    return props.shipDetails?.customer || props.shipDetails?.user || {};
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Shipment Details" />

        <div class="min-h-screen bg-gray-50 py-6">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Back Button & Header -->
                <div class="mb-6">
                    <Link 
                        :href="route('customer.shipment.myShipments')"
                        class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700 mb-4"
                    >
                        <i class="fa-solid fa-arrow-left mr-2"></i>
                        Back to My Shipments
                    </Link>
                    
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                                <i class="fa-solid fa-box text-primary-500"></i>
                                Shipment #{{ shipDetails?.tracking_number || shipDetails?.id }}
                            </h1>
                            <p class="text-sm text-gray-500 mt-1">
                                Created {{ formatDate(shipDetails?.created_at) }}
                            </p>
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <span 
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-semibold border"
                                :class="getStatusConfig(shipDetails?.status).color"
                            >
                                <i :class="['fa-solid', getStatusConfig(shipDetails?.status).icon]"></i>
                                {{ getStatusConfig(shipDetails?.status).label }}
                            </span>
                            <span 
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full text-sm font-semibold border"
                                :class="shipDetails?.invoice_status === 'paid' 
                                    ? 'bg-green-100 text-green-700 border-green-200' 
                                    : 'bg-yellow-100 text-yellow-700 border-yellow-200'"
                            >
                                <i class="fa-solid fa-receipt"></i>
                                {{ shipDetails?.invoice_status?.charAt(0).toUpperCase() + shipDetails?.invoice_status?.slice(1) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tracking Timeline Card -->
                <div class="bg-white rounded-xl border shadow-sm p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-6">
                        <i class="fa-solid fa-route text-primary-500"></i>
                        Tracking Progress
                    </h2>
                    
                    <!-- Timeline -->
                    <div class="relative">
                        <!-- Progress Line Background -->
                        <div class="absolute top-5 left-0 right-0 h-1 bg-gray-200 rounded hidden sm:block"></div>
                        <!-- Progress Line Active -->
                        <div 
                            class="absolute top-5 left-0 h-1 bg-green-500 rounded transition-all duration-500 hidden sm:block"
                            :style="{ width: `${Math.max(0, (currentStepIndex / (timelineSteps.length - 1)) * 100)}%` }"
                        ></div>
                        
                        <div class="flex flex-col sm:flex-row sm:justify-between relative gap-4 sm:gap-0">
                            <div 
                                v-for="(step, idx) in timelineSteps" 
                                :key="step.key"
                                class="flex sm:flex-col items-center gap-3 sm:gap-0"
                            >
                                <div 
                                    class="w-10 h-10 rounded-full flex items-center justify-center transition-all z-10"
                                    :class="idx <= currentStepIndex 
                                        ? 'bg-green-500 text-white shadow-md' 
                                        : 'bg-gray-200 text-gray-400'"
                                >
                                    <i :class="['fa-solid', step.icon, 'text-sm']"></i>
                                </div>
                                <div class="sm:text-center sm:mt-2">
                                    <span 
                                        class="text-sm font-medium"
                                        :class="idx <= currentStepIndex ? 'text-green-600' : 'text-gray-400'"
                                    >
                                        {{ step.label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Carrier Info & Actions -->
                    <div class="mt-8 pt-6 border-t flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-500">Carrier</p>
                            <p class="font-semibold text-gray-900">{{ carrierDisplayName }}</p>
                            <p v-if="shipDetails?.carrier_tracking_number" class="text-blue-600 font-medium">
                                {{ shipDetails.carrier_tracking_number }}
                            </p>
                            <p v-else class="text-gray-400 text-sm italic">Tracking pending...</p>
                        </div>
                        <div class="flex gap-2 flex-wrap">
                            <a
                                v-if="shipDetails?.carrier_tracking_number"
                                :href="getTrackingUrl()"
                                target="_blank"
                                class="inline-flex items-center px-4 py-2 bg-primary-500 text-white rounded-lg font-medium hover:bg-primary-600 transition"
                            >
                                <i class="fa-solid fa-external-link mr-2"></i>
                                Track on {{ carrierDisplayName }}
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Commercial Invoice Card -->
                <div class="bg-white rounded-xl border shadow-sm p-6 mb-6" v-if="shipDetails?.carrier_tracking_number">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                        <i class="fa-solid fa-file-invoice text-primary-500"></i>
                        Commercial Invoice
                    </h2>
                    
                    <div class="flex items-center gap-3 p-4 bg-green-50 rounded-lg border border-green-200 mb-4">
                        <i class="fa-solid fa-check-circle text-green-500 text-xl"></i>
                        <div>
                            <p class="font-medium text-green-800">Invoice Available</p>
                            <p class="text-sm text-green-600">Commercial invoice for customs purposes</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-2 flex-wrap">
                        <a 
                            :href="route('customer.shipment.invoice.view', { ship: shipDetails?.id })" 
                            target="_blank" 
                            class="inline-flex items-center px-4 py-2 bg-primary-500 text-white rounded-lg font-medium hover:bg-primary-600 transition"
                        >
                            <i class="fa-solid fa-file-pdf mr-2"></i>
                            View Invoice
                        </a>
                        <a 
                            :href="route('customer.shipment.invoice.download', { ship: shipDetails?.id })" 
                            class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-700 rounded-lg font-medium hover:bg-blue-200 transition"
                        >
                            <i class="fa-solid fa-download mr-2"></i>
                            Download Invoice
                        </a>
                    </div>
                </div>

                <!-- Merchant Invoices Card -->
                <div class="bg-white rounded-xl border shadow-sm p-6 mb-6" v-if="hasMerchantInvoices">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                        <i class="fa-solid fa-file-invoice-dollar text-primary-500"></i>
                        Merchant Invoices
                    </h2>
                    <p class="text-sm text-gray-600 mb-4">Invoices uploaded during package creation</p>
                    
                    <div class="space-y-4">
                        <template v-for="pkg in (shipDetails?.packages || [])" :key="pkg?.id">
                            <div 
                                v-if="pkg && pkg.items && Array.isArray(pkg.items) && pkg.items.some(item => item && (item.invoice_files || item.invoiceFiles) && Array.isArray(item.invoice_files || item.invoiceFiles) && (item.invoice_files || item.invoiceFiles).length > 0)"
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
                                            v-if="item && (item.invoice_files || item.invoiceFiles) && Array.isArray(item.invoice_files || item.invoiceFiles) && (item.invoice_files || item.invoiceFiles).length > 0"
                                            class="bg-gray-50 rounded-lg p-3"
                                        >
                                            <h4 class="font-medium text-gray-800 mb-2">
                                                <i class="fa-solid fa-tag mr-2"></i>
                                                {{ item.title || 'Item #' + item.id }}
                                            </h4>
                                            
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                                <div 
                                                    v-for="invoice in ((item.invoice_files || item.invoiceFiles) || [])" 
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
                                                        :href="invoice.file_with_url || invoice.file_url" 
                                                        target="_blank"
                                                        class="inline-flex items-center px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition"
                                                        :title="'View ' + (invoice.name || 'invoice')"
                                                    >
                                                        <i class="fa-solid fa-eye mr-1"></i>
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

                <!-- Two Column Grid -->
                <div class="grid md:grid-cols-2 gap-6 mb-6">
                    <!-- Delivery Address -->
                    <div class="bg-white rounded-xl border shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <i class="fa-solid fa-location-dot text-primary-500"></i>
                            Delivery Address
                        </h2>
                        <div v-if="address" class="space-y-1 text-gray-700">
                            <p class="font-semibold">{{ address.full_name || customer.name || 'N/A' }}</p>
                            <p>{{ address.address_line_1 || 'N/A' }}</p>
                            <p v-if="address.address_line_2">{{ address.address_line_2 }}</p>
                            <p>{{ address.city }}, {{ address.state }} {{ address.postal_code }}</p>
                            <p>{{ address.country }}</p>
                            <p v-if="address.phone_number" class="text-gray-500 pt-2">
                                <i class="fa-solid fa-phone mr-2"></i>{{ address.phone_number }}
                            </p>
                        </div>
                        <p v-else class="text-gray-400">No address information</p>
                    </div>

                    <!-- Payment Summary -->
                    <div class="bg-white rounded-xl border shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <i class="fa-solid fa-receipt text-primary-500"></i>
                            Payment Summary
                        </h2>
                        <div class="space-y-3">
                            <div class="flex justify-between text-gray-600">
                                <span>Declared Value</span>
                                <span>{{ formatCurrency(shipDetails?.total_price) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>Weight</span>
                                <span>{{ shipDetails?.total_weight || 0 }} lbs</span>
                            </div>
                            <div v-if="shipDetails?.handling_fee" class="flex justify-between text-gray-600">
                                <span>Handling Fee</span>
                                <span>{{ formatCurrency(shipDetails.handling_fee) }}</span>
                            </div>
                            <div v-if="shipDetails?.addon_charges > 0" class="flex justify-between text-gray-600">
                                <span>Additional Services</span>
                                <span>{{ formatCurrency(shipDetails.addon_charges) }}</span>
                            </div>
                            <div class="border-t pt-3 flex justify-between font-bold text-lg">
                                <span>Total Paid</span>
                                <span class="text-green-600">{{ formatCurrency(shipDetails?.estimated_shipping_charges) }}</span>
                            </div>
                        </div>
                        <div v-if="shipDetails?.national_id" class="mt-4 pt-4 border-t text-sm">
                            <span class="text-gray-500">Tax ID:</span>
                            <span class="ml-2 font-medium">{{ shipDetails.national_id }}</span>
                        </div>
                    </div>
                </div>

                <!-- Packages Gallery -->
                <div class="bg-white rounded-xl border shadow-sm p-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                        <i class="fa-solid fa-boxes-stacked text-primary-500"></i>
                        Packages ({{ shipDetails?.packages?.length || 0 }})
                    </h2>
                    
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div 
                            v-for="(pkg, index) in shipDetails?.packages" 
                            :key="pkg.id"
                            class="bg-gray-50 rounded-xl p-4 border hover:shadow-md transition"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-10 h-10 rounded-lg bg-primary-100 text-primary-600 flex items-center justify-center">
                                        <i class="fa-solid fa-box"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">Package {{ index + 1 }}</p>
                                        <p class="text-xs text-gray-500">#{{ pkg.package_id || pkg.id }}</p>
                                    </div>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full bg-gray-200 text-gray-600">
                                    {{ pkg.status_name || pkg.status }}
                                </span>
                            </div>
                            
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500">From</span>
                                    <span class="font-medium text-gray-700">{{ pkg.from || 'N/A' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Weight</span>
                                    <span class="font-medium text-gray-700">{{ pkg.total_weight || 0 }} lbs</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Value</span>
                                    <span class="font-medium text-gray-700">{{ formatCurrency(pkg.total_value) }}</span>
                                </div>
                                <div v-if="pkg.tracking_id || pkg.store_tracking_id" class="flex justify-between">
                                    <span class="text-gray-500">Tracking</span>
                                    <span class="font-medium text-gray-700 text-xs">{{ pkg.store_tracking_id || pkg.tracking_id }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="!shipDetails?.packages?.length" class="text-center py-8 text-gray-400">
                        <i class="fa-solid fa-box-open text-4xl mb-2"></i>
                        <p>No packages in this shipment</p>
                    </div>
                </div>

                <!-- Selected Options -->
                <div v-if="packingOptions?.length || shippingPreferenceOption?.length" class="grid md:grid-cols-2 gap-6 mb-6">
                    <!-- Packing Options -->
                    <div v-if="packingOptions?.length" class="bg-white rounded-xl border shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <i class="fa-solid fa-box-taped text-primary-500"></i>
                            Packing Options
                        </h2>
                        <ul class="space-y-3">
                            <li v-for="opt in packingOptions" :key="opt.id" class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium text-gray-900">{{ opt.title }}</p>
                                    <p class="text-sm text-gray-500">{{ opt.description }}</p>
                                </div>
                                <span class="text-gray-700 font-medium">{{ formatCurrency(opt.price) }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Shipping Preferences -->
                    <div v-if="shippingPreferenceOption?.length" class="bg-white rounded-xl border shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2 mb-4">
                            <i class="fa-solid fa-sliders text-primary-500"></i>
                            Shipping Preferences
                        </h2>
                        <ul class="space-y-3">
                            <li v-for="opt in shippingPreferenceOption" :key="opt.id" class="flex justify-between items-start">
                                <div>
                                    <p class="font-medium text-gray-900">{{ opt.title }}</p>
                                    <p class="text-sm text-gray-500">{{ opt.description }}</p>
                                </div>
                                <span class="text-gray-700 font-medium">{{ formatCurrency(opt.price) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 text-center">
                    <i class="fa-solid fa-headset text-blue-500 text-3xl mb-3"></i>
                    <h3 class="font-semibold text-blue-900 mb-1">Need Help?</h3>
                    <p class="text-blue-700 text-sm mb-4">
                        If you have questions about your shipment, our support team is here to help.
                    </p>
                    <a 
                        href="mailto:support@example.com"
                        class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded-lg font-medium hover:bg-blue-600 transition"
                    >
                        <i class="fa-solid fa-envelope mr-2"></i>
                        Contact Support
                    </a>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
