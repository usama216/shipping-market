<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import SearchableSelect from "vue-select";
import InputError from "@/Components/InputError.vue";
import { computed } from "vue";

const props = defineProps({
    form: Object,
    users: Array,
    warehouses: Array,
});

const selectedCustomer = computed(() => {
    return props.users.find((u) => u.id === props.form.customer_id);
});
</script>

<template>
    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl">
        <div class="flex items-center gap-3 mb-6">
            <div class="flex items-center justify-center w-10 h-10 text-blue-600 bg-blue-50 rounded-xl">
                <i class="fa-solid fa-user-tag text-lg"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-800">Customer & Destination</h2>
                <p class="text-sm text-gray-500">Assign package to a customer and warehouse</p>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Customer Selection -->
            <div>
                <InputLabel for="customer" value="Select Customer" />
                <SearchableSelect
                    id="customer_id"
                    class="w-full mt-1.5"
                    label="suite"
                    :options="users"
                    :reduce="(option) => option.id"
                    v-model="form.customer_id"
                    placeholder="Search by name, suite #, or email..."
                    :filter-by="(option, label, search) => {
                        const query = search.toLowerCase();
                        return (
                            (option.name && option.name.toLowerCase().includes(query)) ||
                            (option.email && option.email.toLowerCase().includes(query)) ||
                            (option.suite && String(option.suite).toLowerCase().includes(query))
                        );
                    }"
                >
                     <template #option="{ suite, name, email }">
                        <div class="py-1">
                            <span class="font-bold text-gray-800">#{{ suite }}</span> 
                            <span class="mx-2 text-gray-300">|</span>
                            <span class="font-medium text-gray-700">{{ name }}</span>
                            <span class="ml-2 text-xs text-gray-500">({{ email }})</span>
                        </div>
                    </template>
                </SearchableSelect>
                <InputError class="mt-2" :message="form.errors.customer_id" />
            </div>

            <!-- Selected Customer Card -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-2 opacity-0"
            >
                <div v-if="selectedCustomer" class="relative overflow-hidden border border-blue-100 rounded-xl bg-blue-50/50">
                    <div class="absolute top-0 right-0 w-16 h-16 transform translate-x-4 -translate-y-4 rounded-full bg-blue-100/50"></div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 gap-y-3 sm:grid-cols-2">
                             <div class="flex items-center gap-2">
                                <i class="w-4 text-blue-400 fa-solid fa-layer-group"></i>
                                <span class="text-sm font-medium text-gray-500">Suite:</span>
                                <span class="font-bold text-gray-800">#{{ selectedCustomer?.suite ?? "N/A" }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="w-4 text-blue-400 fa-solid fa-user"></i>
                                <span class="text-sm font-medium text-gray-500">Name:</span>
                                <span class="font-semibold text-gray-800">{{ selectedCustomer?.name ?? "N/A" }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="w-4 text-blue-400 fa-solid fa-envelope"></i>
                                <span class="text-sm font-medium text-gray-500">Email:</span>
                                <span class="text-gray-800">{{ selectedCustomer?.email ?? "N/A" }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="w-4 text-blue-400 fa-solid fa-phone"></i>
                                <span class="text-sm font-medium text-gray-500">Phone:</span>
                                <span class="text-gray-800">{{ selectedCustomer?.phone ?? "N/A" }}</span>
                            </div>
                            <div class="flex items-center gap-2 sm:col-span-2">
                                <i class="w-4 text-blue-400 fa-solid fa-location-dot"></i>
                                <span class="text-sm font-medium text-gray-500">Address:</span>
                                <span class="text-gray-800 truncate">
                                    {{ [selectedCustomer?.city, selectedCustomer?.state].filter(Boolean).join(', ') || 'Address not set' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Warehouse Selection -->
            <div v-if="warehouses.length > 0" class="pt-4 border-t border-gray-100">
                <InputLabel for="warehouse" value="Origin Warehouse" />
                <div class="relative mt-1.5">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="text-gray-400 fa-solid fa-warehouse"></i>
                    </div>
                    <select
                        id="warehouse_id"
                        v-model="form.warehouse_id"
                        class="w-full pl-10 border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    >
                        <option 
                            v-for="warehouse in warehouses" 
                            :key="warehouse.id" 
                            :value="warehouse.id"
                        >
                            {{ warehouse.name }} ({{ warehouse.code }}) - {{ warehouse.city }}
                            {{ warehouse.is_default ? '★ Default' : '' }}
                        </option>
                    </select>
                </div>
                <InputError class="mt-2" :message="form.errors.warehouse_id" />
            </div>
        </div>
    </div>
</template>
