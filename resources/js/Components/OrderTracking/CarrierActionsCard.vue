<script setup>
import { ref } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import { usePermissions } from "@/Composables/usePermissions";

const { can } = usePermissions();

const props = defineProps({
    ship: Object,
});

// Retry submission
const isRetrying = ref(false);
const retrySubmission = () => {
    isRetrying.value = true;
    router.post(
        route("admin.shipments.retryCarrier", { ship: props.ship.id }),
        {},
        {
            preserveScroll: true,
            onFinish: () => (isRetrying.value = false),
        }
    );
};

// Manual tracking modal
const showManualModal = ref(false);
const manualForm = useForm({
    carrier_name: "fedex",
    carrier_tracking_number: "",
    carrier_service_type: "",
    label_file: null,
    total_charge: "",
    notes: "",
});

const submitManualTracking = () => {
    manualForm.post(
        route("admin.shipments.manualTracking", { ship: props.ship.id }),
        {
            preserveScroll: true,
            onSuccess: () => {
                showManualModal.value = false;
                manualForm.reset();
            },
        }
    );
};

// Sync from carrier
const showSyncModal = ref(false);
const syncForm = useForm({
    tracking_number: "",
});

const syncFromCarrier = () => {
    syncForm.post(
        route("admin.shipments.syncCarrier", { ship: props.ship.id }),
        {
            preserveScroll: true,
            onSuccess: () => {
                showSyncModal.value = false;
                syncForm.reset();
            },
        }
    );
};

// Check if carrier actions should be shown
const showCarrierActions =
    !props.ship.carrier_status ||
    props.ship.carrier_status === "failed" ||
    props.ship.carrier_status === "pending";

const getStatusColor = (status) => {
    const colors = {
        failed: "text-error",
        pending: "text-warning",
        submitted: "text-success",
    };
    return colors[status] || "text-gray-500";
};

// Technical error details toggle
const showTechnicalDetails = ref(false);
</script>

<template>
    <div
        v-if="showCarrierActions || ship.carrier_errors"
        class="card bg-white shadow-md border-l-4"
        :class="ship.carrier_status === 'failed' ? 'border-error' : 'border-warning'"
    >
        <div class="card-body">
            <h2 class="card-title flex items-center gap-2">
                <i v-if="ship.carrier_status === 'failed'" class="fa-solid fa-triangle-exclamation text-error"></i>
                <i v-else class="fa-solid fa-rotate text-warning"></i>
                Carrier Submission
                <span class="badge" :class="getStatusColor(ship.carrier_status)">
                    {{ ship.carrier_status || "Not Submitted" }}
                </span>
            </h2>

            <!-- Error Display -->
            <div v-if="ship.carrier_errors" class="mt-3">
                <!-- Friendly Error Message -->
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle shrink-0"></i>
                    <span>{{ ship.carrier_errors?.friendly_message || ship.carrier_errors?.message || "Unknown error" }}</span>
                </div>

                <!-- Technical Details Toggle -->
                <div v-if="ship.carrier_errors?.raw_message" class="mt-2">
                    <button
                        @click="showTechnicalDetails = !showTechnicalDetails"
                        class="btn btn-xs btn-ghost text-gray-500"
                    >
                        <i :class="showTechnicalDetails ? 'fas fa-chevron-up' : 'fas fa-chevron-down'" class="mr-1"></i>
                        {{ showTechnicalDetails ? 'Hide' : 'Show' }} Technical Details
                    </button>
                    <div v-if="showTechnicalDetails" class="mt-2 p-3 bg-base-200 rounded-lg text-xs font-mono overflow-x-auto">
                        <p class="text-gray-600 whitespace-pre-wrap break-all">{{ ship.carrier_errors.raw_message }}</p>
                        <p v-if="ship.carrier_errors.error_category" class="mt-2 text-gray-500">
                            Category: {{ ship.carrier_errors.error_category }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-wrap gap-2 mt-4">
                <button
                    v-if="can('order-tracking.carrier.retry')"
                    @click="retrySubmission"
                    :disabled="isRetrying"
                    class="btn btn-outline btn-sm"
                    :class="{ loading: isRetrying }"
                >
                    <i v-if="!isRetrying" class="fas fa-redo mr-1"></i>
                    Retry API Submission
                </button>

                <button
                    v-if="can('order-tracking.carrier.manual')"
                    @click="showManualModal = true"
                    class="btn btn-primary btn-sm"
                >
                    <i class="fas fa-edit mr-1"></i>
                    Enter Tracking Manually
                </button>

                <button
                    v-if="can('order-tracking.carrier.sync') && ship.carrier_tracking_number"
                    @click="showSyncModal = true"
                    class="btn btn-outline btn-sm"
                >
                    <i class="fas fa-sync mr-1"></i>
                    Sync from Carrier
                </button>
            </div>
        </div>
    </div>

    <!-- Manual Tracking Modal -->
    <div v-if="showManualModal" class="modal modal-open">
        <div class="modal-box max-w-lg">
            <h3 class="font-bold text-lg">Enter Tracking Manually</h3>
            <p class="text-sm text-gray-500 mt-1">
                Submit shipment details after creating it directly on the carrier website.
            </p>

            <form @submit.prevent="submitManualTracking" class="space-y-4 mt-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Carrier *</label>
                        <select v-model="manualForm.carrier_name" class="select select-bordered w-full" required>
                            <option value="fedex">FedEx</option>
                            <option value="dhl">DHL Express</option>
                            <option value="ups">UPS</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Service Type</label>
                        <input
                            v-model="manualForm.carrier_service_type"
                            type="text"
                            class="input input-bordered w-full"
                            placeholder="e.g., Express, Ground"
                        />
                    </div>
                </div>

                <div>
                    <label class="label">Tracking Number *</label>
                    <input
                        v-model="manualForm.carrier_tracking_number"
                        type="text"
                        class="input input-bordered w-full"
                        placeholder="Enter carrier tracking number"
                        required
                    />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Total Charge</label>
                        <input
                            v-model="manualForm.total_charge"
                            type="number"
                            step="0.01"
                            min="0"
                            class="input input-bordered w-full"
                            placeholder="0.00"
                        />
                    </div>
                    <div>
                        <label class="label">Label File</label>
                        <input
                            type="file"
                            @change="(e) => (manualForm.label_file = e.target.files[0])"
                            class="file-input file-input-bordered w-full"
                            accept=".pdf,.png,.jpg,.jpeg"
                        />
                    </div>
                </div>

                <div>
                    <label class="label">Notes</label>
                    <textarea
                        v-model="manualForm.notes"
                        class="textarea textarea-bordered w-full"
                        rows="2"
                        placeholder="Optional notes..."
                    ></textarea>
                </div>

                <div v-if="manualForm.errors" class="text-error text-sm">
                    {{ Object.values(manualForm.errors).flat().join(", ") }}
                </div>

                <div class="modal-action">
                    <button type="button" @click="showManualModal = false" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="manualForm.processing">
                        Save Tracking
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" @click="showManualModal = false"></div>
    </div>

    <!-- Sync from Carrier Modal -->
    <div v-if="showSyncModal" class="modal modal-open">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Sync from Carrier</h3>
            <p class="text-sm text-gray-500 mt-1">
                Fetch tracking details from the carrier API.
            </p>

            <form @submit.prevent="syncFromCarrier" class="space-y-4 mt-4">
                <div>
                    <label class="label">Tracking Number</label>
                    <input
                        v-model="syncForm.tracking_number"
                        type="text"
                        class="input input-bordered w-full"
                        :placeholder="ship.carrier_tracking_number || 'Enter tracking number'"
                    />
                    <p class="text-xs text-gray-400 mt-1">
                        Leave empty to use existing: {{ ship.carrier_tracking_number || "N/A" }}
                    </p>
                </div>

                <div class="modal-action">
                    <button type="button" @click="showSyncModal = false" class="btn">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="syncForm.processing">
                        Sync Tracking
                    </button>
                </div>
            </form>
        </div>
        <div class="modal-backdrop" @click="showSyncModal = false"></div>
    </div>
</template>
