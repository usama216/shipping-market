<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { useToast } from "vue-toastification";

const props = defineProps({
    show: Boolean,
    packageData: Object,
});

const emit = defineEmits(['close']);
const toast = useToast();

const form = useForm({
    note: '',
});

watch(() => props.packageData, (newVal) => {
    if (newVal) {
        form.note = newVal.note || '';
    }
}, { immediate: true });

const closeModal = () => {
    emit('close');
    form.reset();
};

const submit = () => {
    form.put(route('admin.packages.updateNote', props.packageData.id), {
        onSuccess: () => {
            toast.success("Note updated successfully");
            closeModal();
        },
        onError: () => {
            toast.error("Failed to update note");
        }
    });
};
</script>

<template>
    <Modal :show="show" @close="closeModal" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-4">
                {{ packageData?.note ? 'Edit Note' : 'Add Note' }}
                <span v-if="packageData" class="text-sm text-gray-500 font-normal">
                    #{{ packageData.package_id }}
                </span>
            </h2>

            <div class="mt-4">
                <label for="note" class="block text-sm font-medium text-gray-700 mb-1">Note Content</label>
                <textarea
                    id="note"
                    v-model="form.note"
                    rows="4"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                    placeholder="Enter note details here..."
                ></textarea>
                <p v-if="form.errors.note" class="mt-1 text-sm text-red-600">
                    {{ form.errors.note }}
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton @click="closeModal">
                    Cancel
                </SecondaryButton>
                
                <PrimaryButton 
                    @click="submit" 
                    :class="{ 'opacity-25': form.processing }" 
                    :disabled="form.processing"
                >
                    Save Note
                </PrimaryButton>
            </div>
        </div>
    </Modal>
</template>
