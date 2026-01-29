<template>
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Coupon Code</h3>
            <!-- Applied Pill Badge -->
            <div v-if="appliedCoupon" class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-gradient-to-r from-green-500 to-emerald-600 text-white shadow-sm">
                    <TicketIcon class="w-4 h-4" />
                    {{ appliedCoupon.code }}
                    <span class="text-green-100">
                        ({{ appliedCoupon.discount_type === 'percentage' ? `${appliedCoupon.discount_value}%` : `$${appliedCoupon.discount_value}` }} off)
                    </span>
                </span>
                <button
                    @click="removeCoupon"
                    class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-full transition-colors"
                    title="Remove coupon"
                >
                    <XMarkIcon class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Coupon Input - Hidden when coupon is applied -->
        <div v-if="!appliedCoupon" class="flex gap-2">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <TicketIcon class="h-5 w-5 text-gray-400" />
                </div>
                <input
                    v-model="couponCode"
                    type="text"
                    placeholder="Enter coupon code"
                    class="w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent uppercase placeholder:normal-case transition-all"
                    @keyup.enter="applyCoupon"
                />
            </div>
            <button
                @click="applyCoupon"
                :disabled="!couponCode || loading"
                class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-5 py-2.5 rounded-lg font-medium transition-all flex items-center gap-2"
            >
                <ArrowPathIcon v-if="loading" class="w-4 h-4 animate-spin" />
                {{ loading ? "Applying..." : "Apply" }}
            </button>
        </div>

        <!-- Error Message -->
        <div v-if="message && messageType === 'error'" class="mt-3">
            <div class="flex items-center gap-2 p-3 rounded-lg bg-red-50 border border-red-100 text-red-700 text-sm">
                <ExclamationCircleIcon class="w-5 h-5 flex-shrink-0" />
                {{ message }}
            </div>
        </div>

        <!-- Success Message (brief, for manual apply only) -->
        <div v-if="message && messageType === 'success' && !appliedCoupon" class="mt-3">
            <div class="flex items-center gap-2 p-3 rounded-lg bg-green-50 border border-green-100 text-green-700 text-sm">
                <CheckCircleIcon class="w-5 h-5 flex-shrink-0" />
                {{ message }}
            </div>
        </div>

        <!-- Applied Coupon Savings Display -->
        <div
            v-if="appliedCoupon"
            class="mt-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg p-4"
        >
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-100 rounded-full">
                        <SparklesIcon class="w-5 h-5 text-green-600" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-green-800">
                            Coupon Applied!
                        </p>
                        <p class="text-xs text-green-600">
                            {{ appliedCoupon.description || 'Discount applied to your order' }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-xl font-bold text-green-700">
                        -${{ appliedDiscount.toFixed(2) }}
                    </p>
                    <p class="text-xs text-green-600">You're saving!</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import { useToast } from "vue-toastification";
import { 
    TicketIcon, 
    XMarkIcon, 
    ArrowPathIcon,
    ExclamationCircleIcon,
    CheckCircleIcon,
    SparklesIcon
} from "@heroicons/vue/24/outline";

const props = defineProps({
    orderAmount: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(["coupon-applied", "coupon-removed"]);
const toast = useToast();

const couponCode = ref("");
const loading = ref(false);
const message = ref("");
const messageType = ref("");
const appliedCoupon = ref(null);
const appliedDiscount = ref(0);

// Helper for null-safe CSRF token retrieval
const getCSRFToken = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) {
        console.warn('CSRF token meta tag not found');
        return '';
    }
    return meta.getAttribute('content') ?? '';
};

const applyCoupon = async () => {
    if (!couponCode.value.trim()) return;

    loading.value = true;
    message.value = "";
    messageType.value = "";

    try {
        const response = await fetch(route("customer.coupons.validate"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": getCSRFToken(),
            },
            body: JSON.stringify({
                code: couponCode.value.trim().toUpperCase(),
                order_amount: props.orderAmount,
            }),
        });

        const result = await response.json();

        if (result.success) {
            appliedCoupon.value = result.coupon;
            appliedDiscount.value = result.discount;
            message.value = "";
            messageType.value = "";
            emit("coupon-applied", {
                coupon: result.coupon,
                discount: result.discount,
            });
        } else {
            message.value = result.message;
            messageType.value = "error";
        }
    } catch (error) {
        console.log("🚀 ~ applyCoupon ~ error:", error);
        message.value = "An error occurred while applying the coupon.";
        messageType.value = "error";
    } finally {
        loading.value = false;
    }
};

const checkAutoApply = async () => {
    if (appliedCoupon.value) return;

    loading.value = true;
    try {
        const response = await fetch(route("customer.coupons.auto-apply"), {
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
            appliedCoupon.value = result.coupon;
            appliedDiscount.value = result.discount;
            toast.success(`Coupon "${result.coupon.code}" automatically applied!`);
            
            emit("coupon-applied", {
                coupon: result.coupon,
                discount: result.discount,
            });
        }
    } catch (error) {
        console.log("Auto-apply check failed", error);
    } finally {
        loading.value = false;
    }
};

const removeCoupon = () => {
    appliedCoupon.value = null;
    appliedDiscount.value = 0;
    couponCode.value = "";
    message.value = "";
    messageType.value = "";
    emit("coupon-removed");
};

// Clear error message after 5 seconds
watch(message, (newMessage) => {
    if (newMessage && messageType.value === 'error') {
        setTimeout(() => {
            message.value = "";
            messageType.value = "";
        }, 5000);
    }
});

onMounted(() => {
    if (props.orderAmount > 0) {
        checkAutoApply();
    }
});
</script>
