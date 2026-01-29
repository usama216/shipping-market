<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    permission: {
        type: String,
        default: null
    }
});

// Format permission name for display
const formatPermission = (permission) => {
    if (!permission) return 'this resource';
    return permission
        .replace(/\./g, ' → ')
        .replace(/-/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
};
</script>

<template>
    <Head title="Access Denied" />
    <AuthenticatedLayout>
        <div class="min-h-[70vh] flex items-center justify-center">
            <div class="text-center max-w-lg mx-auto px-6">
                <!-- Icon -->
                <div class="mb-8">
                    <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-red-100 to-red-200 rounded-full">
                        <i class="fa-solid fa-lock text-4xl text-red-500"></i>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-3xl font-bold text-gray-900 mb-3">
                    Access Denied
                </h1>

                <!-- Description -->
                <p class="text-gray-600 mb-2">
                    You don't have permission to access this page.
                </p>
                
                <!-- Permission Info -->
                <div v-if="permission" class="mb-6">
                    <span class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm font-mono">
                        <i class="fa-solid fa-key text-gray-400 mr-2"></i>
                        {{ formatPermission(permission) }}
                    </span>
                </div>

                <!-- Help Text -->
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-8">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-circle-info text-amber-500 mt-0.5"></i>
                        <div class="text-left">
                            <p class="text-amber-800 text-sm font-medium">Need access?</p>
                            <p class="text-amber-700 text-sm mt-1">
                                Contact your system administrator to request the necessary permissions for your account.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <Link 
                        :href="route('dashboard')"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-primary-500 text-white rounded-xl font-medium hover:bg-primary-600 transition-colors shadow-lg shadow-primary-500/25"
                    >
                        <i class="fa-solid fa-home"></i>
                        Go to Dashboard
                    </Link>
                    <button 
                        @click="$inertia.visit(window.history.back())"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-white text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition-colors border border-gray-200"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                        Go Back
                    </button>
                </div>

                <!-- Error Code -->
                <p class="text-gray-400 text-sm mt-8">
                    Error Code: 403 Forbidden
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
