<script setup>
import { ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Modal from "./Modal.vue";
import DangerButton from "./DangerButton.vue";
import PrimaryButton from "./PrimaryButton.vue";
import SecondaryButton from "./SecondaryButton.vue";

const props = defineProps({
    packageData: { type: Object, required: true },
    status: { type: Number, required: true },
});

const emit = defineEmits(["status-updated"]);

const showItems = ref(false);
const showFiles = ref(false);
const showFileModal = ref(false);
const noteText = ref(props.packageData.note || "");
const isNoting = ref(false);

// Status badge class
const getStatusBadgeClass = (status) => {
    const statusClasses = {
        1: "bg-red-100 text-red-800",
        2: "bg-yellow-100 text-yellow-800",
        3: "bg-blue-100 text-blue-800",
        4: "bg-green-100 text-green-800",
    };
    return statusClasses[status] || "bg-gray-100 text-gray-800";
};

// Format date
const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString();
};

// Toggle note modal
const handleAddNote = () => (showFileModal.value = !showFileModal.value);

// Save note
const saveNote = async () => {
    try {
        isNoting.value = true;
        await router.put(
            route("admin.packages.updateNote", props.packageData.id),
            { note: noteText.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    isNoting.value = false;
                    showFileModal.value = false;
                },
            }
        );
    } catch (error) {
        console.error(error);
    }
};
</script>

<template>
    <div
        class="transition-shadow duration-200 bg-white border border-gray-200 rounded-lg shadow-sm cursor-default package-card hover:shadow-md"
    >
        <!-- Drag handle -->
        <div
            class="flex items-center justify-between p-2 rounded-t-lg cursor-move drag-handle bg-gray-50"
        >
            <h4 class="text-sm font-semibold text-gray-900 truncate">
                {{ packageData.package_id }}
            </h4>
            <span
                :class="[
                    'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                    getStatusBadgeClass(packageData.status),
                ]"
            >
                {{ packageData.status_name }}
            </span>
        </div>

        <!-- Package Details -->
        <div class="p-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-600">Suite:</span>
                <span class="text-gray-900 font-medium truncate max-w-[120px]">
                    {{ packageData?.customer?.suite || "N/A" }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">From:</span>
                <span class="text-gray-900 font-medium truncate max-w-[120px]">
                    {{ packageData.from || "N/A" }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Date:</span>
                <span class="text-gray-900">{{
                    formatDate(packageData.date_received)
                }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Weight:</span>
                <span class="text-gray-900">
                    {{
                        packageData.total_weight ? packageData.total_weight + " kg" : "N/A"
                    }}
                </span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-600">Value:</span>
                <span class="font-medium text-gray-900">
                    {{
                        packageData.total_value
                            ? "$" + packageData.total_value
                            : "N/A"
                    }}
                </span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div
            class="flex items-center justify-between px-4 pb-4 border-t border-gray-100"
        >
            <div class="flex items-center gap-2">
                <Link
                    :href="route('admin.packages.edit', packageData.id)"
                    class="flex items-center text-xs font-medium text-blue-600 hover:text-blue-800"
                >
                    <i class="mr-1 fas fa-edit"></i> Edit
                </Link>
                <button
                    @click.stop="handleAddNote"
                    class="flex items-center text-xs font-medium text-primary-500 hover:text-primary-700"
                >
                    <i class="mr-1 fa-solid fa-file-pen"></i> Add Note
                </button>
            </div>

            <div class="flex space-x-1">
                <button
                    v-if="packageData.items?.length"
                    @click="showItems = !showItems"
                    class="text-xs text-gray-500 hover:text-gray-700"
                    title="View Items"
                >
                    <i class="fas fa-box"></i> {{ packageData.items.length }}
                </button>

                <button
                    v-if="packageData.files?.length"
                    @click="showFiles = !showFiles"
                    class="text-xs text-gray-500 hover:text-gray-700"
                    title="View Files"
                >
                    <i class="fas fa-file"></i> {{ packageData.files.length }}
                </button>
            </div>
        </div>

        <!-- Expandable Items -->
        <div
            v-if="showItems && packageData.items?.length"
            class="px-4 pb-4 space-y-1 overflow-y-auto border-t border-gray-100 max-h-20"
        >
            <div
                v-for="item in packageData.items.slice(0, 3)"
                :key="item.id"
                class="px-2 py-1 text-xs text-gray-600 rounded bg-gray-50"
            >
                {{ item.description || "No description" }}
            </div>
            <div
                v-if="packageData.items.length > 3"
                class="text-xs text-gray-500"
            >
                +{{ packageData.items.length - 3 }} more items
            </div>
        </div>

        <!-- Expandable Files -->
        <div
            v-if="showFiles && packageData.files?.length"
            class="px-4 pb-4 space-y-1 overflow-y-auto border-t border-gray-100 max-h-20"
        >
            <div
                v-for="file in packageData.files.slice(0, 3)"
                :key="file.id"
                class="flex items-center px-2 py-1 text-xs text-gray-600 rounded bg-gray-50"
            >
                <i class="mr-1 fas fa-file"></i> {{ file.name }}
            </div>
            <div
                v-if="packageData.files.length > 3"
                class="text-xs text-gray-500"
            >
                +{{ packageData.files.length - 3 }} more files
            </div>
        </div>

        <!-- Note Modal -->
        <Modal :show="showFileModal" @close="handleAddNote">
            <div class="p-5">
                <div
                    class="flex items-center justify-between pb-2 mb-2 border-b"
                >
                    <h3 class="text-lg font-semibold text-gray-900">
                        Add Note
                    </h3>
                    <button
                        @click="handleAddNote"
                        class="text-gray-400 transition hover:text-gray-600"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="space-y-3 text-sm text-gray-700">
                    <p>
                        Add a note for package
                        <strong>{{ packageData.package_id }}</strong
                        >.
                    </p>
                    <textarea
                        v-model="noteText"
                        rows="4"
                        class="w-full p-2 border rounded-lg border-primary-300 focus:ring-1 focus:ring-primary-500 focus:outline-primary-500 focus:border-primary-500"
                        placeholder="Type your note here..."
                    ></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2 mt-4 border-t">
                    <SecondaryButton @click="handleAddNote"
                        >Cancel</SecondaryButton
                    >
                    <PrimaryButton
                        @click="saveNote"
                        :processing="isNoting"
                        :disabled="isNoting"
                        >Save Note</PrimaryButton
                    >
                </div>
            </div>
        </Modal>
    </div>
</template>

<style scoped>
.package-card {
    @apply transition-all duration-200;
}

.package-card:hover {
    @apply transform scale-[1.02];
}

/* Custom scrollbar */
.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}
.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}
.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}
.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
