<script setup>
import { Head } from "@inertiajs/vue3";
import Edit from "../Edit.vue";
import UpdateProfileInformationForm from "./Partials/UpdateProfileInformationForm.vue";
import UpdatePasswordForm from "./Partials/UpdatePasswordForm.vue";
import DeleteUserForm from "./Partials/DeleteUserForm.vue";
import UpdatePhoneNumber from "./Partials/UpdatePhoneNumber.vue";

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>
<template>
    <Head title="Contact information" />
    <Edit>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Profile
            </h2>
        </template>
        <div class="py-12 bg-white">
            <div class="mx-auto space-y-6 max-w-7xl sm:px-6 lg:px-8">
                <div class="p-4 bg-white shadow sm:rounded-lg sm:p-8">
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>
                <div class="p-4 bg-white shadow sm:rounded-lg sm:p-8">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <!-- <div class="p-4 bg-white shadow sm:rounded-lg sm:p-8">
                    <DeleteUserForm class="max-w-xl" />
                </div> -->
            </div>
        </div>
    </Edit>
</template>
