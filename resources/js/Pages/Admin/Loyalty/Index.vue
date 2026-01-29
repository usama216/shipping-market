<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Loyalty Program Dashboard
                </h2>
                <div class="flex gap-2">
                    <Link
                        v-if="can('loyalty.referrals.view')"
                        :href="route('admin.loyalty.referrals')"
                        class="px-4 py-2 text-white bg-purple-600 rounded-lg hover:bg-purple-700"
                    >
                        Referrals
                    </Link>
                    <Link
                        v-if="can('loyalty.rules.view')"
                        :href="route('admin.loyalty.rules')"
                        class="px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                    >
                        Manage Rules
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 gap-4 mb-8 md:grid-cols-5">
                    <div class="p-5 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <UsersIcon class="w-5 h-5 text-blue-600" />
                            </div>
                            <div class="ml-3">
                                <p class="text-xs font-medium text-gray-500">Active Users</p>
                                <p class="text-xl font-semibold text-gray-900">{{ stats.total_users_with_points }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <PlusIcon class="w-5 h-5 text-green-600" />
                            </div>
                            <div class="ml-3">
                                <p class="text-xs font-medium text-gray-500">Points Issued</p>
                                <p class="text-xl font-semibold text-gray-900">{{ stats.total_points_issued?.toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center">
                            <div class="p-2 bg-orange-100 rounded-lg">
                                <MinusIcon class="w-5 h-5 text-orange-600" />
                            </div>
                            <div class="ml-3">
                                <p class="text-xs font-medium text-gray-500">Points Redeemed</p>
                                <p class="text-xl font-semibold text-gray-900">{{ stats.total_points_redeemed?.toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <CurrencyDollarIcon class="w-5 h-5 text-purple-600" />
                            </div>
                            <div class="ml-3">
                                <p class="text-xs font-medium text-gray-500">Total Discounts</p>
                                <p class="text-xl font-semibold text-gray-900">${{ (parseFloat(stats.total_discount_given) || 0).toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center">
                            <div class="p-2 bg-indigo-100 rounded-lg">
                                <SparklesIcon class="w-5 h-5 text-indigo-600" />
                            </div>
                            <div class="ml-3">
                                <p class="text-xs font-medium text-gray-500">Active Points</p>
                                <p class="text-xl font-semibold text-gray-900">{{ stats.active_points?.toLocaleString() }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Loyalty Rule -->
                <div v-if="activeRule" class="p-6 mb-8 bg-white shadow-sm sm:rounded-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Active Loyalty Rule</h3>
                            <p class="text-sm text-gray-500">{{ activeRule.name }}</p>
                        </div>
                        <span class="px-3 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                            Active
                        </span>
                    </div>

                    <form @submit.prevent="updateActiveRule" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Spend Amount ($)
                            </label>
                            <input
                                v-model.number="ruleForm.spend_amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Earn Points
                            </label>
                            <input
                                v-model.number="ruleForm.earn_points"
                                type="number"
                                step="1"
                                min="1"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Redeem Points
                            </label>
                            <input
                                v-model.number="ruleForm.redeem_points"
                                type="number"
                                step="1"
                                min="1"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Redeem Value ($)
                            </label>
                            <input
                                v-model.number="ruleForm.redeem_value"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>
                        <div class="md:col-span-4 flex justify-end gap-2">
                            <button
                                type="button"
                                @click="resetRuleForm"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                            >
                                Reset
                            </button>
                            <button
                                type="submit"
                                :disabled="ruleForm.processing"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
                            >
                                <span v-if="ruleForm.processing">Saving...</span>
                                <span v-else>Update Rule</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div v-else class="p-6 mb-8 bg-yellow-50 border border-yellow-200 rounded-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-yellow-900">No Active Loyalty Rule</h3>
                            <p class="text-sm text-yellow-700">Please create an active loyalty rule to enable the program.</p>
                        </div>
                        <Link
                            :href="route('admin.loyalty.rules')"
                            class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 rounded-lg hover:bg-yellow-700"
                        >
                            Manage Rules
                        </Link>
                    </div>
                </div>

                <!-- Tier Distribution -->
                <div class="p-6 mb-8 bg-white shadow-sm sm:rounded-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Loyalty Tiers</h3>
                        <button
                            v-if="can('loyalty.tiers.create')"
                            @click="openCreateModal"
                            class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-white transition bg-amber-600 rounded-lg hover:bg-amber-700"
                        >
                            <PlusIcon class="w-4 h-4" />
                            Add Tier
                        </button>
                    </div>
                    
                    <div v-if="tierDistribution.length === 0" class="py-8 text-center text-gray-500">
                        No tiers configured yet.
                        <button @click="openCreateModal" class="block mx-auto mt-2 text-amber-600 hover:underline">
                            Create your first tier
                        </button>
                    </div>
                    
                    <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div 
                            v-for="tier in tierDistribution" 
                            :key="tier.id"
                            class="relative p-5 overflow-hidden border-2 rounded-xl group"
                            :style="{ borderColor: tier.color }"
                        >
                            <div class="absolute top-0 right-0 w-20 h-20 -mr-6 -mt-6 rounded-full opacity-20"
                                :style="{ background: tier.color }"
                            ></div>
                            
                            <!-- Edit/Delete buttons (show on hover) -->
                            <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    v-if="can('loyalty.tiers.update')"
                                    @click="openEditModal(tier)"
                                    class="p-1.5 text-gray-500 bg-white rounded shadow hover:text-blue-600"
                                    title="Edit"
                                >
                                    <PencilIcon class="w-4 h-4" />
                                </button>
                                <button
                                    v-if="can('loyalty.tiers.delete')"
                                    @click="confirmDelete(tier)"
                                    class="p-1.5 text-gray-500 bg-white rounded shadow hover:text-red-600"
                                    title="Delete"
                                >
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </div>
                            
                            <div class="relative">
                                <div class="flex items-center mb-3">
                                    <TrophyIcon class="w-6 h-6 mr-2" :style="{ color: tier.color }" />
                                    <span class="text-lg font-bold" :style="{ color: tier.color }">{{ tier.name }}</span>
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="text-gray-500">Customers</p>
                                        <p class="text-xl font-semibold text-gray-900">{{ tier.customer_count }}</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500">Multiplier</p>
                                        <p class="text-xl font-semibold" :style="{ color: tier.color }">{{ tier.earn_multiplier }}x</p>
                                    </div>
                                </div>
                                <p class="mt-2 text-xs text-gray-400">
                                    Min. spend: ${{ Number(tier.min_spend).toLocaleString() }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Users and Recent Transactions -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Top Loyalty Users -->
                    <div class="p-6 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Top Loyalty Users</h3>
                            <Link
                                :href="route('admin.loyalty.customers')"
                                class="text-sm font-medium text-blue-600 hover:text-blue-800"
                            >
                                View All →
                            </Link>
                        </div>

                        <div v-if="topUsers.length === 0" class="py-8 text-center">
                            <UsersIcon class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                            <p class="text-gray-500">No users with loyalty points yet.</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="(user, index) in topUsers"
                                :key="user.id"
                                class="flex items-center justify-between p-3 transition border border-gray-100 rounded-lg hover:bg-gray-50"
                            >
                                <div class="flex items-center">
                                    <div
                                        class="flex items-center justify-center w-8 h-8 rounded-full"
                                        :class="index < 3 ? 'bg-amber-100' : 'bg-gray-100'"
                                    >
                                        <span
                                            class="text-sm font-medium"
                                            :class="index < 3 ? 'text-amber-600' : 'text-gray-600'"
                                        >{{ index + 1 }}</span>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">{{ user.name }}</p>
                                        <p class="text-xs text-gray-500">{{ user.email }}</p>
                                    </div>
                                </div>
                                <p class="text-lg font-semibold text-blue-600">
                                    {{ user.loyalty_points?.toLocaleString() }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="p-6 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                            <Link
                                :href="route('admin.loyalty.transactions')"
                                class="text-sm font-medium text-blue-600 hover:text-blue-800"
                            >
                                View All →
                            </Link>
                        </div>

                        <div v-if="recentTransactions.length === 0" class="py-8 text-center">
                            <DocumentTextIcon class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                            <p class="text-gray-500">No transactions yet.</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="transaction in recentTransactions"
                                :key="transaction.id"
                                class="flex items-center justify-between p-3 transition border border-gray-100 rounded-lg hover:bg-gray-50"
                            >
                                <div class="flex items-center">
                                    <div
                                        :class="[
                                            'p-2 rounded-lg',
                                            transaction.type === 'earn' ? 'bg-green-100' : 'bg-orange-100',
                                        ]"
                                    >
                                        <PlusIcon v-if="transaction.type === 'earn'" class="w-4 h-4 text-green-600" />
                                        <MinusIcon v-else class="w-4 h-4 text-orange-600" />
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ transaction.customer?.name || transaction.user?.name || 'Unknown' }}
                                        </p>
                                        <p class="text-xs text-gray-500">{{ formatDate(transaction.created_at) }}</p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p
                                        :class="[
                                            'text-sm font-semibold',
                                            transaction.type === 'earn' ? 'text-green-600' : 'text-orange-600',
                                        ]"
                                    >
                                        {{ transaction.type === 'earn' ? '+' : '-' }}{{ transaction.points }}
                                    </p>
                                    <p v-if="transaction.amount" class="text-xs text-gray-500">
                                        ${{ Number(transaction.amount).toFixed(2) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tier Create/Edit Modal -->
        <div
            v-if="showTierModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @click.self="closeTierModal"
        >
            <div class="w-full max-w-md p-6 bg-white rounded-xl">
                <h3 class="mb-4 text-lg font-semibold text-gray-900">
                    {{ isEditingTier ? 'Edit Tier' : 'Create New Tier' }}
                </h3>

                <form @submit.prevent="submitTierForm" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input
                                v-model="tierForm.name"
                                type="text"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                                placeholder="Gold"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Slug</label>
                            <input
                                v-model="tierForm.slug"
                                type="text"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                                placeholder="gold"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Min. Spend ($)</label>
                            <input
                                v-model="tierForm.min_lifetime_spend"
                                type="number"
                                min="0"
                                step="0.01"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Multiplier</label>
                            <input
                                v-model="tierForm.earn_multiplier"
                                type="number"
                                min="1"
                                max="10"
                                step="0.01"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                                required
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Color</label>
                            <div class="flex items-center gap-2 mt-1">
                                <input
                                    v-model="tierForm.color"
                                    type="color"
                                    class="w-10 h-10 p-0 border-0 rounded cursor-pointer"
                                />
                                <input
                                    v-model="tierForm.color"
                                    type="text"
                                    class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                                />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sort Order</label>
                            <input
                                v-model="tierForm.sort_order"
                                type="number"
                                min="0"
                                class="block w-full mt-1 border-gray-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500"
                                required
                            />
                        </div>
                    </div>

                    <div class="flex items-center">
                        <input
                            v-model="tierForm.is_active"
                            type="checkbox"
                            class="w-4 h-4 border-gray-300 rounded text-amber-600 focus:ring-amber-500"
                        />
                        <label class="ml-2 text-sm text-gray-700">Active</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-4">
                        <button
                            type="button"
                            @click="closeTierModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 transition border border-gray-300 rounded-lg hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="tierForm.processing"
                            class="px-4 py-2 text-sm font-medium text-white transition bg-amber-600 rounded-lg hover:bg-amber-700 disabled:opacity-50"
                        >
                            {{ isEditingTier ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, watch } from "vue";
import { Link, useForm, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { usePermissions } from "@/Composables/usePermissions";
import {
    UsersIcon,
    PlusIcon,
    MinusIcon,
    CurrencyDollarIcon,
    DocumentTextIcon,
    TrophyIcon,
    SparklesIcon,
    PencilIcon,
    TrashIcon,
} from "@heroicons/vue/24/outline";

const { can } = usePermissions();

const props = defineProps({
    stats: Object,
    topUsers: Array,
    recentTransactions: Array,
    tierDistribution: Array,
    tiers: Array,
    activeRule: {
        type: Object,
        default: null,
    },
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

// Active Rule Form
const ruleForm = useForm({
    name: props.activeRule?.name || '',
    spend_amount: props.activeRule?.spend_amount || 0,
    earn_points: props.activeRule?.earn_points || 0,
    redeem_points: props.activeRule?.redeem_points || 0,
    redeem_value: props.activeRule?.redeem_value || 0,
    is_active: props.activeRule?.is_active ?? true,
});

const updateActiveRule = () => {
    if (!props.activeRule?.id) return;
    
    // Use router.post to include redirect_to parameter
    router.post(
        route('admin.loyalty.rules.update', props.activeRule.id),
        {
            ...ruleForm.data(),
            redirect_to: 'admin.loyalty.index',
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                // Form will be re-initialized with new data from props via watcher
            },
        }
    );
};

const resetRuleForm = () => {
    if (props.activeRule) {
        ruleForm.name = props.activeRule.name;
        ruleForm.spend_amount = props.activeRule.spend_amount;
        ruleForm.earn_points = props.activeRule.earn_points;
        ruleForm.redeem_points = props.activeRule.redeem_points;
        ruleForm.redeem_value = props.activeRule.redeem_value;
        ruleForm.is_active = props.activeRule.is_active;
    }
};

// Watch for changes in activeRule and update form
watch(() => props.activeRule, (newRule) => {
    if (newRule) {
        ruleForm.name = newRule.name;
        ruleForm.spend_amount = newRule.spend_amount;
        ruleForm.earn_points = newRule.earn_points;
        ruleForm.redeem_points = newRule.redeem_points;
        ruleForm.redeem_value = newRule.redeem_value;
        ruleForm.is_active = newRule.is_active;
    }
}, { immediate: true, deep: true });

// Tier Modal State
const showTierModal = ref(false);
const isEditingTier = ref(false);
const editingTierId = ref(null);

const tierForm = useForm({
    name: '',
    slug: '',
    min_lifetime_spend: 0,
    earn_multiplier: 1,
    color: '#CD7F32',
    icon: '',
    sort_order: 0,
    is_active: true,
});

const openCreateModal = () => {
    isEditingTier.value = false;
    editingTierId.value = null;
    tierForm.reset();
    tierForm.sort_order = props.tierDistribution?.length + 1 || 1;
    showTierModal.value = true;
};

const openEditModal = (tier) => {
    isEditingTier.value = true;
    editingTierId.value = tier.id;
    tierForm.name = tier.name;
    tierForm.slug = tier.slug;
    tierForm.min_lifetime_spend = tier.min_spend;
    tierForm.earn_multiplier = tier.earn_multiplier;
    tierForm.color = tier.color;
    tierForm.sort_order = tier.sort_order || 0;
    tierForm.is_active = true;
    showTierModal.value = true;
};

const closeTierModal = () => {
    showTierModal.value = false;
    tierForm.reset();
};

const submitTierForm = () => {
    if (isEditingTier.value) {
        tierForm.post(route('admin.loyalty.tiers.update', editingTierId.value), {
            onSuccess: () => closeTierModal(),
        });
    } else {
        tierForm.post(route('admin.loyalty.tiers.store'), {
            onSuccess: () => closeTierModal(),
        });
    }
};

const confirmDelete = (tier) => {
    if (confirm(`Are you sure you want to delete the "${tier.name}" tier?`)) {
        router.delete(route('admin.loyalty.tiers.destroy', tier.id));
    }
};
</script>
