<script setup>
import DangerButton from "@js/Components/DangerButton.vue";
import SecondaryButton from "@js/Components/SecondaryButton.vue";
import Modal from "@js/Components/Modal.vue";
import CouponSection from "@/Components/Checkout/CouponSection.vue";
import LoyaltySection from "@/Components/Checkout/LoyaltySection.vue";

import { ref, watch, computed } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
const toast = useToast();
const props = defineProps({
    ship: String,
    selectedShipMethod: [Number, Object],
    selectedPackingOption: [Array, Number],
    selectedShippingPreferenceOption: [Array, Number],
    checkoutAmount: Number,
    selectedCardId: Number,
    selectedAddressId: Number,
});

console.log(props.selectedCardId, props?.selectedAddressId);
const confirmingRecordDeletion = ref(false);

const confirmRecordDeletion = () => {
    confirmingRecordDeletion.value = true;
};

const closeModal = () => {
    confirmingRecordDeletion.value = false;
};

const form = useForm({
    id: props.ship?.id || "",
    international_shipping_option_id: props.selectedShipMethod || "",
    packing_option_id: props.selectedPackingOption || "",
    shipping_preference_option_id: props.selectedShippingPreferenceOption || "",
    estimated_shipping_charges: props.checkoutAmount || 0.0,
    subtotal: props.checkoutAmount || 0.0,
    card_id: props.selectedCardId || null,
    user_address_id: props.selectedAddressId || null,
    coupon_code: null,
    coupon_order_amount: props.checkoutAmount || 0.0,
    coupon_discount: 0.0,
});

// Coupon and Loyalty refs
const couponDiscount = ref(0);
const loyaltyDiscount = ref(0);
const appliedCouponCode = ref("");

const finalAmount = computed(() => {
    const baseAmount = Number(props.checkoutAmount) || 0.0;
    const coupon = Number(couponDiscount.value) || 0.0;
    const loyalty = Number(loyaltyDiscount.value) || 0.0;
    return baseAmount - coupon - loyalty;
});
const checkout = (event) => {
    event.preventDefault();
    console.log(form.card_id);
    if (!form.card_id) {
        toast.error("Please select your card.");
        return;
    }

    if (!form.user_address_id) {
        toast.error("Please select your address.");
        return;
    }

    // Update form with final amount including discounts
    form.estimated_shipping_charges = finalAmount.value;
    form.subtotal = finalAmount.value;

    form.post(route("customer.ship.checkout"), {
        onFinish: () => {
            closeModal();
        },
    });
};

const handleCouponApplied = (data) => {
    // Handle both numeric or object payloads
    const amount = typeof data === "object" ? data.discount : data;
    const code = typeof data === "object" ? data.coupon?.code : "";

    couponDiscount.value = Number(amount) || 0;
    appliedCouponCode.value = code || "";
    form.coupon_code = appliedCouponCode.value || null;
    form.coupon_order_amount = Number(props.checkoutAmount) || 0.0;
    form.coupon_discount = couponDiscount.value;
    toast.success(
        `Coupon applied! You saved $${couponDiscount.value.toFixed(2)}`
    );
};

const handleCouponRemoved = () => {
    couponDiscount.value = 0;
    appliedCouponCode.value = "";
    form.coupon_code = null;
    form.coupon_discount = 0.0;
    toast.info("Coupon removed");
};

const handleLoyaltyApplied = (discount) => {
    loyaltyDiscount.value = Number(discount) || 0;
    toast.success(
        `Loyalty points applied! You saved $${loyaltyDiscount.value.toFixed(2)}`
    );
};

const handleLoyaltyRemoved = () => {
    loyaltyDiscount.value = 0;
    toast.info("Loyalty points removed");
};

watch(
    () => ({
        shipId: props.ship?.id,
        method: props.selectedShipMethod,
        packing: props.selectedPackingOption,
        preference: props.selectedShippingPreferenceOption,
        amount: props.checkoutAmount,
        card: props.selectedCardId,
        address: props.selectedAddressId,
    }),
    (newVals) => {
        form.id = newVals.shipId || "";
        form.international_shipping_option_id = newVals.method || "";
        form.packing_option_id = newVals.packing || "";
        form.shipping_preference_option_id = newVals.preference || "";
        form.estimated_shipping_charges = newVals.amount || 0.0;
        form.subtotal = newVals.amount || 0.0;
        form.card_id = newVals.card || null;
        form.user_address_id = newVals.address || null;
    },
    { deep: true, immediate: true }
);

// Watch for discount changes and update form
watch([couponDiscount, loyaltyDiscount], () => {
    form.estimated_shipping_charges = finalAmount.value;
    form.subtotal = finalAmount.value;
});
</script>

<template>
    <div class="">
        <button
            @click="confirmRecordDeletion"
            class="bg-primary-500 text-white w-full py-2 px-4 hover:bg-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-opacity-50"
        >
            Estimated Total
        </button>

        <Modal :show="confirmingRecordDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-center text-lg font-medium text-gray-900 mb-4">
                    Shipment Checkout
                </h2>

                <!-- Coupon Section -->
                <div class="mb-4">
                    <CouponSection
                        :order-amount="checkoutAmount"
                        @coupon-applied="handleCouponApplied"
                        @coupon-removed="handleCouponRemoved"
                    />
                </div>

                <!-- Loyalty Section -->
                <div class="mb-4">
                    <LoyaltySection
                        :order-amount="checkoutAmount"
                        @loyalty-applied="handleLoyaltyApplied"
                        @loyalty-removed="handleLoyaltyRemoved"
                    />
                </div>

                <!-- Order Summary -->
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <h3 class="font-medium text-gray-900 mb-2">
                        Order Summary
                    </h3>
                    <div class="space-y-1 text-sm">
                        <div class="flex justify-between">
                            <span>Base Amount:</span>
                            <span>${{ checkoutAmount.toFixed(2) }}</span>
                        </div>
                        <div
                            v-if="couponDiscount > 0"
                            class="flex justify-between text-green-600"
                        >
                            <span class="flex items-center gap-1">
                                Coupon
                                <span v-if="appliedCouponCode" class="font-medium">({{ appliedCouponCode }})</span>:
                            </span>
                            <span>-${{ couponDiscount.toFixed(2) }}</span>
                        </div>
                        <div
                            v-if="loyaltyDiscount > 0"
                            class="flex justify-between text-green-600"
                        >
                            <span>Loyalty Points Discount:</span>
                            <span>-${{ loyaltyDiscount.toFixed(2) }}</span>
                        </div>
                        <hr class="my-2" />
                        <div class="flex justify-between font-medium text-base">
                            <span>Final Amount:</span>
                            <span class="text-blue-600">${{ finalAmount.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <p class="text-center text-sm text-gray-600 mb-4">
                    Your credit card will be charged ${{
                        finalAmount.toFixed(2)
                    }}
                    for this transaction.
                </p>

                <div class="flex mt-6 gap-4 justify-end">
                    <SecondaryButton @click="closeModal"
                        >Cancel</SecondaryButton
                    >
                    <DangerButton @click="checkout">Submit</DangerButton>
                </div>
            </div>
        </Modal>
    </div>
</template>
