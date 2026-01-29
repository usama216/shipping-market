<script setup>
import Footer from "@/Components/Footer.vue";
import ApplicationLogo from "@/Components/Images/Icons/ApplicationLogo.vue";
import Logo from "@/Components/Images/Icons/Logo.vue";
import { Link, usePage } from "@inertiajs/vue3";

const page = usePage();
const currentPath = page.url;
</script>

<template>
    <div class="flex flex-col bg-white">
        <!-- Navbar -->
        <nav
            class="flex items-center justify-between h-20 px-6 font-bold bg-white border-b text-md xl:text-lg"
        >
            <a href="/">
                <img
                    src="/assets/image/logo-original.svg"
                    alt="logo"
                    class="logo"
                    width="160"
                />
            </a>
            <div class="flex items-center text-gray-600">
                <Link
                    v-if="currentPath === '/login'"
                    class="flex items-center justify-center px-4 py-2 text-sm font-bold text-white rounded-full md:text-base whitespace-nowrap bg-primary-500"
                    :href="route('register')"
                >
                    Register
                </Link>
                <Link
                    v-else
                    class="flex items-center justify-center px-4 py-2 text-sm font-bold text-white rounded-full md:text-base whitespace-nowrap bg-primary-500"
                    :href="route('login')"
                >
                    Login
                </Link>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 w-full px-4 py-12">
            <slot />
        </main>

        <!-- Footer -->
        <!-- <footer
            class="px-4 py-8 text-xs text-center text-gray-600 bg-gray-100"
            style="background: linear-gradient(#f8f8f8, #fff)"
        >
            <div class="mb-6">
                <p>
                    Marketsz Impressum: Global Malls Access Ltd., 21 Priory
                    Office Park, Stillorgan, County Dublin, A94F660 Ireland
                    <br />
                    Registered in Dublin, Ireland, under registration number
                    685442
                </p>
            </div>
            <div>
                <a class="px-2 underline" href="terms.html">Terms of Service</a>
                &middot;
                <a class="px-2 underline" href="privacy.html">Privacy Policy</a>
            </div>
        </footer> -->
        <Footer />
    </div>
</template>
