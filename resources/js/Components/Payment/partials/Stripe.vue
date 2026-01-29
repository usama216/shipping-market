<script setup>
import { ref, onMounted, computed, watch, nextTick } from "vue";
import { loadStripe } from "@stripe/stripe-js";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { useForm, usePage } from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    stripeKey: String,
    savedAddresses: {
        type: Array,
        default: () => [],
    },
});

const user = usePage().props.auth.user;
const stripePublicKey = props?.stripeKey;

// State
const stripe = ref(null);
const elements = ref(null);
const cardNumber = ref(null);
const cardExpiry = ref(null);
const cardCvc = ref(null);
const isLoading = ref(true);
const isProcessing = ref(false);
const stripeError = ref(null);
const cardBrand = ref(null);
const showBillingAddress = ref(false);
const selectedSavedAddress = ref(null);

// Card brand icons mapping
const cardBrandIcons = {
    visa: { icon: "fa-brands fa-cc-visa", color: "text-blue-600" },
    mastercard: { icon: "fa-brands fa-cc-mastercard", color: "text-orange-500" },
    amex: { icon: "fa-brands fa-cc-amex", color: "text-blue-500" },
    discover: { icon: "fa-brands fa-cc-discover", color: "text-orange-600" },
    diners: { icon: "fa-brands fa-cc-diners-club", color: "text-gray-600" },
    jcb: { icon: "fa-brands fa-cc-jcb", color: "text-green-600" },
    unionpay: { icon: "fa-solid fa-credit-card", color: "text-red-600" },
    unknown: { icon: "fa-solid fa-credit-card", color: "text-gray-400" },
};

// Form data
const form = useForm({
    token: null,
    card_holder_name: user?.name || "",
    address_line1: "",
    address_line2: "",
    country: "",
    state: "",
    city: "",
    postal_code: "",
    country_code: "",
    phone_number: "",
});

const emit = defineEmits(["submitForm"]);

// Computed
const canSubmit = computed(() => {
    return (
        !isProcessing.value &&
        !isLoading.value &&
        form.card_holder_name.trim() !== ""
    );
});

const currentCardIcon = computed(() => {
    return cardBrandIcons[cardBrand.value] || cardBrandIcons.unknown;
});

// Watch for saved address selection
watch(selectedSavedAddress, (addressId) => {
    if (addressId && props.savedAddresses) {
        const address = props.savedAddresses.find(a => a.id === addressId);
        if (address) {
            form.address_line1 = address.address_line_1 || address.address_line1 || "";
            form.address_line2 = address.address_line_2 || address.address_line2 || "";
            form.country = address.country || "";
            form.state = address.state || "";
            form.city = address.city || "";
            form.postal_code = address.postal_code || "";
            form.country_code = address.country_code || "";
            form.phone_number = address.phone_number || "";
        }
    }
});

// Initialize Stripe Elements
const initStripeElements = async () => {
    try {
        stripeError.value = null;

        if (!stripePublicKey) {
            throw new Error("Stripe public key is not configured");
        }

        // Load Stripe SDK
        stripe.value = await loadStripe(stripePublicKey);
        
        if (!stripe.value) {
            throw new Error("Failed to load Stripe");
        }

        elements.value = stripe.value.elements();

        // Create card elements with styling
        const style = {
            base: {
                fontSize: "16px",
                color: "#374151",
                fontFamily: '"Inter", system-ui, sans-serif',
                "::placeholder": {
                    color: "#9CA3AF",
                },
            },
            invalid: {
                color: "#EF4444",
                iconColor: "#EF4444",
            },
        };

        // Create elements (but don't mount yet)
        cardNumber.value = elements.value.create("cardNumber", { 
            style,
            showIcon: false, // We'll show our own icon
        });

        cardExpiry.value = elements.value.create("cardExpiry", { style });
        cardCvc.value = elements.value.create("cardCvc", { style });

        // Listen for card brand detection and errors
        cardNumber.value.on("change", (event) => {
            stripeError.value = event.error ? event.error.message : null;
            // Stripe provides the card brand in the change event
            cardBrand.value = event.brand || null;
        });

        // Hide loading state to render form elements in DOM
        isLoading.value = false;

        // Wait for Vue to render the DOM, then mount Stripe elements
        await nextTick();
        
        // Now the DOM elements exist, safe to mount
        cardNumber.value.mount("#card-number");
        cardExpiry.value.mount("#card-expiry");
        cardCvc.value.mount("#card-cvc");

    } catch (error) {
        console.error("Failed to initialize Stripe:", error);
        stripeError.value = error.message || "Failed to load payment form";
        isLoading.value = false;
    }
};

// Create token and submit form
const createToken = async () => {
    if (!canSubmit.value) return;

    try {
        isProcessing.value = true;
        stripeError.value = null;

        const tokenData = {
            name: form.card_holder_name,
        };

        // Only include address if billing address is shown and filled
        if (showBillingAddress.value && form.address_line1) {
            tokenData.address_line1 = form.address_line1;
            tokenData.address_line2 = form.address_line2;
            tokenData.address_city = form.city;
            tokenData.address_state = form.state;
            tokenData.address_zip = form.postal_code;
            tokenData.address_country = form.country;
        }

        const { token, error } = await stripe.value.createToken(
            cardNumber.value,
            tokenData
        );

        if (error) {
            stripeError.value = error.message;
            return;
        }

        form.token = token;
        emit("submitForm", { form: form });
    } catch (error) {
        console.error("Token creation failed:", error);
        stripeError.value = error.message || "Payment processing failed";
    } finally {
        isProcessing.value = false;
    }
};

onMounted(() => {
    initStripeElements();
});
</script>

<template>
    <div class="space-y-5">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center py-8">
            <div class="text-center">
                <i class="text-3xl fa-solid fa-spinner fa-spin text-primary-500"></i>
                <p class="mt-2 text-sm text-gray-500">Loading payment form...</p>
            </div>
        </div>

        <template v-else>
            <!-- Card Details Section -->
            <div class="space-y-4">
                <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700">
                    <i class="fa-solid fa-credit-card text-primary-500"></i>
                    Card Details
                </h3>

                <!-- Card Holder Name -->
                <div>
                    <InputLabel value="Card Holder Name *" />
                    <TextInput
                        v-model="form.card_holder_name"
                        class="w-full mt-1"
                        placeholder="Name as it appears on card"
                        required
                    />
                    <InputError :message="form.errors.card_holder_name" class="mt-1" />
                </div>

                <!-- Card Number with Brand Icon -->
                <div>
                    <InputLabel value="Card Number *" />
                    <div class="relative">
                        <div
                            id="card-number"
                            class="p-3 pr-12 mt-1 bg-white border border-gray-300 rounded-md focus-within:ring-2 focus-within:ring-primary-500 focus-within:border-primary-500"
                        ></div>
                        <!-- Card Brand Icon -->
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 mt-1 pointer-events-none">
                            <i 
                                :class="[currentCardIcon.icon, currentCardIcon.color, 'text-2xl transition-all duration-200']"
                            ></i>
                        </div>
                    </div>
                </div>

                <!-- Expiry & CVC -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <InputLabel value="Expiry *" />
                        <div
                            id="card-expiry"
                            class="p-3 mt-1 bg-white border border-gray-300 rounded-md focus-within:ring-2 focus-within:ring-primary-500"
                        ></div>
                    </div>
                    <div>
                        <InputLabel value="CVC *" />
                        <div
                            id="card-cvc"
                            class="p-3 mt-1 bg-white border border-gray-300 rounded-md focus-within:ring-2 focus-within:ring-primary-500"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Billing Address Toggle -->
            <div class="pt-2">
                <button
                    type="button"
                    @click="showBillingAddress = !showBillingAddress"
                    class="flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-primary-600 transition-colors"
                >
                    <i :class="showBillingAddress ? 'fa-solid fa-chevron-down' : 'fa-solid fa-chevron-right'" class="text-xs"></i>
                    <i class="fa-solid fa-location-dot text-primary-500"></i>
                    Add Billing Address (Optional)
                </button>
            </div>

            <!-- Collapsible Billing Address Section -->
            <transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 max-h-0"
                enter-to-class="opacity-100 max-h-96"
                leave-active-class="transition-all duration-200 ease-in"
                leave-from-class="opacity-100 max-h-96"
                leave-to-class="opacity-0 max-h-0"
            >
                <div v-if="showBillingAddress" class="pt-3 space-y-4 overflow-hidden border-t border-gray-100">
                    <!-- Saved Address Dropdown -->
                    <div v-if="savedAddresses && savedAddresses.length > 0">
                        <InputLabel value="Use Saved Address" />
                        <select
                            v-model="selectedSavedAddress"
                            class="w-full mt-1 text-gray-700 border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        >
                            <option :value="null">-- Select a saved address --</option>
                            <option 
                                v-for="address in savedAddresses" 
                                :key="address.id" 
                                :value="address.id"
                            >
                                {{ address.full_name || address.address_name }} - {{ address.address_line_1 || address.address_line1 }}, {{ address.city }}
                            </option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <InputLabel value="Address Line 1" />
                            <TextInput
                                v-model="form.address_line1"
                                class="w-full mt-1"
                                placeholder="Street address"
                            />
                        </div>
                        <div>
                            <InputLabel value="Address Line 2" />
                            <TextInput
                                v-model="form.address_line2"
                                class="w-full mt-1"
                                placeholder="Apt, suite (optional)"
                            />
                        </div>
                        <div>
                            <InputLabel value="Country" />
                            <TextInput
                                v-model="form.country"
                                class="w-full mt-1"
                                placeholder="Country"
                            />
                        </div>
                        <div>
                            <InputLabel value="State / Province" />
                            <TextInput
                                v-model="form.state"
                                class="w-full mt-1"
                                placeholder="State"
                            />
                        </div>
                        <div>
                            <InputLabel value="City" />
                            <TextInput
                                v-model="form.city"
                                class="w-full mt-1"
                                placeholder="City"
                            />
                        </div>
                        <div>
                            <InputLabel value="Postal Code" />
                            <TextInput
                                v-model="form.postal_code"
                                class="w-full mt-1"
                                placeholder="ZIP / Postal"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <InputLabel value="Phone (with country code)" />
                            <TextInput
                                v-model="form.phone_number"
                                class="w-full mt-1"
                                placeholder="+1 555-123-4567"
                            />
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Error Display -->
            <div
                v-if="stripeError"
                class="flex items-center gap-2 p-3 text-sm text-red-700 border border-red-200 rounded-lg bg-red-50"
            >
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ stripeError }}
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t border-gray-200">
                <PrimaryButton
                    @click="createToken"
                    :disabled="!canSubmit"
                    class="flex items-center gap-2"
                >
                    <i v-if="isProcessing" class="fa-solid fa-spinner fa-spin"></i>
                    <i v-else class="fa-solid fa-lock"></i>
                    {{ isProcessing ? "Processing..." : "Save Card Securely" }}
                </PrimaryButton>
            </div>

            <!-- Security Note -->
            <p class="flex items-center justify-center gap-1 text-xs text-gray-400">
                <i class="fa-solid fa-shield-halved"></i>
                Your card is encrypted and securely processed by Stripe.
            </p>
        </template>
    </div>
</template>
