<script setup>
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import { VueTelInput } from "vue-tel-input";
import "vue-tel-input/vue-tel-input.css";
import { ref, watch, onMounted } from "vue";
import axios from "axios";

/**
 * Reusable Address Form Fields Component
 * Used by: AddressBook.vue modal, ShipAddress.vue modal, Register.vue
 *
 * Features cascading dropdowns: Country → State → City
 * Postal codes are auto-filled when available, hidden when not.
 */

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    /** Pass countries as prop to avoid extra API call (useful for SSR) */
    countries: {
        type: Array,
        default: null,
    },
});

// Local state for dropdown options
const countryOptions = ref([]);
const stateOptions = ref([]);
const cityOptions = ref([]);

// Loading states
const loadingCountries = ref(false);
const loadingStates = ref(false);
const loadingCities = ref(false);

// Track whether country/state have postal codes
const countryHasPostalCode = ref(false);

/**
 * Fetch countries from API
 */
const fetchCountries = async () => {
    if (props.countries) {
        countryOptions.value = props.countries;
        return;
    }
    
    loadingCountries.value = true;
    try {
        const response = await axios.get('/api/locations/countries');
        countryOptions.value = response.data;
    } catch (error) {
        console.error('Failed to fetch countries:', error);
        countryOptions.value = [];
    } finally {
        loadingCountries.value = false;
    }
};

/**
 * Fetch states for selected country
 */
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
        stateOptions.value = [];
    } finally {
        loadingStates.value = false;
    }
};

/**
 * Fetch cities for selected state
 */
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
        cityOptions.value = [];
    } finally {
        loadingCities.value = false;
    }
};

/**
 * Handle country selection
 */
const onCountryChange = () => {
    const selectedCountry = countryOptions.value.find((c) => c.id == props.form.country_id);
    
    if (selectedCountry) {
        props.form.country = selectedCountry.name;
        props.form.country_code = selectedCountry.code;
        countryHasPostalCode.value = selectedCountry.has_postal_code;
        
        // Clear dependent fields
        props.form.state_id = "";
        props.form.state = "";
        props.form.city_id = "";
        props.form.city = "";
        props.form.postal_code = "";
        
        stateOptions.value = [];
        cityOptions.value = [];
        
        // Fetch states for this country
        fetchStates(selectedCountry.id);
    }
};

/**
 * Handle state selection
 */
const onStateChange = () => {
    const selectedState = stateOptions.value.find((s) => s.id == props.form.state_id);
    
    if (selectedState) {
        props.form.state = selectedState.name;
        
        // Clear dependent fields
        props.form.city_id = "";
        props.form.city = "";
        props.form.postal_code = "";
        
        cityOptions.value = [];
        
        // Fetch cities for this state
        fetchCities(selectedState.id);
    }
};

/**
 * Handle city selection
 */
const onCityChange = () => {
    const selectedCity = cityOptions.value.find((c) => c.id == props.form.city_id);
    
    if (selectedCity) {
        props.form.city = selectedCity.name;
        
        // Auto-fill postal code if available
        if (selectedCity.postal_code) {
            props.form.postal_code = selectedCity.postal_code;
        } else {
            props.form.postal_code = "";
        }
    }
};

const onPhoneValidate = (phoneObject) => {
    // Optional: You can extract more details here if needed
    // phoneObject contains { number, valid, country, ... }
};

// Initialize on mount
onMounted(() => {
    fetchCountries();
    
    // If editing an existing address with country_id, load states
    if (props.form.country_id) {
        fetchStates(props.form.country_id).then(() => {
            if (props.form.state_id) {
                fetchCities(props.form.state_id);
            }
        });
        
        // Set postal code visibility based on country
        const country = countryOptions.value.find(c => c.id == props.form.country_id);
        if (country) {
            countryHasPostalCode.value = country.has_postal_code;
        }
    }
});
</script>

<template>
    <div class="grid gap-3">
        <!-- Address Label/Name and Full Name -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
             <div>
                <InputLabel value="Address Name" />
                <TextInput
                    v-model="form.address_name"
                    placeholder="e.g. Home, Office"
                    class="w-full mt-1"
                />
                <InputError class="mt-1" :message="errors.address_name" />
            </div>

            <div>
                <InputLabel value="Full Name" />
                <TextInput
                    v-model="form.full_name"
                    placeholder="Full Name"
                    class="w-full mt-1"
                />
                <InputError class="mt-1" :message="errors.full_name" />
            </div>
        </div>

        <div class="border-t border-gray-100 my-1"></div>

        <!-- Country Dropdown -->
        <div>
            <InputLabel value="Country" />
            <select
                class="block w-full mt-1 border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                v-model="form.country_id"
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
            <InputError class="mt-1" :message="errors.country || errors.country_id" />
        </div>

        <!-- Address Lines -->
        <div>
            <InputLabel value="Street Address" />
            <TextInput
                v-model="form.address_line_1"
                placeholder="Address Line 1"
                class="w-full mt-1"
            />
            <InputError class="mt-1" :message="errors.address_line_1" />
        </div>

        <div>
            <TextInput
                v-model="form.address_line_2"
                placeholder="Address Line 2 (Optional)"
                class="w-full"
            />
            <InputError class="mt-1" :message="errors.address_line_2" />
        </div>

        <!-- State/Parish Dropdown -->
        <div class="grid grid-cols-2 gap-3">
            <div>
                <InputLabel value="State/Parish" />
                <select
                    class="block w-full mt-1 border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                    v-model="form.state_id"
                    @change="onStateChange"
                    :disabled="loadingStates || stateOptions.length === 0"
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
                <InputError class="mt-1" :message="errors.state || errors.state_id" />
            </div>

            <!-- City Dropdown -->
            <div>
                <InputLabel value="City" />
                <select
                    class="block w-full mt-1 border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm"
                    v-model="form.city_id"
                    @change="onCityChange"
                    :disabled="loadingCities || cityOptions.length === 0"
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
                <InputError class="mt-1" :message="errors.city || errors.city_id" />
            </div>
        </div>

        <!-- Postal Code (shown only if country uses postal codes) -->
        <div class="grid grid-cols-2 gap-3">
            <div v-if="countryHasPostalCode || form.postal_code">
                <InputLabel value="Zip/Postal Code" />
                <TextInput
                    v-model="form.postal_code"
                    placeholder="Postal Code"
                    class="w-full mt-1"
                    :readonly="!!form.postal_code && !!form.city_id"
                    :class="{ 'bg-gray-50': !!form.postal_code && !!form.city_id }"
                />
                <p v-if="form.postal_code && form.city_id" class="mt-1 text-xs text-gray-500">
                    Auto-filled based on city selection
                </p>
                <InputError class="mt-1" :message="errors.postal_code" />
            </div>

            <!-- Phone -->
            <div :class="{ 'col-span-2': !countryHasPostalCode && !form.postal_code }">
                <InputLabel value="Phone Number" />
                <div class="mt-1">
                    <vue-tel-input
                        v-model="form.phone_number"
                        mode="international"
                        :preferredCountries="['US', 'GB', 'CA']"
                        :inputOptions="{ placeholder: 'Enter phone number', required: true, class: 'w-full border-gray-300 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm text-base' }"
                        class="focus-within:ring-1 focus-within:ring-primary-500 focus-within:border-primary-500 rounded-md border-gray-300"
                        @validate="onPhoneValidate"
                    ></vue-tel-input>
                </div>
                <InputError class="mt-1" :message="errors.phone_number" />
            </div>
        </div>
    </div>
</template>
