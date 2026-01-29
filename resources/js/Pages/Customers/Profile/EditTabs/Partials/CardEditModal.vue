<script setup>
import { ref, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import Modal from "@/Components/Modal.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    show: Boolean,
    card: Object,
});

const emit = defineEmits(["close", "updated"]);

const form = useForm({
    card_holder_name: "",
    address_line1: "",
    address_line2: "",
    country: "",
    state: "",
    city: "",
    postal_code: "",
    country_code: "",
    phone_number: "",
});

watch(
    () => props.card,
    (newVal) => {
        if (newVal) {
            form.card_holder_name = newVal.card_holder_name || "";
            form.address_line1 = newVal.address_line1 || "";
            form.address_line2 = newVal.address_line2 || "";
            form.country = newVal.country || "";
            form.state = newVal.state || "";
            form.city = newVal.city || "";
            form.postal_code = newVal.postal_code || "";
            form.country_code = newVal.country_code || "";
            form.phone_number = newVal.phone_number || "";
        }
    },
    { immediate: true }
);

const updateCard = () => {
    form.put(route("customer.card.update", props.card.id), {
        preserveScroll: true,
        onSuccess: () => {
            emit("updated");
            emit("close");
        },
    });
};
</script>

<template>
    <Modal :show="show" @close="$emit('close')">
        <div class="p-4 space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-xl font-bold text-black">Edit Card</h2>
                <button
                    @click="$emit('close')"
                    class="text-gray-500 hover:text-gray-700 text-2xl font-bold leading-none"
                >
                    &times;
                </button>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <InputLabel
                        for="card_holder_name"
                        value="Card Holder Name"
                    />
                    <TextInput
                        v-model="form.card_holder_name"
                        id="card_holder_name"
                        class="mt-1 block w-full"
                    />
                </div>
                <div>
                    <InputLabel for="address_line1" value="Address Line 1" />
                    <TextInput
                        v-model="form.address_line1"
                        id="address_line1"
                        class="mt-1 block w-full"
                    />
                </div>
                <div>
                    <InputLabel for="address_line2" value="Address Line 2" />
                    <TextInput
                        v-model="form.address_line2"
                        id="address_line2"
                        class="mt-1 block w-full"
                    />
                </div>
                <div>
                    <InputLabel for="country" value="Country" />
                    <TextInput
                        v-model="form.country"
                        id="country"
                        class="mt-1 block w-full"
                    />
                </div>
                <div>
                    <InputLabel for="state" value="State" />
                    <TextInput
                        v-model="form.state"
                        id="state"
                        class="mt-1 block w-full"
                    />
                </div>
                <div>
                    <InputLabel for="city" value="City" />
                    <TextInput
                        v-model="form.city"
                        id="city"
                        class="mt-1 block w-full"
                    />
                </div>
                <div>
                    <InputLabel for="postal_code" value="Postal Code" />
                    <TextInput
                        v-model="form.postal_code"
                        id="postal_code"
                        class="mt-1 block w-full"
                    />
                </div>
                <div>
                    <InputLabel for="country_code" value="Country Code" />
                    <TextInput
                        v-model="form.country_code"
                        id="country_code"
                        class="mt-1 block w-full"
                    />
                </div>
                <div>
                    <InputLabel for="phone_number" value="Phone Number" />
                    <TextInput
                        v-model="form.phone_number"
                        id="phone_number"
                        class="mt-1 block w-full"
                    />
                </div>
            </div>

            <div class="flex justify-end pt-4">
                <PrimaryButton @click="updateCard">Update Card</PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
