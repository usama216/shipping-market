<script setup>
const props = defineProps({
    address: Object,
});
</script>
<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-2 mb-4">
        <template v-if="props?.address">
            <div
                class="bg-white shadow-md p-5 border relative flex flex-col justify-between hover:shadow-lg transition-shadow"
            >
                <!-- props?.address Info -->
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">
                        {{ props?.address.full_name }}
                    </h3>
                    <p class="text-sm text-gray-500">
                        {{ props?.address.props?.address_name }}
                    </p>
                    <p class="mt-2 text-gray-600">
                        {{ props?.address.address_line_1 }}
                        <br v-if="props?.address.address_line_2" />
                        {{ props?.address.address_line_2 }}
                    </p>
                    <p class="text-gray-600">
                        {{ props?.address.city
                        }}{{
                            props?.address.state
                                ? ", " + props?.address.state
                                : ""
                        }}
                        -
                        {{ props?.address.postal_code }}
                        <br />
                        {{ props?.address.country }}
                    </p>
                    <p class="mt-1 text-gray-500">
                        Phone: {{ props?.address.country_code }}
                        {{ props?.address.phone_number }}
                    </p>

                    <!-- Default Badges -->
                    <div class="mt-2 space-x-2">
                        <span
                            v-if="props?.address.is_default_us"
                            class="text-xs text-white bg-blue-600 px-2 py-1 rounded-full"
                        >
                            Default US
                        </span>
                        <span
                            v-if="props?.address.is_default_uk"
                            class="text-xs text-white bg-green-600 px-2 py-1 rounded-full"
                        >
                            Default UK
                        </span>
                    </div>
                </div>
            </div>
        </template>
        <div v-else class="col-span-full text-center py-8">
            <div class="text-gray-500">
                <svg
                    class="mx-auto h-12 w-12 text-gray-400"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">
                    No props?.addresses
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Get started by creating a new props?.address.
                </p>
            </div>
        </div>
    </div>
    <hr />
</template>
