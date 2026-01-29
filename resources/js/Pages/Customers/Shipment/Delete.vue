<script setup>
import DangerButton from "@js/Components/DangerButton.vue";
import SecondaryButton from "@js/Components/SecondaryButton.vue";
import Modal from "@js/Components/Modal.vue";

import { ref } from "vue";
import { router } from "@inertiajs/vue3";

const props = defineProps({
    id: String,
    packageId: String,
});

const confirmingRecordDeletion = ref(false);

const confirmRecordDeletion = () => {
    confirmingRecordDeletion.value = true;
};

const closeModal = () => {
    confirmingRecordDeletion.value = false;
};

const deleteRecord = (event) => {
    event.target.disabled = true;

    router.delete(
        route("customer.ship.packages.delete", {
            id: props.id,
            packageId: props.packageId,
        }),
        {
            onFinish: () => {
                event.target.disabled = false;
            },
        }
    );
};
</script>

<template>
    <div class="">
        <button
            class="text-red-600 hover:text-red-800 text-center"
            @click="confirmRecordDeletion"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>

        <Modal :show="confirmingRecordDeletion" @close="closeModal">
            <div class="p-6">
                <h2 class="text-start text-lg font-medium text-gray-900">
                    Are you sure you want to delete the record?
                </h2>

                <div class="flex mt-6 gap-4 justify-end">
                    <SecondaryButton @click="closeModal"
                        >Cancel</SecondaryButton
                    >
                    <DangerButton @click="deleteRecord">Delete</DangerButton>
                </div>
            </div>
        </Modal>
    </div>
</template>
