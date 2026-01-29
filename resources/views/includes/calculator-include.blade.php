<header class="max-w-2xl px-4 mx-auto text-center pt-8 sm:pt-16 md:pt-24">
    <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold leading-snug">How much does it cost?</h2>
    <p class="mt-2 text-base sm:text-lg md:text-xl lg:text-2xl leading-snug">Use our calculator to estimate exactly how much it would cost
        you to ship.</p>
</header>
<div class="grid max-w-4xl grid-cols-2 gap-2 sm:gap-3 md:gap-4 px-4 mx-auto sm:px-4 md:px-0">
    <div class="w-full col-span-2 p-4 sm:p-6 md:p-8 bg-rose-50 md:col-span-2 rounded-xl">
        <div class="text-lg sm:text-xl md:text-2xl font-bold">1. Where are you shopping?</div>
        <div class="text-gray-600"></div>
        <div class="flex flex-wrap pt-4 -mt-2 -ml-2">
            <button
                class="flex items-center justify-center flex-1 px-4 mt-2 ml-2 text-lg border-2 border-black rounded-lg h-14 whitespace-nowrap bg-gray-50">
                <div><img draggable="false" class="flex-shrink-0 w-6 mr-2" alt="🇺🇸"
                        src="assets/image/home/1f1fa-1f1f8.png"></div>
                <span>United States</span>
            </button>

        </div>
    </div>
    <div class="col-span-2 p-4 sm:p-6 md:p-8 bg-rose-50 md:col-span-2 rounded-xl">
        <div class="text-lg sm:text-xl md:text-2xl font-bold">2. Where should we send your package?</div>
        <div class="text-sm sm:text-base text-gray-600 mt-1">We forward to the Caribbean</div>
        <div class="relative mt-3 sm:mt-4">
            <select
                class="flex items-center justify-between w-full px-3 sm:px-4 text-sm sm:text-base md:text-lg border-2 border-black rounded-lg bg-gray-50 h-12 sm:h-14"
                id="destinationCountry" name="country" required>
                <option value="">Select your country</option>
                @php
                    $countries = \App\Models\Country::active()->ordered()->get();
                @endphp
                @foreach($countries as $country)
                    <option value="{{ $country->name }}">{{ $country->name }}</option>
                @endforeach
            </select>
        </div>



    </div>
    <div class="col-span-2 p-4 sm:p-6 md:p-8 bg-rose-50 rounded-xl">
        <div class="items-center md:flex">
            <div>
                <div class="text-lg sm:text-xl md:text-2xl font-bold">What are you ordering?</div>
                <div class="text-sm sm:text-base text-gray-600 mt-1">Enter your dimensions for a more accurate quote or choose a product.
                </div>
            </div>
        </div>
        <div class="grid grid-cols-12 gap-2 sm:gap-3 md:gap-4 mt-3 sm:mt-4">
            <div class="col-span-12 sm:col-span-8 lg:col-span-8"><label class="block text-sm sm:text-base">Dimensions:</label>
                <div class="flex mt-1">
                    <input type="text" name="length" value="" class="flex-auto w-full -mr-px border-gray-400 rounded-l text-sm sm:text-base px-2 sm:px-3 py-2 sm:py-2.5"
                        placeholder="Length" aria-label="Length">
                    <input type="text" name="width" value="" class="flex-auto w-full border-gray-400 text-sm sm:text-base px-2 sm:px-3 py-2 sm:py-2.5"
                        placeholder="Width" aria-label="Width">
                    <input type="text" name="height" value="" class="flex-auto w-full -ml-px border-gray-400 text-sm sm:text-base px-2 sm:px-3 py-2 sm:py-2.5"
                        placeholder="Height" aria-label="Height">
                    <select class="flex-none w-16 sm:w-20 -ml-px border-gray-400 rounded-r text-sm sm:text-base px-1 sm:px-2 py-2 sm:py-2.5" aria-label="Size unit">
                        <option selected="" value="in">in</option>
                        <option value="cm">cm</option>
                    </select>
                </div>
            </div>
            <div class="col-span-12 sm:col-span-4 lg:col-span-4 mt-2 sm:mt-0"><label for="costCalculatorWeight"
                    class="block text-sm sm:text-base">Weight:</label>
                <div class="flex mt-1"><input type="text" name="weight" id="costCalculatorWeight" value=""
                        placeholder="0.00" class="flex-auto w-full border-gray-400 rounded-l text-sm sm:text-base px-2 sm:px-3 py-2 sm:py-2.5"><select
                        class="flex-none w-16 sm:w-20 -ml-px border-gray-400 rounded-r text-sm sm:text-base px-1 sm:px-2 py-2 sm:py-2.5" aria-label="Weight unit">
                        <option selected="" value="lb">lb</option>
                        <option value="kg">kg</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-center col-span-12 mt-3 sm:mt-4 mb-4"><button type="button"
                    class="inline-flex items-center h-10 sm:h-12 px-4 sm:px-6 font-bold text-sm sm:text-base text-white rounded-full bg-primary shadow-primary hover:bg-[#7a1519] transition-colors"
                    id="costEstimation">Get
                    price
                    estimate</button></div>
        </div>

        <div class="result">

        </div>
    </div>
</div>