<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Edit from "../Edit.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage, router } from "@inertiajs/vue3";
import VueDatePicker from "@vuepic/vue-datepicker";
import SearchableSelect from "vue-select";
import Status from "@js/Data/status.json";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import { ref } from "vue";

const props = defineProps({
    user: Object,
});

const authUser = usePage().props.auth.user;
const isSuperAdmin = usePage().props.auth.isSuperAdmin;

const form = useForm({
    first_name: props?.user?.first_name ?? "",
    last_name: props?.user?.last_name ?? "",
    email: props?.user?.email ?? "",
    phone: props?.user?.phone ?? "",
    country: props?.user?.country ?? "",
    state: props?.user?.state ?? "",
    tax_id: props?.user?.tax_id ?? "",
    address: props?.user?.address ?? "",
    is_active: props?.user?.is_active ?? "",
    date_of_birth:
        new Date(props?.user?.date_of_birth).toLocaleString("en-US") ?? "",
    zip_code: props?.user?.zip_code ?? "",
    suite: props?.user?.suite ?? "",
    // city: props?.user?.city ?? "",
    password: "",
});

const showDeleteModal = ref(false);

const handleUpdate = () => {
    form.put(route("admin.customers.update", { customer: props?.user?.id }), {
        onSuccess: () => {
            // Success handled by backend redirect
        },
        onError: (errors) => {
            console.error("Validation errors:", errors);
        },
    });
};

const confirmDelete = () => {
    showDeleteModal.value = true;
};

const deleteCustomer = () => {
    router.delete(route('admin.customers.destroy', { customer: props?.user?.id }), {
        preserveScroll: false,
        onSuccess: () => {
            // Redirect handled by backend
        },
        onError: (errors) => {
            console.error("Delete errors:", errors);
            showDeleteModal.value = false;
        },
    });
};

const cancelDelete = () => {
    showDeleteModal.value = false;
};
</script>
<template>
    <AuthenticatedLayout>
        <Head title="Edit user" />
        <Edit :user="props?.user">
            <div class="card bg-base-100 shadow-sm w-full">
                <div class="card-body">
                    <form @submit.prevent="handleUpdate">
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
                                <InputLabel value="Password (leave blank to keep current)" />
                                <TextInput 
                                    v-model="form.password" 
                                    type="password"
                                    placeholder="Leave blank to keep current password"
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
                                <InputError
                                    :message="form.errors.date_of_birth"
                                />
                            </div>
                        </div>
                        <div class="flex justify-between items-center mt-4">
                            <button
                                @click="confirmDelete"
                                type="button"
                                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                            >
                                <i class="fa fa-trash mr-2"></i>
                                Delete Customer
                            </button>
                            <PrimaryButton :processing="form.processing"
                                >Update Customer</PrimaryButton
                            >
                        </div>
                    </form>
                </div>
            </div>
        </Edit>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="cancelDelete"
        >
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-lg font-semibold mb-4">Delete Customer</h3>
                <p class="text-gray-600 mb-6">
                    Are you sure you want to delete 
                    <strong>{{ props?.user?.first_name }} {{ props?.user?.last_name }} ({{ props?.user?.email }})</strong>?
                    <br><br>
                    <span class="text-red-600 text-sm">
                        Note: This action cannot be undone. Customers with existing shipments or packages cannot be deleted.
                    </span>
                </p>
                <div class="flex justify-end gap-3">
                    <button
                        @click="cancelDelete"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                    >
                        Cancel
                    </button>
                    <button
                        @click="deleteCustomer"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
