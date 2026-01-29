<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Referral Program
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
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 gap-4 mb-8 md:grid-cols-3">
                    <div class="p-5 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <UsersIcon class="w-5 h-5 text-purple-600" />
                            </div>
                            <div class="ml-3">
                                <p class="text-xs font-medium text-gray-500">Total Referrers</p>
                                <p class="text-xl font-semibold text-gray-900">{{ stats.total_referrers }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <UserPlusIcon class="w-5 h-5 text-green-600" />
                            </div>
                            <div class="ml-3">
                                <p class="text-xs font-medium text-gray-500">Total Referred</p>
                                <p class="text-xl font-semibold text-gray-900">{{ stats.total_referred }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-5 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <CalendarIcon class="w-5 h-5 text-blue-600" />
                            </div>
                            <div class="ml-3">
                                <p class="text-xs font-medium text-gray-500">This Month</p>
                                <p class="text-xl font-semibold text-gray-900">{{ stats.this_month_referrals }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <!-- Top Referrers -->
                    <div class="p-6 bg-white shadow-sm sm:rounded-xl">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Top Referrers</h3>
                        
                        <div v-if="referrers.length === 0" class="py-8 text-center text-gray-500">
                            No referrers yet
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="referrer in referrers"
                                :key="referrer.id"
                                class="p-4 transition border border-gray-100 rounded-lg hover:bg-gray-50"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            {{ referrer.first_name }} {{ referrer.last_name }}
                                        </p>
                                        <p class="text-xs text-gray-500">{{ referrer.email }}</p>
                                    </div>
                                    <span class="px-3 py-1 text-sm font-semibold text-purple-700 bg-purple-100 rounded-full">
                                        {{ referrer.referrals_count }} referrals
                                    </span>
                                </div>
                                <div v-if="referrer.referrals?.length" class="mt-2 pt-2 border-t border-gray-100">
                                    <p class="text-xs text-gray-400 mb-1">Recent referrals:</p>
                                    <div class="flex flex-wrap gap-1">
                                        <span 
                                            v-for="ref in referrer.referrals" 
                                            :key="ref.id"
                                            class="px-2 py-0.5 text-xs bg-gray-100 rounded"
                                        >
                                            {{ ref.first_name }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Referrals -->
                    <div class="p-6 bg-white shadow-sm sm:rounded-xl">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">Recent Referrals</h3>
                        
                        <div v-if="recentReferrals.length === 0" class="py-8 text-center text-gray-500">
                            No referrals yet
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="referral in recentReferrals"
                                :key="referral.id"
                                class="flex items-center justify-between p-3 transition border border-gray-100 rounded-lg hover:bg-gray-50"
                            >
                                <div>
                                    <p class="font-medium text-gray-900">
                                        {{ referral.first_name }} {{ referral.last_name }}
                                    </p>
                                    <p class="text-xs text-gray-500">{{ referral.email }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-400">Referred by</p>
                                    <p v-if="referral.referrer" class="text-sm font-medium text-purple-600">
                                        {{ referral.referrer.first_name }} {{ referral.referrer.last_name }}
                                    </p>
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
import { Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { UsersIcon, UserPlusIcon, CalendarIcon } from "@heroicons/vue/24/outline";

defineProps({
    referrers: Array,
    recentReferrals: Array,
    stats: Object,
});
</script>
