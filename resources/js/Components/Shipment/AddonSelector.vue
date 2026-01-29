<script setup>
/**
 * AddonSelector Component (Refactored for Backend Enriched Data)
 * 
 * Displays carrier addons with live pricing from backend.
 * Data flow: Backend → CarrierSelector → Create.vue → AddonSelector
 * 
 * Each addon object from backend includes:
 * - id, addon_code, display_name, description, icon
 * - calculated_price (live from FedEx or calculated)
 * - price_display (formatted string)
 * - is_available (false if carrier_rate without live pricing)
 * - is_mandatory (true if package has dangerous/fragile/oversized items)
 * - is_live_price (true if price from FedEx surcharge data)
 * - unavailable_reason (string if is_available is false)
 */
import { ref, computed, watch } from 'vue';

const props = defineProps({
    carrierAddons: {
        type: Array,
        default: () => []
    },
    selectedAddons: {
        type: Array,
        default: () => []
    },
    declaredValue: {
        type: Number,
        default: 0
    },
    packageClassifications: {
        type: Object,
        default: () => ({})
    }
});

const emit = defineEmits(['addons-changed', 'declared-value-changed']);

// Local state
const selectedAddonIds = ref([...props.selectedAddons]);
const localDeclaredValue = ref(props.declaredValue);
const expandedAddons = ref(false);

// Available addons (already filtered by backend for carrier)
// Sorted: mandatory first, then available, then unavailable last
const availableAddons = computed(() => {
    const addons = props.carrierAddons || [];
    
    return [...addons].sort((a, b) => {
        // Priority 1: Mandatory and available (show first)
        const aMandatoryAvailable = a.is_mandatory && a.is_available !== false;
        const bMandatoryAvailable = b.is_mandatory && b.is_available !== false;
        if (aMandatoryAvailable && !bMandatoryAvailable) return -1;
        if (!aMandatoryAvailable && bMandatoryAvailable) return 1;
        
        // Priority 2: Available (show before unavailable)
        const aAvailable = a.is_available !== false;
        const bAvailable = b.is_available !== false;
        if (aAvailable && !bAvailable) return -1;
        if (!aAvailable && bAvailable) return 1;
        
        // Priority 3: Sort by sort_order or id
        return (a.sort_order || a.id) - (b.sort_order || b.id);
    });
});

// Calculate total addon charges (only for selected + available addons)
const totalAddonCharges = computed(() => {
    return availableAddons.value
        .filter(addon => selectedAddonIds.value.includes(addon.id) && addon.is_available !== false)
        .reduce((sum, addon) => sum + (addon.calculated_price || 0), 0);
});

// Selected addon details for display
const selectedAddonDetails = computed(() => {
    return availableAddons.value.filter(addon => 
        selectedAddonIds.value.includes(addon.id) && addon.is_available !== false
    );
});

// Toggle addon selection
const toggleAddon = (addon) => {
    // Don't allow toggle if unavailable
    if (addon.is_available === false) {
        return;
    }
    
    // Don't allow deselection of mandatory addons
    if (addon.is_mandatory && selectedAddonIds.value.includes(addon.id)) {
        return;
    }
    
    // Check if addon requires declared value
    if (addon.requires_value_declaration && localDeclaredValue.value <= 0) {
        return;
    }
    
    const index = selectedAddonIds.value.indexOf(addon.id);
    if (index === -1) {
        selectedAddonIds.value.push(addon.id);
    } else {
        selectedAddonIds.value.splice(index, 1);
    }
    
    emitChanges();
};

// Update declared value
const updateDeclaredValue = (value) => {
    localDeclaredValue.value = parseFloat(value) || 0;
    emit('declared-value-changed', localDeclaredValue.value);
    emitChanges();
};

// Emit changes to parent
const emitChanges = () => {
    emit('addons-changed', {
        selectedAddonIds: selectedAddonIds.value,
        totalCharges: totalAddonCharges.value,
        declaredValue: localDeclaredValue.value
    });
};

// Format price for display
const formatPrice = (price) => {
    if (typeof price === 'number') {
        return price.toLocaleString('en-US', {
            style: 'currency',
            currency: 'USD'
        });
    }
    return price;
};

// Get addon icon class
const getAddonIcon = (addon) => {
    const iconMap = {
        'dangerous_goods': 'fa-skull-crossbones',
        'extra_handling': 'fa-hand-holding-box',
        'additional_handling': 'fa-boxes-stacked',
        'signature_required': 'fa-signature',
        'insurance': 'fa-shield-alt',
        'insurance_basic': 'fa-shield-alt',
        'insurance_premium': 'fa-shield-virus',
        'fragile_handling': 'fa-glass-whiskey',
        'hold_at_location': 'fa-store',
        'saturday_delivery': 'fa-calendar-day',
        'dry_ice': 'fa-snowflake',
        'priority_handling': 'fa-bolt',
    };
    return addon.icon || iconMap[addon.addon_code] || 'fa-plus-circle';
};

// Watch for prop changes
watch(() => props.selectedAddons, (newVal) => {
    selectedAddonIds.value = [...newVal];
}, { deep: true });

watch(() => props.declaredValue, (newVal) => {
    localDeclaredValue.value = newVal;
});

// Auto-emit on mount if there are mandatory addons
watch(() => props.carrierAddons, (newAddons) => {
    if (newAddons && newAddons.length > 0) {
        // Auto-select mandatory addons
        const mandatoryIds = newAddons
            .filter(a => a.is_mandatory && a.is_available !== false)
            .map(a => a.id);
        
        if (mandatoryIds.length > 0) {
            mandatoryIds.forEach(id => {
                if (!selectedAddonIds.value.includes(id)) {
                    selectedAddonIds.value.push(id);
                }
            });
            emitChanges();
        }
    }
}, { immediate: true });
</script>

<template>
    <div class="space-y-4">
        <!-- Addon Selection -->
        <div v-if="availableAddons.length > 0" class="space-y-3">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-medium text-gray-700">
                    <i class="mr-2 fa-solid fa-puzzle-piece text-primary-500"></i>
                    Carrier Add-ons
                </h4>
                <button 
                    v-if="availableAddons.length > 4"
                    @click="expandedAddons = !expandedAddons"
                    class="text-sm text-primary-600 hover:text-primary-700"
                >
                    {{ expandedAddons ? 'Show less' : `Show all (${availableAddons.length})` }}
                </button>
            </div>

            <!-- Addon Grid -->
            <div class="grid gap-3 sm:grid-cols-2">
                <template v-for="(addon, index) in availableAddons" :key="addon.id">
                    <div 
                        v-if="expandedAddons || index < 4"
                        @click="toggleAddon(addon)"
                        :class="[
                            'relative p-4 border-2 rounded-lg transition-all duration-200',
                            // Unavailable state
                            addon.is_available === false
                                ? 'border-gray-200 bg-gray-50 cursor-not-allowed opacity-60'
                                : selectedAddonIds.includes(addon.id)
                                    ? 'border-primary-500 bg-primary-50 shadow-md cursor-pointer'
                                    : 'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50 cursor-pointer',
                            // Mandatory indicator
                            addon.is_mandatory && addon.is_available !== false
                                ? 'ring-2 ring-amber-300 ring-offset-1'
                                : '',
                            // Requires declared value
                            addon.requires_value_declaration && localDeclaredValue <= 0
                                ? 'opacity-50 cursor-not-allowed'
                                : ''
                        ]"
                    >
                        <!-- Selection indicator / Mandatory badge -->
                        <div class="absolute top-2 right-2 flex gap-1">
                            <span v-if="addon.is_mandatory && addon.is_available !== false" 
                                  class="px-1.5 py-0.5 text-[10px] font-semibold text-amber-700 bg-amber-100 rounded"
                                  title="Required for your package contents">
                                Required
                            </span>
                            <span v-if="selectedAddonIds.includes(addon.id) && addon.is_available !== false" 
                                  class="flex items-center justify-center w-5 h-5 text-white rounded-full bg-primary-500">
                                <i class="text-xs fa-solid fa-check"></i>
                            </span>
                        </div>

                        <div class="flex items-start gap-3">
                            <!-- Icon -->
                            <div :class="[
                                'flex items-center justify-center w-10 h-10 rounded-lg',
                                addon.is_available === false
                                    ? 'bg-gray-100 text-gray-400'
                                    : selectedAddonIds.includes(addon.id)
                                        ? 'bg-primary-100 text-primary-600'
                                        : 'bg-gray-100 text-gray-500'
                            ]">
                                <i :class="['fa-solid', getAddonIcon(addon)]"></i>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0 pr-16">
                                <h5 class="text-sm font-semibold text-gray-900 truncate">
                                    {{ addon.display_name }}
                                </h5>
                                <p class="mt-1 text-xs text-gray-500 line-clamp-2">
                                    {{ addon.description }}
                                </p>
                                
                                <!-- Price -->
                                <div class="flex items-center gap-2 mt-2">
                                    <!-- Unavailable -->
                                    <template v-if="addon.is_available === false">
                                        <span class="text-sm text-gray-400">
                                            <i class="mr-1 fa-solid fa-times-circle"></i>
                                            {{ addon.unavailable_reason || 'Not available' }}
                                        </span>
                                    </template>
                                    
                                    <!-- Available with price -->
                                    <template v-else>
                                        <span :class="[
                                            'text-sm font-bold',
                                            selectedAddonIds.includes(addon.id)
                                                ? 'text-primary-600'
                                                : 'text-gray-700'
                                        ]">
                                            <!-- Use price_display from backend if available -->
                                            <template v-if="addon.price_display">
                                                {{ addon.price_display }}
                                            </template>
                                            <template v-else>
                                                {{ formatPrice(addon.calculated_price || 0) }}
                                            </template>
                                            
                                            <!-- Live price badge -->
                                            <span v-if="addon.is_live_price" 
                                                  class="ml-1 px-1.5 py-0.5 text-[10px] font-semibold text-green-700 bg-green-100 rounded">
                                                ✓ Live
                                            </span>
                                        </span>
                                    </template>
                                    
                                    <span v-if="addon.requires_value_declaration && localDeclaredValue <= 0" 
                                          class="text-xs text-amber-600">
                                        <i class="mr-1 fa-solid fa-exclamation-triangle"></i>
                                        Requires declared value
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else class="p-6 text-center border border-gray-200 border-dashed rounded-lg">
            <i class="mb-2 text-2xl text-gray-400 fa-solid fa-puzzle-piece"></i>
            <p class="text-sm text-gray-500">Select a carrier to see available add-ons</p>
        </div>

        <!-- Selected Addons Summary -->
        <div v-if="selectedAddonDetails.length > 0" class="p-4 border rounded-lg bg-primary-50 border-primary-200">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-medium text-primary-800">
                    <i class="mr-2 fa-solid fa-check-circle"></i>
                    Selected Add-ons
                </h4>
                <span class="text-lg font-bold text-primary-600">
                    +{{ formatPrice(totalAddonCharges) }}
                </span>
            </div>
            <div class="space-y-1">
                <div v-for="addon in selectedAddonDetails" :key="addon.id" 
                     class="flex items-center justify-between text-sm">
                    <span class="text-primary-700">
                        {{ addon.display_name }}
                        <span v-if="addon.is_mandatory" class="text-xs text-amber-600 ml-1">(Required)</span>
                    </span>
                    <span class="text-primary-600">{{ formatPrice(addon.calculated_price || 0) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
