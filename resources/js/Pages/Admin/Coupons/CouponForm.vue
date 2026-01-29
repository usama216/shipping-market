<template>
    <form @submit.prevent="submit" class="space-y-6">
        <!-- Identity Section -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
            <h3 class="text-sm font-medium text-gray-900 mb-4">Identity</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="col-span-1">
                    <InputLabel for="code" value="Code" />
                    <div class="flex gap-2">
                        <TextInput
                            id="code"
                            v-model="form.code"
                            type="text"
                            class="mt-1 block w-full uppercase"
                            required
                            autofocus
                            placeholder="SUMMER2025"
                        />
                        <SecondaryButton type="button" @click="generateCode" class="mt-1" :disabled="processing">
                            <ArrowPathIcon v-if="generating" class="w-4 h-4 animate-spin" />
                            <span v-else>Generate</span>
                        </SecondaryButton>
                    </div>
                    <InputError :message="form.errors.code" class="mt-2" />
                </div>
                
                <div class="col-span-1">
                    <InputLabel value="Status" />
                    <div class="flex items-center gap-4 mt-3">
                         <label class="flex items-center">
                            <Checkbox v-model="form.is_active" name="is_active" />
                            <span class="ml-2 text-sm text-gray-600">Active</span>
                        </label>
                        <label class="flex items-center">
                            <Checkbox v-model="form.is_private" name="is_private" />
                            <span class="ml-2 text-sm text-gray-600">Private (Hidden)</span>
                        </label>
                    </div>
                </div>

                <div class="col-span-2">
                    <InputLabel for="description" value="Description" />
                    <TextInput
                        id="description"
                        v-model="form.description"
                        type="text"
                        class="mt-1 block w-full"
                        placeholder="Summer sale discount 20%"
                    />
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>
            </div>
        </div>

        <!-- Rules & Value -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
             <h3 class="text-sm font-medium text-gray-900 mb-4">Value & Rules</h3>
             <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <InputLabel for="discount_type" value="Type" />
                    <select
                        id="discount_type"
                        v-model="form.discount_type"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount ($)</option>
                    </select>
                </div>

                <div>
                    <InputLabel for="discount_value" value="Value" />
                    <TextInput
                        id="discount_value"
                        v-model="form.discount_value"
                        type="number"
                        step="0.01"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError :message="form.errors.discount_value" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="per_customer_limit" value="Per Customer Limit" />
                    <TextInput
                        id="per_customer_limit"
                        v-model="form.per_customer_limit"
                        type="number"
                        class="mt-1 block w-full"
                        placeholder="1 (default)"
                    />
                    <p class="mt-1 text-xs text-gray-500">Max uses per customer (empty = 1)</p>
                </div>
             </div>
        </div>

        <!-- Automation & Timing -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
            <h3 class="text-sm font-medium text-gray-900 mb-4">Automation & Timing</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                     <InputLabel for="target_audience" value="Target Audience" />
                    <select
                        id="target_audience"
                        v-model="form.target_audience"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option value="all">All Customers</option>
                        <option value="new_customer">New Customers Only</option>
                        <option value="registration">On Registration</option>
                        <option value="certain_customers">Certain Customers Only</option>
                    </select>
                </div>

                <!-- Selected Customers Dropdown (shown when certain_customers is selected) -->
                <div v-if="form.target_audience === 'certain_customers'" class="col-span-2">
                    <InputLabel for="selected_customers" value="Select Customers" />
                    <SearchableSelect
                        id="selected_customers"
                        v-model="form.selected_customer_ids"
                        :options="customers"
                        :multiple="true"
                        :closeOnSelect="false"
                        label="suite"
                        :reduce="(option) => option.id"
                        class="mt-1 block w-full"
                        placeholder="Search by name, suite #, or email..."
                        :filter-by="(option, label, search) => {
                            const query = search.toLowerCase();
                            return (
                                (option.name && option.name.toLowerCase().includes(query)) ||
                                (option.email && option.email.toLowerCase().includes(query)) ||
                                (option.suite && String(option.suite).toLowerCase().includes(query))
                            );
                        }"
                    >
                        <template #option="{ suite, name, email }">
                            <div class="py-1">
                                <span class="font-bold text-gray-800">#{{ suite }}</span> 
                                <span class="mx-2 text-gray-300">|</span>
                                <span class="font-medium text-gray-700">{{ name }}</span>
                                <span class="ml-2 text-xs text-gray-500">({{ email }})</span>
                            </div>
                        </template>
                        <template #selected-option="{ suite, name }">
                            <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded mr-2">#{{ suite }}</span>
                            <span class="text-sm">{{ name }}</span>
                        </template>
                    </SearchableSelect>
                    <p class="mt-1 text-xs text-gray-500">Only selected customers will be able to use this coupon</p>
                    <InputError :message="form.errors.selected_customer_ids" class="mt-2" />
                </div>

                <div class="flex items-center mt-6">
                    <label class="flex items-center">
                        <Checkbox v-model="form.auto_apply" name="auto_apply" />
                        <span class="ml-2 text-sm text-gray-600">Auto-Apply at Checkout</span>
                    </label>
                </div>

                <div>
                    <InputLabel for="start_date" value="Start Date" />
                    <TextInput
                        id="start_date"
                        v-model="form.start_date"
                        type="date"
                        class="mt-1 block w-full"
                    />
                    <InputError :message="form.errors.start_date" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="expiry_date" value="Expiry Date" />
                    <TextInput
                        id="expiry_date"
                        v-model="form.expiry_date"
                        type="date"
                        class="mt-1 block w-full"
                    />
                     <InputError :message="form.errors.expiry_date" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-4 border-t pt-4">
            <SecondaryButton @click="$emit('cancel')" type="button">Cancel</SecondaryButton>
            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                {{ isEditing ? 'Update Coupon' : 'Create Coupon' }}
            </PrimaryButton>
        </div>
    </form>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { ArrowPathIcon } from '@heroicons/vue/24/outline';
import SearchableSelect from 'vue-select';
import axios from 'axios';

const props = defineProps({
    coupon: {
        type: Object,
        default: null
    },
    customers: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['cancel', 'success']);

const isEditing = computed(() => !!props.coupon);
const generating = ref(false);

const form = useForm({
    code: props.coupon?.code || '',
    discount_type: props.coupon?.discount_type || 'percentage',
    discount_value: props.coupon?.discount_value || '',
    per_customer_limit: props.coupon?.per_customer_limit || '',
    start_date: props.coupon?.start_date ? props.coupon.start_date.split('T')[0] : '', // Format for date input
    expiry_date: props.coupon?.expiry_date ? props.coupon.expiry_date.split('T')[0] : '',
    is_active: props.coupon?.is_active ?? true,
    auto_apply: props.coupon?.auto_apply ?? false,
    is_private: props.coupon?.is_private ?? false,
    target_audience: props.coupon?.target_audience || 'all',
    description: props.coupon?.description || '',
    selected_customer_ids: props.coupon?.selected_customer_ids || []
});

const generateCode = async () => {
    generating.value = true;
    try {
        const response = await axios.post(route('admin.coupons.generate-code'));
        if (response.data.success) {
            form.code = response.data.code;
        }
    } catch (error) {
        console.error('Failed to generate code:', error);
    } finally {
        generating.value = false;
    }
};

const submit = () => {
    if (isEditing.value) {
        form.put(route('admin.coupons.update', props.coupon.id), {
            onSuccess: () => emit('success'),
        });
    } else {
        form.post(route('admin.coupons.store'), {
             onSuccess: () => emit('success'),
        });
    }
};
</script>
