<script setup lang="ts">
import { Link } from "@inertiajs/vue3";
</script>

<template>
    <!-- Buttons -->
    <div
        class="mt-4 flex gap-1 items-center flex-wrap text-primary-500 justify-center"
    >
        <a
            href="/calculator"
            class="px-4 py-2 rounded border border-primary-500 hover:bg-primary-500 hover:text-white transition"
        >
            <i class="fa-solid fa-calculator"></i> Calculator
        </a>
        <Link
            :href="route('customer.account.addressBook')"
            class="px-4 py-2 rounded border border-primary-500 hover:bg-primary-500 hover:text-white transition"
        >
            <i class="fa-solid fa-address-book"></i> Address
        </Link>
    </div>
</template>
