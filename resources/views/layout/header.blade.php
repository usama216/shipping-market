<header class="relative w-full overflow-hidden text-white bg-white">
    @hasSection('hide_header_bg')
    @else
        <img class="header-bg" src="{{ asset('assets/image/home/background.svg') }}" alt="background" width="100%">
    @endif

    <nav class="font-bold text-base sm:text-lg md:text-xl lg:text-xl xl:text-xl" style="padding-top: 15px; padding-bottom: 15px;">
        <div class="flex flex-wrap items-center justify-between px-3 sm:px-4 md:px-6 gap-2 sm:gap-3">
            <div class="flex-shrink-0">
                <a href="{{ route('web.home') }}"><img class="logo w-48 sm:w-56 md:w-64 lg:w-72 xl:w-auto" src="{{ asset('assets/image/logo-original.svg') }}"
                        alt="app-logo"></a>
            </div>
            
            <!-- Desktop Navigation Menu -->
            <ul class="items-center justify-between hidden h-full ml-2 sm:ml-3 md:ml-5 lg:ml-6 xl:ml-10 lg:flex">
                <li class="relative z-20 mx-1 sm:mx-1.5 text-black lg:mx-2 xl:mx-6 2xl:mx-6"><a class="border-b-2 border-transparent text-xl sm:text-2xl md:text-3xl lg:text-3xl xl:text-4xl 2xl:text-5xl hover:border-primary-500 transition-colors"
                        href="{{ route('web.how-it-works') }}">How It Works</a></li>
                <li class="relative z-20 mx-1 sm:mx-1.5 text-black lg:mx-2 xl:mx-6 2xl:mx-6"><a class="border-b-2 border-transparent text-xl sm:text-2xl md:text-3xl lg:text-3xl xl:text-4xl 2xl:text-5xl hover:border-primary-500 transition-colors"
                        href="{{ route('web.calculator') }}">Cost Calculator</a></li>
                <li class="relative z-20 mx-1 sm:mx-1.5 text-black lg:mx-2 xl:mx-6 2xl:mx-6"><a class="border-b-2 border-transparent text-xl sm:text-2xl md:text-3xl lg:text-3xl xl:text-4xl 2xl:text-5xl hover:border-primary-500 transition-colors"
                        href="{{ route('web.about') }}">About</a></li>
                <li class="relative z-20 mx-1 sm:mx-1.5 text-black lg:mx-2 xl:mx-6 2xl:mx-6"><a class="border-b-2 border-transparent text-xl sm:text-2xl md:text-3xl lg:text-3xl xl:text-4xl 2xl:text-5xl hover:border-primary-500 transition-colors"
                        href="{{ route('web.contact') }}">Contact US</a></li>
            </ul>
            
            <!-- Login and Get Started Buttons - Always Visible -->
            <div class="flex items-center space-x-2 sm:space-x-2.5 md:space-x-3 lg:space-x-3 flex-shrink-0">
                <a href="{{ route('login') }}"
                    class="border !text-[#9e1d22] !border-[#9e1d22] px-8 py-5 sm:px-10 sm:py-6 md:px-12 md:py-7 lg:px-16 lg:py-8 xl:px-20 xl:py-10 2xl:px-24 2xl:py-12 text-xl sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl 2xl:text-6xl rounded-full whitespace-nowrap hover:bg-[#9e1d22] hover:!text-white transition-colors">Login</a>
                <a href="{{ route('register') }}"
                    class="border !text-white !border-[#9e1d22] px-8 py-5 sm:px-10 sm:py-6 md:px-12 md:py-7 lg:px-16 lg:py-8 xl:px-20 xl:py-10 2xl:px-24 2xl:py-12 text-xl sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl 2xl:text-6xl rounded-full !bg-[#9e1d22] whitespace-nowrap hover:bg-[#7a1519] transition-colors">Get Started</a>
            </div>
            
            <!-- Mobile Menu Button - Only shows on mobile/tablet -->
            <div class="block lg:hidden">
                <button aria-label="Open menu" class="p-2">
                    <svg class="stroke-current w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </nav>
</header>