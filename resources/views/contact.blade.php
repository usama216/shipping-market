@extends('layout.master')
@section('title', 'Contact')
@section('hide_header_bg', true)
@section('content')
    <section class="container pt-8 sm:pt-12 md:pt-20 space-y-8 sm:space-y-12" id="meettheteam">
        <header class="max-w-2xl px-4 sm:px-6 md:px-0 mx-auto text-center sm:pt-12 md:pt-20">
            <div class="px-4 pb-12 sm:pb-16 md:pb-20 sm:px-0">
                <div class="mx-auto text-center">
                    <h3 class="text-2xl sm:text-3xl md:text-4xl font-bold mb-3 sm:mb-4">Contact Marketsz</h3>
                    <p class="text-sm sm:text-base text-gray-600">Contact us today through our livechat or send us an email at <a
                            href="mailto:support@marketsz.com" class="text-primary hover:underline">support@marketsz.com</a></p>
                    <img src="{{ asset('assets') }}/image/contact-usjpg.jpg" alt="Contact Marketsz"
                        class="w-full h-auto rounded-lg mb-8" />
                </div>
            </div>
        </header>

    </section>
    <div class="mt-auto">
        <div class="relative pt-8 pb-40 mt-20 bg-rose-50 sm:pt-0 sm:mt-40">
            <img alt="Seahorse illustration" src="{{ asset('assets') }}/image/ballon.svg"
                class="absolute hidden w-auto h-40 md:-left-36 lg:left-2 xl:left-24 top-48 animate-float md:block">
            <img alt="Fish illustration" src="{{ asset('assets') }}/image/home/cartoon-plane.svg"
                class="absolute w-auto h-20 -mr-20 transform -translate-x-1/2 sm:h-24 bottom-8 right-1/2 md:-mr-0 md:right-2 md:top-1/2 md:-translate-y-1/2 lg:right-24 xl:right-32 animate-wiggle">
            <section class="container pt-8 sm:pt-12 md:pt-20 space-y-8 sm:space-y-12">
                <header class="max-w-2xl px-4 sm:px-6 md:px-0 mx-auto text-center sm:pt-12 md:pt-20">
                    <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold leading-snug">It's time to shop till you drop and get your
                        orders in record time!</h2>
                    <p class="mt-2 text-base sm:text-lg md:text-xl lg:text-2xl leading-snug">It's never been easier to shop from any online store
                        and get your packages delivered right at your door</p>
                </header>
                <div class="relative flex flex-col items-center w-full px-4 mx-auto -mb-2 -top-2 sm:px-0">
                    <div
                        class="flex flex-col flex-wrap items-center justify-center space-y-2 text-lg font-bold text-center sm:flex-row sm:space-y-0 sm:space-x-5 mb-7">
                        <span class="flex items-center space-x-1.5 whitespace-nowrap">
                            <div class="flex items-center justify-center w-5 h-5 text-white rounded-full bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div><span>2-4 Days Shipping</span>
                        </span>
                        <span class="flex items-center space-x-1.5 whitespace-nowrap">
                            <div class="flex items-center justify-center w-5 h-5 text-white rounded-full bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span>Combine &amp; Track</span>
                        </span>
                        <span class="flex items-center space-x-1.5 whitespace-nowrap">
                            <div class="flex items-center justify-center w-5 h-5 text-white rounded-full bg-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span>Live Support</span>
                        </span>
                    </div>
                    <template>
                        <form action="{{ route('register') }}" method="get" class="relative w-full max-w-sm"><input
                                type="hidden" name="country" value="">
                            {{-- <input type="email" name="email" placeholder="someone@mail.com"
                                class="w-full pl-4 text-base text-gray-900 placeholder-gray-500 bg-white border border-gray-300 rounded-full h-14 focus:ring-2 sm:text-lg"
                                aria-label="Enter your email address"> --}}
                            <button
                                class="absolute flex items-center h-10 px-4 font-bold text-white rounded-full bg-primary top-2 right-2">Get
                                started</button>
                        </form>
                    </template>

                    <form action="{{ route('register') }}" method="GET" id="revue-form" name="revue-form">
                        <div class="flex items-center mb-3">
                            <div class="relative w-full mr-3 revue-form-group">
                                <label for="member_email"
                                    class="hidden block mb-2 text-sm font-medium text-gray-900 dark:text-gray-300">Email
                                    address</label>
                                {{-- <input
                                    class="revue-form-field bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-black focus:border-black block w-72 pl-10 p-2.5"
                                    placeholder="Your email address..." type="email" name="member[email]" id="member_email"
                                    required=""> --}}
                            </div>
                            <div class="">
                                <button type="submit"
                                    class="bg-primary focus:ring-2 focus:ring-black font-medium rounded-lg text-white text-sm px-5 py-2.5 text-center"
                                    name="" id="" style="background-color:#9E1D22">Get Started</button>
                            </div>
                        </div>
                    </form>
                    <div class="flex items-center mt-2 text-sm text-center">Quick • Simple • Free</div>
                </div>
            </section>
        </div>

    </div>
@endsection