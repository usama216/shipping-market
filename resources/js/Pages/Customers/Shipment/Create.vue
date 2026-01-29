<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { onMounted, ref, watch, computed } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import axios from "axios";
import { useFacebookPixel } from "@/Composables/useFacebookPixel";
import { useAdRoll } from "@/Composables/useAdRoll";

// Components
import CreditCard from "./partials/CreditCard.vue";
import ShipAddress from "./partials/ShipAddress.vue";
import NationalId from "./NationalId.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Modal from "@/Components/Modal.vue";
import PayPalButton from "@/Components/Payment/PayPalButton.vue";

// New Shipment Components
import PackageSummaryCard from "@/Components/Shipment/PackageSummaryCard.vue";
import CarrierSelector from "@/Components/Shipment/CarrierSelector.vue";
import AdditionalOptions from "@/Components/Shipment/AdditionalOptions.vue";
import OrderSummary from "@/Components/Shipment/OrderSummary.vue";
import AddonSelector from "@/Components/Shipment/AddonSelector.vue";

const props = defineProps({
    ship: Object,
    cards: Object,
    publishableKey: String,
    customerAddresses: Object,
    internationalShippingMethod: Object,
    packingOptions: Object,
    userPreferences: Object,
    shippingPreferenceOptions: Object,
    // New carrier service consolidation
    carrierServices: {
        type: Array,
        default: () => []
    },
    carrierAddons: {
        type: Array,
        default: () => []
    },
    specialRequests: {
        type: Array,
        default: () => []
    },
    specialRequestCost: {
        type: Number,
        default: 0
    },
});

const toast = useToast();

// Helper function for safe JSON parsing
const safeJsonParse = (json, fallback = []) => {
    if (typeof json !== "string" || json.trim() === "") {
        return fallback;
    }
    try {
        return JSON.parse(json);
    } catch {
        return fallback;
    }
};

// ============================================
// STATE
// ============================================

// Selected options
const selectedShipMethod = ref(
    props?.userPreferences
        ? Number(props?.userPreferences?.international_shipping_option)
        : (props.internationalShippingMethod?.[0]?.id || null)
);

const selectedPackingOptions = ref(
    safeJsonParse(props?.userPreferences?.packing_option, [])
);

const selectedShippingPreferences = ref(
    safeJsonParse(props?.userPreferences?.shipping_preference_option, [])
);

const selectedCardId = ref(null);
// Initialize with user's preferred address to ensure CarrierSelector has destination on first render
// Priority: 1) User's saved preference, 2) Any default address (US or UK), 3) First address
const selectedAddressId = ref(
    props.userPreferences?.user_address_id ||
    props.customerAddresses?.find(a => a.is_default_us || a.is_default_uk)?.id || 
    props.customerAddresses?.[0]?.id || 
    null
);

// Pricing
const baseHandlingFee = 10.00;
const internationalShippingAmount = ref(0);
const packingOptionAmount = ref(0);
const shippingPreferenceAmount = ref(0);
const couponDiscount = ref(0);
const couponCode = ref(null); // string|null
const loyaltyDiscount = ref(0);
const loyaltyPointsUsed = ref(0);

// New addon state
const selectedAddonIds = ref([]);
const addonCharges = ref(0);
const declaredValueOverride = ref(null);

// Enriched addon data from backend (with live pricing, is_available, is_mandatory)
const enrichedAddons = ref([]);
const packageClassifications = ref({});
const checkoutEligible = ref(true);
const checkoutErrors = ref([]);

// Classification charges (dangerous, fragile, oversized)
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

// Carrier service ID for proper persistence
const selectedCarrierServiceId = ref(null);

// UI State
const isSubmitting = ref(false);
const showConfirmModal = ref(false);

// ============================================
// COMPUTED
// ============================================

const totalPackages = computed(() => props.ship?.packages?.length || 0);

// Physical weight from items
const totalWeight = computed(() => {
    return props.ship?.packages?.reduce(
        (sum, pkg) => sum + Number(pkg.total_weight || 0), 
        0
    ) || 0;
});

// Volumetric weight from item dimensions
const totalVolumetricWeight = computed(() => {
    return props.ship?.packages?.reduce(
        (sum, pkg) => sum + Number(pkg.total_volumetric_weight || 0),
        0
    ) || 0;
});

// Billed weight = higher of physical or volumetric (what carriers charge on)
const totalBilledWeight = computed(() => {
    return Math.max(totalWeight.value, totalVolumetricWeight.value);
});

// Determine which weight type is being used for billing
const weightType = computed(() => {
    if (totalVolumetricWeight.value > totalWeight.value) {
        return 'volumetric';
    } else if (totalWeight.value > totalVolumetricWeight.value) {
        return 'physical';
    }
    return 'equal';
});

const totalDeclaredValue = computed(() => {
    return props.ship?.packages?.reduce(
        (sum, pkg) => sum + Number(pkg.total_value || 0), 
        0
    ) || 0;
});

// Get selected address for destination info
const selectedAddress = computed(() => {
    if (!selectedAddressId.value || !props.customerAddresses) return null;
    return props.customerAddresses.find(addr => addr.id === selectedAddressId.value);
});

const subtotal = computed(() => {
    return (
        baseHandlingFee +
        internationalShippingAmount.value +
        packingOptionAmount.value +
        shippingPreferenceAmount.value +
        addonCharges.value +
        classificationCharges.value +
        (props.specialRequestCost || 0)
    );
});

const finalTotal = computed(() => {
    const discounts = couponDiscount.value + loyaltyDiscount.value;
    return Math.max(0, subtotal.value - discounts);
});

const canCheckout = computed(() => {
    // Address is mandatory - must be selected first
    if (!selectedAddressId.value) {
        return false;
    }
    
    // Either selectedShipMethod (old system) OR selectedCarrierServiceId (new system) must be set
    const hasCarrierSelected = selectedShipMethod.value || selectedCarrierServiceId.value;
    
    return (
        selectedCardId.value &&
        hasCarrierSelected &&
        finalTotal.value > 0 &&
        checkoutEligible.value // Must pass checkout eligibility (no blocked mandatory addons)
    );
});

// Validation status for UI feedback
const validationErrors = computed(() => {
    const errors = [];
    // Address is mandatory and must be first
    if (!selectedAddressId.value) {
        errors.push({ field: 'address', message: 'Delivery address is required. Please select a delivery address first.' });
        return errors; // Return early - address is mandatory
    }
    // Check for either old system (selectedShipMethod) or new system (selectedCarrierServiceId)
    if (!selectedShipMethod.value && !selectedCarrierServiceId.value) {
        errors.push({ field: 'carrier', message: 'Select a delivery carrier' });
    }
    if (!selectedCardId.value && paymentMethod.value === 'card') errors.push({ field: 'payment', message: 'Select a payment method' });
    if (finalTotal.value <= 0) errors.push({ field: 'amount', message: 'Order amount must be greater than zero' });
    
    // Add checkout eligibility errors (e.g., mandatory addons unavailable)
    if (!checkoutEligible.value && checkoutErrors.value.length > 0) {
        checkoutErrors.value.forEach(err => {
            errors.push({ field: 'eligibility', message: err });
        });
    }
    return errors;
});

// Payment method selection (card or paypal)
const paymentMethod = ref('card'); // 'card' or 'paypal'
const isPayPalProcessing = ref(false);

const canPayWithPayPal = computed(() => {
    return (
        selectedAddressId.value &&
        selectedShipMethod.value &&
        finalTotal.value > 0
    );
});

// ============================================
// METHODS
// ============================================

// calculateShippingCost removed - CarrierSelector now fetches all data via unified endpoint

const handleCarrierChange = (data) => {
    // Priority: Use carrierServiceId if available (new system with carrier_services table)
    // Otherwise, use methodId if it's numeric (old system with international_shipping_options table)
    if (data.carrierServiceId) {
        // New system: using carrier_services table
        selectedCarrierServiceId.value = Number(data.carrierServiceId);
        // Clear old system value since we're using new system
        selectedShipMethod.value = null;
        console.log('Using new carrier service system:', {
            carrierServiceId: selectedCarrierServiceId.value,
        });
    } else if (data.methodId) {
        // Old system: using international_shipping_options table
        // methodId should be numeric for old system
        const methodIdNum = Number(data.methodId);
        if (!isNaN(methodIdNum) && methodIdNum > 0 && methodIdNum.toString() === String(data.methodId)) {
            // Valid numeric ID for old system
            selectedShipMethod.value = methodIdNum;
            selectedCarrierServiceId.value = null;
            console.log('Using old shipping option system:', {
                internationalShippingOptionId: selectedShipMethod.value,
            });
        } else {
            // methodId is a string (service code like "FEDEX_INTERNATIONAL_PRIORITY")
            // This means we need carrierServiceId but it wasn't provided
            console.warn('CarrierSelector returned service code instead of ID:', {
                methodId: data.methodId,
                carrierServiceId: data.carrierServiceId,
                note: 'Expected carrierServiceId but got service code. Service may not be properly configured.',
            });
            // Don't set either - this will cause validation to fail
            selectedShipMethod.value = null;
            selectedCarrierServiceId.value = null;
        }
    } else {
        // No carrier selected
        selectedShipMethod.value = null;
        selectedCarrierServiceId.value = null;
    }
    
    // Use the live rate price directly from CarrierSelector
    if (data.price !== null && data.price !== undefined) {
        internationalShippingAmount.value = data.price;
    }
    
    // Store enriched addon data from backend (with live pricing)
    if (data.carrierAddons && data.carrierAddons.length > 0) {
        enrichedAddons.value = data.carrierAddons;
        
        // Auto-select mandatory addons
        const mandatoryIds = data.carrierAddons
            .filter(a => a.is_mandatory && a.is_available)
            .map(a => a.id);
        if (mandatoryIds.length > 0) {
            // Merge mandatory addons with current selection
            const currentIds = [...selectedAddonIds.value];
            mandatoryIds.forEach(id => {
                if (!currentIds.includes(id)) currentIds.push(id);
            });
            selectedAddonIds.value = currentIds;
        }
    }
    
    // Store checkout eligibility status
    checkoutEligible.value = data.checkoutEligible ?? true;
    checkoutErrors.value = data.checkoutErrors ?? [];
    
    // Store package classifications for UI messaging
    if (data.packageClassifications) {
        packageClassifications.value = data.packageClassifications;
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
    
    // Debug logging
    console.log('Carrier selection complete:', {
        selectedShipMethod: selectedShipMethod.value,
        selectedCarrierServiceId: selectedCarrierServiceId.value,
        hasCarrier: !!(selectedShipMethod.value || selectedCarrierServiceId.value),
        canCheckout: canCheckout.value,
        validationErrors: validationErrors.value,
    });
};

const handleOptionsChange = (data) => {
    selectedPackingOptions.value = data.packingOptions;
    selectedShippingPreferences.value = data.shippingPreferences;
    packingOptionAmount.value = data.packingTotal;
    shippingPreferenceAmount.value = data.shippingPreferencesTotal;
};

const handleCouponApplied = (data) => {
    couponDiscount.value = data.discount || 0;
    couponCode.value = data.code || data.coupon?.code || null;
};

const handleLoyaltyApplied = (data) => {
    loyaltyDiscount.value = data.discount || 0;
    loyaltyPointsUsed.value = data.points || 0;
};

const handleAddonsChanged = (data) => {
    selectedAddonIds.value = data.selectedAddonIds || [];
    addonCharges.value = data.totalCharges || 0;
    if (data.declaredValue !== undefined) {
        declaredValueOverride.value = data.declaredValue;
    }
};

const openConfirmModal = () => {
    // Mandatory validation: Address must be selected
    if (!selectedAddressId.value) {
        toast.error('Please select a delivery address before proceeding to checkout.');
        return;
    }
    
    if (!canCheckout.value) {
        if (!selectedCardId.value) {
            toast.error("Please select a payment card");
            return;
        }
        if (!selectedAddressId.value) {
            toast.error("Please select a delivery address");
            return;
        }
        // Check for either old system (selectedShipMethod) or new system (selectedCarrierServiceId)
        if (!selectedShipMethod.value && !selectedCarrierServiceId.value) {
            toast.error("Please select a shipping carrier");
            return;
        }
    }
    showConfirmModal.value = true;
};

const processCheckout = async () => {
    // Programmatic validation guard - prevents console bypass
    if (!canCheckout.value) {
        toast.error("Please complete all required fields before checkout.");
        console.warn("Checkout blocked: validation failed", validationErrors.value);
        return;
    }
    
    if (validationErrors.value.length > 0) {
        toast.error(validationErrors.value[0].message);
        return;
    }

    // Additional safeguard: Ensure at least one carrier service is selected
    if (!selectedShipMethod.value && !selectedCarrierServiceId.value) {
        toast.error("Please select a carrier service before checkout.");
        console.error("Checkout blocked: No carrier service selected", {
            selectedShipMethod: selectedShipMethod.value,
            selectedCarrierServiceId: selectedCarrierServiceId.value,
        });
        return;
    }

    // Track Facebook Pixel InitiateCheckout event
    const { trackInitiateCheckout } = useFacebookPixel();
    const { trackBeginCheckout } = useAdRoll();
    const packageIds = props.ship?.packages?.map(pkg => pkg.id.toString()) || [];
    
    // Facebook Pixel InitiateCheckout
    trackInitiateCheckout({
        value: finalTotal.value,
        currency: 'USD',
        content_ids: packageIds.length > 0 ? packageIds : [],
        num_items: props.ship?.packages?.length || 1,
    });

    // AdRoll BeginCheckout
    trackBeginCheckout({
        total: finalTotal.value,
        currency: 'USD',
        product_ids: packageIds.length > 0 ? packageIds : [],
    });

    isSubmitting.value = true;
    
    // Ensure we're using the latest calculated values at the moment of checkout
    const currentSubtotal = subtotal.value;
    const currentFinalTotal = finalTotal.value;
    
    // Convert values to proper types for backend validation
    const internationalShippingOptionId = selectedShipMethod.value 
        ? Number(selectedShipMethod.value) 
        : null;
    const carrierServiceId = selectedCarrierServiceId.value 
        ? Number(selectedCarrierServiceId.value) 
        : null;
    const packingOptionId = Array.isArray(selectedPackingOptions.value) && selectedPackingOptions.value.length > 0
        ? selectedPackingOptions.value.map(id => Number(id))
        : null;
    const shippingPreferenceOptionId = Array.isArray(selectedShippingPreferences.value) && selectedShippingPreferences.value.length > 0
        ? selectedShippingPreferences.value.map(id => Number(id))
        : null;
    const cardId = selectedCardId.value ? Number(selectedCardId.value) : null;
    const customerAddressId = selectedAddressId.value ? Number(selectedAddressId.value) : null;
    const selectedAddonIdsArray = Array.isArray(selectedAddonIds.value) && selectedAddonIds.value.length > 0
        ? selectedAddonIds.value.map(id => Number(id))
        : [];
    
    // Log for debugging
    console.log('Checkout submission - Raw values:', {
        selectedShipMethod: selectedShipMethod.value,
        selectedCarrierServiceId: selectedCarrierServiceId.value,
        selectedPackingOptions: selectedPackingOptions.value,
        selectedShippingPreferences: selectedShippingPreferences.value,
        selectedCardId: selectedCardId.value,
        selectedAddressId: selectedAddressId.value,
        selectedAddonIds: selectedAddonIds.value,
    });
    
    console.log('Checkout submission - Converted values:', {
        internationalShippingOptionId,
        carrierServiceId,
        packingOptionId,
        shippingPreferenceOptionId,
        cardId,
        customerAddressId,
        selectedAddonIdsArray,
        subtotal: currentSubtotal,
        finalTotal: currentFinalTotal,
        couponDiscount: couponDiscount.value,
        loyaltyDiscount: loyaltyDiscount.value,
        addonCharges: addonCharges.value,
    });
    
    const form = useForm({
        id: props.ship?.id,
        international_shipping_option_id: internationalShippingOptionId,
        carrier_service_id: carrierServiceId,
        packing_option_id: packingOptionId,
        shipping_preference_option_id: shippingPreferenceOptionId,
        estimated_shipping_charges: Number(currentFinalTotal.toFixed(2)), // Ensure 2 decimal places
        subtotal: Number(currentSubtotal.toFixed(2)), // Subtotal before discounts
        card_id: cardId,
        customer_address_id: customerAddressId,
        loyalty_points_used: Number(loyaltyPointsUsed.value) || 0,
        loyalty_discount: Number(loyaltyDiscount.value.toFixed(2)) || 0,
        coupon_code: couponCode.value || null,
        coupon_order_amount: Number(currentSubtotal.toFixed(2)), // Use current subtotal for coupon validation
        coupon_discount: Number(couponDiscount.value.toFixed(2)) || 0,
        // New addon fields
        selected_addon_ids: selectedAddonIdsArray,
        addon_charges: Number(addonCharges.value.toFixed(2)) || 0,
        declared_value: Number((declaredValueOverride.value || totalDeclaredValue.value).toFixed(2)) || 0,
    });

    form.post(route("customer.ship.checkout"), {
        onSuccess: () => {
            showConfirmModal.value = false;
            toast.success("Checkout completed successfully!");
        },
        onError: (errors) => {
            // Log full error details for debugging
            console.error("Checkout error - Full details:", {
                errors,
                formData: form.data(),
                validationErrors: form.errors,
            });
            
            // Extract error messages
            let errorMessages = [];
            
            if (typeof errors === 'object' && errors !== null) {
                // Laravel validation errors format
                if (errors.message) {
                    errorMessages.push(errors.message);
                }
                
                // Check for validation errors object
                Object.keys(errors).forEach(key => {
                    if (Array.isArray(errors[key])) {
                        errors[key].forEach(msg => errorMessages.push(`${key}: ${msg}`));
                    } else if (typeof errors[key] === 'string') {
                        errorMessages.push(`${key}: ${errors[key]}`);
                    }
                });
            } else if (typeof errors === 'string') {
                errorMessages.push(errors);
            }
            
            // Show first error message or default
            const errorMessage = errorMessages.length > 0 
                ? errorMessages[0] 
                : "Checkout failed. Please try again.";
            
            console.error("Checkout error - User message:", errorMessage);
            toast.error(errorMessage);
            
            // Show additional errors if multiple
            if (errorMessages.length > 1) {
                console.warn("Additional checkout errors:", errorMessages.slice(1));
            }
            
            // If it's a price mismatch error, suggest refreshing
            if (errorMessage.includes('Price has changed') || errorMessage.includes('price')) {
                toast.info("Please refresh the page and try again. The order total may have changed.");
            }
            
            // If it's a validation error, show which field
            if (errorMessage.includes('must be') || errorMessage.includes('required')) {
                console.error("Validation error detected. Check form data types and required fields.");
            }
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

// ============================================
// WATCHERS & LIFECYCLE
// ============================================

// Watcher to debug carrier selection
watch(
    [selectedShipMethod, selectedCarrierServiceId],
    ([newShipMethod, newCarrierServiceId], [oldShipMethod, oldCarrierServiceId]) => {
        if (newShipMethod !== oldShipMethod || newCarrierServiceId !== oldCarrierServiceId) {
            console.log('Carrier selection changed:', {
                selectedShipMethod: newShipMethod,
                selectedCarrierServiceId: newCarrierServiceId,
                hasCarrier: !!(newShipMethod || newCarrierServiceId),
                canCheckout: canCheckout.value,
            });
        }
    },
    { immediate: true }
);

// Watchers for packing/preference options (addon costs handled by CarrierSelector unified endpoint)
watch(
    [selectedPackingOptions, selectedShippingPreferences],
    () => {
        // These amounts are now set by handleOptionsChange from AdditionalOptions component
        // No separate API call needed
    },
    { deep: true }
);

</script>

<template>
    <AuthenticatedLayout>
        <Head title="Shipment Checkout" />

        <div class="min-h-screen py-6 bg-gray-50">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="flex items-center gap-3 text-2xl font-bold text-gray-900">
                        <i class="fa-solid fa-truck-fast text-primary-500"></i>
                        Shipment Checkout
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Review your packages and complete your shipment
                    </p>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <!-- Main Content (Left 2 columns) -->
                    <div class="space-y-6 lg:col-span-2">
                        
                        <!-- Section 1: Packages -->
                        <section class="p-6 bg-white border rounded-lg shadow-sm">
                            <div class="flex items-center justify-between mb-4">
                                <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-800">
                                    <span class="flex items-center justify-center w-6 h-6 text-sm font-bold text-white rounded-full bg-primary-500">1</span>
                                    Packages in this Shipment
                                </h2>
                                <span class="px-3 py-1 text-sm font-medium rounded-full text-primary-700 bg-primary-100">
                                    {{ totalPackages }} package{{ totalPackages !== 1 ? 's' : '' }}
                                </span>
                            </div>

                            <div class="space-y-3">
                                <PackageSummaryCard
                                    v-for="pkg in ship?.packages"
                                    :key="pkg.id"
                                    :package="pkg"
                                    :show-change-request="false"
                                />
                            </div>

                            <!-- Packages Summary -->
                            <div class="grid grid-cols-3 gap-4 p-4 mt-4 rounded-lg bg-gray-50">
                                <div class="text-center">
                                    <p class="text-sm text-gray-500">Total Packages</p>
                                    <p class="text-xl font-bold text-gray-900">{{ totalPackages }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-500">Billable Weight</p>
                                    <p class="text-xl font-bold text-gray-900">{{ totalBilledWeight.toFixed(2) }} lbs</p>
                                    <p v-if="totalVolumetricWeight > totalWeight" class="text-xs text-amber-600 mt-1">
                                        <i class="fa-solid fa-cube mr-1"></i>Volumetric
                                    </p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-gray-500">Declared Value</p>
                                    <p class="text-xl font-bold text-green-600">${{ totalDeclaredValue.toFixed(2) }}</p>
                                </div>
                            </div>
                        </section>

                        <!-- Section 2: Delivery Address (MANDATORY) -->
                        <section class="p-6 bg-white border rounded-lg shadow-sm" :class="{ 'border-red-300 bg-red-50': !selectedAddressId }">
                            <h2 class="flex items-center gap-2 mb-4 text-lg font-semibold text-gray-800">
                                <span class="flex items-center justify-center w-6 h-6 text-sm font-bold text-white rounded-full bg-primary-500">2</span>
                                Delivery Address
                                <span class="px-2 py-0.5 text-xs font-semibold text-red-600 bg-red-100 rounded">Required</span>
                            </h2>

                            <div v-if="!selectedAddressId" class="p-4 mb-4 text-sm border border-amber-300 rounded-lg bg-amber-50">
                                <div class="flex items-start gap-2">
                                    <i class="mt-0.5 fa-solid fa-exclamation-triangle text-amber-600"></i>
                                    <div>
                                        <p class="font-medium text-amber-800">Delivery address is required</p>
                                        <p class="mt-1 text-amber-700">Please select a delivery address to continue with carrier selection.</p>
                                    </div>
                                </div>
                            </div>

                            <ShipAddress
                                :customer-addresses="customerAddresses"
                                @set-selected-address="(addr) => selectedAddressId = addr"
                            />
                        </section>

                        <!-- Section 3: Carrier Selection (Disabled until address is selected) -->
                        <section 
                            class="p-6 border rounded-lg shadow-sm transition-all"
                            :class="selectedAddressId ? 'bg-white' : 'bg-gray-100 border-gray-300 opacity-60'"
                        >
                            <h2 class="flex items-center gap-2 mb-4 text-lg font-semibold text-gray-800">
                                <span class="flex items-center justify-center w-6 h-6 text-sm font-bold text-white rounded-full" :class="selectedAddressId ? 'bg-primary-500' : 'bg-gray-400'">3</span>
                                Select Delivery Carrier
                            </h2>

                            <div v-if="!selectedAddressId" class="p-4 mb-4 text-sm border border-gray-300 rounded-lg bg-gray-50">
                                <div class="flex items-start gap-2">
                                    <i class="mt-0.5 fa-solid fa-lock text-gray-500"></i>
                                    <div>
                                        <p class="font-medium text-gray-700">Please select a delivery address first</p>
                                        <p class="mt-1 text-gray-600">You must select a delivery address in Section 2 before you can view carrier options and rates.</p>
                                    </div>
                                </div>
                            </div>

                            <div v-else>
                                <CarrierSelector
                                    :ship-id="ship?.id"
                                    :address-id="selectedAddressId"
                                    :international-shipping-options="internationalShippingMethod"
                                    :selected-method-id="selectedShipMethod"
                                    :user-preferences="userPreferences"
                                    @update:selected-method-id="(val) => selectedShipMethod = val ? Number(val) : null"
                                    @rate-changed="handleCarrierChange"
                                />
                            </div>
                        </section>

                        <!-- Section 4: Additional Services (Carrier Add-ons) -->
                        <section v-if="enrichedAddons?.length > 0" class="p-6 bg-white border rounded-lg shadow-sm">
                            <h2 class="flex items-center gap-2 mb-4 text-lg font-semibold text-gray-800">
                                <span class="flex items-center justify-center w-6 h-6 text-sm font-bold text-white rounded-full bg-primary-500">4</span>
                                Additional Services
                            </h2>

                            <!-- Checkout Eligibility Warning -->
                            <div v-if="!checkoutEligible && checkoutErrors.length > 0" 
                                 class="p-4 mb-4 text-sm border rounded-lg bg-red-50 border-red-200">
                                <div class="flex items-start gap-2">
                                    <i class="mt-0.5 fa-solid fa-circle-exclamation text-red-500"></i>
                                    <div>
                                        <p class="font-medium text-red-800">Unable to ship with this carrier</p>
                                        <ul class="mt-1 text-red-700 space-y-1">
                                            <li v-for="(err, idx) in checkoutErrors" :key="idx">{{ err }}</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <AddonSelector
                                :carrier-addons="enrichedAddons"
                                :selected-addons="selectedAddonIds"
                                :declared-value="totalDeclaredValue"
                                :package-classifications="packageClassifications"
                                @addons-changed="handleAddonsChanged"
                            />
                        </section>

                        <!-- Section 5: Payment Method -->
                        <section class="p-6 bg-white border rounded-lg shadow-sm">
                            <h2 class="flex items-center gap-2 mb-4 text-lg font-semibold text-gray-800">
                                <span class="flex items-center justify-center w-6 h-6 text-sm font-bold text-white rounded-full bg-primary-500">5</span>
                                Payment Method
                                <span v-if="!selectedAddressId" class="px-2 py-0.5 text-xs font-semibold text-red-600 bg-red-100 rounded">Address Required</span>
                            </h2>

                            <!-- Payment Method Tabs -->
                            <div class="flex gap-2 p-1 mb-4 rounded-lg bg-gray-100">
                                <button
                                    @click="paymentMethod = 'card'"
                                    :class="[
                                        'flex-1 py-2 px-4 rounded-md text-sm font-medium transition-all',
                                        paymentMethod === 'card' 
                                            ? 'bg-white shadow text-primary-600' 
                                            : 'text-gray-600 hover:text-gray-900'
                                    ]"
                                >
                                    <i class="fa-solid fa-credit-card mr-2"></i>
                                    Credit/Debit Card
                                </button>
                                <button
                                    @click="paymentMethod = 'paypal'"
                                    :class="[
                                        'flex-1 py-2 px-4 rounded-md text-sm font-medium transition-all',
                                        paymentMethod === 'paypal' 
                                            ? 'bg-white shadow text-blue-600' 
                                            : 'text-gray-600 hover:text-gray-900'
                                    ]"
                                >
                                    <i class="fa-brands fa-paypal mr-2"></i>
                                    PayPal
                                </button>
                            </div>

                            <!-- Card Payment -->
                            <div v-if="paymentMethod === 'card'">
                                <CreditCard
                                    :cards="cards"
                                    :publishable-key="publishableKey"
                                    @selected-card="(card) => selectedCardId = card"
                                />
                            </div>

                            <!-- PayPal Payment -->
                            <div v-else class="py-4">
                                <div class="p-4 mb-4 text-sm text-blue-800 border border-blue-200 rounded-lg bg-blue-50">
                                    <i class="fa-solid fa-info-circle mr-2"></i>
                                    You'll be redirected to PayPal to complete your payment securely.
                                </div>
                                <PayPalButton
                                    :ship-id="ship?.id"
                                    :amount="finalTotal"
                                    :customer-address-id="selectedAddressId"
                                    :disabled="!canPayWithPayPal"
                                    @processing="(val) => isPayPalProcessing = val"
                                    @error="(msg) => toast.error(msg)"
                                />
                            </div>
                        </section>

                        <!-- Export Documentation -->
                        <section class="p-6 bg-white border rounded-lg shadow-sm">
                            <h2 class="flex items-center gap-2 mb-4 text-lg font-semibold text-gray-800">
                                <i class="fa-solid fa-file-lines text-primary-500"></i>
                                Export Documentation
                            </h2>
                            <NationalId :ship="ship" :user-preferences="userPreferences" />
                        </section>
                    </div>

                    <!-- Sidebar (Right column) -->
                    <div class="lg:col-span-1">
                        <div class="sticky space-y-4 top-6">
                            <!-- Order Summary -->
                            <OrderSummary
                                :shipping-amount="internationalShippingAmount"
                                :handling-fee="baseHandlingFee"
                                :packing-options-amount="packingOptionAmount"
                                :shipping-preferences-amount="shippingPreferenceAmount"
                                :addon-charges-amount="addonCharges"
                                :special-request-cost="props.specialRequestCost || 0"
                                :special-requests="props.specialRequests || []"
                                :classification-charges="classificationCharges"
                                :classification-breakdown="classificationBreakdown"
                                :classification-item-counts="classificationItemCounts"
                                :total-packages="totalPackages"
                                :total-weight="totalBilledWeight"
                                :physical-weight="totalWeight"
                                :volumetric-weight="totalVolumetricWeight"
                                :weight-type="weightType"
                                :declared-value="totalDeclaredValue"
                                :order-amount="subtotal"
                                @coupon-applied="handleCouponApplied"
                                @loyalty-applied="handleLoyaltyApplied"
                            />

                            <!-- Checkout Button (for card payments) -->
                            <div v-if="paymentMethod === 'card'">
                                <PrimaryButton
                                    @click="openConfirmModal"
                                    :disabled="!canCheckout"
                                    class="justify-center w-full py-4 text-lg"
                                >
                                    <i class="mr-2 fa-solid fa-lock"></i>
                                    Checkout
                                </PrimaryButton>

                                <!-- Validation Feedback -->
                                <div v-if="validationErrors.length > 0 && !canCheckout" class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg">
                                    <p class="text-xs font-medium text-amber-800 mb-2">
                                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                                        Please complete the following:
                                    </p>
                                    <ul class="text-xs text-amber-700 space-y-1">
                                        <li v-for="error in validationErrors" :key="error.field" class="flex items-center gap-2">
                                            <i class="fa-solid fa-circle text-[4px]"></i>
                                            {{ error.message }}
                                        </li>
                                    </ul>
                                </div>

                                <p class="mt-2 text-xs text-center text-gray-500">
                                    <i class="mr-1 fa-solid fa-shield-halved"></i>
                                    Secure payment processing via Stripe
                                </p>
                            </div>

                            <!-- PayPal checkout info (when PayPal is selected) -->
                            <div v-else class="text-center">
                                <p class="text-sm text-gray-600">
                                    <i class="fa-brands fa-paypal text-blue-600 mr-1"></i>
                                    Use the PayPal button above to complete your order
                                </p>
                                
                                <!-- Validation Feedback for PayPal -->
                                <div v-if="!canPayWithPayPal" class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-lg text-left">
                                    <p class="text-xs font-medium text-amber-800 mb-2">
                                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                                        Before paying with PayPal:
                                    </p>
                                    <ul class="text-xs text-amber-700 space-y-1">
                                        <li v-if="!selectedShipMethod && !selectedCarrierServiceId" class="flex items-center gap-2">
                                            <i class="fa-solid fa-circle text-[4px]"></i>
                                            Select a delivery carrier
                                        </li>
                                        <li v-if="!selectedAddressId" class="flex items-center gap-2">
                                            <i class="fa-solid fa-circle text-[4px]"></i>
                                            Select a delivery address
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <Modal :show="showConfirmModal" @close="showConfirmModal = false">
            <div class="p-6">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">
                    <i class="mr-2 fa-solid fa-credit-card text-primary-500"></i>
                    Confirm Payment
                </h2>

                <div class="p-4 mb-4 rounded-lg bg-gray-50">
                    <p class="text-sm text-gray-600">
                        Your card will be charged:
                    </p>
                    <p class="text-3xl font-bold text-primary-600">
                        ${{ finalTotal.toFixed(2) }}
                    </p>
                </div>

                <div class="p-3 mb-4 text-sm text-blue-800 border rounded-lg bg-blue-50 border-blue-200">
                    <i class="mr-2 fa-solid fa-info-circle"></i>
                    Your shipment will be submitted to the carrier after payment.
                    You will receive tracking information via email.
                </div>

                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="showConfirmModal = false">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton
                        @click="processCheckout"
                        :disabled="isSubmitting"
                    >
                        <span v-if="isSubmitting">
                            <i class="mr-2 fa-solid fa-spinner fa-spin"></i>
                            Processing...
                        </span>
                        <span v-else>
                            <i class="mr-2 fa-solid fa-check"></i>
                            Confirm & Pay
                        </span>
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
