<script setup>
/**
 * DatePicker - Reusable date picker component
 * 
 * Usage:
 * <DatePicker v-model="myDate" label="Invoice Date" placeholder="Select date" />
 * 
 * Features:
 * - Consistent premium UI styling
 * - Outputs properly formatted date string (YYYY-MM-DD) for backend
 * - Auto-apply mode
 * - Calendar icon and clear button
 */
import { ref, computed, watch } from "vue";
import VueDatePicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

const props = defineProps({
    modelValue: {
        type: [String, Date, null],
        default: null,
    },
    label: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "Select date",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    required: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: "",
    },
    clearable: {
        type: Boolean,
        default: true,
    },
    minDate: {
        type: [String, Date],
        default: null,
    },
    maxDate: {
        type: [String, Date],
        default: null,
    },
    size: {
        type: String,
        default: "md", // sm, md, lg
    },
});

const emit = defineEmits(["update:modelValue"]);

// Internal date value
const internalDate = ref(props.modelValue ? new Date(props.modelValue) : null);

// Watch for external changes
watch(() => props.modelValue, (newVal) => {
    if (newVal) {
        internalDate.value = new Date(newVal);
    } else {
        internalDate.value = null;
    }
});

// Format date for backend (YYYY-MM-DD)
const formatDateForBackend = (date) => {
    if (!date) return null;
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

// Handle date change
const handleDateChange = (date) => {
    internalDate.value = date;
    emit("update:modelValue", formatDateForBackend(date));
};

// Size classes
const sizeClasses = computed(() => {
    switch (props.size) {
        case "sm":
            return "text-sm";
        case "lg":
            return "text-base";
        default:
            return "text-sm";
    }
});

// Input styling
const inputStyle = computed(() => ({
    "--dp-border-radius": "0.5rem",
    "--dp-cell-border-radius": "0.375rem",
    "--dp-font-family": "inherit",
    "--dp-primary-color": "#111827",
    "--dp-primary-text-color": "#ffffff",
    "--dp-secondary-color": "#e5e7eb",
    "--dp-border-color": "#d1d5db",
    "--dp-menu-border-color": "#e5e7eb",
    "--dp-background-color": "#ffffff",
    "--dp-text-color": "#111827",
    "--dp-hover-color": "#f3f4f6",
    "--dp-hover-text-color": "#111827",
    "--dp-disabled-color": "#9ca3af",
    "--dp-icon-color": "#6b7280",
    "--dp-success-color": "#10b981",
    "--dp-danger-color": "#ef4444",
}));
</script>

<template>
    <div class="date-picker-wrapper">
        <!-- Label -->
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ label }}
            <span v-if="required" class="text-red-500 ml-0.5">*</span>
        </label>
        
        <!-- Date Picker -->
        <VueDatePicker
            :model-value="internalDate"
            @update:model-value="handleDateChange"
            :enable-time-picker="false"
            :clearable="clearable"
            :disabled="disabled"
            :min-date="minDate"
            :max-date="maxDate"
            auto-apply
            :placeholder="placeholder"
            :style="inputStyle"
            :class="[
                'datepicker-custom',
                sizeClasses,
                { 'datepicker-error': error },
                { 'datepicker-disabled': disabled }
            ]"
            :input-class-name="[
                'w-full rounded-lg shadow-sm transition-colors focus:ring-2 focus:ring-gray-900/10 focus:outline-none',
                error ? 'border border-red-300 focus:border-red-500' : 'border border-gray-300 hover:border-gray-400 focus:border-gray-500',
                disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white',
                size === 'sm' ? 'py-1.5 px-3 text-sm' : size === 'lg' ? 'py-3 px-4 text-base' : 'py-2.5 px-3 text-sm'
            ].join(' ')"
            format="MMM dd, yyyy"
            preview-format="MMMM dd, yyyy"
        >
            <template #input-icon>
                <i class="fa-regular fa-calendar text-gray-400"></i>
            </template>
            <template #clear-icon="{ clear }">
                <i class="fa-solid fa-xmark text-gray-400 hover:text-gray-600 cursor-pointer" @click="clear"></i>
            </template>
        </VueDatePicker>
        
        <!-- Error Message -->
        <p v-if="error" class="mt-1 text-sm text-red-600">
            <i class="fa-solid fa-circle-exclamation mr-1"></i>{{ error }}
        </p>
    </div>
</template>

<style scoped>
.datepicker-custom :deep(.dp__input_wrap) {
    /* Better input wrapper styling */
}

.datepicker-custom :deep(.dp__input) {
    font-family: inherit;
    padding-left: 2.5rem !important;
    padding-right: 2rem !important;
}

.datepicker-custom :deep(.dp__input_icon) {
    left: 0.75rem;
}

.datepicker-custom :deep(.dp__clear_icon) {
    right: 0.75rem;
}

.datepicker-custom :deep(.dp__input:hover:not(.dp__disabled)) {
    border-color: #9ca3af;
}

.datepicker-custom :deep(.dp__input:focus) {
    border-color: #4b5563;
    box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.1);
}

.datepicker-error :deep(.dp__input) {
    border-color: #fca5a5;
}

.datepicker-error :deep(.dp__input:focus) {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.datepicker-disabled :deep(.dp__input) {
    background-color: #f3f4f6;
    cursor: not-allowed;
}

/* Calendar popup styling */
:deep(.dp__menu) {
    border-radius: 0.75rem;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    border: 1px solid #e5e7eb;
}

:deep(.dp__calendar_header_item) {
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    font-size: 0.75rem;
}

:deep(.dp__today) {
    border-color: #6b7280;
}

:deep(.dp__active_date) {
    background-color: #111827;
    color: #ffffff;
}

:deep(.dp__month_year_select) {
    font-weight: 600;
}

:deep(.dp__arrow_top),
:deep(.dp__arrow_bottom) {
    display: none;
}
</style>
