<template>
    <div class="item-image-manager">
        <!-- File Upload Section -->
        <div class="mb-4">
            <InputLabel :for="`itemImages-${itemId}`" value="Item Images" />
            <input
                :id="`itemImages-${itemId}`"
                type="file"
                multiple
                accept="image/jpeg,image/png,image/webp"
                @change="handleFileChange"
                class="w-full border rounded p-2"
            />
            <p class="text-sm text-gray-600 mt-1">
                Accepted formats: JPEG, PNG, WebP (max 2MB each)
            </p>
        </div>

        <!-- Image Previews -->
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <!-- Existing Images from DB -->
            <div
                v-for="(file, index) in existingImages"
                :key="`existing-${index}`"
                class="relative group"
            >
                <div
                    class="w-full h-32 border rounded-lg overflow-hidden bg-gray-100"
                >
                    <img
                        :src="file.file_with_url"
                        :alt="file.name"
                        class="w-full h-full object-cover"
                    />
                </div>
                <button
                    type="button"
                    @click="removeExistingImage(index)"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                    title="Delete image"
                >
                    ✕
                </button>
                <p
                    class="text-xs text-gray-600 mt-1 truncate"
                    :title="file.name"
                >
                    {{ file.name }}
                </p>
            </div>

            <!-- New Images (not yet saved) -->
            <div
                v-for="(file, index) in newImages"
                :key="`new-${index}`"
                class="relative group"
            >
                <div
                    class="w-full h-32 border rounded-lg overflow-hidden bg-gray-100"
                >
                    <img
                        :src="URL.createObjectURL(file)"
                        :alt="file.name"
                        class="w-full h-full object-cover"
                    />
                </div>
                <button
                    type="button"
                    @click="removeNewImage(index)"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity"
                    title="Remove image"
                >
                    ✕
                </button>
                <p
                    class="text-xs text-gray-600 mt-1 truncate"
                    :title="file.name"
                >
                    {{ file.name }}
                </p>
            </div>
        </div>

        <!-- Image Count Summary -->
        <div class="mt-2 text-sm text-gray-600">
            <span v-if="existingImages.length > 0">
                {{ existingImages.length }} existing image{{
                    existingImages.length !== 1 ? "s" : ""
                }}
            </span>
            <span v-if="newImages.length > 0">
                <span v-if="existingImages.length > 0"> + </span>
                {{ newImages.length }} new image{{
                    newImages.length !== 1 ? "s" : ""
                }}
            </span>
            <span v-if="existingImages.length === 0 && newImages.length === 0">
                No images uploaded
            </span>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import InputLabel from "@/Components/InputLabel.vue";

const props = defineProps({
    itemId: {
        type: [String, Number],
        required: true,
    },
    existingImages: {
        type: Array,
        default: () => [],
    },
    newImages: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    "update:existingImages",
    "update:newImages",
    "fileChange",
]);

const handleFileChange = (event) => {
    const files = Array.from(event.target.files);
    emit("fileChange", files);
};

const removeExistingImage = (index) => {
    const updatedImages = [...props.existingImages];
    const removedImage = updatedImages.splice(index, 1)[0];
    emit("update:existingImages", updatedImages);
    emit("imageRemoved", removedImage);
};

const removeNewImage = (index) => {
    const updatedImages = [...props.newImages];
    updatedImages.splice(index, 1);
    emit("update:newImages", updatedImages);
};
</script>

<style scoped>
.item-image-manager {
    @apply w-full;
}
</style>
