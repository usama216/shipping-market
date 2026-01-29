<script setup>
import { computed } from "vue";

const emit = defineEmits(["update:modelValue"]);

const props = defineProps({
    modelValue: {
        type: [Array, Boolean],
        required: true,
    },
    value: {
        default: null,
    },
});

const proxyChecked = computed({
    get() {
        if (Array.isArray(props.modelValue)) {
            return props.modelValue.includes(props.value);
        }
        return props.modelValue;
    },
    set(checked) {
        if (Array.isArray(props.modelValue)) {
            const newValue = [...props.modelValue];
            if (checked) {
                if (!newValue.includes(props.value)) newValue.push(props.value);
            } else {
                const index = newValue.indexOf(props.value);
                if (index > -1) newValue.splice(index, 1);
            }
            emit("update:modelValue", newValue);
        } else {
            emit("update:modelValue", checked);
        }
    },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
    />
</template>
