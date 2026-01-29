<template>
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Loyalty Points</h3>

        <!-- Current Points Display -->
        <div class="bg-blue-50 border border-blue-200 rounded-md p-4 mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-800">
                        Available Points
                    </p>
                    <p class="text-2xl font-bold text-blue-900">
                        {{ loyaltySummary.current_points }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-blue-600">
                        {{
                            loyaltyRule
                                ? `$${loyaltyRule.redeem_value} per ${loyaltyRule.redeem_points} points`
                                : "No loyalty rule configured"
                        }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Points Redemption -->
        <div v-if="loyaltySummary.current_points > 0" class="space-y-4">
            <div class="flex gap-2">
                <input
                    v-model="pointsToRedeem"
                    type="number"
                    :max="maxRedeemablePoints"
                    min="0"
                    placeholder="Enter points to redeem"
                    class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :disabled="appliedLoyaltyDiscount"
                />
                <button
                    @click="calculateLoyaltyDiscount"
                    :disabled="
                        !pointsToRedeem || loading || appliedLoyaltyDiscount
                    "
                    class="bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white px-4 py-2 rounded-md"
                >
                    {{ loading ? "Calculating..." : "Calculate" }}
                </button>
                <button
                    v-if="appliedLoyaltyDiscount"
                    @click="removeLoyaltyDiscount"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md"
                >
                    Remove
                </button>
            </div>

            <!-- Max Points Info -->
            <p class="text-sm text-gray-600">
                Maximum redeemable: {{ maxRedeemablePoints }} points
            </p>

            <!-- Error/Success Messages -->
            <div v-if="message" class="mb-4">
                <div
                    :class="[
                        'p-3 rounded-md text-sm',
                        messageType === 'success'
                            ? 'bg-green-100 text-green-800'
                            : 'bg-red-100 text-red-800',
                    ]"
                >
                    {{ message }}
                </div>
            </div>

            <!-- Applied Loyalty Discount Display -->
            <div
                v-if="appliedLoyaltyDiscount"
                class="bg-green-50 border border-green-200 rounded-md p-4"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-800">
                            Loyalty Discount
                        </p>
                        <p class="text-sm text-green-600">
                            {{ appliedPoints }} points redeemed
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-semibold text-green-800">
                            -${{ appliedLoyaltyDiscount.toFixed(2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- No Points Message -->
        <div v-else class="bg-gray-50 border border-gray-200 rounded-md p-4">
            <p class="text-sm text-gray-600">
                You don't have any loyalty points to redeem. Make a purchase to
                earn points!
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useToast } from "vue-toastification";

const toast = useToast();

const props = defineProps({
    orderAmount: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(["loyalty-applied", "loyalty-removed"]);

const loading = ref(false);
const message = ref("");
const messageType = ref("");
const pointsToRedeem = ref("");
const appliedLoyaltyDiscount = ref(0);
const appliedPoints = ref(0);
const loyaltySummary = ref({
    current_points: 0,
    total_earned: 0,
    total_redeemed: 0,
    total_discount_earned: 0,
    net_points: 0,
});
const loyaltyRule = ref(null);
const maxRedeemablePoints = ref(0);

// Helper for null-safe CSRF token retrieval
const getCSRFToken = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) {
        console.warn('CSRF token meta tag not found');
        return '';
    }
    return meta.getAttribute('content') ?? '';
};

const fetchLoyaltySummary = async () => {
    try {
        const response = await fetch(route("customer.loyalty.summary"));
        const result = await response.json();
        if (result.success) {
            loyaltySummary.value = result.summary;
        }
    } catch (error) {
        console.error("Failed to fetch loyalty summary:", error);
        toast.warning("Unable to load loyalty points. Please refresh the page.");
    }
};

const fetchMaxRedeemable = async () => {
    try {
        const response = await fetch(route("customer.loyalty.max-redeemable"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCSRFToken(),
            },
            body: JSON.stringify({
                order_amount: props.orderAmount,
            }),
        });
        const result = await response.json();
        if (result.success) {
            maxRedeemablePoints.value = result.max_points;
        }
    } catch (error) {
        console.error("Failed to fetch max redeemable points:", error);
        // Silent failure for this one - not critical to user flow
    }
};

const calculateLoyaltyDiscount = async () => {
    if (!pointsToRedeem.value || pointsToRedeem.value <= 0) return;

    loading.value = true;
    message.value = "";
    messageType.value = "";

    try {
        const response = await fetch(
            route("customer.loyalty.calculate-discount"),
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": getCSRFToken(),
                },
                body: JSON.stringify({
                    points: parseInt(pointsToRedeem.value),
                    order_amount: props.orderAmount,
                }),
            }
        );

        const result = await response.json();

        if (result.success) {
            appliedLoyaltyDiscount.value = result.discount;
            appliedPoints.value = result.points_required;
            message.value = result.message;
            messageType.value = "success";
            emit("loyalty-applied", {
                points: result.points_required,
                discount: result.discount,
            });
        } else {
            message.value = result.message;
            messageType.value = "error";
        }
    } catch (error) {
        message.value = "An error occurred while calculating the discount.";
        messageType.value = "error";
    } finally {
        loading.value = false;
    }
};

const removeLoyaltyDiscount = () => {
    appliedLoyaltyDiscount.value = 0;
    appliedPoints.value = 0;
    pointsToRedeem.value = "";
    message.value = "";
    messageType.value = "";
    emit("loyalty-removed");
};

const fetchLoyaltyRule = async () => {
    try {
        const response = await fetch(route("customer.loyalty.summary"));
        const result = await response.json();
        if (result.success && result.summary.loyalty_rule) {
            loyaltyRule.value = result.summary.loyalty_rule;
        }
    } catch (error) {
        console.error("Failed to fetch loyalty rule:", error);
    }
};

// Clear message after 5 seconds
watch(message, (newMessage) => {
    if (newMessage) {
        setTimeout(() => {
            message.value = "";
            messageType.value = "";
        }, 5000);
    }
});

// Fetch data on mount
onMounted(() => {
    fetchLoyaltySummary();
    fetchLoyaltyRule();
    fetchMaxRedeemable();
});
</script>
