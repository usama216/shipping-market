<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    roles: Array,
    userTypes: Array,
});

const currentStep = ref(1);
const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    password: '',
    password_confirmation: '',
    type: 1, // Default to ADMIN
    role: '',
    is_active: true,
});

// -- Computed --

const steps = [
    { number: 1, title: 'Profile', description: 'Personal details' },
    { number: 2, title: 'Access', description: 'Role & Permissions' },
    { number: 3, title: 'Review', description: 'Confirm Details' },
];

const progressWidth = computed(() => {
    return `${((currentStep.value - 1) / (steps.length - 1)) * 100}%`;
});

// Validation computed properties
const isStep1Valid = computed(() => {
    return form.first_name.trim() !== '' && 
           form.last_name.trim() !== '' && 
           form.email.trim() !== '' && 
           form.password.length >= 8 &&
           form.password === form.password_confirmation;
});

const isStep2Valid = computed(() => {
    return form.role !== '' && form.type !== null;
});

// -- Methods --

const nextStep = () => {
    if (currentStep.value === 1 && !isStep1Valid.value) return;
    if (currentStep.value === 2 && !isStep2Valid.value) return;
    currentStep.value++;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const submit = () => {
    form.post(route('admin.system-users.store'), {
        onSuccess: () => {
            // Handled by redirect in controller
        }
    });
};

const getRoleName = (roleName) => {
    return roleName ? roleName.charAt(0).toUpperCase() + roleName.slice(1) : 'None';
};

const getUserTypeLabel = (value) => {
    const type = props.userTypes.find(t => t.value === value);
    return type ? type.label : 'Unknown';
};
</script>

<template>
    <Head title="Create User" />
    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto">
            <!-- Back Link -->
            <Link :href="route('admin.user-management.index')" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-900 mb-6 transition-colors">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Users
            </Link>

            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <!-- Wizard Header -->
                <div class="bg-gray-50 border-b border-gray-100 p-8">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Create System User</h1>
                            <p class="text-gray-500 mt-1">Add a new administrator or staff member.</p>
                        </div>
                        <div class="text-sm font-medium text-gray-400">
                            Step {{ currentStep }} of 3
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="relative">
                        <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200">
                            <div :style="{ width: progressWidth }" class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-indigo-600 transition-all duration-500 ease-out"></div>
                        </div>
                        <div class="flex justify-between w-full text-xs font-medium text-gray-500 uppercase tracking-widest px-1">
                            <div v-for="step in steps" :key="step.number" :class="{'text-indigo-600 font-bold': currentStep >= step.number, 'text-gray-400': currentStep < step.number}">
                                {{ step.title }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Wizard Body -->
                <div class="p-8 min-h-[400px]">
                    
                    <!-- STEP 1: PERSONAL PROFILE -->
                    <div v-show="currentStep === 1" class="space-y-6 max-w-2xl mx-auto animation-fade-in">
                        <div class="text-center mb-8">
                            <h2 class="text-xl font-bold text-gray-900">Personal Information</h2>
                            <p class="text-gray-500">Enter the user's basic details and credentials.</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                                <input 
                                    v-model="form.first_name"
                                    type="text" 
                                    placeholder="Jane"
                                    class="w-full px-4 py-3 rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-all"
                                    :class="{'border-red-300 focus:ring-red-200': form.errors.first_name}"
                                >
                                <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-600">{{ form.errors.first_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                                <input 
                                    v-model="form.last_name"
                                    type="text" 
                                    placeholder="Doe"
                                    class="w-full px-4 py-3 rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-all"
                                    :class="{'border-red-300 focus:ring-red-200': form.errors.last_name}"
                                >
                                <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-600">{{ form.errors.last_name }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                            <input 
                                v-model="form.email"
                                type="email" 
                                placeholder="jane.doe@company.com"
                                class="w-full px-4 py-3 rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-all"
                                :class="{'border-red-300 focus:ring-red-200': form.errors.email}"
                            >
                            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                <input 
                                    v-model="form.password"
                                    type="password" 
                                    placeholder="Min. 8 characters"
                                    class="w-full px-4 py-3 rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-all"
                                    :class="{'border-red-300 focus:ring-red-200': form.errors.password}"
                                >
                                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                                <input 
                                    v-model="form.password_confirmation"
                                    type="password" 
                                    placeholder="Repeat password"
                                    class="w-full px-4 py-3 rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-all"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: ACCESS & ROLE -->
                    <div v-show="currentStep === 2" class="space-y-6 max-w-2xl mx-auto animation-fade-in">
                        <div class="text-center mb-8">
                            <h2 class="text-xl font-bold text-gray-900">Access Control</h2>
                            <p class="text-gray-500">Assign a role and define user status.</p>
                        </div>

                        <div class="space-y-6">
                            <!-- User Type -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">User Classification</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div 
                                        v-for="type in userTypes" 
                                        :key="type.value"
                                        @click="form.type = type.value"
                                        class="cursor-pointer border rounded-xl p-4 flex items-center gap-3 transition-all hover:border-gray-300"
                                        :class="form.type === type.value ? 'ring-2 ring-indigo-500 border-indigo-500 bg-indigo-50' : 'border-gray-200 bg-white'"
                                    >
                                        <div 
                                            class="w-5 h-5 rounded-full border flex items-center justify-center flex-shrink-0"
                                            :class="form.type === type.value ? 'border-indigo-600' : 'border-gray-300'"
                                        >
                                            <div v-if="form.type === type.value" class="w-2.5 h-2.5 rounded-full bg-indigo-600"></div>
                                        </div>
                                        <span class="font-medium text-gray-900">{{ type.label }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Role -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assigned Role</label>
                                <select 
                                    v-model="form.role"
                                    class="w-full px-4 py-3 rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-all cursor-pointer"
                                    :class="{'border-red-300 focus:ring-red-200': form.errors.role}"
                                >
                                    <option value="" disabled>Select a defined role...</option>
                                    <option v-for="role in roles" :key="role.id" :value="role.name">
                                        {{ role.name }}
                                    </option>
                                </select>
                                <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
                                <p class="text-xs text-gray-500 mt-2">
                                    <i class="fa-solid fa-info-circle mr-1"></i>
                                    Roles define which specific modules and actions this user can access.
                                </p>
                            </div>

                            <!-- Active Status -->
                            <div class="bg-gray-50 rounded-xl p-4 flex items-center justify-between border border-gray-100">
                                <div>
                                    <h4 class="font-bold text-gray-900">Account Status</h4>
                                    <p class="text-xs text-gray-500">Inactive users cannot log in.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="form.is_active" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                    <span class="ml-3 text-sm font-medium text-gray-700">{{ form.is_active ? 'Active' : 'Inactive' }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: REVIEW -->
                    <div v-show="currentStep === 3" class="max-w-2xl mx-auto animation-fade-in">
                        <div class="text-center mb-8">
                            <h2 class="text-xl font-bold text-gray-900">Review & Confirm</h2>
                            <p class="text-gray-500">Please verify the information before creating the user.</p>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden mb-6">
                            <!-- Identity Section -->
                            <div class="p-6 border-b border-gray-100">
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                                        <i class="fa-regular fa-id-card text-gray-400"></i> Identity
                                    </h3>
                                    <button @click="currentStep = 1" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 uppercase tracking-wide">Edit</button>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Full Name</p>
                                        <p class="font-medium text-gray-900">{{ form.first_name }} {{ form.last_name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Email</p>
                                        <p class="font-medium text-gray-900 break-words">{{ form.email }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Access Section -->
                            <div class="p-6 bg-gray-50/50">
                                <div class="flex justify-between items-start mb-4">
                                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                                        <i class="fa-solid fa-shield-halved text-gray-400"></i> Access
                                    </h3>
                                    <button @click="currentStep = 2" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 uppercase tracking-wide">Edit</button>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">User Classification</span>
                                        <span class="px-3 py-1 bg-white border border-gray-200 rounded-lg text-sm font-medium text-gray-900">{{ getUserTypeLabel(form.type) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Assigned Role</span>
                                        <span class="px-3 py-1 bg-indigo-50 border border-indigo-100 text-indigo-700 rounded-lg text-sm font-bold">{{ getRoleName(form.role) }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Account Status</span>
                                        <span :class="['px-2 py-1 rounded text-xs font-bold uppercase', form.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700']">
                                            {{ form.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer / Controls -->
                <div class="bg-gray-50 px-8 py-5 border-t border-gray-100 flex justify-between items-center">
                    <button 
                        @click="prevStep" 
                        class="px-6 py-2.5 rounded-xl font-medium text-gray-600 hover:bg-gray-200 transition-colors"
                        :class="{'invisible': currentStep === 1}"
                    >
                        Back
                    </button>

                    <div class="flex items-center gap-4">
                        <Link :href="route('admin.user-management.index')" class="text-sm text-gray-500 hover:text-gray-700 font-medium">Cancel</Link>
                        
                        <button 
                            v-if="currentStep < 3" 
                            @click="nextStep"
                            :disabled="(currentStep === 1 && !isStep1Valid) || (currentStep === 2 && !isStep2Valid)"
                            class="px-8 py-2.5 bg-gray-900 hover:bg-black text-white rounded-xl font-medium shadow-lg shadow-gray-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Next Step
                        </button>

                         <button 
                            v-if="currentStep === 3" 
                            @click="submit"
                            :disabled="form.processing"
                            class="px-8 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-bold shadow-lg shadow-indigo-200 transition-all flex items-center gap-2"
                        >
                            <i v-if="form.processing" class="fa-solid fa-circle-notch fa-spin"></i>
                            {{ form.processing ? 'Creating...' : 'Create User' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animation-fade-in {
    animation: fadeIn 0.4s ease-out forwards;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
