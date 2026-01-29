<template>
    <AuthenticatedLayout>
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Coupon Management
            </h2>
            <button
                v-if="can('coupons.create')"
                @click="openCreateModal"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 cursor-pointer transition"
            >
                <PlusIcon class="w-4 h-4" />
                Create Coupon
            </button>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <!-- Stats Cards Content (Same as before but cleaner structure if needed) -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    <TicketIcon class="w-6 h-6 text-blue-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Total Coupons</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ stats.total_coupons }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ... other stats cards ... -->
                     <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-2 bg-green-100 rounded-lg">
                                    <CheckCircleIcon class="w-6 h-6 text-green-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Active Coupons</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ stats.active_coupons }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                     <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-2 bg-red-100 rounded-lg">
                                    <XCircleIcon class="w-6 h-6 text-red-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Expired Coupons</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ stats.expired_coupons }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="p-2 bg-purple-100 rounded-lg">
                                    <UsersIcon class="w-6 h-6 text-purple-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-600">Total Usage</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ stats.total_usage }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <form @submit.prevent="search" class="flex gap-4">
                            <div class="flex-1">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search coupons..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md transition">
                                Search
                            </button>
                            <button type="button" @click="clearSearch" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition">
                                Clear
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Coupons Table -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Discount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rules</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Validity</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="coupon in coupons.data" :key="coupon.id" class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-gray-900">{{ coupon.code }}</span>
                                                <span class="text-xs text-gray-500">{{ coupon.description || 'No description' }}</span>
                                                <span v-if="coupon.auto_apply" class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 w-fit">
                                                    Auto-Apply
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900 font-medium">
                                                {{ coupon.discount_type === 'percentage' ? coupon.discount_value + '%' : '$' + coupon.discount_value }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Target: {{ formatAudience(coupon.target_audience) }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div>Per Customer Limit: {{ coupon.per_customer_limit || 1 }}</div>
                                            <div v-if="coupon.target_audience === 'certain_customers' && coupon.selected_customer_ids">
                                                Selected Customers: {{ coupon.selected_customer_ids.length }}
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <button 
                                                v-if="can('coupons.toggle.update')"
                                                @click="toggleStatus(coupon)"
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full cursor-pointer transition transform active:scale-95"
                                                :class="getStatusClass(coupon)"
                                            >
                                                {{ getStatusLabel(coupon) }}
                                            </button>
                                            <span 
                                                v-else 
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                :class="getStatusClass(coupon)"
                                            >
                                                {{ getStatusLabel(coupon) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex flex-col">
                                                <span>End: {{ coupon.expiry_date ? formatDate(coupon.expiry_date) : 'No Expiry' }}</span>
                                                <span v-if="coupon.start_date" class="text-xs text-gray-400">Start: {{ formatDate(coupon.start_date) }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex gap-3">
                                                <button
                                                    v-if="can('coupons.update')"
                                                    @click="openEditModal(coupon)"
                                                    class="text-blue-600 hover:text-blue-900 font-medium cursor-pointer"
                                                >
                                                    Edit
                                                </button>
                                                <button
                                                    v-if="can('coupons.delete')"
                                                    @click="deleteCoupon(coupon)"
                                                    class="text-red-600 hover:text-red-900 font-medium cursor-pointer"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                         <div class="mt-6">
                            <Pagination :links="coupons.links" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal for Create/Edit -->
        <Modal :show="showModal" @close="closeModal" maxWidth="2xl">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingCoupon ? 'Edit Coupon' : 'Create New Coupon' }}
                </h2>
                <CouponForm 
                    :coupon="editingCoupon"
                    :customers="customers"
                    @cancel="closeModal" 
                    @success="handleSuccess" 
                />
            </div>
        </Modal>

    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Pagination from "@/Components/Pagination.vue";
import Modal from "@/Components/Modal.vue";
import CouponForm from "./CouponForm.vue";
import { usePermissions } from "@/Composables/usePermissions";
import {
    PlusIcon,
    TicketIcon,
    CheckCircleIcon,
    XCircleIcon,
    UsersIcon,
} from "@heroicons/vue/24/outline";

const { can } = usePermissions();

const props = defineProps({
    coupons: Object,
    stats: Object,
    search: String,
    customers: {
        type: Array,
        default: () => []
    }
});

const searchQuery = ref(props.search || "");
const showModal = ref(false);
const editingCoupon = ref(null);

const search = () => {
    router.get(
        route("admin.coupons.index"),
        { search: searchQuery.value },
        { preserveState: true, preserveScroll: true }
    );
};

const clearSearch = () => {
    searchQuery.value = "";
    router.get(route("admin.coupons.index"), {}, { preserveState: true, preserveScroll: true });
};

// Modal Logic
const openCreateModal = () => {
    editingCoupon.value = null;
    showModal.value = true;
};

const openEditModal = (coupon) => {
    editingCoupon.value = coupon;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    setTimeout(() => { editingCoupon.value = null; }, 200); // Reset after transition
};

const handleSuccess = () => {
    closeModal();
    // Ideally we could show a toast here, but for now we rely on Inertia page reload flash messages if implemented
};

// Status Logic
const toggleStatus = (coupon) => {
    router.put(
        route("admin.coupons.toggle-status", coupon.id),
        {},
        {
            preserveState: true,
            preserveScroll: true,
             onSuccess: () => {
                // Optimistic update handled by Inertia reload usually, or we can force update local state if needed
                coupon.is_active = !coupon.is_active;
            },
        }
    );
};

const getStatusClass = (coupon) => {
    if (!coupon.is_active) return 'bg-red-100 text-red-800';
    if (coupon.is_expired) return 'bg-gray-100 text-gray-800'; // Or a different color for expired but active?
    
    // Check if scheduled
    const now = new Date();
    if (coupon.start_date && new Date(coupon.start_date) > now) {
        return 'bg-yellow-100 text-yellow-800';
    }
    
    return 'bg-green-100 text-green-800';
};

const getStatusLabel = (coupon) => {
    if (!coupon.is_active) return 'Inactive';
    
    // Check if scheduled
    const now = new Date();
    if (coupon.start_date && new Date(coupon.start_date) > now) {
        return 'Scheduled';
    }
    
    return 'Active';
};

const deleteCoupon = (coupon) => {
    if (confirm("Are you sure you want to delete this coupon?")) {
        router.delete(route("admin.coupons.destroy", coupon.id), {
            preserveState: true,
            preserveScroll: true,
        });
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString();
};

const formatAudience = (audience) => {
    const map = {
        'all': 'All',
        'new_customer': 'New Customers',
        'registration': 'Registration',
        'certain_customers': 'Certain Customers'
    };
    return map[audience] || audience;
};
</script>
