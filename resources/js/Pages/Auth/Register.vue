<script setup>
import AuthLayout from "@/Layouts/AuthLayout.vue";
import { VueTelInput } from 'vue-tel-input';
import 'vue-tel-input/vue-tel-input.css';
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import Checkbox from "@/Components/Checkbox.vue";
import { computed, ref, onMounted, watch } from "vue";
import axios from "axios";

const form = useForm({
    first_name: "",
    last_name: "",
    email: "",
    phone: "",
    password: "",
    country_id: "",
    country: "",
    country_code: "",
    state_id: "",
    state: "",
    city_id: "",
    city: "",
    address: "",
    zip_code: "",
    tax_id: "",
    year: "",
    month: "",
    day: "",
    ref: "", // Referral code from URL
});

// Location dropdown options
const countryOptions = ref([]);
const stateOptions = ref([]);
const cityOptions = ref([]);
const loadingCountries = ref(false);
const loadingStates = ref(false);
const loadingCities = ref(false);
const countryHasPostalCode = ref(false);

// Capture referral code from URL on mount
onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const refCode = urlParams.get('ref');
    if (refCode) {
        form.ref = refCode;
    }
    fetchCountries();
});

const months = [
    "January",
    "February",
    "March",
    "April",
    "May",
    "June",
    "July",
    "August",
    "September",
    "October",
    "November",
    "December",
];

const days = computed(() => Array.from({ length: 31 }, (_, i) => i + 1));

const years = computed(() => {
    const startYear = 1922;
    const currentYear = new Date().getFullYear();
    return Array.from(
        { length: currentYear - startYear + 1 },
        (_, i) => currentYear - i
    );
});

// Fetch countries
const fetchCountries = async () => {
    loadingCountries.value = true;
    try {
        const response = await axios.get('/api/locations/countries');
        countryOptions.value = response.data;
    } catch (error) {
        console.error('Failed to fetch countries:', error);
    } finally {
        loadingCountries.value = false;
    }
};

// Fetch states for selected country
const fetchStates = async (countryId) => {
    if (!countryId) {
        stateOptions.value = [];
        return;
    }
    loadingStates.value = true;
    try {
        const response = await axios.get(`/api/locations/states/${countryId}`);
        stateOptions.value = response.data;
    } catch (error) {
        console.error('Failed to fetch states:', error);
    } finally {
        loadingStates.value = false;
    }
};

// Fetch cities for selected state
const fetchCities = async (stateId) => {
    if (!stateId) {
        cityOptions.value = [];
        return;
    }
    loadingCities.value = true;
    try {
        const response = await axios.get(`/api/locations/cities/${stateId}`);
        cityOptions.value = response.data;
    } catch (error) {
        console.error('Failed to fetch cities:', error);
    } finally {
        loadingCities.value = false;
    }
};

const onCountryChange = () => {
    const selectedCountry = countryOptions.value.find(c => c.id == form.country_id);
    if (selectedCountry) {
        form.country = selectedCountry.name;
        form.country_code = selectedCountry.code;
        countryHasPostalCode.value = selectedCountry.has_postal_code;
        
        // Clear dependent fields
        form.state_id = "";
        form.state = "";
        form.city_id = "";
        form.city = "";
        form.zip_code = "";
        stateOptions.value = [];
        cityOptions.value = [];
        
        fetchStates(selectedCountry.id);
    }
};

const onStateChange = () => {
    const selectedState = stateOptions.value.find(s => s.id == form.state_id);
    if (selectedState) {
        form.state = selectedState.name;
        
        form.city_id = "";
        form.city = "";
        form.zip_code = "";
        cityOptions.value = [];
        
        fetchCities(selectedState.id);
    }
};

const onCityChange = () => {
    const selectedCity = cityOptions.value.find(c => c.id == form.city_id);
    if (selectedCity) {
        form.city = selectedCity.name;
        if (selectedCity.postal_code) {
            form.zip_code = selectedCity.postal_code;
        } else {
            form.zip_code = "";
        }
    }
};

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <AuthLayout heading="Create Account" subheading="Get your tax-free USA shipping address instantly.">
        <Head title="Register" />

        <form @submit.prevent="submit" class="mt-8 space-y-6">
            
            <!-- 1. Account Identity -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider border-b pb-1">Account Identity</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel required  for="first_name" value="First Name" />
                        <TextInput
                            id="first_name"
                            type="text"
                            class="block w-full mt-1"
                            v-model="form.first_name"
                            required
                            autofocus
                            autocomplete="given-name"
                            placeholder="John"
                        />
                        <InputError class="mt-2" :message="form.errors.first_name" />
                    </div>
                    <div>
                        <InputLabel required  for="last_name" value="Last Name" />
                        <TextInput
                            id="last_name"
                            type="text"
                            class="block w-full mt-1"
                            v-model="form.last_name"
                            required
                            autocomplete="family-name"
                            placeholder="Doe"
                        />
                        <InputError class="mt-2" :message="form.errors.last_name" />
                    </div>
                </div>

                <div>
                    <InputLabel required  for="email" value="Email Address" />
                    <TextInput
                        id="email"
                        type="email"
                        class="block w-full mt-1"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="john@example.com"
                    />
                    <InputError class="mt-2" :message="form.errors.email" />
                </div>

                <div>
                    <InputLabel required  for="password" value="Password" />
                    <TextInput
                        id="password"
                        type="password"
                        class="block w-full mt-1"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="••••••••"
                    />
                    <InputError class="mt-2" :message="form.errors.password" />
                </div>
            </div>

            <!-- 2. Contact Info -->
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider border-b pb-1">Contact Info</h3>
                
                <div>
                     <InputLabel required  for="phone" value="Mobile Phone" />
                     <div class="mt-1">
                        <vue-tel-input 
                            v-model="form.phone" 
                            mode="international"
                            :preferredCountries="['US', 'GB', 'CA']"
                            :inputOptions="{ placeholder: 'Enter phone number', required: true, class: 'w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-base' }"
                            class="focus-within:ring-1 focus-within:ring-primary-500 focus-within:border-primary-500 rounded-md border-gray-300"
                        ></vue-tel-input>
                     </div>
                     <InputError class="mt-2" :message="form.errors.phone" />
                </div>
            </div>

             <!-- 3. Shipping Address - Cascading Dropdowns -->
             <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider border-b pb-1">Your Address</h3>
                
                <!-- Country Dropdown -->
                <div>
                    <InputLabel required  for="country" value="Country" />
                    <select
                        id="country"
                        class="block w-full mt-1 border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                        v-model="form.country_id"
                        required
                        @change="onCountryChange"
                        :disabled="loadingCountries"
                    >
                        <option value="" disabled>
                            {{ loadingCountries ? 'Loading countries...' : 'Select Country...' }}
                        </option>
                        <option :value="country.id" v-for="country in countryOptions" :key="country.id">
                            {{ country.name }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.country || form.errors.country_id" />
                </div>

                <!-- Street Address -->
                <div>
                    <InputLabel required  for="address" value="Street Address" />
                    <TextInput
                        id="address"
                        type="text"
                        class="block w-full mt-1"
                        v-model="form.address"
                        required
                        placeholder="123 Main St"
                    />
                    <InputError class="mt-2" :message="form.errors.address" />
                </div>

                <!-- State and City Dropdowns -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- State/Parish Dropdown -->
                    <div>
                        <InputLabel required  for="state" value="State / Parish" />
                        <select
                            id="state"
                            class="block w-full mt-1 border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                            v-model="form.state_id"
                            @change="onStateChange"
                            :disabled="loadingStates || stateOptions.length === 0"
                            required
                        >
                            <option value="" disabled>
                                <template v-if="loadingStates">Loading...</template>
                                <template v-else-if="!form.country_id">Select country first</template>
                                <template v-else-if="stateOptions.length === 0">No states available</template>
                                <template v-else>Select State/Parish...</template>
                            </option>
                            <option :value="state.id" v-for="state in stateOptions" :key="state.id">
                                {{ state.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.state || form.errors.state_id" />
                    </div>

                    <!-- City Dropdown -->
                    <div>
                        <InputLabel required  for="city" value="City" />
                        <select
                            id="city"
                            class="block w-full mt-1 border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                            v-model="form.city_id"
                            @change="onCityChange"
                            :disabled="loadingCities || cityOptions.length === 0"
                            required
                        >
                            <option value="" disabled>
                                <template v-if="loadingCities">Loading...</template>
                                <template v-else-if="!form.state_id">Select state first</template>
                                <template v-else-if="cityOptions.length === 0">No cities available</template>
                                <template v-else>Select City...</template>
                            </option>
                            <option :value="city.id" v-for="city in cityOptions" :key="city.id">
                                {{ city.name }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.city || form.errors.city_id" />
                    </div>
                </div>

                <!-- Postal Code (only shown if country uses postal codes) -->
                <div v-if="countryHasPostalCode || form.zip_code">
                    <InputLabel required  for="zip_code" value="Zip / Postal Code" />
                    <TextInput
                        id="zip_code"
                        type="text"
                        class="block w-full mt-1"
                        v-model="form.zip_code"
                        :placeholder="form.zip_code ? '' : 'Postal Code'"
                        :readonly="!!form.zip_code && !!form.city_id"
                        :class="{ 'bg-gray-50': !!form.zip_code && !!form.city_id }"
                    />
                    <p v-if="form.zip_code && form.city_id" class="mt-1 text-xs text-gray-500">
                        Auto-filled based on city selection
                    </p>
                    <InputError class="mt-2" :message="form.errors.zip_code" />
                </div>
            </div>

            <!-- 4. Compliance -->
            <div class="space-y-4">
                 <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wider border-b pb-1">Compliance</h3>
                 
                 <div>
                    <InputLabel required  value="Date of Birth" />
                    <div class="flex gap-2">
                        <select class="block w-full mt-1 border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm" v-model="form.year" required>
                             <option value="" disabled>Year</option>
                             <option :value="year" v-for="year in years" :key="year">{{ year }}</option>
                        </select>
                        <select class="block w-full mt-1 border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm" v-model="form.month" required>
                             <option value="" disabled>Month</option>
                             <option :value="month" v-for="month in months" :key="month">{{ month }}</option>
                        </select>
                        <select class="block w-full mt-1 border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm" v-model="form.day" required>
                             <option value="" disabled>Day</option>
                             <option :value="day" v-for="day in days" :key="day">{{ day }}</option>
                        </select>
                    </div>
                     <p class="mt-1 text-xs text-gray-500">You must be over 18 years old to create an account.</p>
                 </div>

                 <div>
                    <InputLabel for="tax_id" value="Tax ID (Optional)" />
                    <TextInput
                        id="tax_id"
                        type="text"
                        class="block w-full mt-1"
                        v-model="form.tax_id"
                        placeholder=""
                    />
                 </div>

                 <div class="flex items-start">
                     <Checkbox name="terms" required class="mt-1" />
                     <span class="ms-2 text-sm text-gray-600">
                        By signing up I agree to the <a href="https://www.mymalls.com/en/terms" target="_blank" class="underline text-primary-600 hover:text-primary-900">terms of service</a>.
                     </span>
                 </div>
            </div>

            <div class="pt-4">
                <PrimaryButton class="w-full justify-center py-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Create Account
                </PrimaryButton>
            </div>
             <div class="text-center text-xs text-gray-500 mt-4">
                By submitting this form you agree to receive occasional emails from us. You can unsubscribe at any time.
            </div>
             <div class="text-center text-sm text-gray-600 mt-2">
                Already have an account? 
                <Link :href="route('login')" class="font-medium text-primary-600 hover:text-primary-500">
                    Log in
                </Link>
            </div>
        </form>
    </AuthLayout>
</template>
