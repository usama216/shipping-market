<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";
import CameraCapture from "@/Components/CameraCapture.vue";
import HSCodeLookup from "@/Components/Package/HSCodeLookup.vue";
import { ref } from "vue";

const props = defineProps({
    form: Object,
});

const activeCameraItemIndex = ref(null);
const showCamera = ref(false);

// Lightbox state
const lightboxOpen = ref(false);
const lightboxImage = ref(null);
const lightboxIndex = ref(0);
const lightboxItemIndex = ref(0);

// Invoice lightbox state
const invoiceLightboxOpen = ref(false);
const invoiceLightboxImage = ref(null);
const invoiceLightboxIndex = ref(0);
const invoiceLightboxItemIndex = ref(0);
const viewingExistingInvoice = ref(false);

// Collapsed state for items (track which items are collapsed)
const collapsedItems = ref({});

// HS Code Lookup modal state
const showHSCodeLookup = ref(false);
const hsCodeLookupItemIndex = ref(null);

const openHSCodeLookup = (itemIndex) => {
    hsCodeLookupItemIndex.value = itemIndex;
    showHSCodeLookup.value = true;
};

const closeHSCodeLookup = () => {
    showHSCodeLookup.value = false;
    hsCodeLookupItemIndex.value = null;
};

const handleHSCodeSelect = (result) => {
    if (hsCodeLookupItemIndex.value !== null) {
        const item = props.form.items[hsCodeLookupItemIndex.value];
        item.hs_code = result.hs_code;
        // Optionally update description if it's empty
        if (!item.description && result.description) {
            item.description = result.description;
        }
    }
    closeHSCodeLookup();
};

const toggleCollapse = (index) => {
    collapsedItems.value[index] = !collapsedItems.value[index];
};

const isCollapsed = (index) => {
    return collapsedItems.value[index] === true;
};

const addItem = () => {
    // Collapse all existing items
    props.form.items.forEach((_, idx) => {
        collapsedItems.value[idx] = true;
    });
    
    // Add new item (will be expanded)
    const newIndex = props.form.items.length;
    collapsedItems.value[newIndex] = false;
    
    props.form.items.push({
        title: "",
        description: "",
            hs_code: "",
            material: "",
        quantity: 1,
        value_per_unit: 0,
        weight_per_unit: 0,
        weight_unit: "lb",
        total_line_value: 0,
        total_line_weight: 0,
        length: null,
        width: null,
        height: null,
        dimension_unit: "in",
        is_dangerous: false,
        un_code: "",
        is_fragile: false,
        is_oversized: false,
        classification_notes: "",
        files: [],
        preview: [],
        invoice_files: [],
        invoice_previews: [],
        skip_action_required: false,
    });
};

const removeItem = (index) => {
    props.form.items.splice(index, 1);
};

const handleFileChange = (e, index) => {
    const files = Array.from(e.target.files);
    files.forEach((file) => {
        props.form.items[index].files.push(file);
        props.form.items[index].preview.push(URL.createObjectURL(file));
    });
};

const handleInvoiceFileChange = (e, index) => {
    const files = Array.from(e.target.files);
    if (!props.form.items[index].invoice_files) {
        props.form.items[index].invoice_files = [];
    }
    if (!props.form.items[index].invoice_previews) {
        props.form.items[index].invoice_previews = [];
    }
    files.forEach((file) => {
        const isPdf = file.type === 'application/pdf';
        props.form.items[index].invoice_files.push(file);
        props.form.items[index].invoice_previews.push({
            url: URL.createObjectURL(file),
            type: isPdf ? 'pdf' : 'image',
            name: file.name
        });
    });
};

const removeImage = (itemIndex, imgIndex) => {
    props.form.items[itemIndex].preview.splice(imgIndex, 1);
    props.form.items[itemIndex].files.splice(imgIndex, 1);
};

const removeInvoiceFile = (itemIndex, fileIndex) => {
    props.form.items[itemIndex].invoice_files.splice(fileIndex, 1);
    props.form.items[itemIndex].invoice_previews.splice(fileIndex, 1);
};

const markExistingInvoiceForDeletion = (itemIndex, fileId) => {
    if (!props.form.items[itemIndex].delete_invoice_file_ids) {
        props.form.items[itemIndex].delete_invoice_file_ids = [];
    }
    props.form.items[itemIndex].delete_invoice_file_ids.push(fileId);
};

const openCamera = (index) => {
    activeCameraItemIndex.value = index;
    showCamera.value = true;
};

const addCameraPhoto = (file) => {
    if (activeCameraItemIndex.value !== null) {
        props.form.items[activeCameraItemIndex.value].files.push(file);
        props.form.items[activeCameraItemIndex.value].preview.push(URL.createObjectURL(file));
        showCamera.value = false;
        activeCameraItemIndex.value = null;
    }
};

// Track if viewing existing file (for proper URL handling)
const isViewingExistingFile = ref(false);

// Get all viewable images for an item (existing + new previews)
const getAllImages = (itemIdx) => {
    const item = props.form.items[itemIdx];
    const existingFiles = (item.package_files || []).filter(f => !item.delete_file_ids?.includes(f.id));
    const existingUrls = existingFiles.map(f => ({ url: `/storage/${f.file}`, isExisting: true }));
    const previewUrls = (item.preview || []).map(p => ({ url: p, isExisting: false }));
    return [...existingUrls, ...previewUrls];
};

// Lightbox functions
const openLightbox = (itemIdx, imgIdx) => {
    lightboxItemIndex.value = itemIdx;
    lightboxIndex.value = imgIdx;
    lightboxImage.value = props.form.items[itemIdx].preview[imgIdx];
    isViewingExistingFile.value = false;
    lightboxOpen.value = true;
};

// Open lightbox for existing files
const openExistingLightbox = (itemIdx, fileIdx) => {
    const item = props.form.items[itemIdx];
    const existingFiles = (item.package_files || []).filter(f => !item.delete_file_ids?.includes(f.id));
    lightboxItemIndex.value = itemIdx;
    lightboxIndex.value = fileIdx;
    lightboxImage.value = `/storage/${existingFiles[fileIdx].file}`;
    isViewingExistingFile.value = true;
    lightboxOpen.value = true;
};

const closeLightbox = () => {
    lightboxOpen.value = false;
    lightboxImage.value = null;
    isViewingExistingFile.value = false;
};

const prevImage = () => {
    const allImages = getAllImages(lightboxItemIndex.value);
    // Calculate current absolute index
    const item = props.form.items[lightboxItemIndex.value];
    const existingFiles = (item.package_files || []).filter(f => !item.delete_file_ids?.includes(f.id));
    const absIndex = isViewingExistingFile.value ? lightboxIndex.value : existingFiles.length + lightboxIndex.value;
    
    if (absIndex > 0) {
        const newAbsIndex = absIndex - 1;
        if (newAbsIndex < existingFiles.length) {
            lightboxIndex.value = newAbsIndex;
            lightboxImage.value = `/storage/${existingFiles[newAbsIndex].file}`;
            isViewingExistingFile.value = true;
        } else {
            lightboxIndex.value = newAbsIndex - existingFiles.length;
            lightboxImage.value = item.preview[lightboxIndex.value];
            isViewingExistingFile.value = false;
        }
    }
};

const nextImage = () => {
    const allImages = getAllImages(lightboxItemIndex.value);
    const item = props.form.items[lightboxItemIndex.value];
    const existingFiles = (item.package_files || []).filter(f => !item.delete_file_ids?.includes(f.id));
    const absIndex = isViewingExistingFile.value ? lightboxIndex.value : existingFiles.length + lightboxIndex.value;
    
    if (absIndex < allImages.length - 1) {
        const newAbsIndex = absIndex + 1;
        if (newAbsIndex < existingFiles.length) {
            lightboxIndex.value = newAbsIndex;
            lightboxImage.value = `/storage/${existingFiles[newAbsIndex].file}`;
            isViewingExistingFile.value = true;
        } else {
            lightboxIndex.value = newAbsIndex - existingFiles.length;
            lightboxImage.value = item.preview[lightboxIndex.value];
            isViewingExistingFile.value = false;
        }
    }
};

// Get total image count for lightbox counter
const getTotalImageCount = (itemIdx) => {
    return getAllImages(itemIdx).length;
};

// Get current absolute index for lightbox counter
const getCurrentAbsIndex = () => {
    const item = props.form.items[lightboxItemIndex.value];
    const existingFiles = (item.package_files || []).filter(f => !item.delete_file_ids?.includes(f.id));
    return isViewingExistingFile.value ? lightboxIndex.value : existingFiles.length + lightboxIndex.value;
};

// ========== INVOICE LIGHTBOX FUNCTIONS ==========

// Get all viewable invoice images for an item (existing + new, excluding PDFs)
const getAllInvoiceImages = (itemIdx) => {
    const item = props.form.items[itemIdx];
    const existingFiles = (item.invoice_files_existing || []).filter(f => 
        !item.delete_invoice_file_ids?.includes(f.id) && f.file_type !== 'pdf'
    );
    const existingUrls = existingFiles.map(f => ({
        url: f.file_with_url || `/storage/${f.file}`,
        isExisting: true
    }));
    const newFiles = (item.invoice_previews || []).filter(f => f.type !== 'pdf');
    const previewUrls = newFiles.map(p => ({ url: p.url, isExisting: false }));
    return [...existingUrls, ...previewUrls];
};

// Open invoice lightbox for existing files
const openExistingInvoiceLightbox = (itemIdx, fileIdx) => {
    const item = props.form.items[itemIdx];
    const existingFiles = (item.invoice_files_existing || []).filter(f => 
        !item.delete_invoice_file_ids?.includes(f.id) && f.file_type !== 'pdf'
    );
    if (existingFiles[fileIdx]) {
        invoiceLightboxItemIndex.value = itemIdx;
        invoiceLightboxIndex.value = fileIdx;
        invoiceLightboxImage.value = existingFiles[fileIdx].file_with_url || `/storage/${existingFiles[fileIdx].file}`;
        viewingExistingInvoice.value = true;
        invoiceLightboxOpen.value = true;
    }
};

// Open invoice lightbox for new preview files
const openNewInvoiceLightbox = (itemIdx, previewIdx) => {
    const item = props.form.items[itemIdx];
    const existingCount = (item.invoice_files_existing || []).filter(f => 
        !item.delete_invoice_file_ids?.includes(f.id) && f.file_type !== 'pdf'
    ).length;
    const previews = (item.invoice_previews || []).filter(f => f.type !== 'pdf');
    if (previews[previewIdx]) {
        invoiceLightboxItemIndex.value = itemIdx;
        invoiceLightboxIndex.value = previewIdx;
        invoiceLightboxImage.value = previews[previewIdx].url;
        viewingExistingInvoice.value = false;
        invoiceLightboxOpen.value = true;
    }
};

const closeInvoiceLightbox = () => {
    invoiceLightboxOpen.value = false;
    invoiceLightboxImage.value = null;
    viewingExistingInvoice.value = false;
};

const prevInvoiceImage = () => {
    const allImages = getAllInvoiceImages(invoiceLightboxItemIndex.value);
    const item = props.form.items[invoiceLightboxItemIndex.value];
    const existingFiles = (item.invoice_files_existing || []).filter(f => 
        !item.delete_invoice_file_ids?.includes(f.id) && f.file_type !== 'pdf'
    );
    const absIndex = viewingExistingInvoice.value ? invoiceLightboxIndex.value : existingFiles.length + invoiceLightboxIndex.value;
    
    if (absIndex > 0) {
        const newAbsIndex = absIndex - 1;
        if (newAbsIndex < existingFiles.length) {
            invoiceLightboxIndex.value = newAbsIndex;
            invoiceLightboxImage.value = existingFiles[newAbsIndex].file_with_url || `/storage/${existingFiles[newAbsIndex].file}`;
            viewingExistingInvoice.value = true;
        } else {
            invoiceLightboxIndex.value = newAbsIndex - existingFiles.length;
            const previews = (item.invoice_previews || []).filter(f => f.type !== 'pdf');
            invoiceLightboxImage.value = previews[invoiceLightboxIndex.value].url;
            viewingExistingInvoice.value = false;
        }
    }
};

const nextInvoiceImage = () => {
    const allImages = getAllInvoiceImages(invoiceLightboxItemIndex.value);
    const item = props.form.items[invoiceLightboxItemIndex.value];
    const existingFiles = (item.invoice_files_existing || []).filter(f => 
        !item.delete_invoice_file_ids?.includes(f.id) && f.file_type !== 'pdf'
    );
    const absIndex = viewingExistingInvoice.value ? invoiceLightboxIndex.value : existingFiles.length + invoiceLightboxIndex.value;
    
    if (absIndex < allImages.length - 1) {
        const newAbsIndex = absIndex + 1;
        if (newAbsIndex < existingFiles.length) {
            invoiceLightboxIndex.value = newAbsIndex;
            invoiceLightboxImage.value = existingFiles[newAbsIndex].file_with_url || `/storage/${existingFiles[newAbsIndex].file}`;
            viewingExistingInvoice.value = true;
        } else {
            invoiceLightboxIndex.value = newAbsIndex - existingFiles.length;
            const previews = (item.invoice_previews || []).filter(f => f.type !== 'pdf');
            invoiceLightboxImage.value = previews[invoiceLightboxIndex.value].url;
            viewingExistingInvoice.value = false;
        }
    }
};

// Get total invoice image count for lightbox counter
const getTotalInvoiceImageCount = (itemIdx) => {
    return getAllInvoiceImages(itemIdx).length;
};

// Get current absolute index for invoice lightbox counter
const getCurrentInvoiceAbsIndex = () => {
    const item = props.form.items[invoiceLightboxItemIndex.value];
    const existingFiles = (item.invoice_files_existing || []).filter(f => 
        !item.delete_invoice_file_ids?.includes(f.id) && f.file_type !== 'pdf'
    );
    return viewingExistingInvoice.value ? invoiceLightboxIndex.value : existingFiles.length + invoiceLightboxIndex.value;
};
</script>

<template>
    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl relative">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-10 h-10 text-orange-600 bg-orange-50 rounded-xl">
                    <i class="fa-solid fa-layer-group text-lg"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-800">Items Content</h2>
                    <p class="text-sm text-gray-500">Declare contents for customs</p>
                </div>
            </div>
            <PrimaryButton
                type="button"
                @click="addItem"
                class="bg-gray-900 hover:bg-black text-white px-4 py-2 rounded-lg flex items-center gap-2 shadow-lg shadow-gray-200 transition-all active:scale-95"
            >
                <i class="fa-solid fa-plus text-xs"></i> Add Item
            </PrimaryButton>
        </div>

        <!-- Camera Modal -->
        <CameraCapture 
            v-if="showCamera" 
            @photo-captured="addCameraPhoto" 
            @close="showCamera = false" 
        />

        <!-- Lightbox Modal -->
        <Teleport to="body">
            <div 
                v-if="lightboxOpen" 
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/90"
                @click.self="closeLightbox"
            >
                <!-- Close Button -->
                <button 
                    @click="closeLightbox"
                    class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors"
                >
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>

                <!-- Navigation Arrows -->
                <button 
                    v-if="getCurrentAbsIndex() > 0"
                    @click="prevImage"
                    class="absolute left-4 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors"
                >
                    <i class="fa-solid fa-chevron-left text-xl"></i>
                </button>
                <button 
                    v-if="getCurrentAbsIndex() < getTotalImageCount(lightboxItemIndex) - 1"
                    @click="nextImage"
                    class="absolute right-4 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors"
                >
                    <i class="fa-solid fa-chevron-right text-xl"></i>
                </button>

                <!-- Image -->
                <img 
                    :src="lightboxImage" 
                    class="max-w-[90vw] max-h-[85vh] object-contain rounded-lg shadow-2xl"
                />

                <!-- Counter -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 px-4 py-2 rounded-full bg-white/10 text-white text-sm">
                    {{ getCurrentAbsIndex() + 1 }} / {{ getTotalImageCount(lightboxItemIndex) }}
                </div>
            </div>
        </Teleport>

        <!-- Invoice Lightbox Modal -->
        <Teleport to="body">
            <div 
                v-if="invoiceLightboxOpen" 
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/90"
                @click.self="closeInvoiceLightbox"
            >
                <!-- Close Button -->
                <button 
                    @click="closeInvoiceLightbox"
                    class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors"
                >
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>

                <!-- Navigation Arrows -->
                <button 
                    v-if="getCurrentInvoiceAbsIndex() > 0"
                    @click="prevInvoiceImage"
                    class="absolute left-4 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors"
                >
                    <i class="fa-solid fa-chevron-left text-xl"></i>
                </button>
                <button 
                    v-if="getCurrentInvoiceAbsIndex() < getTotalInvoiceImageCount(invoiceLightboxItemIndex) - 1"
                    @click="nextInvoiceImage"
                    class="absolute right-4 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors"
                >
                    <i class="fa-solid fa-chevron-right text-xl"></i>
                </button>

                <!-- Image -->
                <img 
                    :src="invoiceLightboxImage" 
                    class="max-w-[90vw] max-h-[85vh] object-contain rounded-lg shadow-2xl"
                />

                <!-- Counter -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 px-4 py-2 rounded-full bg-white/10 text-white text-sm">
                    <span class="mr-2"><i class="fa-solid fa-file-invoice-dollar"></i></span>
                    {{ getCurrentInvoiceAbsIndex() + 1 }} / {{ getTotalInvoiceImageCount(invoiceLightboxItemIndex) }}
                </div>
            </div>
        </Teleport>

        <div class="space-y-6">
            <transition-group 
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="transform -translate-x-4 opacity-0"
                enter-to-class="transform translate-x-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="transform translate-x-0 opacity-100"
                leave-to-class="transform -translate-x-4 opacity-0"
            >
                <div
                    v-for="(item, index) in form.items"
                    :key="index"
                    class="relative border border-gray-200 rounded-xl bg-gray-50 hover:border-gray-300 transition-all group overflow-hidden"
                >
                    <!-- Collapsible Header -->
                    <div 
                        @click="toggleCollapse(index)"
                        class="flex items-center justify-between p-4 cursor-pointer select-none hover:bg-gray-100/50 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <!-- Collapse Icon -->
                            <div class="w-6 h-6 rounded-full bg-white border border-gray-200 flex items-center justify-center text-gray-400 transition-transform duration-200"
                                :class="{ 'rotate-180': !isCollapsed(index) }"
                            >
                                <i class="fa-solid fa-chevron-down text-[10px]"></i>
                            </div>
                            <span class="text-sm font-bold text-gray-700">Item #{{ index + 1 }}</span>
                            <!-- Quick Summary when collapsed -->
                            <span v-if="isCollapsed(index) && item.title" class="text-sm text-gray-500 hidden sm:inline">
                                — {{ item.title }}
                                <span v-if="item.quantity > 0" class="text-gray-400">× {{ item.quantity }}</span>
                                <span v-if="item.value_per_unit > 0" class="text-green-600 font-medium ml-2">${{ (item.quantity * item.value_per_unit).toFixed(2) }}</span>
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Classification badges when collapsed -->
                            <div v-if="isCollapsed(index)" class="flex gap-1">
                                <span v-if="item.is_dangerous" class="w-5 h-5 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-[10px]" title="Dangerous">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                </span>
                                <span v-if="item.is_fragile" class="w-5 h-5 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center text-[10px]" title="Fragile">
                                    <i class="fa-solid fa-wine-glass"></i>
                                </span>
                                <span v-if="item.is_oversized" class="w-5 h-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-[10px]" title="Oversized">
                                    <i class="fa-solid fa-box-open"></i>
                                </span>
                                <span v-if="item.preview?.length > 0" class="w-5 h-5 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-[10px]" title="Has photos">
                                    <i class="fa-solid fa-image"></i>
                                </span>
                                <span v-if="(item.invoice_previews?.length > 0) || (item.invoice_files_existing?.length > 0)" class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-[10px]" title="Has invoices">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                </span>
                            </div>
                            <button
                                v-if="form.items.length > 1"
                                @click.stop="removeItem(index)"
                                type="button"
                                class="text-gray-400 hover:text-red-500 transition-colors p-1"
                                title="Remove Item"
                            >
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Collapsible Content -->
                    <div 
                        v-show="!isCollapsed(index)"
                        class="p-5 pt-4 border-t border-gray-200"
                    >
                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">
                        <!-- Main Info -->
                        <div class="lg:col-span-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <InputLabel value="Item Title" />
                                <TextInput v-model="item.title" class="w-full mt-1" placeholder="e.g. Nike Sneakers" />
                                <InputError :message="form.errors[`items.${index}.title`]" class="mt-1" />
                            </div>
                            <div class="col-span-2">
                                <InputLabel value="Description" />
                                <TextInput v-model="item.description" class="w-full mt-1" placeholder="Detailed description..." />
                                <InputError :message="form.errors[`items.${index}.description`]" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="HS Code" />
                                <div class="flex gap-2 mt-1">
                                    <TextInput 
                                        v-model="item.hs_code" 
                                        class="flex-1" 
                                        placeholder="e.g. 8471.30.01" 
                                    />
                                    <button
                                        type="button"
                                        @click="openHSCodeLookup(index)"
                                        class="px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors flex items-center gap-2 whitespace-nowrap"
                                        title="Lookup HS Code"
                                    >
                                        <i class="fa-solid fa-magnifying-glass"></i>
                                        <span class="hidden sm:inline">Lookup</span>
                                    </button>
                                </div>
                                <InputError :message="form.errors[`items.${index}.hs_code`]" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel value="Material" />
                                <TextInput v-model="item.material" class="w-full mt-1" placeholder="e.g. Leather" />
                                <InputError :message="form.errors[`items.${index}.material`]" class="mt-1" />
                            </div>
                        </div>

                        <!-- Quantities, Values & Weight -->
                        <div class="lg:col-span-4 bg-white p-4 rounded-lg border border-gray-200/60 shadow-sm">
                            <div class="space-y-3">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <InputLabel value="Quantity" class="mb-1 text-xs" />
                                        <TextInput v-model.number="item.quantity" type="number" min="1" class="w-full text-sm" />
                                        <InputError :message="form.errors[`items.${index}.quantity`]" class="mt-1" />
                                    </div>
                                    <div>
                                        <InputLabel value="Value/Unit ($)" class="mb-1 text-xs" />
                                        <div class="relative">
                                            <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs">$</span>
                                            <TextInput v-model.number="item.value_per_unit" type="number" step="0.01" class="w-full pl-5 text-sm" placeholder="0.00" />
                                        </div>
                                        <InputError :message="form.errors[`items.${index}.value_per_unit`]" class="mt-1" />
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <InputLabel value="Weight/Unit" class="text-xs" />
                                        <div class="inline-flex overflow-hidden border border-gray-200 rounded p-0.5 bg-gray-50">
                                            <button 
                                                type="button" 
                                                @click="item.weight_unit = 'lb'"
                                                :class="item.weight_unit === 'lb' 
                                                    ? 'bg-white text-gray-800 shadow-sm' 
                                                    : 'text-gray-400 hover:text-gray-600'"
                                                class="px-1.5 py-0.5 text-[9px] font-bold rounded transition-all"
                                            >LB</button>
                                            <button 
                                                type="button" 
                                                @click="item.weight_unit = 'kg'"
                                                :class="item.weight_unit === 'kg' 
                                                    ? 'bg-white text-gray-800 shadow-sm' 
                                                    : 'text-gray-400 hover:text-gray-600'"
                                                class="px-1.5 py-0.5 text-[9px] font-bold rounded transition-all"
                                            >KG</button>
                                        </div>
                                    </div>
                                    <TextInput v-model.number="item.weight_per_unit" type="number" step="0.01" min="0" class="w-full text-sm" placeholder="0.00" />
                                    <InputError :message="form.errors[`items.${index}.weight_per_unit`]" class="mt-1" />
                                </div>

                                <div class="pt-3 border-t border-gray-100 space-y-2">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-500 uppercase">Line Total</span>
                                        <span class="font-mono font-bold text-green-600">${{ (item.quantity * item.value_per_unit).toFixed(2) }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-500 uppercase">Physical Wt.</span>
                                        <span class="font-mono font-bold text-blue-600">{{ (item.quantity * (item.weight_per_unit || 0)).toFixed(2) }} {{ item.weight_unit || 'lb' }}</span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-500 uppercase">Volumetric Wt.</span>
                                        <span class="font-mono font-medium" :class="(item.length && item.width && item.height) ? 'text-purple-600' : 'text-gray-400'">
                                            {{ (item.length && item.width && item.height) 
                                                ? ((item.length * item.width * item.height / (item.dimension_unit === 'cm' ? 5000 : 139)) * item.quantity).toFixed(2) + ' lb'
                                                : '—' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Item Dimensions -->
                        <div class="col-span-full">
                            <div class="p-4 rounded-lg bg-white border border-gray-200/60">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide flex items-center gap-2">
                                        <i class="fa-solid fa-ruler text-gray-400"></i> Item Dimensions
                                    </span>
                                    <div class="inline-flex overflow-hidden border border-gray-200 rounded-md p-0.5 bg-gray-50">
                                        <button 
                                            type="button" 
                                            @click="item.dimension_unit = 'in'"
                                            :class="item.dimension_unit === 'in' 
                                                ? 'bg-white text-gray-800 shadow-sm' 
                                                : 'text-gray-400 hover:text-gray-600'"
                                            class="px-2 py-0.5 text-[10px] font-bold rounded transition-all"
                                        >IN</button>
                                        <button 
                                            type="button" 
                                            @click="item.dimension_unit = 'cm'"
                                            :class="item.dimension_unit === 'cm' 
                                                ? 'bg-white text-gray-800 shadow-sm' 
                                                : 'text-gray-400 hover:text-gray-600'"
                                            class="px-2 py-0.5 text-[10px] font-bold rounded transition-all"
                                        >CM</button>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <InputLabel :value="`L (${item.dimension_unit})`" class="text-[10px] uppercase text-gray-400 mb-1" />
                                        <TextInput v-model.number="item.length" type="number" step="0.01" min="0" class="w-full text-sm text-center" placeholder="0" />
                                    </div>
                                    <div>
                                        <InputLabel :value="`W (${item.dimension_unit})`" class="text-[10px] uppercase text-gray-400 mb-1" />
                                        <TextInput v-model.number="item.width" type="number" step="0.01" min="0" class="w-full text-sm text-center" placeholder="0" />
                                    </div>
                                    <div>
                                        <InputLabel :value="`H (${item.dimension_unit})`" class="text-[10px] uppercase text-gray-400 mb-1" />
                                        <TextInput v-model.number="item.height" type="number" step="0.01" min="0" class="w-full text-sm text-center" placeholder="0" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Classifications -->
                        <div class="col-span-full">
                            <div class="flex flex-wrap gap-3">
                                <label class="cursor-pointer select-none">
                                    <input type="checkbox" v-model="item.is_dangerous" class="peer sr-only" />
                                    <div class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-600 bg-white hover:bg-gray-50 peer-checked:bg-red-50 peer-checked:text-red-700 peer-checked:border-red-200 transition-all flex items-center gap-2">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Dangerous
                                    </div>
                                </label>
                                <label class="cursor-pointer select-none">
                                    <input type="checkbox" v-model="item.is_fragile" class="peer sr-only" />
                                    <div class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-600 bg-white hover:bg-gray-50 peer-checked:bg-yellow-50 peer-checked:text-yellow-700 peer-checked:border-yellow-200 transition-all flex items-center gap-2">
                                        <i class="fa-solid fa-wine-glass"></i> Fragile
                                    </div>
                                </label>
                                <label class="cursor-pointer select-none">
                                    <input type="checkbox" v-model="item.is_oversized" class="peer sr-only" />
                                    <div class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm text-gray-600 bg-white hover:bg-gray-50 peer-checked:bg-blue-50 peer-checked:text-blue-700 peer-checked:border-blue-200 transition-all flex items-center gap-2">
                                        <i class="fa-solid fa-box-open"></i> Oversized
                                    </div>
                                </label>
                            </div>
                            <!-- UN Code (shown when dangerous is checked) -->
                            <div v-if="item.is_dangerous" class="mt-3">
                                <InputLabel value="UN Code" class="text-xs mb-1" />
                                <TextInput 
                                    v-model="item.un_code" 
                                    class="w-full text-sm" 
                                    placeholder="e.g., UN1202, UN1263"
                                    maxlength="10"
                                />
                                <p class="text-xs text-gray-500 mt-1">
                                    United Nations number for dangerous goods classification
                                </p>
                            </div>
                            <div v-if="item.is_dangerous || item.is_fragile || item.is_oversized" class="mt-3">
                                <TextInput v-model="item.classification_notes" class="w-full text-sm" placeholder="Add classification notes..." />
                                <InputError :message="form.errors[`items.${index}.classification_notes`]" class="mt-1" />
                            </div>
                        </div>

                        <!-- Photos -->
                        <div class="col-span-full">
                             <InputLabel value="Item Photos" class="mb-2" />
                             <div class="flex flex-wrap gap-3">
                                <!-- Upload Button -->
                                <div class="relative w-20 h-20 rounded-lg border-2 border-dashed border-gray-300 flex flex-col items-center justify-center text-gray-400 hover:text-gray-600 hover:border-gray-400 transition-colors cursor-pointer bg-white">
                                    <i class="fa-solid fa-arrow-up-from-bracket mb-1"></i>
                                    <span class="text-[10px]">Upload</span>
                                    <input type="file" multiple accept=".bmp,.jpg,.jpeg,.gif,.png,.tif,.tiff,.webp,image/*" class="absolute inset-0 opacity-0 cursor-pointer" @change="(e) => handleFileChange(e, index)" />
                                </div>
                                
                                <!-- Camera Button -->
                                <button type="button" @click="openCamera(index)" class="relative w-20 h-20 rounded-lg border-2 border-dashed border-gray-300 flex flex-col items-center justify-center text-gray-400 hover:text-blue-600 hover:border-blue-400 transition-colors cursor-pointer bg-white">
                                    <i class="fa-solid fa-camera mb-1"></i>
                                    <span class="text-[10px]">Camera</span>
                                </button>

                                <!-- Existing Files (Edit Mode) -->
                                <template v-if="item.package_files && item.package_files.length > 0">
                                    <div 
                                        v-for="(file, fileIndex) in item.package_files.filter(f => !item.delete_file_ids?.includes(f.id))" 
                                        :key="'existing-' + file.id" 
                                        class="relative group w-20 h-20"
                                    >
                                        <img 
                                            :src="`/storage/${file.file}`" 
                                            class="w-full h-full object-cover rounded-lg border-2 border-green-200 cursor-pointer hover:opacity-90 transition-opacity" 
                                            :alt="file.name"
                                            @click="openExistingLightbox(index, fileIndex)"
                                        />
                                        <!-- Enlarge icon overlay -->
                                        <div 
                                            class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer pointer-events-none"
                                        >
                                            <i class="fa-solid fa-expand text-white text-lg"></i>
                                        </div>
                                        <!-- Existing file indicator -->
                                        <div class="absolute -top-1 -left-1 bg-green-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-[8px]">
                                            <i class="fa-solid fa-check"></i>
                                        </div>
                                        <!-- Remove button -->
                                        <button 
                                            @click.stop="item.delete_file_ids = [...(item.delete_file_ids || []), file.id]" 
                                            type="button" 
                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-opacity text-xs hover:bg-red-600"
                                        >✕</button>
                                    </div>
                                </template>
                                
                                <!-- New Image Previews -->
                                <div v-for="(img, imgIndex) in item.preview" :key="imgIndex" class="relative group w-20 h-20">
                                    <img 
                                        :src="img" 
                                        class="w-full h-full object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-90 transition-opacity" 
                                        @click="openLightbox(index, imgIndex)"
                                    />
                                    <!-- Enlarge icon overlay -->
                                    <div 
                                        class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer pointer-events-none"
                                    >
                                        <i class="fa-solid fa-expand text-white text-lg"></i>
                                    </div>
                                    <!-- Remove button -->
                                    <button 
                                        @click.stop="removeImage(index, imgIndex)" 
                                        type="button" 
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-opacity text-xs hover:bg-red-600"
                                    >✕</button>
                                </div>
                             </div>
                        </div>

                        <!-- Invoice Files -->
                        <div class="col-span-full">
                             <InputLabel value="Invoice / Receipt Files" class="mb-2">
                                 <span>Invoice / Receipt Files</span>
                                 <!-- Show checkmark if admin has uploaded invoices -->
                                 <span v-if="item.invoice_files_existing && item.invoice_files_existing.length > 0" class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700">
                                     <i class="fas fa-check text-[10px]"></i>
                                     {{ item.invoice_files_existing.length }} uploaded
                                 </span>
                             </InputLabel>
                             <p class="text-xs text-gray-500 mb-3">Upload merchant invoices or receipts for customs declaration. Admin-uploaded invoices bypass customer requirements.</p>
                             <div class="flex flex-wrap gap-3">
                                <!-- Upload Button -->
                                <div class="relative w-20 h-20 rounded-lg border-2 border-dashed border-emerald-300 flex flex-col items-center justify-center text-emerald-400 hover:text-emerald-600 hover:border-emerald-400 transition-colors cursor-pointer bg-emerald-50/50">
                                    <i class="fa-solid fa-file-invoice-dollar mb-1"></i>
                                    <span class="text-[10px]">Upload</span>
                                    <input type="file" multiple accept=".bmp,.jpg,.jpeg,.gif,.png,.tif,.tiff,.webp,.pdf,image/*,application/pdf" class="absolute inset-0 opacity-0 cursor-pointer" @change="(e) => handleInvoiceFileChange(e, index)" />
                                </div>

                                <!-- Existing Invoice Files (Edit Mode) -->
                                <template v-if="item.invoice_files_existing && item.invoice_files_existing.length > 0">
                                    <div 
                                        v-for="(file, fileIndex) in item.invoice_files_existing.filter(f => !item.delete_invoice_file_ids?.includes(f.id))" 
                                        :key="'existing-inv-' + file.id" 
                                        class="relative group w-20 h-20"
                                    >
                                        <!-- PDF indicator or Image preview -->
                                        <template v-if="file.file_type === 'pdf'">
                                            <a 
                                                :href="file.file_with_url || `/storage/${file.file}`"
                                                target="_blank"
                                                class="w-full h-full rounded-lg border-2 border-emerald-200 bg-emerald-50 flex flex-col items-center justify-center cursor-pointer hover:bg-emerald-100 transition-colors"
                                                title="Click to open PDF"
                                            >
                                                <i class="fa-solid fa-file-pdf text-2xl text-red-500"></i>
                                                <span class="text-[8px] text-gray-500 mt-1 max-w-full px-1 truncate">{{ file.name }}</span>
                                            </a>
                                        </template>
                                        <template v-else>
                                            <img 
                                                :src="file.file_with_url || `/storage/${file.file}`" 
                                                class="w-full h-full object-cover rounded-lg border-2 border-emerald-200 cursor-pointer hover:opacity-90 transition-opacity" 
                                                :alt="file.name"
                                                @click="openExistingInvoiceLightbox(index, item.invoice_files_existing.filter(f => !item.delete_invoice_file_ids?.includes(f.id) && f.file_type !== 'pdf').indexOf(file))"
                                            />
                                            <!-- Enlarge icon overlay -->
                                            <div 
                                                class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer pointer-events-none"
                                            >
                                                <i class="fa-solid fa-expand text-white text-lg"></i>
                                            </div>
                                        </template>
                                        <!-- Existing file indicator -->
                                        <div class="absolute -top-1 -left-1 bg-emerald-500 text-white rounded-full w-4 h-4 flex items-center justify-center text-[8px]">
                                            <i class="fa-solid fa-check"></i>
                                        </div>
                                        <!-- Remove button -->
                                        <button 
                                            @click.stop="markExistingInvoiceForDeletion(index, file.id)" 
                                            type="button" 
                                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-opacity text-xs hover:bg-red-600"
                                        >✕</button>
                                    </div>
                                </template>
                                
                                <!-- New Invoice File Previews -->
                                <div v-for="(file, fileIndex) in (item.invoice_previews || [])" :key="'new-inv-' + fileIndex" class="relative group w-20 h-20">
                                    <!-- PDF indicator or Image preview -->
                                    <template v-if="file.type === 'pdf'">
                                        <a 
                                            :href="file.url"
                                            target="_blank"
                                            class="w-full h-full rounded-lg border border-gray-200 bg-gray-50 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-100 transition-colors"
                                            title="Click to open PDF"
                                        >
                                            <i class="fa-solid fa-file-pdf text-2xl text-red-500"></i>
                                            <span class="text-[8px] text-gray-500 mt-1 max-w-full px-1 truncate">{{ file.name }}</span>
                                        </a>
                                    </template>
                                    <template v-else>
                                        <img 
                                            :src="file.url" 
                                            class="w-full h-full object-cover rounded-lg border border-gray-200 cursor-pointer hover:opacity-90 transition-opacity" 
                                            @click="openNewInvoiceLightbox(index, (item.invoice_previews || []).filter(f => f.type !== 'pdf').indexOf(file))"
                                        />
                                        <!-- Enlarge icon overlay -->
                                        <div 
                                            class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer pointer-events-none"
                                        >
                                            <i class="fa-solid fa-expand text-white text-lg"></i>
                                        </div>
                                    </template>
                                    <!-- Remove button -->
                                    <button 
                                        @click.stop="removeInvoiceFile(index, fileIndex)" 
                                        type="button" 
                                        class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center shadow-sm opacity-0 group-hover:opacity-100 transition-opacity text-xs hover:bg-red-600"
                                    >✕</button>
                                </div>
                             </div> <!-- Close flex flex-wrap -->
                             
                             <!-- Skip Action Required Checkbox -->
                             <div class="mt-4 pt-4 border-t border-gray-100">
                                 <label class="flex items-center gap-3 cursor-pointer group">
                                     <input 
                                         type="checkbox" 
                                         v-model="item.skip_action_required" 
                                         class="w-5 h-5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                     />
                                     <div>
                                         <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">Skip Customer Action Required</span>
                                         <p class="text-xs text-gray-500">If checked, this item goes directly to "Ready to Send" instead of requiring customer action</p>
                                     </div>
                                 </label>
                             </div>
                        </div>
                        </div> <!-- Close grid -->
                    </div> <!-- Close collapsible content -->
                </div>
            </transition-group>
        </div>

        <!-- HS Code Lookup Modal -->
        <HSCodeLookup
            :show="showHSCodeLookup"
            @close="closeHSCodeLookup"
            @select="handleHSCodeSelect"
        />
    </div>
</template>
