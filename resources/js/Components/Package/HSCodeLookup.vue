<template>
    <Modal :show="show" @close="closeModal" max-width="2xl">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Commodity Code Lookup</h2>
                <button
                    @click="closeModal"
                    class="text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <!-- Instructions -->
            <p class="text-sm text-gray-600 mb-6">
                You can verify the code is valid and is the one you want to use for your item.
            </p>

            <!-- Search Form -->
            <div class="space-y-4 mb-6">
                <!-- What is the item? (Required) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        What is the item? <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input
                            v-model="form.item_description"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                            placeholder="e.g., Electronics device, Laptop, Smartphone"
                            @keyup.enter="search"
                        />
                        <div v-if="form.item_description && !form.item_description.trim()" class="absolute right-3 top-2.5">
                            <span class="text-red-500 text-xs">Required</span>
                        </div>
                    </div>
                </div>

                <!-- What is it made of? (Optional) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        What is it made of? <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <input
                        v-model="form.material"
                        type="text"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        placeholder="e.g., Plastic, Metal, Leather"
                    />
                </div>

                <!-- How will it be used? (Optional) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        How will it be used? <span class="text-gray-400 text-xs">(Optional)</span>
                    </label>
                    <input
                        v-model="form.usage"
                        type="text"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                        placeholder="e.g., Personal use, Business, Consumer electronics"
                    />
                </div>
            </div>

            <!-- Search Button -->
            <div class="flex justify-end mb-6">
                <button
                    @click="search"
                    :disabled="!form.item_description || isLoading"
                    class="px-6 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <i class="fa-solid fa-magnifying-glass"></i>
                    Search
                </button>
            </div>

            <!-- Loading State -->
            <div v-if="isLoading" class="text-center py-8">
                <i class="fa-solid fa-spinner fa-spin text-3xl text-primary-500 mb-2"></i>
                <p class="text-gray-600">Searching for HS codes...</p>
            </div>

            <!-- Error State -->
            <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <p class="text-red-800 text-sm">{{ error }}</p>
            </div>

            <!-- Results -->
            <div v-if="results.length > 0" class="space-y-3">
                <h3 class="font-semibold text-gray-900 mb-3">
                    Found {{ results.length }} result(s)
                </h3>
                <div class="max-h-96 overflow-y-auto space-y-2">
                    <div
                        v-for="(result, index) in results"
                        :key="index"
                        @click="selectCode(result)"
                        class="p-4 border border-gray-200 rounded-lg hover:border-primary-500 hover:bg-primary-50 cursor-pointer transition-colors"
                    >
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    <span class="font-bold text-primary-600 text-lg">{{ result.hs_code }}</span>
                                    <span class="text-xs px-2 py-1 bg-gray-100 rounded">{{ result.source || 'database' }}</span>
                                    <span v-if="result.confidence_score" class="text-xs px-2 py-1 bg-green-100 text-green-800 rounded">
                                        {{ Math.round(result.confidence_score * 100) }}% match
                                    </span>
                                </div>
                                <p class="font-medium text-gray-900">{{ result.description }}</p>
                                <p v-if="result.full_description" class="text-sm text-gray-600 mt-1">
                                    {{ result.full_description }}
                                </p>
                                <p v-if="result.category" class="text-xs text-gray-500 mt-1">
                                    Category: {{ result.category }}
                                </p>
                                <div v-if="result.matched_keywords && result.matched_keywords.length > 0" class="mt-2">
                                    <span class="text-xs text-gray-500">Matched: </span>
                                    <span
                                        v-for="(keyword, idx) in result.matched_keywords"
                                        :key="idx"
                                        class="text-xs px-2 py-0.5 bg-blue-100 text-blue-800 rounded mr-1"
                                    >
                                        {{ keyword }}
                                    </span>
                                </div>
                            </div>
                            <button
                                @click.stop="selectCode(result)"
                                class="ml-4 px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg text-sm font-medium transition-colors"
                            >
                                Select
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Results -->
            <div v-if="!isLoading && !error && searched && results.length === 0" class="text-center py-8">
                <i class="fa-solid fa-search text-3xl text-gray-400 mb-2"></i>
                <p class="text-gray-600">No HS codes found. Try different keywords or search terms.</p>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref, reactive } from 'vue';
import Modal from '@/Components/Modal.vue';
import axios from 'axios';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close', 'select']);

const form = reactive({
    item_description: '',
    material: '',
    usage: '',
});

const isLoading = ref(false);
const error = ref(null);
const results = ref([]);
const searched = ref(false);

const search = async () => {
    if (!form.item_description.trim()) {
        error.value = 'Please enter an item description';
        return;
    }

    isLoading.value = true;
    error.value = null;
    results.value = [];
    searched.value = false;

    try {
        const response = await axios.post('/api/hs-codes/lookup', {
            item_description: form.item_description,
            material: form.material || null,
            usage: form.usage || null,
        });

        if (response.data.success) {
            // API now returns 'suggestions' instead of 'results'
            results.value = response.data.suggestions || response.data.results || [];
            searched.value = true;
            
            if (results.value.length === 0) {
                toast.info('No HS codes found. Try different search terms.');
            } else {
                toast.success(`Found ${results.value.length} HS code suggestion(s)`);
            }
        } else {
            error.value = 'Failed to search HS codes';
        }
    } catch (e) {
        error.value = e.response?.data?.message || 'An error occurred while searching for HS codes';
        toast.error(error.value);
    } finally {
        isLoading.value = false;
    }
};

const selectCode = (result) => {
    emit('select', {
        hs_code: result.hs_code,
        description: result.description,
    });
    closeModal();
};

const closeModal = () => {
    // Reset form
    form.item_description = '';
    form.material = '';
    form.usage = '';
    results.value = [];
    error.value = null;
    searched.value = false;
    emit('close');
};
</script>
