<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import VueDatePicker from "@vuepic/vue-datepicker";
import SearchableSelect from "vue-select";
import Status from "@js/Data/status.json";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";

const form = useForm({
    first_name: "",
    last_name: "",
    email: "",
    phone: "",
    country: "",
    state: "",
    tax_id: "",
    address: "",
    is_active: "",
    date_of_birth: "",
    zip_code: "",
    suite: "",
    password: "",
});

const handleSubmit = () => {
    form.post(route("admin.customers.store"), {
        onSuccess: () => {
            // Success handled by backend redirect
        },
        onError: (errors) => {
            console.error("Validation errors:", errors);
        },
    });
};
</script>
<template>
    <AuthenticatedLayout>
        <Head title="Create user" />
        <div class="card bg-base-100 shadow-sm w-full">
            <div class="card-body">
                <h1 class="text-2xl">Create user</h1>
                <form @submit.prevent="handleSubmit">
                    <div
                        class="grid lg:grid-cols-2 md:grid-cols-2 sm:grid-cols-1 gap-2"
                    >
                        <div>
                            <InputLabel value="First name" />
                            <TextInput v-model="form.first_name" />
                            <InputError :message="form.errors.first_name" />
                        </div>
                        <div>
                            <InputLabel value="Last name" />
                            <TextInput v-model="form.last_name" />
                            <InputError :message="form.errors.last_name" />
                        </div>
                        <div>
                            <InputLabel value="Email" />
                            <TextInput v-model="form.email" />
                            <InputError :message="form.errors.email" />
                        </div>
                        <div>
                            <InputLabel value="Phone" />
                            <TextInput v-model="form.phone" />
                            <InputError :message="form.errors.phone" />
                        </div>
                        <div>
                            <InputLabel value="Password" />
                            <TextInput 
                                v-model="form.password" 
                                type="password"
                                required
                            />
                            <InputError :message="form.errors.password" />
                        </div>
                        <div>
                            <InputLabel value="Country" />
                            <TextInput v-model="form.country" />
                            <InputError :message="form.errors.country" />
                        </div>
                        <div>
                            <InputLabel value="State" />
                            <TextInput v-model="form.state" />
                            <InputError :message="form.errors.state" />
                        </div>
                        <!-- <div>
                                <InputLabel value="City" />
                                <TextInput v-model="form.city" />
                                <InputError :message="form.errors.city" />
                            </div> -->
                        <div>
                            <InputLabel value="suite" />
                            <TextInput v-model="form.suite" />
                            <InputError :message="form.errors.suite" />
                        </div>
                        <div>
                            <InputLabel value="Zip code" />
                            <TextInput v-model="form.zip_code" />
                            <InputError :message="form.errors.zip_code" />
                        </div>
                        <div>
                            <InputLabel value="Address" />
                            <TextInput v-model="form.address" />
                            <InputError :message="form.errors.address" />
                        </div>
                        <div>
                            <InputLabel value="Tax id" />
                            <TextInput v-model="form.tax_id" />
                            <InputError :message="form.errors.tax_id" />
                        </div>
                        <div>
                            <InputLabel value="Status" />
                            <SearchableSelect
                                v-model="form.is_active"
                                :options="Status"
                                :reduce="(option) => option.is_active"
                                label="name"
                            />
                            <InputError :message="form.errors.is_active" />
                        </div>
                        <div>
                            <InputLabel value="Date of birth" />
                            <VueDatePicker
                                v-model="form.date_of_birth"
                                :teleport="true"
                                :enable-time-picker="false"
                            />
                            <InputError :message="form.errors.date_of_birth" />
                        </div>
                    </div>
                    <div class="float-right mt-4">
                        <PrimaryButton :processing="form.processing"
                            >Submit</PrimaryButton
                        >
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
