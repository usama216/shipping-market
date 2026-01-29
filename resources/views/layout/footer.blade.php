     <footer class="relative px-4 text-white bg-primary md:px-0">
         <style>
             .footer-round {
                 border-radius: 100% 100% 0 0;
             }
         </style>
         <div
             class="absolute left-0 right-0 z-10 h-6 overflow-hidden rounded-t-full footer-round -top-6 md:h-10 bg-primary">
         </div>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex flex-col items-center justify-between pt-6 sm:pt-8 pb-6 sm:pb-8 text-center md:flex-row md:text-left gap-4 sm:gap-6">
                <div><a href="/"><img class="logo w-28 sm:w-32 md:w-36 lg:w-auto" src="assets/image/logo.svg" alt="app-logo"></a></div>
                <ul class="flex flex-col text-2xl sm:text-3xl md:text-4xl lg:text-4xl xl:text-5xl 2xl:text-6xl font-bold md:flex-row gap-3 sm:gap-4 md:gap-0">
                    <li class="relative z-20 mx-2 sm:mx-2.5 lg:mx-3 xl:mx-5 2xl:mx-6"><a class="text-white border-b-2 border-transparent hover:border-white transition-colors"
                            href="{{ route('web.about') }}">About</a></li>
                    <li class="relative z-20 mx-2 sm:mx-2.5 lg:mx-3 xl:mx-5 2xl:mx-6"><a class="text-white border-b-2 border-transparent hover:border-white transition-colors"
                            href="{{ route('web.contact') }}">Contact US</a>
                    </li>
                    <li class="relative z-20 mx-2 sm:mx-2.5 lg:mx-3 xl:mx-5 2xl:mx-6"><a class="text-white border-b-2 border-transparent hover:border-white transition-colors"
                            href="{{ route('web.calculator') }}">Cost
                            Calculator</a></li>
                    <li class="relative z-20 mx-2 sm:mx-2.5 lg:mx-3 xl:mx-5 2xl:mx-6"><a class="text-white border-b-2 border-transparent hover:border-white transition-colors"
                            href="{{ route('web.faqs') }}">FAQ</a></li>
                </ul>
                 <div class="flex flex-col items-center sm:flex-row">
                     <ul class="flex">
                         <li class="px-2"><a href="https://www.facebook.com/Marketsz/" target="_blank"
                                 rel="noreferrer" class="hover:scale-105"><i class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl text-white fab fa-facebook"
                                     aria-hidden="true"></i></a>
                         </li>
                         <li class="px-2"><a href="https://www.instagram.com/marketsz.official/" target="_blank"
                                 rel="noreferrer" class="hover:scale-105"><i class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl text-white fab fa-instagram"
                                     aria-hidden="true"></i></a>
                         </li>
                         <li class="px-2"><a href="https://www.youtube.com/@marketsz.official" target="_blank"
                                 rel="noreferrer" class="hover:scale-105"><i class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl xl:text-8xl text-white fab fa-youtube"
                                     aria-hidden="true"></i></a>
                         </li>
                     </ul>
                 </div>
             </nav>
            <hr class="my-5 sm:my-6 md:my-8">
            <div class="pt-5 sm:pt-6 md:pt-8 pb-10 sm:pb-12 md:pb-16 text-xl sm:text-2xl md:text-3xl lg:text-3xl text-center text-white text-opacity-75">
                <div class="max-w-lg mx-auto"><span class="">© All Rights Reserved - {{ date('Y') }}</span>
                    <div class="mt-3 sm:mt-4 flex flex-wrap justify-center gap-3 sm:gap-5">
                        <a class="text-white underline whitespace-nowrap hover:text-opacity-100 transition-opacity"
                            href="{{ route('web.terms') }}">Terms
                            of
                            Service</a>
                        <a class="text-white underline whitespace-nowrap hover:text-opacity-100 transition-opacity"
                            href="{{ route('web.privacy') }}">Privacy
                            Policy</a>
                    </div>
                </div>
            </div>
         </div>
     </footer>
