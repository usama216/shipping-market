<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    permissionGroups: {
        type: Array,
        default: () => []
    },
    rolePresets: {
        type: Array,
        default: () => []
    }
});

const currentStep = ref(1);
const form = useForm({
    name: '',
    description: '',
    permissions: [],
});

const selectedPreset = ref(null);
const showAdvanced = ref(false);

// Access Level Mapping
const accessMap = ref({}); // { module_handle: 'none' | 'view' | 'manage' | 'custom' }

// Initialize Map
props.permissionGroups.forEach(group => {
    accessMap.value[group.handle] = 'none';
    if(group.subModules) {
        group.subModules.forEach(sub => {
            accessMap.value[sub.handle] = 'none';
        });
    }
});


// -- Computed --

const steps = [
    { number: 1, title: 'Identity', description: 'Name and describe the role' },
    { number: 2, title: 'Permissions', description: 'Define access levels' },
    { number: 3, title: 'Review', description: 'Confirm and update' },
];

const progressWidth = computed(() => {
    return `${((currentStep.value - 1) / (steps.length - 1)) * 100}%`;
});

const isStep1Valid = computed(() => form.name.trim().length > 0);
const isStep2Valid = computed(() => form.permissions.length > 0);


// -- Methods --

const nextStep = () => {
    if (currentStep.value === 1 && !isStep1Valid.value) return;
    if (currentStep.value === 2 && !isStep2Valid.value) return;
    currentStep.value++;
};

const prevStep = () => {
    if (currentStep.value > 1) currentStep.value--;
};

const selectPreset = (preset) => {
    selectedPreset.value = preset;
    form.name = preset.name;
    form.description = preset.description;
    
    // Reset permissions first
    form.permissions = [];
    
    // 1. Reset Map
    Object.keys(accessMap.value).forEach(key => accessMap.value[key] = 'none');
    
    // 2. Apply Preset Levels
    Object.entries(preset.access_level).forEach(([handle, level]) => {
        if (accessMap.value[handle] !== undefined) {
             accessMap.value[handle] = level;
        }
    });
    
    // 3. Update Permissions
    updatePermissionsFromMap();
};

const setAccessLevel = (groupHandle, level) => {
    accessMap.value[groupHandle] = level;
    updatePermissionsFromMap();
};

const updatePermissionsFromMap = () => {
    let constructedPermissions = [];
    
    props.permissionGroups.forEach(group => {
        const level = accessMap.value[group.handle];
        
        if (level === 'custom') {
            // Retain existing permissions for this group if we were editing, but here we are creating. 
            // In creation, if it becomes custom, it means manual toggle.
            // But if we switch FROM custom to something else, we overwrite.
            // Ideally, simple mode just overwrites.
            // If we are in 'custom' state in the map, it implies we shouldn't touch the form.permissions for this group
            // UNLESS this function is called by setAccessLevel, which sets it to something specific.
        } else if (level === 'manage') {
             constructedPermissions.push(...group.permissions.map(p => p.name));
             if(group.subModules) constructedPermissions.push(...group.subModules.flatMap(s => s.permissions.map(p => p.name)));
        } else if (level === 'view') {
             // Heuristic: filter permissions with 'view', 'index', 'show'
             constructedPermissions.push(...group.permissions.filter(p => p.action.includes('view') || p.action.includes('index') || p.action.includes('show') || p.action.includes('list')).map(p => p.name));
             if(group.subModules) {
                 constructedPermissions.push(...group.subModules.flatMap(s => s.permissions.filter(p => p.action.includes('view') || p.action.includes('index') || p.action.includes('show')).map(p => p.name)));
             }
        }
    });
    
    // We need to merge with any "custom" selections if we want to support hybrid. 
    // But for simplicity, the simplified controls dictate the state for their modules.
    // If a user toggles a single permission, that module becomes "custom".
    // When "custom", updatePermissionsFromMap shouldn't remove those permissions if called by another module's change.
    // So we should rebuild permissions ONLY for non-custom modules? 
    // Actually, easier: form.permissions is the source of truth.
    // When simplified controls are used, they update form.permissions.
    // When checkboxes are used, they update form.permissions.
    
    // To implement "update form permissions based on map":
    // We should clear permissions for the target module and add back the ones for the level.
    // But since this function iterates all, we need to be careful.
    
    // Better approach matching Create.vue logic:
    // Only rebuild for the specific module changed? No, `updatePermissionsFromMap` rebuilds all based on map.
    // This implies `custom` state needs to know what permissions are selected.
    // Actually, if map says "custom", we should probably NOT touch permissions for that group in this loop,
    // assuming they are correctly in `form.permissions`.
    // BUT we need `form.permissions` to strictly reflect the map for 'view'/'manage'/'none'.
    
    const newPermissions = [];
    
    // 1. Keep permissions from "custom" groups
    props.permissionGroups.forEach(group => {
        if (accessMap.value[group.handle] === 'custom') {
            // Find permissions belonging to this group that are currently in form.permissions
             const groupPermNames = [
                 ...group.permissions.map(p => p.name),
                 ...(group.subModules ? group.subModules.flatMap(s => s.permissions.map(p => p.name)) : [])
             ];
             const existing = form.permissions.filter(p => groupPermNames.includes(p));
             newPermissions.push(...existing);
        }
    });
    
    // 2. Add permissions for "view"/"manage" groups
    props.permissionGroups.forEach(group => {
        const level = accessMap.value[group.handle];
        if (level === 'manage') {
             newPermissions.push(...group.permissions.map(p => p.name));
             if(group.subModules) newPermissions.push(...group.subModules.flatMap(s => s.permissions.map(p => p.name)));
        } else if (level === 'view') {
             newPermissions.push(...group.permissions.filter(p => p.action.includes('view') || p.action.includes('index') || p.action.includes('show') || p.action.includes('list')).map(p => p.name));
             if(group.subModules) {
                 newPermissions.push(...group.subModules.flatMap(s => s.permissions.filter(p => p.action.includes('view') || p.action.includes('index') || p.action.includes('show')).map(p => p.name)));
             }
        }
    });
    
    form.permissions = [...new Set(newPermissions)];
};


// Advanced: Toggle individual permission
const togglePermission = (permName) => {
    if (form.permissions.includes(permName)) {
        form.permissions = form.permissions.filter(p => p !== permName);
    } else {
        form.permissions.push(permName);
    }

    // Set module to custom
    const group = props.permissionGroups.find(g => 
        g.permissions.some(p => p.name === permName) || 
        (g.subModules && g.subModules.some(s => s.permissions.some(sp => sp.name === permName)))
    );
    
    if (group) {
        accessMap.value[group.handle] = 'custom';
    }
};

const submit = () => {
    form.post(route('admin.roles.store'), {
        onSuccess: () => {
            // Toast handled by layout/flash
        }
    });
};
</script>

<template>
    <Head title="Create New Role" />
    <AuthenticatedLayout>
        <div class="max-w-5xl mx-auto">
            <!-- Back Link -->
            <Link :href="route('admin.user-management.index', { tab: 'roles' })" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-900 mb-6 transition-colors">
                <i class="fa-solid fa-arrow-left mr-2"></i> Back to Roles
            </Link>

            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                <!-- Wizard Header -->
                <div class="bg-gray-50 border-b border-gray-100 p-8">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Create New Role</h1>
                            <p class="text-gray-500 mt-1">Define access rights and permissions for your team.</p>
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
                <div class="p-8">
                    
                    <!-- STEP 1: IDENTITY -->
                    <div v-show="currentStep === 1" class="space-y-6 max-w-3xl mx-auto animation-fade-in">
                        <div class="text-center mb-10">
                            <h2 class="text-xl font-bold text-gray-900">Let's start with the basics</h2>
                            <p class="text-gray-500">Give this role a distinct name and description.</p>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Role Name</label>
                                <input 
                                    v-model="form.name"
                                    type="text" 
                                    placeholder="e.g. Warehouse Manager" 
                                    class="w-full px-4 py-3 rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-all"
                                    :class="{'border-red-300 focus:ring-red-200': form.errors.name}"
                                >
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description (Optional)</label>
                                <textarea 
                                    v-model="form.description"
                                    placeholder="What is this role responsible for?"
                                    rows="3" 
                                    class="w-full px-4 py-3 rounded-xl border-gray-200 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm transition-all"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: PERMISSIONS -->
                    <div v-show="currentStep === 2" class="space-y-8 animation-fade-in">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                            
                            <!-- Sidebar: Presets -->
                            <div class="lg:col-span-4 space-y-4">
                                <div>
                                    <h3 class="font-bold text-gray-900">Start with a Preset</h3>
                                    <p class="text-sm text-gray-500">Pick a template to quick-start.</p>
                                </div>
                                
                                <div class="space-y-3">
                                    <div 
                                        v-for="preset in rolePresets" 
                                        :key="preset.name"
                                        @click="selectPreset(preset)"
                                        class="cursor-pointer border rounded-xl p-4 transition-all hover:shadow-md"
                                        :class="selectedPreset?.name === preset.name ? 'border-indigo-500 bg-indigo-50 ring-1 ring-indigo-500' : 'border-gray-200 bg-white hover:border-gray-300'"
                                    >
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="font-semibold text-gray-900">{{ preset.name }}</span>
                                            <i v-if="selectedPreset?.name === preset.name" class="fa-solid fa-check-circle text-indigo-600"></i>
                                        </div>
                                        <p class="text-xs text-gray-500">{{ preset.description }}</p>
                                    </div>
                                    
                                     <div 
                                        v-if="selectedPreset" 
                                        class="text-xs text-gray-400 italic text-center cursor-pointer hover:text-indigo-600" 
                                        @click="selectedPreset = null"
                                    >
                                        Clear Selection
                                    </div>
                                </div>
                            </div>

                            <!-- Main: Access Levels -->
                            <div class="lg:col-span-8 space-y-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="font-bold text-gray-900">Access Control</h3>
                                    <button @click="showAdvanced = !showAdvanced" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                        {{ showAdvanced ? 'Switch to Simple Mode' : 'Switch to Advanced Mode' }}
                                    </button>
                                </div>

                                <!-- Simple Mode -->
                                <div v-if="!showAdvanced" class="bg-gray-50 rounded-2xl p-6 space-y-6">
                                    <div v-for="group in permissionGroups" :key="group.handle" class="bg-white border border-gray-100 rounded-xl p-5 shadow-sm">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                            <div>
                                                <h4 class="font-bold text-gray-900 text-lg flex items-center gap-2">
                                                    {{ group.name }}
                                                    <span v-if="accessMap[group.handle] === 'custom'" class="bg-amber-100 text-amber-700 text-[10px] px-2 py-0.5 rounded-full uppercase tracking-wide">Custom</span>
                                                </h4>
                                                <p class="text-sm text-gray-500">Manage access to {{ group.name.toLowerCase() }} module.</p>
                                            </div>
                                            
                                            <!-- Toggle Pill -->
                                            <div class="bg-gray-100 p-1 rounded-lg inline-flex text-xs font-semibold">
                                                <button 
                                                    @click="setAccessLevel(group.handle, 'none')"
                                                    :class="['px-4 py-2 rounded-md transition-all', accessMap[group.handle] === 'none' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700']"
                                                >
                                                    No Access
                                                </button>
                                                <button 
                                                    @click="setAccessLevel(group.handle, 'view')"
                                                    :class="['px-4 py-2 rounded-md transition-all', accessMap[group.handle] === 'view' ? 'bg-white text-blue-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']"
                                                >
                                                    View Only
                                                </button>
                                                <button 
                                                    @click="setAccessLevel(group.handle, 'manage')"
                                                    :class="['px-4 py-2 rounded-md transition-all', accessMap[group.handle] === 'manage' ? 'bg-white text-emerald-600 shadow-sm' : 'text-gray-500 hover:text-gray-700']"
                                                >
                                                    Full Control
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Advanced Mode -->
                                <div v-else class="space-y-6">
                                    <div v-for="group in permissionGroups" :key="group.handle + '_adv'" class="bg-white border border-gray-200 rounded-xl p-6">
                                        <h4 class="font-bold text-gray-900 mb-4 pb-2 border-b">{{ group.name }}</h4>
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <label v-for="perm in group.permissions" :key="perm.name" class="flex items-center gap-3 p-3 rounded-lg hover:bg-gray-50 cursor-pointer border border-transparent hover:border-gray-200 transition-all">
                                                <div class="relative flex items-center">
                                                    <input 
                                                        type="checkbox" 
                                                        :checked="form.permissions.includes(perm.name)"
                                                        @change="togglePermission(perm.name)"
                                                        class="w-5 h-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                                    >
                                                </div>
                                                <span class="text-sm font-medium text-gray-700">{{ perm.label }}</span>
                                                <span class="text-xs text-gray-400 ml-auto font-mono">{{ perm.action }}</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: REVIEW -->
                    <div v-show="currentStep === 3" class="max-w-3xl mx-auto animation-fade-in">
                        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden mb-8">
                            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="font-bold text-gray-900">Role Summary</h3>
                                <button @click="currentStep = 1" class="text-sm text-indigo-600 hover:underline">Edit Identity</button>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase">Role Name</label>
                                    <p class="text-lg font-bold text-gray-900">{{ form.name }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-500 uppercase">Description</label>
                                    <p class="text-gray-700">{{ form.description || 'No description provided.' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white border border-gray-200 rounded-2xl overflow-hidden">
                            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="font-bold text-gray-900">Capabilities ({{ form.permissions.length }})</h3>
                                <button @click="currentStep = 2" class="text-sm text-indigo-600 hover:underline">Edit Permissions</button>
                            </div>
                            <div class="p-6">
                                <div v-if="form.permissions.length > 0" class="flex flex-wrap gap-2">
                                    <span v-for="perm in form.permissions" :key="perm" class="px-3 py-1 bg-indigo-50 text-indigo-700 border border-indigo-100 rounded-full text-sm font-medium">
                                        {{ perm }}
                                    </span>
                                </div>
                                <div v-else class="text-center py-6 text-yellow-600 bg-yellow-50 rounded-xl border border-yellow-100">
                                    <i class="fa-solid fa-triangle-exclamation mb-2"></i>
                                    <p class="font-medium">Warning: No permissions selected.</p>
                                    <p class="text-sm">This role will have no meaningful access.</p>
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
                            {{ form.processing ? 'Create Role' : 'Create Role' }}
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
