<script setup>
import { computed } from "vue";

const emit = defineEmits(["update:modelValue"]);

const props = defineProps({
    modelValue: {
        required: true,
    },
    value: {
        required: true,
    },
    name: {
        type: String,
        default: "",
    },
});

const proxyValue = computed({
    get() {
        return props.modelValue;
    },
    set(val) {
        emit("update:modelValue", val);
    },
});
</script>

<template>
    <input
        type="radio"
        :name="name"
        :value="value"
        v-model="proxyValue"
        class="text-primary-600 focus:ring-primary-500 border-gray-300 rounded-full"
    />
</template>
