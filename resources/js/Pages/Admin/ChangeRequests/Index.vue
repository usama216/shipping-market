<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Pagination from "@/Components/Pagination.vue";
import { useToast } from "vue-toastification";
import axios from "axios";
import { usePermissions } from "@/Composables/usePermissions";

const toast = useToast();
const { can } = usePermissions();

const props = defineProps({
    requests: Object,
    counts: Object,
    currentStatus: String,
});

// State
const selectedRequest = ref(null);
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const adminNotes = ref("");
const isProcessing = ref(false);
const selectedIds = ref([]);

// Status tab navigation
const statusTabs = [
    { key: "pending", label: "Pending", color: "yellow" },
    { key: "approved", label: "Approved", color: "green" },
    { key: "rejected", label: "Rejected", color: "red" },
    { key: "all", label: "All", color: "gray" },
];

const changeTab = (status) => {
    router.get(route("admin.change-requests.index"), { status }, {
        preserveState: true,
        replace: true,
    });
};

// Actions
const openApproveModal = (request) => {
    selectedRequest.value = request;
    adminNotes.value = "";
    showApproveModal.value = true;
};

const openRejectModal = (request) => {
    selectedRequest.value = request;
    adminNotes.value = "";
    showRejectModal.value = true;
};

const closeModals = () => {
    showApproveModal.value = false;
    showRejectModal.value = false;
    selectedRequest.value = null;
    adminNotes.value = "";
};

const approveRequest = async () => {
    if (!selectedRequest.value) return;
    
    isProcessing.value = true;
    try {
        await axios.post(route("admin.change-requests.approve", selectedRequest.value.id), {
            admin_notes: adminNotes.value,
        });
        
        toast.success("Change request approved successfully!");
        closeModals();
        router.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to approve request");
    } finally {
        isProcessing.value = false;
    }
};

const rejectRequest = async () => {
    if (!selectedRequest.value || !adminNotes.value.trim()) {
        toast.error("Please provide a reason for rejection");
        return;
    }
    
    isProcessing.value = true;
    try {
        await axios.post(route("admin.change-requests.reject", selectedRequest.value.id), {
            admin_notes: adminNotes.value,
        });
        
        toast.success("Change request rejected");
        closeModals();
        router.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to reject request");
    } finally {
        isProcessing.value = false;
    }
};

// Helpers
const getStatusBadgeClass = (status) => {
    switch (status) {
        case "pending": return "bg-yellow-100 text-yellow-800";
        case "approved": return "bg-green-100 text-green-800";
        case "rejected": return "bg-red-100 text-red-800";
        default: return "bg-gray-100 text-gray-800";
    }
};

const formatDate = (date) => {
    if (!date) return "-";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const formatChanges = (changes) => {
    if (!changes) return [];
    return Object.entries(changes)
        .filter(([key, value]) => value !== null && key !== "notes")
        .map(([key, value]) => ({ key, value }));
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Package Change Requests" />

        <div class="py-6">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-6">
                    <h1 class="flex items-center gap-3 text-2xl font-bold text-gray-900">
                        <i class="fa-solid fa-clipboard-check text-primary-500"></i>
                        Package Change Requests
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Review and manage customer requests to modify package details
                    </p>
                </div>

                <!-- Status Tabs -->
                <div class="mb-6 border-b border-gray-200">
                    <nav class="flex -mb-px space-x-8">
                        <button
                            v-for="tab in statusTabs"
                            :key="tab.key"
                            @click="changeTab(tab.key)"
                            :class="[
                                'py-4 px-1 border-b-2 font-medium text-sm whitespace-nowrap',
                                currentStatus === tab.key
                                    ? 'border-primary-500 text-primary-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                            ]"
                        >
                            {{ tab.label }}
                            <span
                                v-if="counts[tab.key]"
                                :class="[
                                    'ml-2 px-2 py-0.5 rounded-full text-xs font-medium',
                                    tab.key === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                    tab.key === 'approved' ? 'bg-green-100 text-green-800' :
                                    tab.key === 'rejected' ? 'bg-red-100 text-red-800' :
                                    'bg-gray-100 text-gray-800'
                                ]"
                            >
                                {{ counts[tab.key] }}
                            </span>
                        </button>
                    </nav>
                </div>

                <!-- Requests List -->
                <div class="overflow-hidden bg-white border rounded-lg shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Package
                                </th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Customer
                                </th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Requested Changes
                                </th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Customer Notes
                                </th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Submitted
                                </th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-xs font-medium tracking-wider text-right text-gray-500 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="request in requests.data" :key="request.id" class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ request.package?.package_id || 'N/A' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ request.package?.from || 'Unknown' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ request.user?.name || 'N/A' }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ request.user?.email || '' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm">
                                        <div
                                            v-for="change in formatChanges(request.requested_changes)"
                                            :key="change.key"
                                            class="mb-1"
                                        >
                                            <span class="font-medium capitalize">{{ change.key }}:</span>
                                            <span class="ml-1">{{ change.value }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-600 max-w-xs truncate">
                                        {{ request.customer_notes || '-' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                    {{ formatDate(request.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2 py-1 text-xs font-medium rounded-full',
                                            getStatusBadgeClass(request.status)
                                        ]"
                                    >
                                        {{ request.status_label || request.status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-right whitespace-nowrap">
                                                <div v-if="request.status === 'pending' && can('change-requests.update')" class="flex justify-end gap-2">
                                                    <button
                                                        @click="openApproveModal(request)"
                                                        class="px-3 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-md hover:bg-green-200"
                                                    >
                                                        <i class="mr-1 fa-solid fa-check"></i>
                                                        Approve
                                                    </button>
                                                    <button
                                                        @click="openRejectModal(request)"
                                                        class="px-3 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200"
                                                    >
                                                        <i class="mr-1 fa-solid fa-times"></i>
                                                        Reject
                                                    </button>
                                                </div>
                                                <div v-else-if="request.status !== 'pending'" class="text-xs text-gray-400">
                                        <span v-if="request.reviewed_at">
                                            Reviewed {{ formatDate(request.reviewed_at) }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!requests.data?.length">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="mb-3 text-4xl fa-solid fa-inbox"></i>
                                    <p>No change requests found</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="requests.data?.length" class="px-4 py-3 bg-gray-50">
                        <Pagination :links="requests.links" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <Modal :show="showApproveModal" @close="closeModals">
            <div class="p-6">
                <h2 class="flex items-center gap-2 mb-4 text-lg font-semibold text-gray-900">
                    <i class="text-green-500 fa-solid fa-check-circle"></i>
                    Approve Change Request
                </h2>

                <div class="p-4 mb-4 rounded-lg bg-green-50">
                    <p class="text-sm text-green-800">
                        <strong>Package:</strong> {{ selectedRequest?.package?.package_id || 'N/A' }}
                    </p>
                    <p class="mt-2 text-sm text-green-700">
                        Approving this request will apply the following changes to the package:
                    </p>
                    <ul class="mt-2 ml-4 text-sm list-disc">
                        <li
                            v-for="change in formatChanges(selectedRequest?.requested_changes)"
                            :key="change.key"
                        >
                            <strong class="capitalize">{{ change.key }}:</strong> {{ change.value }}
                        </li>
                    </ul>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 text-sm font-medium text-gray-700">
                        Notes (optional)
                    </label>
                    <textarea
                        v-model="adminNotes"
                        rows="3"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Add any notes for the customer..."
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="closeModals">Cancel</SecondaryButton>
                    <PrimaryButton
                        @click="approveRequest"
                        :disabled="isProcessing"
                        class="bg-green-600 hover:bg-green-700"
                    >
                        <span v-if="isProcessing">Processing...</span>
                        <span v-else>Approve & Apply Changes</span>
                    </PrimaryButton>
                </div>
            </div>
        </Modal>

        <!-- Reject Modal -->
        <Modal :show="showRejectModal" @close="closeModals">
            <div class="p-6">
                <h2 class="flex items-center gap-2 mb-4 text-lg font-semibold text-gray-900">
                    <i class="text-red-500 fa-solid fa-times-circle"></i>
                    Reject Change Request
                </h2>

                <div class="p-4 mb-4 rounded-lg bg-red-50">
                    <p class="text-sm text-red-800">
                        <strong>Package:</strong> {{ selectedRequest?.package?.package_id || 'N/A' }}
                    </p>
                    <p class="mt-2 text-sm text-red-700">
                        The following changes will NOT be applied:
                    </p>
                    <ul class="mt-2 ml-4 text-sm list-disc">
                        <li
                            v-for="change in formatChanges(selectedRequest?.requested_changes)"
                            :key="change.key"
                        >
                            <strong class="capitalize">{{ change.key }}:</strong> {{ change.value }}
                        </li>
                    </ul>
                </div>

                <div class="mb-4">
                    <label class="block mb-1 text-sm font-medium text-gray-700">
                        Reason for rejection <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="adminNotes"
                        rows="3"
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        placeholder="Explain why this request is being rejected..."
                        required
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="closeModals">Cancel</SecondaryButton>
                    <DangerButton
                        @click="rejectRequest"
                        :disabled="isProcessing || !adminNotes.trim()"
                    >
                        <span v-if="isProcessing">Processing...</span>
                        <span v-else>Reject Request</span>
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
