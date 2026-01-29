<script setup>
import { ref } from "vue";
import Report from "../Report.vue";
import TextInput from "@/Components/TextInput.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import axios from "axios";
import { useToast } from "vue-toastification";
import { Head, router } from "@inertiajs/vue3";
import CurrencyDollarText from "@/Components/Packages/CurrencyDollarText.vue";
import PackageLinks from "@/Components/Packages/PackageLinks.vue";
import PhotoLightbox from "@/Components/PhotoLightbox.vue";

const props = defineProps({
    actions: Object,
    specialRequests: Object,
    packageCounts: Array,
});
const toast = useToast();
const actions = props.actions;

const expandedRows = ref(new Set());
const selectedService = ref(null);
const dropdownOpen = ref(false);
const isShowNote = ref(false);
const isShowUploadInvoiceModal = ref(false);
const isShowPhotosModal = ref(false);
const addNote = ref(null);
const files = ref([]);
const packageId = ref(null);
const selectedItemIds = ref([]); // Array of selected item IDs for invoice upload (can be multiple)
const currentPackageItems = ref([]); // Items of currently selected package for invoice upload
const isUploadingInvoice = ref(false);
const packagePhotos = ref([]);
const previews = ref([]);

// Lightbox state
const lightboxOpen = ref(false);
const lightboxImage = ref(null);
const lightboxIndex = ref(0);

// Invoice lightbox state
const invoiceLightboxOpen = ref(false);
const invoicePhotos = ref([]);

// Check if any item has classification flags
const hasClassification = (pkg) => {
    if (!pkg.items || pkg.items.length === 0) return { dangerous: false, fragile: false, oversized: false };
    return {
        dangerous: pkg.items.some(item => item.is_dangerous),
        fragile: pkg.items.some(item => item.is_fragile),
        oversized: pkg.items.some(item => item.is_oversized)
    };
};

const toggleRow = (id) => {
    if (expandedRows.value.has(id)) {
        expandedRows.value.delete(id);
    } else {
        expandedRows.value.add(id);
    }
};
const toggleAll = () => {
    if (expandedRows.value.size === actions.length) {
        expandedRows.value.clear();
    } else {
        expandedRows.value = new Set(actions.map((a) => a.id));
    }
};

const allExpanded = () => expandedRows.value.size === actions.length;

const toggleDropdown = () => {
    dropdownOpen.value = !dropdownOpen.value;
};

const selectService = (service, id) => {
    selectedService.value = service;
    try {
        const response = axios.post(
            route("customer.packageSetSpecialRequest"),
            {
                package_id: id,
                special_request: service.id,
            }
        );
        toast.success(
            response.message || "Special request added successfully."
        );
    } catch (error) {
        toast.error(error);
    } finally {
        dropdownOpen.value = false;
    }
};
const handleShowNote = () => {
    isShowNote.value = !isShowNote.value;
};
const closeModal = () => {
    isShowUploadInvoiceModal.value = false;
    isShowPhotosModal.value = false;
    files.value = [];
    packagePhotos.value = [];
    selectedItemIds.value = [];
    currentPackageItems.value = [];
    previews.value = [];
};

// Show upload invoice modal - stores package items for customer to select which items need invoice
const showUploadInvoiceModal = (pkg) => {
    packageId.value = pkg.id;
    // Always get fresh package data from props (in case invoices were just uploaded)
    // Find the package in actions to get the latest data - props.actions is reactive
    const actionsArray = Array.isArray(props.actions) ? props.actions : [];
    const freshPackage = actionsArray.find(a => a.id === pkg.id);
    
    if (freshPackage) {
        // Use fresh data from props - ensure invoice_files is properly mapped
        currentPackageItems.value = (freshPackage.items || []).map(item => ({
            ...item,
            // Map invoiceFiles relationship to invoice_files for consistency
            invoice_files: item.invoice_files || item.invoiceFiles || []
        }));
    } else {
        // Fallback to passed package if not found in props (shouldn't happen)
        currentPackageItems.value = (pkg.items || []).map(item => ({
            ...item,
            invoice_files: item.invoice_files || item.invoiceFiles || []
        }));
    }
    
    // Don't auto-select - let customer choose
    selectedItemIds.value = [];
    isShowUploadInvoiceModal.value = true;
};

const onFileChange = (e) => {
    const selectedFiles = Array.from(e.target.files);
    files.value = selectedFiles;
    previews.value = [];

    selectedFiles.forEach((file) => {
        if (file.type.startsWith("image/")) {
            const reader = new FileReader();
            reader.onload = (e) => {
                previews.value.push(e.target.result);
            };
            reader.readAsDataURL(file);
        } else {
            previews.value.push(null); // for non-images (like PDF)
        }
    });
};
const removeImage = (index) => {
    files.value.splice(index, 1);
    previews.value.splice(index, 1);
};
const upload = async () => {
    if (selectedItemIds.value.length === 0) {
        toast.error("Please select at least one item for the invoice");
        return;
    }
    if (files.value.length === 0) {
        toast.error("Please select at least one invoice file");
        return;
    }
    isUploadingInvoice.value = true;
    const formData = new FormData();
    files.value.forEach((file) => formData.append("invoices[]", file));
    formData.append("package_id", packageId.value);
    // Send array of item IDs
    selectedItemIds.value.forEach((itemId) => {
        formData.append("item_ids[]", itemId);
    });

    try {
        const response = await axios.post(
            route("customers.packageUploadInvoices"),
            formData,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            }
        );

        toast.success(
            response.data.message || "Invoices uploaded successfully"
        );
        
        // If package was auto-completed, redirect to In Review page
        if (response.data.auto_completed) {
            closeModal();
            router.visit(route("customer.suite.inReview"));
        } else {
            // Stay on Action Required page and refresh to show updated invoice status
            router.reload({ 
                only: ['actions', 'packageCounts'],
                preserveState: false,
                preserveScroll: true,
                onSuccess: () => {
                    closeModal();
                }
            });
        }
    } catch (error) {
        toast.error(
            error.response?.data?.message || "Failed to upload invoices"
        );
    } finally {
        isUploadingInvoice.value = false;
    }
};

const handleAddNote = async (e, id) => {
    e.preventDefault();
    try {
        const response = await axios.post(route("customer.packageAddNote"), {
            note: addNote.value,
            id: id,
        });
        isShowNote.value = false;
        toast.success(response.data.message);
    } catch (error) {
        toast.error(response.data.message);
    }
};
const showPackagePhotos = async (packageId) => {
    try {
        const response = await axios.get(
            route("customers.packageGetPhotos", { package_id: packageId })
        );
        packagePhotos.value = response.data.data || [];
        if (packagePhotos.value.length > 0) {
            lightboxIndex.value = 0;
            lightboxOpen.value = true;
        } else {
            toast.info("No photos available for this package");
        }
    } catch (error) {
        toast.error("Failed to fetch photos");
    }
};

const closeLightbox = () => {
    lightboxOpen.value = false;
    packagePhotos.value = [];
};

// Show package invoices in lightbox - now collects from item-level invoice_files
const showPackageInvoices = (pkg) => {
    const invoiceFiles = [];
    
    // Collect invoice files from all items
    if (pkg.items && pkg.items.length > 0) {
        pkg.items.forEach(item => {
            if (item.invoice_files && item.invoice_files.length > 0) {
                item.invoice_files.forEach(file => {
                    invoiceFiles.push({ 
                        file_with_url: file.file_with_url || `/storage/${file.file}`, 
                        type: file.file_type || 'image',
                        name: file.name,
                        itemTitle: item.title
                    });
                });
            }
        });
    }
    
    if (invoiceFiles.length > 0) {
        // Pass all invoice files (images and PDFs) to lightbox
        invoicePhotos.value = invoiceFiles;
        invoiceLightboxOpen.value = true;
    } else {
        toast.info("No invoices available for this package");
    }
};

const closeInvoiceLightbox = () => {
    invoiceLightboxOpen.value = false;
    invoicePhotos.value = [];
};

// Check if all items in a package have invoices
const allItemsHaveInvoices = (pkg) => {
    if (!pkg.items || pkg.items.length === 0) return false;
    return pkg.items.every(item => {
        const invoiceFiles = item.invoice_files || item.invoiceFiles || [];
        return invoiceFiles.length > 0;
    });
};

</script>

<template>
    <Head title="Action Required" />
    <Report
        :actionCount="props?.packageCounts.action_required"
        :inReviewCount="props?.packageCounts?.in_review"
        :readyToSendCount="props?.packageCounts?.ready_to_send"
        :allPackagesCount="props?.packageCounts?.all"
    >
        <div class="grid gap-4 md:grid-cols-12">
            <div class="col-span-9">
                <table class="w-full text-sm text-center border data-table">
                    <thead class="uppercase bg-gray-100">
                        <tr>
                            <th>
                                <i
                                    @click="toggleAll"
                                    class="cursor-pointer"
                                    :class="[
                                        'fa-solid',
                                        allExpanded()
                                            ? 'fa-angles-down'
                                            : 'fa-angles-right',
                                        'text-primary-500',
                                    ]"
                                ></i>
                            </th>
                            <th>From</th>
                            <th>Package ID</th>
                            <th>Date Received</th>
                            <th class="text-white bg-primary-500">
                                Action Required
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="action in actions" :key="action.id">
                            <tr class="text-center">
                                <td
                                    @click="toggleRow(action.id)"
                                    class="cursor-pointer"
                                >
                                    <i
                                        :class="[
                                            'fas',
                                            expandedRows.has(action.id)
                                                ? 'fa-chevron-down'
                                                : 'fa-chevron-right',
                                            'text-primary-500',
                                        ]"
                                    ></i>
                                </td>
                                <td>{{ action.from }}</td>
                                <td>{{ action.package_id }}</td>
                                <td>
                                    {{
                                        __format_date_time(action.date_received)
                                    }}
                                </td>
                                <td>
                                    <div>
                                        <button
                                            class="text-primary-500"
                                            @click="
                                                showUploadInvoiceModal(
                                                    action
                                                )
                                            "
                                        >
                                            Upload Merchant Invoice
                                        </button>
                                        <p>As required by Customs</p>
                                        <p v-if="allItemsHaveInvoices(action)" class="text-sm text-green-600 mt-1">
                                            <i class="fas fa-check-circle mr-1"></i>
                                            All invoices uploaded - will auto-complete
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            <transition name="fade">
                                <tr
                                    v-if="expandedRows.has(action.id)"
                                    class="bg-gray-50"
                                >
                                    <td colspan="5" class="px-5 text-left">
                                        <div>
                                            <strong
                                                >Upload Merchant Invoice</strong
                                            >
                                            <p class="text-sm text-gray-600">
                                                Please upload the merchant
                                                invoice for this package. When
                                                your invoice is successfully
                                                uploaded, your package will be
                                                placed In Review until it is
                                                verified by Marketsz
                                            </p>
                                            <hr />
                                        </div>
                                        <table class="w-full my-5">
                                            <thead>
                                                <th>
                                                    <div
                                                        class="flex items-center justify-between"
                                                    >
                                                        <div
                                                            class="flex flex-col items-start"
                                                        >
                                                            <p>
                                                                Package Details
                                                            </p>
                                                            <p>
                                                                To:
                                                                {{
                                                                    action
                                                                        ?.customer
                                                                        ?.name
                                                                }}
                                                            </p>
                                                        </div>

                                                        <div class="flex gap-2">
                                                            <button
                                                                class="text-white btn bg-primary-500"
                                                                @click="showPackagePhotos(action.id)"
                                                            >
                                                                Photo
                                                            </button>
                                                            <button
                                                                v-if="action.items && action.items.some(item => item.invoice_files && item.invoice_files.length > 0)"
                                                                class="text-white btn bg-blue-500 hover:bg-blue-600"
                                                                @click="showPackageInvoices(action)"
                                                            >
                                                                Invoice
                                                            </button>
                                                        </div>
                                                    </div>
                                                </th>
                                                <th>Qty</th>
                                                <th>Value Per Unit (USD)</th>
                                                <th>Total Line Value (USD)</th>
                                            </thead>
                                            <tbody>
                                                <template
                                                    v-for="item in action.items"
                                                    :key="item.id"
                                                >
                                                    <tr
                                                        class="border bg-[#e8e7e7]"
                                                    >
                                                        <td>
                                                            <div class="flex items-start justify-between">
                                                                <div>
                                                                    <p class="text-lg font-medium">
                                                                        {{ item?.title }}
                                                                    </p>
                                                                    <p class="text-gray-600 text-md">
                                                                        {{ item?.description }}
                                                                    </p>
                                                                    <p class="text-sm text-gray-500" v-if="item?.item_note">
                                                                        <i class="fas fa-sticky-note mr-1"></i>{{ item?.item_note }}
                                                                    </p>
                                                                    <!-- Item dimensions -->
                                                                    <p v-if="item?.length || item?.width || item?.height" class="text-xs text-gray-400 mt-1">
                                                                        <i class="fas fa-ruler-combined mr-1"></i>
                                                                        {{ item?.length || '-' }} × {{ item?.width || '-' }} × {{ item?.height || '-' }} {{ item?.dimension_unit || 'in' }}
                                                                    </p>
                                                                </div>
                                                                <!-- Classification + Invoice badges -->
                                                                <div class="flex flex-col gap-1 ml-2">
                                                                    <div class="flex gap-1">
                                                                        <span v-if="item?.is_dangerous" class="px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-700" title="Dangerous">
                                                                            <i class="fas fa-fire"></i>
                                                                        </span>
                                                                        <span v-if="item?.is_fragile" class="px-1.5 py-0.5 rounded text-[10px] bg-amber-100 text-amber-700" title="Fragile">
                                                                            <i class="fas fa-wine-glass"></i>
                                                                        </span>
                                                                        <span v-if="item?.is_oversized" class="px-1.5 py-0.5 rounded text-[10px] bg-blue-100 text-blue-700" title="Oversized">
                                                                            <i class="fas fa-expand-arrows-alt"></i>
                                                                        </span>
                                                                    </div>
                                                                    <!-- Invoice status badge -->
                                                                    <span 
                                                                        v-if="item?.invoice_files && item?.invoice_files.length > 0" 
                                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-emerald-100 text-emerald-700"
                                                                        title="Has Invoice"
                                                                    >
                                                                        <i class="fas fa-file-invoice"></i> Invoice ✓
                                                                    </span>
                                                                    <span 
                                                                        v-else
                                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-700"
                                                                        title="Invoice Required"
                                                                    >
                                                                        <i class="fas fa-file-invoice"></i> No Invoice
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            {{ item?.quantity }}
                                                        </td>
                                                        <td class="text-center">
                                                            {{ __currency_format(item?.value_per_unit) }}
                                                        </td>
                                                        <td class="text-center">
                                                            {{ __currency_format(item?.total_line_value) }}
                                                        </td>
                                                    </tr>
                                                </template>
                                                <tr>
                                                    <td colspan="5">
                                                        <div
                                                            class="flex items-center justify-between"
                                                        >
                                                            <p>
                                                                <span
                                                                    class="uppercase"
                                                                    >Total
                                                                    weight: </span
                                                                >{{
                                                                    action?.total_weight
                                                                }}
                                                                lbs
                                                            </p>
                                                            <p>
                                                                <span
                                                                    class="uppercase"
                                                                    >Total value
                                                                    of this
                                                                    package: </span
                                                                >${{
                                                                    action?.total_value.toFixed(
                                                                        2
                                                                    )
                                                                }}
                                                                USD
                                                            </p>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="5">
                                                        <div
                                                            class="w-full my-2"
                                                        >
                                                            <a
                                                                @click="
                                                                    handleShowNote()
                                                                "
                                                                href="#"
                                                                class="text-red-700"
                                                                >Add your
                                                                notes</a
                                                            >
                                                            to this package,
                                                            this is for your use
                                                            only, Marketsz will
                                                            not review this
                                                            area.
                                                        </div>
                                                        <div v-if="isShowNote">
                                                            <TextInput
                                                                class="w-full"
                                                                placeholder="Please add note here"
                                                                v-model="
                                                                    addNote
                                                                "
                                                            />
                                                            <div
                                                                class="flex items-center gap-2 my-2"
                                                            >
                                                                <DangerButton
                                                                    @click.prevent="
                                                                        handleAddNote(
                                                                            $event,
                                                                            action.id
                                                                        )
                                                                    "
                                                                >
                                                                    Save your
                                                                    note
                                                                </DangerButton>
                                                                <a
                                                                    @click="
                                                                        handleShowNote()
                                                                    "
                                                                    href="javascript:void(0)"
                                                                    >Cancel</a
                                                                >
                                                            </div>
                                                        </div>
                                                        <p class="">
                                                            Note:
                                                            <span
                                                                class="text-red-500"
                                                                >{{
                                                                    addNote
                                                                        ? addNote
                                                                        : action?.note
                                                                }}</span
                                                            >
                                                        </p>
                                                        <hr />
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="5">
                                                        <div
                                                            class="flex items-center justify-between w-full"
                                                        >
                                                            <div class="w-full">
                                                                <label
                                                                    class="block mb-2 text-sm font-medium text-gray-700"
                                                                    >Optional
                                                                    Services</label
                                                                >
                                                                <div
                                                                    class="relative w-full max-w-md"
                                                                >
                                                                    <button
                                                                        type="button"
                                                                        class="w-full py-2 pl-4 pr-10 text-sm text-left bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
                                                                        @click="
                                                                            toggleDropdown
                                                                        "
                                                                    >
                                                                        <span
                                                                            class="block truncate"
                                                                        >
                                                                            {{
                                                                                selectedService?.title ||
                                                                                "Select Optional Service"
                                                                            }}
                                                                        </span>
                                                                        <span
                                                                            class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none"
                                                                        >
                                                                            <i
                                                                                class="text-gray-400 fa fa-chevron-down"
                                                                            ></i>
                                                                        </span>
                                                                    </button>

                                                                    <ul
                                                                        v-if="
                                                                            dropdownOpen
                                                                        "
                                                                        class="absolute z-10 w-full py-1 mt-1 overflow-auto text-sm bg-white rounded-md shadow-lg max-h-60 ring-1 ring-black ring-opacity-5"
                                                                    >
                                                                        <li
                                                                            v-for="(
                                                                                service,
                                                                                index
                                                                            ) in props?.specialRequests"
                                                                            :key="
                                                                                index
                                                                            "
                                                                            class="px-4 py-2 cursor-pointer hover:bg-gray-100"
                                                                            @click="
                                                                                selectService(
                                                                                    service,
                                                                                    action.id
                                                                                )
                                                                            "
                                                                        >
                                                                            <div
                                                                                class="flex justify-between font-medium"
                                                                            >
                                                                                <span
                                                                                    class="text-primary-500 fw-bold"
                                                                                    >{{
                                                                                        service.title
                                                                                    }}</span
                                                                                >
                                                                                <span
                                                                                    class="text-primary-600"
                                                                                    >${{
                                                                                        service.price
                                                                                    }}</span
                                                                                >
                                                                            </div>
                                                                            <p
                                                                                class="mt-1 text-xs text-gray-500"
                                                                            >
                                                                                {{
                                                                                    service.description
                                                                                }}
                                                                            </p>
                                                                        </li>
                                                                    </ul>
                                                                </div>

                                                                <div
                                                                    class="py-2"
                                                                    v-if="
                                                                        action.special_request
                                                                    "
                                                                >
                                                                    <p
                                                                        class="bold"
                                                                    >
                                                                        Your
                                                                        current
                                                                        special
                                                                        request
                                                                        is:
                                                                        <span
                                                                            class="text-primary-800"
                                                                        >
                                                                            {{
                                                                                action
                                                                                    .special_request
                                                                                    ?.title ??
                                                                                ""
                                                                            }}
                                                                        </span>
                                                                    </p>
                                                                </div>
                                                            </div>
                                                            <button
                                                                :disabled="
                                                                    isUploadingInvoice
                                                                "
                                                                type="button"
                                                                class="mt-4 text-white btn btn-big bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400"
                                                                @click="
                                                                    showUploadInvoiceModal(
                                                                        action
                                                                    )
                                                                "
                                                            >
                                                                Upload Invoice
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            </transition>
                        </template>
                    </tbody>
                </table>
                
                <!-- Empty State -->
                <div v-if="!actions || actions.length === 0" class="flex flex-col items-center justify-center py-16 px-8 text-center bg-white border border-t-0 rounded-b-lg">
                    <div class="w-20 h-20 mb-6 rounded-full bg-green-100 flex items-center justify-center">
                        <i class="fas fa-check-circle text-4xl text-green-500"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">You're All Caught Up!</h3>
                    <p class="text-gray-500 max-w-sm mb-6">
                        No packages require your action right now. When a package needs an invoice upload, it will appear here.
                    </p>
                    <a :href="route('customer.suite.viewAll')" class="text-primary-600 hover:text-primary-800 font-medium">
                        <i class="fas fa-box mr-1"></i> View All Packages
                    </a>
                </div>
            </div>
            <div class="col-span-3 p-4 rounded bg-gray-50 mt-32">
                <CurrencyDollarText />
                <PackageLinks />
            </div>
        </div>
    </Report>
    <Modal :show="isShowUploadInvoiceModal" @close="closeModal">
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    Upload Merchant Invoice
                </h2>
                <button
                    @click="closeModal"
                    class="text-gray-500 hover:text-gray-800"
                >
                    Close
                </button>
            </div>

            <!-- Item Selector - Customer can select multiple items for the invoice -->
            <div class="mb-4">
                <label class="block mb-2 text-sm font-medium text-gray-700">
                    Select Items for Invoice <span class="text-red-500">*</span>
                </label>
                <p class="mb-2 text-xs text-gray-500">
                    Select one or more items this invoice applies to. You can upload one invoice for multiple items or individual invoices for each item. Items marked with ✓ already have invoices.
                </p>
                <div class="space-y-2 max-h-48 overflow-y-auto border rounded-md p-2 bg-gray-50">
                    <div 
                        v-for="item in currentPackageItems" 
                        :key="item.id"
                        class="flex items-center justify-between p-2 rounded transition-colors"
                        :class="selectedItemIds.includes(item.id)
                            ? 'bg-primary-50 border border-primary-300' 
                            : 'bg-white border border-gray-200 hover:bg-gray-50'"
                    >
                        <div class="flex items-center gap-2 flex-1">
                            <input 
                                type="checkbox" 
                                :value="item.id" 
                                v-model="selectedItemIds"
                                class="text-primary-600 rounded border-gray-300 focus:ring-primary-500"
                                @click.stop
                            />
                            <div class="flex-1">
                                <p class="font-medium text-sm">{{ item.title }}</p>
                                <p class="text-xs text-gray-500" v-if="item.description">{{ item.description }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 ml-2">
                            <!-- Invoice status badge -->
                            <span 
                                v-if="item.invoice_files && item.invoice_files.length > 0" 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700"
                            >
                                <i class="fas fa-check text-[10px]"></i>
                                {{ item.invoice_files.length }} invoice(s)
                            </span>
                            <span 
                                v-else 
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-red-100 text-red-700"
                            >
                                <i class="fas fa-exclamation-circle text-[10px]"></i>
                                Required
                            </span>
                        </div>
                    </div>
                </div>
                <p v-if="currentPackageItems.length === 0" class="text-sm text-gray-500 mt-2">
                    No items found in this package.
                </p>
                <p v-if="selectedItemIds.length > 0" class="text-xs text-emerald-600 mt-2">
                    <i class="fas fa-info-circle"></i> {{ selectedItemIds.length }} item(s) selected
                </p>
            </div>

            <div>
                <input
                    type="file"
                    multiple
                    @change="onFileChange($event)"
                    accept=".bmp, .jpg, .jpeg, .gif, .tif, .tiff, .pdf"
                />
                <p class="mt-1 text-sm text-gray-600">
                    Accepted File Types: BMP, JPG, JPEG, GIF, TIF, TIFF, PDF
                </p>
                <p class="text-sm text-gray-600">Max File Size: 2MB</p>
            </div>

            <div v-if="files.length" class="mt-4">
                <p class="mb-1 text-sm font-medium">Selected Files:</p>
                <ul class="pl-5 space-y-1 text-sm text-gray-700 list-disc">
                    <li v-for="(file, index) in files" :key="index">
                        {{ file.name }}
                    </li>
                </ul>
            </div>

            <div class="grid grid-cols-2 gap-4 mt-4 md:grid-cols-3">
                <template v-for="(preview, index) in previews" :key="index">
                    <div class="relative group">
                        <img
                            v-if="preview"
                            :src="preview"
                            alt="Preview"
                            class="w-full h-auto border rounded shadow"
                        />
                        <button
                            @click="removeImage(index)"
                            class="absolute p-1 text-xs text-red-500 bg-white border border-red-300 rounded-full top-1 right-1 opacity-80 group-hover:opacity-100 hover:bg-red-100"
                            title="Remove"
                        >
                            ❌
                        </button>

                        <div
                            v-if="!preview"
                            class="p-4 text-sm text-gray-500 bg-gray-100 border rounded"
                        >
                            {{ files[index].type }} preview not supported.
                            <button
                                @click="removeImage(index)"
                                class="ml-2 text-xs text-red-500 underline"
                            >
                                Remove
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <button
                    @click="closeModal()"
                    class="px-4 py-2 text-sm text-gray-700 border border-gray-300 rounded hover:bg-gray-50"
                >
                    Cancel
                </button>
                <button
                    @click="upload"
                    :disabled="isUploadingInvoice"
                    class="px-4 py-2 text-sm text-white bg-primary-600 rounded hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="isUploadingInvoice">Uploading...</span>
                    <span v-else>Upload Invoice</span>
                </button>
            </div>
        </div>
    </Modal>
    <!-- Photo Lightbox -->
    <PhotoLightbox
        :photos="packagePhotos"
        :show="lightboxOpen"
        v-model:index="lightboxIndex"
        @close="closeLightbox"
    />
    <!-- Invoice Lightbox -->
    <PhotoLightbox
        :photos="invoicePhotos"
        :show="invoiceLightboxOpen"
        v-model:index="lightboxIndex"
        @close="closeInvoiceLightbox"
    />
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}
</style>
