<script setup>
import InputError from "@/Components/InputError.vue";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import AddressFormFields from "@/Components/AddressFormFields.vue";
import { useForm } from "@inertiajs/vue3";
import { onMounted, ref, watch } from "vue";

const props = defineProps({
    customerAddresses: Array,
});
const emit = defineEmits(["setSelectedAddress"]);

const showModal = ref(false);
const selectedAddress = ref(null);
const dropdownOpen = ref(false);
watch(selectedAddress, (val) => {
    emit("setSelectedAddress", val);
});

const form = useForm({
    id: null,
    address_name: "",
    full_name: "",
    address_line_1: "",
    address_line_2: "",
    country_id: "",
    country: "",
    state_id: "",
    state: "",
    city_id: "",
    city: "",
    postal_code: "",
    country_code: "",
    phone_number: "",
});

const resetForm = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
};

const openModal = (address = null) => {
    resetForm();

    if (address) {
        Object.assign(form, {
            id: address.id,
            address_name: address.address_name || "",
            full_name: address.full_name || "",
            address_line_1: address.address_line_1 || "",
            address_line_2: address.address_line_2 || "",
            country_id: address.country_id || "",
            country: address.country || "",
            state_id: address.state_id || "",
            state: address.state || "",
            city_id: address.city_id || "",
            city: address.city || "",
            postal_code: address.postal_code || "",
            country_code: address.country_code || "",
            phone_number: address.phone_number || "",
        });
    }

    showModal.value = true;
};

const saveAddress = () => {
    const isEdit = form.id !== null;
    const routeName = isEdit
        ? route("customer.addresses.update", { address: form.id })
        : route("customer.addresses.store");

    const method = isEdit ? form.put.bind(form) : form.post.bind(form);
    method(routeName, {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            resetForm();
        },
    });
};

const getAddressLabel = (address) => {
    return `${address.full_name}, ${address.address_line_1}, ${
        address.city || ""
    }, ${address.country}`;
};

watch(
    () => props.customerAddresses,
    (list) => {
        if (!selectedAddress.value) {
            const defaultUS = list.find((a) => a.is_default_us);
            if (defaultUS) selectedAddress.value = defaultUS.id;
        }
    },
    { immediate: true }
);

onMounted(() => {
    const defaultUS = props.customerAddresses.find((a) => a.is_default_us);

    if (defaultUS) {
        selectedAddress.value = defaultUS.id;
    }
});
</script>

<template>
    <div class="flex items-center justify-between mt-2 mb-4">
        <div>
            <p>Shipping Address</p>
            <p>Choose your address for shipping</p>
        </div>
        <PrimaryButton @click="openModal()">+ Add New Address</PrimaryButton>
    </div>

    <!-- Dropdown -->
    <div class="relative w-full">
        <div
            class="flex items-center justify-between p-4 bg-white border rounded-lg shadow-sm cursor-pointer"
            @click="dropdownOpen = !dropdownOpen"
        >
            <span>
                {{
                    selectedAddress
                        ? getAddressLabel(
                              props.customerAddresses.find(
                                  (a) => a.id === selectedAddress
                              )
                          )
                        : "Select a shipping address"
                }}
            </span>
            <svg
                class="w-4 h-4 text-gray-500"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </div>

        <!-- Address List -->
        <ul
            v-if="dropdownOpen"
            class="absolute z-50 w-full mt-1 overflow-y-auto bg-white border rounded-lg shadow-lg max-h-60"
        >
            <li
                v-for="address in props.customerAddresses"
                :key="address.id"
                @click="
                    selectedAddress = address.id;
                    dropdownOpen = false;
                "
                class="p-3 cursor-pointer hover:bg-gray-100"
            >
                <p class="font-medium">{{ address.full_name }}</p>
                <p class="text-sm text-gray-500">
                    {{ address.address_line_1 }}
                </p>
                <p class="text-xs text-gray-400">
                    {{ address.country }}
                    {{ address.state ? `, ${address.state}` : "" }}
                    {{ address.city ? `, ${address.city}` : "" }}
                </p>
            </li>
        </ul>
    </div>

    <!-- Add/Edit Modal -->
    <Modal :show="showModal" @close="showModal = false">
        <div class="p-6">
            <h2 class="mb-4 text-xl font-semibold text-black">
                {{ form.id ? "Edit Address" : "Add New Address" }}
            </h2>
            <form @submit.prevent="saveAddress">
                <AddressFormFields :form="form" :errors="form.errors" />

                <div class="flex justify-end gap-2 mt-4">
                    <SecondaryButton type="button" @click="showModal = false">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton type="submit" :disabled="form.processing">
                        {{
                            form.processing
                                ? "Saving..."
                                : form.id
                                ? "Update"
                                : "Save"
                        }}
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
