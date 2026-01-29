<script setup>
import { computed } from "vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    form: Object,
    isEditMode: {
        type: Boolean,
        default: false,
    },
    isSubmitting: {
        type: Boolean,
        default: false,
    },
    packageId: {
        type: String,
        default: null,
    },
    pendingPackageId: {
        type: String,
        default: null,
    },
    packageDbId: {
        type: Number,
        default: null,
    },
    specialRequest: {
        type: Object,
        default: null,
    },
});

// Calculate Total Value
const totalValue = computed(() => {
    return props.form.items.reduce((acc, item) => acc + (item.quantity * item.value_per_unit || 0), 0);
});

// Calculate Total Physical Weight from items (weight_per_unit * quantity)
const totalPhysicalWeight = computed(() => {
    return props.form.items.reduce((acc, item) => {
        const weight = parseFloat(item.weight_per_unit) || 0;
        const qty = parseInt(item.quantity) || 1;
        return acc + (weight * qty);
    }, 0);
});

// Open barcode file - fetches URL from API and opens in new tab (for PDF)
// For ZPL, directly downloads the file
const openBarcodeFile = async (url) => {
    // Check if it's a ZPL file - if so, download directly
    if (url.includes('/barcode/zpl')) {
        // Create a temporary link and trigger download
        const link = document.createElement('a');
        link.href = url;
        link.download = '';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        return;
    }
    
    // For PDF files, fetch JSON and open URL
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.url) {
            window.open(data.url, '_blank');
        } else {
            alert(data.message || 'Failed to generate barcode file');
        }
    } catch (error) {
        console.error('Error fetching barcode file:', error);
        alert('Failed to generate barcode file. Please try again.');
    }
};

// Calculate Total Volumetric Weight from item dimensions
const totalVolumetricWeight = computed(() => {
    return props.form.items.reduce((acc, item) => {
        const l = parseFloat(item.length) || 0;
        const w = parseFloat(item.width) || 0;
        const h = parseFloat(item.height) || 0;
        const qty = parseInt(item.quantity) || 1;
        
        if (l > 0 && w > 0 && h > 0) {
            // Divisor: 139 for inches, 5000 for cm
            const divisor = item.dimension_unit === 'cm' ? 5000 : 139;
            const itemVolumetric = (l * w * h) / divisor;
            return acc + (itemVolumetric * qty);
        }
        return acc;
    }, 0);
});

// Billed Weight (max of physical and volumetric)
const billedWeight = computed(() => {
    return Math.max(totalPhysicalWeight.value, totalVolumetricWeight.value);
});

// Check if volumetric weight is being used for billing
const isVolumetricBilled = computed(() => {
    return totalVolumetricWeight.value > totalPhysicalWeight.value && totalVolumetricWeight.value > 0;
});

// Count items with dimensions entered
const itemsWithDimensions = computed(() => {
    return props.form.items.filter(item => {
        const l = parseFloat(item.length) || 0;
        const w = parseFloat(item.width) || 0;
        const h = parseFloat(item.height) || 0;
        return l > 0 && w > 0 && h > 0;
    }).length;
});
</script>

<template>
    <div class="sticky top-6 space-y-4">
        <div class="bg-white border border-gray-200 shadow-sm rounded-2xl overflow-hidden">
            <div class="bg-gray-50 px-5 py-3 border-b border-gray-100">
                <h3 class="font-bold text-gray-700">Package Summary</h3>
                <p v-if="packageId || pendingPackageId" class="text-xs text-gray-500 mt-0.5 font-mono">
                    <i class="fa-solid fa-hashtag text-gray-400 mr-0.5"></i>{{ packageId || pendingPackageId }}
                </p>
                <!-- Barcode Downloads (only show if package exists) -->
                <div v-if="isEditMode && packageId && packageDbId" class="mt-3 flex gap-2">
                    <a
                        :href="route('admin.packages.barcode.pdf', packageDbId)"
                        @click.prevent="openBarcodeFile(route('admin.packages.barcode.pdf', packageDbId))"
                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 cursor-pointer"
                    >
                        <i class="fas fa-file-pdf mr-1"></i>
                        PDF
                    </a>
                    <a
                        :href="route('admin.packages.barcode.zpl', packageDbId)"
                        @click.prevent="openBarcodeFile(route('admin.packages.barcode.zpl', packageDbId))"
                        class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded hover:bg-gray-200 cursor-pointer"
                    >
                        <i class="fas fa-print mr-1"></i>
                        ZPL
                    </a>
                </div>
            </div>
            <div class="p-5 space-y-4">
                <!-- Customer Special Request Alert -->
                <div v-if="specialRequest" class="rounded-xl p-4 bg-purple-50 border border-purple-200">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid fa-bell text-purple-600"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="font-bold text-purple-900 text-sm">Customer Request</h4>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-purple-600 text-white">
                                    ${{ specialRequest.price || 0 }}
                                </span>
                            </div>
                            <p class="text-purple-800 font-medium text-sm mt-1">{{ specialRequest.title }}</p>
                            <p class="text-purple-600 text-xs mt-1 leading-relaxed">{{ specialRequest.description }}</p>
                        </div>
                    </div>
                </div>

                <!-- Status Selector (Edit Mode Only) -->
                <div v-if="isEditMode" class="pb-4 border-b border-dashed border-gray-200">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fa-solid fa-circle-dot text-gray-400 mr-1.5"></i>Package Status
                    </label>
                    <select
                        v-model="form.status"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                    >
                        <option :value="1">Action Required</option>
                        <option :value="2">In Review</option>
                        <option :value="3">Ready to Send</option>
                        <option :value="4">Sent</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Update package processing status</p>
                </div>

                <!-- Value -->
                <div class="flex justify-between items-center pb-4 border-b border-dashed border-gray-200">
                    <span class="text-sm text-gray-500">Declared Value</span>
                    <span class="text-lg font-bold text-gray-900">${{ totalValue.toFixed(2) }}</span>
                </div>

                <!-- Weight Breakdown -->
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">
                            <i class="fa-solid fa-weight-hanging text-gray-400 mr-1.5"></i>
                            Physical Weight
                        </span>
                        <span class="font-medium text-gray-700">{{ totalPhysicalWeight.toFixed(2) }} lb</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-500">
                            <i class="fa-solid fa-cube text-gray-400 mr-1.5"></i>
                            Volumetric Weight
                        </span>
                        <span class="font-medium" :class="totalVolumetricWeight > 0 ? 'text-gray-700' : 'text-gray-400'">
                            {{ totalVolumetricWeight > 0 ? totalVolumetricWeight.toFixed(2) + ' lb' : '—' }}
                        </span>
                    </div>
                    <p v-if="itemsWithDimensions < form.items.length" class="text-xs text-amber-600 bg-amber-50 rounded px-2 py-1">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                        {{ form.items.length - itemsWithDimensions }} item(s) missing dimensions
                    </p>
                </div>

                <!-- Billed Weight Highlight -->
                <div class="rounded-xl p-3 flex justify-between items-center" 
                     :class="isVolumetricBilled ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700'">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider">Billed Weight</span>
                        <p class="text-[10px] mt-0.5 opacity-75">
                            {{ isVolumetricBilled ? 'Based on volumetric' : 'Based on physical' }}
                        </p>
                    </div>
                    <span class="font-bold text-xl">
                        {{ billedWeight.toFixed(2) }} lb
                    </span>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="p-4 bg-gray-50 border-t border-gray-100 space-y-2">
                <PrimaryButton
                    class="w-full justify-center bg-gray-900 hover:bg-black py-3 text-base shadow-lg shadow-gray-200 transition-all active:scale-95"
                    :disabled="isSubmitting"
                    type="submit"
                >
                    <span v-if="isSubmitting"><i class="fa-solid fa-spinner fa-spin mr-2"></i>Saving...</span>
                    <span v-else-if="isEditMode"><i class="fa-solid fa-pen mr-2"></i>Update Package</span>
                    <span v-else><i class="fa-solid fa-check mr-2"></i>Create Package</span>
                </PrimaryButton>
                
                <!-- Save as Draft only in create mode (hidden for now) -->
                <button 
                    v-if="false && !isEditMode"
                    type="button" 
                    @click="$emit('save-draft')"
                    :disabled="isSubmitting"
                    class="w-full flex items-center justify-center gap-2 text-sm text-gray-500 hover:text-gray-700 py-2 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors disabled:opacity-50"
                >
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Save as Draft</span>
                </button>
            </div>
        </div>

        <!-- Help Card -->
        <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-5 text-white shadow-lg relative overflow-hidden hidden xl:block">
            <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -translate-y-10 translate-x-10"></div>
             <div class="absolute bottom-0 left-0 w-16 h-16 bg-white/10 rounded-full translate-y-6 -translate-x-6"></div>
            <h4 class="font-bold mb-2 relative z-10">Weight Info</h4>
            <p class="text-indigo-100 text-sm mb-3 relative z-10 leading-relaxed">
                Carriers charge based on the higher of physical or volumetric weight. Add item dimensions for accurate billing estimates.
            </p>
        </div>
    </div>
</template>


