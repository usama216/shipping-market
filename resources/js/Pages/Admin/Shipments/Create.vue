<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, computed, watch } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import axios from "axios";

// Components
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import CarrierSelector from "@/Components/Shipment/CarrierSelector.vue";
import AddonSelector from "@/Components/Shipment/AddonSelector.vue";

const props = defineProps({
    customers: Array,
    carrierServices: Array,
    carrierAddons: Array,
    preselectedCustomerId: [Number, String],
});

const toast = useToast();

// ============================================
// STATE
// ============================================

// Customer selection
const searchQuery = ref("");
const searchResults = ref([]);
const selectedCustomer = ref(null);
const isSearching = ref(false);

// Customer data
const availablePackages = ref([]);
const customerAddresses = ref([]);
const isLoadingPackages = ref(false);

// Selected items
const selectedPackageIds = ref([]);
const selectedAddressId = ref(null);
const selectedCarrierServiceId = ref(null);
const selectedAddonIds = ref([]);

// EEI Code (per shipment, not per item)
const eeiCode = ref('');
const eeiRequired = ref(false);
const eeiExemptionReason = ref('');

// Pricing
const carrierRate = ref(0);
const addonCharges = ref(0);
const baseHandlingFee = 10.0;

// Enriched addon data from carrier selector
const enrichedAddons = ref([]);

// UI State
const isSubmitting = ref(false);

// ============================================
// COMPUTED
// ============================================

const selectedPackages = computed(() => {
    return availablePackages.value.filter(pkg => 
        selectedPackageIds.value.includes(pkg.id)
    );
});

const totalWeight = computed(() => {
    return selectedPackages.value.reduce(
        (sum, pkg) => sum + Number(pkg.total_weight || 0), 0
    );
});

const totalBilledWeight = computed(() => {
    return selectedPackages.value.reduce(
        (sum, pkg) => sum + Number(pkg.billed_weight || pkg.total_weight || 0), 0
    );
});

const totalDeclaredValue = computed(() => {
    return selectedPackages.value.reduce(
        (sum, pkg) => sum + Number(pkg.total_value || 0), 0
    );
});

// Classification charges
const classificationCharges = ref(0);
const classificationBreakdown = ref({
    dangerous: 0,
    fragile: 0,
    oversized: 0,
});
const classificationItemCounts = ref({
    dangerous: 0,
    fragile: 0,
    oversized: 0,
});

const estimatedTotal = computed(() => {
    return baseHandlingFee + carrierRate.value + addonCharges.value + classificationCharges.value;
});

const canSubmit = computed(() => {
    return (
        selectedCustomer.value &&
        selectedPackageIds.value.length > 0 &&
        selectedAddressId.value &&
        selectedCarrierServiceId.value !== null &&
        carrierRate.value > 0
    );
});

// ============================================
// METHODS
// ============================================

// Customer search
let searchTimeout = null;
const searchCustomers = async () => {
    if (!searchQuery.value || searchQuery.value.length < 2) {
        searchResults.value = [];
        return;
    }

    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
        isSearching.value = true;
        try {
            const response = await axios.get(route('admin.shipments.searchCustomers'), {
                params: { search: searchQuery.value }
            });
            searchResults.value = response.data.customers;
        } catch (error) {
            console.error('Customer search failed:', error);
            toast.error('Failed to search customers');
        } finally {
            isSearching.value = false;
        }
    }, 300);
};

const selectCustomer = async (customer) => {
    selectedCustomer.value = customer;
    searchQuery.value = '';
    searchResults.value = [];
    
    // Reset selections
    selectedPackageIds.value = [];
    selectedAddressId.value = null;
    
    // Load customer packages and addresses
    await loadCustomerData(customer.id);
};

const loadCustomerData = async (customerId) => {
    isLoadingPackages.value = true;
    try {
        const response = await axios.get(
            route('admin.shipments.availablePackages', { customer: customerId })
        );
        availablePackages.value = response.data.packages;
        customerAddresses.value = response.data.addresses;
        
        // Don't auto-select address - user must explicitly choose
    } catch (error) {
        console.error('Failed to load customer data:', error);
        toast.error('Failed to load customer packages');
    } finally {
        isLoadingPackages.value = false;
    }
};

const togglePackage = (pkgId) => {
    const idx = selectedPackageIds.value.indexOf(pkgId);
    if (idx === -1) {
        selectedPackageIds.value.push(pkgId);
    } else {
        selectedPackageIds.value.splice(idx, 1);
    }
};

const selectAllPackages = () => {
    selectedPackageIds.value = availablePackages.value.map(pkg => pkg.id);
};

const clearPackageSelection = () => {
    selectedPackageIds.value = [];
};

const handleCarrierChange = (data) => {
    selectedCarrierServiceId.value = data.carrierServiceId || null;
    carrierRate.value = data.price || 0;
    
    // Store enriched addon data
    if (data.carrierAddons) {
        enrichedAddons.value = data.carrierAddons;
    }
    
    // Store classification charges
    if (data.classificationCharges) {
        classificationCharges.value = data.classificationCharges.total || 0;
        classificationBreakdown.value = data.classificationCharges.breakdown || {
            dangerous: 0,
            fragile: 0,
            oversized: 0,
        };
        classificationItemCounts.value = data.classificationCharges.item_counts || {
            dangerous: 0,
            fragile: 0,
            oversized: 0,
        };
    }
};

const handleAddonsChanged = (data) => {
    selectedAddonIds.value = data.selectedAddonIds || [];
    addonCharges.value = data.totalCharges || 0;
};

const clearCustomer = () => {
    selectedCustomer.value = null;
    availablePackages.value = [];
    customerAddresses.value = [];
    selectedPackageIds.value = [];
    selectedAddressId.value = null;
    carrierRate.value = 0;
    addonCharges.value = 0;
};

const submitShipment = () => {
    if (!canSubmit.value) {
        if (!selectedCarrierServiceId.value) {
            toast.error('Please select a carrier service');
        } else {
            toast.error('Please complete all required fields');
        }
        return;
    }

    isSubmitting.value = true;

    const form = useForm({
        customer_id: selectedCustomer.value.id,
        package_ids: selectedPackageIds.value,
        customer_address_id: selectedAddressId.value,
        carrier_service_id: selectedCarrierServiceId.value,
        selected_addon_ids: selectedAddonIds.value,
        declared_value: totalDeclaredValue.value,
        estimated_shipping_charges: estimatedTotal.value,
        eei_code: eeiRequired.value ? eeiCode.value : null,
        eei_required: eeiRequired.value,
        eei_exemption_reason: eeiRequired.value ? null : 'NO EEI REQUIRED – FTR 30.37(a)',
    });

    form.post(route('admin.shipments.store'), {
        onSuccess: () => {
            toast.success('Shipment created successfully!');
        },
        onError: (errors) => {
            toast.error(errors.message || 'Failed to create shipment');
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

// Watch for search query changes
watch(searchQuery, searchCustomers);
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Create Shipment" />

        <div class="min-h-screen py-6 bg-gray-50">
            <div class="px-4 mx-auto max-w-5xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="flex items-center gap-3 text-2xl font-bold text-gray-900">
                                <i class="fa-solid fa-plus-circle text-primary-500"></i>
                                Create Shipment
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">
                                Create a shipment on behalf of a customer
                            </p>
                        </div>
                        <SecondaryButton @click="router.visit(route('admin.shipments'))">
                            <i class="fa-solid fa-arrow-left mr-2"></i>
                            Back to Shipments
                        </SecondaryButton>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Main Content -->
                    <div class="space-y-6 lg:col-span-2">
                        
                        <!-- Step 1: Customer Selection -->
                        <section class="p-6 bg-white border rounded-lg shadow-sm">
                            <h2 class="flex items-center gap-2 mb-4 text-lg font-semibold text-gray-800">
                                <span class="flex items-center justify-center w-6 h-6 text-sm font-bold text-white rounded-full bg-primary-500">1</span>
                                Select Customer
                            </h2>

                            <div v-if="!selectedCustomer" class="relative">
                                <div class="relative">
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search by name, email, or suite number..."
                                        class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                                    />
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <i v-if="isSearching" class="fa-solid fa-spinner fa-spin text-gray-400"></i>
                                        <i v-else class="fa-solid fa-search text-gray-400"></i>
                                    </div>
                                </div>

                                <!-- Search Results -->
                                <div v-if="searchResults.length > 0" 
                                     class="absolute z-10 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-64 overflow-auto">
                                    <button
                                        v-for="customer in searchResults"
                                        :key="customer.id"
                                        @click="selectCustomer(customer)"
                                        class="w-full px-4 py-3 text-left hover:bg-gray-50 border-b last:border-b-0"
                                    >
                                        <div class="font-medium text-gray-900">{{ customer.name }}</div>
                                        <div class="text-sm text-gray-500">
                                            <span class="mr-3">{{ customer.email }}</span>
                                            <span v-if="customer.suite" class="text-primary-600 font-medium">Suite {{ customer.suite }}</span>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <!-- Selected Customer -->
                            <div v-else class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center justify-center w-10 h-10 bg-green-500 rounded-full">
                                        <i class="fa-solid fa-user text-white"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">{{ selectedCustomer.name }}</div>
                                        <div class="text-sm text-gray-600">
                                            {{ selectedCustomer.email }}
                                            <span v-if="selectedCustomer.suite" class="ml-2 text-primary-600">
                                                Suite {{ selectedCustomer.suite }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <button @click="clearCustomer" class="text-gray-400 hover:text-red-500">
                                    <i class="fa-solid fa-times-circle text-xl"></i>
                                </button>
                            </div>
                        </section>

                        <!-- Step 2: Package Selection -->
                        <section v-if="selectedCustomer" class="p-6 bg-white border rounded-lg shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-800">
                                    <span class="flex items-center justify-center w-6 h-6 text-sm font-bold text-white rounded-full bg-primary-500">2</span>
                                    Select Packages
                                </h2>
                                <div class="flex gap-2">
                                    <button @click="selectAllPackages" class="text-sm text-primary-600 hover:text-primary-800">
                                        Select All
                                    </button>
                                    <span class="text-gray-300">|</span>
                                    <button @click="clearPackageSelection" class="text-sm text-gray-500 hover:text-gray-700">
                                        Clear
                                    </button>
                                </div>
                            </div>

                            <div v-if="isLoadingPackages" class="flex items-center justify-center py-8">
                                <i class="fa-solid fa-spinner fa-spin text-2xl text-primary-500"></i>
                            </div>

                            <div v-else-if="availablePackages.length === 0" class="py-8 text-center text-gray-500">
                                <i class="fa-solid fa-box-open text-4xl mb-2 text-gray-300"></i>
                                <p>No eligible packages found for this customer.</p>
                                <p class="text-sm">Packages must be "Ready to Send" or "Consolidated" status.</p>
                            </div>

                            <div v-else class="space-y-2 max-h-80 overflow-auto">
                                <label
                                    v-for="pkg in availablePackages"
                                    :key="pkg.id"
                                    class="flex items-center gap-4 p-4 border rounded-lg cursor-pointer transition-all"
                                    :class="selectedPackageIds.includes(pkg.id) 
                                        ? 'border-primary-500 bg-primary-50' 
                                        : 'border-gray-200 hover:border-gray-300'"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="selectedPackageIds.includes(pkg.id)"
                                        @change="togglePackage(pkg.id)"
                                        class="w-5 h-5 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900">{{ pkg.package_id }}</span>
                                            <span class="px-2 py-0.5 text-xs rounded-full"
                                                  :class="pkg.status === 3 ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'">
                                                {{ pkg.status_name }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-500 mt-1">
                                            <span class="mr-3">{{ pkg.from || 'Unknown sender' }}</span>
                                            <span>{{ pkg.items_count }} item(s)</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-medium text-gray-900">{{ Number(pkg.billed_weight || 0).toFixed(2) }} lbs</div>
                                        <div class="text-sm text-green-600">${{ Number(pkg.total_value || 0).toFixed(2) }}</div>
                                    </div>
                                </label>
                            </div>

                            <!-- Selection Summary -->
                            <div v-if="selectedPackageIds.length > 0" class="mt-4 p-4 bg-gray-50 rounded-lg">
                                <div class="grid grid-cols-3 gap-4 text-center">
                                    <div>
                                        <div class="text-sm text-gray-500">Selected</div>
                                        <div class="text-xl font-bold text-primary-600">{{ selectedPackageIds.length }}</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Billed Weight</div>
                                        <div class="text-xl font-bold text-gray-900">{{ totalBilledWeight.toFixed(2) }} lbs</div>
                                    </div>
                                    <div>
                                        <div class="text-sm text-gray-500">Declared Value</div>
                                        <div class="text-xl font-bold text-green-600">${{ totalDeclaredValue.toFixed(2) }}</div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Step 3: Destination Address -->
                        <section v-if="selectedPackageIds.length > 0" class="p-6 bg-white border rounded-lg shadow-sm">
                            <h2 class="flex items-center gap-2 mb-4 text-lg font-semibold text-gray-800">
                                <span class="flex items-center justify-center w-6 h-6 text-sm font-bold text-white rounded-full bg-primary-500">3</span>
                                Destination Address
                            </h2>

                            <div v-if="customerAddresses.length === 0" class="py-4 text-center text-gray-500">
                                <i class="fa-solid fa-location-dot text-2xl mb-2 text-gray-300"></i>
                                <p>No saved addresses found for this customer.</p>
                            </div>

                            <div v-else class="space-y-2">
                                <label
                                    v-for="addr in customerAddresses"
                                    :key="addr.id"
                                    class="flex items-center gap-4 p-4 border rounded-lg cursor-pointer transition-all"
                                    :class="selectedAddressId === addr.id 
                                        ? 'border-primary-500 bg-primary-50' 
                                        : 'border-gray-200 hover:border-gray-300'"
                                >
                                    <input
                                        type="radio"
                                        name="address"
                                        :value="addr.id"
                                        v-model="selectedAddressId"
                                        class="w-5 h-5 text-primary-600 border-gray-300 focus:ring-primary-500"
                                    />
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">{{ addr.label }}</div>
                                        <div class="text-sm text-gray-500">
                                            {{ addr.country }}
                                            <span v-if="addr.is_default" class="ml-2 px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">
                                                Default
                                            </span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </section>

                        <!-- Step 4: Carrier Selection (only after packages AND address selected) -->
                        <section v-if="selectedPackageIds.length > 0 && selectedAddressId" class="p-6 bg-white border rounded-lg shadow-sm">
                            <h2 class="flex items-center gap-2 mb-4 text-lg font-semibold text-gray-800">
                                <span class="flex items-center justify-center w-6 h-6 text-sm font-bold text-white rounded-full bg-primary-500">4</span>
                                Select Carrier
                            </h2>

                            <CarrierSelector
                                :package-ids="selectedPackageIds"
                                :address-id="selectedAddressId"
                                :selected-method-id="null"
                                @rate-changed="handleCarrierChange"
                            />
                        </section>

                        <!-- Step 5: Addons -->
                        <section v-if="enrichedAddons.length > 0" class="p-6 bg-white border rounded-lg shadow-sm">
                            <h2 class="flex items-center gap-2 mb-4 text-lg font-semibold text-gray-800">
                                <span class="flex items-center justify-center w-6 h-6 text-sm font-bold text-white rounded-full bg-primary-500">5</span>
                                Additional Services
                            </h2>

                            <AddonSelector
                                :carrier-addons="enrichedAddons"
                                :selected-addons="selectedAddonIds"
                                :declared-value="totalDeclaredValue"
                                @addons-changed="handleAddonsChanged"
                            />
                        </section>
                    </div>

                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-6 space-y-4">
                            <!-- Order Summary -->
                            <div class="p-6 bg-white border rounded-lg shadow-sm">
                                <h3 class="text-lg font-semibold text-gray-900 mb-4">Shipment Summary</h3>
                                
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Base Handling Fee</span>
                                        <span class="font-medium">${{ baseHandlingFee.toFixed(2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Carrier Shipping</span>
                                        <span class="font-medium">${{ carrierRate.toFixed(2) }}</span>
                                    </div>
                                    <div v-if="addonCharges > 0" class="flex justify-between">
                                        <span class="text-gray-600">Additional Services</span>
                                        <span class="font-medium">${{ addonCharges.toFixed(2) }}</span>
                                    </div>
                                    <div v-if="classificationCharges > 0" class="flex justify-between">
                                        <span class="text-gray-600">Classification Charges</span>
                                        <span class="font-medium">${{ classificationCharges.toFixed(2) }}</span>
                                    </div>
                                    <div v-if="classificationCharges > 0" class="mt-1 text-xs text-gray-500 space-y-0.5">
                                        <div v-if="classificationBreakdown.dangerous > 0" class="flex justify-between">
                                            <span class="flex items-center gap-1">
                                                <span class="w-3 h-3 rounded-full bg-red-100 flex items-center justify-center" title="Dangerous Goods">
                                                    <i class="fa-solid fa-triangle-exclamation text-red-600 text-[8px]"></i>
                                                </span>
                                                Dangerous ({{ classificationItemCounts.dangerous }})
                                            </span>
                                            <span>${{ classificationBreakdown.dangerous.toFixed(2) }}</span>
                                        </div>
                                        <div v-if="classificationBreakdown.fragile > 0" class="flex justify-between">
                                            <span class="flex items-center gap-1">
                                                <span class="w-3 h-3 rounded-full bg-amber-100 flex items-center justify-center" title="Fragile">
                                                    <i class="fa-solid fa-wine-glass text-amber-600 text-[8px]"></i>
                                                </span>
                                                Fragile ({{ classificationItemCounts.fragile }})
                                            </span>
                                            <span>${{ classificationBreakdown.fragile.toFixed(2) }}</span>
                                        </div>
                                        <div v-if="classificationBreakdown.oversized > 0" class="flex justify-between">
                                            <span class="flex items-center gap-1">
                                                <span class="w-3 h-3 rounded-full bg-blue-100 flex items-center justify-center" title="Oversized">
                                                    <i class="fa-solid fa-box text-blue-600 text-[8px]"></i>
                                                </span>
                                                Oversized ({{ classificationItemCounts.oversized }})
                                            </span>
                                            <span>${{ classificationBreakdown.oversized.toFixed(2) }}</span>
                                        </div>
                                    </div>
                                    <hr class="my-2" />
                                    <div class="flex justify-between text-lg font-bold">
                                        <span>Total</span>
                                        <span class="text-primary-600">${{ estimatedTotal.toFixed(2) }}</span>
                                    </div>
                                </div>

                                <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-sm">
                                    <div class="flex items-start gap-2">
                                        <i class="fa-solid fa-info-circle text-amber-500 mt-0.5"></i>
                                        <div class="text-amber-800">
                                            <strong>Payment Bypass:</strong> This shipment will be marked as paid without processing payment.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <PrimaryButton
                                @click="submitShipment"
                                :disabled="!canSubmit || isSubmitting"
                                class="w-full justify-center py-4 text-lg"
                            >
                                <span v-if="isSubmitting">
                                    <i class="fa-solid fa-spinner fa-spin mr-2"></i>
                                    Creating...
                                </span>
                                <span v-else>
                                    <i class="fa-solid fa-check mr-2"></i>
                                    Create Shipment
                                </span>
                            </PrimaryButton>

                            <!-- Validation Feedback -->
                            <div v-if="!canSubmit && selectedCustomer" class="p-3 bg-gray-50 border rounded-lg text-sm">
                                <p class="font-medium text-gray-700 mb-2">Complete these steps:</p>
                                <ul class="text-gray-600 space-y-1">
                                    <li v-if="selectedPackageIds.length === 0" class="flex items-center gap-2">
                                        <i class="fa-solid fa-circle text-[6px] text-gray-400"></i>
                                        Select at least one package
                                    </li>
                                    <li v-if="!selectedAddressId" class="flex items-center gap-2">
                                        <i class="fa-solid fa-circle text-[6px] text-gray-400"></i>
                                        Select a destination address
                                    </li>
                                    <li v-if="carrierRate === 0" class="flex items-center gap-2">
                                        <i class="fa-solid fa-circle text-[6px] text-gray-400"></i>
                                        Select a carrier service
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
