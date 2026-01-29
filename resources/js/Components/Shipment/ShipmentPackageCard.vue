<script setup>
const props = defineProps({
    package: Object,
    formatCurrency: Function,
    getFileUrl: Function,
});
</script>

<template>
    <div class="p-4 bg-white border rounded">
        <!-- Package Header -->
        <div class="flex items-start justify-between mb-2">
            <div>
                <p class="font-semibold">Package ID: {{ package?.package_id }}</p>
                <p class="text-sm text-gray-600">
                    Tracking ID: {{ package?.tracking_id || "N/A" }}
                </p>
                <p class="text-sm text-gray-600">From: {{ package?.from }}</p>
                <p class="text-sm text-gray-600">Weight: {{ package?.total_weight }} kg</p>
                <p class="text-sm text-gray-600">
                    Value: {{ formatCurrency(package?.total_value) }}
                </p>
            </div>
        </div>

        <!-- Package Files -->
        <div v-if="package?.files?.length > 0" class="mt-3">
            <strong class="text-sm">Documents/Photos:</strong>
            <div class="flex flex-wrap gap-2 mt-2">
                <a
                    v-for="file in package?.files"
                    :key="file.id"
                    :href="file.file_with_url || getFileUrl(file.file)"
                    target="_blank"
                    class="text-sm text-blue-600 underline hover:text-blue-800"
                >
                    <i class="mr-1 fas fa-file"></i>
                    {{ file.name }}
                </a>
            </div>
        </div>

        <!-- Package Items with Files -->
        <div v-if="package?.items?.length > 0" class="mt-3">
            <strong class="text-sm">Items:</strong>
            <div class="pl-4 mt-2 space-y-2">
                <div
                    v-for="item in package?.items"
                    :key="item.id"
                    class="pl-3 border-l-2 border-gray-300"
                >
                    <p class="font-medium">{{ item.title }}</p>
                    <p v-if="item.description" class="text-sm text-gray-600">
                        {{ item.description }}
                    </p>
                    <p class="text-xs text-gray-500">
                        Qty: {{ item.quantity }} | Value:
                        {{ formatCurrency(item.total_line_value) }}
                    </p>
                    <!-- Item Files -->
                    <div v-if="item?.packageFiles?.length > 0" class="mt-1">
                        <span class="text-xs text-gray-500">Files:</span>
                        <div class="flex flex-wrap gap-1 mt-1">
                            <a
                                v-for="file in item?.packageFiles"
                                :key="file.id"
                                :href="file.file_with_url || getFileUrl(file.file)"
                                target="_blank"
                                class="text-xs text-blue-600 underline hover:text-blue-800"
                            >
                                {{ file.name }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Package Invoices -->
        <div v-if="package?.invoices?.length > 0" class="mt-3">
            <strong class="text-sm">Invoices:</strong>
            <div class="flex flex-wrap gap-2 mt-2">
                <a
                    v-for="invoice in package?.invoices"
                    :key="invoice.id"
                    :href="invoice.image_with_url || getFileUrl(invoice.image)"
                    target="_blank"
                    class="text-sm text-blue-600 underline hover:text-blue-800"
                >
                    <i class="mr-1 fas fa-file-invoice"></i>
                    Invoice #{{ invoice.id }}
                </a>
            </div>
        </div>
    </div>
</template>
