<script setup>
import SpinnerIcon from "@js/Components/Icons/SpinnerIcon.vue";
const props = defineProps({
    processing: {
        type: Boolean,
        default: false,
    },
    spinner: {
        type: Boolean,
        default: true,
    },
});
</script>
<template>
    <button
        class="inline-flex items-center rounded-md border border-transparent bg-primary-500 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition duration-150 ease-in-out hover:bg-primary-700 focus:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:bg-primary-900"
    >
        <SpinnerIcon v-show="props.spinner && props.processing" />
        <slot />
    </button>
</template>
