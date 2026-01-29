<template>
    <div
        v-if="hasActiveFilters"
        class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4"
    >
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-filter text-blue-600 mr-2"></i>
                <span class="text-sm font-medium text-blue-800"
                    >Active Filters:</span
                >
            </div>
            <button
                @click="$emit('clear')"
                class="text-sm text-blue-600 hover:text-blue-800 flex items-center"
            >
                <i class="fas fa-times mr-1"></i>
                Clear All
            </button>
        </div>
        <div class="flex flex-wrap gap-2 mt-2">
            <span
                v-for="(value, key) in activeFilters"
                :key="key"
                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
            >
                {{ getFilterLabel(key, value) }}
                <button
                    @click="$emit('remove', key)"
                    class="ml-1 text-blue-600 hover:text-blue-800"
                >
                    <i class="fas fa-times"></i>
                </button>
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const emit = defineEmits(["clear", "remove"]);

const hasActiveFilters = computed(() => {
    return Object.values(props.filters).some((value) => value !== "");
});

const activeFilters = computed(() => {
    const active = {};
    Object.entries(props.filters).forEach(([key, value]) => {
        if (value !== "") {
            active[key] = value;
        }
    });
    return active;
});

const getFilterLabel = (key, value) => {
    const labels = {
        status: `Status: ${getStatusName(value)}`,
        date_from: `From: ${formatDate(value)}`,
        date_to: `To: ${formatDate(value)}`,
        customer_id: `Customer: ${value}`,
        tracking_id: `Tracking: ${value}`,
        total_value_min: `Min: $${value}`,
        total_value_max: `Max: $${value}`,
    };
    return labels[key] || `${key}: ${value}`;
};

const getStatusName = (status) => {
    const statusNames = {
        1: "Action Required",
        2: "In Review",
        3: "Ready to Send",
        4: "Consolidated",
    };
    return statusNames[status] || status;
};

const formatDate = (dateString) => {
    if (!dateString) return "";
    return new Date(dateString).toLocaleDateString();
};
</script>
