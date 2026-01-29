<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    show: Boolean,
    packageData: Object,
});

const emit = defineEmits(["close"]);

const close = () => {
    emit("close");
};

const formatCurrency = (value) => {
    if (!value) return "-";
    return `$${parseFloat(value).toFixed(2)}`;
};

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const statusName = computed(() => {
    if (!props.packageData) return "";
    const names = {
        1: "Action Required",
        2: "In Review",
        3: "Ready to Send",
        4: "Consolidated",
    };
    return names[props.packageData.status] || "Unknown";
});

// Open barcode file - fetches URL from API and opens in new tab (for PDF)
// For ZPL, directly downloads the file
const openBarcodeFile = async (url) => {
    // Check if it's a ZPL file - if so, download directly
    if (url.includes('/barcode/zpl')) {
        // Create a temporary link and trigger download
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        return;
    }
    
    // For PDF files, fetch JSON and open URL
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.url) {
            window.open(data.url, '_blank');
        } else {
            alert(data.message || 'Failed to generate barcode file');
        }
    } catch (error) {
        console.error('Error fetching barcode file:', error);
        alert('Failed to generate barcode file. Please try again.');
    }
};

const statusColorClass = computed(() => {
    if (!props.packageData) return "";
    switch (props.packageData.status) {
        case 1:
            return "bg-red-100 text-red-800";
        case 2:
            return "bg-yellow-100 text-yellow-800";
        case 3:
            return "bg-blue-100 text-blue-800";
        case 4:
            return "bg-green-100 text-green-800";
        default:
            return "bg-gray-100 text-gray-800";
    }
});
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
    >
        <div
            class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0"
        >
            <div
                class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                aria-hidden="true"
                @click="close"
            ></div>

            <span
                class="hidden sm:inline-block sm:align-middle sm:h-screen"
                aria-hidden="true"
                >&#8203;</span
            >

            <div
                class="inline-block px-4 pt-5 pb-4 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6"
            >
                <!-- Close Button -->
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button
                        type="button"
                        class="text-gray-400 bg-white rounded-md hover:text-gray-500 focus:outline-none"
                        @click="close"
                    >
                        <span class="sr-only">Close</span>
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div v-if="packageData">
                    <div class="sm:flex sm:items-start">
                        <div
                            class="flex items-center justify-center flex-shrink-0 w-12 h-12 mx-auto bg-blue-100 rounded-full sm:mx-0 sm:h-10 sm:w-10"
                        >
                            <i class="fas fa-box text-blue-600"></i>
                        </div>
                        <div
                            class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full"
                        >
                            <h3
                                class="text-lg font-medium leading-6 text-gray-900"
                                id="modal-title"
                            >
                                Package Details
                                <span class="ml-2 font-mono text-gray-500"
                                    >#{{ packageData.package_id }}</span
                                >
                            </h3>

                            <div class="mt-2 text-sm text-gray-500">
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <span class="block text-xs font-semibold uppercase text-gray-400">Customer</span>
                                        <span class="font-medium text-gray-900">{{ packageData.customer?.name || 'N/A' }}</span>
                                        <span class="ml-1 text-xs bg-gray-100 px-1.5 py-0.5 rounded">{{ packageData.customer?.suite || 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-semibold uppercase text-gray-400">Status</span>
                                        <span :class="['px-2 py-0.5 rounded text-xs font-semibold', statusColorClass]">
                                            {{ statusName }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-semibold uppercase text-gray-400">Received Date</span>
                                        <span class="text-gray-900">{{ formatDate(packageData.date_received || packageData.created_at) }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-semibold uppercase text-gray-400">Total Value</span>
                                        <span class="text-green-600 font-medium">{{ formatCurrency(packageData.total_value) }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-semibold uppercase text-gray-400">Tracking</span>
                                        <span class="text-gray-900 font-mono">{{ packageData.tracking_id || '---' }}</span>
                                    </div>
                                    <div>
                                        <span class="block text-xs font-semibold uppercase text-gray-400">From</span>
                                        <span class="text-gray-900">{{ packageData.from || '---' }}</span>
                                    </div>
                                </div>

                                <!-- Items Section -->
                                <div class="mt-4 border-t pt-3">
                                    <h4 class="font-medium text-gray-900 mb-2 flex items-center">
                                        <i class="fas fa-boxes mr-2 text-gray-400"></i> Items ({{ packageData.items ? packageData.items.length : 0 }})
                                    </h4>
                                    <div v-if="packageData.items && packageData.items.length > 0" class="bg-gray-50 rounded-lg p-3 max-h-40 overflow-y-auto">
                                        <ul class="space-y-2">
                                            <li v-for="item in packageData.items" :key="item.id" class="text-sm border-b border-gray-100 last:border-0 pb-1 last:pb-0">
                                                <div class="flex justify-between">
                                                    <span class="font-medium text-gray-700">{{ item.description || 'No description' }}</span>
                                                    <span class="text-gray-500">Qty: {{ item.quantity || 1 }}</span>
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    Val: {{ formatCurrency(item.unit_value) }} | W: {{ item.total_line_weight }} {{ item.weight_unit || 'lb' }}
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <p v-else class="text-center text-gray-400 py-2">No items recorded.</p>
                                </div>

                                <!-- Note Section -->
                                <div v-if="packageData.note" class="mt-4 border-t pt-3">
                                    <h4 class="font-medium text-gray-900 mb-2">Note</h4>
                                    <div class="bg-yellow-50 text-yellow-800 p-3 rounded-md text-sm">
                                        {{ packageData.note }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barcode Section -->
                <div v-if="packageData && packageData.package_id" class="mt-4 border-t pt-4">
                    <h4 class="font-medium text-gray-900 mb-3 flex items-center">
                        <i class="fas fa-barcode mr-2 text-gray-400"></i> Package Barcode
                    </h4>
                    <!-- Barcode Preview -->
                    <div class="mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200 text-center">
                        <img 
                            :src="route('admin.packages.barcode.image', packageData.id)" 
                            :alt="`Barcode for ${packageData.package_id}`"
                            class="mx-auto max-w-full h-16 object-contain mb-2"
                        />
                        <p class="text-xs font-mono text-gray-600">{{ packageData.package_id }}</p>
                    </div>
                    <!-- Download Buttons -->
                    <div class="flex gap-2 flex-wrap">
                        <a
                            :href="route('admin.packages.barcode.pdf', packageData.id)"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700 cursor-pointer"
                            @click.prevent="openBarcodeFile(route('admin.packages.barcode.pdf', packageData.id))"
                        >
                            <i class="fas fa-file-pdf mr-1.5"></i>
                            Download PDF
                        </a>
                        <a
                            :href="route('admin.packages.barcode.zpl', packageData.id)"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200 cursor-pointer"
                            @click.prevent="openBarcodeFile(route('admin.packages.barcode.zpl', packageData.id))"
                        >
                            <i class="fas fa-print mr-1.5"></i>
                            Download ZPL
                        </a>
                        <a
                            :href="route('admin.packages.barcode.view', packageData.id)"
                            @click.prevent="openBarcodeFile(route('admin.packages.barcode.view', packageData.id))"
                            class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200 cursor-pointer"
                        >
                            <i class="fas fa-eye mr-1.5"></i>
                            View PDF
                        </a>
                    </div>
                </div>

                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                    <Link
                        v-if="packageData && packageData.id"
                        :href="route('admin.packages.edit', packageData.id)"
                        class="inline-flex justify-center w-full px-4 py-2 text-base font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        Edit Full Details
                    </Link>
                    <button
                        type="button"
                        class="inline-flex justify-center w-full px-4 py-2 mt-3 text-base font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                        @click="close"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
