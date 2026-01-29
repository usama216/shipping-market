<script setup>
import { Link } from "@inertiajs/vue3";
</script>
<template>
    <footer class="relative px-4 py-3 text-white bg-primary-500 md:px-0">
        <!-- <style>
            .footer-round {
                border-radius: 100% 100% 0 0;
            }
        </style> -->
        <div
            class="absolute left-0 right-0 z-10 h-6 overflow-hidden rounded-t-full footer-round -top-6 md:h-10 bg-primary-500"
        ></div>
        <div class="container">
            <nav
                class="flex flex-col items-center justify-between pt-0 pb-6 text-center md:flex-row md:text-left"
            >
                <div>
                    <a :href="route('web.home')"
                        ><img
                            class="logo"
                            src="assets/image/logo.svg"
                            alt="app-logo"
                    /></a>
                </div>
                <ul class="flex flex-col text-lg font-bold md:flex-row">
                    <li class="relative z-20 mx-1 lg:mx-2 xl:mx-6">
                        <a
                            class="border-b-2 border-transparent"
                            :href="route('web.about')"
                            >About</a
                        >
                    </li>
                    <li class="relative z-20 mx-1 lg:mx-2 xl:mx-6">
                        <a
                            class="border-b-2 border-transparent"
                            :href="route('web.contact')"
                            >Contact US</a
                        >
                    </li>
                    <li class="relative z-20 mx-1 lg:mx-2 xl:mx-6">
                        <a
                            class="border-b-2 border-transparent"
                            :href="route('web.calculator')"
                            >Cost Calculator</a
                        >
                    </li>
                    <li class="relative z-20 mx-1 lg:mx-2 xl:mx-6">
                        <a
                            class="border-b-2 border-transparent"
                            :href="route('web.faqs')"
                            >FAQ</a
                        >
                    </li>
                </ul>
                <div class="flex flex-col items-center sm:flex-row">
                    <ul class="flex">
                        <li class="px-2">
                            <a
                                href="https://www.facebook.com/Marketsz/"
                                target="_blank"
                                rel="noreferrer"
                                class="hover:scale-105"
                                ><i
                                    class="text-xl fab fa-facebook"
                                    aria-hidden="true"
                                ></i
                            ></a>
                        </li>
                        <li class="px-2">
                            <a
                                href="https://www.instagram.com/marketsz.official/"
                                target="_blank"
                                rel="noreferrer"
                                class="hover:scale-105"
                                ><i
                                    class="text-xl fab fa-instagram"
                                    aria-hidden="true"
                                ></i
                            ></a>
                        </li>
                        <li class="px-2">
                            <a
                                href="https://www.youtube.com/@marketsz.official"
                                target="_blank"
                                rel="noreferrer"
                                class="hover:scale-105"
                                ><i
                                    class="text-xl fab fa-youtube"
                                    aria-hidden="true"
                                ></i
                            ></a>
                        </li>
                    </ul>
                </div>
            </nav>
            <hr />
            <div
                class="pt-6 pb-12 text-sm text-center text-white text-opacity-75"
            >
                <div class="max-w-lg mx-auto">
                    <span class="">© All Rights Reserved - {{ 2025 }}</span>
                    <div class="mt-2 space-x-4">
                        <a
                            class="underline whitespace-nowrap hover:text-white"
                            :href="route('web.terms')"
                            >Terms of Service</a
                        >
                        <a
                            class="underline whitespace-nowrap hover:text-white"
                            :href="route('web.privacy')"
                            >Privacy Policy</a
                        >
                    </div>
                </div>
            </div>
        </div>
    </footer>
</template>
