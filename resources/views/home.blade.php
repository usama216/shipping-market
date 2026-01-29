@extends('layout.master')
@section('title', 'Get a USA Tax-Free Shipping Address | Ship to All Caribbean Islands | 2-4 Days Shipping | Marketsz')
@section('content')
    {{-- OLD HEADER COMMENTED OUT
    <header class="relative w-full overflow-hidden text-white bg-[#9E1D22]">
        <img class="header-bg" src="assets/image/home/background.svg" alt="background" width="100%">
        <header class="pt-16 space-y-12 container-fluid sm:pt-32">
            <div class="relative z-10 mx-auto mt-12 max-w-7xl">
                <img src="assets/image/home/airplane.svg" alt="EU illustration" class="z-10 -mb-px xl:mx-0 airplane">
                <div class="flex items-end justify-between">
                    <img src="assets/image/home/us-with-plane.svg" alt="U.S. illustration" style='width:300px'>
                    <img src="assets/image/home/island.svg" alt="Island illustration" class="city">
                    <img src="{{ asset('assets') }}/image/home/blue-dolphin.svg" alt="Dolphin"
                        class="z-10 -mb-px dolphin city xl:mx-0">
                    <div class="w-full">
                        <img src="{{ asset('assets') }}/image/home/ship.svg" alt="Ship"
                            class="z-10 -mb-px xl:mx-0 city ship">
                    </div>
                    <img src="assets/image/home/home2.svg" alt="EU illustration" class="city">
                </div>
            </div>
            <div class="px-4 mx-auto text-center sm:px-0 max-w-7xl">
                <h1 class="text-4xl font-bold leading-snug text-white sm:text-10xl">Get Your Tax Free USA Shipping
                    Address for the
                    Caribbean</h1>
                <div class="block mx-auto text-4xl font-bold">From the US to The Caribbean</div>
                <p class="mx-auto mt-3 text-xl sm:text-2xl">
                    Shop at any online store using your unique and personal Marketsz USA Address and we quickly ship your
                    orders to your home or business in the Caribbean.
                </p>
            </div>
            <div class="relative flex flex-col items-center w-full px-4 mx-auto -mb-2 -top-2 sm:px-0">
                <div
                    class="flex flex-col flex-wrap items-center justify-center space-y-2 text-lg font-bold text-center sm:flex-row sm:space-y-0 sm:space-x-5 mb-7">
                    <span class="flex items-center space-x-1.5 whitespace-nowrap">
                        <div class="flex items-center justify-center w-5 h-5 text-green-500 bg-white rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                        </div>
                        <span>2-4 Hassle Free Shipping</span>
                    </span>
                    <span class="flex items-center space-x-1.5 whitespace-nowrap">
                        <div class="flex items-center justify-center w-5 h-5 text-green-500 bg-white rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                        </div>
                        <span>Combine and Tracking</span>
                    </span>
                    <span class="flex items-center space-x-1.5 whitespace-nowrap">
                        <div class="flex items-center justify-center w-5 h-5 text-green-500 bg-white rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                class="w-4 h-4">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                        </div>
                        <span>24/7 Support</span>
                    </span>
                </div>
                <template>
                    <form action="register.html" method="get" class="relative w-full max-w-sm">
                        <input type="hidden" name="country" value="">
                        <input type="email" name="email" placeholder="someone@mail.com"
                            class="w-full pl-4 text-base text-gray-900 placeholder-gray-500 bg-white border-none rounded-full h-14 focus:ring-2 sm:text-lg"
                            aria-label="Enter your email address">
                        <button
                            class="absolute flex items-center h-10 px-4 font-bold text-white rounded-full bg-primary top-2 right-2 focus:ring-2 focus:ring-black">Get
                            Started</button>
                    </form>
                </template>
                <form action="{{ route('register') }}" method="GET" id="revue-form" name="revue-form">
                    <div class="flex items-center mb-3">

                        <div class="revue-form-actions">
                            <input type="submit" value="Get started"
                                class="cursor-pointer bg-gray-50 focus:ring-2 focus:ring-black font-medium rounded-lg text-rose-900 text-sm px-5 py-2.5 text-center"
                                name="member[subscribe]" id="member_submit">
                        </div>
                    </div>
                </form>
            </div>
        </header>

        <img src="{{ asset('assets') }}/image/home/box.svg" alt="floating icon"
            class="absolute h-12 -ml-64 opacity-50 top-28 left-1/2">
        <img src="{{ asset('assets') }}/image/home/box.svg" alt="floating icon"
            class="absolute h-8 mt-2 ml-64 opacity-25 top-32 left-1/2">
        <img src="{{ asset('assets') }}/image/home/box.svg" alt="floating icon"
            class="absolute h-10 mr-8 opacity-50 right-8 top-64">
        <img src="{{ asset('assets') }}/image/home/box.svg" alt="floating icon"
            class="absolute h-8 opacity-25 top-80 left-20">

    </header> --}}

    <!-- Hero Section -->
    <section class="hero-outer">
        <div class="max-width-container">
            <img src="{{ asset('assets/images/cloud-1.svg') }}" alt="cloud illustration" class="hero-cloud hero-cloud-1" />
            <img src="{{ asset('assets/images/cloud-2.svg') }}" alt="cloud illustration" class="hero-cloud hero-cloud-2" />
            <img src="{{ asset('assets/images/cloud-3.svg') }}" alt="cloud illustration" class="hero-cloud hero-cloud-3" />
            <img src="{{ asset('assets/images/cloud-4.svg') }}" alt="cloud illustration" class="hero-cloud hero-cloud-4" />
            <img src="{{ asset('assets/images/cloud-5.svg') }}" alt="cloud illustration" class="hero-cloud hero-cloud-5" />

            <img src="{{ asset('assets/images/USA landmarks.svg') }}" alt="" class="hero-buildings-first" />
            <img src="{{ asset('assets/images/home.svg') }}" alt="" class="hero-buildings-second" />
                <img src="{{ asset('assets/images/uk landmarks.svg') }}" alt="" class="hero-buildings-third" />
            <img src="{{ asset('assets/images/black-crow.svg') }}" alt="bird illustration" class="hero-black-crow hero-black-crow-1" />
            <img src="{{ asset('assets/images/black-crow.svg') }}" alt="bird illustration" class="hero-black-crow hero-black-crow-2" />
            <img src="{{ asset('assets/images/black-crow.svg') }}" alt="bird illustration" class="hero-black-crow hero-black-crow-3" />
            <img src="{{ asset('assets/images/black-crow.svg') }}" alt="bird illustration" class="hero-black-crow hero-black-crow-4" />
            <img src="{{ asset('assets/images/black-crow.svg') }}" alt="bird illustration" class="hero-black-crow hero-black-crow-5" />
        
            <div class="hero-heading-and-content-outer">
                <div class="hero-heading-and-content-inner">
                    <h1>
                        Get Your <span>USA</span> Tax-Free Shipping Address for
                        the Caribbean
                    </h1>
                    <p>
                        Shop on any online store using your unique and personal Marketsz
                        USA Shipping Address and we deliver your orders to your
                        doorstep at your home or business in the Caribbean.
                    </p>
                    <div class="hero-check-and-text">
                        <div>
                            <img src="{{ asset('assets/images/check-icon-circle.svg') }}" alt="" />
                            <span>2-4 Days Delivery</span>
                        </div>
                        <div>
                            <img src="{{ asset('assets/images/check-icon-circle.svg') }}" alt="" />
                            <span>Unlimited Consolidation</span>
                        </div>
                        <div>
                            <img src="{{ asset('assets/images/check-icon-circle.svg') }}" alt="" />
                            <span>Live Support</span>
                        </div>
                    </div>
                    <div class="hero-get-started-button">
                        <a href="{{ route('register') }}">
                            <button>Get Started</button>
                        </a>
                    </div>
                </div>
            </div>

            <svg width="100%" height="100%" viewBox="0 0 1000 500" fill="none" xmlns="http://www.w3.org/2000/svg"
                class="hero-path-outer">
                <g clip-path="url(#clip0_412_89)">
                    <path class="path" d="M0 250.5C139.5 211.5 259.2 91 500 91C740.8 91 861 213 1000 250.5" stroke="#0000" />
                </g>
            </svg>

            <img src="{{ asset('assets/images/hero-aeroplane.svg') }}" alt="" class="hero-aeroplane" />
            <img src="{{ asset('assets/images/hero-aeroplane-2-flipped.svg') }}" alt="" class="hero-aeroplane-2" />
            <img src="{{ asset('assets/images/tts-box.svg') }}" alt="" width="50px" class="hero-box rotating-box" />
        </div>
    </section>

    <!-- Ready to Shipping Section -->
    <section class="ready-to-shipping">
        <div class="max-width-container">
            <img src="{{ asset('assets/images/gift-parachute.svg') }}" alt="gift parachute illustration" class="rts-shipping-1 rotating-box" />
            <div class="rts-text-and-button">
                <h2>Ready to start shipping with Marketsz?</h2>
                <p>Sign up now and get your instant forwarding address</p>
                <button><a href="{{ route('register') }}">Get started now</a></button>
            </div>
            <img src="{{ asset('assets/images/dotted-circle.svg') }}" alt="" class="rts-shipping-2 rotating-circle" />
            <img src="{{ asset('assets/images/aeroplane.svg') }}" alt="aeroplane illustration" class="rts-shipping-3 rotating-aeroplane" />
        </div>
    </section>

    <!-- Hassle-Free Solution Section -->
    <section class="hassle-free-solution-outer">
        <div class="max-width-container">
            <div class="hassle-free-heading-and-para">
                <h2>Your Hassle-Free Solution!</h2>
                <p>Shop Globally, Ship Effortlessly with Marketsz</p>
            </div>
            <img src="{{ asset('assets/images/hassle-free-cart.svg') }}" alt="animated cart image"
                class="hassle-free-animated-cart animated-cart-animation" />
            <section class="hassle-free-blue-badges-container" style="display: none">
                <div class="hassle-free-blue-badge hassle-free-blue-badge-1">
                    <span>User-Friendly Dashboard</span>
                </div>
                <div class="hassle-free-blue-badge hassle-free-blue-badge-2">
                    <span>Express Shipping</span>
                </div>
                <div class="hassle-free-blue-badge hassle-free-blue-badge-3">
                    <span>Swift Shopping, Sure Shipping</span>
                </div>
                <div class="hassle-free-blue-badge hassle-free-blue-badge-4">
                    <span>24/7 Live Support</span>
                </div>
                <div class="hassle-free-blue-badge hassle-free-blue-badge-5">
                    <span>Free Storage</span>
                </div>
                <div class="hassle-free-blue-badge hassle-free-blue-badge-6">
                    <span>Doorstep Delivery</span>
                </div>
                <div class="hassle-free-blue-badge hassle-free-blue-badge-7">
                    <span>Consolidation Service</span>
                </div>
            </section>

            <div class="hf-destination-card hf-destination-card-1">
                <div class="hf-destination-card-black-nums hf-destination-card-black-num-1">
                    <span>1</span>
                </div>
                <img src="{{ asset('assets/images/hf-rectangle-1.svg') }}" alt="rectangle illustration" />
                <span class="hf-rectangle-text hf-rectangle-text-1">Get a USA shipping address</span>
                <img src="{{ asset('assets/images/hf-location-icon-1.svg') }}" alt="location icon" class="hf-location-icon hf-location-icon-1" />
            </div>

            <div class="hf-destination-card hf-destination-card-2">
                <div class="hf-destination-card-black-nums hf-destination-card-black-num-2">
                    <span>2</span>
                </div>
                <img src="{{ asset('assets/images/hf-rectangle-2.svg') }}" alt="rectangle illustration" />
                <span class="hf-rectangle-text hf-rectangle-text-2">Shop from any online store</span>
                <img src="{{ asset('assets/images/hf-location-icon-2.svg') }}" alt="location icon" class="hf-location-icon hf-location-icon-2" />
            </div>

            <div class="hf-destination-card hf-destination-card-3">
                <div class="hf-destination-card-black-nums hf-destination-card-black-num-3">
                    <span>3</span>
                </div>
                <img src="{{ asset('assets/images/hf-rectangle-3.svg') }}" alt="rectangle illustration" />
                <span class="hf-rectangle-text hf-rectangle-text-3">Consolidate & send to your island</span>
                <img src="{{ asset('assets/images/hf-location-icon-3.svg') }}" alt="location icon" class="hf-location-icon hf-location-icon-3" />
                <div class="hf-partners-logos">
                    <h4 class="color-primary">Partners:</h4>
                    <img src="{{ asset('assets/images/fedex-logo.png') }}" alt="" />
                    <img src="{{ asset('assets/images/dhl-logo.png') }}" alt="" />
                    <img src="{{ asset('assets/images/ups-logo.png') }}" width="27px" alt="" />
                </div>
                <img class="hf-c3-plane" src="{{ asset('assets/images/su-aeroplane.svg') }}" alt="" />
            </div>

            <img src="{{ asset('assets/images/hf-warehouse-icon.svg') }}" alt="warehouse illustration" class="hf-warehouse-icon" />

            <svg width="2" height="60" viewBox="0 0 2 60" fill="none" xmlns="http://www.w3.org/2000/svg" id="hf-curve-1">
                <path id="hf-curve-1-path" d="M1 0L1 60" stroke="#FFF1F2" stroke-width="2" />
            </svg>

            <svg width="654" height="165" viewBox="0 0 654 165" fill="none" xmlns="http://www.w3.org/2000/svg"
                id="hf-curve-2">
                <path id="hf-curve-2-path"
                    d="M3.49878 0.5C-7.33455 38.1667 17.6988 101.4 204.499 53C437.999 -7.5 529.5 12.5 653 163.5" stroke="#FFF1F2"
                    stroke-width="2" />
            </svg>

            <svg width="701" height="293" viewBox="0 0 701 293" fill="none" xmlns="http://www.w3.org/2000/svg"
                id="hf-curve-3">
                <path id="hf-curve-3-path"
                    d="M6.76266 1C-21.6328 89.8995 55.6285 178.709 222.649 182.009C223.94 182.035 269.129 180.551 270.412 180.414V180.414C319.227 175.172 367.345 195.18 398.051 233.488L399.804 235.675C432.104 275.972 482.966 296.67 534.225 290.379L700.265 270"
                    stroke="#FFF1F2" stroke-width="2" />
            </svg>

            <div class="hf-cargo-track-container">
                <img src="{{ asset('assets/images/hf-cargo-track.svg') }}" alt="cargo track illustration" class="hf-cargo-track" />
                <img src="{{ asset('assets/images/hf-cargo-box-1.svg') }}" alt="cargo box illustration" class="hf-cargo-box hf-cargo-box-1" />
                <img src="{{ asset('assets/images/hf-cargo-box-2.svg') }}" alt="cargo box illustration" class="hf-cargo-box hf-cargo-box-2" />
                <img src="{{ asset('assets/images/hf-cargo-box-3.svg') }}" alt="cargo box illustration" class="hf-cargo-box hf-cargo-box-3" />
                <img src="{{ asset('assets/images/hf-cargo-box-4.svg') }}" alt="cargo box illustration" class="hf-cargo-box hf-cargo-box-4" />
                <img src="{{ asset('assets/images/hf-cargo-box-5.svg') }}" alt="cargo box illustration" class="hf-cargo-box hf-cargo-box-5" />
            </div>

            <div class="hf-destination-card hf-destination-card-4">
                <div class="hf-destination-card-black-nums hf-destination-card-black-num-4">
                    <span>4</span>
                </div>
                <img src="{{ asset('assets/images/hf-couple-with-poster.svg') }}" alt="couple with poster illustration"
                    class="hf-couple-with-poster" />
                <img src="{{ asset('assets/images/hf-rectangle-4.svg') }}" alt="rectangle illustration" />
                <span class="hf-rectangle-text hf-rectangle-text-4">Fast & Worry-Free Delivery</span>
                <img src="{{ asset('assets/images/hf-location-icon-4.svg') }}" alt="location icon" class="hf-location-icon hf-location-icon-4" />
            </div>

            <a href="{{ route('web.calculator') }}">
                <button class="hf-learn-more-btn">Learn more</button>
            </a>
        </div>
    </section>

    <!-- section 3 - Mobile -->
    <section class="mbl-hassle-free-solution-outer">
        <img src="{{ asset('assets/images/hassle-free-cart.svg') }}" alt="animated cart image" class="mbl-hassle-free-animated-cart" />
        <div class="hassle-free-heading-and-para">
            <h2>Your Hassle-Free Solution!</h2>
            <p>Shop Globally, Ship Effortlessly with Marketsz</p>
        </div>
        <div class="mbl-hf-destination-cards-container">
            <div class="mbl-hf-destination-card mbl-hf-destination-card-1">
                <div class="hf-destination-card-black-nums mbl-hf-destination-card-black-nums-1">
                    <span>1</span>
                </div>
                <img src="{{ asset('assets/images/hf-location-icon-1.svg') }}" alt="" />
                <span>Get a USA shipping address</span>
            </div>

            <div class="mbl-hf-c3-plane-container">
                <img src="{{ asset('assets/images/mbl-su-aeroplane.svg') }}" alt="" class="mbl-hf-icons mbl-hf-c3-plane" />

                <svg width="259" height="206" viewBox="0 0 259 206" fill="none" xmlns="http://www.w3.org/2000/svg"
                    id="mbl-hf-curve-right-1">
                    <path id="mbl-hf-curve-right-1-path" d="M258 0C258 97.5 1 45 1 205.5" stroke="#FFF1F2" stroke-width="2" />
                </svg>
            </div>

            <div class="mbl-hf-destination-card mbl-hf-destination-card-2">
                <div class="hf-destination-card-black-nums mbl-hf-destination-card-black-nums-2">
                    <span>2</span>
                </div>
                <img src="{{ asset('assets/images/hf-location-icon-2.svg') }}" alt="" />
                <span>Shop from any online store</span>
            </div>

            <div class="mbl-hf-cargo-box-container">
                <img src="{{ asset('assets/images/mbl-hf-cargo-box.svg') }}" alt="cargo box illustration" class="mbl-hf-icons mbl-hf-cargo-box" />

                <svg width="259" height="206" viewBox="0 0 259 206" fill="none" xmlns="http://www.w3.org/2000/svg"
                    id="mbl-hf-curve-left-1">
                    <path id="mbl-hf-curve-left-1-path" d="M1 0C1 97.5 258 45 258 205.5" stroke="#FFF1F2" stroke-width="2" />
                </svg>
            </div>

            <div class="mbl-hf-destination-card mbl-hf-destination-card-3">
                <div class="hf-destination-card-black-nums mbl-hf-destination-card-black-nums-3">
                    <span>3</span>
                </div>
                <img src="{{ asset('assets/images/hf-location-icon-3.svg') }}" alt="" />
                <span>Consolidate & send to your island</span>
                <div class="mbl-hf-destination-card-3-partners-outer">
                    <span>Partners:</span>
                    <img src="{{ asset('assets/images/hf-fedX-logo.svg') }}" alt="" />
                    <img src="{{ asset('assets/images/hf-dhl-logo.svg') }}" alt="" />
                    <img src="{{ asset('assets/images/ups-logo.png') }}" width="27px" alt="UPS logo" />
                </div>
            </div>

            <div class="mbl-hf-warehouse-icon-container">
                <img src="{{ asset('assets/images/hf-warehouse-icon.svg') }}" alt="warehouse illustration"
                    class="mbl-hf-icons mbl-hf-warehouse-icon" />

                <svg width="259" height="206" viewBox="0 0 259 206" fill="none" xmlns="http://www.w3.org/2000/svg"
                    id="mbl-hf-curve-right-2">
                    <path id="mbl-hf-curve-right-1-path" d="M258 0C258 97.5 1 45 1 205.5" stroke="#FFF1F2" stroke-width="2" />
                </svg>
            </div>

            <div class="mbl-hf-destination-card mbl-hf-destination-card-4">
                <div class="hf-destination-card-black-nums mbl-hf-destination-card-black-nums-4">
                    <span>4</span>
                </div>
                <img src="{{ asset('assets/images/hf-location-icon-4.svg') }}" alt="" />
                <span>Fast & Worry-Free Delivery</span>
            </div>
        </div>

        <img src="{{ asset('assets/images/hf-couple-with-poster.svg') }}" alt="" class="mbl-hf-couple-img" />
        <section class="mbl-hassle-free-blue-badges-container">
            <div class="mbl-hassle-free-blue-badge mbl-hassle-free-blue-badge-1">
                <span>User-Friendly Dashboard</span>
            </div>
            <div class="mbl-hassle-free-blue-badge mbl-hassle-free-blue-badge-2">
                <span>Express Shipping</span>
            </div>
            <div class="mbl-hassle-free-blue-badge mbl-hassle-free-blue-badge-3">
                <span>Swift Shopping, Sure Shipping</span>
            </div>
            <div class="mbl-hassle-free-blue-badge mbl-hassle-free-blue-badge-4">
                <span>24/7 Live Support</span>
            </div>
            <div class="mbl-hassle-free-blue-badge mbl-hassle-free-blue-badge-5">
                <span>Free Storage</span>
            </div>
            <div class="mbl-hassle-free-blue-badge mbl-hassle-free-blue-badge-6">
                <span>Doorstep Delivery</span>
            </div>
            <div class="mbl-hassle-free-blue-badge mbl-hassle-free-blue-badge-7">
                <span>Consolidation Service</span>
            </div>
        </section>
        <a href="{{ route('web.calculator') }}">
            <button class="mbl-hf-learn-more-btn">Learn more</button>
        </a>
    </section>

    <!-- Save Up to 80% Section -->
    <section class="save-upto-outer">
        <div class="max-width-container">
            <div class="save-upto-inner"></div>
            <div class="su-heading-and-para">
                <h2><span>"Save upto 80%</span> off with our Low Shipping Rates"</h2>
                <p>
                    Use our calculator to estimate exactly how much it would cost you to
                    ship.
                </p>
            </div>
            <section class="su-cart-and-location">
                <div>
                    <img src="{{ asset('assets/images/hassle-free-cart.svg') }}" alt="animated cart icon" class="animated-cart-animation-2" />
                </div>
                <div class="su-location-outer">
                    <h5>Where should we send your package?</h5>
                    <span class="su-carribbean-span">We forward to the Caribbean</span>
                    <div class="calculator-country-select-container">
                        <img class="cal-location-pointer" src="{{ asset('assets/images/location-pointer.svg') }}" alt="" />
                        <div class="dd-icon-container">
                            <img src="{{ asset('assets/images/su-down-arrow.svg') }}" alt="" />
                        </div>
                        <select id="country-dropdown" name="SelectedCountry" class="calculator-country-select">
                            <option value="">Select a Country</option>
                            @php
                                $countries = \App\Models\Country::active()->ordered()->get();
                            @endphp
                            @foreach($countries as $country)
                                <option value="{{ $country->name }}">{{ $country->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </section>
            <section class="how-much-cost-outer">
                <div class="how-much-cost-inner">
                    <h4>How much does it cost?</h4>
                    <p>Enter your dimensions for a more accurate quote.</p>
                    <div class="hmc-dimentions-and-weight-outer">
                        <div class="hmc-dimentions">
                            <div class="hmc-dimentions-title-and-unit">
                                <h6>Dimensions</h6>
                                <div class="hmc-units-outer hmc-dimensions-unit">
                                    <div class="hmc-in dim-selected">in</div>
                                    <div class="hmc-cm">cm</div>
                                </div>
                            </div>
                            <div class="hmc-dimentions-outer">
                                <div>
                                    <label for="cal-length">Length</label>
                                    <input id="cal-length" type="text" />
                                </div>
                                <div>
                                    <label for="cal-width">Width</label>
                                    <input id="cal-width" type="text" />
                                </div>
                                <div>
                                    <label for="cal-height">Height</label>
                                    <input id="cal-height" type="text" />
                                </div>
                            </div>
                        </div>
                        <div class="hmc-wight">
                            <div class="hmc-wight-title-and-unit">
                                <h6>Weight</h6>
                                <div class="hmc-units-outer hmc-wight-unit">
                                    <div class="hmc-in wu-selected">kg</div>
                                    <div class="hmc-cm">lbs</div>
                                </div>
                            </div>
                            <div class="hmc-wight-outer">
                                <div>
                                    <label for="cal-weight" id="wight-label">Weight in KG</label>
                                    <input id="cal-weight" type="text" />
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="calulator-discliamer">
                        *While 99% of shipments are calculated by weight only, this
                        excludes packages with linear dimensions (length + width + height)
                        greater than 72 inches (183 cm).
                    </div>
                    <div class="get-price-btn-container">
                        <button class="get-price-btn" id="homePageCostEstimation">Get Price Estimate</button>
                    </div>
                    <div class="home-calculator-result" style="margin-top: 20px;"></div>
                </div>
                <div class="hmc-dimentions-pics-container">
                    <img src="{{ asset('assets/images/su-aeroplane.svg') }}" alt="aeroplane illustration" class="su-aeroplane" />
                    <img src="{{ asset('assets/images/su-boxes.svg') }}" alt="boxes illustration" class="su-boxes" />
                </div>
            </section>
        </div>
    </section>

    <!-- Where Can I Shop Section -->
    <section class="where-can-i-shop-outer">
        <div class="max-width-container">
            <div class="partners-logos-carousel partners-logos-carousel-upper">
                <div class="slide-track"></div>
            </div>
            <section class="where-can-i-shop-inner">
                <h3>Where can I <span>shop?</span></h3>
                <p>
                    Marketsz works with all online stores that ship to the US. When shopping online simply use your personal US Marketsz
                    address as your shipping address.
                </p>
                <span>These are some of our members' favorite stores!</span>
            </section>
            <div class="partners-logos-carousel partners-logos-carousel-lower">
                <div class="slide-track"></div>
            </div>
        </div>
    </section>

    <!-- Free Benefits Section -->
    <section class="free-benifits-outer">
        <div class="max-width-container">
            <h3><span>Free benefits</span> you will <span>love!</span></h3>
            <p>
                We go the extra mile to give you a seamless shipping experience,
                hassle free!
            </p>
            <div class="fb-text-passage fb-text-passage-1">
                <p>
                    <span>Swift doorstep delivery:</span> Shop, we ship, and your
                    packages arrive in 2-4 days!
                </p>
            </div>
            <div class="fb-text-passage fb-text-passage-2">
                <p>
                    <span>Track, snap, deliver:</span> See your items, know their
                    location, and get them ASAP!
                </p>
            </div>
            <div class="fb-text-passage fb-text-passage-3">
                <p>
                    <!-- <span>Free 30-Day Storage:</span>  -->
                    <span>Free 90 days storage:</span> No worries! We'll
                    store your items for 90 days at no cost.
                </p>
            </div>
            <img src="{{ asset('assets/images/tts-box.svg') }}" alt="balloon illustration" class="fb-big-balloon rotating-box" />
            <img src="{{ asset('assets/images/tts-box.svg') }}" alt="balloon illustration"
                class="fb-small-balloon fb-small-balloon-1 rotating-box" />
            <img src="{{ asset('assets/images/tts-box.svg') }}" alt="balloon illustration"
                class="fb-small-balloon fb-small-balloon-2 rotating-box" />
            <img src="{{ asset('assets/images/tts-box.svg') }}" alt="balloon illustration"
                class="fb-small-balloon fb-small-balloon-3 rotating-box" />
            <img src="{{ asset('assets/images/fb-aeroplane.svg') }}" alt="aeroplane illustration" class="fb-aeroplane rotating-aeroplane" />
        </div>
        <div class="forklift-animation-container" id="home-forklift-animation-container">
            <img class="forklift-machine-img" src="{{ asset('assets/images/forklift-machine.svg') }}" alt="" />
            <div class="road-imgs-container">
                <img class="forklift-road-img forklift-road-img--1" src="{{ asset('assets/images/road-line.svg') }}" alt="" />
                <img class="forklift-road-img forklift-road-img--2" src="{{ asset('assets/images/road-line.svg') }}" alt="" />
            </div>
        </div>
    </section>

    <section class="container pt-12 space-y-12 sm:pt-20">
        <header class="max-w-2xl px-4 mx-auto text-center sm:pt-20">
            <h2 class="text-3xl font-bold leading-snug sm:text-4xl">Hassle-Free Worldwide Shopping and Shipping
            </h2>
            <p class="mt-2 text-xl leading-snug sm:text-2xl">Here’s how Marketsz Works</p>
        </header>
        <div class="relative">
            <div class="relative z-10 grid grid-cols-1 gap-12 px-4 md:grid-cols-3 sm:px-0">
                <div class="space-y-5 text-center">
                    <div
                        class="flex items-center justify-center w-10 h-10 mx-auto text-xl font-bold text-white rounded-full bg-primary">
                        1</div>
                    <div><img src="{{ asset('assets') }}/image/home/home.svg" alt="" class="mx-auto shipment-icon">
                    </div>
                    <div class="text-xl leading-tight">Get a <strong>Marketsz USA Shipping Address</strong>
                        instantly when you sign up</div>
                </div>
                <div class="space-y-5 text-center">
                    <div
                        class="flex items-center justify-center w-10 h-10 mx-auto text-xl font-bold text-white rounded-full bg-primary">
                        2</div>
                    <div><img src="{{ asset('assets') }}/image/home/cart.svg" alt="" class="mx-auto shipment-icon">
                    </div>
                    <div class="text-xl leading-tight">Shop at ANY online store using your <strong>Marketsz
                            unique USA Address</strong> as your shipping address</div>
                </div>
                <div class="space-y-0 text-center">
                    <div
                        class="flex items-center justify-center w-10 h-10 mx-auto text-xl font-bold text-white rounded-full bg-primary">
                        3</div>
                    <div class="w-full h-[222px]"><img src="{{ asset('assets') }}/image/home/plane-and-ship.svg" alt=""
                            class="w-full h-full mx-auto shipment-icon">
                    </div>
                    <div class="text-xl leading-tight"><strong>Marketsz ships your orders</strong> to you in
                        the
                        Caribbean within 2-4 days</div>
                </div>
            </div>
        </div>
        <template>
            <footer class="mt-10 text-center">
                <a class="inline-flex items-center px-6 text-lg font-bold text-white rounded-full h-11 bg-primary shadow-primary focus:ring-2 focus:ring-black"
                    href="/how-it-works">
                    Check out our shipping information</a>
            </footer>
        </template>
    </section>
    <div class="relative mt-40 sm:mt-60">
        <template>
            <div class="absolute left-0 right-0 z-10 h-10 overflow-hidden bottom-full">
                <svg viewBox="0 0 1200 26" class="absolute bottom-0 left-0 right-0 z-10 w-full -mx-8 -mb-px text-blue-50"
                    style="width:calc(100% + 4rem)">
                    <path
                        d="M1200 0v26H0V0c0 13.807 11.193 25 25 25S50 13.807 50 0c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25s25-11.193 25-25c0 13.807 11.193 25 25 25 13.669 0 24.776-10.97 24.997-24.587L1200 0z"
                        fill="currentColor" fill-rule="evenodd"></path>
                </svg>
            </div>
        </template>
        <div class="absolute left-0 right-0 z-10 h-10 overflow-hidden top-full">
            <svg viewBox="0 0 1201 13" class="absolute top-0 left-0 right-0 z-10 w-full -mx-8 -mt-px text-rose-50"
                style="width:calc(100% + 4rem)">
                <path
                    d="M.719 0h1200v7c-35.7.659-61.366.659-77 0-52.59-2.215-95.155-6.956-131-7-49.257-.06-198.99 7.27-225 7-49.626-.514-88.678-6.988-117-7-88.461-.039-132.35 7.209-176 7-87.18-.417-158.48 1.407-196 0-26.588-.997-104.583 5.985-122 6-13.85.012-30.055-6.014-42-6-21.827.025-46.777 6-67 6-19.149 0-34.816-2-47-6V0z"
                    fill="currentColor" fill-rule="evenodd"></path>
            </svg>
        </div>
        <div class="absolute right-0 z-30 inline w-auto p-3 -mb-16 overflow-hidden bottom-full" id="whale"
            style="transform: translateX(-20px);">
            <div class="animate-wiggle">
                <img src="{{ asset('assets') }}/image/home/cartoon-plane.svg" alt="Whale illustration"
                    class="h-20 transform md:h-36 lg:h-40 xl:h-48">
                <img src="{{ asset('assets') }}/image/home/ship.svg" alt="Whale illustration" class="">
            </div>
        </div>
        <div class="relative z-10 pt-12 pb-12 bg-rose-50 sm:pb-20 sm:pt-0 on-gray">
            <section class="container pt-12 space-y-12 sm:pt-20">
                <header class="max-w-6xl px-4 mx-auto text-center sm:pt-20">
                    <h2 class="text-3xl font-bold leading-snug sm:text-4xl">Why Choose Marketsz?</h2>
                    <p class="mt-2 text-xl leading-snug sm:text-2xl">
                        If you are looking for a hassle-free worldwide shopping and shipping experience, you
                        have come to the
                        right place. Guaranteed and quick deliveries from the most known couriers like DHL,
                        Fedex, UPS, Sea-freight and Air-Cargo.</p>
                </header>
                <div class="max-w-6xl px-5 mx-auto text-xl leading-relaxed text-center">
                    <p>Don’t wait in line at a pickup store! Get your orders delivered right at your door.</p>
                    <p>
                        Experience Marketsz! Receive all of your packages within 2-4 days. Track your order, get
                        high quality
                        support throughout the whole process. Zero Headache, fun shopping and shipping
                        experience!
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-12 px-4 md:grid-cols-3 sm:px-0">
                    <div class="p-4 text-center bg-gray-50 rounded-xl">
                        <div class="flex items-center justify-center"><img
                                src="{{ asset('assets') }}/image/home/delivery.png" alt="" class="max-h-32">
                        </div>
                        <div class="mt-5 text-2xl font-bold leading-tight">Quick and Guaranteed Shopping and
                            Shipping</div>
                        <div class="pt-2 text-lg text-gray-600">Shop from any online store and get your order
                            within 2-4 days</div>
                    </div>
                    <div class="p-4 text-center bg-gray-50 rounded-xl">
                        <div class="flex items-center justify-center"><img
                                src="{{ asset('assets') }}/image/home/monitor.svg" alt="" class="max-h-32">
                        </div>
                        <div class="mt-5 text-2xl font-bold leading-tight">Easy to Use Dashboard</div>
                        <div class="pt-2 text-lg text-gray-600">From your dashboard you can see all of your
                            orders, upload invoices, combine and track your packages</div>
                    </div>
                    <div class="p-4 text-center bg-gray-50 rounded-xl">
                        <div class="flex items-center justify-center"><img
                                src="{{ asset('assets') }}/image/home/call-center.svg" alt="" class="max-h-32">
                        </div>
                        <div class="mt-5 text-2xl font-bold leading-tight">24/7 Live Support</div>
                        <div class="pt-2 text-lg text-gray-600">
                            Need help with anything? Our friendly staff is there to give you a hand!</div>
                    </div>
                </div>
                <div class="max-w-xl px-4 mx-auto text-2xl font-bold text-center">How Marketsz Stacks Up
                    Against
                    Competition</div>
                <div class="py-2 mx-4 bg-white rounded-lg sm:px-10 sm:py-5">
                    <table class="w-full">
                        <thead>
                            <tr class="">
                                <th class="hidden sm:block"></th>
                                <th class="sticky top-0 py-4 text-lg bg-white sm:text-xl sm:static">Marketsz
                                </th>
                                <th class="sticky top-0 py-4 text-lg bg-white sm:text-xl sm:static">Competitors
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr rowspan="2" class="w-full text-lg font-bold text-center border-t border-gray-100 sm:hidden">
                                <th colspan="2" class="pt-4">Free Storage</th>
                            </tr>
                            <tr class="sm:border-t sm:border-gray-100">
                                <th class="hidden py-4 text-xl text-left sm:table-cell">Free Storage</th>
                                <td class="w-1/2 px-3 py-4 leading-snug text-center sm:w-auto">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto mb-2 text-green-500 bg-green-100 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-check">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    </div>
                                    Up to 90 days
                                </td>
                                <td class="w-1/2 px-3 py-4 leading-snug text-center sm:w-auto">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto mb-2 text-red-500 bg-red-200 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-x">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </div>
                                    Additional costs
                                </td>
                            </tr>
                            <tr rowspan="2" class="w-full text-lg font-bold text-center border-t border-gray-100 sm:hidden">
                                <th colspan="2" class="pt-4">Combine Service</th>
                            </tr>
                            <tr class="sm:border-t sm:border-gray-100">
                                <th class="hidden py-4 text-xl text-left sm:table-cell">Combine Service</th>
                                <td class="w-1/2 px-3 py-4 leading-snug text-center sm:w-auto">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto mb-2 text-green-500 bg-green-100 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-check">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    </div>
                                    Unlimited Combinations
                                </td>
                                <td class="w-1/2 px-3 py-4 leading-snug text-center sm:w-auto">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto mb-2 text-red-500 bg-red-200 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-x">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </div>
                                    Additional costs
                                </td>
                            </tr>
                            <tr rowspan="2" class="w-full text-lg font-bold text-center border-t border-gray-100 sm:hidden">
                                <th colspan="2" class="pt-4">Express Shipping</th>
                            </tr>
                            <tr class="sm:border-t sm:border-gray-100">
                                <th class="hidden py-4 text-xl text-left sm:table-cell">Express Shipping</th>
                                <td class="w-1/2 px-3 py-4 leading-snug text-center sm:w-auto">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto mb-2 text-green-500 bg-green-100 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-check">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    </div>
                                    Get Your package within 2-4 days
                                </td>
                                <td class="w-1/2 px-3 py-4 leading-snug text-center sm:w-auto">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto mb-2 text-red-500 bg-red-200 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-x">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </div>
                                    Higher rates
                                </td>
                            </tr>
                            <tr rowspan="2" class="w-full text-lg font-bold text-center border-t border-gray-100 sm:hidden">
                                <th colspan="2" class="pt-4">Doorstep Delivery</th>
                            </tr>
                            <tr class="sm:border-t sm:border-gray-100">
                                <th class="hidden py-4 text-xl text-left sm:table-cell">Doorstep Delivery</th>
                                <td class="w-1/2 px-3 py-4 leading-snug text-center sm:w-auto">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto mb-2 text-green-500 bg-green-100 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-check">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    </div>Get it all the way at your door
                                </td>
                                <td class="w-1/2 px-3 py-4 leading-snug text-center sm:w-auto">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto mb-2 text-red-500 bg-red-200 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-x">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </div>Additional costs
                                </td>
                            </tr>
                            <tr rowspan="2" class="w-full text-lg font-bold text-center border-t border-gray-100 sm:hidden">
                                <th colspan="2" class="pt-4">24/7 Support</th>
                            </tr>
                            <tr class="sm:border-t sm:border-gray-100">
                                <th class="hidden py-4 text-xl text-left sm:table-cell">24/7 Support</th>
                                <td class="w-1/2 px-3 py-4 leading-snug text-center sm:w-auto">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto mb-2 text-green-500 bg-green-100 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-check">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    </div>Live Chat to smooth out every step of the process
                                </td>
                                <td class="w-1/2 px-3 py-4 leading-snug text-center sm:w-auto">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto mb-2 text-red-500 bg-red-200 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-x">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </div>No live chat available
                                </td>
                            </tr>
                            <!-- More Services Row for Mobile -->
                            <tr rowspan="2" class="w-full text-lg font-bold text-center border-t border-gray-100 sm:hidden">
                                <th colspan="2" class="pt-4">More Services</th>
                            </tr>
                            <tr class="sm:border-t sm:border-gray-100">
                                <!-- Desktop Left Column -->
                                <th class="hidden py-4 text-xl text-left sm:table-cell">More Services</th>

                                <!-- Marketsz Column -->
                                <td class="w-1/2 px-3 py-4 leading-snug text-center sm:w-auto">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto mb-2 text-green-500 bg-green-100 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-check">
                                            <polyline points="20 6 9 17 4 12"></polyline>
                                        </svg>
                                    </div>
                                    Air-Cargo & Sea-Freight
                                </td>

                                <!-- Competitors Column -->
                                <td class="w-1/2 px-3 py-4 leading-snug text-center sm:w-auto">
                                    <div
                                        class="flex items-center justify-center w-10 h-10 mx-auto mb-2 text-red-500 bg-red-200 rounded-full">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                            fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                            stroke-linejoin="round" class="feather feather-x">
                                            <line x1="18" y1="6" x2="6" y2="18"></line>
                                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </div>
                                    Only Air-Freight
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>

    <!-- World Map Section -->
    <section class="world-map-bg-outer">
        <div class="wmb-map-container">
            <img src="{{ asset('assets/images/world-map.svg') }}" alt="world map illustration" class="world-map" />
        </div>
        <div class="wmb-text-and-button">
            <h3>Ready to start shipping with Marketsz?</h3>
            <p>Sign up now and get your instant forwarding address</p>
            <a href="{{ route('register') }}">
                <button>Get started now</button>
            </a>
                </div>
            </section>

    <!-- Time to Shop Section -->
    <section class="time-to-shop-outer">
        <div class="max-width-container">
            <img src="{{ asset('assets/images/tts-aeoplane.svg') }}" alt="aeroplane illustration" class="tts-aeroplane rotating-aeroplane" />
            <div class="tts-text-and-button">
                <h3>
                    It's time to <span>shop</span> till you drop and
                    <span>get your orders</span> in record time!
                </h3>
                <p>
                    It's never been easier to shop from any online store and get your
                    packages delivered right at your door
                </p>
                <div class="tts-check-and-text">
                    <div>
                        <img src="{{ asset('assets/images/tts-check-circle-green.svg') }}" alt="" />
                        <span>2-4 Days Delivery</span>
        </div>
                    <div>
                        <img src="{{ asset('assets/images/tts-check-circle-green.svg') }}" alt="" />
                        <span>Unlimited Consolidation</span>
        </div>
                    <div>
                        <img src="{{ asset('assets/images/tts-check-circle-green.svg') }}" alt="" />
                        <span>Live Support</span>
            </div>
        </div>
                <div class="tts-get-started-button">
                    <a href="{{ route('register') }}"><button>Get Started</button></a>
    </div>
            </div>
            <img src="{{ asset('assets/images/tts-box.svg') }}" alt="balloon illustration" class="tts-box rotating-box" />
        </div>
    </section>
@endsection

@section('script')
    <script>
        // Set base URL for partner logos
        window.PARTNERS_LOGOS_BASE_URL = '{{ asset('assets/images/partners-logos/') }}/';
        
        document.addEventListener('DOMContentLoaded', function() {
            try {
            // slider images logic
            // PL = Partners Logos
            const PL_BASE_URL = window.PARTNERS_LOGOS_BASE_URL;
            const allImages = [
                "aliexpress-logo.png",
                "amazon-logo.png",
                "apple-logo.png",
                "bath-and-body-works-logo.png",
                "coach-logo.png",
                "zappos-logo.png",
                "ebay-logo.png",
                "fashion-nova-logo.jpg",
                "forever-logo.png",
                "gap-logo.png",
                "hm-logo.png",
                "home-depot-logo.png",
                "jc-penney-logo.jpg",
                "macys-logo.jpg",
                "nike-logo.png",
                "old-navy-logo.png",
                "pretty-little-thing-logo.png",
                "shein-logo.png",
                "tory-burch-logo.png",
                "vs-logo.png",
                "walmart-logo.jpg",
                "zaful-logo.png",
                "zara-logo.png",
                "zulily-logo.png",
                "vans-logo.jpg",
            ].map((imgName) => `${PL_BASE_URL}${imgName}`);

            const getSlideImgHTML = (imgPath) =>
                `<img class="slide" src="${imgPath}" alt="" />`;
            const allImgsHTML = allImages.map(getSlideImgHTML).join("");

            document.querySelectorAll(".slide-track").forEach((slideTrack) => {
                if (slideTrack && slideTrack.innerHTML !== undefined) {
                    slideTrack.innerHTML = allImgsHTML + allImgsHTML;
                }
            });

            // calculator logic
            const dimButtons = document.querySelectorAll(
                ".hmc-dimensions-unit > div"
            );
            let isInchSelected = true;
            const DIM_ACTIVE_CLASS = "dim-selected";

            if (dimButtons && dimButtons.length > 0) {
                dimButtons.forEach((dimBtn, i) => {
                    if (dimBtn && dimBtn.addEventListener) {
                        dimBtn.addEventListener("click", () => {
                            isInchSelected = i == 0;
                            dimButtons.forEach((btn) => {
                                if (btn && btn.classList) {
                                    btn.classList.remove(DIM_ACTIVE_CLASS);
                                }
                            });
                            if (dimBtn && dimBtn.classList) {
                                dimBtn.classList.add(DIM_ACTIVE_CLASS);
                            }
                        });
                    }
                });
            }

            const weightButtons = document.querySelectorAll(".hmc-wight-unit > div");
            let isKgSelected = true;
            const WEIGHT_ACTIVE_CLASS = "wu-selected";

            if (weightButtons && weightButtons.length > 0) {
                weightButtons.forEach((weightBtn, i) => {
                    if (weightBtn && weightBtn.addEventListener) {
                        weightBtn.addEventListener("click", () => {
                            isKgSelected = i == 0;
                            weightButtons.forEach((btn) => {
                                if (btn && btn.classList) {
                                    btn.classList.remove(WEIGHT_ACTIVE_CLASS);
                                }
                            });
                            if (weightBtn && weightBtn.classList) {
                                weightBtn.classList.add(WEIGHT_ACTIVE_CLASS);
                            }
                            const weightLabel = document.getElementById("wight-label");
                            if (weightLabel) {
                                weightLabel.innerHTML = isKgSelected
                                    ? "Weight in KG"
                                    : "Weight in LBS";
                            }
                        });
                    }
                });
            }

            // Home page calculator calculation logic
            const homePageCostEstimationBtn = document.querySelector('#homePageCostEstimation');
            if (homePageCostEstimationBtn) {
                homePageCostEstimationBtn.addEventListener('click', function() {
                    const button = this;
                    const container = document.querySelector('.home-calculator-result');
                    
                    // Get form values
                    const countrySelect = document.querySelector('#country-dropdown');
                    const country = countrySelect ? countrySelect.value : '';
                    const length = document.querySelector('#cal-length')?.value || 0;
                    const width = document.querySelector('#cal-width')?.value || 0;
                    const height = document.querySelector('#cal-height')?.value || 0;
                    const weight = document.querySelector('#cal-weight')?.value || 0;
                    
                    // Get unit selections
                    const dimUnit = isInchSelected ? 'in' : 'cm';
                    const weightUnit = isKgSelected ? 'kg' : 'lb';
                    
                    // Validate required fields
                    if (!country || country === 'null') {
                        alert('Please select a destination country');
                        return;
                    }
                    if (!weight || parseFloat(weight) <= 0) {
                        alert('Please enter a valid weight');
                        return;
                    }
                    
                    // Show loading state
                    button.disabled = true;
                    button.textContent = 'Calculating...';
                    if (container) {
                        container.innerHTML = '<div style="padding:20px; text-align:center;"><p>Fetching live rates from carriers...</p><p style="font-size:0.9em; color:#666;">This may take a few seconds</p></div>';
                    }
                    
                    const data = {
                        length: length,
                        width: width,
                        height: height,
                        dimension_unit: dimUnit,
                        weight: weight,
                        weight_unit: weightUnit,
                        country: country,
                    };

                    fetch('/calculate-shipping', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || 
                                               document.querySelector('meta[name="csrf_token"]')?.content || 
                                               document.querySelector('input[name="_token"]')?.value || ''
                            },
                            body: JSON.stringify(data)
                        })
                        .then(res => {
                            if (!res.ok) {
                                return res.json().then(err => Promise.reject(err));
                            }
                            return res.json();
                        })
                        .then(res => {
                            console.log('Calculator response:', res);
                            if (!container) return;
                            container.innerHTML = '';

                            // Handle errors
                            if (!res.success) {
                                container.innerHTML = `
                                   <div style="padding:20px; background:#ffecec; color:#a94442; border:1px solid #f5c2c2; border-radius:8px;">
                                       <strong>Error:</strong> ${res.message || 'Unable to calculate shipping costs'}
                                       ${res.errors ? '<br><small>' + JSON.stringify(res.errors) + '</small>' : ''}
                                   </div>
                                `;
                                return;
                            }

                            // --- Best Price Card ---
                            if (res.best_price) {
                                container.innerHTML += `
                                       <div style="
                               background: #f0fff4;
                               border: 2px solid #38a169;
                               border-radius: 12px;
                               padding: 20px;
                               margin-bottom: 20px;
                               box-shadow: 0 4px 12px rgba(0,0,0,0.05);
                               font-family: Arial, sans-serif;
                           ">
                                       <h2 style="color:#2f855a; font-size: 1.3em; margin-bottom: 10px;">🏆 Best Price</h2>
                                       <p style="margin: 4px 0;"><strong>Carrier:</strong> ${res.best_price.carrier || 'N/A'}</p>
                                       <p style="margin: 4px 0;"><strong>Service:</strong> ${res.best_price.service || 'Standard'}</p>
                                       <p style="margin: 4px 0;"><strong>Transit Time:</strong> ${res.best_price.transit_time || 'Varies'}</p>
                                       <p style="margin: 4px 0; font-size: 1.4em; font-weight: bold; color:#2f855a;">
                                           ${res.best_price.currency || 'USD'} $${res.best_price.rate || '0.00'}
                                       </p>
                                       ${res.rate_source === 'live_api' ? '<p style="margin-top:8px; font-size:0.85em; color:#666;">✓ Live rate from carrier API</p>' : ''}
                                   </div>
                               `;
                            }

                            // --- Other Estimates Cards ---
                            if (res.best_estimates && res.best_estimates.length) {
                                container.innerHTML +=
                                    `<h3 style="font-family: Arial, sans-serif; margin-bottom: 10px; margin-top: 20px;">📦 All Available Options</h3>`;
                                container.innerHTML +=
                                    `<div style="display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));">`;

                                res.best_estimates.forEach(r => {
                                    container.innerHTML += `
                                           <div style="
                                   background: white;
                                   border-radius: 10px;
                                   padding: 15px;
                                   box-shadow: 0 4px 10px rgba(0,0,0,0.05);
                                   font-family: Arial, sans-serif;
                                   border: 1px solid #eee;
                                   transition: all 0.3s ease;
                               " onmouseover="this.style.transform='translateY(-5px)';this.style.boxShadow='0 8px 15px rgba(0,0,0,0.1)'"
                                              onmouseout="this.style.transform='';this.style.boxShadow='0 4px 10px rgba(0,0,0,0.05)'">
                                       <p style="font-weight:bold; font-size: 1.1em;">${r.carrier || 'N/A'}</p>
                                       ${r.service ? `<p style="color: #888; font-size: 0.9em;">${r.service}</p>` : ''}
                                       <p style="color: #555; margin-top: 4px;">Transit: ${r.transit_time || 'Varies'}</p>
                                       <p style="margin-top:8px; font-size: 1.2em; color: #2b6cb0; font-weight: bold;">
                                           ${r.currency || 'USD'} $${r.rate || '0.00'}
                                       </p>
                                   </div>
                               `;
                                });

                                container.innerHTML += `</div>`;

                                container.innerHTML += `
                                       <div style="margin-top: 30px; padding: 20px; background: #f9fafb; border-radius: 8px;">
                                           <h3 style="font-size: 1.2em; margin-bottom: 10px;">Marketsz Costs Include:</h3>
                                           <ul style="list-style: disc; padding-left: 20px; line-height: 1.8;">
                                               <li>Live shipping rates from major carriers (FedEx, DHL, UPS)</li>
                                               <li>FREE package storage and consolidation</li>
                                               <li>Deep discounts with global carrier partners</li>
                                               <li>Customs documentation completion</li>
                                           </ul>
                                           <p style="margin-top: 15px; font-size: 0.9em; color: #666;">
                                               <strong>Note:</strong> This amount is an estimate based on the provided dimensions and weight. 
                                               Surcharges may apply due to size, commodity type, and delivery address details and will be 
                                               included in the final shipping charge. Excludes oversized shipments and palletized shipments 
                                               with linear dimensions greater than 72 inches (183 cm).
                                           </p>
                                           <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                                               Rates quoted above are inclusive of any applicable package consolidation and preparation for export.
                                           </p>
                                       </div>
                                   `
                            } else {
                                container.innerHTML += `
                                       <div style="padding:20px; background:#fff3cd; color:#856404; border:1px solid #ffeaa7; border-radius:8px;">
                                           <strong>No shipping options available</strong><br>
                                           <small>Please check your dimensions, weight, and destination country.</small>
                                       </div>
                                   `;
                            }

                            // --- Note ---
                            if (res.note) {
                                container.innerHTML +=
                                    `<p style="margin-top: 15px; font-size: 0.9em; color:#666; font-style: italic;">${res.note}</p>`;
                            }
                        })
                        .catch(error => {
                            console.error('Calculator error:', error);
                            if (container) {
                                container.innerHTML = `
                                   <div style="padding:20px; background:#ffecec; color:#a94442; border:1px solid #f5c2c2; border-radius:8px;">
                                       <strong>Error:</strong> ${error.message || 'Unable to calculate shipping costs. Please try again.'}
                                       ${error.errors ? '<br><small>' + JSON.stringify(error.errors) + '</small>' : ''}
                                   </div>
                                `;
                            }
                        })
                        .finally(() => {
                            button.disabled = false;
                            button.textContent = 'Get Price Estimate';
                        });
                });
            }
            } catch (error) {
                console.error('Error initializing calculator:', error);
            }
        });
    </script>
@endsection