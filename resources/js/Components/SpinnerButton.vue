<script setup>
const props = defineProps({
    isLoading: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <button
        class="inline-flex items-center rounded-md border border-transparent bg-primary-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-primary-700 focus:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-primary-900 disabled:bg-primary-500"
        :disabled="isLoading"
    >
        <span class="loading loading-spinner" v-if="isLoading"></span>
        <slot></slot>
    </button>
</template>
