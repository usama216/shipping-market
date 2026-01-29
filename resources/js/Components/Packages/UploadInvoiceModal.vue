<template>
    <Modal :show="isShowUploadInvoiceModal" @close="closeModal">
        <div class="p-6 max-w-xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-800">
                    Upload Merchant Invoice
                </h2>
                <button
                    @click="closeModal"
                    class="text-gray-500 hover:text-gray-700"
                >
                    &times;
                </button>
            </div>

            <!-- File Input -->
            <div>
                <label class="block mb-2 font-medium text-gray-700"
                    >Select Files</label
                >

                <div class="flex items-center gap-4">
                    <input
                        ref="fileInput"
                        type="file"
                        multiple
                        accept=".bmp, .jpg, .jpeg, .gif, .tif, .tiff, .pdf"
                        class="hidden"
                        @change="handleFiles"
                    />
                    <button
                        type="button"
                        class="px-4 py-2 bg-primary-500 text-white rounded hover:bg-primary-600 transition"
                        @click="$refs.fileInput.click()"
                    >
                        Browse
                    </button>
                    <span class="text-sm text-gray-600 truncate max-w-xs">
                        {{ fileNames || "No file chosen" }}
                    </span>
                </div>

                <p class="mt-2 text-sm text-gray-500">
                    Accepted File Types: BMP, JPG, JPEG, GIF, TIF, TIFF, PDF<br />
                    Max File Size: 2MB each
                </p>
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-4 mt-6">
                <button
                    type="button"
                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition"
                    @click="cancelUpload"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="px-4 py-2 bg-primary-500 text-white rounded hover:bg-primary-600 transition"
                    @click="uploadFiles"
                    :disabled="!files.length"
                >
                    Upload Document
                </button>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref } from "vue";

const props = defineProps({
    isShowUploadInvoiceModal: Boolean,
});

const emit = defineEmits(["close", "uploaded"]);

const files = ref([]);
const fileNames = ref("");

const closeModal = () => {
    emit("close");
    files.value = [];
    fileNames.value = "";
};

const cancelUpload = () => {
    files.value = [];
    fileNames.value = "";
};

const handleFiles = (event) => {
    files.value = Array.from(event.target.files).filter(
        (file) => file.size <= 2 * 1024 * 1024
    ); // 2MB limit
    fileNames.value = files.value.map((f) => f.name).join(", ");
};

const uploadFiles = () => {
    const formData = new FormData();
    files.value.forEach((file) => {
        formData.append("invoices[]", file);
    });

    emit("uploaded", formData);

    closeModal();
};
</script>

<style scoped></style>
