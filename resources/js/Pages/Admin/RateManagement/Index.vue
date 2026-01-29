<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import axios from "axios";

const props = defineProps({
    rules: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    carriers: {
        type: Array,
        default: () => ['fedex', 'dhl', 'ups'],
    },
});

// Modal state
const showModal = ref(false);
const editingRule = ref(null);
const showSimulator = ref(false);

// Form for create/edit
const form = useForm({
    name: '',
    description: '',
    type: 'percentage',
    value: '',
    carrier: '',
    service_code: '',
    min_weight: '',
    max_weight: '',
    destination_country: '',
    is_active: true,
    priority: 0,
});

// Simulator form
const simulatorForm = ref({
    carrier: 'fedex',
    weight: 10,
    base_price: 50,
    destination_country: '',
});
const simulatorResult = ref(null);
const simulatorLoading = ref(false);

// Open create modal
const openCreateModal = () => {
    editingRule.value = null;
    form.reset();
    form.is_active = true;
    form.type = 'percentage';
    form.priority = 0;
    showModal.value = true;
};

// Open edit modal
const openEditModal = (rule) => {
    editingRule.value = rule;
    form.name = rule.name;
    form.description = rule.description || '';
    form.type = rule.type;
    form.value = rule.value;
    form.carrier = rule.carrier || '';
    form.service_code = rule.service_code || '';
    form.min_weight = rule.min_weight || '';
    form.max_weight = rule.max_weight || '';
    form.destination_country = rule.destination_country || '';
    form.is_active = rule.is_active;
    form.priority = rule.priority;
    showModal.value = true;
};

// Submit form
const submitForm = () => {
    if (editingRule.value) {
        form.put(route('admin.rate-management.update', editingRule.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
        });
    } else {
        form.post(route('admin.rate-management.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
};

// Toggle rule
const toggleRule = (rule) => {
    router.post(route('admin.rate-management.toggle', rule.id), {}, {
        preserveScroll: true,
    });
};

// Delete rule
const deleteRule = (rule) => {
    if (confirm(`Delete rule "${rule.name}"?`)) {
        router.delete(route('admin.rate-management.destroy', rule.id), {
            preserveScroll: true,
        });
    }
};

// Run simulator
const runSimulator = async () => {
    simulatorLoading.value = true;
    try {
        const response = await axios.post(route('admin.rate-management.simulate'), simulatorForm.value);
        simulatorResult.value = response.data;
    } catch (error) {
        console.error('Simulation failed:', error);
    } finally {
        simulatorLoading.value = false;
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Rate Management" />

        <div class="py-8 bg-gray-50/50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                            <i class="fa-solid fa-percent text-primary-500"></i>
                            Rate Management
                        </h1>
                        <p class="text-sm text-gray-500 mt-1">
                            Configure markup rules applied to carrier rates
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <button
                            @click="showSimulator = !showSimulator"
                            class="btn btn-outline btn-sm"
                        >
                            <i class="fa-solid fa-calculator mr-2"></i>
                            Rate Simulator
                        </button>
                        <button
                            @click="openCreateModal"
                            class="btn btn-primary btn-sm"
                        >
                            <i class="fa-solid fa-plus mr-2"></i>
                            Add Rule
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white rounded-lg border p-4 shadow-sm">
                        <p class="text-sm text-gray-500">Total Rules</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats.total_rules }}</p>
                    </div>
                    <div class="bg-white rounded-lg border p-4 shadow-sm">
                        <p class="text-sm text-gray-500">Active Rules</p>
                        <p class="text-2xl font-bold text-green-600">{{ stats.active_rules }}</p>
                    </div>
                    <div class="bg-white rounded-lg border p-4 shadow-sm">
                        <p class="text-sm text-gray-500">Carriers Configured</p>
                        <p class="text-2xl font-bold text-blue-600">{{ stats.carriers_covered }}</p>
                    </div>
                </div>

                <!-- Simulator Panel -->
                <div v-if="showSimulator" class="bg-white rounded-lg border shadow-sm mb-6 p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">
                        <i class="fa-solid fa-flask text-purple-500 mr-2"></i>
                        Rate Simulator
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <label class="label text-sm">Carrier</label>
                            <select v-model="simulatorForm.carrier" class="select select-bordered w-full">
                                <option v-for="c in carriers" :key="c" :value="c">
                                    {{ c.toUpperCase() }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="label text-sm">Weight (lbs)</label>
                            <input
                                v-model.number="simulatorForm.weight"
                                type="number"
                                step="0.1"
                                min="0.1"
                                class="input input-bordered w-full"
                            />
                        </div>
                        <div>
                            <label class="label text-sm">Base Price ($)</label>
                            <input
                                v-model.number="simulatorForm.base_price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="input input-bordered w-full"
                            />
                        </div>
                        <div>
                            <label class="label text-sm">Destination Country</label>
                            <input
                                v-model="simulatorForm.destination_country"
                                type="text"
                                maxlength="2"
                                placeholder="e.g., US"
                                class="input input-bordered w-full uppercase"
                            />
                        </div>
                    </div>
                    <button
                        @click="runSimulator"
                        :disabled="simulatorLoading"
                        class="btn btn-primary btn-sm"
                    >
                        <i v-if="simulatorLoading" class="fa-solid fa-spinner fa-spin mr-2"></i>
                        <i v-else class="fa-solid fa-play mr-2"></i>
                        Simulate
                    </button>

                    <!-- Simulator Results -->
                    <div v-if="simulatorResult" class="mt-4 p-4 bg-gray-50 rounded-lg">
                        <div class="grid grid-cols-3 gap-4 text-center mb-4">
                            <div>
                                <p class="text-sm text-gray-500">Base Price</p>
                                <p class="text-xl font-bold text-gray-700">${{ simulatorResult.base_price }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Total Markup</p>
                                <p class="text-xl font-bold text-amber-600">+${{ simulatorResult.total_markup }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Final Price</p>
                                <p class="text-xl font-bold text-green-600">${{ simulatorResult.final_price }}</p>
                            </div>
                        </div>
                        <div v-if="simulatorResult.applied_rules.length > 0">
                            <p class="text-sm font-medium text-gray-600 mb-2">Applied Rules:</p>
                            <div class="space-y-1">
                                <div
                                    v-for="rule in simulatorResult.applied_rules"
                                    :key="rule.id"
                                    class="text-sm text-gray-600 flex justify-between"
                                >
                                    <span>{{ rule.name }}</span>
                                    <span class="text-amber-600">+${{ rule.markup_amount }}</span>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-sm text-gray-500 text-center">
                            No markup rules applied
                        </div>
                    </div>
                </div>

                <!-- Rules Table -->
                <div class="bg-white rounded-lg border shadow-sm overflow-hidden">
                    <table class="table w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th>Rule Name</th>
                                <th>Type</th>
                                <th>Value</th>
                                <th>Carrier</th>
                                <th>Weight Range</th>
                                <th>Destination</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="rule in rules" :key="rule.id" class="hover:bg-gray-50">
                                <td>
                                    <div class="font-medium">{{ rule.name }}</div>
                                    <div v-if="rule.description" class="text-xs text-gray-500">
                                        {{ rule.description }}
                                    </div>
                                </td>
                                <td>
                                    <span
                                        class="badge badge-sm"
                                        :class="rule.type === 'percentage' ? 'badge-primary' : 'badge-secondary'"
                                    >
                                        {{ rule.type_label }}
                                    </span>
                                </td>
                                <td class="font-medium">{{ rule.value_display }}</td>
                                <td>{{ rule.carrier_display }}</td>
                                <td class="text-sm text-gray-600">{{ rule.weight_range }}</td>
                                <td>{{ rule.destination_display }}</td>
                                <td>
                                    <span class="badge badge-ghost badge-sm">{{ rule.priority }}</span>
                                </td>
                                <td>
                                    <button
                                        @click="toggleRule(rule)"
                                        class="btn btn-xs"
                                        :class="rule.is_active ? 'btn-success' : 'btn-ghost'"
                                    >
                                        {{ rule.is_active ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>
                                <td>
                                    <div class="flex gap-1">
                                        <button
                                            @click="openEditModal(rule)"
                                            class="btn btn-ghost btn-xs"
                                        >
                                            <i class="fa-solid fa-pen"></i>
                                        </button>
                                        <button
                                            @click="deleteRule(rule)"
                                            class="btn btn-ghost btn-xs text-error"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="rules.length === 0">
                                <td colspan="9" class="text-center py-8 text-gray-500">
                                    <i class="fa-solid fa-inbox text-3xl mb-2"></i>
                                    <p>No markup rules configured yet</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <div v-if="showModal" class="modal modal-open">
            <div class="modal-box max-w-2xl">
                <h3 class="font-bold text-lg mb-4">
                    {{ editingRule ? 'Edit Markup Rule' : 'Create Markup Rule' }}
                </h3>

                <form @submit.prevent="submitForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="label">Rule Name *</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="input input-bordered w-full"
                                placeholder="e.g., Peak Season Surcharge"
                                required
                            />
                        </div>

                        <div class="col-span-2">
                            <label class="label">Description</label>
                            <textarea
                                v-model="form.description"
                                class="textarea textarea-bordered w-full"
                                rows="2"
                                placeholder="Optional description"
                            ></textarea>
                        </div>

                        <div>
                            <label class="label">Markup Type *</label>
                            <select v-model="form.type" class="select select-bordered w-full">
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount ($)</option>
                            </select>
                        </div>

                        <div>
                            <label class="label">Value *</label>
                            <input
                                v-model.number="form.value"
                                type="number"
                                step="0.01"
                                min="0"
                                class="input input-bordered w-full"
                                :placeholder="form.type === 'percentage' ? 'e.g., 10 for 10%' : 'e.g., 5.00'"
                                required
                            />
                        </div>

                        <div>
                            <label class="label">Carrier</label>
                            <select v-model="form.carrier" class="select select-bordered w-full">
                                <option value="">All Carriers</option>
                                <option v-for="c in carriers" :key="c" :value="c">
                                    {{ c.toUpperCase() }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="label">Service Code</label>
                            <input
                                v-model="form.service_code"
                                type="text"
                                class="input input-bordered w-full"
                                placeholder="Optional"
                            />
                        </div>

                        <div>
                            <label class="label">Min Weight (lbs)</label>
                            <input
                                v-model.number="form.min_weight"
                                type="number"
                                step="0.1"
                                min="0"
                                class="input input-bordered w-full"
                                placeholder="Optional"
                            />
                        </div>

                        <div>
                            <label class="label">Max Weight (lbs)</label>
                            <input
                                v-model.number="form.max_weight"
                                type="number"
                                step="0.1"
                                min="0"
                                class="input input-bordered w-full"
                                placeholder="Optional"
                            />
                        </div>

                        <div>
                            <label class="label">Destination Country</label>
                            <input
                                v-model="form.destination_country"
                                type="text"
                                maxlength="2"
                                class="input input-bordered w-full uppercase"
                                placeholder="e.g., US"
                            />
                        </div>

                        <div>
                            <label class="label">Priority</label>
                            <input
                                v-model.number="form.priority"
                                type="number"
                                min="0"
                                max="100"
                                class="input input-bordered w-full"
                            />
                            <p class="text-xs text-gray-500 mt-1">Higher = applied first</p>
                        </div>

                        <div class="col-span-2">
                            <label class="label cursor-pointer justify-start gap-3">
                                <input
                                    type="checkbox"
                                    v-model="form.is_active"
                                    class="checkbox checkbox-primary"
                                />
                                <span>Active</span>
                            </label>
                        </div>
                    </div>

                    <div class="modal-action">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="btn"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="btn btn-primary"
                        >
                            <i v-if="form.processing" class="fa-solid fa-spinner fa-spin mr-2"></i>
                            {{ editingRule ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
            <div class="modal-backdrop" @click="showModal = false"></div>
        </div>
    </AuthenticatedLayout>
</template>
