@extends('layout.master')
@section('title', 'Calculator')
@section('hide_header_bg', true)
@section('content')
    <section class="container space-y-12">
        @include('includes.calculator-include')
    </section>
    <template>
        <section class="container pt-12 space-y-12 sm:pt-20">
            <header class="max-w-2xl px-4 mx-auto text-center sm:pt-20">
                <h2 class="text-3xl font-bold leading-snug sm:text-4xl">Transparent pricing</h2>
                <p class="mt-2 text-xl leading-snug sm:text-2xl">Find out about all costs involved.</p>
            </header>
            <div class="max-w-md p-5 mx-5 bg-white border shadow-lg sm:p-10 sm:mx-auto rounded-xl">
                <div class="text-xl font-bold text-center sm:text-2xl">Sample invoice</div>
                <div class="mt-3 sm:mt-6 sm:text-lg">
                    <div class="py-1">
                        <div class="flex items-baseline justify-between leading-snug">
                            <div class="">Shipping Fee</div>
                            <div class="relative pr-5 sm:pr-0">
                                <div class="font-bold text-right text-rose-500">from 18 US$</div>
                                <div
                                    class="absolute right-0 flex items-center justify-center w-6 h-6 -mt-3 -mr-1 cursor-pointer sm:-mr-6 top-1/2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" class="w-5 h-5 text-rose-500">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="py-1">
                        <div class="flex items-baseline justify-between leading-snug">
                            <div class="">MarketszHandling Fee</div>
                            <div class="relative sm:pr-0">
                                <div class="font-bold text-right undefined">10 US$</div>
                            </div>
                        </div>
                    </div>
                    <div class="py-1">
                        <div class="flex items-baseline justify-between leading-snug">
                            <div class="">Duties &amp; Taxes</div>
                            <div class="relative pr-5 sm:pr-0">
                                <div class="font-bold text-right text-orange-600">Due upon delivery</div>
                                <div
                                    class="absolute right-0 flex items-center justify-center w-6 h-6 -mt-3 -mr-1 cursor-pointer sm:-mr-6 top-1/2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" class="w-5 h-5 text-orange-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pt-3 pb-1 mt-3 border-t">
                        <div class="flex items-baseline justify-between leading-snug">
                            <div class="">Insurance</div>
                            <div class="relative pr-5 sm:pr-0">
                                <div class="font-bold text-right text-orange-600">Free or optional</div>
                                <div
                                    class="absolute right-0 flex items-center justify-center w-6 h-6 -mt-3 -mr-1 cursor-pointer sm:-mr-6 top-1/2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" class="w-5 h-5 text-orange-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="py-1">
                        <div class="flex items-baseline justify-between leading-snug">
                            <div class="">Third-Party Fees</div>
                            <div class="relative pr-5 sm:pr-0">
                                <div class="font-bold text-right text-orange-600">Depends</div>
                                <div
                                    class="absolute right-0 flex items-center justify-center w-6 h-6 -mt-3 -mr-1 cursor-pointer sm:-mr-6 top-1/2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" class="w-5 h-5 text-orange-600">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="pt-3 pb-1 mt-3 border-t">
                        <div class="flex items-baseline justify-between leading-snug">
                            <div class="">Membership Fee</div>
                            <div class="relative sm:pr-0">
                                <div class="font-bold text-right text-rose-500">FREE</div>
                            </div>
                        </div>
                    </div>
                    <div class="py-1">
                        <div class="flex items-baseline justify-between leading-snug">
                            <div class="">Repack &amp; Combine Service</div>
                            <div class="relative sm:pr-0">
                                <div class="font-bold text-right text-rose-500">FREE</div>
                            </div>
                        </div>
                    </div>
                    <div class="py-1">
                        <div class="flex items-baseline justify-between leading-snug">
                            <div class="">Single Repack Service</div>
                            <div class="relative sm:pr-0">
                                <div class="font-bold text-right text-rose-500">FREE</div>
                            </div>
                        </div>
                    </div>
                    <div class="py-1">
                        <div class="flex items-baseline justify-between leading-snug">
                            <div class="">Storage</div>
                            <div class="relative sm:pr-0">
                                <div class="font-bold text-right text-rose-500">FREE</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </template>
    <div class="mt-auto">
        <div class="relative pt-8 pb-40 mt-20 bg-rose-50 sm:pt-0 sm:mt-40">
            <img alt="Seahorse illustration" src="assets/image/ballon.svg"
                class="absolute hidden w-auto h-40 md:-left-36 lg:left-2 xl:left-24 top-48 animate-float md:block">
            <img alt="Fish illustration" src="assets/image/home/cartoon-plane.svg"
                class="absolute w-auto h-20 -mr-20 transform -translate-x-1/2 sm:h-24 bottom-8 right-1/2 md:-mr-0 md:right-2 md:top-1/2 md:-translate-y-1/2 lg:right-24 xl:right-32 animate-wiggle">
            <section class="container pt-12 space-y-12 sm:pt-20">
                <header class="max-w-2xl px-4 mx-auto text-center sm:pt-20">
                    <h2 class="text-3xl font-bold leading-snug sm:text-4xl">It’s time to shop till you drop and get your
                        orders in record time!</h2>
                    <p class="mt-2 text-xl leading-snug sm:text-2xl">It’s never been easier to shop from any online store
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


                    <a href="{{ route('register') }}"
                        class="bg-primary focus:ring-2 focus:ring-black font-medium rounded-lg text-white text-sm px-5 py-2.5 text-center"
                        name="" id="" style="background-color:#9E1D22">Get Register</a>
                    {{-- <div class="flex items-center mt-2 text-sm text-center">Quick • Simple • Free</div> --}}
                </div>
            </section>
        </div>
    </div>
    {{--
    <script src="{{ asset('assets/js/calculator.js') }}"></script> --}}
@endsection