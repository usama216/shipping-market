<script setup>
import { onMounted, ref } from "vue";

const model = defineModel({
    type: [String, Number],
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute("autofocus")) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="rounded-md text-black border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 w-full"
        v-model="model"
        ref="input"
    />
</template>
