<script setup>
import { ref, computed, watch, onMounted, nextTick } from "vue";
import axios from "axios";
import { useToast } from "vue-toastification";

const toast = useToast();

const props = defineProps({
    shipId: {
        type: [Number, String],
        default: null,
    },
    // Alternative: for operator create flow where shipId doesn't exist yet
    packageIds: {
        type: Array,
        default: () => [],
    },
    totalWeight: {
        type: Number,
        default: 0,
    },
    dimensions: {
        type: Object,
        default: () => ({ length: 10, width: 10, height: 10 }),
    },
    addressId: {
        type: [Number, String],
        default: null,
    },
    internationalShippingOptions: {
        type: Array,
        default: () => [],
    },
    selectedMethodId: {
        type: [Number, String],
        default: null,
    },
    userPreferences: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["update:selectedMethodId", "rateChanged", "addonsLoaded"]);

// State
const isLoading = ref(false);
const carrierLoading = ref({ fedex: false, dhl: false, ups: false }); // Per-carrier loading
const activeCarrier = ref('fedex');
const allCarrierRates = ref({});
const selectedService = ref(null);
const errorMessage = ref(null);

// New: Enriched addon data from backend (with live pricing, is_available, is_mandatory)
const carrierAddons = ref({});
const packageClassifications = ref({});
const classificationCharges = ref({
    total: 0,
    breakdown: { dangerous: 0, fragile: 0, oversized: 0 },
    item_counts: { dangerous: 0, fragile: 0, oversized: 0 },
});

// Available carriers
const carriers = [
    { id: 'fedex', name: 'FedEx', logo: '/images/carriers/fedex.svg' },
    { id: 'dhl', name: 'DHL Express', logo: '/images/carriers/dhl.svg' },
    { id: 'ups', name: 'UPS', logo: '/images/carriers/ups.svg' },
];

// Get rates for active carrier
const activeCarrierRates = computed(() => {
    const data = allCarrierRates.value[activeCarrier.value];
    if (!data) return [];
    return data.rates || [];
});

// Check if carrier has rates
const carrierHasRates = (carrierId) => {
    const data = allCarrierRates.value[carrierId];
    return data && data.rates && data.rates.length > 0;
};

// Is carrier enabled
const isCarrierEnabled = (carrierId) => {
    const data = allCarrierRates.value[carrierId];
    return data && data.enabled;
};

// Get min price for carrier (for tab display)
const getCarrierMinPrice = (carrierId) => {
    const data = allCarrierRates.value[carrierId];
    if (!data || !data.rates || data.rates.length === 0) return null;
    return Math.min(...data.rates.map(r => r.price));
};

// Get carrier error message
const getCarrierError = (carrierId) => {
    const data = allCarrierRates.value[carrierId];
    return data?.error || null;
};

// Retry configuration
const MAX_RETRIES = 3;
const RETRY_DELAY_BASE = 1000; // 1 second base delay

// Helper: delay function for retry backoff
const delay = (ms) => new Promise(resolve => setTimeout(resolve, ms));

// Fetch all carrier rates from backend with retry logic
const fetchAllRates = async (retryCount = 0) => {
    // Need ship_id OR package_ids to get package data
    const hasShipId = props.shipId;
    const hasPackageIds = props.packageIds && props.packageIds.length > 0;

    if (!hasShipId && !hasPackageIds) {
        initializeEmptyCarriers();
        return;
    }

    isLoading.value = true;
    errorMessage.value = null;

    try {
        // Use different endpoints based on whether we have shipId or packageIds
        let response;
        if (hasShipId) {
            response = await axios.post(
                route("customer.shipment.getAllRates"),
                {
                    ship_id: props.shipId,
                    address_id: props.addressId,
                }
            );
        } else {
            // Operator flow - use package_ids
            response = await axios.post(
                route("admin.shipments.getRates"),
                {
                    package_ids: props.packageIds,
                    address_id: props.addressId,
                }
            );
        }

        if (response.data.success) {
            // New unified response format: { carriers: {...}, carrier_addons: {...}, package_classifications: {...} }
            const data = response.data.data;
            allCarrierRates.value = data.carriers || data;
            
            // Store enriched addon data per carrier
            if (data.carrier_addons) {
                carrierAddons.value = data.carrier_addons;
            }
            
            // Store package classifications for UI messaging
            if (data.package_classifications) {
                packageClassifications.value = data.package_classifications;
            }
            
            // Store classification charges
            if (data.classification_charges) {
                classificationCharges.value = data.classification_charges;
            }
            
            // Auto-select cheapest option from first carrier with rates
            await nextTick();
            autoSelectBest();
        } else if (retryCount < MAX_RETRIES - 1) {
            // API returned success: false, retry
            console.warn(`Rate fetch returned false, retrying (${retryCount + 1}/${MAX_RETRIES})...`);
            await delay(RETRY_DELAY_BASE * Math.pow(2, retryCount));
            return fetchAllRates(retryCount + 1);
        } else {
            // Max retries reached - show error, no fallback
            console.error("Max retries reached");
            initializeEmptyCarriers('Unable to fetch rates. Please try again.');
        }
    } catch (error) {
        console.error(`Failed to fetch rates (attempt ${retryCount + 1}/${MAX_RETRIES}):`, error);
        
        // Check if this is a transient error worth retrying
        const isTransientError = error.code === 'ECONNABORTED' || 
                                  error.code === 'ETIMEDOUT' ||
                                  error.response?.status >= 500 ||
                                  !error.response; // Network error
        
        if (isTransientError && retryCount < MAX_RETRIES - 1) {
            // Exponential backoff: 1s, 2s, 4s
            const delayMs = RETRY_DELAY_BASE * Math.pow(2, retryCount);
            console.log(`Retrying in ${delayMs}ms...`);
            await delay(delayMs);
            return fetchAllRates(retryCount + 1);
        }
        
        // Max retries reached or non-transient error - show error, no fallback
        initializeEmptyCarriers('Unable to fetch rates. Please try again.');
    } finally {
        isLoading.value = false;
    }
};

// Fetch rates for a single carrier (for refresh button)
const fetchSingleCarrierRates = async (carrierId) => {
    const hasShipId = props.shipId;
    const hasPackageIds = props.packageIds && props.packageIds.length > 0;

    if (!hasShipId && !hasPackageIds) return;

    carrierLoading.value[carrierId] = true;

    try {
        let response;
        if (hasShipId) {
            response = await axios.post(
                route("customer.shipment.getAllRates"),
                {
                    ship_id: props.shipId,
                    address_id: props.addressId,
                    carrier: carrierId,
                }
            );
        } else {
            response = await axios.post(
                route("admin.shipments.getRates"),
                {
                    package_ids: props.packageIds,
                    address_id: props.addressId,
                    carrier: carrierId,
                }
            );
        }

        if (response.data.success) {
            const data = response.data.data;
            const carrierData = data.carriers?.[carrierId] || data[carrierId];
            
            if (carrierData) {
                allCarrierRates.value[carrierId] = carrierData;
                toast.success(`${carrierId.toUpperCase()} rates refreshed`);
                
                // Auto-select first rate if this carrier has rates and none selected
                if (carrierData.rates?.length > 0 && !selectedService.value) {
                    selectService(carrierData.rates[0]);
                }
            }
        } else {
            // Update carrier with error state
            allCarrierRates.value[carrierId] = {
                ...allCarrierRates.value[carrierId],
                rates: [],
                error: response.data.message || 'Unable to fetch rates',
            };
            toast.error(`Failed to refresh ${carrierId.toUpperCase()} rates`);
        }
    } catch (error) {
        console.error(`Failed to fetch ${carrierId} rates:`, error);
        allCarrierRates.value[carrierId] = {
            ...allCarrierRates.value[carrierId],
            rates: [],
            error: error.response?.data?.message || 'Unable to fetch rates',
        };
        toast.error(`Failed to refresh ${carrierId.toUpperCase()} rates`);
    } finally {
        carrierLoading.value[carrierId] = false;
    }
};

// Auto-select best option
const autoSelectBest = () => {
    // Find first carrier with rates
    for (const carrier of carriers) {
        if (carrierHasRates(carrier.id)) {
            activeCarrier.value = carrier.id;
            const rates = allCarrierRates.value[carrier.id].rates;
            if (rates.length > 0) {
                selectService(rates[0]);
            }
            return;
        }
    }
};

// Initialize empty carriers structure (no fallback - just structure for UI)
const initializeEmptyCarriers = (error = null) => {
    allCarrierRates.value = {
        fedex: { name: 'FedEx', logo: '/images/carriers/fedex.svg', rates: [], enabled: true, error },
        dhl: { name: 'DHL Express', logo: '/images/carriers/dhl.svg', rates: [], enabled: true, error },
        ups: { name: 'UPS', logo: '/images/carriers/ups.svg', rates: [], enabled: true, error },
    };
};

// Select carrier tab
const selectCarrierTab = (carrierId) => {
    activeCarrier.value = carrierId;
};

// Select service
const selectService = (service) => {
    selectedService.value = service;
    emit("update:selectedMethodId", service.id);
    
    // Get addon data for this carrier
    const carrierCode = service.carrier || activeCarrier.value;
    const addonData = carrierAddons.value[carrierCode] || [];
    const carrierData = allCarrierRates.value[carrierCode] || {};
    
    emit("rateChanged", {
        methodId: service.id,
        carrierServiceId: service.carrier_service_id || null,
        price: service.price,
        baseCharge: service.base_charge ?? null,
        totalSurcharges: service.total_surcharges ?? null,
        surchargeBreakdown: service.surcharge_breakdown ?? [],
        carrier: carrierCode,
        service: service.service_name,
        isLiveRate: service.is_live_rate,
        deliveryDate: service.delivery_date,
        // New: enriched addon data
        carrierAddons: addonData,
        checkoutEligible: carrierData.checkout_eligible ?? true,
        checkoutErrors: carrierData.checkout_errors ?? [],
        packageClassifications: packageClassifications.value,
        classificationCharges: classificationCharges.value,
    });
    
    // Also emit addonsLoaded for convenience
    emit("addonsLoaded", {
        carrier: carrierCode,
        addons: addonData,
        classifications: packageClassifications.value,
    });
};


// Format price
const formatPrice = (price) => {
    if (price === null || price === undefined) return '—';
    return `$${Number(price).toFixed(2)}`;
};

// Get service icon class
const getServiceIcon = (serviceName) => {
    const lower = serviceName?.toLowerCase() || '';
    if (lower.includes('overnight') || lower.includes('express') || lower.includes('priority')) return 'fa-solid fa-bolt text-yellow-500';
    if (lower.includes('2day') || lower.includes('2-day')) return 'fa-solid fa-rocket text-blue-500';
    if (lower.includes('ground') || lower.includes('economy') || lower.includes('standard')) return 'fa-solid fa-box text-gray-500';
    return 'fa-solid fa-box text-gray-500';
};

// Track last fetched address to avoid redundant calls
const lastFetchedAddressId = ref(null);

// Watch for address ID changes that should trigger rate refresh
watch(
    () => props.addressId,
    (newId, oldId) => {
        const hasSource = props.shipId || (props.packageIds && props.packageIds.length > 0);
        if (newId && newId !== lastFetchedAddressId.value && hasSource) {
            lastFetchedAddressId.value = newId;
            fetchAllRates();
        }
    }
);

// Watch for packageIds changes (operator flow)
watch(
    () => props.packageIds,
    (newIds, oldIds) => {
        if (newIds && newIds.length > 0 && props.addressId) {
            const newSet = new Set(newIds);
            const oldSet = new Set(oldIds || []);
            const hasChanges = newIds.length !== (oldIds?.length || 0) || 
                               newIds.some(id => !oldSet.has(id));
            if (hasChanges) {
                fetchAllRates();
            }
        }
    },
    { deep: true }
);

// Watch for addressId changes - refetch rates when address changes
watch(
    () => props.addressId,
    (newAddressId, oldAddressId) => {
        if (newAddressId && newAddressId !== oldAddressId) {
            const hasSource = props.shipId || (props.packageIds && props.packageIds.length > 0);
            if (hasSource) {
                lastFetchedAddressId.value = newAddressId;
                fetchAllRates();
            }
        }
    },
    { immediate: false }
);

// Initialize - fetch rates on mount if we have ship_id or package_ids
onMounted(() => {
    const hasSource = props.shipId || (props.packageIds && props.packageIds.length > 0);
    if (hasSource && props.addressId) {
        lastFetchedAddressId.value = props.addressId;
        fetchAllRates();
    } else if (hasSource && !props.addressId) {
        initializeEmptyCarriers('Please select a delivery address to get rates');
    } else {
        initializeEmptyCarriers('Select package and address to get rates');
    }
});
</script>

<template>
    <div class="carrier-selector">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                <i class="fa-solid fa-truck text-primary-500"></i>
                Select Shipping Method
            </h3>
            <span v-if="totalWeight" class="text-xs text-gray-500">
                Package: {{ totalWeight.toFixed(2) }} lbs
            </span>
        </div>

        <!-- Carrier Tabs -->
        <div class="flex gap-2 p-1 mb-4 bg-gray-100 rounded-lg">
            <button
                v-for="carrier in carriers"
                :key="carrier.id"
                @click="selectCarrierTab(carrier.id)"
                class="carrier-tab"
                :class="{
                    'carrier-tab--active': activeCarrier === carrier.id,
                    'carrier-tab--disabled': !isCarrierEnabled(carrier.id) && !carrierHasRates(carrier.id),
                }"
            >
                <img :src="carrier.logo" :alt="carrier.name" class="w-16 h-6 object-contain" />
                <span 
                    v-if="getCarrierMinPrice(carrier.id)"
                    class="text-xs text-gray-500"
                >
                    from {{ formatPrice(getCarrierMinPrice(carrier.id)) }}
                </span>
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="py-8 text-center">
            <div class="inline-flex items-center gap-2">
                <i class="text-xl fa-solid fa-spinner fa-spin text-primary-500"></i>
                <span class="text-sm text-gray-500">Fetching live rates...</span>
            </div>
        </div>

        <!-- Error Message -->
        <div v-else-if="errorMessage" class="p-3 mb-4 text-sm text-amber-700 bg-amber-50 rounded-lg border border-amber-200">
            <i class="mr-2 fa-solid fa-exclamation-triangle"></i>
            {{ errorMessage }}
        </div>

        <!-- Service Options -->
        <div v-else class="space-y-2">
            <label
                v-for="service in activeCarrierRates"
                :key="service.id"
                :for="`service_${service.id}`"
                class="service-card"
                :class="{
                    'service-card--selected': selectedService?.id === service.id,
                }"
                @click="selectService(service)"
            >
                <div class="flex items-center gap-3">
                    <!-- Radio -->
                    <input
                        type="radio"
                        :id="`service_${service.id}`"
                        :value="service.id"
                        :checked="selectedService?.id === service.id"
                        class="w-4 h-4 border-gray-300 text-primary-600 focus:ring-primary-500"
                    />

                    <!-- Service Info -->
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <i :class="getServiceIcon(service.service_name)"></i>
                            <span class="font-medium text-gray-900">{{ service.service_name }}</span>
                            <span 
                                v-if="service.is_live_rate"
                                class="px-1.5 py-0.5 text-[10px] font-semibold text-green-700 bg-green-100 rounded"
                            >
                                ✓ Live
                            </span>
                        </div>
                        <p v-if="service.delivery_date" class="mt-0.5 text-xs text-gray-500">
                            <i class="mr-1 fa-regular fa-calendar"></i>
                            Arrives by {{ service.delivery_date }}
                        </p>
                        <p v-else-if="service.transit_days" class="mt-0.5 text-xs text-gray-500">
                            <i class="mr-1 fa-regular fa-clock"></i>
                            {{ service.transit_days }} business days
                        </p>
                    </div>

                    <!-- Price -->
                    <div class="text-right">
                        <p 
                            v-if="service.price !== null"
                            class="text-lg font-bold"
                            :class="selectedService?.id === service.id ? 'text-primary-600' : 'text-gray-800'"
                        >
                            {{ formatPrice(service.price) }}
                        </p>
                        <p v-else class="text-sm text-gray-400">
                            Contact for quote
                        </p>
                    </div>
                </div>
            </label>

            <!-- No services message / Error state -->
            <div v-if="activeCarrierRates.length === 0" class="py-6 text-center">
                <!-- Per-carrier loading state -->
                <template v-if="carrierLoading[activeCarrier]">
                    <i class="mb-2 text-2xl fa-solid fa-spinner fa-spin text-primary-500"></i>
                    <p class="text-gray-500">Fetching {{ activeCarrier.toUpperCase() }} rates...</p>
                </template>
                <!-- Error state with refresh button -->
                <template v-else-if="getCarrierError(activeCarrier)">
                    <i class="mb-2 text-2xl text-amber-500 fa-solid fa-exclamation-circle"></i>
                    <p class="text-gray-700 font-medium">{{ getCarrierError(activeCarrier) }}</p>
                    <button
                        @click="fetchSingleCarrierRates(activeCarrier)"
                        class="mt-3 inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition-colors"
                    >
                        <i class="fa-solid fa-rotate"></i>
                        Refresh {{ activeCarrier.toUpperCase() }} Rates
                    </button>
                </template>
                <!-- Generic no services message -->
                <template v-else>
                    <i class="mb-2 text-2xl text-gray-400 fa-solid fa-box-open"></i>
                    <p class="text-gray-500">No services available from this carrier</p>
                    <button
                        @click="fetchSingleCarrierRates(activeCarrier)"
                        class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                    >
                        <i class="fa-solid fa-rotate"></i>
                        Try Again
                    </button>
                </template>
            </div>
        </div>

        <!-- Info Note -->
        <p class="mt-4 text-xs text-gray-500">
            <i class="mr-1 fa-solid fa-info-circle"></i>
            Rates are based on package weight and destination. Final cost confirmed at checkout.
        </p>
    </div>
</template>

<style scoped>
.carrier-tab {
    @apply flex flex-col items-center gap-1 flex-1 py-2 px-3 rounded-md transition-all duration-200 cursor-pointer;
    @apply bg-transparent hover:bg-white/50;
}

.carrier-tab--active {
    @apply bg-white shadow-sm ring-1 ring-gray-200;
}

.carrier-tab--disabled {
    @apply opacity-50 cursor-not-allowed;
}

.service-card {
    @apply block p-3 border-2 rounded-lg cursor-pointer transition-all duration-200;
    @apply border-gray-200 bg-white hover:border-gray-300 hover:shadow-sm;
}

.service-card--selected {
    @apply border-primary-500 bg-primary-50 shadow-md;
}
</style>
