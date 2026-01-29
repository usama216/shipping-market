<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import QuickViewModal from "./Components/QuickViewModal.vue";
import KanbanBoard from "./Components/KanbanBoard.vue";
import PackageNoteModal from "./Components/PackageNoteModal.vue";
import { usePermissions } from "@/Composables/usePermissions";

const { can } = usePermissions();

const props = defineProps({
    packages: Object,
    filters: Object,
    customers: Array,
    suites: Array,
    warehouses: Array,
    defaultWarehouseId: Number,
    stats: Object,
});

const viewMode = ref("list"); // 'list' or 'board'
const showQuickView = ref(false);
const showNoteModal = ref(false);
const showDeleteModal = ref(false);
const selectedPackage = ref(null);
const isDeleting = ref(false);
const barcodeDropdownOpen = ref({}); // Track which package's barcode dropdown is open

const toggleView = (mode) => {
    viewMode.value = mode;
};

// Navigate to create page
const goToCreate = () => {
    router.visit(route('admin.packages.create'));
};

// Navigate to edit page
const goToEdit = (pkg) => {
    router.visit(route('admin.packages.edit', pkg.id));
};

// Quick View Handler
const handleQuickView = (pkg) => {
    selectedPackage.value = pkg;
    showQuickView.value = true;
};

const closeQuickView = () => {
    showQuickView.value = false;
    selectedPackage.value = null; // Don't clear immediately to avoid modal content jump, but here it's fine
};

// Add Note Handler
const handleAddNote = (pkg) => {
    selectedPackage.value = pkg;
    showNoteModal.value = true;
};

const closeNoteModal = () => {
    showNoteModal.value = false;
    selectedPackage.value = null;
};

// Delete Package Handler
const handleDeleteClick = (pkg, e) => {
    if (e) e.stopPropagation();
    selectedPackage.value = pkg;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    selectedPackage.value = null;
};

const confirmDelete = () => {
    if (!selectedPackage.value) return;
    isDeleting.value = true;
    router.delete(route('admin.packages.delete', selectedPackage.value.id), {
        onSuccess: () => {
            closeDeleteModal();
            isDeleting.value = false;
        },
        onError: () => {
            isDeleting.value = false;
        }
    });
};

// Clear all filters
const clearAllFilters = () => {
    router.get(route("admin.packages"), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Filter by status (clicking stats card)
const filterByStatus = (status) => {
    router.get(route("admin.packages"), { status }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// Format currency
const formatCurrency = (value) => {
    if (!value) return '-';
    return `$${parseFloat(value).toFixed(2)}`;
};

// Format weight
const formatWeight = (weight, unit = 'lb') => {
    if (!weight) return '-';
    return `${parseFloat(weight).toFixed(2)} ${unit}`;
};

// Format date
const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short', day: 'numeric', year: 'numeric'
    });
};

// Check if any item has classification flags
const hasClassification = (pkg) => {
    if (!pkg.items || pkg.items.length === 0) return { dangerous: false, fragile: false, oversized: false };
    return {
        dangerous: pkg.items.some(item => item.is_dangerous),
        fragile: pkg.items.some(item => item.is_fragile),
        oversized: pkg.items.some(item => item.is_oversized)
    };
};

// Toggle barcode dropdown
const toggleBarcodeDropdown = (pkgId, e) => {
    if (e) e.stopPropagation();
    // Close all other dropdowns
    Object.keys(barcodeDropdownOpen.value).forEach(key => {
        if (key !== pkgId.toString()) {
            barcodeDropdownOpen.value[key] = false;
        }
    });
    barcodeDropdownOpen.value[pkgId] = !barcodeDropdownOpen.value[pkgId];
};

// Close barcode dropdown
const closeBarcodeDropdown = (pkgId) => {
    barcodeDropdownOpen.value[pkgId] = false;
};

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

// Close dropdown when clicking outside
onMounted(() => {
    const handleClickOutside = (e) => {
        // Check if click is outside dropdown
        if (!e.target.closest('.barcode-dropdown-container')) {
            Object.keys(barcodeDropdownOpen.value).forEach(key => {
                barcodeDropdownOpen.value[key] = false;
            });
        }
    };
    document.addEventListener('click', handleClickOutside);
    
    onUnmounted(() => {
        document.removeEventListener('click', handleClickOutside);
    });
});
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Packages" />
        <div class="container-fluid">
          
            <!-- Header & Controls -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                <div>
                   <h1 class="text-2xl font-bold text-gray-900">Packages</h1>
                   <p class="text-gray-500 text-sm">Manage incoming shipments and consolidations</p>
                </div>
                
                <div class="flex items-center gap-3">
                   <!-- View Toggles -->
                   <div class="bg-gray-100 p-1 rounded-lg flex items-center">
                       <button 
                           @click="toggleView('list')"
                           :class="{'bg-white shadow text-blue-600': viewMode === 'list', 'text-gray-500 hover:text-gray-700': viewMode !== 'list'}"
                           class="px-3 py-1.5 rounded-md text-sm font-medium transition-all"
                        >
                           <i class="fas fa-list mr-2"></i> List
                       </button>
                       <button 
                           @click="toggleView('board')"
                           :class="{'bg-white shadow text-blue-600': viewMode === 'board', 'text-gray-500 hover:text-gray-700': viewMode !== 'board'}"
                           class="px-3 py-1.5 rounded-md text-sm font-medium transition-all"
                        >
                           <i class="fas fa-columns mr-2"></i> Board
                       </button>
                   </div>
                   
                   <PrimaryButton v-if="can('packages.create')" @click="goToCreate">
                       <i class="fas fa-plus mr-2"></i> New Package
                   </PrimaryButton>
                </div>
            </div>

            <!-- Stats Bar (hidden in board view since columns show counts) -->
            <div v-if="viewMode === 'list'" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <button @click="filterByStatus(1)" 
                    class="bg-white border rounded-xl p-4 hover:shadow-md transition-shadow text-left group"
                    :class="{'ring-2 ring-red-500': filters.status == 1}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ stats?.action_required || 0 }}</p>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Action Required</p>
                        </div>
                    </div>
                </button>
                
                <button @click="filterByStatus(2)" 
                    class="bg-white border rounded-xl p-4 hover:shadow-md transition-shadow text-left group"
                    :class="{'ring-2 ring-yellow-500': filters.status == 2}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                            <i class="fas fa-search text-yellow-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ stats?.in_review || 0 }}</p>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">In Review</p>
                        </div>
                    </div>
                </button>
                
                <button @click="filterByStatus(3)" 
                    class="bg-white border rounded-xl p-4 hover:shadow-md transition-shadow text-left group"
                    :class="{'ring-2 ring-blue-500': filters.status == 3}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-paper-plane text-blue-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ stats?.ready_to_send || 0 }}</p>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Ready to Send</p>
                        </div>
                    </div>
                </button>
                
                <button @click="clearAllFilters" 
                    class="bg-white border rounded-xl p-4 hover:shadow-md transition-shadow text-left group"
                    :class="{'ring-2 ring-gray-500': !filters.status}">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-box text-gray-600"></i>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-gray-900">{{ stats?.total || 0 }}</p>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Packages</p>
                        </div>
                    </div>
                </button>
            </div>

            <!-- Filters -->
            <PackageFilter 
                class="mb-6"
                :customers="customers" 
                :suites="suites" 
                :current-filters="filters" 
            />

            <!-- Content Area -->
            <div>
                
                <!-- LIST VIEW -->
                <transition name="fade">
                    <div v-if="viewMode === 'list'">
                        
                        <!-- No Results -->
                        <NoResults
                            v-if="packages.data.length === 0"
                            icon="fas fa-box-open"
                            title="No Packages Found"
                            message="Try adjusting your filters or create a new package."
                            :show-action="Object.keys(filters).length > 0"
                            action-text="Clear All Filters"
                            @action="clearAllFilters"
                        />

                        <!-- Table -->
                        <div v-else class="bg-white border rounded-lg shadow-sm overflow-hidden">
                             <div class="overflow-x-auto">
                                 <table class="w-full text-left text-sm text-gray-600">
                                    <thead class="bg-gray-50 border-b uppercase font-semibold text-xs text-gray-500">
                                        <tr>
                                            <th class="px-4 py-3">Suite</th>
                                            <th class="px-4 py-3">From</th>
                                            <th class="px-4 py-3">Package ID</th>

                                            <th class="px-4 py-3">Status</th>
                                            <th class="px-4 py-3">Request</th>
                                            <th class="px-4 py-3 text-center">Items</th>
                                            <th class="px-4 py-3 text-center">Invoices</th>
                                            <th class="px-4 py-3 text-center">Class.</th>
                                            <th class="px-4 py-3">Date</th>
                                            <th class="px-4 py-3 text-right">Value</th>
                                            <th class="px-4 py-3 text-right">Weight</th>
                                            <th class="px-4 py-3 text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr v-for="pkg in packages.data" :key="pkg.id" 
                                            @click="goToEdit(pkg)"
                                            class="hover:bg-blue-50 transition-colors cursor-pointer">
                                            
                                            <!-- Suite -->
                                            <td class="px-4 py-3">
                                                <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">
                                                    {{ pkg.customer?.suite || 'N/A' }}
                                                </span>
                                            </td>
                                            
                                            <!-- From -->
                                            <td class="px-4 py-3 text-gray-900">{{ pkg.from || '-' }}</td>
                                            
                                            <!-- Package ID -->
                                            <td class="px-4 py-3">
                                                <span class="font-mono font-medium text-gray-900">{{ pkg.package_id }}</span>
                                            </td>
                                            
                                            
                                            <!-- Status -->
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold whitespace-nowrap" 
                                                    :class="{
                                                        'bg-red-100 text-red-700': pkg.status === 1,
                                                        'bg-yellow-100 text-yellow-700': pkg.status === 2,
                                                        'bg-blue-100 text-blue-700': pkg.status === 3,
                                                        'bg-green-100 text-green-700': pkg.status === 4
                                                    }">
                                                    {{ pkg.status_name }}
                                                </span>
                                            </td>
                                            
                                            <!-- Special Request -->
                                            <td class="px-4 py-3">
                                                <span v-if="pkg.special_request" 
                                                    class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200"
                                                    :title="pkg.special_request.description">
                                                    <i class="fas fa-bell text-[10px]"></i>
                                                    {{ pkg.special_request.title }}
                                                </span>
                                                <span v-else class="text-gray-300">—</span>
                                            </td>
                                            
                                            <!-- Items Count -->
                                            <td class="px-4 py-3 text-center">
                                                <span v-if="pkg.items_count > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">
                                                    <i class="fas fa-box-open text-[10px]"></i>{{ pkg.items_count }}
                                                </span>
                                                <span v-else class="text-gray-300">—</span>
                                            </td>
                                            
                                            <!-- Invoices Count -->
                                            <td class="px-4 py-3 text-center">
                                                <span v-if="pkg.invoices_count > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-emerald-50 text-emerald-700">
                                                    <i class="fas fa-file-invoice-dollar text-[10px]"></i>{{ pkg.invoices_count }}
                                                </span>
                                                <span v-else class="text-gray-300">—</span>
                                            </td>
                                            
                                            <!-- Classification Badges -->
                                            <td class="px-4 py-3 text-center">
                                                <div class="flex items-center justify-center gap-1">
                                                    <span v-if="hasClassification(pkg).dangerous" class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center" title="Dangerous Goods">
                                                        <i class="fas fa-fire text-red-600 text-[10px]"></i>
                                                    </span>
                                                    <span v-if="hasClassification(pkg).fragile" class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center" title="Fragile">
                                                        <i class="fas fa-wine-glass text-amber-600 text-[10px]"></i>
                                                    </span>
                                                    <span v-if="hasClassification(pkg).oversized" class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center" title="Oversized">
                                                        <i class="fas fa-expand-arrows-alt text-blue-600 text-[10px]"></i>
                                                    </span>
                                                    <span v-if="!hasClassification(pkg).dangerous && !hasClassification(pkg).fragile && !hasClassification(pkg).oversized" class="text-gray-300">—</span>
                                                </div>
                                            </td>
                                            
                                            <!-- Date Received -->
                                            <td class="px-4 py-3 text-gray-500 text-xs">
                                                {{ formatDate(pkg.date_received) }}
                                            </td>
                                            
                                            <!-- Value -->
                                            <td class="px-4 py-3 text-right font-medium text-gray-900">
                                                {{ formatCurrency(pkg.total_value) }}
                                            </td>
                                            
                                            <!-- Weight -->
                                            <td class="px-4 py-3 text-right text-gray-500 text-xs">
                                                {{ formatWeight(pkg.total_weight, pkg.weight_unit) }}
                                            </td>
                                            
                                            <!-- Actions -->
                                            <td class="px-4 py-3" @click.stop>
                                                <div class="flex items-center justify-center gap-2">
                                                    <button 
                                                        @click="handleQuickView(pkg)"
                                                        class="text-gray-400 hover:text-blue-600 p-1"
                                                        title="Quick View">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <Link v-if="can('packages.update')" 
                                                        :href="route('admin.packages.edit', pkg.id)"
                                                        class="text-gray-400 hover:text-blue-600 p-1"
                                                        title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </Link>
                                                    <!-- Barcode Dropdown -->
                                                    <div v-if="pkg.package_id && can('packages.view')" class="relative barcode-dropdown-container">
                                                        <button 
                                                            @click="toggleBarcodeDropdown(pkg.id, $event)"
                                                            class="text-gray-400 hover:text-green-600 p-1 relative"
                                                            title="Barcode Options">
                                                            <i class="fas fa-barcode"></i>
                                                        </button>
                                                        <!-- Dropdown Menu -->
                                                        <div 
                                                            v-if="barcodeDropdownOpen[pkg.id]"
                                                            class="absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg border border-gray-200 z-50 py-1"
                                                            @click.stop
                                                        >
                                                            <a
                                                                :href="route('admin.packages.barcode.pdf', pkg.id)"
                                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer"
                                                                @click.prevent="closeBarcodeDropdown(pkg.id); openBarcodeFile(route('admin.packages.barcode.pdf', pkg.id))"
                                                            >
                                                                <i class="fas fa-file-pdf mr-2 text-red-500"></i>
                                                                Download PDF
                                                            </a>
                                                            <a
                                                                :href="route('admin.packages.barcode.zpl', pkg.id)"
                                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer"
                                                                @click.prevent="closeBarcodeDropdown(pkg.id); openBarcodeFile(route('admin.packages.barcode.zpl', pkg.id))"
                                                            >
                                                                <i class="fas fa-print mr-2 text-gray-500"></i>
                                                                Download ZPL
                                                            </a>
                                                            <a
                                                                :href="route('admin.packages.barcode.view', pkg.id)"
                                                                @click.prevent="closeBarcodeDropdown(pkg.id); openBarcodeFile(route('admin.packages.barcode.view', pkg.id))"
                                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 cursor-pointer"
                                                            >
                                                                <i class="fas fa-eye mr-2 text-blue-500"></i>
                                                                View PDF
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <button 
                                                        v-if="can('packages.delete')" 
                                                        @click="handleDeleteClick(pkg, $event)"
                                                        class="text-gray-400 hover:text-red-600 p-1"
                                                        title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <span v-if="!can('packages.update') && !can('packages.delete') && (!pkg.package_id || !can('packages.view'))" class="text-gray-300">—</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                             </div>
                        </div>
                        
                        <!-- Pagination -->
                        <div v-if="packages.data.length > 0" class="mt-6 bg-white border rounded-lg p-4">
                            <Pagination 
                                :links="packages.links" 
                                :from="packages.from" 
                                :to="packages.to" 
                                :total="packages.total" 
                            />
                        </div>
                    </div>
                </transition>

                <transition name="fade">
                    <div v-if="viewMode === 'board'">
                        <KanbanBoard 
                            :packages="packages.data" 
                            :can-update="can('packages.kanban.update')" 
                            @quick-view="handleQuickView"
                            @add-note="handleAddNote"
                        />
                    </div>
                </transition>

            </div>
        </div>

        <QuickViewModal 
            :show="showQuickView" 
            :package-data="selectedPackage" 
            @close="closeQuickView" 
        />

        <PackageNoteModal
            :show="showNoteModal"
            :package-data="selectedPackage"
            @close="closeNoteModal"
        />

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="closeDeleteModal">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900">
                    Delete Package?
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Are you sure you want to delete package <strong class="font-mono">{{ selectedPackage?.package_id }}</strong>? This action cannot be undone.
                </p>
                <div class="flex justify-end gap-3 mt-6">
                    <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
                    <DangerButton @click="confirmDelete" :disabled="isDeleting">
                        <i v-if="isDeleting" class="fas fa-spinner fa-spin mr-2"></i>
                        Delete
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
