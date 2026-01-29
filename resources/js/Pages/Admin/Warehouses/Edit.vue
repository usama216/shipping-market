<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    warehouse: {
        type: Object,
        required: true
    }
});

const form = useForm({
    name: props.warehouse.name || '',
    code: props.warehouse.code || '',
    company_name: props.warehouse.company_name || '',
    full_name: props.warehouse.full_name || '',
    address: props.warehouse.address || '',
    address_line_2: props.warehouse.address_line_2 || '',
    city: props.warehouse.city || '',
    state: props.warehouse.state || '',
    zip: props.warehouse.zip || '',
    country: props.warehouse.country || 'United States',
    country_code: props.warehouse.country_code || 'US',
    phone_number: props.warehouse.phone_number || '',
    is_active: props.warehouse.is_active ?? true,
});

const currentStep = ref(1);
const totalSteps = 3;

const nextStep = () => {
    if (currentStep.value < totalSteps) {
        currentStep.value++;
    }
};

const prevStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const submit = () => {
    form.put(route('admin.warehouses.update', { warehouse: props.warehouse.id }), {
        onSuccess: () => {
            router.visit(route('admin.warehouses.index'));
        },
    });
};

// Common countries for dropdown
const countries = [
    { code: 'US', name: 'United States' },
    { code: 'GB', name: 'United Kingdom' },
    { code: 'CA', name: 'Canada' },
    { code: 'AU', name: 'Australia' },
    { code: 'DE', name: 'Germany' },
    { code: 'FR', name: 'France' },
    { code: 'JP', name: 'Japan' },
    { code: 'CN', name: 'China' },
];

const setCountry = (code) => {
    const country = countries.find(c => c.code === code);
    if (country) {
        form.country = country.name;
        form.country_code = country.code;
    }
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Edit Warehouse" />

        <div class="min-h-screen">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">
                    Edit Warehouse
                </h1>
                <p class="text-base-content/60 mt-1">
                    Update warehouse "{{ warehouse.name }}" ({{ warehouse.code }})
                </p>
                <div class="flex gap-2 mt-2" v-if="warehouse.is_default">
                    <span class="badge badge-primary">Default Warehouse</span>
                </div>
            </div>

            <!-- Progress Steps -->
            <div class="mb-8">
                <ul class="steps steps-horizontal w-full">
                    <li class="step" :class="{ 'step-primary': currentStep >= 1 }">
                        <span class="text-sm">Basic Info</span>
                    </li>
                    <li class="step" :class="{ 'step-primary': currentStep >= 2 }">
                        <span class="text-sm">Address</span>
                    </li>
                    <li class="step" :class="{ 'step-primary': currentStep >= 3 }">
                        <span class="text-sm">Review</span>
                    </li>
                </ul>
            </div>

            <!-- Form Card -->
            <div class="card bg-base-100 shadow-xl max-w-3xl mx-auto">
                <div class="card-body">
                    <form @submit.prevent="submit">
                        <!-- Step 1: Basic Info -->
                        <div v-show="currentStep === 1" class="space-y-6">
                            <h2 class="text-xl font-semibold mb-4">Basic Information</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="name" value="Warehouse Name *" />
                                    <TextInput
                                        id="name"
                                        v-model="form.name"
                                        class="w-full mt-1"
                                        placeholder="e.g., Miami Warehouse"
                                        required
                                    />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="code" value="Warehouse Code *" />
                                    <TextInput
                                        id="code"
                                        v-model="form.code"
                                        class="w-full mt-1 uppercase"
                                        placeholder="e.g., MIA"
                                        maxlength="10"
                                        required
                                    />
                                    <InputError :message="form.errors.code" class="mt-2" />
                                    <p class="text-xs text-base-content/50 mt-1">Short unique identifier (max 10 chars)</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="company_name" value="Company Name" />
                                    <TextInput
                                        id="company_name"
                                        v-model="form.company_name"
                                        class="w-full mt-1"
                                        placeholder="e.g., Marketz LLC"
                                    />
                                    <InputError :message="form.errors.company_name" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="full_name" value="Contact Name" />
                                    <TextInput
                                        id="full_name"
                                        v-model="form.full_name"
                                        class="w-full mt-1"
                                        placeholder="e.g., John Smith"
                                    />
                                    <InputError :message="form.errors.full_name" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="phone_number" value="Phone Number *" />
                                <TextInput
                                    id="phone_number"
                                    v-model="form.phone_number"
                                    type="tel"
                                    class="w-full mt-1"
                                    placeholder="e.g., +1 305 555 0123"
                                    required
                                />
                                <InputError :message="form.errors.phone_number" class="mt-2" />
                            </div>

                            <!-- Active Status (only if not default) -->
                            <div v-if="!warehouse.is_default" class="form-control">
                                <label class="label cursor-pointer justify-start gap-4">
                                    <input 
                                        type="checkbox" 
                                        v-model="form.is_active"
                                        class="checkbox checkbox-primary" 
                                    />
                                    <span class="label-text">Warehouse is active</span>
                                </label>
                                <p class="text-xs text-base-content/50 ml-10">Inactive warehouses cannot be assigned to new customers or packages</p>
                            </div>
                        </div>

                        <!-- Step 2: Address -->
                        <div v-show="currentStep === 2" class="space-y-6">
                            <h2 class="text-xl font-semibold mb-4">Address Details</h2>

                            <div>
                                <InputLabel for="address" value="Street Address *" />
                                <TextInput
                                    id="address"
                                    v-model="form.address"
                                    class="w-full mt-1"
                                    placeholder="e.g., 2900 NW 112th Ave"
                                    required
                                />
                                <InputError :message="form.errors.address" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="address_line_2" value="Address Line 2" />
                                <TextInput
                                    id="address_line_2"
                                    v-model="form.address_line_2"
                                    class="w-full mt-1"
                                    placeholder="e.g., Unit 2F, Suite 100"
                                />
                                <InputError :message="form.errors.address_line_2" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <InputLabel for="city" value="City *" />
                                    <TextInput
                                        id="city"
                                        v-model="form.city"
                                        class="w-full mt-1"
                                        placeholder="e.g., Doral"
                                        required
                                    />
                                    <InputError :message="form.errors.city" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="state" value="State/Province *" />
                                    <TextInput
                                        id="state"
                                        v-model="form.state"
                                        class="w-full mt-1"
                                        placeholder="e.g., Florida"
                                        required
                                    />
                                    <InputError :message="form.errors.state" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="zip" value="ZIP/Postal Code *" />
                                    <TextInput
                                        id="zip"
                                        v-model="form.zip"
                                        class="w-full mt-1"
                                        placeholder="e.g., 33172"
                                        required
                                    />
                                    <InputError :message="form.errors.zip" class="mt-2" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="country_code" value="Country *" />
                                <select
                                    id="country_code"
                                    v-model="form.country_code"
                                    @change="setCountry(form.country_code)"
                                    class="select select-bordered w-full mt-1"
                                    required
                                >
                                    <option v-for="country in countries" :key="country.code" :value="country.code">
                                        {{ country.name }} ({{ country.code }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.country_code" class="mt-2" />
                            </div>
                        </div>

                        <!-- Step 3: Review -->
                        <div v-show="currentStep === 3" class="space-y-6">
                            <h2 class="text-xl font-semibold mb-4">Review Changes</h2>

                            <div class="bg-base-200 rounded-lg p-6 space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-sm text-base-content/60">Warehouse Name</span>
                                        <p class="font-semibold">{{ form.name || '-' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm text-base-content/60">Code</span>
                                        <p class="font-semibold font-mono">{{ form.code || '-' }}</p>
                                    </div>
                                </div>

                                <div class="divider my-2"></div>

                                <div>
                                    <span class="text-sm text-base-content/60">Full Address</span>
                                    <p class="font-semibold">
                                        {{ form.address }}<br>
                                        <span v-if="form.address_line_2">{{ form.address_line_2 }}<br></span>
                                        {{ form.city }}, {{ form.state }} {{ form.zip }}<br>
                                        {{ form.country }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-sm text-base-content/60">Company</span>
                                        <p class="font-semibold">{{ form.company_name || '-' }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm text-base-content/60">Phone</span>
                                        <p class="font-semibold">{{ form.phone_number || '-' }}</p>
                                    </div>
                                </div>

                                <div class="flex gap-4 pt-2">
                                    <span 
                                        v-if="warehouse.is_default"
                                        class="badge badge-primary"
                                    >Default Warehouse</span>
                                    <span 
                                        :class="form.is_active ? 'badge-success' : 'badge-error'"
                                        class="badge"
                                    >{{ form.is_active ? 'Active' : 'Inactive' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation Buttons -->
                        <div class="flex justify-between mt-8 pt-6 border-t border-base-200">
                            <button 
                                type="button"
                                @click="prevStep"
                                :disabled="currentStep === 1"
                                class="btn btn-ghost"
                                :class="{ 'invisible': currentStep === 1 }"
                            >
                                ← Previous
                            </button>

                            <div class="flex gap-2">
                                <button 
                                    type="button"
                                    @click="router.visit(route('admin.warehouses.index'))"
                                    class="btn btn-ghost"
                                >
                                    Cancel
                                </button>

                                <button 
                                    v-if="currentStep < totalSteps"
                                    type="button"
                                    @click="nextStep"
                                    class="btn btn-primary"
                                >
                                    Next →
                                </button>

                                <button 
                                    v-else
                                    type="submit"
                                    class="btn btn-primary"
                                    :disabled="form.processing"
                                >
                                    <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
