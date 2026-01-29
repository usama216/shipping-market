<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    href: String,
    icon: String,
    label: String,
    isCollapsed: Boolean,
    active: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="flex items-center space-x-3 px-3 py-2 rounded-md hover:bg-primary-700 transition-all"
        :class="{ 'text-white': active }"
    >
        <i :class="[icon, 'text-lg']"></i>
        <span v-if="!isCollapsed" class="text-sm">{{ label }}</span>
    </Link>
</template>
