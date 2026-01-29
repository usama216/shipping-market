<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Loyalty Program Rules
                </h2>
                <Link
                    :href="route('admin.loyalty.index')"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg"
                >
                    Back to Dashboard
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Current Active Rule -->
                <div
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8"
                >
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Current Active Rule
                        </h3>

                        <div
                            v-if="activeRule"
                            class="bg-green-50 border border-green-200 rounded-lg p-4"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-medium text-green-900">
                                        {{ activeRule.name }}
                                    </h4>
                                    <p class="text-sm text-green-700 mt-1">
                                        Earn {{ activeRule.earn_points }} points
                                        for every ${{
                                            activeRule.spend_amount
                                        }}
                                        spent
                                    </p>
                                    <p class="text-sm text-green-700">
                                        Redeem
                                        {{ activeRule.redeem_points }} points
                                        for ${{
                                            activeRule.redeem_value
                                        }}
                                        discount
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                    >
                                        Active
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else
                            class="bg-yellow-50 border border-yellow-200 rounded-lg p-4"
                        >
                            <p class="text-yellow-800">
                                No active loyalty rule configured.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Create New Rule -->
                <div
                    v-if="can('loyalty.rules.create')"
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8"
                >
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            Create New Rule
                        </h3>

                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Rule Name -->
                                <div>
                                    <label
                                        for="name"
                                        class="block text-sm font-medium text-gray-700"
                                    >
                                        Rule Name
                                    </label>
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :class="{
                                            'border-red-500': errors.name,
                                        }"
                                        placeholder="e.g., Standard Loyalty Program"
                                    />
                                    <p
                                        v-if="errors.name"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ errors.name }}
                                    </p>
                                </div>

                                <!-- Spend Amount -->
                                <div>
                                    <label
                                        for="spend_amount"
                                        class="block text-sm font-medium text-gray-700"
                                    >
                                        Spend Amount ($)
                                    </label>
                                    <input
                                        id="spend_amount"
                                        v-model="form.spend_amount"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :class="{
                                            'border-red-500':
                                                errors.spend_amount,
                                        }"
                                        placeholder="e.g., 10.00"
                                    />
                                    <p
                                        v-if="errors.spend_amount"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ errors.spend_amount }}
                                    </p>
                                </div>

                                <!-- Earn Points -->
                                <div>
                                    <label
                                        for="earn_points"
                                        class="block text-sm font-medium text-gray-700"
                                    >
                                        Points Earned
                                    </label>
                                    <input
                                        id="earn_points"
                                        v-model="form.earn_points"
                                        type="number"
                                        min="1"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :class="{
                                            'border-red-500':
                                                errors.earn_points,
                                        }"
                                        placeholder="e.g., 1"
                                    />
                                    <p
                                        v-if="errors.earn_points"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ errors.earn_points }}
                                    </p>
                                </div>

                                <!-- Redeem Points -->
                                <div>
                                    <label
                                        for="redeem_points"
                                        class="block text-sm font-medium text-gray-700"
                                    >
                                        Points to Redeem
                                    </label>
                                    <input
                                        id="redeem_points"
                                        v-model="form.redeem_points"
                                        type="number"
                                        min="1"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :class="{
                                            'border-red-500':
                                                errors.redeem_points,
                                        }"
                                        placeholder="e.g., 100"
                                    />
                                    <p
                                        v-if="errors.redeem_points"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ errors.redeem_points }}
                                    </p>
                                </div>

                                <!-- Redeem Value -->
                                <div>
                                    <label
                                        for="redeem_value"
                                        class="block text-sm font-medium text-gray-700"
                                    >
                                        Discount Value ($)
                                    </label>
                                    <input
                                        id="redeem_value"
                                        v-model="form.redeem_value"
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        :class="{
                                            'border-red-500':
                                                errors.redeem_value,
                                        }"
                                        placeholder="e.g., 5.00"
                                    />
                                    <p
                                        v-if="errors.redeem_value"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ errors.redeem_value }}
                                    </p>
                                </div>
                            </div>

                            <!-- Active Status -->
                            <div class="flex items-center">
                                <input
                                    id="is_active"
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <label
                                    for="is_active"
                                    class="ml-2 block text-sm text-gray-900"
                                >
                                    Set as active rule (will deactivate other
                                    rules)
                                </label>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end">
                                <button
                                    type="submit"
                                    :disabled="processing"
                                    class="bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white px-4 py-2 rounded-md"
                                >
                                    {{
                                        processing
                                            ? "Creating..."
                                            : "Create Rule"
                                    }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- All Rules -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            All Rules
                        </h3>

                        <div v-if="rules.length === 0" class="text-center py-8">
                            <p class="text-gray-500">
                                No loyalty rules created yet.
                            </p>
                        </div>

                        <div v-else class="space-y-4">
                            <div
                                v-for="rule in rules"
                                :key="rule.id"
                                class="border border-gray-200 rounded-lg p-4"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-900">
                                            {{ rule.name }}
                                        </h4>
                                        <p class="text-sm text-gray-600 mt-1">
                                            Earn {{ rule.earn_points }} points
                                            for every ${{
                                                rule.spend_amount
                                            }}
                                            spent
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            Redeem
                                            {{ rule.redeem_points }} points for
                                            ${{ rule.redeem_value }} discount
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span
                                            :class="[
                                                'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                                rule.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-gray-100 text-gray-800',
                                            ]"
                                        >
                                            {{
                                                rule.is_active
                                                    ? "Active"
                                                    : "Inactive"
                                            }}
                                        </span>
                                        <button
                                            v-if="can('loyalty.rules.update')"
                                            @click="toggleRuleStatus(rule)"
                                            :class="[
                                                'px-3 py-1 text-xs rounded-md',
                                                rule.is_active
                                                    ? 'bg-red-100 text-red-700 hover:bg-red-200'
                                                    : 'bg-green-100 text-green-700 hover:bg-green-200',
                                            ]"
                                        >
                                            {{
                                                rule.is_active
                                                    ? "Deactivate"
                                                    : "Activate"
                                            }}
                                        </button>
                                        <button
                                            v-if="can('loyalty.rules.delete')"
                                            @click="deleteRule(rule)"
                                            class="px-3 py-1 text-xs bg-red-100 text-red-700 hover:bg-red-200 rounded-md"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from "vue";
import { Link, useForm, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { usePermissions } from "@/Composables/usePermissions";

const { can } = usePermissions();

const props = defineProps({
    activeRule: Object,
    rules: Array,
});

const form = useForm({
    name: "",
    spend_amount: "",
    earn_points: "",
    redeem_points: "",
    redeem_value: "",
    is_active: true,
});

const processing = ref(false);

const submit = () => {
    processing.value = true;
    form.post(route("admin.loyalty.rules.store"), {
        onFinish: () => {
            processing.value = false;
        },
    });
};

const toggleRuleStatus = (rule) => {
    router.put(
        route("admin.loyalty.rules.toggle-status", rule.id),
        {},
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

const deleteRule = (rule) => {
    if (confirm("Are you sure you want to delete this rule?")) {
        router.delete(route("admin.loyalty.rules.destroy", rule.id), {
            preserveState: true,
            preserveScroll: true,
        });
    }
};
</script>
