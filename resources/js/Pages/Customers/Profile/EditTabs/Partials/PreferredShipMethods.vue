<script setup>
import TextInput from "@/Components/TextInput.vue";

const props = defineProps({
    preferredShipMethods: Object,
});
</script>
<template>
    <div class="py-5">
        <div class="mb-2">
            <h1 class="text-xl">Preferred Ship Method</h1>
        </div>

        <div
            class=""
            v-for="shipMethod in props?.preferredShipMethods"
            :key="shipMethod.id"
        >
            <label :for="shipMethod.id">
                <input
                    type="radio"
                    class="border-gray-300 text-primary-600 shadow-sm focus:ring-primary-500"
                    :id="shipMethod.id"
                />
                <span class="px-2">{{ shipMethod?.title }}</span>
            </label>
        </div>
    </div>
</template>
