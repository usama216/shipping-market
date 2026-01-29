<script setup>
import Modal from "../Modal.vue";
import Stripe from "./partials/Stripe.vue";

const props = defineProps({
    publishableKey: String,
    savedAddresses: {
        type: Array,
        default: () => [],
    },
    isShowCardModal: {
        type: Boolean,
        default: false,
    },
    closeCardModal: {
        type: Function,
        required: true,
    },
});

const emit = defineEmits(["submitForm"]);

const submitForm = (stripeResponse) => {
    emit("submitForm", stripeResponse);
};
</script>
<template>
    <Modal :show="isShowCardModal" @close="closeCardModal">
        <div class="p-3 mx-3 space-y-6 text-black">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Add Card</h2>
                <button
                    @click="closeCardModal"
                    class="text-gray-500 hover:text-gray-700 text-2xl font-bold leading-none"
                >
                    &times;
                </button>
            </div>

            <!-- Modal Body -->
            <div>
                <Stripe
                    :stripeKey="props.publishableKey"
                    :savedAddresses="props.savedAddresses"
                    @submitForm="submitForm"
                />
            </div>
        </div>
    </Modal>
</template>
