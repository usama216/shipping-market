<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    icon: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    isCollapsed: {
        type: Boolean,
        default: false,
    },
});

const isOpen = ref(props.active);

// Auto-open if active prop changes to true
watch(() => props.active, (newVal) => {
    if (newVal) isOpen.value = true;
});

const toggle = () => {
    if (props.isCollapsed) return; // Don't toggle in collapsed mode, potentially show hover menu instead (future enhancement)
    isOpen.value = !isOpen.value;
};

const headerClasses = computed(() => {
    return props.active || isOpen.value
        ? "text-slate-100"
        : "text-slate-400 hover:bg-slate-800 hover:text-slate-100";
});

const iconClasses = computed(() => {
    return props.active || isOpen.value ? "text-primary-500" : "text-slate-400 group-hover:text-slate-100";
});
</script>

<template>
    <div class="mb-1">
        <button
            @click="toggle"
            class="w-full group flex items-center justify-between px-4 py-3 transition-all duration-200 ease-in-out border-r-[3px] border-transparent"
            :class="headerClasses"
            :title="isCollapsed ? label : ''"
        >
            <div class="flex items-center overflow-hidden">
                <div class="flex items-center justify-center min-w-[24px]">
                    <i :class="[icon, 'text-lg transition-colors duration-200', iconClasses]"></i>
                </div>
                <span 
                    class="ml-3 font-medium text-sm whitespace-nowrap transition-all duration-300 origin-left"
                    :class="{ 'opacity-0 w-0 overflow-hidden': isCollapsed, 'opacity-100 w-auto': !isCollapsed }"
                >
                    {{ label }}
                </span>
            </div>
            
            <i 
                v-if="!isCollapsed"
                class="fa-solid fa-chevron-right text-xs transition-transform duration-200"
                :class="{ 'rotate-90': isOpen, 'text-primary-500': active, 'text-slate-500': !active }"
            ></i>
        </button>
        
        <div 
            v-if="isOpen && !isCollapsed"
            class="bg-slate-900/50 overflow-hidden transition-all duration-300"
        >
            <slot />
        </div>
    </div>
</template>
