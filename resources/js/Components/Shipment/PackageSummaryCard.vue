<script setup>
import { ref, computed, onMounted } from "vue";
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { useToast } from "vue-toastification";
import axios from "axios";

const toast = useToast();

const props = defineProps({
    package: {
        type: Object,
        required: true,
    },
    showChangeRequest: {
        type: Boolean,
        default: true,
    },
    expanded: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["changeRequested"]);

// Local state
const isExpanded = ref(props.expanded);
const isEditMode = ref(false);
const isSubmitting = ref(false);
const showPhotosModal = ref(false);
const packagePhotos = ref([]);
const pendingChangeRequest = ref(null);
const hasCheckedPending = ref(false);

// Editable fields for change request
const editForm = ref({
    length: props.package?.length || null,
    width: props.package?.width || null,
    height: props.package?.height || null,
    weight: props.package?.total_weight || null,
    dimension_unit: props.package?.dimension_unit || "in",
    weight_unit: props.package?.weight_unit || "lb",
    notes: "",
});

// Check for pending change requests on mount
const checkPendingRequests = async () => {
    if (!props.showChangeRequest || !props.package?.id) return;
    
    try {
        const response = await axios.get(
            route("customer.package-changes.for-package", { packageId: props.package.id })
        );
        
        if (response.data.has_pending) {
            pendingChangeRequest.value = response.data.data.find(r => r.status === 'pending');
        }
    } catch (error) {
        // Silently fail - not critical
    } finally {
        hasCheckedPending.value = true;
    }
};

onMounted(() => {
    checkPendingRequests();
});

// Computed properties
const dimensionUnit = computed(() => props.package?.dimension_unit || "in");
const weightUnit = computed(() => props.package?.weight_unit || "lb");

// Check if any items have dimensions set
const hasDimensions = computed(() => {
    if (!props.package?.items?.length) return false;
    return props.package.items.some(item => item.length && item.width && item.height);
});

// Get aggregated dimensions display from items
const dimensionsDisplay = computed(() => {
    if (!props.package?.items?.length) return null;
    
    const itemsWithDims = props.package.items.filter(item => item.length && item.width && item.height);
    if (itemsWithDims.length === 0) return null;
    
    // If single item, show its dimensions
    if (itemsWithDims.length === 1) {
        const item = itemsWithDims[0];
        return `${item.length}" × ${item.width}" × ${item.height}"`;
    }
    
    // For multiple items, show count
    return `${itemsWithDims.length} items with dimensions`;
});

// Use backend-computed volumetric weight (from items)
const volumetricWeight = computed(() => {
    return Number(props.package?.total_volumetric_weight) || null;
});

// Use backend-computed billed weight (max of actual vs volumetric)
const billedWeight = computed(() => {
    return Number(props.package?.billed_weight) || Number(props.package?.total_weight) || 0;
});

const isVolumetricHigher = computed(() => {
    const actualWeight = Number(props.package?.total_weight) || 0;
    const volWeight = Number(props.package?.total_volumetric_weight) || 0;
    return volWeight > actualWeight;
});

const itemCount = computed(() => {
    return props.package?.items?.length || 0;
});

const totalQuantity = computed(() => {
    return props.package?.items?.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0) || 0;
});

// Check if any item has classification flags
const hasAnyClassification = computed(() => {
    if (!props.package?.items?.length) return false;
    return props.package.items.some(item => item.is_dangerous || item.is_fragile || item.is_oversized);
});

// Methods
const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
};

const enterEditMode = () => {
    // Reset form with current package values
    editForm.value = {
        length: props.package?.length || null,
        width: props.package?.width || null,
        height: props.package?.height || null,
        weight: props.package?.total_weight || null,
        dimension_unit: props.package?.dimension_unit || "in",
        weight_unit: props.package?.weight_unit || "lb",
        notes: "",
    };
    isEditMode.value = true;
};

const cancelEdit = () => {
    isEditMode.value = false;
};

const submitChangeRequest = async () => {
    isSubmitting.value = true;
    try {
        const response = await axios.post(
            route("customer.package.requestChange", { package: props.package.id }),
            {
                changes: editForm.value,
                notes: editForm.value.notes,
            }
        );
        
        toast.success("Change request submitted. Admin will review your request.");
        isEditMode.value = false;
        emit("changeRequested", response.data);
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to submit change request");
    } finally {
        isSubmitting.value = false;
    }
};

const fetchPhotos = async () => {
    try {
        const response = await axios.get(
            route("customers.packageGetPhotos", { package_id: props.package.id })
        );
        packagePhotos.value = response.data.data || [];
        showPhotosModal.value = true;
    } catch (error) {
        toast.error("Failed to load photos");
    }
};

const formatCurrency = (amount) => {
    return `$${Number(amount || 0).toFixed(2)}`;
};
</script>

<template>
    <div class="overflow-hidden bg-white border border-gray-200 rounded-lg shadow-sm">
        <!-- Header Row (Always Visible) -->
        <div 
            class="flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-gray-50"
            @click="toggleExpand"
        >
            <div class="flex items-center gap-3">
                <!-- Expand/Collapse Icon -->
                <i 
                    :class="[
                        'fas text-primary-500 transition-transform',
                        isExpanded ? 'fa-chevron-down' : 'fa-chevron-right'
                    ]"
                ></i>
                
                <!-- Package Icon & Source -->
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-box text-primary-500 text-xl"></i>
                    <div>
                        <p class="font-semibold text-gray-900">{{ package.from || 'Unknown Source' }}</p>
                    </div>
                </div>
            </div>

            <!-- Summary Stats -->
            <div class="flex items-center gap-6 text-sm">
                <div class="text-center">
                    <p class="text-xs text-gray-500">Items</p>
                    <p class="font-medium">{{ totalQuantity }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500">Weight</p>
                    <p class="font-medium">{{ package.total_weight || 0 }} {{ weightUnit }}</p>
                </div>
                <div class="text-center">
                    <p class="text-xs text-gray-500">Value</p>
                    <p class="font-medium text-green-600">{{ formatCurrency(package.total_value) }}</p>
                </div>
                <!-- Classification indicator in header -->
                <div v-if="hasAnyClassification" class="flex gap-1">
                    <span class="px-1.5 py-0.5 rounded text-[10px] bg-gray-100 text-gray-600" title="Has classified items">
                        <i class="fas fa-tag"></i>
                    </span>
                </div>
            </div>
        </div>

        <!-- Expanded Content -->
        <transition name="slide-fade">
            <div v-if="isExpanded" class="border-t border-gray-200">
                <div class="grid gap-4 p-4 md:grid-cols-2">
                    <!-- Left Column: Items -->
                    <div>
                        <h4 class="flex items-center gap-2 mb-3 text-sm font-semibold text-gray-700">
                            <i class="fa-solid fa-box-open text-primary-500"></i>
                            Items ({{ itemCount }})
                        </h4>
                        <div class="space-y-2">
                            <div 
                                v-for="item in package.items" 
                                :key="item.id"
                                class="p-2 rounded bg-gray-50"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <p class="font-medium text-gray-900 truncate">{{ item.title }}</p>
                                            <!-- Classification badges -->
                                            <div class="flex gap-1 flex-shrink-0">
                                                <span v-if="item.is_dangerous" class="px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-700" title="Dangerous Goods">
                                                    <i class="fas fa-fire"></i>
                                                </span>
                                                <span v-if="item.is_fragile" class="px-1.5 py-0.5 rounded text-[10px] bg-amber-100 text-amber-700" title="Fragile">
                                                    <i class="fas fa-wine-glass"></i>
                                                </span>
                                                <span v-if="item.is_oversized" class="px-1.5 py-0.5 rounded text-[10px] bg-blue-100 text-blue-700" title="Oversized">
                                                    <i class="fas fa-expand-arrows-alt"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500">{{ item.description }}</p>
                                        <!-- Item dimensions -->
                                        <p v-if="item.length || item.width || item.height" class="text-[10px] text-gray-400 mt-0.5">
                                            <i class="fas fa-ruler-combined mr-1"></i>
                                            {{ item.length || '-' }} × {{ item.width || '-' }} × {{ item.height || '-' }} {{ item.dimension_unit || 'in' }}
                                        </p>
                                    </div>
                                    <div class="text-right ml-3 flex-shrink-0">
                                        <p class="text-sm font-medium">{{ item.quantity }}x</p>
                                        <p class="text-xs text-gray-500">{{ formatCurrency(item.value_per_unit) }}/ea</p>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!package.items?.length" class="py-4 text-sm text-center text-gray-400">
                                No items recorded
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Shipping Details -->
                    <div>
                        <h4 class="flex items-center gap-2 mb-3 text-sm font-semibold text-gray-700">
                            <i class="fa-solid fa-ruler-combined text-primary-500"></i>
                            Shipping Details
                        </h4>
                        
                        <!-- Display Mode -->
                        <div v-if="!isEditMode" class="p-3 space-y-3 rounded bg-gray-50">
                            <!-- Dimensions -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Dimensions</span>
                                <span v-if="dimensionsDisplay" class="font-medium">
                                    {{ dimensionsDisplay }}
                                </span>
                                <span v-else class="text-gray-400">Not set</span>
                            </div>
                            
                            <!-- Actual Weight -->
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Actual Weight</span>
                                <span class="font-medium">{{ package.total_weight || 0 }} {{ weightUnit }}</span>
                            </div>
                            
                            <!-- Volumetric Weight -->
                            <div v-if="volumetricWeight" class="flex items-center justify-between">
                                <span class="text-sm text-gray-600">Volumetric Weight</span>
                                <span class="font-medium">{{ volumetricWeight }} {{ weightUnit }}</span>
                            </div>
                            
                            <!-- Billed Weight -->
                            <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                                <span class="text-sm font-medium text-gray-700">Billed Weight</span>
                                <span 
                                    :class="[
                                        'font-bold',
                                        isVolumetricHigher ? 'text-orange-600' : 'text-gray-900'
                                    ]"
                                >
                                    {{ billedWeight }} {{ weightUnit }}
                                    <span v-if="isVolumetricHigher" class="text-xs">(volumetric)</span>
                                </span>
                            </div>
                        </div>

                        <!-- Edit Mode (Change Request) -->
                        <div v-else class="p-3 space-y-3 border-2 rounded border-primary-200 bg-primary-50">
                            <p class="text-xs font-medium text-primary-700">
                                <i class="mr-1 fa-solid fa-edit"></i>
                                Edit fields and submit for admin approval
                            </p>
                            
                            <!-- Dimensions -->
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <InputLabel value="Length" class="text-xs" />
                                    <TextInput 
                                        v-model.number="editForm.length" 
                                        type="number" 
                                        step="0.01"
                                        class="w-full mt-1 text-sm"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Width" class="text-xs" />
                                    <TextInput 
                                        v-model.number="editForm.width" 
                                        type="number" 
                                        step="0.01"
                                        class="w-full mt-1 text-sm"
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Height" class="text-xs" />
                                    <TextInput 
                                        v-model.number="editForm.height" 
                                        type="number" 
                                        step="0.01"
                                        class="w-full mt-1 text-sm"
                                    />
                                </div>
                            </div>
                            
                            <!-- Weight -->
                            <div>
                                <InputLabel value="Weight" class="text-xs" />
                                <TextInput 
                                    v-model.number="editForm.weight" 
                                    type="number" 
                                    step="0.01"
                                    class="w-full mt-1 text-sm"
                                />
                            </div>
                            
                            <!-- Notes -->
                            <div>
                                <InputLabel value="Notes for Admin" class="text-xs" />
                                <textarea
                                    v-model="editForm.notes"
                                    rows="2"
                                    class="w-full mt-1 text-sm border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    placeholder="Explain what needs to be changed..."
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <div class="flex gap-2">
                        <button 
                            @click.stop="fetchPhotos"
                            class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
                        >
                            <i class="fa-solid fa-camera"></i>
                            Photos
                        </button>
                    </div>
                    
                    <div v-if="showChangeRequest" class="flex items-center gap-2">
                        <!-- Pending Request Indicator -->
                        <div 
                            v-if="pendingChangeRequest" 
                            class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-yellow-700 bg-yellow-50 border border-yellow-200 rounded-md"
                        >
                            <i class="fa-solid fa-clock"></i>
                            Change Request Pending
                        </div>
                        
                        <template v-else-if="!isEditMode">
                            <button 
                                @click.stop="enterEditMode"
                                class="flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-primary-700 bg-primary-50 border border-primary-200 rounded-md hover:bg-primary-100"
                            >
                                <i class="fa-solid fa-pen"></i>
                                Request Changes
                            </button>
                        </template>
                        <template v-else>
                            <SecondaryButton @click.stop="cancelEdit" class="text-sm">
                                Cancel
                            </SecondaryButton>
                            <PrimaryButton 
                                @click.stop="submitChangeRequest"
                                :disabled="isSubmitting"
                                class="text-sm"
                            >
                                <span v-if="isSubmitting">Submitting...</span>
                                <span v-else>Submit for Approval</span>
                            </PrimaryButton>
                        </template>
                    </div>
                </div>
            </div>
        </transition>
    </div>

    <!-- Photos Modal -->
    <Modal :show="showPhotosModal" @close="showPhotosModal = false">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Package Photos</h3>
                <button @click="showPhotosModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
            
            <div v-if="packagePhotos.length" class="grid grid-cols-2 gap-4 md:grid-cols-3">
                <img 
                    v-for="(photo, index) in packagePhotos" 
                    :key="index"
                    :src="photo.file_with_url"
                    class="object-cover w-full h-32 border rounded-lg"
                    alt="Package photo"
                />
            </div>
            <div v-else class="py-8 text-center text-gray-500">
                No photos available for this package
            </div>
        </div>
    </Modal>
</template>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.2s ease;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
