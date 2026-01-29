<template>
    <div class="text-center py-12">
        <div class="text-gray-500">
            <i :class="icon" class="text-6xl mb-4 block"></i>
            <p class="text-xl font-medium text-gray-700 mb-2">{{ title }}</p>
            <p class="text-sm text-gray-500">{{ message }}</p>
            <div v-if="showAction" class="mt-4">
                <slot name="action">
                    <button
                        @click="$emit('action')"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        {{ actionText }}
                    </button>
                </slot>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    icon: {
        type: String,
        default: "fas fa-box-open",
    },
    title: {
        type: String,
        default: "No Results Found",
    },
    message: {
        type: String,
        default: "Try adjusting your filters or search criteria.",
    },
    showAction: {
        type: Boolean,
        default: false,
    },
    actionText: {
        type: String,
        default: "Clear Filters",
    },
});

defineEmits(["action"]);
</script>
