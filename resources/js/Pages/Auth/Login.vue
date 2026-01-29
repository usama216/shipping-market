<script setup>
import Checkbox from "@/Components/Checkbox.vue";
import AuthLayout from "@/Layouts/AuthLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";
import { ref } from "vue";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const showPassword = ref(false);

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <AuthLayout heading="Welcome Back" subheading="Log in to your account to manage your packages.">
        <Head title="Login" />

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600 bg-green-50 p-3 rounded-md border border-green-200">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="mt-8 space-y-6">
            <div>
                <InputLabel for="email" value="Email Address" />
                <TextInput
                    id="email"
                    type="email"
                    class="block w-full mt-1"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                    placeholder="you@example.com"
                />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <InputLabel for="password" value="Password" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm font-medium text-primary-600 hover:text-primary-500"
                    >
                        Forgot password?
                    </Link>
                </div>
                <div class="relative">
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="block w-full mt-1 pr-10"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        @click="togglePasswordVisibility"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none"
                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                    >
                        <i v-if="showPassword" class="fa-solid fa-eye-slash"></i>
                        <i v-else class="fa-solid fa-eye"></i>
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="block">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
            </div>

            <div class="pt-2">
                <PrimaryButton class="w-full justify-center py-3" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                    Sign In
                </PrimaryButton>
            </div>

            <div class="text-center text-sm text-gray-600 mt-4">
                Don't have an account? 
                <Link :href="route('register')" class="font-medium text-primary-600 hover:text-primary-500">
                    Register for free
                </Link>
            </div>
        </form>
    </AuthLayout>
</template>
