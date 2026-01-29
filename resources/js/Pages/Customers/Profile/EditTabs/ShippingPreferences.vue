<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import Edit from "../Edit.vue";
import ShippingPreferenceAddress from "./Partials/ShippingPreferenceAddress.vue";
import PreferenceAddress from "./Partials/PreferenceAddress.vue";
import PreferredShipMethods from "./Partials/PreferredShipMethods.vue";
import Radiobox from "@/Components/Radiobox.vue";
import { ref, computed } from "vue";
import Tooltip from "@/Components/Tooltip.vue";
import Checkbox from "@/Components/Checkbox.vue";
import TextInput from "@/Components/TextInput.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    shippingPreference: Object,
    preferredShipMethods: Object,
    internationalShippingOptions: Object,
    shippingPreferenceOptions: Object,
    packingOptions: Object,
    proformaInvoiceOptions: Object,
    loginOptions: Object,
    addresses: Object,
});
const form = useForm({
    preferred_ship_method:
        props.shippingPreference?.preferred_ship_method ?? null,
    international_shipping_option:
        props.shippingPreference?.international_shipping_option ?? null,
    shipping_preference_option: props.shippingPreference
        ?.shipping_preference_option
        ? JSON.parse(props.shippingPreference.shipping_preference_option)
        : [],
    packing_option: props.shippingPreference?.packing_option
        ? JSON.parse(props.shippingPreference.packing_option)
        : [],
    maximum_weight_per_box:
        props.shippingPreference?.maximum_weight_per_box ?? 0,
    proforma_invoice_options: props.shippingPreference?.proforma_invoice_options
        ? JSON.parse(props.shippingPreference.proforma_invoice_options)
        : [],
    tax_id: props.shippingPreference?.tax_id ?? null,
    additional_email: props.shippingPreference?.additional_email ?? null,
    login_option: props.shippingPreference?.login_option
        ? JSON.parse(props.shippingPreference.login_option)
        : [],
});

const handleSetAddress = (data) => {
    router.post(route("customer.preference.changeAddress"), {
        address_type: data.type,
    });
};

const handleCancelChanges = () => {
    form.reset();
};

const handleSubmitPreferences = () => {
    form.post(route("customer.preferences.saveChange"), {
        preserveScroll: true,
        preserveState: true,
    });
};

const internationalOptions = computed(() =>
    Array.isArray(props?.internationalShippingOptions)
        ? props.internationalShippingOptions
        : []
);

const isInternationalOptionDisabled = (iso, index) => {
    // Disable last two options (as requested)
    const lastTwoDisabled =
        internationalOptions.value.length >= 2 &&
        index >= internationalOptions.value.length - 2;

    // Keep existing "inactive" behavior too
    return lastTwoDisabled || iso?.status === "inactive";
};
</script>
<template>
    <Head title="Shipping Preferences" />
    <Edit>
        <ShippingPreferenceAddress
            :address="props?.shippingPreference?.address"
            @setAddress="handleSetAddress"
        />
        <PreferenceAddress
            :address="props?.shippingPreference?.address"
            class="mb-5"
        />

        <!-- <PreferredShipMethods
            :preferredShipMethods="props?.preferredShipMethods"
        /> -->
        <div class="my-5">
            <h1 class="text-xl">Preferred Ship Method</h1>
            <label
                class="flex items-center space-x-2 mt-2"
                v-for="shipMethod in props?.preferredShipMethods"
                :key="shipMethod.id"
            >
                <Radiobox
                    v-model="form.preferred_ship_method"
                    :value="shipMethod?.id"
                />
                <span>
                    <div class="flex items-center gap-2">
                        <div>
                            {{ shipMethod?.title }}
                        </div>

                        <Tooltip :text="shipMethod?.description">
                            <i
                                class="fa-solid fa-circle-info text-primary-500 cursor-pointer"
                            ></i>
                        </Tooltip>
                    </div>
                </span>
            </label>
        </div>
        <hr />

        <div class="my-5">
            <h1 class="text-xl">International Shipping Options</h1>

            <label
                v-for="(iso, index) in internationalOptions"
                :key="iso.id"
                class="flex items-center space-x-2 mt-2 rounded-md px-2 py-1"
                :class="{
                    'bg-gray-100 text-gray-500 opacity-70 cursor-not-allowed':
                        isInternationalOptionDisabled(iso, index),
                }"
            >
                <div class="flex gap-4 items-center">
                    <div class="flex items-center gap-2">
                        <Radiobox
                            v-model="form.international_shipping_option"
                            :value="iso.id"
                            :disabled="isInternationalOptionDisabled(iso, index)"
                        />
                        <span>
                            <div class="flex items-center gap-2">
                                <div>
                                    {{ iso?.title }}
                                </div>
                                <span
                                    v-if="isInternationalOptionDisabled(iso, index)"
                                    class="text-sm text-gray-500 italic"
                                >
                                    (Not available)
                                </span>
                            </div>
                        </span>
                        <span>-</span>
                    </div>
                    <div>{{ iso?.description }}</div>
                </div>
            </label>
        </div>
        <hr />

        <div class="my-5">
            <h1 class="text-xl">Shipping Preference Options</h1>
            <label
                class="flex items-center space-x-2 mt-2"
                v-for="shippingPreference in props?.shippingPreferenceOptions"
                :key="shippingPreference.id"
            >
                <Checkbox
                    v-model="form.shipping_preference_option"
                    :value="shippingPreference?.id"
                />
                <span>
                    <div class="flex items-center gap-2">
                        <div>
                            {{ shippingPreference?.title }}
                        </div>

                        <Tooltip :text="shippingPreference?.description">
                            <i
                                class="fa-solid fa-circle-info text-primary-500 cursor-pointer"
                            ></i>
                        </Tooltip>
                    </div>
                </span>
            </label>
        </div>
        <hr />
        <div class="my-5">
            <h1 class="text-xl">Packing Options</h1>
            <label
                class="flex items-center space-x-2 mt-2"
                v-for="packingOption in props?.packingOptions"
                :key="packingOption.id"
            >
                <Checkbox
                    v-model="form.packing_option"
                    :value="packingOption?.id"
                    v-if="!packingOption.is_text_input"
                />
                <span>
                    <div class="flex items-center gap-2">
                        <div class="w-full">
                            {{ packingOption?.title }}
                        </div>

                        <Tooltip :text="packingOption?.description">
                            <i
                                class="fa-solid fa-circle-info text-primary-500 cursor-pointer"
                            ></i>
                        </Tooltip>
                        <TextInput
                            v-if="packingOption.is_text_input"
                            v-model="form.maximum_weight_per_box"
                        />
                    </div>
                </span>
            </label>
        </div>
        <hr />
        <div class="my-5">
            <h1 class="text-xl">Proforma Invoice Options</h1>
            <label
                class="flex items-center space-x-2 mt-2"
                v-for="proformaInvoiceOption in props?.proformaInvoiceOptions"
                :key="proformaInvoiceOption.id"
            >
                <Checkbox
                    v-model="form.proforma_invoice_options"
                    :value="proformaInvoiceOption?.id"
                    v-if="!proformaInvoiceOption.is_text_input"
                />
                <span>
                    <div class="flex items-center gap-2">
                        <div class="w-full">
                            {{ proformaInvoiceOption?.title }}
                        </div>

                        <TextInput
                            v-if="proformaInvoiceOption.is_text_input"
                            v-model="form.tax_id"
                            step="2"
                        />
                    </div>
                </span>
            </label>
        </div>
        <hr />
        <div class="my-5">
            <h1 class="text-xl">Log in Options</h1>
            <label
                class="flex items-center space-x-2 mt-2"
                v-for="loginOption in props?.loginOptions"
                :key="loginOption.id"
            >
                <Checkbox
                    v-model="form.login_option"
                    :value="loginOption?.id"
                    v-if="!loginOption.is_text_input"
                />
                <span>
                    <div class="flex items-center gap-2">
                        <div class="w-full">
                            {{ loginOption?.title }}
                        </div>
                        <Tooltip :text="loginOption?.description">
                            <i
                                class="fa-solid fa-circle-info text-primary-500 cursor-pointer"
                            ></i>
                        </Tooltip>
                        <TextInput
                            v-if="loginOption.is_text_input"
                            v-model="form.additional_email"
                            step="2"
                        />
                    </div>
                </span>
            </label>
        </div>
        <hr />

        <div class="py-5">
            <p class="mb-2">
                * These options have a cost/fee, please click on the question
                mark next to it to review the fee.
            </p>

            <div class="flex items-center gap-2">
                <SecondaryButton @click="handleCancelChanges"
                    >Cancel changes</SecondaryButton
                >
                <PrimaryButton
                    @click="handleSubmitPreferences"
                    :disabled="form.processing"
                    >Save Changes</PrimaryButton
                >
            </div>
        </div>
    </Edit>
</template>
