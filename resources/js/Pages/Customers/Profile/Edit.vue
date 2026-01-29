<script setup>
import NavLink from "@/Components/NavLink.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
</script>
<template>
    <AuthenticatedLayout>
        <div class="space-x-4">
            <NavLink
                :href="route('customer.account.profile')"
                :active="route().current('customer.account.profile')"
                >Profile information</NavLink
            >
            <NavLink
                :href="route('customer.account.addressBook')"
                :active="route().current('customer.account.addressBook')"
                >Address Book</NavLink
            >
            <NavLink
                :href="route('customer.payment.paymentMethods')"
                :active="route().current('customer.payment.paymentMethods')"
                >Payment Methods</NavLink
            >
            <NavLink
                :href="route('customer.shippingPreferences.preference')"
                :active="
                    route().current('customer.shippingPreferences.preference')
                "
                >Shipping Preferences</NavLink
            >
        </div>
        <div class="mt-6">
            <slot />
        </div>
    </AuthenticatedLayout>
</template>
