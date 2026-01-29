<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";

const props = defineProps({
    commission: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    dhl_commission_percentage: props.commission.dhl_commission_percentage,
    fedex_commission_percentage: props.commission.fedex_commission_percentage,
    ups_commission_percentage: props.commission.ups_commission_percentage,
    dangerous_goods_charge: props.commission.dangerous_goods_charge,
    fragile_item_charge: props.commission.fragile_item_charge,
    oversized_item_charge: props.commission.oversized_item_charge,
});

const submit = () => {
    form.put(route('admin.commission.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success message is handled by the controller redirect
        },
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Carrier Commission Settings" />
        
        <div class="min-h-screen">
            <!-- Header Section -->
            <div class="mb-8">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">
                        Carrier Commission Settings
                    </h1>
                    <p class="text-base-content/60 mt-1">
                        Configure commission percentages for each carrier and additional charges for classified items (Dangerous, Fragile, Oversized)
                    </p>
                </div>
            </div>

            <!-- Commission Settings Card -->
            <div class="card bg-base-100 shadow-xl">
                <div class="card-body">
                    <h2 class="card-title text-xl mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Commission Configuration
                    </h2>

                    <form @submit.prevent="submit">
                        <!-- DHL Commission -->
                        <div class="form-control w-full mb-6">
                            <label class="label">
                                <span class="label-text font-semibold flex items-center gap-2">
                                    <div class="avatar placeholder">
                                        <div class="bg-red-100 text-red-600 rounded w-8">
                                            <span class="text-sm font-bold">DHL</span>
                                        </div>
                                    </div>
                                    DHL Express Commission
                                </span>
                            </label>
                            <div class="input-group">
                                <input
                                    v-model="form.dhl_commission_percentage"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    placeholder="15.00"
                                    class="input input-bordered w-full"
                                    :class="{ 'input-error': form.errors.dhl_commission_percentage }"
                                    required
                                />
                                <span class="bg-base-200 px-4 flex items-center">%</span>
                            </div>
                            <label class="label" v-if="form.errors.dhl_commission_percentage">
                                <span class="label-text-alt text-error">{{ form.errors.dhl_commission_percentage }}</span>
                            </label>
                        </div>

                        <!-- FedEx Commission -->
                        <div class="form-control w-full mb-6">
                            <label class="label">
                                <span class="label-text font-semibold flex items-center gap-2">
                                    <div class="avatar placeholder">
                                        <div class="bg-blue-100 text-blue-600 rounded w-8">
                                            <span class="text-sm font-bold">FX</span>
                                        </div>
                                    </div>
                                    FedEx Commission
                                </span>
                            </label>
                            <div class="input-group">
                                <input
                                    v-model="form.fedex_commission_percentage"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    placeholder="15.00"
                                    class="input input-bordered w-full"
                                    :class="{ 'input-error': form.errors.fedex_commission_percentage }"
                                    required
                                />
                                <span class="bg-base-200 px-4 flex items-center">%</span>
                            </div>
                            <label class="label" v-if="form.errors.fedex_commission_percentage">
                                <span class="label-text-alt text-error">{{ form.errors.fedex_commission_percentage }}</span>
                            </label>
                        </div>

                        <!-- UPS Commission -->
                        <div class="form-control w-full mb-6">
                            <label class="label">
                                <span class="label-text font-semibold flex items-center gap-2">
                                    <div class="avatar placeholder">
                                        <div class="bg-yellow-100 text-yellow-600 rounded w-8">
                                            <span class="text-sm font-bold">UPS</span>
                                        </div>
                                    </div>
                                    UPS Commission
                                </span>
                            </label>
                            <div class="input-group">
                                <input
                                    v-model="form.ups_commission_percentage"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    placeholder="15.00"
                                    class="input input-bordered w-full"
                                    :class="{ 'input-error': form.errors.ups_commission_percentage }"
                                    required
                                />
                                <span class="bg-base-200 px-4 flex items-center">%</span>
                            </div>
                            <label class="label" v-if="form.errors.ups_commission_percentage">
                                <span class="label-text-alt text-error">{{ form.errors.ups_commission_percentage }}</span>
                            </label>
                        </div>

                        <!-- Classification Charges Section -->
                        <div class="divider my-8">
                            <h2 class="card-title text-xl">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Item Classification Charges
                            </h2>
                        </div>

                        <div class="alert alert-warning mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div>
                                <h3 class="font-bold">Additional Charges</h3>
                                <div class="text-sm">
                                    <p class="mt-1">
                                        These charges are applied per item that is marked as Dangerous, Fragile, or Oversized during shipment creation or checkout.
                                    </p>
                                    <p class="mt-2">
                                        For example, if you set Dangerous Goods charge to $5.00 and a shipment has 2 dangerous items, an additional $10.00 will be charged.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Dangerous Goods Charge -->
                        <div class="form-control w-full mb-6">
                            <label class="label">
                                <span class="label-text font-semibold flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center" title="Dangerous Goods">
                                        <i class="fa-solid fa-triangle-exclamation text-red-600 text-xs"></i>
                                    </span>
                                    Dangerous Goods Charge (per item)
                                </span>
                            </label>
                            <div class="input-group">
                                <span class="bg-base-200 px-4 flex items-center">$</span>
                                <input
                                    v-model="form.dangerous_goods_charge"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    class="input input-bordered w-full"
                                    :class="{ 'input-error': form.errors.dangerous_goods_charge }"
                                    required
                                />
                            </div>
                            <label class="label" v-if="form.errors.dangerous_goods_charge">
                                <span class="label-text-alt text-error">{{ form.errors.dangerous_goods_charge }}</span>
                            </label>
                        </div>

                        <!-- Fragile Item Charge -->
                        <div class="form-control w-full mb-6">
                            <label class="label">
                                <span class="label-text font-semibold flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center" title="Fragile">
                                        <i class="fa-solid fa-wine-glass text-amber-600 text-xs"></i>
                                    </span>
                                    Fragile Item Charge (per item)
                                </span>
                            </label>
                            <div class="input-group">
                                <span class="bg-base-200 px-4 flex items-center">$</span>
                                <input
                                    v-model="form.fragile_item_charge"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    class="input input-bordered w-full"
                                    :class="{ 'input-error': form.errors.fragile_item_charge }"
                                    required
                                />
                            </div>
                            <label class="label" v-if="form.errors.fragile_item_charge">
                                <span class="label-text-alt text-error">{{ form.errors.fragile_item_charge }}</span>
                            </label>
                        </div>

                        <!-- Oversized Item Charge -->
                        <div class="form-control w-full mb-6">
                            <label class="label">
                                <span class="label-text font-semibold flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center" title="Oversized">
                                        <i class="fa-solid fa-box text-blue-600 text-xs"></i>
                                    </span>
                                    Oversized Item Charge (per item)
                                </span>
                            </label>
                            <div class="input-group">
                                <span class="bg-base-200 px-4 flex items-center">$</span>
                                <input
                                    v-model="form.oversized_item_charge"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="0.00"
                                    class="input input-bordered w-full"
                                    :class="{ 'input-error': form.errors.oversized_item_charge }"
                                    required
                                />
                            </div>
                            <label class="label" v-if="form.errors.oversized_item_charge">
                                <span class="label-text-alt text-error">{{ form.errors.oversized_item_charge }}</span>
                            </label>
                        </div>

                        <!-- Info Box -->
                        <div class="alert alert-info mb-6">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h3 class="font-bold">How it works</h3>
                                <div class="text-sm">
                                    <p class="mt-1">
                                        Each carrier can have its own commission percentage. This allows you to set different markups for DHL, FedEx, and UPS.
                                    </p>
                                    <p class="mt-2">
                                        For example, if you set DHL to 15% and FedEx to 20%, a shipping rate of $100 will become $115 for DHL and $120 for FedEx.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Current Settings Info -->
                        <div class="bg-base-200 rounded-lg p-4 mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <p class="text-sm text-base-content/60">DHL Commission</p>
                                    <p class="text-2xl font-bold text-red-600">
                                        {{ commission.dhl_commission_percentage }}%
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-base-content/60">FedEx Commission</p>
                                    <p class="text-2xl font-bold text-blue-600">
                                        {{ commission.fedex_commission_percentage }}%
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-base-content/60">UPS Commission</p>
                                    <p class="text-2xl font-bold text-yellow-600">
                                        {{ commission.ups_commission_percentage }}%
                                    </p>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-base-300">
                                <p class="text-sm font-semibold mb-2">Classification Charges</p>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-sm text-base-content/60">Dangerous Goods</p>
                                        <p class="text-xl font-bold text-red-600">
                                            ${{ parseFloat(commission.dangerous_goods_charge || 0).toFixed(2) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-base-content/60">Fragile Items</p>
                                        <p class="text-xl font-bold text-amber-600">
                                            ${{ parseFloat(commission.fragile_item_charge || 0).toFixed(2) }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-base-content/60">Oversized Items</p>
                                        <p class="text-xl font-bold text-blue-600">
                                            ${{ parseFloat(commission.oversized_item_charge || 0).toFixed(2) }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4 pt-4 border-t border-base-300">
                                <p class="text-sm text-base-content/60">Last Updated</p>
                                <p class="text-sm font-medium">
                                    {{ new Date(commission.updated_at).toLocaleString() }}
                                </p>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="card-actions justify-end">
                            <button
                                type="submit"
                                class="btn btn-primary"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ form.processing ? 'Updating...' : 'Update Commissions' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Carrier Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                <div class="card bg-base-100 shadow-lg border border-red-200">
                    <div class="card-body">
                        <div class="flex items-center gap-3">
                            <div class="avatar placeholder">
                                <div class="bg-red-100 text-red-600 rounded w-12">
                                    <span class="text-lg font-bold">DHL</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-semibold">DHL Express</h3>
                                <p class="text-sm text-base-content/60">
                                    Commission: <span class="font-bold text-red-600">{{ commission.dhl_commission_percentage }}%</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 shadow-lg border border-blue-200">
                    <div class="card-body">
                        <div class="flex items-center gap-3">
                            <div class="avatar placeholder">
                                <div class="bg-blue-100 text-blue-600 rounded w-12">
                                    <span class="text-lg font-bold">FX</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-semibold">FedEx</h3>
                                <p class="text-sm text-base-content/60">
                                    Commission: <span class="font-bold text-blue-600">{{ commission.fedex_commission_percentage }}%</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 shadow-lg border border-yellow-200">
                    <div class="card-body">
                        <div class="flex items-center gap-3">
                            <div class="avatar placeholder">
                                <div class="bg-yellow-100 text-yellow-600 rounded w-12">
                                    <span class="text-lg font-bold">UPS</span>
                                </div>
                            </div>
                            <div>
                                <h3 class="font-semibold">UPS</h3>
                                <p class="text-sm text-base-content/60">
                                    Commission: <span class="font-bold text-yellow-600">{{ commission.ups_commission_percentage }}%</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
