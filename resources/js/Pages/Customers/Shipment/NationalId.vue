<script setup>
import DangerButton from "@js/Components/DangerButton.vue";
import SecondaryButton from "@js/Components/SecondaryButton.vue";
import Modal from "@js/Components/Modal.vue";

import { ref, computed } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    ship: Object,
    userPreferences: Object,
});

const confirmingRecordDeletion = ref(false);

const confirmRecordDeletion = () => {
    confirmingRecordDeletion.value = true;
};

const closeModal = () => {
    confirmingRecordDeletion.value = false;
};

// Pre-populate from ship.national_id first, then fallback to user's saved tax_id preference
const form = useForm({
    national_id: props.ship?.national_id || props.userPreferences?.tax_id || "",
});

// For display purposes
const hasTaxId = computed(() => !!props.ship?.national_id);
const displayTaxId = computed(() => props.ship?.national_id || props.userPreferences?.tax_id || null);
const addNationalId = (event) => {
    event.target.disabled = true;

    form.post(
        route("customer.ship.packages.nationalId", { id: props.ship?.id }),
        {
            onFinish: () => {
                closeModal();
            },
        }
    );
};
</script>

<template>
    <div class="">
        <div class="flex items-center gap-2">
            <span class="text-gray-700">Tax ID:</span>
            <template v-if="hasTaxId">
                <span class="font-medium text-gray-900">{{ displayTaxId }}</span>
                <a href="#" class="text-primary-500 text-sm" @click.prevent="confirmRecordDeletion">(Edit)</a>
            </template>
            <template v-else-if="displayTaxId">
                <span class="text-gray-500 italic">{{ displayTaxId }} (from preferences)</span>
                <a href="#" class="text-primary-500 text-sm" @click.prevent="confirmRecordDeletion">(Use this)</a>
            </template>
            <template v-else>
                <a href="#" class="text-primary-500" @click.prevent="confirmRecordDeletion">Add</a>
            </template>
        </div>

        <Modal :show="confirmingRecordDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-start text-lg font-medium text-gray-900">
                    Add Tax ID
                </h2>
                <div>
                    <TextInput v-model="form.national_id" required />
                    <InputError :message="form?.errors.national_id" />
                </div>

                <div class="flex mt-6 gap-4 justify-end">
                    <SecondaryButton @click="closeModal"
                        >Cancel</SecondaryButton
                    >
                    <DangerButton @click="addNationalId"
                        >Add Tax ID</DangerButton
                    >
                </div>
            </div>
        </Modal>
    </div>
</template>
