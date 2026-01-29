<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const props = defineProps({
    saleAmount: Number,
    totalCustomers: Number,
    newCustomers: Number,
    salesTrend: Number,
    customersTrend: Number,
    packagesActionRequired: Number,
    packagesInReview: Number,
    packagesReadyToSend: Number,
    totalPackages: Number,
    activeShipments: Number,
    totalShipments: Number,
    todayShipments: Number,
    todayTransactions: Number,
    todaySales: Number,
    recentTransactions: Array,
    recentCustomers: Array,
    selectedRange: String,
});

const dateRange = ref(props.selectedRange || '30');

const dateRanges = [
    { value: 'today', label: 'Today' },
    { value: '7', label: '7 Days' },
    { value: '30', label: '30 Days' },
    { value: '90', label: '90 Days' },
];

const changeRange = (range) => {
    dateRange.value = range;
    router.get(route('dashboard'), { range }, { preserveState: true });
};

const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 2,
    }).format(amount || 0);
};

const formatNumber = (num) => {
    return new Intl.NumberFormat('en-US').format(num || 0);
};
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard Overview</h1>
                    <p class="text-gray-500 mt-1">Welcome back! Here's what's happening with your business.</p>
                </div>
                
                <!-- Date Range Filter -->
                <div class="flex items-center gap-2 bg-white rounded-xl p-1 shadow-sm border border-gray-100">
                    <button
                        v-for="range in dateRanges"
                        :key="range.value"
                        @click="changeRange(range.value)"
                        :class="[
                            'px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200',
                            dateRange === range.value
                                ? 'bg-primary-500 text-white shadow-md'
                                : 'text-gray-600 hover:bg-gray-100'
                        ]"
                    >
                        {{ range.label }}
                    </button>
                </div>
            </div>

            <!-- Primary Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Total Sales Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-primary-500 to-primary-700 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow duration-300">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute bottom-0 left-0 -mb-8 -ml-8 w-32 h-32 bg-white/5 rounded-full"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                <i class="fa-solid fa-dollar-sign text-xl"></i>
                            </div>
                            <div :class="[
                                'flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium',
                                salesTrend >= 0 ? 'bg-green-400/20 text-green-100' : 'bg-red-400/20 text-red-100'
                            ]">
                                <i :class="salesTrend >= 0 ? 'fa-solid fa-arrow-up' : 'fa-solid fa-arrow-down'"></i>
                                {{ Math.abs(salesTrend) }}%
                            </div>
                        </div>
                        <p class="text-white/80 text-sm font-medium mb-1">Total Sales</p>
                        <p class="text-3xl font-bold">{{ formatCurrency(saleAmount) }}</p>
                    </div>
                </div>

                <!-- Total Customers Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow duration-300">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute bottom-0 left-0 -mb-8 -ml-8 w-32 h-32 bg-white/5 rounded-full"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                <i class="fa-solid fa-users text-xl"></i>
                            </div>
                            <div :class="[
                                'flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium',
                                customersTrend >= 0 ? 'bg-green-400/20 text-green-100' : 'bg-red-400/20 text-red-100'
                            ]">
                                <i :class="customersTrend >= 0 ? 'fa-solid fa-arrow-up' : 'fa-solid fa-arrow-down'"></i>
                                {{ Math.abs(customersTrend) }}%
                            </div>
                        </div>
                        <p class="text-white/80 text-sm font-medium mb-1">Active Customers</p>
                        <p class="text-3xl font-bold">{{ formatNumber(totalCustomers) }}</p>
                        <p class="text-white/60 text-xs mt-2">+{{ newCustomers }} new this period</p>
                    </div>
                </div>

                <!-- Pending Packages Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow duration-300">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute bottom-0 left-0 -mb-8 -ml-8 w-32 h-32 bg-white/5 rounded-full"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                <i class="fa-solid fa-box text-xl"></i>
                            </div>
                            <span class="px-2 py-1 bg-white/20 rounded-full text-xs font-medium">
                                {{ totalPackages }} total
                            </span>
                        </div>
                        <p class="text-white/80 text-sm font-medium mb-1">Action Required</p>
                        <p class="text-3xl font-bold">{{ formatNumber(packagesActionRequired) }}</p>
                        <p class="text-white/60 text-xs mt-2">{{ packagesInReview }} in review</p>
                    </div>
                </div>

                <!-- Active Shipments Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-violet-500 to-purple-700 rounded-2xl p-6 text-white shadow-lg hover:shadow-xl transition-shadow duration-300">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute bottom-0 left-0 -mb-8 -ml-8 w-32 h-32 bg-white/5 rounded-full"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                                <i class="fa-solid fa-truck-fast text-xl"></i>
                            </div>
                            <span class="px-2 py-1 bg-white/20 rounded-full text-xs font-medium">
                                {{ totalShipments }} total
                            </span>
                        </div>
                        <p class="text-white/80 text-sm font-medium mb-1">Active Shipments</p>
                        <p class="text-3xl font-bold">{{ formatNumber(activeShipments) }}</p>
                        <p class="text-white/60 text-xs mt-2">{{ todayShipments }} created today</p>
                    </div>
                </div>
            </div>

            <!-- Secondary Stats Row -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <!-- In Review -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-blue-50 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-magnifying-glass text-blue-500 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Packages In Review</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(packagesInReview) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Ready to Send -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-green-50 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-check-circle text-green-500 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Ready to Send</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(packagesReadyToSend) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Today's Transactions -->
                <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-primary-50 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-receipt text-primary-500 text-xl"></i>
                        </div>
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Today's Transactions</p>
                            <p class="text-2xl font-bold text-gray-900">{{ formatNumber(todayTransactions) }}</p>
                            <p class="text-xs text-gray-400 mt-0.5">{{ formatCurrency(todaySales) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Reports Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Transactions -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-primary-50 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-credit-card text-primary-500"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900">Recent Transactions</h3>
                        </div>
                        <a :href="route('admin.transactions.allTransactions')" class="text-primary-500 text-sm font-medium hover:text-primary-600 transition-colors">
                            View All <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div 
                            v-for="transaction in recentTransactions" 
                            :key="transaction.id"
                            class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center text-gray-600 font-semibold text-sm">
                                    {{ transaction.customer?.charAt(0) || 'U' }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ transaction.customer || 'Unknown' }}</p>
                                    <p class="text-xs text-gray-400">{{ transaction.date }} at {{ transaction.time }}</p>
                                </div>
                            </div>
                            <span class="font-semibold text-gray-900">{{ formatCurrency(transaction.amount) }}</span>
                        </div>
                        <div v-if="!recentTransactions?.length" class="px-6 py-8 text-center text-gray-400">
                            <i class="fa-solid fa-inbox text-3xl mb-2"></i>
                            <p>No recent transactions</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Customers -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-emerald-50 rounded-lg flex items-center justify-center">
                                <i class="fa-solid fa-user-plus text-emerald-500"></i>
                            </div>
                            <h3 class="font-semibold text-gray-900">Recent Customers</h3>
                        </div>
                        <a :href="route('admin.customers')" class="text-primary-500 text-sm font-medium hover:text-primary-600 transition-colors">
                            View All <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="divide-y divide-gray-50">
                        <div 
                            v-for="customer in recentCustomers" 
                            :key="customer.id"
                            class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-emerald-100 to-emerald-200 rounded-full flex items-center justify-center text-emerald-600 font-semibold text-sm">
                                    {{ customer.name?.charAt(0) || 'U' }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">{{ customer.name }}</p>
                                    <p class="text-xs text-gray-400">{{ customer.email }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="inline-flex items-center px-2 py-1 bg-gray-100 text-gray-600 rounded-full text-xs font-medium">
                                    Suite {{ customer.suite || 'N/A' }}
                                </span>
                                <p class="text-xs text-gray-400 mt-1">{{ customer.joined }}</p>
                            </div>
                        </div>
                        <div v-if="!recentCustomers?.length" class="px-6 py-8 text-center text-gray-400">
                            <i class="fa-solid fa-users text-3xl mb-2"></i>
                            <p>No recent customers</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                <h3 class="font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="flex flex-wrap gap-3">
                    <a 
                        :href="route('admin.packages')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-50 text-primary-600 rounded-xl font-medium hover:bg-primary-100 transition-colors"
                    >
                        <i class="fa-solid fa-box"></i>
                        View All Packages
                    </a>
                    <a 
                        :href="route('admin.shipments')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-50 text-violet-600 rounded-xl font-medium hover:bg-violet-100 transition-colors"
                    >
                        <i class="fa-solid fa-truck"></i>
                        Manage Shipments
                    </a>
                    <a 
                        :href="route('admin.customers')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-emerald-50 text-emerald-600 rounded-xl font-medium hover:bg-emerald-100 transition-colors"
                    >
                        <i class="fa-solid fa-users"></i>
                        Customer List
                    </a>
                    <a 
                        :href="route('admin.transactions.allTransactions')"
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-amber-50 text-amber-600 rounded-xl font-medium hover:bg-amber-100 transition-colors"
                    >
                        <i class="fa-solid fa-file-invoice-dollar"></i>
                        Transaction History
                    </a>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
