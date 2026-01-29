<script setup>
import { ref, computed, watch, onMounted } from "vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import axios from "axios";
import { useToast } from "vue-toastification";

const toast = useToast();

const props = defineProps({
    // Base amounts
    shippingAmount: {
        type: Number,
        default: 0,
    },
    handlingFee: {
        type: Number,
        default: 10.00,
    },
    packingOptionsAmount: {
        type: Number,
        default: 0,
    },
    shippingPreferencesAmount: {
        type: Number,
        default: 0,
    },
    addonChargesAmount: {
        type: Number,
        default: 0,
    },
    specialRequestCost: {
        type: Number,
        default: 0,
    },
    specialRequests: {
        type: Array,
        default: () => []
    },
    classificationCharges: {
        type: Number,
        default: 0,
    },
    classificationBreakdown: {
        type: Object,
        default: () => ({
            dangerous: 0,
            fragile: 0,
            oversized: 0,
        }),
    },
    classificationItemCounts: {
        type: Object,
        default: () => ({
            dangerous: 0,
            fragile: 0,
            oversized: 0,
        }),
    },
    // Package info
    totalPackages: {
        type: Number,
        default: 0,
    },
    totalWeight: {
        type: Number,
        default: 0,
    },
    physicalWeight: {
        type: Number,
        default: 0,
    },
    volumetricWeight: {
        type: Number,
        default: 0,
    },
    weightType: {
        type: String,
        default: 'physical' // 'physical', 'volumetric', or 'equal'
    },
    declaredValue: {
        type: Number,
        default: 0,
    },
    // Order amount for coupon validation
    orderAmount: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(["totalChanged", "couponApplied", "loyaltyApplied"]);

// State
const couponCode = ref("");
const isApplyingCoupon = ref(false);
const appliedCoupon = ref(null);
const couponDiscount = ref(0);

const loyaltyPointsToUse = ref(0);
const isApplyingLoyalty = ref(false);
const loyaltyDiscount = ref(0);
const availableLoyaltyPoints = ref(0);
const loyaltyRule = ref(null);
const maxRedeemablePoints = ref(0);

// Computed
const subtotal = computed(() => {
    return (
        props.shippingAmount +
        props.handlingFee +
        props.packingOptionsAmount +
        props.shippingPreferencesAmount +
        props.addonChargesAmount +
        props.specialRequestCost +
        props.classificationCharges
    );
});

const totalDiscount = computed(() => {
    return couponDiscount.value + loyaltyDiscount.value;
});

const finalTotal = computed(() => {
    const total = subtotal.value - totalDiscount.value;
    return Math.max(0, total);
});

// Watch for changes and emit
watch(finalTotal, (newTotal) => {
    emit("totalChanged", newTotal);
});

// Methods
const applyCoupon = async () => {
    if (!couponCode.value.trim()) {
        toast.error("Please enter a coupon code");
        return;
    }

    isApplyingCoupon.value = true;
    try {
        const response = await axios.post(route("customer.coupons.validate"), {
            code: couponCode.value.toUpperCase(),
            order_amount: props.orderAmount || subtotal.value,
        });

        // Backend returns 'success' not 'valid'
        if (response.data.success) {
            appliedCoupon.value = response.data.coupon;
            couponDiscount.value = Number(response.data.discount) || 0;
            toast.success(`Coupon applied! You saved $${couponDiscount.value.toFixed(2)}`);
            emit("couponApplied", {
                code: response.data.coupon?.code || couponCode.value,
                discount: couponDiscount.value,
                coupon: response.data.coupon,
            });
        } else {
            toast.error(response.data.message || "Invalid coupon code");
        }
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to apply coupon");
    } finally {
        isApplyingCoupon.value = false;
    }
};

const removeCoupon = () => {
    couponCode.value = "";
    appliedCoupon.value = null;
    couponDiscount.value = 0;
    toast.info("Coupon removed");
    emit("couponApplied", { code: null, discount: 0 });
};

const applyLoyaltyPoints = async () => {
    if (loyaltyPointsToUse.value <= 0) {
        toast.error("Please enter points to use");
        return;
    }

    if (loyaltyPointsToUse.value > availableLoyaltyPoints.value) {
        toast.error(`You only have ${availableLoyaltyPoints.value} points available`);
        return;
    }

    if (loyaltyPointsToUse.value > maxRedeemablePoints.value) {
        toast.error(`Maximum redeemable points for this order: ${maxRedeemablePoints.value}`);
        return;
    }

    isApplyingLoyalty.value = true;
    try {
        // Use the API to calculate discount based on LoyaltyRule
        const response = await axios.post(route("customer.loyalty.calculate-discount"), {
            points: loyaltyPointsToUse.value,
            order_amount: subtotal.value - couponDiscount.value,
        });

        if (response.data.success) {
            loyaltyDiscount.value = response.data.discount;
            toast.success(`Loyalty points applied! You saved $${loyaltyDiscount.value.toFixed(2)}`);
            emit("loyaltyApplied", {
                points: loyaltyPointsToUse.value,
                discount: loyaltyDiscount.value,
            });
        } else {
            toast.error(response.data.message || "Failed to apply loyalty points");
        }
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to apply loyalty points");
    } finally {
        isApplyingLoyalty.value = false;
    }
};

const removeLoyaltyPoints = () => {
    loyaltyPointsToUse.value = 0;
    loyaltyDiscount.value = 0;
    toast.info("Loyalty points removed");
    emit("loyaltyApplied", { points: 0, discount: 0 });
};

const fetchLoyaltySummary = async () => {
    try {
        const response = await axios.get(route("customer.loyalty.summary"));
        if (response.data.success) {
            availableLoyaltyPoints.value = response.data.summary.current_points || 0;
            loyaltyRule.value = response.data.summary.loyalty_rule || null;
        }
    } catch (error) {
        console.error("Failed to fetch loyalty summary:", error);
    }
};

const isFetchingMaxRedeemable = ref(false);
const fetchMaxRedeemable = async () => {
    // Prevent duplicate requests
    if (isFetchingMaxRedeemable.value) {
        return;
    }
    
    // Don't fetch if subtotal is 0 or invalid
    if (!subtotal.value || subtotal.value <= 0) {
        return;
    }
    
    isFetchingMaxRedeemable.value = true;
    try {
        const response = await axios.post(route("customer.loyalty.max-redeemable"), {
            order_amount: subtotal.value,
        });
        if (response.data.success) {
            maxRedeemablePoints.value = response.data.max_points || 0;
        }
    } catch (error) {
        // Ignore aborted requests (happens during page navigation/refresh)
        if (error.code !== 'ECONNABORTED' && error.name !== 'CanceledError') {
            console.error("Failed to fetch max redeemable:", error);
        }
    } finally {
        isFetchingMaxRedeemable.value = false;
    }
};

const formatCurrency = (amount) => `$${Number(amount || 0).toFixed(2)}`;

// Auto-apply coupon on mount
const checkAutoApplyCoupons = async () => {
    if (appliedCoupon.value) return; // Already has a coupon

    try {
        const response = await axios.post(route("customer.coupons.auto-apply"), {
            order_amount: props.orderAmount || subtotal.value,
        });

        if (response.data.success && response.data.coupon) {
            appliedCoupon.value = response.data.coupon;
            couponDiscount.value = Number(response.data.discount) || 0;
            couponCode.value = response.data.coupon.code;
            toast.success(`Coupon "${response.data.coupon.code}" automatically applied! You saved $${couponDiscount.value.toFixed(2)}`);
            emit("couponApplied", {
                code: response.data.coupon.code,
                discount: couponDiscount.value,
                coupon: response.data.coupon,
            });
        }
    } catch (error) {
        // Silent failure for auto-apply
        console.log("Auto-apply check failed", error);
    }
};

// Track if auto-apply has been attempted
const autoApplyAttempted = ref(false);

// Watch for subtotal changes and recalculate discounts
watch(subtotal, (newSubtotal, oldSubtotal) => {
    // Auto-apply coupon on first load
    if (newSubtotal > 0 && !autoApplyAttempted.value && !appliedCoupon.value) {
        autoApplyAttempted.value = true;
        checkAutoApplyCoupons();
    }
    
    // Recalculate existing coupon discount when subtotal changes
    if (appliedCoupon.value && newSubtotal > 0 && newSubtotal !== oldSubtotal) {
        recalculateCouponDiscount();
    }
    
    // Recalculate loyalty discount when subtotal changes
    if (loyaltyPointsToUse.value > 0 && newSubtotal > 0 && newSubtotal !== oldSubtotal) {
        recalculateLoyaltyDiscount();
    }
}, { immediate: true });

// Recalculate coupon discount with new subtotal
const recalculateCouponDiscount = async () => {
    if (!appliedCoupon.value || !couponCode.value) return;
    
    try {
        const response = await axios.post(route("customer.coupons.validate"), {
            code: couponCode.value.toUpperCase(),
            order_amount: subtotal.value,
        });
        
        if (response.data.success) {
            const newDiscount = Number(response.data.discount) || 0;
            if (newDiscount !== couponDiscount.value) {
                couponDiscount.value = newDiscount;
                emit("couponApplied", {
                    code: couponCode.value,
                    discount: couponDiscount.value,
                    coupon: appliedCoupon.value,
                });
            }
        } else {
            // Coupon no longer valid, remove it
            removeCoupon();
        }
    } catch (error) {
        console.error("Failed to recalculate coupon discount:", error);
        // Don't remove coupon on error, just log it
    }
};

// Recalculate loyalty discount with new subtotal
const recalculateLoyaltyDiscount = async () => {
    if (loyaltyPointsToUse.value <= 0) return;
    
    try {
        const response = await axios.post(route("customer.loyalty.calculate-discount"), {
            points: loyaltyPointsToUse.value,
            order_amount: subtotal.value - couponDiscount.value,
        });
        
        if (response.data.success) {
            const newDiscount = response.data.discount;
            if (newDiscount !== loyaltyDiscount.value) {
                loyaltyDiscount.value = newDiscount;
                emit("loyaltyApplied", {
                    points: loyaltyPointsToUse.value,
                    discount: loyaltyDiscount.value,
                });
            }
        }
    } catch (error) {
        console.error("Failed to recalculate loyalty discount:", error);
    }
};

onMounted(() => {
    // Fetch loyalty data
    fetchLoyaltySummary();
    
    // Fallback: if subtotal is already > 0 on mount, trigger auto-apply
    setTimeout(() => {
        if (!autoApplyAttempted.value && !appliedCoupon.value && (subtotal.value > 0 || props.orderAmount > 0)) {
            autoApplyAttempted.value = true;
            checkAutoApplyCoupons();
        }
        // Fetch max redeemable after subtotal is ready
        if (subtotal.value > 0) {
            fetchMaxRedeemable();
        }
    }, 500); // Small delay to ensure props are ready
});

// Re-fetch max redeemable when subtotal changes (separate watcher to avoid conflicts)
watch(
    () => subtotal.value,
    (newSubtotal) => {
        if (newSubtotal > 0) {
            fetchMaxRedeemable();
        }
    }
);
</script>

<template>
    <div class="p-4 bg-white border rounded-lg shadow-sm">
        <h3 class="flex items-center gap-2 mb-4 text-lg font-semibold text-gray-800">
            <i class="fa-solid fa-receipt text-primary-500"></i>
            Order Summary
        </h3>

        <!-- Package Overview -->
        <div class="p-3 mb-4 rounded-lg bg-gray-50">
            <div class="grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-xs text-gray-500">Packages</p>
                    <p class="font-semibold text-gray-900">{{ totalPackages }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">
                        Billed Weight
                        <span v-if="weightType === 'volumetric'" class="text-xs text-amber-600" title="Volumetric weight is higher">
                            (Vol)
                        </span>
                        <span v-else-if="weightType === 'physical'" class="text-xs text-blue-600" title="Physical weight is higher">
                            (Phys)
                        </span>
                    </p>
                    <p class="font-semibold text-gray-900">{{ totalWeight.toFixed(2) }} lbs</p>
                    <p v-if="physicalWeight > 0 || volumetricWeight > 0" class="text-xs text-gray-400 mt-1">
                        Phys: {{ physicalWeight.toFixed(2) }} | Vol: {{ volumetricWeight.toFixed(2) }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Declared Value</p>
                    <p class="font-semibold text-green-600">{{ formatCurrency(declaredValue) }}</p>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Shipping</span>
                <span class="font-medium">{{ formatCurrency(shippingAmount) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Handling Fee</span>
                <span class="font-medium">{{ formatCurrency(handlingFee) }}</span>
            </div>
            <div v-if="packingOptionsAmount > 0" class="flex justify-between">
                <span class="text-gray-600">Packing Options</span>
                <span class="font-medium">{{ formatCurrency(packingOptionsAmount) }}</span>
            </div>
            <div v-if="shippingPreferencesAmount > 0" class="flex justify-between">
                <span class="text-gray-600">Shipping Preferences</span>
                <span class="font-medium">{{ formatCurrency(shippingPreferencesAmount) }}</span>
            </div>
            <div v-if="addonChargesAmount > 0" class="flex justify-between">
                <span class="text-gray-600">Additional Services</span>
                <span class="font-medium">{{ formatCurrency(addonChargesAmount) }}</span>
            </div>
            <div v-if="specialRequestCost > 0" class="flex justify-between">
                <span class="text-gray-600">Optional Services</span>
                <span class="font-medium">{{ formatCurrency(specialRequestCost) }}</span>
            </div>
            <!-- Show selected special requests -->
            <div v-if="specialRequests && specialRequests.length > 0" class="mt-2 text-xs text-gray-500">
                <div v-for="sr in specialRequests" :key="sr.id" class="flex justify-between">
                    <span class="italic">{{ sr.title }}</span>
                    <span>{{ formatCurrency(sr.price) }}</span>
                </div>
            </div>
            
            <!-- Classification Charges -->
            <div v-if="classificationCharges > 0" class="pt-2 mt-2 border-t border-gray-200">
                <div class="flex justify-between mb-1">
                    <span class="text-gray-600 font-medium">Item Classification Charges</span>
                    <span class="font-medium">{{ formatCurrency(classificationCharges) }}</span>
                </div>
                <div class="text-xs text-gray-500 space-y-1">
                    <div v-if="classificationBreakdown.dangerous > 0" class="flex justify-between">
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 rounded-full bg-red-100 flex items-center justify-center" title="Dangerous Goods">
                                <i class="fa-solid fa-triangle-exclamation text-red-600 text-[8px]"></i>
                            </span>
                            Dangerous ({{ classificationItemCounts.dangerous }} item{{ classificationItemCounts.dangerous !== 1 ? 's' : '' }})
                        </span>
                        <span>{{ formatCurrency(classificationBreakdown.dangerous) }}</span>
                    </div>
                    <div v-if="classificationBreakdown.fragile > 0" class="flex justify-between">
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 rounded-full bg-amber-100 flex items-center justify-center" title="Fragile">
                                <i class="fa-solid fa-wine-glass text-amber-600 text-[8px]"></i>
                            </span>
                            Fragile ({{ classificationItemCounts.fragile }} item{{ classificationItemCounts.fragile !== 1 ? 's' : '' }})
                        </span>
                        <span>{{ formatCurrency(classificationBreakdown.fragile) }}</span>
                    </div>
                    <div v-if="classificationBreakdown.oversized > 0" class="flex justify-between">
                        <span class="flex items-center gap-1">
                            <span class="w-3 h-3 rounded-full bg-blue-100 flex items-center justify-center" title="Oversized">
                                <i class="fa-solid fa-box text-blue-600 text-[8px]"></i>
                            </span>
                            Oversized ({{ classificationItemCounts.oversized }} item{{ classificationItemCounts.oversized !== 1 ? 's' : '' }})
                        </span>
                        <span>{{ formatCurrency(classificationBreakdown.oversized) }}</span>
                    </div>
                </div>
            </div>

            <div class="pt-2 border-t border-gray-200">
                <div class="flex justify-between font-medium">
                    <span class="text-gray-700">Subtotal</span>
                    <span>{{ formatCurrency(subtotal) }}</span>
                </div>
            </div>
        </div>

        <!-- Coupon Section -->
        <div class="pt-4 mt-4 border-t border-gray-200">
            <label class="block mb-2 text-sm font-medium text-gray-700">
                <i class="mr-1 fa-solid fa-tag text-primary-500"></i>
                Coupon Code
            </label>
            
            <div v-if="!appliedCoupon" class="flex gap-2">
                <TextInput
                    v-model="couponCode"
                    placeholder="Enter code"
                    class="flex-1"
                />
                <PrimaryButton 
                    @click="applyCoupon"
                    :disabled="isApplyingCoupon"
                    class="text-sm whitespace-nowrap"
                >
                    <span v-if="isApplyingCoupon">...</span>
                    <span v-else>Apply</span>
                </PrimaryButton>
            </div>
            
            <div v-else class="flex items-center justify-between p-2 rounded bg-green-50">
                <div class="flex items-center gap-2">
                    <i class="text-green-600 fa-solid fa-check-circle"></i>
                    <span class="text-sm font-medium text-green-700">
                        {{ appliedCoupon.code }} applied
                    </span>
                </div>
                <button 
                    @click="removeCoupon"
                    class="text-sm text-red-600 hover:text-red-700"
                >
                    Remove
                </button>
            </div>
        </div>

        <!-- Loyalty Points Section -->
        <div class="pt-4 mt-4 border-t border-gray-200">
            <label class="block mb-2 text-sm font-medium text-gray-700">
                <i class="mr-1 fa-solid fa-coins text-primary-500"></i>
                Loyalty Points
            </label>
            
            <!-- Points Info Banner -->
            <div v-if="availableLoyaltyPoints > 0" class="p-2 mb-3 text-xs rounded bg-blue-50 text-blue-700">
                <div class="flex items-center justify-between">
                    <span>Available: <strong>{{ availableLoyaltyPoints }}</strong> points</span>
                    <span v-if="loyaltyRule">
                        ({{ loyaltyRule.redeem_points }} pts = ${{ loyaltyRule.redeem_value }})
                    </span>
                </div>
                <div v-if="maxRedeemablePoints > 0" class="mt-1">
                    Max for this order: <strong>{{ maxRedeemablePoints }}</strong> points
                </div>
            </div>

            <div v-if="availableLoyaltyPoints <= 0" class="p-2 text-xs rounded bg-gray-50 text-gray-500">
                You don't have any loyalty points to redeem.
            </div>
            
            <div v-else-if="loyaltyDiscount === 0" class="flex gap-2">
                <TextInput
                    v-model.number="loyaltyPointsToUse"
                    type="number"
                    :max="Math.min(availableLoyaltyPoints, maxRedeemablePoints)"
                    placeholder="Points to use"
                    class="flex-1"
                />
                <PrimaryButton 
                    @click="applyLoyaltyPoints"
                    :disabled="isApplyingLoyalty || loyaltyPointsToUse <= 0"
                    class="text-sm whitespace-nowrap"
                >
                    <span v-if="isApplyingLoyalty">...</span>
                    <span v-else>Apply</span>
                </PrimaryButton>
            </div>
            
            <div v-else class="flex items-center justify-between p-2 rounded bg-green-50">
                <div class="flex items-center gap-2">
                    <i class="text-green-600 fa-solid fa-coins"></i>
                    <span class="text-sm font-medium text-green-700">
                        {{ loyaltyPointsToUse }} points = {{ formatCurrency(loyaltyDiscount) }}
                    </span>
                </div>
                <button 
                    @click="removeLoyaltyPoints"
                    class="text-sm text-red-600 hover:text-red-700"
                >
                    Remove
                </button>
            </div>
        </div>

        <!-- Discounts Applied -->
        <div v-if="totalDiscount > 0" class="pt-4 mt-4 space-y-2 text-sm border-t border-gray-200">
            <div v-if="couponDiscount > 0" class="flex justify-between text-green-600">
                <span>Coupon Discount</span>
                <span>-{{ formatCurrency(couponDiscount) }}</span>
            </div>
            <div v-if="loyaltyDiscount > 0" class="flex justify-between text-green-600">
                <span>Loyalty Points Discount</span>
                <span>-{{ formatCurrency(loyaltyDiscount) }}</span>
            </div>
        </div>

        <!-- Final Total -->
        <div class="pt-4 mt-4 border-t-2 border-gray-300">
            <div class="flex items-center justify-between">
                <span class="text-lg font-bold text-gray-900">Total</span>
                <span class="text-2xl font-bold text-primary-600">
                    {{ formatCurrency(finalTotal) }}
                </span>
            </div>
        </div>
    </div>
</template>
