<script setup>
import { ref, computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import TabLink from "@/Components/TabLink.vue";

const currentUrl = usePage().url;
const props = defineProps({
    actionCount: Object,
    inReviewCount: Object,
    readyToSendCount: Object,
    allPackagesCount: Object,
});

const activeIndex = ref(null);
const isOpenAddress = ref(false);

const toggle = (index) => {
    activeIndex.value = activeIndex.value === index ? null : index;
};

const copyText = (text) => {
    navigator.clipboard.writeText(text);
    alert(`Copy text successfully. ${text}`);
};

// Get warehouse from auth user
const warehouse = computed(() => usePage().props?.auth?.user?.warehouse);
const suite = computed(() => usePage().props?.auth?.user?.suite);
</script>

<template>
    <AuthenticatedLayout>
        <div class="grid items-start gap-4 md:grid-cols-10">
            <!-- Left Side -->
            <div class="col-span-2">
                <p class="text-lg font-semibold">
                    Suite: {{ suite }}
                </p>
            </div>
            <div></div>
            <div></div>
            <div></div>
            <!-- Right Side -->
            <div class="col-span-3 col-start-8 space-y-2 flex justify-end">
                <div class="relative bg-white border border-gray-200 shadow-md w-[85%]">
                    <!-- Header -->
                    <div
                        class="cursor-pointer bg-[#f3f3f4] text-[#9e1d22] font-[700] p-2 flex justify-between items-center"
                        @click="isOpenAddress = !isOpenAddress"
                    >
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 ml-2 text-sm">
                                {{ warehouse?.country || 'US' }} Address
                            </span>
                        </div>
                        <i :class="isOpenAddress ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-gray-500"></i>
                    </div>

                    <!-- Content (Absolutely positioned dropdown) -->
                    <div
                        v-show="isOpenAddress"
                        class="absolute right-0 z-50 p-4 space-y-2 bg-white border border-gray-200 shadow-lg top-full w-full"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">Address 1:</span>
                            <span class="flex items-center gap-2 text-sm text-gray-700">
                                {{ warehouse?.address || 'Not assigned' }}
                                <i
                                    v-if="warehouse?.address"
                                    class="cursor-pointer fas fa-copy text-primary-500"
                                    @click="copyText(warehouse.address)"
                                ></i>
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">Address 2:</span>
                            <span class="flex items-center gap-2 text-sm text-gray-700">
                                {{ suite }} {{ warehouse?.address_line_2 ? `- ${warehouse.address_line_2}` : '' }}
                                <i
                                    class="cursor-pointer fas fa-copy text-primary-500"
                                    @click="copyText(`${suite}${warehouse?.address_line_2 ? ` - ${warehouse.address_line_2}` : ''}`)"
                                ></i>
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">City:</span>
                            <span class="flex items-center gap-2 text-sm text-gray-700">
                                {{ warehouse?.city || '-' }}
                                <i
                                    v-if="warehouse?.city"
                                    class="cursor-pointer fas fa-copy text-primary-500"
                                    @click="copyText(warehouse.city)"
                                ></i>
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">State:</span>
                            <span class="flex items-center gap-2 text-sm text-gray-700">
                                {{ warehouse?.state || '-' }}
                                <i
                                    v-if="warehouse?.state"
                                    class="cursor-pointer fas fa-copy text-primary-500"
                                    @click="copyText(warehouse.state)"
                                ></i>
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">Postal Code:</span>
                            <span class="flex items-center gap-2 text-sm text-gray-700">
                                {{ warehouse?.zip || '-' }}
                                <i
                                    v-if="warehouse?.zip"
                                    class="cursor-pointer fas fa-copy text-primary-500"
                                    @click="copyText(warehouse.zip)"
                                ></i>
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">Country:</span>
                            <span class="flex items-center gap-2 text-sm text-gray-700">
                                {{ warehouse?.country || '-' }}
                                <i
                                    v-if="warehouse?.country"
                                    class="cursor-pointer fas fa-copy text-primary-500"
                                    @click="copyText(warehouse.country)"
                                ></i>
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700">Phone:</span>
                            <span class="flex items-center gap-2 text-sm text-gray-700">
                                {{ warehouse?.phone_number || '-' }}
                                <i
                                    v-if="warehouse?.phone_number"
                                    class="cursor-pointer fas fa-copy text-primary-500"
                                    @click="copyText(warehouse.phone_number)"
                                ></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="flex flex-wrap gap-2 pb-2 mt-6 border-b">
            <TabLink
                label="Action Required"
                :href="route('customer.suiteActionRequired')"
                :count="props?.actionCount"
                color="red"
                :active="currentUrl === '/customer/suite/action-required'"
            />

            <TabLink
                label="In Review"
                :href="route('customer.suite.inReview')"
                :count="props?.inReviewCount"
                color="yellow"
                :active="currentUrl === '/customer/suite/in-review'"
            />
            <TabLink
                label="Ready to Send"
                :href="route('customer.suite.readyToSend')"
                :count="props?.readyToSendCount"
                color="green"
                :active="currentUrl === '/customer/suite/ready-to-send'"
            />
            <TabLink
                label="View All"
                :href="route('customer.suite.viewAll')"
                :count="props?.allPackagesCount"
                color="slate"
                :active="currentUrl === '/customer/suite/view-all'"
            />
        </div>

        <div class="w-full mt-6">
            <slot />
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: scaleY(0.95);
}
</style>