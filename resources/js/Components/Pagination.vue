<script setup>
import { leftArrow, rightArrow } from "@js/Data/icon.js";
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    links: Array,
    from: Number,
    to: Number,
    total: Number,
    activeTabLink: {
        type: String,
        default: "",
    },
});

props.links[0].label = leftArrow;
props.links[props.links.length - 1].label = rightArrow;
const processedLinks = computed(() => {
    if (!props.links.length) return [];
    const updatedLinks = [...props.links];
    updatedLinks[0] = { ...updatedLinks[0], label: leftArrow };
    updatedLinks[updatedLinks.length - 1] = {
        ...updatedLinks[updatedLinks.length - 1],
        label: rightArrow,
    };
    return updatedLinks;
});
</script>
<template>
    <nav
        class="flex flex-col sm:flex-row items-center justify-between pt-4 px-4 sm:px-6 md:px-8 lg:px-10 xl:px-12 2xl:px-16 overflow-auto no-scrollbar"
    >
        <span class="text-sm font-normal text-gray-500"
            >Showing
            <span class="font-semibold text-gray-900"
                >{{ from }} - {{ to }}</span
            >
            of
            <span class="font-semibold text-gray-900">{{ total }}</span></span
        >
        <ul
            class="inline-flex flex-wrap -space-x-px text-base h-10 mt-2 sm:mt-0"
        >
            <li v-for="link in processedLinks">
                <Link
                    v-if="link.active"
                    :href="`${link.url || ''}${props.activeTabLink}`"
                    v-html="link.label"
                    class="flex items-center justify-center align-middle text-center px-3 text-white bg-primary-500 hover:bg-primary-800 hover:text-white rounded-full transition-colors mx-1 h-8 min-w-8"
                >
                </Link>
                <Link
                    v-else
                    :href="`${link.url || ''}${props.activeTabLink}`"
                    v-html="link.label"
                    class="flex items-center justify-center px-3 align-middle text-center text-gray-500 bg-white hover:bg-gray-100 hover:text-gray-700 rounded-full transition-colors mx-1 h-8 min-w-8"
                >
                </Link>
            </li>
        </ul>
    </nav>
</template>
