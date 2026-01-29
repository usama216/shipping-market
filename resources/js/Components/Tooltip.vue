<template>
    <div class="relative flex items-center group">
        <slot />
        <div
            class="absolute bottom-full mb-1 w-max max-w-xs text-sm text-white bg-gray-800 rounded px-2 py-1 opacity-0 group-hover:opacity-100 transition-opacity z-10"
        >
            {{ text }}
        </div>
    </div>
</template>

<script setup>
defineProps({
    text: {
        type: String,
        required: true,
    },
});
</script>
