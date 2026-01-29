<script setup>
import { ref, computed, watch } from "vue";
import Checkbox from "@/Components/Checkbox.vue";

const props = defineProps({
    packingOptions: {
        type: Array,
        default: () => [],
    },
    shippingPreferenceOptions: {
        type: Array,
        default: () => [],
    },
    selectedPackingOptions: {
        type: Array,
        default: () => [],
    },
    selectedShippingPreferences: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits([
    "update:selectedPackingOptions",
    "update:selectedShippingPreferences",
    "optionsChanged",
]);

// Local state
const localPackingOptions = ref([...props.selectedPackingOptions]);
const localShippingPreferences = ref([...props.selectedShippingPreferences]);

// Computed totals
const packingTotal = computed(() => {
    return props.packingOptions
        .filter(opt => localPackingOptions.value.includes(opt.id))
        .reduce((sum, opt) => sum + Number(opt.price || 0), 0);
});

const shippingPreferencesTotal = computed(() => {
    return props.shippingPreferenceOptions
        .filter(opt => localShippingPreferences.value.includes(opt.id))
        .reduce((sum, opt) => sum + Number(opt.price || 0), 0);
});

const totalAdditionalCost = computed(() => {
    return packingTotal.value + shippingPreferencesTotal.value;
});

// Watch for changes and emit
watch(localPackingOptions, (newVal) => {
    emit("update:selectedPackingOptions", newVal);
    emitChange();
}, { deep: true });

watch(localShippingPreferences, (newVal) => {
    emit("update:selectedShippingPreferences", newVal);
    emitChange();
}, { deep: true });

const emitChange = () => {
    emit("optionsChanged", {
        packingOptions: localPackingOptions.value,
        shippingPreferences: localShippingPreferences.value,
        packingTotal: packingTotal.value,
        shippingPreferencesTotal: shippingPreferencesTotal.value,
        totalCost: totalAdditionalCost.value,
    });
};

const togglePackingOption = (id) => {
    const index = localPackingOptions.value.indexOf(id);
    if (index > -1) {
        localPackingOptions.value.splice(index, 1);
    } else {
        localPackingOptions.value.push(id);
    }
};

const toggleShippingPreference = (id) => {
    const index = localShippingPreferences.value.indexOf(id);
    if (index > -1) {
        localShippingPreferences.value.splice(index, 1);
    } else {
        localShippingPreferences.value.push(id);
    }
};

const formatCurrency = (amount) => `$${Number(amount || 0).toFixed(2)}`;
</script>

<template>
    <div class="space-y-6">
        <!-- Packing Options -->
        <div v-if="packingOptions.length > 0">
            <div class="flex items-center justify-between mb-3">
                <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <i class="fa-solid fa-box text-primary-500"></i>
                    Packing Options
                </h4>
                <span v-if="packingTotal > 0" class="text-sm font-medium text-primary-600">
                    +{{ formatCurrency(packingTotal) }}
                </span>
            </div>

            <div class="space-y-2">
                <label
                    v-for="option in packingOptions"
                    :key="option.id"
                    :for="`packing_${option.id}`"
                    class="flex items-start gap-3 p-3 transition-colors border rounded-lg cursor-pointer hover:bg-gray-50"
                    :class="{
                        'border-primary-300 bg-primary-50': localPackingOptions.includes(option.id),
                        'border-gray-200': !localPackingOptions.includes(option.id)
                    }"
                >
                    <div class="flex items-center h-5 pt-0.5">
                        <input
                            type="checkbox"
                            :id="`packing_${option.id}`"
                            :checked="localPackingOptions.includes(option.id)"
                            @change="togglePackingOption(option.id)"
                            class="w-4 h-4 border-gray-300 rounded text-primary-600 focus:ring-primary-500"
                        />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-900">{{ option.title }}</span>
                            <span class="text-sm font-medium text-gray-600">
                                +{{ formatCurrency(option.price) }}
                            </span>
                        </div>
                        <p v-if="option.description" class="mt-1 text-xs text-gray-500">
                            {{ option.description }}
                        </p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Shipping Preferences -->
        <div v-if="shippingPreferenceOptions.length > 0">
            <div class="flex items-center justify-between mb-3">
                <h4 class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <i class="fa-solid fa-sliders text-primary-500"></i>
                    Shipping Preferences
                </h4>
                <span v-if="shippingPreferencesTotal > 0" class="text-sm font-medium text-primary-600">
                    +{{ formatCurrency(shippingPreferencesTotal) }}
                </span>
            </div>

            <div class="space-y-2">
                <label
                    v-for="option in shippingPreferenceOptions"
                    :key="option.id"
                    :for="`preference_${option.id}`"
                    class="flex items-start gap-3 p-3 transition-colors border rounded-lg cursor-pointer hover:bg-gray-50"
                    :class="{
                        'border-primary-300 bg-primary-50': localShippingPreferences.includes(option.id),
                        'border-gray-200': !localShippingPreferences.includes(option.id)
                    }"
                >
                    <div class="flex items-center h-5 pt-0.5">
                        <input
                            type="checkbox"
                            :id="`preference_${option.id}`"
                            :checked="localShippingPreferences.includes(option.id)"
                            @change="toggleShippingPreference(option.id)"
                            class="w-4 h-4 border-gray-300 rounded text-primary-600 focus:ring-primary-500"
                        />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-gray-900">{{ option.title }}</span>
                            <span class="text-sm font-medium text-gray-600">
                                <template v-if="option.price">+{{ formatCurrency(option.price) }}</template>
                                <template v-else>Free</template>
                            </span>
                        </div>
                        <p v-if="option.description" class="mt-1 text-xs text-gray-500">
                            {{ option.description }}
                        </p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Total Additional Cost -->
        <div 
            v-if="totalAdditionalCost > 0" 
            class="flex items-center justify-between p-3 rounded-lg bg-gray-50"
        >
            <span class="text-sm font-medium text-gray-700">Additional Services Total</span>
            <span class="font-semibold text-primary-600">+{{ formatCurrency(totalAdditionalCost) }}</span>
        </div>
    </div>
</template>
