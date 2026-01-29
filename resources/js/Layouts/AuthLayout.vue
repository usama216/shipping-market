<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/Images/Icons/ApplicationLogo.vue';

defineProps({
    title: String,
    heading: String,
    subheading: String,
});
</script>

<template>
    <div class="flex min-h-screen bg-white">
        <!-- Left Side: Branding / Marketing -->
        <div class="hidden w-1/2 lg:flex flex-col relative overflow-hidden bg-slate-900">
             <!-- Background / Hero -->
             <div class="absolute inset-0 z-0">
                <img src="/assets/image/home/ship.svg" class="absolute bottom-0 left-0 w-3/4 opacity-20 transform -translate-x-10 translate-y-10" alt="Ship" />
                <div class="absolute inset-0 bg-gradient-to-br from-primary-900/90 to-slate-900/90"></div>
             </div>

             <!-- Content -->
             <div class="relative z-10 flex flex-col justify-between h-full p-12 text-white">
                <div class="flex items-center gap-3">
                     <!-- Logo (White version or filter) -->
                     <a href="/" class="cursor-pointer">
                         <img src="/assets/image/logo-original.svg" alt="Marketsz" class="h-10 brightness-0 invert" />
                     </a>
                </div>

                <div class="mb-12">
                    <h2 class="text-4xl font-bold tracking-tight leading-tight mb-4">Global Shopping,<br />Simplified.</h2>
                    <p class="text-lg text-slate-300 max-w-md">
                        Get your tax-free USA shipping address instantly. Shop from your favorite US stores and we'll ship it to your doorstep.
                    </p>
                </div>

                <div class="flex gap-6 text-sm text-slate-400">
                    <a href="#" class="hover:text-white transition">Terms</a>
                    <a href="#" class="hover:text-white transition">Privacy</a>
                    <a href="#" class="hover:text-white transition">Help Center</a>
                </div>
             </div>
        </div>

        <!-- Right Side: Form -->
        <div class="w-full lg:w-1/2 flex flex-col relative bg-white">
            <!-- Mobile Header (Logo) -->
            <div class="lg:hidden p-6 flex justify-center border-b border-gray-100">
                 <a href="/" class="cursor-pointer">
                     <img src="/assets/image/logo-original.svg" alt="Marketsz" class="h-8" />
                 </a>
            </div>

            <div class="flex-1 flex flex-col justify-center px-6 py-12 sm:px-12 lg:px-24 overflow-y-auto">
                <div class="w-full max-w-md mx-auto">
                    <!-- Header -->
                    <div class="mb-8 text-center lg:text-left">
                        <h1 v-if="heading" class="text-2xl font-bold text-gray-900">{{ heading }}</h1>
                        <p v-if="subheading" class="mt-2 text-gray-600">{{ subheading }}</p>
                    </div>

                    <!-- Slot for Login/Register Form -->
                    <slot />
                </div>
            </div>
            
             <!-- Back to Home / Alternative Actions -->
            <div class="absolute top-6 right-6 hidden lg:block">
                <a href="/" class="text-sm font-medium text-gray-500 hover:text-primary-600 transition">
                    &larr; Back to Home
                </a>
            </div>
        </div>
    </div>
</template>
