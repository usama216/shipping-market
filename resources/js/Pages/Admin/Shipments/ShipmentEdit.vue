<script setup>
/**
 * Modern Shipment Edit Page
 * 
 * Unified single-page edit interface with:
 * - Header with status actions
 * - Two-column layout: editable details + read-only info
 * - Packages display with expansion
 * - Tracking timeline
 */
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router, Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useToast } from "vue-toastification";
import { usePermissions } from "@/Composables/usePermissions";
import VueSelect from "vue-select";
import { shippingStatus, invoiceStatus } from "@js/Data/statuses";
import TrackingTimeline from "@/Components/TrackingTimeline.vue";
import Modal from "@/Components/Modal.vue";
import PhotoLightbox from "@/Components/PhotoLightbox.vue";
import DeleteShipment from "./Components/Delete.vue";

const { can } = usePermissions();
const toast = useToast();

const props = defineProps({
    ship: Object,
});

// Form state
const form = useForm({
    tracking_number: props.ship?.tracking_number ?? "",
    carrier_tracking_number: props.ship?.carrier_tracking_number ?? "",
    total_weight: props.ship?.total_weight ?? 0,
    total_price: props.ship?.total_price ?? 0,
    status: props.ship?.status ?? "pending",
    invoice_status: props.ship?.invoice_status ?? "pending",
    declared_value: props.ship?.declared_value ?? 0,
    // Export Compliance (DHL)
    incoterm: props.ship?.incoterm ?? "DAP",
    us_filing_type: props.ship?.us_filing_type ?? "30.37(a)",
    invoice_signature_name: props.ship?.invoice_signature_name ?? "",
    invoice_signature_title: props.ship?.invoice_signature_title ?? "Mr.",
    exporter_id: props.ship?.exporter_id ?? "EAR99",
    exporter_code: props.ship?.exporter_code ?? "EXPCZ",
    // Custom Invoice for Customs (DHL Paperless Trade)
    use_custom_invoice: props.ship?.use_custom_invoice ?? false,
    custom_invoice_file: null, // New file upload
});

// UI State
const expandedPackages = ref(new Set());
const showLightbox = ref(false);
const lightboxImages = ref([]);
const lightboxIndex = ref(0);

// Computed
const packages = computed(() => props.ship?.packages ?? []);
const customer = computed(() => props.ship?.customer);
const address = computed(() => props.ship?.customer_address);
const carrierService = computed(() => props.ship?.carrier_service);
const trackingEvents = computed(() => props.ship?.tracking_events ?? []);

// Format helpers
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);
};

const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const formatDateTime = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleString('en-US', { 
        month: 'short', day: 'numeric', year: 'numeric',
        hour: 'numeric', minute: '2-digit'
    });
};

// Status helpers
const getStatusClass = (status) => {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800 border-yellow-300',
        paid: 'bg-blue-100 text-blue-800 border-blue-300',
        processing: 'bg-indigo-100 text-indigo-800 border-indigo-300',
        submitted: 'bg-cyan-100 text-cyan-800 border-cyan-300',
        label_ready: 'bg-teal-100 text-teal-800 border-teal-300',
        picked_up: 'bg-violet-100 text-violet-800 border-violet-300',
        shipped: 'bg-purple-100 text-purple-800 border-purple-300',
        in_transit: 'bg-purple-100 text-purple-800 border-purple-300',
        out_for_delivery: 'bg-orange-100 text-orange-800 border-orange-300',
        delivered: 'bg-green-100 text-green-800 border-green-300',
        cancelled: 'bg-gray-100 text-gray-800 border-gray-300',
        failed: 'bg-red-100 text-red-800 border-red-300',
        returned: 'bg-pink-100 text-pink-800 border-pink-300',
        on_hold: 'bg-amber-100 text-amber-800 border-amber-300',
    };
    return classes[status] || 'bg-gray-100 text-gray-800 border-gray-300';
};

const getStatusLabel = (status) => {
    const found = shippingStatus.find(s => s.id === status);
    return found?.name || status;
};

// Form submission
const submitForm = () => {
    form.post(route('admin.shipments.update', { ship: props.ship.id }), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Shipment updated successfully');
        },
        onError: (errors) => {
            toast.error('Failed to update shipment');
            console.error(errors);
        }
    });
};

// Package expansion
const togglePackage = (pkgId) => {
    if (expandedPackages.value.has(pkgId)) {
        expandedPackages.value.delete(pkgId);
    } else {
        expandedPackages.value.add(pkgId);
    }
};

const toggleAllPackages = () => {
    if (expandedPackages.value.size === packages.value.length) {
        expandedPackages.value.clear();
    } else {
        expandedPackages.value = new Set(packages.value.map(p => p.id));
    }
};

// Lightbox
const openLightbox = (images, startIndex = 0) => {
    lightboxImages.value = images.map(img => img.file_with_url || img.image_with_url);
    lightboxIndex.value = startIndex;
    showLightbox.value = true;
};

// Classification helpers
const hasClassification = (pkg) => {
    if (!pkg.items || pkg.items.length === 0) return { dangerous: false, fragile: false, oversized: false };
    return {
        dangerous: pkg.items.some(item => item.is_dangerous),
        fragile: pkg.items.some(item => item.is_fragile),
        oversized: pkg.items.some(item => item.is_oversized)
    };
};

// Operator status helpers
const getOperatorStatusLabel = (ship) => {
    const status = ship?.operator_status || ship?.status;
    const labels = {
        ready_to_pack: 'Ready to Pack',
        awaiting_pickup: 'Awaiting Pickup',
        paid: 'Ready to Prepare',
        failed: 'Needs Attention',
        delivered: 'Delivered',
        in_transit: 'In Transit',
        picked_up: 'Picked Up',
    };
    return labels[status] || getStatusLabel(status);
};

const getOperatorStatusClass = (ship) => {
    const status = ship?.operator_status || ship?.status;
    const classes = {
        ready_to_pack: 'bg-teal-100 text-teal-800 border-teal-300',
        awaiting_pickup: 'bg-blue-100 text-blue-800 border-blue-300',
        paid: 'bg-orange-100 text-orange-800 border-orange-300',
        failed: 'bg-red-100 text-red-800 border-red-300',
    };
    return classes[status] || getStatusClass(status);
};
</script>

<template>
    <AuthenticatedLayout>
        <Head :title="`Edit Shipment #${ship?.id}`" />

        <div class="min-h-screen py-6 bg-gray-50">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                
                <!-- Header -->
                <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                    <div class="flex items-center gap-4">
                        <Link 
                            :href="route('admin.shipments')"
                            class="flex items-center gap-2 text-gray-500 hover:text-gray-700"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                            Back
                        </Link>
                        <div>
                            <h1 class="flex items-center gap-3 text-2xl font-bold text-gray-900">
                                <i class="fa-solid fa-truck text-primary-500"></i>
                                Shipment #{{ ship?.tracking_number || ship?.id }}
                            </h1>
                            <p class="flex items-center gap-2 mt-1 text-sm text-gray-500">
                                <span>{{ customer?.name || 'Unknown Customer' }}</span>
                                <span>•</span>
                                <span>{{ formatDate(ship?.created_at) }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span :class="['inline-flex items-center gap-2 px-4 py-2 text-sm font-medium border rounded-full', getOperatorStatusClass(ship)]">
                            {{ getOperatorStatusLabel(ship) }}
                        </span>
                        <Link 
                            v-if="can('shipments.view')"
                            :href="route('admin.shipments.tracking', ship?.id)"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-primary-600 bg-primary-50 border border-primary-200 rounded-lg hover:bg-primary-100"
                        >
                            <i class="fa-solid fa-location-crosshairs"></i>
                            Track
                        </Link>
                        <DeleteShipment 
                            v-if="can('shipments.delete')"
                            :id="ship?.id"
                            :carrier-status="ship?.carrier_status"
                            :shipment-status="ship?.status"
                        />
                        <button 
                            v-if="can('shipments.update')"
                            @click="submitForm"
                            :disabled="form.processing"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 disabled:opacity-50"
                        >
                            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin"></i>
                            <i v-else class="fa-solid fa-save"></i>
                            Save Changes
                        </button>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid gap-6 lg:grid-cols-3">
                    
                    <!-- Left Column (2/3) -->
                    <div class="space-y-6 lg:col-span-2">
                        
                        <!-- Shipment Details Card -->
                        <div class="overflow-hidden bg-white border rounded-xl shadow-sm">
                            <div class="px-6 py-4 border-b bg-gray-50">
                                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                                    <i class="fa-solid fa-file-lines text-primary-500"></i>
                                    Shipment Details
                                </h2>
                            </div>
                            <div class="p-6">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <!-- Tracking Number -->
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Tracking Number</label>
                                        <input 
                                            v-model="form.tracking_number"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                            placeholder="Internal tracking #"
                                        />
                                        <p v-if="form.errors.tracking_number" class="mt-1 text-sm text-red-500">{{ form.errors.tracking_number }}</p>
                                    </div>
                                    
                                    <!-- Carrier Tracking -->
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Carrier Tracking #</label>
                                        <input 
                                            v-model="form.carrier_tracking_number"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                            placeholder="Carrier tracking #"
                                        />
                                        <p v-if="form.errors.carrier_tracking_number" class="mt-1 text-sm text-red-500">{{ form.errors.carrier_tracking_number }}</p>
                                    </div>
                                    
                                    <!-- Status -->
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Status</label>
                                        <VueSelect
                                            v-model="form.status"
                                            :options="shippingStatus"
                                            :reduce="option => option.id"
                                            label="name"
                                            class="vue-select-lg"
                                        />
                                        <p v-if="form.errors.status" class="mt-1 text-sm text-red-500">{{ form.errors.status }}</p>
                                    </div>
                                    
                                    <!-- Invoice Status -->
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Invoice Status</label>
                                        <VueSelect
                                            v-model="form.invoice_status"
                                            :options="invoiceStatus"
                                            :reduce="option => option.id"
                                            label="name"
                                            class="vue-select-lg"
                                        />
                                        <p v-if="form.errors.invoice_status" class="mt-1 text-sm text-red-500">{{ form.errors.invoice_status }}</p>
                                    </div>
                                    
                                    <!-- Weight -->
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Total Weight (lbs)</label>
                                        <input 
                                            v-model="form.total_weight"
                                            type="number"
                                            step="0.01"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                        />
                                        <p v-if="form.errors.total_weight" class="mt-1 text-sm text-red-500">{{ form.errors.total_weight }}</p>
                                    </div>
                                    
                                    <!-- Price -->
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Total Price ($)</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                                            <input 
                                                v-model="form.total_price"
                                                type="number"
                                                step="0.01"
                                                class="w-full py-2 pl-8 pr-4 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                            />
                                        </div>
                                        <p v-if="form.errors.total_price" class="mt-1 text-sm text-red-500">{{ form.errors.total_price }}</p>
                                    </div>
                                    
                                    <!-- Declared Value -->
                                    <div class="md:col-span-2">
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Declared Value (for insurance/customs)</label>
                                        <div class="relative max-w-xs">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                                            <input 
                                                v-model="form.declared_value"
                                                type="number"
                                                step="0.01"
                                                class="w-full py-2 pl-8 pr-4 border border-gray-300 rounded-lg focus:ring-primary-500 focus:border-primary-500"
                                            />
                                        </div>
                                        <p v-if="form.errors.declared_value" class="mt-1 text-sm text-red-500">{{ form.errors.declared_value }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Export Compliance Card (DHL/International) -->
                        <div class="overflow-hidden bg-white border rounded-xl shadow-sm">
                            <div class="px-6 py-4 border-b bg-amber-50">
                                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                                    <i class="fa-solid fa-file-export text-amber-500"></i>
                                    Export Compliance
                                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-amber-100 text-amber-700 rounded">DHL/Intl</span>
                                </h2>
                                <p class="mt-1 text-sm text-gray-500">Edit these fields if carrier submission fails due to export compliance errors</p>
                            </div>
                            <div class="p-6">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <!-- Incoterm -->
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Incoterm</label>
                                        <select 
                                            v-model="form.incoterm"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                        >
                                            <option value="DAP">DAP - Delivered at Place</option>
                                            <option value="DDU">DDU - Delivered Duty Unpaid</option>
                                            <option value="DDP">DDP - Delivered Duty Paid</option>
                                            <option value="CIF">CIF - Cost, Insurance & Freight</option>
                                            <option value="FOB">FOB - Free on Board</option>
                                        </select>
                                    </div>
                                    
                                    <!-- US Filing Type -->
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">US Filing Type</label>
                                        <select 
                                            v-model="form.us_filing_type"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                        >
                                            <option value="30.37(a)">30.37(a) - Under $2,500</option>
                                            <option value="30.36">30.36 - Canada/Mexico Low Value</option>
                                            <option value="">ITN Required (enter below)</option>
                                        </select>
                                        <p class="mt-1 text-xs text-gray-500">For shipments &gt;$2,500, leave blank and enter ITN</p>
                                    </div>
                                    
                                    <!-- Invoice Signature Name -->
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Invoice Signature Name</label>
                                        <input 
                                            v-model="form.invoice_signature_name"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                            placeholder="Authorized Shipper"
                                        />
                                    </div>
                                    
                                    <!-- Invoice Signature Title -->
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Signature Title</label>
                                        <select 
                                            v-model="form.invoice_signature_title"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                        >
                                            <option value="Mr.">Mr.</option>
                                            <option value="Ms.">Ms.</option>
                                            <option value="Dr.">Dr.</option>
                                            <option value="">None</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Exporter ID -->
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Exporter ID / License</label>
                                        <input 
                                            v-model="form.exporter_id"
                                            type="text"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                            placeholder="EAR99"
                                        />
                                        <p class="mt-1 text-xs text-gray-500">EAR99 = No license required</p>
                                    </div>
                                    
                                    <!-- Exporter Code -->
                                    <div>
                                        <label class="block mb-1 text-sm font-medium text-gray-700">Exporter Code</label>
                                        <select 
                                            v-model="form.exporter_code"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-amber-500 focus:border-amber-500"
                                        >
                                            <option value="EXPCZ">EXPCZ - Export Czech Rep</option>
                                            <option value="USHTS">USHTS - US HTS Code</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Custom Invoice Section -->
                                <div class="mt-6 pt-6 border-t border-gray-200">
                                    <h3 class="flex items-center gap-2 mb-4 text-sm font-semibold text-gray-900">
                                        <i class="fa-solid fa-file-invoice text-amber-500"></i>
                                        Commercial Invoice (Paperless Trade)
                                    </h3>
                                    
                                    <!-- Toggle -->
                                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                                        <div>
                                            <p class="font-medium text-gray-900">Use Custom Invoice</p>
                                            <p class="text-sm text-gray-500">Upload your own commercial invoice instead of system-generated</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input 
                                                type="checkbox" 
                                                v-model="form.use_custom_invoice"
                                                class="sr-only peer"
                                            />
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                                        </label>
                                    </div>
                                    
                                    <!-- File Upload (when toggle enabled) -->
                                    <div v-if="form.use_custom_invoice" class="mt-4 p-4 border-2 border-dashed border-amber-300 rounded-lg bg-amber-50">
                                        <label class="block mb-2 text-sm font-medium text-gray-700">Upload Custom Invoice (PDF)</label>
                                        <input 
                                            type="file"
                                            @change="form.custom_invoice_file = $event.target.files[0]"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200"
                                        />
                                        <p class="mt-2 text-xs text-gray-500">Accepted: PDF, JPG, PNG (max 5MB)</p>
                                        
                                        <!-- Existing custom invoice preview -->
                                        <a 
                                            v-if="ship?.custom_invoice_path && !form.custom_invoice_file"
                                            :href="`/storage/${ship.custom_invoice_path}`"
                                            target="_blank"
                                            class="inline-flex items-center gap-2 mt-3 text-sm text-amber-600 hover:text-amber-700"
                                        >
                                            <i class="fa-solid fa-file-pdf"></i>
                                            View Current Custom Invoice
                                        </a>
                                    </div>
                                    
                                    <!-- System Generated Invoice Preview -->
                                    <div v-else class="mt-4 flex items-center gap-2 text-sm text-gray-600">
                                        <i class="fa-solid fa-robot text-gray-400"></i>
                                        <span>System will auto-generate commercial invoice from shipment data</span>
                                        <a 
                                            v-if="ship?.generated_invoice_path"
                                            :href="`/storage/${ship.generated_invoice_path}`"
                                            target="_blank"
                                            class="ml-2 text-primary-600 hover:text-primary-700"
                                        >
                                            <i class="fa-solid fa-eye"></i> Preview
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Packages Card -->
                        <div class="overflow-hidden bg-white border rounded-xl shadow-sm">
                            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50">
                                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                                    <i class="fa-solid fa-boxes-stacked text-primary-500"></i>
                                    Packages ({{ packages.length }})
                                </h2>
                                <button 
                                    v-if="packages.length > 0"
                                    @click="toggleAllPackages"
                                    class="text-sm text-primary-600 hover:text-primary-700"
                                >
                                    {{ expandedPackages.size === packages.length ? 'Collapse All' : 'Expand All' }}
                                </button>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <template v-for="pkg in packages" :key="pkg.id">
                                    <!-- Package Row -->
                                    <div 
                                        @click="togglePackage(pkg.id)"
                                        class="flex items-center justify-between px-6 py-4 cursor-pointer hover:bg-gray-50"
                                    >
                                        <div class="flex items-center gap-4">
                                            <i :class="['fa-solid text-gray-400 transition-transform', expandedPackages.has(pkg.id) ? 'fa-chevron-down' : 'fa-chevron-right']"></i>
                                            <div>
                                                <p class="font-medium text-gray-900">{{ pkg.package_id || `PKG-${pkg.id}` }}</p>
                                                <p class="text-sm text-gray-500">{{ pkg.from || 'Unknown Origin' }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-6 text-sm">
                                            <!-- Classification badges -->
                                            <div class="flex gap-1">
                                                <span v-if="hasClassification(pkg).dangerous" class="w-6 h-6 rounded-full bg-red-100 flex items-center justify-center" title="Dangerous">
                                                    <i class="fas fa-fire text-red-600 text-xs"></i>
                                                </span>
                                                <span v-if="hasClassification(pkg).fragile" class="w-6 h-6 rounded-full bg-amber-100 flex items-center justify-center" title="Fragile">
                                                    <i class="fas fa-wine-glass text-amber-600 text-xs"></i>
                                                </span>
                                                <span v-if="hasClassification(pkg).oversized" class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center" title="Oversized">
                                                    <i class="fas fa-expand-arrows-alt text-blue-600 text-xs"></i>
                                                </span>
                                            </div>
                                            <span class="text-gray-500">{{ pkg.total_weight || 0 }} lbs</span>
                                            <span class="font-medium text-gray-900">{{ formatCurrency(pkg.total_value) }}</span>
                                            <span class="px-2 py-1 text-xs rounded-full" :class="pkg.status_name === 'Ready to Send' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'">
                                                {{ pkg.status_name || 'Unknown' }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Expanded Package Details -->
                                    <div v-if="expandedPackages.has(pkg.id)" class="px-6 py-4 bg-gray-50">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <!-- Items -->
                                            <div v-if="pkg.items?.length > 0">
                                                <h4 class="mb-2 text-sm font-semibold text-gray-700">Items ({{ pkg.items.length }})</h4>
                                                <div class="space-y-2">
                                                    <div v-for="item in pkg.items" :key="item.id" class="p-3 bg-white rounded-lg border">
                                                        <div class="flex items-start justify-between">
                                                            <div>
                                                                <p class="font-medium text-gray-900">{{ item.title }}</p>
                                                                <p v-if="item.description" class="text-sm text-gray-500">{{ item.description }}</p>
                                                            </div>
                                                            <div class="flex gap-1">
                                                                <span v-if="item.is_dangerous" class="px-1.5 py-0.5 text-xs bg-red-100 text-red-700 rounded">DG</span>
                                                                <span v-if="item.is_fragile" class="px-1.5 py-0.5 text-xs bg-amber-100 text-amber-700 rounded">Fragile</span>
                                                            </div>
                                                        </div>
                                                        <p class="mt-1 text-sm text-gray-600">
                                                            Qty: {{ item.quantity }} × {{ formatCurrency(item.value_per_unit) }} = {{ formatCurrency(item.total_line_value) }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Files & Invoices -->
                                            <div>
                                                <!-- Photos -->
                                                <div v-if="pkg.files?.length > 0" class="mb-4">
                                                    <h4 class="mb-2 text-sm font-semibold text-gray-700">Photos ({{ pkg.files.length }})</h4>
                                                    <div class="flex flex-wrap gap-2">
                                                        <img 
                                                            v-for="(file, idx) in pkg.files.slice(0, 4)" 
                                                            :key="file.id"
                                                            :src="file.file_with_url"
                                                            @click.stop="openLightbox(pkg.files, idx)"
                                                            class="object-cover w-16 h-16 rounded-lg border cursor-pointer hover:opacity-80"
                                                        />
                                                        <div 
                                                            v-if="pkg.files.length > 4"
                                                            @click.stop="openLightbox(pkg.files, 4)"
                                                            class="flex items-center justify-center w-16 h-16 text-sm font-medium text-gray-500 bg-gray-100 rounded-lg border cursor-pointer hover:bg-gray-200"
                                                        >
                                                            +{{ pkg.files.length - 4 }}
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Invoices (from items) -->
                                                <div v-if="pkg.items?.some(item => item.invoice_files?.length > 0)">
                                                    <h4 class="mb-2 text-sm font-semibold text-gray-700">
                                                        Invoices ({{ pkg.items.reduce((sum, item) => sum + (item.invoice_files?.length || 0), 0) }})
                                                    </h4>
                                                    <div class="space-y-1">
                                                        <template v-for="item in pkg.items" :key="'invoice-item-' + item.id">
                                                            <a 
                                                                v-for="invoiceFile in (item.invoice_files || [])"
                                                                :key="invoiceFile.id"
                                                                :href="invoiceFile.file_with_url || `/storage/${invoiceFile.file}`"
                                                                target="_blank"
                                                                class="flex items-center gap-2 text-sm text-primary-600 hover:text-primary-700"
                                                            >
                                                                <i :class="['fa-solid', invoiceFile.file_type === 'pdf' ? 'fa-file-pdf' : 'fa-file-image']"></i>
                                                                {{ item.title }} - Invoice
                                                            </a>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                
                                <!-- Empty state -->
                                <div v-if="packages.length === 0" class="px-6 py-12 text-center">
                                    <i class="mb-4 text-4xl text-gray-300 fa-solid fa-box-open"></i>
                                    <p class="text-gray-500">No packages in this shipment</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (1/3) -->
                    <div class="space-y-6">
                        
                        <!-- Customer Card -->
                        <div class="overflow-hidden bg-white border rounded-xl shadow-sm">
                            <div class="px-5 py-4 border-b bg-gray-50">
                                <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                    <i class="fa-solid fa-user text-primary-500"></i>
                                    Customer
                                </h3>
                            </div>
                            <div class="p-5">
                                <div class="space-y-3">
                                    <div>
                                        <p class="text-lg font-medium text-gray-900">{{ customer?.name || 'Unknown' }}</p>
                                        <p class="text-sm text-gray-500">{{ customer?.email }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 text-sm">
                                        <i class="fa-solid fa-box text-gray-400"></i>
                                        <span class="text-gray-600">Suite:</span>
                                        <span class="font-medium text-gray-900">{{ customer?.suite || 'N/A' }}</span>
                                    </div>
                                    <Link 
                                        v-if="customer?.id && can('customers.view')"
                                        :href="route('admin.customers.edit', customer.id)"
                                        class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700"
                                    >
                                        View Profile
                                        <i class="fa-solid fa-arrow-right text-xs"></i>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- Delivery Address Card -->
                        <div class="overflow-hidden bg-white border rounded-xl shadow-sm">
                            <div class="px-5 py-4 border-b bg-gray-50">
                                <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                    <i class="fa-solid fa-location-dot text-primary-500"></i>
                                    Delivery Address
                                </h3>
                            </div>
                            <div class="p-5">
                                <div v-if="address" class="space-y-1 text-sm text-gray-700">
                                    <p class="font-medium">{{ address.full_name }}</p>
                                    <p>{{ address.address_line_1 }}</p>
                                    <p v-if="address.address_line_2">{{ address.address_line_2 }}</p>
                                    <p>{{ address.city }}, {{ address.state }} {{ address.postal_code }}</p>
                                    <p>{{ address.country }}</p>
                                    <p v-if="address.phone_number" class="pt-2">
                                        <i class="fa-solid fa-phone text-gray-400 mr-1"></i>
                                        {{ address.phone_number }}
                                    </p>
                                </div>
                                <p v-else class="text-sm text-gray-500">No address on file</p>
                            </div>
                        </div>

                        <!-- Carrier & Service Card -->
                        <div class="overflow-hidden bg-white border rounded-xl shadow-sm">
                            <div class="px-5 py-4 border-b bg-gray-50">
                                <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                    <i class="fa-solid fa-truck text-primary-500"></i>
                                    Carrier & Service
                                </h3>
                            </div>
                            <div class="p-5">
                                <div class="space-y-3">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Carrier:</span>
                                        <span class="font-medium text-gray-900">{{ carrierService?.display_name || ship?.carrier_name || 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Service:</span>
                                        <span class="font-medium text-gray-900">{{ carrierService?.service_code || ship?.carrier_service_type || 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span class="text-gray-500">Shipping:</span>
                                        <span class="font-medium text-gray-900">{{ formatCurrency(ship?.estimated_shipping_charges) }}</span>
                                    </div>
                                    <div v-if="ship?.addon_charges > 0" class="flex justify-between text-sm">
                                        <span class="text-gray-500">Addons:</span>
                                        <span class="font-medium text-gray-900">{{ formatCurrency(ship?.addon_charges) }}</span>
                                    </div>
                                    <div class="pt-2 mt-2 border-t">
                                        <div class="flex justify-between text-sm font-semibold">
                                            <span class="text-gray-700">Total:</span>
                                            <span class="text-primary-600">{{ formatCurrency(ship?.total_price) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tracking Timeline Card -->
                        <div v-if="trackingEvents.length > 0" class="overflow-hidden bg-white border rounded-xl shadow-sm">
                            <div class="px-5 py-4 border-b bg-gray-50">
                                <h3 class="flex items-center gap-2 font-semibold text-gray-900">
                                    <i class="fa-solid fa-timeline text-primary-500"></i>
                                    Recent Activity
                                </h3>
                            </div>
                            <div class="p-5">
                                <div class="space-y-3">
                                    <div 
                                        v-for="(event, idx) in trackingEvents.slice(0, 5)" 
                                        :key="event.id"
                                        class="flex gap-3"
                                    >
                                        <div class="flex flex-col items-center">
                                            <div :class="['w-2.5 h-2.5 rounded-full', idx === 0 ? 'bg-primary-500' : 'bg-gray-300']"></div>
                                            <div v-if="idx < Math.min(trackingEvents.length, 5) - 1" class="w-0.5 h-full bg-gray-200"></div>
                                        </div>
                                        <div class="flex-1 pb-3">
                                            <p class="text-sm font-medium text-gray-900">{{ event.event_description || event.status }}</p>
                                            <p class="text-xs text-gray-500">{{ formatDateTime(event.event_time) }}</p>
                                        </div>
                                    </div>
                                </div>
                                <Link 
                                    v-if="trackingEvents.length > 5"
                                    :href="route('admin.shipments.tracking', ship?.id)"
                                    class="inline-flex items-center gap-1 mt-2 text-sm text-primary-600 hover:text-primary-700"
                                >
                                    View all {{ trackingEvents.length }} events
                                    <i class="fa-solid fa-arrow-right text-xs"></i>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lightbox -->
        <PhotoLightbox 
            v-if="showLightbox"
            :images="lightboxImages"
            :initial-index="lightboxIndex"
            @close="showLightbox = false"
        />
    </AuthenticatedLayout>
</template>

<style scoped>
.vue-select-lg :deep(.vs__dropdown-toggle) {
    padding: 6px 12px;
    border-radius: 0.5rem;
    border-color: rgb(209 213 219);
}

.vue-select-lg :deep(.vs__dropdown-toggle:focus-within) {
    border-color: var(--primary-500, #6366f1);
    box-shadow: 0 0 0 1px var(--primary-500, #6366f1);
}

/* Fix dropdown being cut off by parent containers */
.vue-select-lg :deep(.vs__dropdown-menu) {
    z-index: 9999 !important;
    position: absolute;
    max-height: 300px;
    overflow-y: auto;
}

/* Ensure dropdown container doesn't clip */
.vue-select-lg :deep(.vs__dropdown-toggle) {
    overflow: visible;
}

.vue-select-lg {
    position: relative;
    z-index: 100;
}
</style>
