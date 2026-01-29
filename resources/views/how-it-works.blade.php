@extends('layout.master')
@section('title', 'How It Works - Marketsz')
@section('content')
    <!-- Hassle-Free Solution Section -->
    <section class="hassle-free-solution-outer hassle-free-solution-outer-on-hiw-page">
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
    </section>
@endsection
