<script setup>
import { onMounted, ref, watch } from "vue";
import Report from "../Report.vue";
import TextInput from "@/Components/TextInput.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import axios from "axios";
import { useToast } from "vue-toastification";
import Checkbox from "@/Components/Checkbox.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import PackageLinks from "@/Components/Packages/PackageLinks.vue";
import CurrencyDollarText from "@/Components/Packages/CurrencyDollarText.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import PhotoLightbox from "@/Components/PhotoLightbox.vue";
import CreditCard from "@/Components/Shipment/partials/CreditCard.vue";

const props = defineProps({
    readyToSends: Object,
    specialRequests: Object,
    packageCounts: Array,
    cards: {
        type: Array,
        default: () => []
    }
});
const toast = useToast();
const readyToSends = props.readyToSends;

const expandedRows = ref(new Set());
const isShowNote = ref(false);
const isShowUploadInvoiceModal = ref(false);
const isShowPhotosModal = ref(false);
const addNote = ref(null);
const files = ref([]);
const packagePhotos = ref([]);
const selectedIds = ref([]);

// Lightbox state
const lightboxOpen = ref(false);
const lightboxImage = ref(null);
const lightboxIndex = ref(0);
const bulkCheckbox = ref(false);
const selectedService = ref(null);
const dropdownOpen = ref({}); // Track dropdown state per package
const selectedAddons = ref({}); // Track selected addons per package: { packageId: [addonIds] }

// Invoice lightbox state
const invoiceLightboxOpen = ref(false);
const invoicePhotos = ref([]);

// Payment modal state
const showPaymentModal = ref(false);
const paymentPackageId = ref(null);
const selectedCardId = ref(null);
const paymentTotal = ref(0);
const paymentProcessing = ref(false);

// Check if any item has classification flags
const hasClassification = (pkg) => {
    if (!pkg.items || pkg.items.length === 0) return { dangerous: false, fragile: false, oversized: false };
    return {
        dangerous: pkg.items.some(item => item.is_dangerous),
        fragile: pkg.items.some(item => item.is_fragile),
        oversized: pkg.items.some(item => item.is_oversized)
    };
};

const totalValue = ref(0);
const totalWeight = ref(0);
const totalPackages = ref(0);

watch(selectedIds, () => {
    bulkCheckbox.value = selectedIds.value.length === readyToSends.length;
    calculateTotals();
});

const selectAll = (e) => {
    selectedIds.value = e.target.checked
        ? readyToSends.map((item) => item.id)
        : [];
};
const resetSelection = () => {
    bulkCheckbox.value = false;
    selectedIds.value = [];
};

const toggleRow = (id) => {
    if (expandedRows.value.has(id)) {
        expandedRows.value.delete(id);
    } else {
        expandedRows.value.add(id);
    }
};
const toggleAll = () => {
    if (expandedRows.value.size === readyToSends.length) {
        expandedRows.value.clear();
    } else {
        expandedRows.value = new Set(readyToSends.map((a) => a.id));
    }
};

const allExpanded = () => expandedRows.value.size === readyToSends.length;
const toggleDropdown = (packageId) => {
    if (!dropdownOpen.value[packageId]) {
        dropdownOpen.value[packageId] = false;
    }
    dropdownOpen.value[packageId] = !dropdownOpen.value[packageId];
};

// Initialize selected special requests from package data
const initializePackageSpecialRequests = (pkg) => {
    if (pkg.selected_addon_ids && Array.isArray(pkg.selected_addon_ids)) {
        selectedAddons.value[pkg.id] = [...pkg.selected_addon_ids];
    } else if (pkg.selected_addon_ids && typeof pkg.selected_addon_ids === 'string') {
        // Handle JSON string
        try {
            selectedAddons.value[pkg.id] = JSON.parse(pkg.selected_addon_ids);
        } catch {
            selectedAddons.value[pkg.id] = [];
        }
    } else if (pkg.special_request) {
        // Legacy: single special_request
        selectedAddons.value[pkg.id] = [pkg.special_request];
    } else {
        selectedAddons.value[pkg.id] = [];
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

// Show package invoices in lightbox - collects from item-level invoice_files
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

const toggleSpecialRequest = async (specialRequest, packageId) => {
    // Initialize if not exists
    if (!selectedAddons.value[packageId]) {
        selectedAddons.value[packageId] = [];
    }
    
    const requestIndex = selectedAddons.value[packageId].indexOf(specialRequest.id);
    
    if (requestIndex > -1) {
        // Remove special request
        selectedAddons.value[packageId].splice(requestIndex, 1);
    } else {
        // Add special request
        selectedAddons.value[packageId].push(specialRequest.id);
    }
    
    // Check if any selected services have cost > 0
    const selectedServices = props.specialRequests.filter(s => 
        selectedAddons.value[packageId]?.includes(s.id)
    );
    const totalCost = selectedServices.reduce((sum, s) => sum + (parseFloat(s.price) || 0), 0);
    
    // If services with cost are selected, trigger payment flow
    if (totalCost > 0) {
        await processSpecialRequestPayment(packageId);
    } else {
        // No cost, just save
        await savePackageSpecialRequests(packageId);
    }
};

const processSpecialRequestPayment = async (packageId) => {
    // Calculate total cost
    const selectedServices = props.specialRequests.filter(s => 
        selectedAddons.value[packageId]?.includes(s.id)
    );
    const totalCost = selectedServices.reduce((sum, s) => sum + (parseFloat(s.price) || 0), 0);
    
    if (totalCost <= 0) {
        // No cost, just save
        await savePackageSpecialRequests(packageId);
        return;
    }
    
    // Check if customer has cards
    if (!props.cards || props.cards.length === 0) {
        toast.error("Please add a payment card first.");
        return;
    }
    
    // Set default card if available
    const defaultCard = props.cards.find(c => c.is_default) || props.cards[0];
    selectedCardId.value = defaultCard?.id;
    paymentPackageId.value = packageId;
    paymentTotal.value = totalCost;
    showPaymentModal.value = true;
};

const confirmSpecialRequestPayment = async () => {
    if (!selectedCardId.value) {
        toast.error("Please select a payment card.");
        return;
    }
    
    if (!paymentPackageId.value) {
        toast.error("Invalid package.");
        return;
    }
    
    paymentProcessing.value = true;
    
    try {
        const response = await axios.post(
            route("customer.packageSetSpecialRequest"),
            {
                package_id: paymentPackageId.value,
                special_request_ids: selectedAddons.value[paymentPackageId.value] || [],
                card_id: selectedCardId.value,
            }
        );
        
        toast.success(response.data?.message || "Special services payment processed successfully. Package moved to In Review.");
        showPaymentModal.value = false;
        paymentPackageId.value = null;
        selectedCardId.value = null;
        paymentTotal.value = 0;
        
        // Reload to show updated status
        router.reload({ only: ['readyToSends'] });
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to process payment.");
    } finally {
        paymentProcessing.value = false;
    }
};

const savePackageSpecialRequests = async (packageId) => {
    try {
        const response = await axios.post(
            route("customer.packageSetSpecialRequest"),
            {
                package_id: packageId,
                special_request_ids: selectedAddons.value[packageId] || [],
            }
        );
        toast.success(response.data?.message || "Optional services updated successfully.");
    } catch (error) {
        toast.error(error.response?.data?.message || "Failed to update optional services.");
        // Revert on error - reload package data
        router.reload({ only: ['readyToSends'] });
    }
};

const isSpecialRequestSelected = (requestId, packageId) => {
    return selectedAddons.value[packageId]?.includes(requestId) || false;
};

const handleCreateShipRequest = async () => {
    if (selectedIds.value.length === 0) {
        toast.error(
            "Please select at least one package to create a ship request."
        );
        return;
    }
    try {
        router.post(
            route("customer.shipment.create"),
            {
                package_ids: selectedIds.value,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    // toast.success("Ship request created successfully.");
                    resetSelection();
                },
                onError: (error) => {
                    toast.error(
                        error.response.data.message ||
                            "Failed to create ship request."
                    );
                },
            }
        );
    } catch (error) {
        toast.error(
            error.response.data.message || "Failed to create ship request."
        );
    }
};



const calculateTotals = () => {
    const selected = readyToSends.filter((item) =>
        selectedIds.value.includes(item.id)
    );

    totalPackages.value = selected.length;
    totalValue.value = selected.reduce(
        (sum, p) => sum + Number(p.total_value),
        0
    );
    totalWeight.value = selected.reduce((sum, p) => sum + Number(p.total_weight), 0);
};
onMounted(() => {
    selectedIds.value = readyToSends.map((item) => item.id);
    // Initialize special request selections for each package
    readyToSends.forEach(pkg => {
        initializePackageSpecialRequests(pkg);
    });
    calculateTotals();
});
</script>

<template>
    <Head title="Ready to send" />
    <Report
        :actionCount="props?.packageCounts.action_required"
        :inReviewCount="props?.packageCounts?.in_review"
        :readyToSendCount="props?.packageCounts?.ready_to_send"
        :allPackagesCount="props?.packageCounts?.all"
    >
        <div class="grid md:grid-cols-12 gap-2">
            <div class="col-span-12 md:col-span-9">
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
                            <th>Total value</th>
                            <th>Total weight</th>
                            <th>
                                <input
                                    class="border-gray-300 shadow-sm text-primary-600 focus:ring-primary-500"
                                    type="checkbox"
                                    v-model="bulkCheckbox"
                                    @change="selectAll"
                                />
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <template
                            v-for="readyToSend in readyToSends"
                            :key="readyToSend.id"
                        >
                            <tr>
                                <td
                                    @click="toggleRow(readyToSend.id)"
                                    class="cursor-pointer"
                                >
                                    <i
                                        :class="[
                                            'fas',
                                            expandedRows.has(readyToSend.id)
                                                ? 'fa-chevron-down'
                                                : 'fa-chevron-right',
                                            'text-primary-500',
                                        ]"
                                    ></i>
                                </td>
                                <td>{{ readyToSend.from }}</td>
                                <td>{{ readyToSend.package_id }}</td>
                                <td>
                                    {{
                                        __format_date_time(
                                            readyToSend.date_received
                                        )
                                    }}
                                </td>
                                <td>
                                    {{
                                        __currency_format(
                                            readyToSend.total_value
                                        )
                                    }}
                                    USD
                                </td>
                                <td>{{ readyToSend.total_weight }} lbs</td>
                                <td class="whitespace-nowrap !text-center">
                                    <input
                                        class="border-gray-300 shadow-sm text-primary-600 focus:ring-primary-500"
                                        type="checkbox"
                                        :value="readyToSend.id"
                                        v-model="selectedIds"
                                    />
                                </td>
                            </tr>
                            <transition name="fade">
                                <tr
                                    v-if="expandedRows.has(readyToSend.id)"
                                    class="bg-gray-50"
                                >
                                    <td colspan="6" class="px-5 text-left">
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
                                                                    readyToSend
                                                                        ?.customer
                                                                        ?.name
                                                                }}
                                                            </p>
                                                        </div>

                                                        <div class="flex gap-2">
                                                            <button
                                                                class="text-white btn bg-primary-500"
                                                                @click="showPackagePhotos(readyToSend.id)"
                                                            >
                                                                Photo
                                                            </button>
                                                            <button
                                                            v-if="readyToSend.items && readyToSend.items.some(item => item.invoice_files && item.invoice_files.length > 0)"
                                                                class="text-white btn bg-blue-500 hover:bg-blue-600"
                                                                @click="showPackageInvoices(readyToSend)"
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
                                                    v-for="item in readyToSend.items"
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
                                                                <!-- Classification badges -->
                                                                <div class="flex gap-1 ml-2">
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
                                                                    readyToSend?.total_weight
                                                                }}
                                                                lbs
                                                            </p>
                                                            <p>
                                                                <span
                                                                    class="uppercase"
                                                                    >Total value
                                                                    of this
                                                                    package: </span
                                                                >{{
                                                                    __currency_format(
                                                                        readyToSend.total_value
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
                                                            class="flex items-center justify-between w-full"
                                                        >
                                                            <div class="w-full">
                                                                <label
                                                                    class="block mb-2 text-sm font-medium text-gray-700"
                                                                    >Optional Services</label
                                                                >
                                                                <div class="space-y-2 max-w-md">
                                                                    <div
                                                                        v-for="(service, index) in props?.specialRequests"
                                                                        :key="index"
                                                                        class="flex items-start p-3 border border-gray-200 rounded-md hover:bg-gray-50 transition-colors"
                                                                    >
                                                                        <div class="flex items-center h-5 mt-0.5">
                                                                            <input
                                                                                type="checkbox"
                                                                                :checked="isSpecialRequestSelected(service.id, readyToSend.id)"
                                                                                @change="toggleSpecialRequest(service, readyToSend.id)"
                                                                                class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                                                            />
                                                                        </div>
                                                                        <div class="ml-3 flex-1">
                                                                            <div class="flex justify-between items-start">
                                                                                <div>
                                                                                    <label class="text-sm font-medium text-gray-900 cursor-pointer" @click="toggleAddon(service, readyToSend.id)">
                                                                                        {{ service?.title }}
                                                                                    </label>
                                                                                    <p class="mt-1 text-xs text-gray-500">
                                                                                        {{ service?.description }}
                                                                                    </p>
                                                                                </div>
                                                                                <span class="ml-4 text-sm font-semibold text-primary-600 whitespace-nowrap">
                                                                                    ${{ service?.price || 0 }}
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <p v-if="!props?.specialRequests || props?.specialRequests.length === 0" class="text-sm text-gray-500 italic">
                                                                        No optional services available
                                                                    </p>
                                                                </div>
                                                                
                                                                <!-- Show selected services summary -->
                                                                <div
                                                                    v-if="selectedAddons[readyToSend.id] && selectedAddons[readyToSend.id].length > 0"
                                                                    class="mt-3 p-2 bg-primary-50 rounded-md"
                                                                >
                                                                    <p class="text-xs font-medium text-primary-800 mb-1">
                                                                        Selected Services:
                                                                    </p>
                                                                    <div class="flex flex-wrap gap-2">
                                                                        <span
                                                                            v-for="addonId in selectedAddons[readyToSend.id]"
                                                                            :key="addonId"
                                                                            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-primary-100 text-primary-800"
                                                                        >
                                                                            {{ props?.specialRequests?.find(s => s.id === addonId)?.title || 'Service #' + addonId }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="6">
                                                        <p
                                                            class="text-gray-500"
                                                        >
                                                            **Values shown are
                                                            obtained from the
                                                            merchant invoices,
                                                            when available.
                                                            Researched values
                                                            based on current
                                                            market prices have
                                                            been provided above
                                                            for any items that
                                                            arrived without
                                                            invoices. The value
                                                            should be updated to
                                                            reflect the actual
                                                            price paid for each
                                                            item, and must be
                                                            confirmed.
                                                        </p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="6">
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
                                                                            readyToSend.id
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
                                                                        : readyToSend?.note
                                                                }}</span
                                                            >
                                                        </p>
                                                        <hr />
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
                <div v-if="!readyToSends || readyToSends.length === 0" class="flex flex-col items-center justify-center py-16 px-8 text-center bg-white border border-t-0 rounded-b-lg">
                    <div class="w-20 h-20 mb-6 rounded-full bg-blue-100 flex items-center justify-center">
                        <i class="fas fa-rocket text-4xl text-blue-500"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No Packages Ready Yet</h3>
                    <p class="text-gray-500 max-w-sm mb-6">
                        Once your packages are verified, they'll be ready for shipping here.
                    </p>
                    <a :href="route('customer.suite.inReview')" class="text-primary-600 hover:text-primary-800 font-medium">
                        <i class="fas fa-hourglass-half mr-1"></i> Check In Review
                    </a>
                </div>
            </div>
            <div class="p-4 rounded md:col-span-3 bg-gray-50 mt-32">
                <CurrencyDollarText />
                <div class="col-span-3 p-4 mt-4 bg-white rounded shadow">
                    <div class="flex flex-wrap items-center justify-between">
                        <h3 class="mb-2 text-lg font-semibold">
                            Estimated Shipping:
                        </h3>
                        <div
                            class="flex flex-wrap items-center justify-between w-full"
                        >
                            <p>Total Values</p>
                            <p>{{ __currency_format(totalValue) }}</p>
                        </div>
                        <div
                            class="flex flex-wrap items-center justify-between w-full"
                        >
                            <p>Total Weight</p>
                            <p>{{ totalWeight }} lbs</p>
                        </div>
                        <div
                            class="flex flex-wrap items-center justify-between w-full"
                        >
                            <p>Packages</p>
                            <p>{{ totalPackages }}</p>
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-700">
                        One or more packages in this ship request cannot be
                        delivered. Please contact customer service for more
                        information.
                    </p>
                    <div class="text-center">
                        <PrimaryButton
                            class="mt-4 font-medium"
                            @click="handleCreateShipRequest"
                        >
                            Create ship request
                        </PrimaryButton>
                    </div>
                    <p class="mt-2 text-sm text-gray-600">
                        All items are subject to a customs duty upon receipt of
                        package. Payment will be due when your package is
                        delivered.
                    </p>
                </div>

                <PackageLinks />
            </div>
        </div>
    </Report>
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

    <!-- Payment Modal for Special Services -->
    <Modal :show="showPaymentModal" @close="showPaymentModal = false" maxWidth="2xl">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Pay for Special Services</h2>
                <button
                    @click="showPaymentModal = false"
                    class="text-gray-400 hover:text-gray-600"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-gray-700 mb-2">
                    <strong>Total Amount:</strong> <span class="text-lg font-bold text-primary-600">${{ paymentTotal.toFixed(2) }}</span>
                </p>
                <p class="text-xs text-gray-600">
                    After payment, your package will be moved to "In Review" status for admin processing.
                </p>
            </div>

            <div v-if="cards && cards.length > 0" class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Payment Card</label>
                <CreditCard
                    :cards="cards"
                    :publishable-key="null"
                    @selected-card="(card) => selectedCardId = card"
                />
            </div>
            
            <div v-else class="mb-4 p-4 bg-yellow-50 rounded-lg">
                <p class="text-sm text-yellow-800">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    No payment cards found. Please add a card first.
                </p>
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <SecondaryButton @click="showPaymentModal = false" :disabled="paymentProcessing">
                    Cancel
                </SecondaryButton>
                <PrimaryButton 
                    @click="confirmSpecialRequestPayment" 
                    :disabled="!selectedCardId || paymentProcessing"
                    class="bg-primary-600"
                >
                    <span v-if="paymentProcessing">
                        <i class="fas fa-spinner fa-spin mr-2"></i>Processing...
                    </span>
                    <span v-else>
                        Pay ${{ paymentTotal.toFixed(2) }}
                    </span>
                </PrimaryButton>
            </div>
        </div>
    </Modal>
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
