<script setup>
import { ref } from "vue";
import Report from "../Report.vue";
import TextInput from "@/Components/TextInput.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import axios from "axios";
import { useToast } from "vue-toastification";
import CurrencyDollarText from "@/Components/Packages/CurrencyDollarText.vue";
import PackageLinks from "@/Components/Packages/PackageLinks.vue";
import PhotoLightbox from "@/Components/PhotoLightbox.vue";

const props = defineProps({
    inReviews: Object,
    specialRequests: Object,
    packageCounts: Array,
});
const toast = useToast();
const inReviews = props.inReviews;

const expandedRows = ref(new Set());
const isShowNote = ref(false);
const isShowUploadInvoiceModal = ref(false);
const isShowPhotosModal = ref(false);
const addNote = ref(null);
const files = ref([]);
const packagePhotos = ref([]);

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
    if (expandedRows.value.size === inReviews.length) {
        expandedRows.value.clear();
    } else {
        expandedRows.value = new Set(inReviews.map((a) => a.id));
    }
};

const allExpanded = () => expandedRows.value.size === inReviews.length;

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
</script>

<template>
    <Report
        :actionCount="props?.packageCounts.action_required"
        :inReviewCount="props?.packageCounts?.in_review"
        :readyToSendCount="props?.packageCounts?.ready_to_send"
        :allPackagesCount="props?.packageCounts?.all"
    >
        <div class="grid grid-cols-12 gap-4">
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
                            <th>Total value</th>
                            <th>Total weight</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template
                            v-for="in_review in inReviews"
                            :key="in_review.id"
                        >
                            <tr>
                                <td
                                    @click="toggleRow(in_review.id)"
                                    class="cursor-pointer"
                                >
                                    <i
                                        :class="[
                                            'fas',
                                            expandedRows.has(in_review.id)
                                                ? 'fa-chevron-down'
                                                : 'fa-chevron-right',
                                            'text-primary-500',
                                        ]"
                                    ></i>
                                </td>
                                <td>{{ in_review.from }}</td>
                                <td>{{ in_review.package_id }}</td>
                                <td>
                                    {{
                                        __format_date_time(
                                            in_review.date_received
                                        )
                                    }}
                                </td>
                                <td>
                                    ${{
                                        __to_fixed_number(in_review.total_value)
                                    }}
                                    USD
                                </td>
                                <td>{{ in_review.total_weight }} lbs</td>
                            </tr>
                            <transition name="fade">
                                <tr
                                    v-if="expandedRows.has(in_review.id)"
                                    class="bg-gray-50"
                                >
                                    <td colspan="6" class="px-5 text-left">
                                        <div>
                                            <strong class="bold"
                                                >Why is this package in
                                                review?</strong
                                            ><br />
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                <span v-if="hasClassification(in_review).dangerous" class="text-sm text-white bg-red-500 uppercase px-2 py-0.5 inline-block rounded">
                                                    <i class="fas fa-fire mr-1"></i>Dangerous Goods
                                                </span>
                                                <span v-if="hasClassification(in_review).fragile" class="text-sm text-white bg-amber-500 uppercase px-2 py-0.5 inline-block rounded">
                                                    <i class="fas fa-wine-glass mr-1"></i>Fragile Items
                                                </span>
                                                <span v-if="hasClassification(in_review).oversized" class="text-sm text-white bg-blue-500 uppercase px-2 py-0.5 inline-block rounded">
                                                    <i class="fas fa-expand-arrows-alt mr-1"></i>Oversized Items
                                                </span>
                                                <span v-if="!hasClassification(in_review).dangerous && !hasClassification(in_review).fragile && !hasClassification(in_review).oversized" class="text-sm text-white bg-[#f19445] uppercase px-2 py-0.5 inline-block rounded">
                                                    <i class="fas fa-clock mr-1"></i>Pending Admin Review
                                                </span>
                                            </div>
                                            <p class="py-1">
                                                We are reviewing your package
                                                and will email you if it is not
                                                ready to send within two
                                                business days.
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
                                                                    in_review
                                                                        ?.customer
                                                                        ?.name
                                                                }}
                                                            </p>
                                                        </div>

                                                        <div class="flex gap-2">
                                                            <button
                                                                class="text-black bg-white btn"
                                                                @click="showPackagePhotos(in_review.id)"
                                                            >
                                                                Photo
                                                            </button>
                                                            <button
                                                            v-if="in_review.items && in_review.items.some(item => item.invoice_files && item.invoice_files.length > 0)"
                                                                class="text-white btn bg-blue-500 hover:bg-blue-600"
                                                                @click="showPackageInvoices(in_review)"
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
                                                    v-for="item in in_review.items"
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
                                                    <td colspan="6">
                                                        <div
                                                            class="flex items-center justify-between"
                                                        >
                                                            <p>
                                                                <span
                                                                    class="uppercase"
                                                                    >Total
                                                                    weight: </span
                                                                >{{
                                                                    in_review?.total_weight
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
                                                                    in_review.total_value
                                                                }}
                                                                USD
                                                            </p>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td
                                                        colspan="6"
                                                        class="text-gray-500"
                                                    >
                                                        **Values shown are
                                                        obtained from the
                                                        merchant invoices, when
                                                        available. Researched
                                                        values based on current
                                                        market prices have been
                                                        provided above for any
                                                        items that arrived
                                                        without invoices. The
                                                        value should be updated
                                                        to reflect the actual
                                                        price paid for each
                                                        item, and must be
                                                        confirmed.
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
                                                                            in_review.id
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
                                                                        : in_review?.note
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
                <div v-if="!inReviews || inReviews.length === 0" class="flex flex-col items-center justify-center py-16 px-8 text-center bg-white border border-t-0 rounded-b-lg">
                    <div class="w-20 h-20 mb-6 rounded-full bg-amber-100 flex items-center justify-center">
                        <i class="fas fa-hourglass-half text-4xl text-amber-500"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Nothing In Review</h3>
                    <p class="text-gray-500 max-w-sm">
                        Packages awaiting verification will show up here after you upload invoices.
                    </p>
                </div>
            </div>
            <div class="col-span-3 p-4 rounded bg-gray-50 mt-32">
                <CurrencyDollarText />
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
