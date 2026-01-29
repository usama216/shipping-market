<script setup>
import NavLink from "@/Components/NavLink.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";

const props = defineProps({
    package: Object,
    customers: Object,
});
</script>

<template>
    <Head title="Edit Package" />
    <AuthenticatedLayout>
        <div class="space-x-4">
            <NavLink
                :href="route('admin.packages.edit', props?.package?.id)"
                :active="route().current('admin.packages.edit')"
                >Edit</NavLink
            >
        </div>
        <div class="mt-6">
            <slot />
        </div>
    </AuthenticatedLayout>
</template>
