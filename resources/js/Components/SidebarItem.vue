<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    icon: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        required: true,
    },
    isCollapsed: {
        type: Boolean,
        default: false,
    },
});

const classes = computed(() => {
    return props.active
        ? "bg-primary-500/10 text-white border-r-[3px] border-primary-500"
        : "text-slate-400 hover:bg-slate-800 hover:text-slate-100 border-r-[3px] border-transparent";
});

const iconClasses = computed(() => {
    return props.active ? "text-primary-500" : "text-slate-400 group-hover:text-slate-100";
});
</script>

<template>
    <Link
        :href="href"
        class="group flex items-center px-4 py-3 transition-all duration-200 ease-in-out mb-1"
        :class="classes"
        :title="isCollapsed ? label : ''"
    >
        <div class="flex items-center justify-center min-w-[24px]">
            <i :class="[icon, 'text-lg transition-colors duration-200', iconClasses]"></i>
        </div>
        
        <span 
            class="ml-3 font-medium text-sm whitespace-nowrap transition-all duration-300 origin-left"
            :class="{ 'opacity-0 w-0 overflow-hidden': isCollapsed, 'opacity-100 w-auto': !isCollapsed }"
        >
            {{ label }}
        </span>
    </Link>
</template>
