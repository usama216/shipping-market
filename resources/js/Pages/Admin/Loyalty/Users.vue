<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Loyalty Customers
                </h2>
                <Link
                    :href="route('admin.loyalty.index')"
                    class="text-sm text-blue-600 hover:text-blue-800"
                >
                    ← Back to Dashboard
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="p-6 bg-white shadow-sm sm:rounded-xl">
                    <!-- Search -->
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">All Customers</h3>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by name or email..."
                            class="w-64 px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <!-- Customers Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3">Customer</th>
                                    <th class="px-4 py-3">Points</th>
                                    <th class="px-4 py-3">Lifetime Spend</th>
                                    <th class="px-4 py-3">Tier</th>
                                    <th class="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr
                                    v-for="customer in filteredCustomers"
                                    :key="customer.id"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ customer.name }}</p>
                                            <p class="text-xs text-gray-500">{{ customer.email }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="text-lg font-semibold text-blue-600">
                                            {{ (customer.loyalty_points || 0).toLocaleString() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        ${{ Number(customer.lifetime_spend || 0).toFixed(2) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span 
                                            class="px-2 py-1 text-xs font-medium rounded-full"
                                            :style="{ 
                                                backgroundColor: getTierColor(customer.lifetime_spend) + '20',
                                                color: getTierColor(customer.lifetime_spend)
                                            }"
                                        >
                                            {{ getTierName(customer.lifetime_spend) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <button
                                            @click="openAdjustModal(customer)"
                                            class="px-3 py-1.5 text-xs font-medium text-white bg-amber-600 rounded hover:bg-amber-700"
                                        >
                                            Adjust Points
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="filteredCustomers.length === 0">
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                        No customers found
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adjust Points Modal -->
        <div
            v-if="showAdjustModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @click.self="closeAdjustModal"
        >
            <div class="w-full max-w-md p-6 bg-white rounded-xl">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">
                    Adjust Points for {{ selectedCustomer?.name }}
                </h3>
                <p class="mb-4 text-sm text-gray-500">
                    Current Balance: <span class="font-semibold text-blue-600">{{ (selectedCustomer?.loyalty_points || 0).toLocaleString() }}</span> points
                </p>

                <form @submit.prevent="submitAdjust" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Action</label>
                        <div class="flex gap-4 mt-2">
                            <label class="flex items-center">
                                <input
                                    v-model="adjustForm.type"
                                    type="radio"
                                    value="add"
                                    class="w-4 h-4 text-green-600 border-gray-300 focus:ring-green-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Add Points</span>
                            </label>
                            <label class="flex items-center">
                                <input
                                    v-model="adjustForm.type"
                                    type="radio"
                                    value="deduct"
                                    class="w-4 h-4 text-red-600 border-gray-300 focus:ring-red-500"
                                />
                                <span class="ml-2 text-sm text-gray-700">Deduct Points</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Points</label>
                        <input
                            v-model="adjustForm.points"
                            type="number"
                            min="1"
                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reason</label>
                        <input
                            v-model="adjustForm.reason"
                            type="text"
                            placeholder="e.g., Customer compensation, Promotion bonus"
                            class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                            required
                        />
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <button
                            type="button"
                            @click="closeAdjustModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 transition border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="adjustForm.processing"
                            :class="[
                                'px-4 py-2 text-sm font-medium text-white transition rounded-lg disabled:opacity-50',
                                adjustForm.type === 'add' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'
                            ]"
                        >
                            {{ adjustForm.type === 'add' ? 'Add' : 'Deduct' }} Points
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    users: Array, // Still named 'users' from backend for compatibility
});

const search = ref('');
const showAdjustModal = ref(false);
const selectedCustomer = ref(null);

const adjustForm = useForm({
    type: 'add',
    points: 100,
    reason: '',
});

const filteredCustomers = computed(() => {
    if (!search.value) return props.users;
    const q = search.value.toLowerCase();
    return props.users.filter(c => 
        c.name?.toLowerCase().includes(q) || 
        c.email?.toLowerCase().includes(q)
    );
});

// Simple tier calculation (matches backend logic)
const getTierName = (lifetimeSpend) => {
    const spend = Number(lifetimeSpend || 0);
    if (spend >= 2000) return 'Gold';
    if (spend >= 500) return 'Silver';
    return 'Bronze';
};

const getTierColor = (lifetimeSpend) => {
    const spend = Number(lifetimeSpend || 0);
    if (spend >= 2000) return '#FFD700';
    if (spend >= 500) return '#C0C0C0';
    return '#CD7F32';
};

const openAdjustModal = (customer) => {
    selectedCustomer.value = customer;
    adjustForm.reset();
    adjustForm.type = 'add';
    adjustForm.points = 100;
    showAdjustModal.value = true;
};

const closeAdjustModal = () => {
    showAdjustModal.value = false;
    selectedCustomer.value = null;
};

const submitAdjust = () => {
    adjustForm.post(route('admin.loyalty.adjust-points', selectedCustomer.value.id), {
        onSuccess: () => closeAdjustModal(),
    });
};
</script>
