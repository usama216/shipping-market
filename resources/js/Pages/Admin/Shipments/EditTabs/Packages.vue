<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Edit from "../Edit.vue";
import Modal from "@/Components/Modal.vue";
import { ref } from "vue";
import { Head } from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
    ship: Object,
});
const viewAllPackages = props.ship.packages;
const expandedRows = ref(new Set());
const isShowUploadInvoiceModal = ref(false);
const isShowPhotosModal = ref(false);
const files = ref([]);
const packagePhotos = ref([]);

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
    if (expandedRows.value.size === viewAllPackages.length) {
        expandedRows.value.clear();
    } else {
        expandedRows.value = new Set(viewAllPackages.map((a) => a.id));
    }
};

const allExpanded = () => expandedRows.value.size === viewAllPackages.length;

const showPackagePhotos = (packageId) => {
    const selectedPackage = viewAllPackages.find((pkg) => pkg.id === packageId);
    if (selectedPackage && selectedPackage.files) {
        packagePhotos.value = selectedPackage.files;
    } else {
        packagePhotos.value = [];
    }
    isShowPhotosModal.value = true;
};

const closeModal = () => {
    isShowUploadInvoiceModal.value = false;
    isShowPhotosModal.value = false;
    files.value = [];
    packagePhotos.value = [];
};
</script>
<template>
    <AuthenticatedLayout>
        <Head title="Shipment Packages" />
        <Edit :ship="props?.ship">
            <div class="grid grid-cols-12 gap-4">
                <div class="col-span-12">
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
                                <th class="text-center">Items</th>
                                <th class="text-center">Class.</th>
                                <th>Date Received</th>
                                <th>Total value</th>
                                <th>Total weight</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template
                                v-for="shipPackage in ship.packages"
                                :key="shipPackage.id"
                            >
                                <tr>
                                    <td
                                        @click="toggleRow(shipPackage.id)"
                                        class="cursor-pointer"
                                    >
                                        <i
                                            :class="[
                                                'fas',
                                                expandedRows.has(shipPackage.id)
                                                    ? 'fa-chevron-down'
                                                    : 'fa-chevron-right',
                                                'text-primary-500',
                                            ]"
                                        ></i>
                                    </td>
                                    <td>{{ shipPackage?.from }}</td>
                                    <td>{{ shipPackage?.package_id }}</td>
                                    <!-- Items Count -->
                                    <td class="text-center">
                                        <span v-if="shipPackage?.items?.length > 0" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-700">
                                            <i class="fas fa-box-open text-[10px]"></i>{{ shipPackage?.items?.length }}
                                        </span>
                                        <span v-else class="text-gray-300">—</span>
                                    </td>
                                    <!-- Classification -->
                                    <td class="text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <span v-if="hasClassification(shipPackage).dangerous" class="w-5 h-5 rounded-full bg-red-100 flex items-center justify-center" title="Dangerous Goods">
                                                <i class="fas fa-fire text-red-600 text-[10px]"></i>
                                            </span>
                                            <span v-if="hasClassification(shipPackage).fragile" class="w-5 h-5 rounded-full bg-amber-100 flex items-center justify-center" title="Fragile">
                                                <i class="fas fa-wine-glass text-amber-600 text-[10px]"></i>
                                            </span>
                                            <span v-if="hasClassification(shipPackage).oversized" class="w-5 h-5 rounded-full bg-blue-100 flex items-center justify-center" title="Oversized">
                                                <i class="fas fa-expand-arrows-alt text-blue-600 text-[10px]"></i>
                                            </span>
                                            <span v-if="!hasClassification(shipPackage).dangerous && !hasClassification(shipPackage).fragile && !hasClassification(shipPackage).oversized" class="text-gray-300">—</span>
                                        </div>
                                    </td>
                                    <td>
                                        {{
                                            __format_date_time(
                                                shipPackage?.date_received
                                            )
                                        }}
                                    </td>
                                    <td>
                                        {{
                                            __currency_format(
                                                shipPackage?.total_value
                                            )
                                        }}
                                        USD
                                    </td>
                                    <td>{{ shipPackage?.total_weight }} {{ shipPackage?.weight_unit || 'lbs' }}</td>
                                    <td>
                                        <p
                                            v-if="
                                                shipPackage?.status_name ==
                                                'Ready to Send'
                                            "
                                        >
                                            <i
                                                class="font-extrabold fa-solid fa-check text-primary-500"
                                            ></i
                                            ><br />
                                            <span class="text-red-500">
                                                {{ shipPackage?.status_name }}
                                            </span>
                                        </p>
                                        <p
                                            v-if="
                                                shipPackage?.status_name ==
                                                'Action Required'
                                            "
                                            class="text-primary-500"
                                        >
                                            <i
                                                class="fa-solid fa-triangle-exclamation"
                                            ></i>
                                            <br />
                                            <span>
                                                {{ shipPackage?.status_name }}
                                            </span>
                                        </p>
                                        <p
                                            v-if="
                                                shipPackage?.status_name ==
                                                'In Review'
                                            "
                                            class="text-primary-500"
                                        >
                                            <i
                                                class="fa-solid fa-magnifying-glass"
                                            ></i>
                                            <br />
                                            <span class="text-red-500">
                                                {{ shipPackage?.status_name }}
                                            </span>
                                        </p>
                                    </td>
                                </tr>
                                <transition name="fade">
                                    <tr
                                        v-if="expandedRows.has(shipPackage.id)"
                                        class="bg-gray-50"
                                    >
                                        <td colspan="9" class="px-5 text-left">
                                            <div
                                                v-if="
                                                    shipPackage.status_name ==
                                                    'Action Required'
                                                "
                                            >
                                                <strong
                                                    >Upload Merchant
                                                    Invoice</strong
                                                >
                                                <p
                                                    class="text-sm text-gray-600"
                                                >
                                                    Please upload the merchant
                                                    invoice for this package.
                                                    When your invoice is
                                                    successfully uploaded, your
                                                    package will be placed In
                                                    Review until it is verified
                                                    by Marketsz
                                                </p>
                                                <hr />
                                            </div>
                                            <div
                                                v-if="
                                                    shipPackage.status_name ==
                                                    'In Review'
                                                "
                                            >
                                                <div>
                                                    <strong class="bold"
                                                        >Why is this package in
                                                        review?</strong
                                                    ><br />
                                                    <p
                                                        class="text-sm text-white bg-[#f19445] uppercase px-2 inline-block"
                                                    >
                                                        Dangerous Goods
                                                    </p>
                                                    <p class="py-1">
                                                        We are reviewing your
                                                        package and will email
                                                        you if it is not ready
                                                        to send within two
                                                        business days.
                                                    </p>
                                                    <hr />
                                                </div>
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
                                                                    Package
                                                                    Details
                                                                </p>
                                                                <p>
                                                                    To:
                                                                    {{
                                                                        shipPackage
                                                                            ?.user
                                                                            ?.name
                                                                    }}
                                                                </p>
                                                            </div>

                                                            <div>
                                                                <button
                                                                    class="text-white btn bg-primary-500"
                                                                    @click="
                                                                        showPackagePhotos(
                                                                            shipPackage.id
                                                                        )
                                                                    "
                                                                >
                                                                    Photo
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </th>
                                                    <th>Qty</th>
                                                    <th>
                                                        Value Per Unit (USD)
                                                    </th>
                                                    <th>
                                                        Total Line Value (USD)
                                                    </th>
                                                </thead>
                                                <tbody>
                                                    <template
                                                        v-for="item in shipPackage.items"
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
                                                                        <!-- Item dimensions if available -->
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
                                                                        shipPackage?.weight
                                                                    }}
                                                                    lbs
                                                                </p>
                                                                <p>
                                                                    <span
                                                                        class="uppercase"
                                                                        >Total
                                                                        value of
                                                                        this
                                                                        package: </span
                                                                    >{{
                                                                        __currency_format(
                                                                            shipPackage.total_value
                                                                        )
                                                                    }}
                                                                    USD
                                                                </p>
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
                </div>
            </div>
        </Edit>
    </AuthenticatedLayout>
    <Modal :show="isShowPhotosModal" @close="closeModal">
        <div class="p-6 space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    Package Photos
                </h2>
                <button
                    @click="closeModal"
                    class="text-gray-500 hover:text-gray-800"
                >
                    Close
                </button>
            </div>

            <div
                class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4"
                v-if="packagePhotos.length > 0"
            >
                <img
                    v-for="(photo, index) in packagePhotos"
                    :key="index"
                    :src="photo.file_with_url"
                    alt="Package Photo"
                    class="border rounded shadow"
                />
            </div>
            <div class="text-center text-gray-900" v-else>
                <h3>No photos available</h3>
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
