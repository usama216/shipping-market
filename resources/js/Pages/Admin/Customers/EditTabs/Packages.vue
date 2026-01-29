<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import Edit from "../Edit.vue";
import NoResults from "@/Components/NoResults.vue";
import { usePermissions } from "@/Composables/usePermissions";

const { can } = usePermissions();

const props = defineProps({
    user: Object,
    userPackages: Object,
});

// Check if any item has classification flags
const hasClassification = (pkg) => {
    if (!pkg.items || pkg.items.length === 0) return { dangerous: false, fragile: false, oversized: false };
    return {
        dangerous: pkg.items.some(item => item.is_dangerous),
        fragile: pkg.items.some(item => item.is_fragile),
        oversized: pkg.items.some(item => item.is_oversized)
    };
};

const goToEdit = (pkg) => {
    if (can('packages.update')) {
        router.visit(route('admin.packages.edit', pkg.id));
    }
};

const formatCurrency = (value) => {
    if (!value) return '-';
    return `$${parseFloat(value).toFixed(2)}`;
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric'
    });
};
</script>
<template>
    <Head title="Customer Packages" />
    <AuthenticatedLayout>
        <Edit :user="props?.user">
            <div class="space-y-4">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Packages</h3>
                    <span class="text-sm text-gray-500">{{ userPackages?.length || 0 }} packages</span>
                </div>

                <!-- No Results -->
                <NoResults
                    v-if="!userPackages || userPackages.length === 0"
                    icon="fas fa-box-open"
                    title="No Packages"
                    message="This customer has no packages yet."
                />

                <!-- Packages Table -->
                <div v-else class="bg-white border rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-gray-600">
                            <thead class="bg-gray-50 border-b uppercase font-semibold text-xs text-gray-500">
                                <tr>
                                    <th class="px-4 py-3">From</th>
                                    <th class="px-4 py-3">Package ID</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3 text-center">Items</th>
                                    <th class="px-4 py-3 text-center">Class.</th>
                                    <th class="px-4 py-3">Date</th>
                                    <th class="px-4 py-3 text-right">Value</th>
                                    <th class="px-4 py-3 text-right">Weight</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr 
                                    v-for="pkg in userPackages" 
                                    :key="pkg.id"
                                    @click="goToEdit(pkg)"
                                    class="hover:bg-blue-50 transition-colors"
                                    :class="can('packages.update') ? 'cursor-pointer' : ''"
                                >
                                    <!-- From -->
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ pkg.from || '-' }}
                                    </td>
                                    
                                    <!-- Package ID -->
                                    <td class="px-4 py-3">
                                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                                            {{ pkg.package_id }}
                                        </span>
                                    </td>
                                    
                                    <!-- Status -->
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold whitespace-nowrap" 
                                            :class="{
                                                'bg-red-100 text-red-700': pkg.status === 1,
                                                'bg-yellow-100 text-yellow-700': pkg.status === 2,
                                                'bg-blue-100 text-blue-700': pkg.status === 3,
                                                'bg-green-100 text-green-700': pkg.status === 4
                                            }">
                                            {{ pkg.status_name }}
                                        </span>
                                    </td>
                                    
                                    <!-- Items Count -->
                                    <td class="px-4 py-3 text-center">
                                        <span v-if="pkg.items?.length > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">
                                            <i class="fas fa-box-open text-[10px]"></i>{{ pkg.items?.length }}
                                        </span>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                    
                                    <!-- Classification Badges -->
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <span v-if="hasClassification(pkg).dangerous" class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center" title="Dangerous Goods">
                                                <i class="fas fa-fire text-red-600 text-[10px]"></i>
                                            </span>
                                            <span v-if="hasClassification(pkg).fragile" class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center" title="Fragile">
                                                <i class="fas fa-wine-glass text-amber-600 text-[10px]"></i>
                                            </span>
                                            <span v-if="hasClassification(pkg).oversized" class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center" title="Oversized">
                                                <i class="fas fa-expand-arrows-alt text-blue-600 text-[10px]"></i>
                                            </span>
                                            <span v-if="!hasClassification(pkg).dangerous && !hasClassification(pkg).fragile && !hasClassification(pkg).oversized" class="text-gray-300">—</span>
                                        </div>
                                    </td>
                                    
                                    <!-- Date -->
                                    <td class="px-4 py-3 text-gray-500 text-xs">
                                        {{ formatDate(pkg.date_received || pkg.created_at) }}
                                    </td>
                                    
                                    <!-- Value -->
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">
                                        {{ formatCurrency(pkg.total_value) }}
                                    </td>
                                    
                                    <!-- Weight -->
                                    <td class="px-4 py-3 text-right text-gray-500 text-xs">
                                        {{ pkg.total_weight || '-' }} {{ pkg.weight_unit || 'lb' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </Edit>
    </AuthenticatedLayout>
</template>
