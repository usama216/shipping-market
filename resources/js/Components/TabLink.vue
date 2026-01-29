<script setup>
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    label: String,
    href: String,
    count: Number,
    color: {
        type: String,
        default: "slate",
    },
    active: {
        type: Boolean,
        default: false,
    },
});
const tabColors = {
    green: {
        base: "bg-gray-100 text-green-800 border-t-green-500",
        badge: "bg-green-600 text-white",
    },
    yellow: {
        base: "bg-gray-100 text-yellow-800 border-t-yellow-500",
        badge: "bg-yellow-500 text-white",
    },
    red: {
        base: "bg-gray-100 text-red-800 border-t-red-500",
        badge: "bg-red-600 text-white",
    },
    slate: {
        base: "bg-gray-100 text-slate-800 border-t-slate-500",
        badge: "bg-slate-600 text-white",
    },
};

const color = tabColors[props.color] || tabColors.slate;
</script>

<template>
    <Link
        :href="href"
        class="flex items-center px-4 py-2 rounded-t border border-t-4 transition"
        :class="[color.base, active ? 'bg-white' : '']"
    >
        <span class="font-semibold mr-2">{{ label }}</span>
        <span class="text-xs px-2 py-0.5 rounded-full" :class="color.badge">
            {{ count }}
        </span>
    </Link>
</template>
