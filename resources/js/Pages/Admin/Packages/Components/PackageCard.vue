<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3"; 
import { usePermissions } from "@/Composables/usePermissions";

const props = defineProps({
    packageData: { type: Object, required: true },
});

const emit = defineEmits(['edit', 'add-note', 'quick-view', 'delete']);

const { can } = usePermissions();

// Dropdown state
const showDropdown = ref(false);

const toggleDropdown = (e) => {
    e.stopPropagation();
    showDropdown.value = !showDropdown.value;
};

const closeDropdown = () => {
    showDropdown.value = false;
};

// Actions
const handleEdit = () => {
    closeDropdown();
    router.visit(route('admin.packages.edit', props.packageData.id));
};

const handleAddNote = (e) => {
    if (e) e.stopPropagation();
    closeDropdown();
    emit('add-note', props.packageData);
};

const handleQuickView = (e) => {
    if (e) e.stopPropagation();
    closeDropdown();
    emit('quick-view', props.packageData);
};

const handleDelete = (e) => {
    if (e) e.stopPropagation();
    closeDropdown();
    emit('delete', props.packageData);
};

// Status Colors
const statusColorClass = computed(() => {
    switch (props.packageData.status) {
        case 1: return "bg-red-50 text-red-700 border-red-100";
        case 2: return "bg-yellow-50 text-yellow-700 border-yellow-100";
        case 3: return "bg-blue-50 text-blue-700 border-blue-100";
        case 4: return "bg-green-50 text-green-700 border-green-100";
        default: return "bg-gray-50 text-gray-700 border-gray-100";
    }
});

const formatDate = (dateString) => {
    if (!dateString) return "N/A";
    return new Date(dateString).toLocaleDateString("en-US", {
        month: "short", day: "numeric"
    });
};

const formatCurrency = (value) => {
    if (!value) return '';
    return `$${parseFloat(value).toFixed(2)}`;
};

// Check if package is old (> 3 days)
const isOld = computed(() => {
    if (!props.packageData.created_at) return false;
    const created = new Date(props.packageData.created_at);
    const now = new Date();
    const diffDays = (now - created) / (1000 * 60 * 60 * 24);
    return diffDays > 3;
});

// Check if any item has classification flags
const hasClassification = computed(() => {
    const items = props.packageData.items || [];
    if (items.length === 0) return { dangerous: false, fragile: false, oversized: false };
    return {
        dangerous: items.some(item => item.is_dangerous),
        fragile: items.some(item => item.is_fragile),
        oversized: items.some(item => item.is_oversized)
    };
});

const hasAnyClassification = computed(() => {
    return hasClassification.value.dangerous || hasClassification.value.fragile || hasClassification.value.oversized;
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
</script>

<template>
    <div 
        class="bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all duration-200 group cursor-grab active:cursor-grabbing relative overflow-hidden"
        @click="handleEdit"
        v-click-outside="closeDropdown"
    >
        <!-- Quick Status Indicator Strip -->
        <div class="absolute left-0 top-0 bottom-0 w-1" 
            :class="{
                'bg-red-500': packageData.status === 1,
                'bg-yellow-500': packageData.status === 2,
                'bg-blue-500': packageData.status === 3,
                'bg-green-500': packageData.status === 4
            }">
        </div>

        <!-- Age Warning Badge -->
        <div v-if="isOld" class="absolute top-2 right-10 z-10" title="Package pending for more than 3 days">
            <span class="text-[9px] bg-orange-100 text-orange-600 px-1.5 py-0.5 rounded-full font-medium">
                <i class="fas fa-clock mr-0.5"></i> Old
            </span>
        </div>

        <!-- Classification Warning (if any) -->
        <div v-if="hasAnyClassification" class="absolute top-2 left-5 z-10 flex gap-1">
            <span v-if="hasClassification.dangerous" class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center" title="Dangerous Goods">
                <i class="fas fa-fire text-red-600 text-[10px]"></i>
            </span>
            <span v-if="hasClassification.fragile" class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center" title="Fragile">
                <i class="fas fa-wine-glass text-amber-600 text-[10px]"></i>
            </span>
            <span v-if="hasClassification.oversized" class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center" title="Oversized">
                <i class="fas fa-expand-arrows-alt text-blue-600 text-[10px]"></i>
            </span>
        </div>

        <div class="p-4 pl-5"> 
            <!-- Header: ID, Name, and Actions -->
            <div class="flex justify-between items-start mb-2">
                <div class="flex flex-col min-w-0 flex-1">
                    <span class="text-xs font-mono text-gray-400">#{{ packageData.package_id }}</span>
                    <h4 class="font-semibold text-gray-900 line-clamp-1 text-sm" :title="packageData.customer?.name">
                        {{ packageData.customer?.name || 'Unknown Customer' }}
                    </h4>
                </div>
                
                <!-- Actions Dropdown -->
                <div class="relative" v-if="can('packages.update')">
                    <button 
                        @click="toggleDropdown"
                        class="w-7 h-7 flex items-center justify-center rounded-lg text-gray-300 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                    >
                        <i class="fas fa-ellipsis-v text-xs"></i>
                    </button>
                    
                    <!-- Dropdown Menu -->
                    <div 
                        v-show="showDropdown"
                        class="absolute right-0 top-full mt-1 w-36 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50"
                    >
                        <button 
                            @click="handleEdit" 
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                        >
                            <i class="fas fa-edit text-gray-400 w-4"></i> Edit
                        </button>
                        <button 
                            v-if="can('packages.notes.update')"
                            @click="handleAddNote($event)" 
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                        >
                            <i class="fas fa-sticky-note text-gray-400 w-4"></i> Add Note
                        </button>
                        <button 
                            @click="handleQuickView($event)" 
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                        >
                            <i class="fas fa-eye text-gray-400 w-4"></i> Quick View
                        </button>
                        <hr class="my-1 border-gray-100">
                        <a 
                            v-if="packageData.package_id"
                            :href="route('admin.packages.barcode.pdf', packageData.id)"
                            @click.stop.prevent="openBarcodeFile(route('admin.packages.barcode.pdf', packageData.id))"
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 cursor-pointer"
                        >
                            <i class="fas fa-barcode text-gray-400 w-4"></i> Barcode PDF
                        </a>
                        <a 
                            v-if="packageData.package_id"
                            :href="route('admin.packages.barcode.zpl', packageData.id)"
                            @click.stop.prevent="openBarcodeFile(route('admin.packages.barcode.zpl', packageData.id))"
                            class="w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 cursor-pointer"
                        >
                            <i class="fas fa-print text-gray-400 w-4"></i> Barcode ZPL
                        </a>
                        <hr class="my-1 border-gray-100">
                        <button 
                            v-if="can('packages.delete')"
                            @click="handleDelete($event)" 
                            class="w-full px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                        >
                            <i class="fas fa-trash text-red-400 w-4"></i> Delete
                        </button>
                        <hr class="my-1 border-gray-100">
                        <Link 
                            :href="route('admin.customers.edit', packageData.customer?.id)"
                            class="w-full px-3 py-2 text-left text-sm text-gray-500 hover:bg-gray-50 flex items-center gap-2"
                            v-if="packageData.customer?.id"
                        >
                            <i class="fas fa-user text-gray-400 w-4"></i> View Customer
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-2 gap-y-1.5 gap-x-1 text-xs text-gray-600 mb-3">
                <div title="Suite" class="flex items-center gap-1.5">
                    <i class="fas fa-door-open text-gray-400 w-3 text-[10px]"></i>
                    <span class="font-medium bg-blue-50 text-blue-700 px-1.5 py-0.5 rounded text-[10px]">{{ packageData.customer?.suite || 'N/A' }}</span>
                </div>
                <div title="Tracking Number" class="flex items-center gap-1.5 truncate">
                    <i class="fas fa-barcode text-gray-400 w-3 text-[10px]"></i>
                    <span class="truncate text-[11px]">{{ packageData.tracking_id || '---' }}</span>
                </div>
                <div v-if="packageData.total_value" title="Value" class="flex items-center gap-1.5">
                    <i class="fas fa-dollar-sign text-gray-400 w-3 text-[10px]"></i>
                    <span class="font-medium text-green-700">{{ formatCurrency(packageData.total_value) }}</span>
                </div>
                <div v-if="packageData.total_weight" title="Weight" class="flex items-center gap-1.5">
                    <i class="fas fa-weight-hanging text-gray-400 w-3 text-[10px]"></i>
                    <span>{{ packageData.total_weight }} {{ packageData.weight_unit || 'lb' }}</span>
                </div>
            </div>

            <!-- Footer: Date, Items, From -->
            <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] text-gray-400 font-medium">
                        {{ formatDate(packageData.date_received || packageData.created_at) }}
                    </span>
                    <span v-if="packageData.from" class="text-[10px] text-gray-400 truncate max-w-[60px]" :title="packageData.from">
                        · {{ packageData.from }}
                    </span>
                </div>
                
                <div class="flex items-center gap-2">
                    <!-- Barcode indicator -->
                    <a 
                        v-if="packageData.package_id"
                        :href="route('admin.packages.barcode.pdf', packageData.id)"
                        @click.stop.prevent="openBarcodeFile(route('admin.packages.barcode.pdf', packageData.id))"
                        class="flex items-center gap-1 text-[10px] text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded hover:bg-blue-100 cursor-pointer"
                        title="Download Barcode"
                    >
                        <i class="fas fa-barcode"></i>
                    </a>
                    <!-- Items count -->
                    <div v-if="packageData.items_count > 0" class="flex items-center gap-1 text-[10px] text-gray-500 bg-gray-50 px-1.5 py-0.5 rounded">
                        <i class="fas fa-box-open"></i> {{ packageData.items_count }}
                    </div>
                    <!-- Invoices count -->
                    <div v-if="packageData.invoices_count > 0" class="flex items-center gap-1 text-[10px] text-emerald-600 bg-emerald-50 px-1.5 py-0.5 rounded">
                        <i class="fas fa-file-invoice-dollar"></i> {{ packageData.invoices_count }}
                    </div>
                    <!-- Files indicator -->
                    <div v-if="packageData.files_count > 0" class="flex items-center gap-1 text-[10px] text-gray-500 bg-gray-50 px-1.5 py-0.5 rounded">
                        <i class="fas fa-paperclip"></i> {{ packageData.files_count }}
                    </div>
                </div>
            </div>

            <!-- Note Preview (if exists) -->
            <div v-if="packageData.note" class="mt-2 p-2 bg-yellow-50 rounded-lg border border-yellow-100">
                <p class="text-[10px] text-yellow-800 line-clamp-2">
                    <i class="fas fa-sticky-note mr-1"></i>
                    {{ packageData.note }}
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Click outside directive styles */
</style>
