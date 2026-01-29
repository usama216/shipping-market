<script setup>
import { ref, watch } from "vue";
import draggable from "vuedraggable";
import PackageCard from "./PackageCard.vue";
import { useToast } from "vue-toastification";

const toast = useToast();

const props = defineProps({
    packages: { type: Array, required: true },
    canUpdate: { type: Boolean, default: true },
});

const emit = defineEmits(["status-updated"]);

const actionRequiredPackages = ref([]);
const inReviewPackages = ref([]);
const readyToSendPackages = ref([]);
const consolidatePackages = ref([]);

const initializePackages = () => {
    actionRequiredPackages.value = props.packages.filter((p) => p.status === 1);
    inReviewPackages.value = props.packages.filter((p) => p.status === 2);
    readyToSendPackages.value = props.packages.filter((p) => p.status === 3);
    consolidatePackages.value = props.packages.filter((p) => p.status === 4);
};
watch(() => props.packages, initializePackages, { immediate: true });

const onDragChange = async (event) => {
    if (!event.added || !event.added.element) return;
    const { added } = event;
    if (!added || !added.element) return;

    const packageId =
        added.element.id ?? added.element.package_id ?? added.element._id;

    let newStatus = null;
    const packageItem = added.element;

    if (actionRequiredPackages.value.some((p) => p.id === packageItem.id)) {
        newStatus = 1;
    } else if (inReviewPackages.value.some((p) => p.id === packageItem.id)) {
        newStatus = 2;
    } else if (readyToSendPackages.value.some((p) => p.id === packageItem.id)) {
        newStatus = 3;
    } else if (consolidatePackages.value.some((p) => p.id === packageItem.id)) {
        newStatus = 4;
    }

    console.log("Package ID:", packageId, "→ New Status:", newStatus);

    if (!packageId || !newStatus) {
        toast.error("Could not identify package or status. Please try again.");
        initializePackages();
        return;
    }

    if (!props.canUpdate) {
        toast.error("You do not have permission to update package status.");
        initializePackages();
        return;
    }

    try {
        const result = await updatePackageStatus(packageId, newStatus);
        console.log("API response:", result);
        toast.success("Package status updated successfully!");
        emit("status-updated", { packageId, newStatus });
    } catch (error) {
        console.error("Error updating package status:", error);
        toast.error("Failed to update package status. Reverting changes...");
        initializePackages();
    }
};

const updatePackageStatus = async (packageId, newStatus) => {
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    if (!csrfToken) throw new Error("CSRF token not found");

    const response = await fetch(`/package/${packageId}/status`, {
        method: "PUT",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrfToken,
            Accept: "application/json",
        },
        body: JSON.stringify({ status: newStatus }),
    });

    if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `HTTP ${response.status}`);
    }
    return response.json();
};
</script>

<template>
    <div class="kanban-board">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            <!-- Action Required Column -->
            <div
                class="border border-red-200 rounded-lg kanban-column bg-red-50"
            >
                <div class="p-4 bg-red-100 rounded-t-lg column-header">
                    <h3 class="text-lg font-semibold text-red-800">
                        Action Required
                    </h3>
                    <span class="text-sm text-red-600">
                        {{ actionRequiredPackages.length }} packages
                    </span>
                </div>
                <div class="column-content p-2 min-h-[400px]">
                    <draggable
                        v-model="actionRequiredPackages"
                        :group="canUpdate ? 'packages' : { name: 'packages', pull: false, put: false }"
                        item-key="id"
                        handle=".drag-handle"
                        @change="onDragChange"
                        class="space-y-2"
                        :data-status="1"
                        :disabled="!canUpdate"
                    >
                        <template #item="{ element: packageItem }">
                            <PackageCard
                                :packageData="packageItem"
                                :status="1"
                            />
                        </template>
                    </draggable>
                </div>
            </div>

            <!-- In Review Column -->
            <div
                class="border border-yellow-200 rounded-lg kanban-column bg-yellow-50"
            >
                <div class="p-4 bg-yellow-100 rounded-t-lg column-header">
                    <h3 class="text-lg font-semibold text-yellow-800">
                        In Review
                    </h3>
                    <span class="text-sm text-yellow-600">
                        {{ inReviewPackages.length }} packages
                    </span>
                </div>
                <div class="column-content p-2 min-h-[400px]">
                    <draggable
                        v-model="inReviewPackages"
                        :group="canUpdate ? 'packages' : { name: 'packages', pull: false, put: false }"
                        item-key="id"
                        handle=".drag-handle"
                        @change="onDragChange"
                        class="space-y-2"
                        :data-status="2"
                        :disabled="!canUpdate"
                    >
                        <template #item="{ element: packageItem }">
                            <PackageCard
                                :packageData="packageItem"
                                :status="2"
                            />
                        </template>
                    </draggable>
                </div>
            </div>

            <!-- Ready to Send Column -->
            <div
                class="border border-blue-200 rounded-lg kanban-column bg-blue-50"
            >
                <div class="p-4 bg-blue-100 rounded-t-lg column-header">
                    <h3 class="text-lg font-semibold text-blue-800">
                        Ready to Send
                    </h3>
                    <span class="text-sm text-blue-600">
                        {{ readyToSendPackages.length }} packages
                    </span>
                </div>
                <div class="column-content p-2 min-h-[400px]">
                    <draggable
                        v-model="readyToSendPackages"
                        :group="canUpdate ? 'packages' : { name: 'packages', pull: false, put: false }"
                        item-key="id"
                        handle=".drag-handle"
                        @change="onDragChange"
                        class="space-y-2"
                        :data-status="3"
                        :disabled="!canUpdate"
                    >
                        <template #item="{ element: packageItem }">
                            <PackageCard
                                :packageData="packageItem"
                                :status="3"
                            />
                        </template>
                    </draggable>
                </div>
            </div>

            <!-- Consolidate Column -->
            <!-- <div
                class="border border-green-200 rounded-lg kanban-column bg-green-50"
            >
                <div class="p-4 bg-green-100 rounded-t-lg column-header">
                    <h3 class="text-lg font-semibold text-green-800">
                        Consolidate
                    </h3>
                    <span class="text-sm text-green-600">
                        {{ consolidatePackages.length }} packages
                    </span>
                </div>
                <div class="column-content p-2 min-h-[400px]">
                    <draggable
                        v-model="consolidatePackages"
                        group="packages"
                        item-key="id"
                        handle=".drag-handle"
                        @change="onDragChange"
                        class="space-y-2"
                        :data-status="4"
                    >
                        <template #item="{ element: packageItem }">
                            <PackageCard
                                :packageData="packageItem"
                                :status="4"
                            />
                        </template>
                    </draggable>
                </div>
            </div> -->
        </div>
    </div>
</template>
