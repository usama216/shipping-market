<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Edit from "../Edit.vue";
import { Head, useForm } from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import VueSelect from "vue-select";
import { invoiceStatus, shippingStatus } from "@js/Data/statuses";
import PrimaryButton from "@/Components/PrimaryButton.vue";
const props = defineProps({
    ship: Object,
});

const form = useForm({
    tracking_number: props?.ship?.tracking_number ?? "",
    total_weight: props?.ship?.total_weight ?? 0.0,
    total_price: props?.ship?.total_price ?? 0.0,
    status: props?.ship?.status ?? "",
    invoice_status: props?.ship?.invoice_status ?? "",
});
const updateShipment = () => {
    form.post(route("admin.shipments.update", { ship: props?.ship?.id }));
};
</script>
<template>
    <Head title="Edit shipment" />
    <AuthenticatedLayout>
        <Edit :ship="props?.ship">
            <div class="w-full shadow-sm card bg-base-100">
                <div class="card-body">
                    <form @submit.prevent="updateShipment">
                        <div
                            class="grid gap-2 lg:grid-cols-2 md:grid-cols-2 sm:grid-cols-1"
                        >
                            <div>
                                <InputLabel value="Tracking number" />
                                <TextInput v-model="form.tracking_number" />
                                <InputError
                                    :message="form.errors.tracking_number"
                                />
                            </div>
                            <div>
                                <InputLabel value="Total weight (lbs)" />
                                <TextInput v-model="form.total_weight" />
                                <InputError
                                    :message="form.errors.total_weight"
                                />
                            </div>
                            <div>
                                <InputLabel value="Total Price" />

                                <div
                                    class="flex items-center px-3 bg-gray-100 border border-gray-300 rounded-md h-11"
                                >
                                    <i
                                        class="mr-2 text-gray-700 fa-solid fa-dollar-sign"
                                    ></i>

                                    <TextInput
                                        v-model="form.total_price"
                                        type="number"
                                        step="any"
                                        class="flex-1 bg-transparent border-none outline-none"
                                    />
                                </div>

                                <InputError
                                    :message="form.errors.total_price"
                                />
                            </div>

                            <div>
                                <InputLabel value="Status" />
                                <VueSelect
                                    v-model="form.status"
                                    :options="shippingStatus"
                                    :reduce="(option) => option.id"
                                    label="name"
                                />
                                <InputError :message="form.errors.status" />
                            </div>
                            <div>
                                <InputLabel value="Invoice status" />
                                <VueSelect
                                    v-model="form.invoice_status"
                                    :options="invoiceStatus"
                                    :reduce="(option) => option.id"
                                    label="name"
                                />
                                <InputError
                                    :message="form.errors.invoice_status"
                                />
                            </div>
                        </div>
                        <div class="mt-2 text-end">
                            <PrimaryButton :processing="form.processing"
                                >Update</PrimaryButton
                            >
                        </div>
                    </form>
                </div>
            </div>
        </Edit>
    </AuthenticatedLayout>
</template>
