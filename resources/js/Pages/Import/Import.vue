<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import SpinnerButton from "@/Components/SpinnerButton.vue";
import TextInput from "@/Components/TextInput.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, useForm } from "@inertiajs/vue3";

const form = new useForm({
    fileType: "",
    file: null,
});
const handleFileChange = (event) => {
    form.file = event.target.files[0];
};

const handleSubmit = () => {
    form.post(route("admin.importUser"), {
        forceFormData: true,
        onFinish: () => form.reset(""),
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Import files" />
        <div class="container">
            <h1 class="text-[2.5rem]">Imports</h1>
            <div class="card bg-white shadow-md">
                <div class="card-body">
                    <form
                        @submit.prevent="handleSubmit"
                        enctype="multipart/form-data"
                    >
                        <div class="grid grid-cols-2 gap-2 w-full">
                            <div class="w-full mb-2">
                                <InputLabel value="Select Type" class="mb-2" />
                                <select
                                    name=""
                                    class="w-full rounded"
                                    v-model="form.fileType"
                                >
                                    <option value="">Select file type</option>
                                    <option value="user">Users</option>
                                </select>
                            </div>
                            <div class="w-full mb-2">
                                <InputLabel value="Chose file" class="mb-2" />
                                <TextInput
                                    type="file"
                                    class="file-input bg-white w-full"
                                    @change="handleFileChange"
                                />
                            </div>
                        </div>
                        <div class="text-end">
                            <SpinnerButton :isLoading="form.processing"
                                >Submit</SpinnerButton
                            >
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
