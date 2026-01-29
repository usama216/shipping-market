<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import Edit from "../Edit.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Modal from "@/Components/Modal.vue";
import { ref } from "vue";
import TextInput from "@/Components/TextInput.vue";
import Stripe from "@/Components/Payment/partials/Stripe.vue";
import { useToast } from "vue-toastification";
import CardEditModal from "./Partials/CardEditModal.vue";

const toast = useToast();
const props = defineProps({
    publishableKey: String,
    cards: Object,
    customerAddresses: {
        type: Array,
        default: () => [],
    },
});

const isShowCardModal = ref(false);
const showCardModal = () => {
    isShowCardModal.value = true;
};
const closeCardModal = () => {
    isShowCardModal.value = false;
};

const submitForm = (stripeResponse) => {
    try {
        const { form } = stripeResponse;
        form.post(route("customer.card.add"), {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
        closeCardModal();
    } catch (error) {
        toast.error(error);
    }
};

const setDefault = (id) => {
    router.put(
        route("customer.card.setDefault", id),
        {},
        {
            onSuccess: () => toast.success("Default card updated"),
            onError: (err) =>
                toast.error(err.message || "Error setting default"),
        }
    );
};

const selectedCard = ref(null);
const isEditCardModal = ref(false);

const editCard = (card) => {
    selectedCard.value = card;
    isEditCardModal.value = true;
};

const deleteCard = (id) => {
    if (confirm("Are you sure you want to delete this card?")) {
        router.delete(route("customer.card.delete", id), {
            onSuccess: () => toast.success("Card deleted"),
            onError: (err) => toast.error(err.message || "Error deleting card"),
        });
    }
};
</script>
<template>
    <Head title="Payments" />
    <Edit>
        <div class="text-end">
            <PrimaryButton @click="showCardModal()">+ Add card</PrimaryButton>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
            <div
                v-for="card in props?.cards"
                :key="card?.id"
                class="relative bg-white border shadow rounded-xl p-5 space-y-3"
            >
                <div class="flex justify-between items-center">
                    <div class="text-gray-700 font-semibold">
                        {{ card?.brand }} **** **** **** {{ card?.last4 }}
                    </div>
                    <div
                        v-if="card?.is_default"
                        class="text-xs bg-green-100 text-green-800 font-bold px-2 py-1 rounded-full"
                    >
                        Default
                    </div>
                </div>

                <div class="text-sm text-gray-600">
                    <p>
                        <strong>Card Holder:</strong>
                        {{ card.card_holder_name }}
                    </p>
                    <p>
                        <strong>Expiry:</strong> {{ card?.exp_month }}/{{
                            card?.exp_year
                        }}
                    </p>
                    <p><strong>Phone:</strong> {{ card?.phone_number }}</p>
                    <p><strong>Address:</strong></p>
                    <p class="ml-2">
                        {{ card?.address_line1 }}<br />
                        {{ card?.address_line2 }}<br />
                        {{ card?.city }}, {{ card?.state }},
                        {{ card?.postal_code }}<br />
                        {{ card?.country }} ({{ card?.country_code }})
                    </p>
                </div>

                <div
                    class="flex justify-between items-center pt-3 border-t mt-3"
                >
                    <button
                        v-if="!card.is_default"
                        class="text-blue-600 hover:underline text-sm"
                        @click="setDefault(card.id)"
                    >
                        Set as Default
                    </button>
                    <div class="space-x-3">
                        <button
                            @click="editCard(card)"
                            class="text-blue-500 hover:underline"
                        >
                            Edit
                        </button>
                        <button
                            class="text-red-600 hover:underline text-sm"
                            @click="deleteCard(card.id)"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <CardEditModal
            :card="selectedCard"
            :show="isEditCardModal"
            @close="isEditCardModal = false"
        />
    </Edit>
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
                    :savedAddresses="props.customerAddresses"
                    @submitForm="submitForm"
                />
            </div>
        </div>
    </Modal>
</template>
