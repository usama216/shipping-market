<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Loyalty Program
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Tier Card Section -->
                <div class="grid grid-cols-1 gap-6 mb-8 lg:grid-cols-3">
                    <!-- Tier Progress Card -->
                    <div class="lg:col-span-2">
                        <div
                            class="relative overflow-hidden shadow-lg bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 sm:rounded-2xl"
                        >
                            <!-- Decorative Elements -->
                            <div class="absolute top-0 right-0 w-64 h-64 rounded-full opacity-10 -mr-32 -mt-32 bg-gradient-to-br"
                                :style="{ background: `radial-gradient(circle, ${tierColor} 0%, transparent 70%)` }"
                            ></div>
                            
                            <div class="relative p-6 lg:p-8">
                                <div class="flex items-start justify-between mb-6">
                                    <div>
                                        <p class="text-sm font-medium text-gray-400 uppercase tracking-wider">Your Tier</p>
                                        <h3 class="mt-1 text-3xl font-bold" :style="{ color: tierColor }">
                                            {{ tierName }}
                                        </h3>
                                    </div>
                                    <div class="flex items-center justify-center w-16 h-16 rounded-full" 
                                        :style="{ background: `${tierColor}20`, border: `2px solid ${tierColor}` }">
                                        <TrophyIcon class="w-8 h-8" :style="{ color: tierColor }" />
                                    </div>
                                </div>

                                <!-- Points Display -->
                                <div class="grid grid-cols-2 gap-4 mb-6">
                                    <div class="p-4 rounded-xl bg-white/5">
                                        <p class="text-xs font-medium text-gray-400 uppercase">Available Points</p>
                                        <p class="mt-1 text-2xl font-bold text-white">{{ summary.current_points?.toLocaleString() || 0 }}</p>
                                    </div>
                                    <div class="p-4 rounded-xl bg-white/5">
                                        <p class="text-xs font-medium text-gray-400 uppercase">Earning Rate</p>
                                        <p class="mt-1 text-2xl font-bold" :style="{ color: tierColor }">{{ earnMultiplier }}x</p>
                                    </div>
                                </div>

                                <!-- Tier Progress -->
                                <div v-if="summary.tier_progress?.next_tier" class="space-y-2">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-400">Progress to {{ summary.tier_progress.next_tier.name }}</span>
                                        <span class="font-medium text-white">{{ summary.tier_progress.percentage }}%</span>
                                    </div>
                                    <div class="w-full h-2 overflow-hidden bg-gray-700 rounded-full">
                                        <div 
                                            class="h-full transition-all duration-500 rounded-full"
                                            :style="{ width: `${summary.tier_progress.percentage}%`, background: tierColor }"
                                        ></div>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        Spend ${{ summary.tier_progress.spend_remaining?.toFixed(2) || '0.00' }} more to reach {{ summary.tier_progress.next_tier.name }}
                                    </p>
                                </div>
                                <div v-else class="p-3 text-sm text-center rounded-lg bg-white/5 text-amber-400">
                                    🎉 You've reached the highest tier!
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Referral Card -->
                    <div class="lg:col-span-1">
                        <div class="h-full p-6 bg-white shadow-lg sm:rounded-2xl">
                            <div class="flex items-center mb-4">
                                <div class="p-2 mr-3 rounded-lg bg-primary-100">
                                    <UserPlusIcon class="w-6 h-6 text-primary-600" />
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900">Refer Friends</h3>
                            </div>
                            
                            <p class="mb-4 text-sm text-gray-600">
                                Share your referral code and earn <strong>100 bonus points</strong> when they make their first purchase!
                            </p>

                            <div class="p-3 mb-4 border-2 border-dashed rounded-lg bg-gray-50 border-primary-200">
                                <p class="text-xs font-medium text-gray-500 uppercase">Your Referral Code</p>
                                <div class="flex items-center justify-between mt-1">
                                    <span class="text-xl font-bold tracking-wider text-primary-600">
                                        {{ referralCode || 'N/A' }}
                                    </span>
                                    <button 
                                        @click="copyReferralCode"
                                        class="p-2 text-gray-500 transition hover:text-primary-600"
                                    >
                                        <ClipboardDocumentIcon class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            <button
                                @click="shareReferral"
                                class="flex items-center justify-center w-full gap-2 px-4 py-2 text-sm font-medium text-white transition rounded-lg bg-primary-600 hover:bg-primary-700"
                            >
                                <ShareIcon class="w-4 h-4" />
                                Share Link
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 gap-4 mb-8 md:grid-cols-4">
                    <div class="p-4 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center">
                            <div class="p-2 mr-3 bg-blue-100 rounded-lg">
                                <StarIcon class="w-5 h-5 text-blue-600" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500">Current</p>
                                <p class="text-lg font-semibold text-gray-900">{{ summary.current_points?.toLocaleString() || 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center">
                            <div class="p-2 mr-3 bg-green-100 rounded-lg">
                                <ArrowTrendingUpIcon class="w-5 h-5 text-green-600" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500">Earned</p>
                                <p class="text-lg font-semibold text-gray-900">{{ summary.total_earned?.toLocaleString() || 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center">
                            <div class="p-2 mr-3 bg-orange-100 rounded-lg">
                                <ArrowTrendingDownIcon class="w-5 h-5 text-orange-600" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500">Redeemed</p>
                                <p class="text-lg font-semibold text-gray-900">{{ summary.total_redeemed?.toLocaleString() || 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 bg-white shadow-sm sm:rounded-xl">
                        <div class="flex items-center">
                            <div class="p-2 mr-3 bg-purple-100 rounded-lg">
                                <CurrencyDollarIcon class="w-5 h-5 text-purple-600" />
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500">Saved</p>
                                <p class="text-lg font-semibold text-gray-900">${{ (summary.total_discount_earned || 0).toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- How It Works + Recent Transactions -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- How It Works -->
                    <div class="p-6 bg-white shadow-sm lg:col-span-1 sm:rounded-xl">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900">How It Works</h3>
                        <div class="space-y-4">
                            <div class="flex items-start">
                                <div class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-sm font-bold text-white rounded-full bg-primary-600">1</div>
                                <div class="ml-3">
                                    <p class="font-medium text-gray-900">Shop & Earn</p>
                                    <p class="text-sm text-gray-500">Earn points on every purchase based on your tier.</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-sm font-bold text-white rounded-full bg-primary-600">2</div>
                                <div class="ml-3">
                                    <p class="font-medium text-gray-900">Level Up</p>
                                    <p class="text-sm text-gray-500">Spend more to unlock higher tiers with better multipliers.</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <div class="flex items-center justify-center flex-shrink-0 w-8 h-8 text-sm font-bold text-white rounded-full bg-primary-600">3</div>
                                <div class="ml-3">
                                    <p class="font-medium text-gray-900">Redeem</p>
                                    <p class="text-sm text-gray-500">Use points at checkout for instant discounts!</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="p-6 bg-white shadow-sm lg:col-span-2 sm:rounded-xl">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                            <Link
                                :href="route('customer.loyalty.transactions')"
                                class="text-sm font-medium text-primary-600 hover:text-primary-800"
                            >
                                View All →
                            </Link>
                        </div>

                        <div v-if="transactions.length === 0" class="py-8 text-center">
                            <DocumentTextIcon class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                            <p class="text-gray-500">No transactions yet.</p>
                            <p class="text-sm text-gray-400">Start shopping to earn points!</p>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="transaction in transactions.slice(0, 5)"
                                :key="transaction.id"
                                class="flex items-center justify-between p-3 transition border border-gray-100 rounded-lg hover:bg-gray-50"
                            >
                                <div class="flex items-center">
                                    <div :class="[
                                        'p-2 rounded-lg',
                                        transaction.type === 'earn' ? 'bg-green-100' : 'bg-orange-100'
                                    ]">
                                        <PlusIcon v-if="transaction.type === 'earn'" class="w-4 h-4 text-green-600" />
                                        <MinusIcon v-else class="w-4 h-4 text-orange-600" />
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">{{ transaction.description }}</p>
                                        <p class="text-xs text-gray-500">{{ formatDate(transaction.created_at) }}</p>
                                    </div>
                                </div>
                                <span :class="[
                                    'font-semibold',
                                    transaction.type === 'earn' ? 'text-green-600' : 'text-orange-600'
                                ]">
                                    {{ transaction.type === 'earn' ? '+' : '-' }}{{ transaction.points }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import {
    StarIcon,
    PlusIcon,
    MinusIcon,
    CurrencyDollarIcon,
    DocumentTextIcon,
    TrophyIcon,
    UserPlusIcon,
    ShareIcon,
    ClipboardDocumentIcon,
    ArrowTrendingUpIcon,
    ArrowTrendingDownIcon,
} from "@heroicons/vue/24/outline";

const toast = useToast();
const page = usePage();

const props = defineProps({
    summary: Object,
    transactions: Array,
});

// Computed tier properties
const tierName = computed(() => props.summary?.tier?.name || "Bronze");
const tierColor = computed(() => props.summary?.tier?.color || "#CD7F32");
const earnMultiplier = computed(() => props.summary?.tier?.earn_multiplier || 1.0);
const referralCode = computed(() => page.props.auth?.user?.referral_code || null);

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const copyReferralCode = async () => {
    if (!referralCode.value) return;
    try {
        await navigator.clipboard.writeText(referralCode.value);
        toast.success("Referral code copied!");
    } catch {
        toast.error("Failed to copy code");
    }
};

const shareReferral = async () => {
    const shareUrl = `${window.location.origin}/register?ref=${referralCode.value}`;
    if (navigator.share) {
        try {
            await navigator.share({
                title: "Join Marketsz!",
                text: "Sign up using my referral code and get bonus points!",
                url: shareUrl,
            });
        } catch {
            // User cancelled
        }
    } else {
        await navigator.clipboard.writeText(shareUrl);
        toast.success("Referral link copied!");
    }
};
</script>
