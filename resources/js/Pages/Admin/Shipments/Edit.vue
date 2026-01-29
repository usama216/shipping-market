<script setup>
import NavLink from "@/Components/NavLink.vue";

const props = defineProps({
    ship: Object,
});
</script>
<template>
    <div class="space-x-4">
        <NavLink
            :href="route('admin.shipments.edit', { ship: props?.ship?.id })"
            :active="route().current('admin.shipments.edit')"
        >
            info
        </NavLink>
        <NavLink
            :href="route('admin.shipments.packages', { ship: props?.ship?.id })"
            :active="route().current('admin.shipments.packages')"
        >
            Packages
        </NavLink>
    </div>
    <div class="mt-6">
        <slot />
    </div>
</template>
