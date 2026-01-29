<script setup>
import { ref, computed } from "vue";

const props = defineProps({
    shipId: {
        type: Number,
        required: true,
    },
    amount: {
        type: Number,
        required: true,
    },
    customerAddressId: {
        type: Number,
        required: true,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["processing", "error", "success"]);

const isProcessing = ref(false);
const errorMessage = ref(null);

const canPay = computed(() => {
    return !props.disabled && !isProcessing.value && props.amount > 0 && props.customerAddressId;
});

/**
 * Initiate PayPal checkout flow via native PayPal REST API
 * Backend creates a PayPal Order and returns approval URL for redirect
 */
const initiatePayPalCheckout = async () => {
    if (!canPay.value) return;

    try {
        isProcessing.value = true;
        errorMessage.value = null;
        emit("processing", true);

        // Create PayPal Order on backend
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ?? '';
        if (!csrfToken) {
            console.warn('CSRF token meta tag not found');
        }
        
        const response = await fetch(route("customer.checkout.paypal.initiate"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
            body: JSON.stringify({
                ship_id: props.shipId,
                amount: props.amount,
                customer_address_id: props.customerAddressId,
            }),
        });

        const data = await response.json();

        if (!response.ok || data.error) {
            throw new Error(data.error || "Failed to initiate PayPal checkout");
        }

        // Redirect to PayPal approval URL
        if (data.approval_url) {
            window.location.href = data.approval_url;
            emit("success");
        } else {
            throw new Error("No approval URL returned from PayPal");
        }

    } catch (error) {
        console.error("PayPal checkout error:", error);
        errorMessage.value = error.message || "PayPal checkout failed. Please try again.";
        emit("error", errorMessage.value);
        isProcessing.value = false;
        emit("processing", false);
    }
    // Note: Don't reset isProcessing on success since we're redirecting
};
</script>

<template>
    <div class="paypal-button-container">
        <!-- PayPal Button -->
        <button
            type="button"
            :disabled="!canPay"
            @click="initiatePayPalCheckout"
            class="paypal-button"
            :class="{ 'paypal-button--disabled': !canPay }"
        >
            <span v-if="isProcessing" class="flex items-center justify-center gap-2">
                <i class="fa-solid fa-spinner fa-spin"></i>
                Connecting to PayPal...
            </span>
            <span v-else class="flex items-center justify-center gap-2">
                <i class="fa-brands fa-paypal text-lg"></i>
                Pay with PayPal
            </span>
        </button>

        <!-- Error Message -->
        <div
            v-if="errorMessage"
            class="mt-2 p-2 text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg"
        >
            <i class="fa-solid fa-circle-exclamation mr-1"></i>
            {{ errorMessage }}
        </div>

        <!-- PayPal Info -->
        <p class="mt-2 text-xs text-center text-gray-500">
            <i class="fa-solid fa-shield-halved mr-1"></i>
            Secure payment via PayPal. You'll be redirected to complete payment.
        </p>
    </div>
</template>

<style scoped>
.paypal-button {
    width: 100%;
    padding: 12px 24px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: linear-gradient(135deg, #003087 0%, #009cde 100%);
    color: white;
    border: none;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.paypal-button:hover:not(.paypal-button--disabled) {
    background: linear-gradient(135deg, #002060 0%, #0080c0 100%);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    transform: translateY(-1px);
}

.paypal-button--disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
</style>
