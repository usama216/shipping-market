@extends('layout.master')
@section('title', 'Frequently Asked Questions')
@section('hide_header_bg', true)

@section('content')
    <div class="max-w-4xl mx-auto px-4 py-12">
        <!-- Page Header -->
        <header class="text-center mb-10">
            <h1 class="text-4xl font-extrabold text-gray-900">Frequently Asked Questions</h1>
            <p class="mt-3 text-lg text-gray-600">Everything you need to know about Marketsz and our services.</p>
        </header>

        <!-- FAQ Accordion -->
        <div class="space-y-4">
            <!-- Item 1 -->
            <div class="collapse collapse-plus bg-white border border-gray-200 rounded-lg shadow-sm">
                <input type="radio" name="faq-accordion" checked />
                <div class="collapse-title text-lg font-semibold text-[#9e1d22]">
                    How does Marketsz work?
                </div>
                <div class="collapse-content text-gray-600 text-sm leading-relaxed">
                    Marketsz provides international shoppers with a US shipping address you can use when placing orders
                    with US online stores. The store ships your purchases to Marketsz, we ship it to you, and you save
                    BIG on international shipping costs.
                </div>
            </div>

            <!-- Item 2 -->
            <div class="collapse collapse-plus bg-white border border-gray-200 rounded-lg shadow-sm">
                <input type="radio" name="faq-accordion" />
                <div class="collapse-title text-lg font-semibold text-[#9e1d22]">
                    Why is my login information not working?
                </div>
                <div class="collapse-content text-gray-600 text-sm leading-relaxed">
                    Make sure your Caps Lock button is not on and your browser is updated.
                    If you still can't log in, use the "Forgot email, suite number, or password" option on the Sign In page.
                </div>
            </div>

            <!-- Item 3 -->
            <div class="collapse collapse-plus bg-white border border-gray-200 rounded-lg shadow-sm">
                <input type="radio" name="faq-accordion" />
                <div class="collapse-title text-lg font-semibold text-[#9e1d22]">
                    How can I estimate international shipping costs?
                </div>
                <div class="collapse-content text-gray-600 text-sm leading-relaxed">
                    Ask the merchant for the weight and dimensions of your package. Enter those details in our shipping
                    rate calculator to get an estimate before purchasing.
                </div>
            </div>

            <!-- Item 4 -->
            <div class="collapse collapse-plus bg-white border border-gray-200 rounded-lg shadow-sm">
                <input type="radio" name="faq-accordion" />
                <div class="collapse-title text-lg font-semibold text-[#9e1d22]">
                    Can Marketsz ship my products?
                </div>
                <div class="collapse-content text-gray-600 text-sm leading-relaxed space-y-3">
                    <p>
                        We can ship almost any item, including clothing, cosmetics, toys, cell phones, laptops, and more.
                    </p>
                    <p>
                        Items we cannot ship are restricted by the US government or prohibited in your country.
                        For example, anything requiring an export license or license exception.
                    </p>
                    <p>
                        Please review our <a href="#" class="text-blue-600 hover:underline">Restricted/Prohibited Item
                            List</a>.
                    </p>
                    <p>
                        To have us review an item, send:
                    </p>
                    <div class="px-5">
                        <ul class="list-disc list-inside">
                            <li>Link to the item</li>
                            <li>Shipping address</li>
                            <li>Weight and dimensions</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection