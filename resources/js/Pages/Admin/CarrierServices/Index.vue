<script setup>
/**
 * Carrier Services & Addons Management Page
 * 
 * Admin interface for managing carrier services and addon configurations.
 */
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, computed } from "vue";
import { Head, router, useForm } from "@inertiajs/vue3";
import { useToast } from "vue-toastification";
import Modal from "@/Components/Modal.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";

const props = defineProps({
    carrierServices: Array,
    carrierAddons: Array,
    groupedServices: Object,
    stats: Object,
});

const toast = useToast();

// Tab state
const activeTab = ref('services');

// Modal state
const showServiceModal = ref(false);
const showAddonModal = ref(false);
const editingService = ref(null);
const editingAddon = ref(null);

// Form for service
const serviceForm = useForm({
    carrier_code: '',
    service_code: '',
    display_name: '',
    description: '',
    is_international: true,
    is_domestic: false,
    transit_time_min: null,
    transit_time_max: null,
    fallback_base_rate: null,
    fallback_per_lb_rate: null,
    is_active: true,
});

// Form for addon
const addonForm = useForm({
    addon_code: '',
    display_name: '',
    description: '',
    carrier_code: 'all',
    price_type: 'fixed',
    price_value: null,
    fallback_price: null,
    use_fallback: true,
    currency: 'USD',
    is_active: true,
});

// Carrier options
const carriers = [
    { code: 'fedex', name: 'FedEx', color: 'bg-purple-500' },
    { code: 'dhl', name: 'DHL', color: 'bg-yellow-500' },
    { code: 'ups', name: 'UPS', color: 'bg-amber-700' },
    { code: 'sea', name: 'Sea Freight', color: 'bg-blue-500' },
    { code: 'air', name: 'Air Cargo', color: 'bg-sky-500' },
];

const getCarrierBadge = (code) => {
    const carrier = carriers.find(c => c.code === code);
    return carrier ? carrier.color : 'bg-gray-500';
};

const getCarrierName = (code) => {
    const carrier = carriers.find(c => c.code === code);
    return carrier ? carrier.name : code?.toUpperCase();
};

// Open service modal for create/edit
const openServiceModal = (service = null) => {
    editingService.value = service;
    if (service) {
        serviceForm.carrier_code = service.carrier_code;
        serviceForm.service_code = service.service_code;
        serviceForm.display_name = service.display_name;
        serviceForm.description = service.description || '';
        serviceForm.is_international = service.is_international;
        serviceForm.is_domestic = service.is_domestic;
        serviceForm.transit_time_min = service.transit_time_min;
        serviceForm.transit_time_max = service.transit_time_max;
        serviceForm.fallback_base_rate = service.fallback_base_rate;
        serviceForm.fallback_per_lb_rate = service.fallback_per_lb_rate;
        serviceForm.is_active = service.is_active;
    } else {
        serviceForm.reset();
    }
    showServiceModal.value = true;
};

// Open addon modal for create/edit
const openAddonModal = (addon = null) => {
    editingAddon.value = addon;
    if (addon) {
        addonForm.addon_code = addon.addon_code;
        addonForm.display_name = addon.display_name;
        addonForm.description = addon.description || '';
        addonForm.carrier_code = addon.carrier_code || 'all';
        addonForm.price_type = addon.price_type;
        addonForm.price_value = addon.price_value;
        addonForm.fallback_price = addon.fallback_price;
        addonForm.use_fallback = addon.use_fallback ?? true;
        addonForm.currency = addon.currency || 'USD';
        addonForm.is_active = addon.is_active;
    } else {
        addonForm.reset();
    }
    showAddonModal.value = true;
};

// Save service
const saveService = () => {
    const routeName = editingService.value 
        ? 'admin.carrier-services.updateService'
        : 'admin.carrier-services.storeService';
    const method = editingService.value ? 'patch' : 'post';
    const url = editingService.value 
        ? route(routeName, editingService.value.id)
        : route(routeName);

    serviceForm[method](url, {
        onSuccess: () => {
            showServiceModal.value = false;
            toast.success(editingService.value ? 'Service updated' : 'Service created');
        },
        onError: () => {
            toast.error('Failed to save service');
        }
    });
};

// Save addon
const saveAddon = () => {
    const routeName = editingAddon.value 
        ? 'admin.carrier-services.updateAddon'
        : 'admin.carrier-services.storeAddon';
    const method = editingAddon.value ? 'patch' : 'post';
    const url = editingAddon.value 
        ? route(routeName, editingAddon.value.id)
        : route(routeName);

    addonForm[method](url, {
        onSuccess: () => {
            showAddonModal.value = false;
            toast.success(editingAddon.value ? 'Addon updated' : 'Addon created');
        },
        onError: () => {
            toast.error('Failed to save addon');
        }
    });
};

// Toggle service status
const toggleService = (service) => {
    router.post(route('admin.carrier-services.toggleService', service.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Service ${service.is_active ? 'deactivated' : 'activated'}`);
        }
    });
};

// Toggle addon status
const toggleAddon = (addon) => {
    router.post(route('admin.carrier-services.toggleAddon', addon.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`Addon ${addon.is_active ? 'deactivated' : 'activated'}`);
        }
    });
};

// Delete service
const deleteService = (service) => {
    if (!confirm(`Delete "${service.display_name}"? This cannot be undone.`)) return;
    
    router.delete(route('admin.carrier-services.destroyService', service.id), {
        preserveScroll: true,
        onSuccess: () => toast.success('Service deleted'),
        onError: (errors) => toast.error(errors.message || 'Cannot delete service')
    });
};

// Delete addon
const deleteAddon = (addon) => {
    if (!confirm(`Delete "${addon.display_name}"? This cannot be undone.`)) return;
    
    router.delete(route('admin.carrier-services.destroyAddon', addon.id), {
        preserveScroll: true,
        onSuccess: () => toast.success('Addon deleted')
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Carrier Services & Addons" />

        <div class="py-6">
            <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="flex items-center gap-3 text-2xl font-bold text-gray-900">
                            <i class="fa-solid fa-truck-fast text-primary-500"></i>
                            Carrier Services & Addons
                        </h1>
                        <p class="mt-1 text-sm text-gray-500">
                            Manage carrier service options and addon configurations
                        </p>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="grid grid-cols-2 gap-4 mb-6 md:grid-cols-4">
                    <div class="p-4 bg-white border rounded-lg shadow-sm">
                        <p class="text-sm text-gray-500">Total Services</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats?.total_services || 0 }}</p>
                    </div>
                    <div class="p-4 bg-white border rounded-lg shadow-sm">
                        <p class="text-sm text-gray-500">Active Services</p>
                        <p class="text-2xl font-bold text-green-600">{{ stats?.active_services || 0 }}</p>
                    </div>
                    <div class="p-4 bg-white border rounded-lg shadow-sm">
                        <p class="text-sm text-gray-500">Total Addons</p>
                        <p class="text-2xl font-bold text-gray-900">{{ stats?.total_addons || 0 }}</p>
                    </div>
                    <div class="p-4 bg-white border rounded-lg shadow-sm">
                        <p class="text-sm text-gray-500">Active Addons</p>
                        <p class="text-2xl font-bold text-green-600">{{ stats?.active_addons || 0 }}</p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="mb-6 border-b border-gray-200">
                    <nav class="flex gap-4 -mb-px">
                        <button
                            @click="activeTab = 'services'"
                            :class="[
                                'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                                activeTab === 'services'
                                    ? 'border-primary-500 text-primary-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            <i class="mr-2 fa-solid fa-shipping-fast"></i>
                            Carrier Services
                        </button>
                        <button
                            @click="activeTab = 'addons'"
                            :class="[
                                'px-4 py-3 text-sm font-medium border-b-2 transition-colors',
                                activeTab === 'addons'
                                    ? 'border-primary-500 text-primary-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700'
                            ]"
                        >
                            <i class="mr-2 fa-solid fa-puzzle-piece"></i>
                            Addons
                        </button>
                    </nav>
                </div>

                <!-- Services Tab -->
                <div v-if="activeTab === 'services'" class="space-y-6">
                    <div class="flex justify-end">
                        <PrimaryButton @click="openServiceModal(null)">
                            <i class="mr-2 fa-solid fa-plus"></i>
                            Add Service
                        </PrimaryButton>
                    </div>

                    <!-- Services by Carrier -->
                    <div v-for="(services, carrierCode) in groupedServices" :key="carrierCode" 
                         class="overflow-hidden bg-white border rounded-lg shadow-sm">
                        <div class="flex items-center gap-3 px-4 py-3 bg-gray-50 border-b">
                            <span :class="['w-3 h-3 rounded-full', getCarrierBadge(carrierCode)]"></span>
                            <h3 class="font-semibold text-gray-900">{{ getCarrierName(carrierCode) }}</h3>
                            <span class="px-2 py-0.5 text-xs bg-gray-200 rounded-full">
                                {{ services.length }} service{{ services.length !== 1 ? 's' : '' }}
                            </span>
                        </div>
                        <div class="divide-y">
                            <div v-for="service in services" :key="service.id"
                                 class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900">{{ service.display_name }}</span>
                                        <code class="px-2 py-0.5 text-xs bg-gray-100 rounded">{{ service.service_code }}</code>
                                        <span v-if="service.is_default" class="px-2 py-0.5 text-xs bg-primary-100 text-primary-700 rounded">Default</span>
                                        <span v-if="!service.is_active" class="px-2 py-0.5 text-xs bg-red-100 text-red-700 rounded">Inactive</span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">{{ service.description }}</p>
                                    <div class="flex gap-4 mt-1 text-xs text-gray-400">
                                        <span v-if="service.transit_time_min">
                                            <i class="mr-1 fa-regular fa-clock"></i>
                                            {{ service.transit_time_min }}-{{ service.transit_time_max || service.transit_time_min }} days
                                        </span>
                                        <span v-if="service.fallback_base_rate">
                                            <i class="mr-1 fa-solid fa-dollar-sign"></i>
                                            Base: ${{ service.fallback_base_rate }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="toggleService(service)" 
                                            :class="service.is_active ? 'text-green-600 hover:text-green-700' : 'text-gray-400 hover:text-gray-600'"
                                            title="Toggle Active">
                                        <i :class="['fa-solid', service.is_active ? 'fa-toggle-on' : 'fa-toggle-off']"></i>
                                    </button>
                                    <button @click="openServiceModal(service)" class="text-blue-600 hover:text-blue-700" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button @click="deleteService(service)" class="text-red-600 hover:text-red-700" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Addons Tab -->
                <div v-if="activeTab === 'addons'" class="space-y-6">
                    <div class="flex justify-end">
                        <PrimaryButton @click="openAddonModal(null)">
                            <i class="mr-2 fa-solid fa-plus"></i>
                            Add Addon
                        </PrimaryButton>
                    </div>

                    <div class="overflow-hidden bg-white border rounded-lg shadow-sm">
                        <div class="divide-y">
                            <div v-for="addon in carrierAddons" :key="addon.id"
                                 class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900">{{ addon.display_name }}</span>
                                        <code class="px-2 py-0.5 text-xs bg-gray-100 rounded">{{ addon.addon_code }}</code>
                                        <span :class="['px-2 py-0.5 text-xs rounded', getCarrierBadge(addon.carrier_code), 'text-white']">
                                            {{ addon.carrier_code === 'all' ? 'All Carriers' : getCarrierName(addon.carrier_code) }}
                                        </span>
                                        <span v-if="!addon.is_active" class="px-2 py-0.5 text-xs bg-red-100 text-red-700 rounded">Inactive</span>
                                    </div>
                                    <p class="mt-1 text-sm text-gray-500">{{ addon.description }}</p>
                                    <div class="flex gap-4 mt-1 text-xs text-gray-400">
                                        <span>
                                            <i class="mr-1 fa-solid fa-tag"></i>
                                            <template v-if="addon.price_type === 'fixed'">
                                                ${{ addon.price_value }}
                                            </template>
                                            <template v-else-if="addon.price_type === 'percentage'">
                                                {{ addon.price_value }}%
                                            </template>
                                            <template v-else-if="addon.price_type === 'carrier_rate'">
                                                ${{ addon.fallback_price || '—' }} (fallback)
                                            </template>
                                        </span>
                                        <span v-if="addon.source === 'admin'">
                                            <i class="mr-1 fa-solid fa-user-gear"></i>
                                            Custom
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button @click="toggleAddon(addon)" 
                                            :class="addon.is_active ? 'text-green-600 hover:text-green-700' : 'text-gray-400 hover:text-gray-600'"
                                            title="Toggle Active">
                                        <i :class="['fa-solid', addon.is_active ? 'fa-toggle-on' : 'fa-toggle-off']"></i>
                                    </button>
                                    <button @click="openAddonModal(addon)" class="text-blue-600 hover:text-blue-700" title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button @click="deleteAddon(addon)" class="text-red-600 hover:text-red-700" title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Modal -->
                <Modal :show="showServiceModal" @close="showServiceModal = false" max-width="lg">
                    <div class="p-6">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">
                            {{ editingService ? 'Edit Service' : 'Add Service' }}
                        </h2>
                        <form @submit.prevent="saveService" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Carrier</label>
                                    <select v-model="serviceForm.carrier_code" class="w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                        <option v-for="c in carriers" :key="c.code" :value="c.code">{{ c.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Service Code</label>
                                    <input v-model="serviceForm.service_code" type="text" 
                                           :disabled="editingService"
                                           class="w-full mt-1 border-gray-300 rounded-md shadow-sm" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Display Name</label>
                                <input v-model="serviceForm.display_name" type="text" class="w-full mt-1 border-gray-300 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea v-model="serviceForm.description" rows="2" class="w-full mt-1 border-gray-300 rounded-md shadow-sm"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Transit (Min Days)</label>
                                    <input v-model="serviceForm.transit_time_min" type="number" min="1" class="w-full mt-1 border-gray-300 rounded-md shadow-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Transit (Max Days)</label>
                                    <input v-model="serviceForm.transit_time_max" type="number" min="1" class="w-full mt-1 border-gray-300 rounded-md shadow-sm" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Fallback Base Rate ($)</label>
                                    <input v-model="serviceForm.fallback_base_rate" type="number" step="0.01" min="0" class="w-full mt-1 border-gray-300 rounded-md shadow-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Fallback Per-Lb Rate ($)</label>
                                    <input v-model="serviceForm.fallback_per_lb_rate" type="number" step="0.01" min="0" class="w-full mt-1 border-gray-300 rounded-md shadow-sm" />
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2">
                                    <input v-model="serviceForm.is_international" type="checkbox" class="rounded border-gray-300" />
                                    <span class="text-sm text-gray-700">International</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input v-model="serviceForm.is_domestic" type="checkbox" class="rounded border-gray-300" />
                                    <span class="text-sm text-gray-700">Domestic</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input v-model="serviceForm.is_active" type="checkbox" class="rounded border-gray-300" />
                                    <span class="text-sm text-gray-700">Active</span>
                                </label>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t">
                                <SecondaryButton @click="showServiceModal = false">Cancel</SecondaryButton>
                                <PrimaryButton type="submit" :disabled="serviceForm.processing">Save</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Modal>

                <!-- Addon Modal -->
                <Modal :show="showAddonModal" @close="showAddonModal = false" max-width="lg">
                    <div class="p-6">
                        <h2 class="mb-4 text-lg font-semibold text-gray-900">
                            {{ editingAddon ? 'Edit Addon' : 'Add Addon' }}
                        </h2>
                        <form @submit.prevent="saveAddon" class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Addon Code</label>
                                    <input v-model="addonForm.addon_code" type="text" 
                                           :disabled="editingAddon"
                                           class="w-full mt-1 border-gray-300 rounded-md shadow-sm" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Carrier</label>
                                    <select v-model="addonForm.carrier_code" class="w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                        <option value="all">All Carriers</option>
                                        <option v-for="c in carriers" :key="c.code" :value="c.code">{{ c.name }}</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Display Name</label>
                                <input v-model="addonForm.display_name" type="text" class="w-full mt-1 border-gray-300 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea v-model="addonForm.description" rows="2" class="w-full mt-1 border-gray-300 rounded-md shadow-sm"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Price Type</label>
                                    <select v-model="addonForm.price_type" class="w-full mt-1 border-gray-300 rounded-md shadow-sm">
                                        <option value="fixed">Fixed Amount</option>
                                        <option value="percentage">Percentage of Value</option>
                                        <option value="carrier_rate">Carrier Rate (Fallback)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        {{ addonForm.price_type === 'percentage' ? 'Percentage' : 'Price' }}
                                    </label>
                                    <input v-model="addonForm.price_value" type="number" step="0.01" min="0" 
                                           :disabled="addonForm.price_type === 'carrier_rate'"
                                           class="w-full mt-1 border-gray-300 rounded-md shadow-sm" />
                                </div>
                            </div>
                            <!-- Fallback Pricing for Carrier Rate type -->
                            <div v-if="addonForm.price_type === 'carrier_rate'" class="p-4 rounded-lg bg-amber-50 border border-amber-200 space-y-3">
                                <p class="text-sm text-amber-800 font-medium">
                                    <i class="fa-solid fa-info-circle mr-1"></i>
                                    Fallback Pricing Configuration
                                </p>
                                <p class="text-xs text-amber-700">
                                    Set a fallback price to display when carrier API pricing is unavailable. This ensures customers always see pricing.
                                </p>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Fallback Price ($)</label>
                                        <input v-model="addonForm.fallback_price" type="number" step="0.01" min="0" 
                                               placeholder="e.g., 18.00"
                                               class="w-full mt-1 border-gray-300 rounded-md shadow-sm" />
                                    </div>
                                    <div class="flex items-end">
                                        <label class="flex items-center gap-2 mb-2">
                                            <input v-model="addonForm.use_fallback" type="checkbox" class="rounded border-gray-300" />
                                            <span class="text-sm text-gray-700">Use Fallback Price</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-2">
                                    <input v-model="addonForm.is_active" type="checkbox" class="rounded border-gray-300" />
                                    <span class="text-sm text-gray-700">Active</span>
                                </label>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t">
                                <SecondaryButton @click="showAddonModal = false">Cancel</SecondaryButton>
                                <PrimaryButton type="submit" :disabled="addonForm.processing">Save</PrimaryButton>
                            </div>
                        </form>
                    </div>
                </Modal>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
