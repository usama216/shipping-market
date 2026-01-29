<script setup>
import NavLink from "@/Components/NavLink.vue";

const props = defineProps({
    user: Object,
});
</script>
<template>
    <div class="space-x-4">
        <NavLink
            :href="route('admin.customers.edit', props?.user?.id)"
            :active="route().current('admin.customers.edit')"
        >
            Info
        </NavLink>
    </div>

    <!-- Page Content -->
    <div class="mt-6">
        <slot />
    </div>
</template>
