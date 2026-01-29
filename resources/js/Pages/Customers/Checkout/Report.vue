<template>
    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8 bg-slate-50 min-h-screen font-sans">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Checkout</h1>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <div class="lg:col-span-8 space-y-6">
                    <div
                        class="bg-white border border-gray-200 rounded-lg shadow-sm"
                    >
                        <div class="flex items-center p-4 border-b">
                            <span class="text-xl font-bold text-orange-500 mr-3"
                                >1</span
                            >
                            <h2 class="text-xl font-semibold text-gray-700">
                                Shipping Address
                            </h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div
                                v-for="card in addressCards"
                                :key="card.id"
                                @click="selectedAddressId = card.id"
                                class="border p-5 rounded-lg relative transition duration-200 cursor-pointer hover:border-blue-500"
                                :class="{
                                    'border-blue-600 ring-2 ring-blue-200':
                                        selectedAddressId === card.id,
                                    'border-gray-300':
                                        selectedAddressId !== card.id,
                                }"
                            >
                                <div class="flex items-start">
                                    <input
                                        type="radio"
                                        name="selectedCard"
                                        :value="card.id"
                                        v-model="selectedAddressId"
                                        class="mt-1 mr-4 shrink-0 text-blue-600 focus:ring-blue-500"
                                    />
                                    <div class="flex-1">
                                        <p class="font-bold text-gray-800">
                                            {{ card.name }}
                                        </p>
                                        <p class="text-sm text-gray-600">
                                            {{ card.address }}
                                        </p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Payment Method: **** **** ****
                                            {{ card.last4 }}
                                        </p>
                                    </div>
                                </div>
                                <div
                                    class="absolute top-4 right-4 flex space-x-4"
                                >
                                    <button
                                        @click.stop="editCard(card)"
                                        class="text-sm font-medium text-blue-600 hover:underline"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        @click.stop="deleteCard(card.id)"
                                        class="text-sm font-medium text-red-600 hover:underline"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                            <button
                                @click="addCard"
                                class="text-sm font-medium text-blue-600 hover:underline mt-2"
                            >
                                + Add a new address
                            </button>
                        </div>
                    </div>

                    <div
                        class="bg-white border border-gray-200 rounded-lg shadow-sm"
                    >
                        <div class="flex items-center p-4 border-b">
                            <span class="text-xl font-bold text-orange-500 mr-3"
                                >2</span
                            >
                            <h2 class="text-xl font-semibold text-gray-700">
                                Review Items & Delivery
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                <div
                                    v-for="item in cartItems"
                                    :key="item.id"
                                    class="flex items-start space-x-4"
                                >
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-800">
                                            {{ item.name }}
                                        </h3>
                                        <p class="text-sm text-gray-500">
                                            Qty: {{ item.qty }}
                                        </p>
                                    </div>
                                    <p class="font-semibold text-gray-800">
                                        ${{
                                            (item.price * item.qty).toFixed(2)
                                        }}
                                    </p>
                                </div>
                            </div>

                            <hr class="my-6" />

                            <div>
                                <h3
                                    class="text-lg font-semibold text-gray-700 mb-4"
                                >
                                    Choose a delivery option:
                                </h3>
                                <div class="space-y-3">
                                    <label
                                        v-for="option in shippingOptions"
                                        :key="option.id"
                                        class="flex items-center p-4 border rounded-lg cursor-pointer transition-all"
                                        :class="{
                                            'bg-blue-50 border-blue-500 ring-2 ring-blue-200':
                                                selectedShippingId ===
                                                option.id,
                                            'border-gray-300 hover:border-gray-400':
                                                selectedShippingId !==
                                                option.id,
                                        }"
                                    >
                                        <input
                                            type="radio"
                                            name="shipping"
                                            :value="option.id"
                                            v-model="selectedShippingId"
                                            class="mr-4 text-blue-600 focus:ring-blue-500"
                                        />
                                        <div
                                            class="flex justify-between w-full items-center"
                                        >
                                            <div>
                                                <p
                                                    class="font-bold text-green-600"
                                                >
                                                    {{ option.date }}
                                                </p>
                                                <p
                                                    class="text-sm text-gray-600"
                                                >
                                                    {{ option.name }} ({{
                                                        totalWeight
                                                    }}lbs)
                                                </p>
                                            </div>
                                            <p
                                                class="font-medium text-sm text-gray-700"
                                            >
                                                ${{ option.price.toFixed(2) }}
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-4">
                    <div class="sticky top-6 space-y-6">
                        <div
                            class="bg-white border border-gray-200 rounded-lg shadow-sm p-6"
                        >
                            <h3
                                class="text-lg font-semibold text-gray-800 mb-4"
                            >
                                Optional Services
                            </h3>
                            <div class="space-y-4 text-sm">
                                <div>
                                    <h4
                                        class="font-semibold text-gray-700 mb-2"
                                    >
                                        Packing Options
                                    </h4>
                                    <div class="space-y-2">
                                        <label
                                            v-for="option in packingOptions"
                                            :key="option.id"
                                            class="flex items-center"
                                        >
                                            <input
                                                type="checkbox"
                                                v-model="option.selected"
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2"
                                            />
                                            <span
                                                class="flex-grow text-gray-700"
                                                >{{ option.text }}</span
                                            >
                                            <span class="text-gray-600"
                                                >${{
                                                    option.price.toFixed(2)
                                                }}</span
                                            >
                                        </label>
                                    </div>
                                </div>
                                <hr class="my-4" />
                                <div>
                                    <h4
                                        class="font-semibold text-gray-700 mb-2"
                                    >
                                        Shipping Preferences
                                    </h4>
                                    <label class="flex items-center">
                                        <input
                                            type="checkbox"
                                            v-model="wantsInsurance"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-2"
                                        />
                                        <span class="flex-grow text-gray-700"
                                            >Add shipping insurance</span
                                        >
                                        <span class="text-gray-600"
                                            >+${{
                                                insuranceCost.toFixed(2)
                                            }}</span
                                        >
                                    </label>
                                    <p class="text-xs text-gray-500 ml-6">
                                        Covers up to ${{
                                            itemsSubtotal.toFixed(2)
                                        }}
                                    </p>
                                </div>
                                <hr class="my-4" />
                                <div>
                                    <h4
                                        class="font-semibold text-gray-700 mb-2"
                                    >
                                        Export Documentation
                                    </h4>
                                    <div
                                        class="flex items-center text-gray-700"
                                    >
                                        <span>Tax ID:</span>
                                        <button
                                            class="ml-2 text-blue-600 hover:underline"
                                        >
                                            Add Document
                                        </button>
                                        <span class="flex-grow"></span>
                                        <span class="text-gray-600">$0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Coupon Section -->
                        <CouponSection
                            :order-amount="grandTotal"
                            @coupon-applied="handleCouponApplied"
                            @coupon-removed="handleCouponRemoved"
                        />

                        <!-- Loyalty Section -->
                        <LoyaltySection
                            :order-amount="grandTotal"
                            @loyalty-applied="handleLoyaltyApplied"
                            @loyalty-removed="handleLoyaltyRemoved"
                        />

                        <div
                            class="bg-white border border-gray-200 rounded-lg shadow-sm p-6"
                        >
                            <h3
                                class="text-lg font-semibold text-gray-800 mb-4"
                            >
                                Order Summary
                            </h3>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between text-gray-600">
                                    <span>Items ({{ totalItems }}):</span>
                                    <span>${{ itemsSubtotal.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-gray-600">
                                    <span>Shipping & handling:</span>
                                    <span>${{ shippingCost.toFixed(2) }}</span>
                                </div>
                                <div
                                    v-if="packingOptionsCost > 0"
                                    class="flex justify-between text-gray-600"
                                >
                                    <span>Packing services:</span>
                                    <span
                                        >${{
                                            packingOptionsCost.toFixed(2)
                                        }}</span
                                    >
                                </div>
                                <div
                                    v-if="insuranceCost > 0"
                                    class="flex justify-between text-gray-600"
                                >
                                    <span>Shipment insurance:</span>
                                    <span>${{ insuranceCost.toFixed(2) }}</span>
                                </div>
                                <div class="flex justify-between text-gray-600">
                                    <span>Handling Fee:</span>
                                    <span>${{ handlingFee.toFixed(2) }}</span>
                                </div>
                                <div
                                    v-if="couponDiscount > 0"
                                    class="flex justify-between text-green-600"
                                >
                                    <span>Coupon Discount:</span>
                                    <span
                                        >-${{ couponDiscount.toFixed(2) }}</span
                                    >
                                </div>
                                <div
                                    v-if="loyaltyDiscount > 0"
                                    class="flex justify-between text-green-600"
                                >
                                    <span>Loyalty Points Discount:</span>
                                    <span
                                        >-${{
                                            loyaltyDiscount.toFixed(2)
                                        }}</span
                                    >
                                </div>
                                <hr class="my-2" />
                                <div
                                    class="flex justify-between font-bold text-gray-800 text-base"
                                >
                                    <span>Total before tax:</span>
                                    <span
                                        >${{ totalBeforeTax.toFixed(2) }}</span
                                    >
                                </div>
                                <div class="flex justify-between text-gray-600">
                                    <span>Estimated tax:</span>
                                    <span>${{ estimatedTax.toFixed(2) }}</span>
                                </div>
                            </div>
                            <hr class="my-4 border-dashed" />
                            <div
                                class="flex justify-between text-xl font-bold text-red-700"
                            >
                                <span>Order total:</span>
                                <span>${{ finalTotal.toFixed(2) }}</span>
                            </div>
                            <div class="mt-6">
                                <PrimaryButton
                                    class="w-full justify-center py-3"
                                    >Checkout</PrimaryButton
                                >
                            </div>
                            <div class="mt-4">
                                <!-- <div>
                                    <i class="fa-brands fa-cc-visa"></i>
                                    <i class="fa-brands fa-cc-mastercard"></i>
                                </div> -->
                                <div class="mt-4">
                                    <p
                                        class="text-xs text-center text-gray-500 mb-2"
                                    >
                                        We accept:
                                    </p>
                                    <div
                                        class="flex items-center justify-center gap-x-3 flex-wrap"
                                    >
                                        <svg
                                            class="h-6"
                                            viewBox="0 0 38 12"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <path
                                                d="M37.5 1.3H33.8L31.1 10.1H34.8L37.5 1.3ZM25.3 1.3L21.6 10.1H25.4L29.1 1.3H25.3ZM17.4 6.5C17.4 3.9 19.8 1.3 22.2 1.3C23.5 1.3 24.1 2.1 24.5 2.7L22.6 3.8C22.1 3.1 21.6 2.8 21.1 2.8C20.1 2.8 19.4 3.6 19.4 4.7C19.4 5.2 19.6 5.8 20.2 6.1L20.4 6.2C21.4 6.6 22 7 22 7.8C22 8.7 21.1 9.2 20.1 9.2C18.9 9.2 18.2 8.6 17.9 8.1L20.1 7.1C20.4 7.6 20.8 7.9 21.2 7.9C22.1 7.9 22.7 7.3 22.7 6.4C22.7 5.8 22.5 5.3 21.7 5L21.5 4.9C20.5 4.5 19.9 4.1 19.9 3.3C19.9 2.3 20.8 1.8 21.8 1.8C23.1 1.8 23.9 2.5 24.2 3.1L21.9 4.3C21.5 3.6 21 3.3 20.5 3.3C19.5 3.3 18.8 4.1 18.8 5.2C18.8 5.7 19 6.3 19.6 6.6L19.8 6.7C20.8 7.1 21.4 7.5 21.4 8.3C21.4 9.2 20.5 9.7 19.5 9.7C18.3 9.7 17.6 9.1 17.3 8.6L19.5 7.6C19.8 8.1 20.2 8.4 20.6 8.4C21.5 8.4 22.1 7.8 22.1 6.9C22.1 6.3 21.9 5.8 21.1 5.5L20.9 5.4C19.9 5 19.3 4.6 19.3 3.8C19.3 2.8 20.2 2.3 21.2 2.3C22.5 2.3 23.3 3 23.6 3.6L21.3 4.8C20.9 4.1 20.4 3.8 19.9 3.8C18.9 3.8 18.2 4.6 18.2 5.7C18.2 6.2 18.4 6.8 19 7.1L19.2 7.2C20.2 7.6 20.8 8 20.8 8.8C20.8 9.7 19.9 10.2 18.9 10.2C17.7 10.2 17 9.6 16.7 9.1L18.9 8.1C19.2 8.6 19.6 8.9 20 8.9C20.9 8.9 21.5 8.3 21.5 7.4C21.5 6.8 21.3 6.3 20.5 6L20.3 5.9C19.3 5.5 18.7 5.1 18.7 4.3C18.7 3.3 19.6 2.8 20.6 2.8C21.9 2.8 22.7 3.5 23 4.1L20.7 5.3C20.3 4.6 19.8 4.3 19.3 4.3C18.3 4.3 17.6 5.1 17.6 6.2C17.6 6.7 17.8 7.3 18.4 7.6L18.6 7.7C19.6 8.1 20.2 8.5 20.2 9.3C20.2 10.2 19.3 10.7 18.3 10.7C17.1 10.7 16.4 10.1 16.1 9.6L18.3 8.6C18.6 9.1 19 9.4 19.4 9.4C20.3 9.4 20.9 8.8 20.9 7.9C20.9 7.3 20.7 6.8 19.9 6.5L19.7 6.4C18.7 6 18.1 5.6 18.1 4.8C18.1 3.8 19 3.3 20 3.3C21.3 3.3 22.1 4 22.4 4.6L20.1 5.8C19.7 5.1 19.2 4.8 18.7 4.8C17.7 4.8 17 5.6 17 6.7C17 7.2 17.2 7.8 17.8 8.1L18 8.2C19 8.6 19.6 9 19.6 9.8C19.6 10.7 18.7 11.2 17.7 11.2C16.5 11.2 15.8 10.6 15.5 10.1L17.7 9.1C18 9.6 18.4 9.9 18.8 9.9C19.7 9.9 20.3 9.3 20.3 8.4C20.3 7.8 20.1 7.3 19.3 7L19.1 6.9C18.1 6.5 17.5 6.1 17.5 5.3C17.5 4.3 18.4 3.8 19.4 3.8C20.7 3.8 21.5 4.5 21.8 5.1L19.5 6.3C19.1 5.6 18.6 5.3 18.1 5.3C17.1 5.3 16.4 6.1 16.4 7.2C16.4 7.7 16.6 8.3 17.2 8.6L17.4 8.7C18.4 9.1 19 9.5 19 10.3C19 11.2 18.1 11.7 17.1 11.7C15.9 11.7 15.2 11.1 14.9 10.6L17.1 9.6C17.4 10.1 17.8 10.4 18.2 10.4C19.1 10.4 19.7 9.8 19.7 8.9C19.7 8.3 19.5 7.8 18.7 7.5L18.5 7.4C17.5 7 16.9 6.6 16.9 5.8C16.9 4.8 17.8 4.3 18.8 4.3C20.1 4.3 20.9 5 21.2 5.6L18.9 6.8C18.5 6.1 18 5.8 17.5 5.8C16.5 5.8 15.8 6.6 15.8 7.7C15.8 8.2 16 8.8 16.6 9.1L16.8 9.2C17.8 9.6 18.4 10 18.4 10.8C18.4 11.7 17.5 12.2 16.5 12.2C15.3 12.2 14.6 11.6 14.3 11.1L16.5 10.1C16.8 10.6 17.2 10.9 17.6 10.9C18.5 10.9 19.1 10.3 19.1 9.4C19.1 8.8 18.9 8.3 18.1 8L17.9 7.9C16.9 7.5 16.3 7.1 16.3 6.3C16.3 5.3 17.2 4.8 18.2 4.8C19.5 4.8 20.3 5.5 20.6 6.1L18.3 7.3C17.9 6.6 17.4 6.3 16.9 6.3C15.9 6.3 15.2 7.1 15.2 8.2C15.2 8.7 15.4 9.3 16 9.6L16.2 9.7C17.2 10.1 17.8 10.5 17.8 11.3C17.8 12.2 16.9 12.7 15.9 12.7C14.7 12.7 14 12.1 13.7 11.6L15.9 10.6C16.2 11.1 16.6 11.4 17 11.4C17.9 11.4 18.5 10.8 18.5 9.9C18.5 9.3 18.3 8.8 17.5 8.5L17.3 8.4C16.3 8 15.7 7.6 15.7 6.8C15.7 5.8 16.6 5.3 17.6 5.3C18.9 5.3 19.7 6 20 6.6L17.7 7.8C17.3 7.1 16.8 6.8 16.3 6.8C15.3 6.8 14.6 7.6 14.6 8.7C14.6 9.2 14.8 9.8 15.4 10.1L15.6 10.2C16.6 10.6 17.2 11 17.2 11.8C17.2 12.7 16.3 13.2 15.3 13.2C14.1 13.2 13.4 12.6 13.1 12.1L15.3 11.1C15.6 11.6 16 11.9 16.4 11.9C17.3 11.9 17.9 11.3 17.9 10.4C17.9 9.8 17.7 9.3 16.9 9L16.7 8.9C15.7 8.5 15.1 8.1 15.1 7.3C15.1 6.3 16 5.8 17 5.8C18.3 5.8 19.1 6.5 19.4 7.1L17.1 8.3C16.7 7.6 16.2 7.3 15.7 7.3C14.7 7.3 14 8.1 14 9.2C14 9.7 14.2 10.3 14.8 10.6L15 10.7C16 11.1 16.6 11.5 16.6 12.3C16.6 13.2 15.7 13.7 14.7 13.7C13.5 13.7 12.8 13.1 12.5 12.6L14.7 11.6C15 12.1 15.4 12.4 15.8 12.4C16.7 12.4 17.3 11.8 17.3 10.9C17.3 10.3 17.1 9.8 16.3 9.5L16.1 9.4C15.1 9 14.5 8.6 14.5 7.8C14.5 6.8 15.4 6.3 16.4 6.3C17.7 6.3 18.5 7 18.8 7.6L16.5 8.8C16.1 8.1 15.6 7.8 15.1 7.8C14.1 7.8 13.4 8.6 13.4 9.7C13.4 10.2 13.6 10.8 14.2 11.1L14.4 11.2C15.4 11.6 16 12 16 12.8C16 13.7 15.1 14.2 14.1 14.2C12.9 14.2 12.2 13.6 11.9 13.1L14.1 12.1C14.4 12.6 14.8 12.9 15.2 12.9C16.1 12.9 16.7 12.3 16.7 11.4C16.7 10.8 16.5 10.3 15.7 10L15.5 9.9C14.5 9.5 13.9 9.1 13.9 8.3C13.9 7.3 14.8 6.8 15.8 6.8C17.1 6.8 17.9 7.5 18.2 8.1L15.9 9.3C15.5 8.6 15 8.3 14.5 8.3C13.5 8.3 12.8 9.1 12.8 10.2C12.8 10.7 13 11.3 13.6 11.6L13.8 11.7C14.8 12.1 15.4 12.5 15.4 13.3C15.4 14.2 14.5 14.7 13.5 14.7C12.3 14.7 11.6 14.1 11.3 13.6L13.5 12.6C13.8 13.1 14.2 13.4 14.6 13.4C15.5 13.4 16.1 12.8 16.1 11.9C16.1 11.3 15.9 10.8 15.1 10.5L14.9 10.4C13.9 10 13.3 9.6 13.3 8.8C13.3 7.8 14.2 7.3 15.2 7.3C16.5 7.3 17.3 8 17.6 8.6L15.3 9.8C14.9 9.1 14.4 8.8 13.9 8.8C12.9 8.8 12.2 9.6 12.2 10.7C12.2 11.2 12.4 11.8 13 12.1L13.2 12.2C14.2 12.6 14.8 13 14.8 13.8C14.8 14.7 13.9 15.2 12.9 15.2C11.7 15.2 11 14.6 10.7 14.1L12.9 13.1C13.2 13.6 13.6 13.9 14 13.9C14.9 13.9 15.5 13.3 15.5 12.4C15.5 11.8 15.3 11.3 14.5 11L14.3 10.9C13.3 10.5 12.7 10.1 12.7 9.3C12.7 8.3 13.6 7.8 14.6 7.8C15.9 7.8 16.7 8.5 17 9.1L14.7 10.3C14.3 9.6 13.8 9.3 13.3 9.3C12.3 9.3 11.6 10.1 11.6 11.2C11.6 11.7 11.8 12.3 12.4 12.6L12.6 12.7C13.6 13.1 14.2 13.5 14.2 14.3C14.2 15.2 13.3 15.7 12.3 15.7C11.1 15.7 10.4 15.1 10.1 14.6L12.3 13.6C12.6 14.1 13 14.4 13.4 14.4C14.3 14.4 14.9 13.8 14.9 12.9C14.9 12.3 14.7 11.8 13.9 11.5L13.7 11.4C12.7 11 12.1 10.6 12.1 9.8C12.1 8.8 13 8.3 14 8.3C15.3 8.3 16.1 9 16.4 9.6L14.1 10.8C13.7 10.1 13.2 9.8 12.7 9.8C11.7 9.8 11 10.6 11 11.7C11 12.2 11.2 12.8 11.8 13.1L12 13.2C13 13.6 13.6 14 13.6 14.8C13.6 15.7 12.7 16.2 11.7 16.2C10.5 16.2 9.8 15.6 9.5 15.1L11.7 14.1C12 14.6 12.4 14.9 12.8 14.9C13.7 14.9 14.3 14.3 14.3 13.4C14.3 12.8 14.1 12.3 13.3 12L13.1 11.9C12.1 11.5 11.5 11.1 11.5 10.3C11.5 9.3 12.4 8.8 13.4 8.8C14.7 8.8 15.5 9.5 15.8 10.1L13.5 11.3C13.1 10.6 12.6 10.3 12.1 10.3C11.1 10.3 10.4 11.1 10.4 12.2C10.4 12.7 10.6 13.3 11.2 13.6L11.4 13.7C12.4 14.1 13 14.5 13 15.3C13 16.2 12.1 16.7 11.1 16.7C9.9 16.7 9.2 16.1 8.9 15.6L11.1 14.6C11.4 15.1 11.8 15.4 12.2 15.4C13.1 15.4 13.7 14.8 13.7 13.9C13.7 13.3 13.5 12.8 12.7 12.5L12.5 12.4C11.5 12 10.9 11.6 10.9 10.8C10.9 9.8 11.8 9.3 12.8 9.3C14.1 9.3 14.9 10 15.2 10.6L12.9 11.8C12.5 11.1 12 10.8 11.5 10.8C10.5 10.8 9.8 11.6 9.8 12.7C9.8 13.2 10 13.8 10.6 14.1L10.8 14.2C11.8 14.6 12.4 15 12.4 15.8C12.4 16.7 11.5 17.2 10.5 17.2C9.3 17.2 8.6 16.6 8.3 16.1L10.5 15.1C10.8 15.6 11.2 15.9 11.6 15.9C12.5 15.9 13.1 15.3 13.1 14.4C13.1 13.8 12.9 13.3 12.1 13L11.9 12.9C10.9 12.5 10.3 12.1 10.3 11.3C10.3 10.3 11.2 9.8 12.2 9.8C13.5 9.8 14.3 10.5 14.6 11.1L12.3 12.3C11.9 11.6 11.4 11.3 10.9 11.3C9.9 11.3 9.2 12.1 9.2 13.2C9.2 13.7 9.4 14.3 10 14.6L10.2 14.7C11.2 15.1 11.8 15.5 11.8 16.3C11.8 17.2 10.9 17.7 9.9 17.7C8.7 17.7 8 17.1 7.7 16.6L9.9 15.6C10.2 16.1 10.6 16.4 11 16.4C11.9 16.4 12.5 15.8 12.5 14.9C12.5 14.3 12.3 13.8 11.5 13.5L11.3 13.4C10.3 13 9.7 12.6 9.7 11.8C9.7 10.8 10.6 10.3 11.6 10.3C12.9 10.3 13.7 11 14 11.6L11.7 12.8C11.3 12.1 10.8 11.8 10.3 11.8C9.3 11.8 8.6 12.6 8.6 13.7C8.6 14.2 8.8 14.8 9.4 15.1L9.6 15.2C10.6 15.6 11.2 16 11.2 16.8C11.2 17.7 10.3 18.2 9.3 18.2C8.1 18.2 7.4 17.6 7.1 17.1L9.3 16.1C9.6 16.6 10 16.9 10.4 16.9C11.3 16.9 11.9 16.3 11.9 15.4C11.9 14.8 11.7 14.3 10.9 14L10.7 13.9C9.7 13.5 9.1 13.1 9.1 12.3C9.1 11.3 10 10.8 11 10.8C12.3 10.8 13.1 11.5 13.4 12.1L11.1 13.3C10.7 12.6 10.2 12.3 9.7 12.3C8.7 12.3 8 13.1 8 14.2C8 14.7 8.2 15.3 8.8 15.6L9 15.7C10 16.1 10.6 16.5 10.6 17.3C10.6 18.2 9.7 18.7 8.7 18.7C7.5 18.7 6.8 18.1 6.5 17.6L8.7 16.6C9 17.1 9.4 17.4 9.8 17.4C10.7 17.4 11.3 16.8 11.3 15.9C11.3 15.3 11.1 14.8 10.3 14.5L10.1 14.4C9.1 14 8.5 13.6 8.5 12.8C8.5 11.8 9.4 11.3 10.4 11.3C11.7 11.3 12.5 12 12.8 12.6L10.5 13.8C10.1 13.1 9.6 12.8 9.1 12.8C8.1 12.8 7.4 13.6 7.4 14.7C7.4 15.2 7.6 15.8 8.2 16.1L8.4 16.2C9.4 16.6 10 17 10 17.8C10 18.7 9.1 19.2 8.1 19.2C6.9 19.2 6.2 18.6 5.9 18.1L8.1 17.1C8.4 17.6 8.8 17.9 9.2 17.9C10.1 17.9 10.7 17.3 10.7 16.4C10.7 15.8 10.5 15.3 9.7 15L9.5 14.9C8.5 14.5 7.9 14.1 7.9 13.3C7.9 12.3 8.8 11.8 9.8 11.8C11.1 11.8 11.9 12.5 12.2 13.1L9.9 14.3C9.5 13.6 9 13.3 8.5 13.3C7.5 13.3 6.8 14.1 6.8 15.2C6.8 15.7 7 16.3 7.6 16.6L7.8 16.7C8.8 17.1 9.4 17.5 9.4 18.3C9.4 19.2 8.5 19.7 7.5 19.7C6.3 19.7 5.6 19.1 5.3 18.6L7.5 17.6C7.8 18.1 8.2 18.4 8.6 18.4C9.5 18.4 10.1 17.8 10.1 16.9C10.1 16.3 9.9 15.8 9.1 15.5L8.9 15.4C7.9 15 7.3 14.6 7.3 13.8C7.3 12.8 8.2 12.3 9.2 12.3C10.5 12.3 11.3 13 11.6 13.6L9.3 14.8C8.9 14.1 8.4 13.8 7.9 13.8C6.9 13.8 6.2 14.6 6.2 15.7C6.2 16.2 6.4 16.8 7 17.1L7.2 17.2C8.2 17.6 8.8 18 8.8 18.8C8.8 19.7 7.9 20.2 6.9 20.2C5.7 20.2 5 19.6 4.7 19.1L6.9 18.1C7.2 18.6 7.6 18.9 8 18.9C8.9 18.9 9.5 18.3 9.5 17.4C9.5 16.8 9.3 16.3 8.5 16L8.3 15.9C7.3 15.5 6.7 15.1 6.7 14.3C6.7 13.3 7.6 12.8 8.6 12.8C9.9 12.8 10.7 13.5 11 14.1L8.7 15.3C8.3 14.6 7.8 14.3 7.3 14.3C6.3 14.3 5.6 15.1 5.6 16.2C5.6 16.7 5.8 17.3 6.4 17.6L6.6 17.7C7.6 18.1 8.2 18.5 8.2 19.3C8.2 20.2 7.3 20.7 6.3 20.7C5.1 20.7 4.4 20.1 4.1 19.6L6.3 18.6C6.6 19.1 7 19.4 7.4 19.4C8.3 19.4 8.9 18.8 8.9 17.9C8.9 17.3 8.7 16.8 7.9 16.5L7.7 16.4C6.7 16 6.1 15.6 6.1 14.8C6.1 13.8 7 13.3 8 13.3C9.3 13.3 10.1 14 10.4 14.6L8.1 15.8C7.7 15.1 7.2 14.8 6.7 14.8C5.7 14.8 5 15.6 5 16.7C5 17.2 5.2 17.8 5.8 18.1L6 18.2C7 18.6 7.6 19 7.6 19.8C7.6 20.7 6.7 21.2 5.7 21.2C4.5 21.2 3.8 20.6 3.5 20.1L5.7 19.1C6 19.6 6.4 19.9 6.8 19.9C7.7 19.9 8.3 19.3 8.3 18.4C8.3 17.8 8.1 17.3 7.3 17L7.1 16.9C6.1 16.5 5.5 16.1 5.5 15.3C5.5 14.3 6.4 13.8 7.4 13.8C8.7 13.8 9.5 14.5 9.8 15.1L7.5 16.3C7.1 15.6 6.6 15.3 6.1 15.3C5.1 15.3 4.4 16.1 4.4 17.2C4.4 17.7 4.6 18.3 5.2 18.6L5.4 18.7C6.4 19.1 7 19.5 7 20.3C7 21.2 6.1 21.7 5.1 21.7C3.9 21.7 3.2 21.1 2.9 20.6L5.1 19.6C5.4 20.1 5.8 20.4 6.2 20.4C7.1 20.4 7.7 19.8 7.7 18.9C7.7 18.3 7.5 17.8 6.7 17.5L6.5 17.4C5.5 17 4.9 16.6 4.9 15.8C4.9 14.8 5.8 14.3 6.8 14.3C8.1 14.3 8.9 15 9.2 15.6L6.9 16.8C6.5 16.1 6 15.8 5.5 15.8C4.5 15.8 3.8 16.6 3.8 17.7C3.8 18.2 4 18.8 4.6 19.1L4.8 19.2C5.8 19.6 6.4 20 6.4 20.8C6.4 21.7 5.5 22.2 4.5 22.2C3.3 22.2 2.6 21.6 2.3 22.1L4.5 21.1C4.8 21.6 5.2 21.9 5.6 21.9C6.5 21.9 7.1 21.3 7.1 20.4C7.1 19.8 6.9 19.3 6.1 19L5.9 18.9C4.9 18.5 4.3 18.1 4.3 17.3C4.3 16.3 5.2 15.8 6.2 15.8C7.5 15.8 8.3 16.5 8.6 17.1L6.3 18.3C5.9 17.6 5.4 17.3 4.9 17.3C3.9 17.3 3.2 18.1 3.2 19.2C3.2 19.7 3.4 20.3 4 20.6L4.2 20.7C5.2 21.1 5.8 21.5 5.8 22.3C5.8 23.2 4.9 23.7 3.9 23.7C2.7 23.7 2 23.1 1.7 22.6L3.9 21.6C4.2 22.1 4.6 22.4 5 22.4C5.9 22.4 6.5 21.8 6.5 20.9C6.5 20.3 6.3 19.8 5.5 19.5L5.3 19.4C4.3 19 3.7 18.6 3.7 17.8C3.7 16.8 4.6 16.3 5.6 16.3C6.9 16.3 7.7 17 8 17.6L5.7 18.8C5.3 18.1 4.8 17.8 4.3 17.8C3.3 17.8 2.6 18.6 2.6 19.7C2.6 20.2 2.8 20.8 3.4 21.1L3.6 21.2C4.6 21.6 5.2 22 5.2 22.8C5.2 23.7 4.3 24.2 3.3 24.2C2.1 24.2 1.4 23.6 1.1 23.1L3.3 22.1C3.6 22.6 4 22.9 4.4 22.9C5.3 22.9 5.9 22.3 5.9 21.4C5.9 20.8 5.7 20.3 4.9 20L4.7 19.9C3.7 19.5 3.1 19.1 3.1 18.3C3.1 17.3 4 16.8 5 16.8C6.3 16.8 7.1 17.5 7.4 18.1L5.1 19.3C4.7 18.6 4.2 18.3 3.7 18.3C2.7 18.3 2 19.1 2 20.2C2 20.7 2.2 21.3 2.8 21.6L3 21.7C4 22.1 4.6 22.5 4.6 23.3C4.6 24.2 3.7 24.7 2.7 24.7C1.5 24.7 0.8 24.1 0.5 23.6L2.7 22.6C3 23.1 3.4 23.4 3.8 23.4C4.7 23.4 5.3 22.8 5.3 21.9C5.3 21.3 5.1 20.8 4.3 20.5L4.1 20.4C3.1 20 2.5 19.6 2.5 18.8C2.5 17.8 3.4 17.3 4.4 17.3C5.7 17.3 6.5 18 6.8 18.6L4.5 19.8C4.1 19.1 3.6 18.8 3.1 18.8C2.1 18.8 1.4 19.6 1.4 20.7C1.4 21.2 1.6 21.8 2.2 22.1L2.4 22.2C3.4 22.6 4 23 4 23.8C4 24.7 3.1 25.2 2.1 25.2C0.9 25.2 0.2 24.6 0 24.1"
                                                fill="#142688"
                                            />
                                        </svg>

                                        <svg
                                            class="h-6"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <circle
                                                cx="7"
                                                cy="12"
                                                r="7"
                                                fill="#EA001B"
                                            />
                                            <circle
                                                cx="17"
                                                cy="12"
                                                r="7"
                                                fill="#F79E1B"
                                            />
                                            <path
                                                d="M12 12C12 8.68629 14.6863 6 18 6V18C14.6863 18 12 15.3137 12 12Z"
                                                fill="#FF5F00"
                                            />
                                        </svg>

                                        <svg
                                            class="h-7"
                                            viewBox="0 0 24 24"
                                            fill="#006FCF"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <rect
                                                width="24"
                                                height="24"
                                                rx="3"
                                            />
                                            <path
                                                d="M11.6431 16.14V17H8.25313V16.14H11.6431ZM9.94813 14.53V13.56L12 12L9.94813 10.44V9.47H14.0531V10.44L12.0011 12L14.0531 13.56V14.53H9.94813ZM8.25313 7.86V7H15.7531V7.86H8.25313Z"
                                                fill="white"
                                            />
                                        </svg>

                                        <svg
                                            class="h-6"
                                            viewBox="0 0 14 17"
                                            fill="none"
                                            xmlns="http://www.w3.org/2000/svg"
                                        >
                                            <path
                                                d="M10.589 1.43262H4.72901C4.28901 1.43262 3.93901 1.78262 3.88901 2.22262L2.00901 15.0026C1.96901 15.3926 2.26901 15.7326 2.66901 15.7326H5.43901C5.83901 15.7326 6.18901 15.4226 6.25901 15.0326L6.52901 13.4326C6.56901 13.1926 6.76901 13.0026 7.00901 13.0026H9.37901C12.319 13.0026 14.199 11.2326 14.829 7.82262C15.179 5.86262 14.639 4.11262 13.219 2.94262C12.299 2.15262 11.399 1.63262 10.589 1.43262ZM9.41901 11.4526H7.07901L7.54901 8.79262C7.58901 8.55262 7.78901 8.36262 8.02901 8.36262H8.50901C10.059 8.36262 10.979 9.07262 10.749 10.4326C10.589 11.2326 10.029 11.4526 9.41901 11.4526ZM11.329 6.84262C10.759 7.79262 9.61901 8.00262 8.68901 8.00262H8.20901L8.90901 3.73262C8.94901 3.49262 9.14901 3.30262 9.38901 3.30262H9.86901C11.169 3.30262 12.069 3.63262 12.489 4.70262C12.759 5.37262 12.359 6.20262 11.329 6.84262Z"
                                                fill="#253B80"
                                            />
                                            <path
                                                d="M8.88995 0H3.02995C2.58995 0 2.23995 0.35 2.18995 0.79L0.309953 13.57C0.269953 13.96 0.569953 14.3 0.969953 14.3H4.49995C4.89995 14.3 5.24995 13.99 5.31995 13.6L5.58995 12C5.62995 11.76 5.82995 11.57 6.06995 11.57H8.43995C11.38 11.57 13.26 9.8 13.89 6.39C14.24 4.43 13.7 2.68 12.28 1.51C11.36 0.72 10.46 0.2 9.65995 0H8.88995Z"
                                                fill="#179BD7"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import CouponSection from "@/Components/Checkout/CouponSection.vue";
import LoyaltySection from "@/Components/Checkout/LoyaltySection.vue";
import { ref, computed } from "vue";

// --- DATA REFS ---

const addressCards = ref([
    {
        id: 1,
        name: "Home",
        address: "123 Sunshine Ave, Los Angeles, CA 90001",
        last4: "1234",
    },
    {
        id: 2,
        name: "Work",
        address: "456 Business Rd, San Francisco, CA 94103",
        last4: "5678",
    },
]);

const cartItems = ref([
    {
        id: 1,
        name: "High-Performance Running Shoes",
        qty: 1,
        price: 120.0,
        weight: 3.0,
        image: "https://via.placeholder.com/150/EEEEEE/808080?text=Shoes",
    },
    {
        id: 2,
        name: "Premium Cotton T-Shirt (Blue)",
        qty: 2,
        price: 25.0,
        weight: 1.0,
        image: "https://via.placeholder.com/150/EEEEEE/808080?text=Shirt",
    },
    {
        id: 3,
        name: "Wireless Noise-Cancelling Headphones",
        qty: 1,
        price: 249.5,
        weight: 2.0,
        image: "https://via.placeholder.com/150/EEEEEE/808080?text=Headphones",
    },
]);

const shippingOptions = ref([
    { id: 1, name: "Sea Freight", date: "Friday, Aug 29", price: 0.0 },
    { id: 2, name: "Fedex Economy", date: "Wednesday, Aug 13", price: 12.99 },
    { id: 3, name: "DHL Express", date: "Monday, Aug 11", price: 22.5 },
]);

// New Refs for Optional Services
const packingOptions = ref([
    { id: "fragile", text: "Fragile Stickers", price: 1.5, selected: false },
    { id: "padding", text: "Extra Padding", price: 4.0, selected: false },
    {
        id: "discardBoxes",
        text: "Discard Shoe Boxes",
        price: 0.0,
        selected: false,
    },
    {
        id: "originalBoxes",
        text: "Ship in Original Boxes",
        price: 0.0,
        selected: true,
    },
]);
const wantsInsurance = ref(false);
const handlingFee = ref(10.0);

const selectedAddressId = ref(1);
const selectedShippingId = ref(1);

// --- COMPUTED PROPERTIES FOR DYNAMIC CALCULATIONS ---

const itemsSubtotal = computed(() =>
    cartItems.value.reduce((total, item) => total + item.price * item.qty, 0)
);
const totalItems = computed(() =>
    cartItems.value.reduce((total, item) => total + item.qty, 0)
);
const totalWeight = computed(() =>
    cartItems.value.reduce((total, item) => total + item.weight * item.qty, 0)
);

const shippingCost = computed(() => {
    const selectedOption = shippingOptions.value.find(
        (option) => option.id === selectedShippingId.value
    );
    return selectedOption ? selectedOption.price : 0;
});

// New Computed Properties for Optional Services
const packingOptionsCost = computed(() => {
    return packingOptions.value
        .filter((opt) => opt.selected)
        .reduce((total, opt) => total + opt.price, 0);
});
const insuranceCost = computed(() => {
    // Example: 1.5% of item value
    return wantsInsurance.value ? itemsSubtotal.value * 0.015 : 0;
});

const totalBeforeTax = computed(() => {
    return (
        itemsSubtotal.value +
        shippingCost.value +
        packingOptionsCost.value +
        insuranceCost.value +
        handlingFee.value
    );
});

const estimatedTax = computed(() => totalBeforeTax.value * 0.08); // Example tax rate of 8%

const grandTotal = computed(() => totalBeforeTax.value + estimatedTax.value);

// Coupon and Loyalty refs
const couponDiscount = ref(0);
const loyaltyDiscount = ref(0);

const finalTotal = computed(
    () => grandTotal.value - couponDiscount.value - loyaltyDiscount.value
);

// --- METHODS ---

const addCard = () => {
    alert("Redirecting to Add New Address page...");
};
const editCard = (card) => {
    alert("Opening modal to edit: " + card.name);
};
const deleteCard = (id) => {
    if (confirm("Are you sure you want to delete this address?")) {
        addressCards.value = addressCards.value.filter(
            (card) => card.id !== id
        );
        if (selectedAddressId.value === id) {
            selectedAddressId.value =
                addressCards.value.length > 0 ? addressCards.value[0].id : null;
        }
    }
};

// Coupon and Loyalty event handlers
const handleCouponApplied = (discount) => {
    couponDiscount.value = discount;
};

const handleCouponRemoved = () => {
    couponDiscount.value = 0;
};

const handleLoyaltyApplied = (discount) => {
    loyaltyDiscount.value = discount;
};

const handleLoyaltyRemoved = () => {
    loyaltyDiscount.value = 0;
};
</script>
```
