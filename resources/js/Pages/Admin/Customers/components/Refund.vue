<script setup>
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { router } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    id: String,
});

const confirmingRecordRefunding = ref(false);

const confirmRefund = () => {
    confirmingRecordRefunding.value = true;
};

const closeModal = () => {
    confirmingRecordRefunding.value = false;
};

const refund = (event) => {
    event.preventDefault();
    router.put(
        route("admin.customers.refundTransaction", props.id),
        {},
        {
            onFinish: () => {
                closeModal();
            },
        }
    );
};
</script>
<template>
    <div class="float-right">
        <SecondaryButton @click="confirmRefund">Refund</SecondaryButton>

        <Modal :show="confirmingRecordRefunding" @close="closeModal">
            <div class="p-6">
                <h2 class="text-start text-lg font-medium text-gray-900">
                    Are you sure you want to refund this transaction?
                </h2>

                <div class="flex mt-6 gap-4 justify-end">
                    <SecondaryButton @click="closeModal"
                        >Cancel</SecondaryButton
                    >
                    <DangerButton @click="refund">Refund</DangerButton>
                </div>
            </div>
        </Modal>
    </div>
</template>
