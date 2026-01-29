<script setup>
import { ref, watch, computed, onMounted } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import { usePermissions } from "@/Composables/usePermissions";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import VueDatePicker from "@vuepic/vue-datepicker";
import SearchableSelect from "vue-select";
import InputError from "@/Components/InputError.vue";
import EditLayout from "../Edit.vue";
import Delete from "../Delete.vue";
import packageStatus from "@js/Data/package_status.json";
import CameraCapture from "@/Components/CameraCapture.vue";

const props = defineProps({
    package: Object,
    customers: Array,
});

const { can } = usePermissions();

const editPackage = props.package;

const form = useForm({
    from: editPackage?.from,
    date: new Date(editPackage?.date_received),
    customer_id: editPackage?.customer_id,
    status: editPackage?.status,
    note: editPackage?.note || "",
    files: [],
    items: editPackage?.items?.map((item) => ({
        id: item.id,
        title: item.title || "",
        description: item.description || "",
        hs_code: item.hs_code || "",
        material: item.material || "",
        item_note: item.item_note || "",
        quantity: item.quantity || 1,
        value_per_unit: item.value_per_unit || 0,
        weight_per_unit: item.weight_per_unit || 0,
        total_line_value: item.total_line_value || 0,
        total_line_weight: item.total_line_weight || 0,
        // Item dimensions
        length: item.length || null,
        width: item.width || null,
        height: item.height || null,
        dimension_unit: item.dimension_unit || "in",
        // Item classification
        is_dangerous: item.is_dangerous || false,
        is_fragile: item.is_fragile || false,
        is_oversized: item.is_oversized || false,
        classification_notes: item.classification_notes || "",
        package_files: item.package_files || [],
        new_files: [],
        delete_file_ids: [],
        // Invoice files - preserve from editPackage
        invoice_files: item.invoice_files || item.invoiceFiles || [],
    })) ?? [
        {
            title: "",
            description: "",
            hs_code: "",
            material: "",
            item_note: "",
            quantity: 1,
            value_per_unit: 0,
            weight_per_unit: 0,
            total_line_value: 0,
            total_line_weight: 0,
            length: null,
            width: null,
            height: null,
            dimension_unit: "in",
            is_dangerous: false,
            is_fragile: false,
            is_oversized: false,
            classification_notes: "",
            package_files: [],
            new_files: [],
            delete_file_ids: [],
        },
    ],
    totalPrice: 0,
    totalWeight: 0,
    store_tracking_id: editPackage?.store_tracking_id || "",
});

const calculateTotals = () => {
    form.items.forEach((item) => {
        item.total_line_value = parseFloat(
            (item.quantity * item.value_per_unit).toFixed(2)
        );
    });
    form.totalPrice = parseFloat(
        form.items
            .reduce((sum, item) => sum + (item.total_line_value || 0), 0)
            .toFixed(2)
    );
    form.totalWeight = parseFloat(
        form.items
            .reduce((sum, item) => sum + (item.total_line_weight || 0), 0)
            .toFixed(2)
    );
};

watch(() => form.items, calculateTotals, { deep: true });
onMounted(calculateTotals);

const addItem = () => {
    form.items.push({
        title: "",
        description: "",
        hs_code: "",
        material: "",
        item_note: "",
        quantity: 1,
        value_per_unit: 0,
        weight_per_unit: 0,
        total_line_value: 0,
        total_line_weight: 0,
        length: null,
        width: null,
        height: null,
        dimension_unit: "in",
        is_dangerous: false,
        is_fragile: false,
        is_oversized: false,
        classification_notes: "",
        package_files: [],
        new_files: [],
        delete_file_ids: [],
    });
};
const removeItem = (index) => {
    if (form.items.length > 1) form.items.splice(index, 1);
};

const handlePackageFileChange = (e) => {
    form.files.push(...e.target.files);
};
const handleItemFileChange = (e, index) => {
    const files = Array.from(e.target.files);
    form.items[index].new_files.push(...files);
};
const removeItemFile = (itemIndex, fileIndex, isExisting = false) => {
    const item = form.items[itemIndex];
    if (isExisting) {
        const file = item.package_files[fileIndex];
        if (file?.id) item.delete_file_ids.push(file.id);
        item.package_files.splice(fileIndex, 1);
    } else {
        item.new_files.splice(fileIndex, 1);
    }
};

const submitForm = () => {
    form.date = new Date(form.date).toISOString();
    form.transform((data) => {
        const payload = new FormData();
        payload.append("from", data.from);
        payload.append("date_received", data.date);
        payload.append("customer_id", data.customer_id);
        payload.append("status", data.status);
        payload.append("note", data.note);
        payload.append("store_tracking_id", data.store_tracking_id);
        payload.append("total_value", data.totalPrice);

        data.files.forEach((file, index) =>
            payload.append(`files[${index}]`, file)
        );

        data.items.forEach((item, i) => {
            payload.append(`items[${i}][id]`, item.id || "");
            payload.append(`items[${i}][title]`, item.title);
            payload.append(`items[${i}][description]`, item.description);
            // Customs declaration fields
            payload.append(`items[${i}][hs_code]`, item.hs_code || "");
            payload.append(`items[${i}][material]`, item.material || "");
            payload.append(`items[${i}][item_note]`, item.item_note);
            payload.append(`items[${i}][quantity]`, item.quantity);
            payload.append(`items[${i}][value_per_unit]`, item.value_per_unit);
            payload.append(`items[${i}][weight_per_unit]`, item.weight_per_unit || 0);
            payload.append(
                `items[${i}][total_line_value]`,
                item.total_line_value
            );
            payload.append(
                `items[${i}][total_line_weight]`,
                item.total_line_weight
            );
            
            // Item dimensions
            if (item.length) payload.append(`items[${i}][length]`, item.length);
            if (item.width) payload.append(`items[${i}][width]`, item.width);
            if (item.height) payload.append(`items[${i}][height]`, item.height);
            payload.append(`items[${i}][dimension_unit]`, item.dimension_unit);
            
            // Item classification
            payload.append(`items[${i}][is_dangerous]`, item.is_dangerous ? 1 : 0);
            payload.append(`items[${i}][is_fragile]`, item.is_fragile ? 1 : 0);
            payload.append(`items[${i}][is_oversized]`, item.is_oversized ? 1 : 0);
            if (item.classification_notes) {
                payload.append(`items[${i}][classification_notes]`, item.classification_notes);
            }

            item.new_files.forEach((file, j) => {
                payload.append(`items[${i}][new_files][${j}]`, file);
            });

            item.delete_file_ids.forEach((fileId, j) => {
                payload.append(`items[${i}][delete_file_ids][${j}]`, fileId);
            });
        });

        return payload;
    }).post(route("admin.packages.update", editPackage.id), {
        preserveScroll: true,
        onSuccess: () => window.location.reload(),
        onError: (errors) => console.error(errors),
    });
};

const addCameraPhoto = (index, file) => {
    if (file instanceof File) {
        form.items[index].new_files.push(file);
    } else {
        console.warn("Captured file is not a valid File object:", file);
    }
};

// Check if there are any invoice files
const hasInvoiceFiles = computed(() => {
    if (!editPackage?.items || !Array.isArray(editPackage.items)) {
        console.log('[Merge PDF] No items found');
        return false;
    }
    
    // Debug: log the structure
    console.log('[Merge PDF] Checking invoice files. Items:', editPackage.items.length);
    let totalInvoiceFiles = 0;
    
    editPackage.items.forEach((item, idx) => {
        const invoiceFiles = item.invoice_files || item.invoiceFiles || [];
        const count = Array.isArray(invoiceFiles) ? invoiceFiles.length : 0;
        totalInvoiceFiles += count;
        
        if (count > 0) {
            console.log(`[Merge PDF] Item ${idx} (${item.id} - ${item.title}):`, {
                invoice_files_count: item.invoice_files?.length || 0,
                invoiceFiles_count: item.invoiceFiles?.length || 0,
                total: count,
                files: invoiceFiles.map(f => ({ id: f.id, file_type: f.file_type }))
            });
        }
    });
    
    const hasFiles = totalInvoiceFiles > 0;
    console.log('[Merge PDF] Final result:', hasFiles, 'Total invoice files:', totalInvoiceFiles);
    
    return hasFiles;
});

// Download master PDF - fetches URL from API and opens in new tab
const downloadMasterPDF = async (url) => {
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.url) {
            window.open(data.url, '_blank');
        } else {
            alert(data.message || 'Failed to generate master PDF');
        }
    } catch (error) {
        console.error('Error fetching master PDF:', error);
        alert('Failed to generate master PDF. Please try again.');
    }
};

// Handle download master PDF with validation
const handleDownloadMasterPDF = () => {
    if (!editPackage?.id) {
        alert('Package ID not found. Package ID: ' + (editPackage?.id || 'undefined'));
        return;
    }
    
    if (!hasInvoiceFiles.value) {
        alert('No invoice files found for this package. Please check console for details.');
        return;
    }
    
    downloadMasterPDF(route('admin.packages.invoices.merged', editPackage.id));
};

const selectedCustomer = computed(() => {
    return props.customers.find((u) => u.id === form.customer_id);
});
</script>

<template>
    <EditLayout :package="props.package">
        <form @submit.prevent="submitForm" enctype="multipart/form-data">
            <div class="card">
                <div class="grid grid-cols-2 gap-6 card-body">
                    <div>
                        <InputLabel for="from" value="From" />
                        <TextInput
                            id="from"
                            v-model="form.from"
                            type="text"
                            placeholder="Enter company name e.g Amazon"
                            class="w-full"
                        />
                        <InputError class="mt-2" :message="form.errors.from" />
                    </div>

                    <div>
                        <InputLabel for="date" value="Date Received" />
                        <VueDatePicker
                            v-model="form.date"
                            class="w-full text-black border-gray-300 rounded-md shadow-sm"
                        />
                        <InputError class="mt-2" :message="form.errors.date" />
                    </div>
                    <div class="">
                        <InputLabel value="Store Tracking ID" />
                        <TextInput
                            type="text"
                            v-model="form.store_tracking_id"
                            class="w-full"
                            placeholder="Amazon, FedEx, UPS tracking number"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Tracking number from the store/merchant
                        </p>
                        <InputError
                            class="mt-2"
                            :message="form.errors.store_tracking_id"
                        />
                    </div>
                    <div class="">
                        <InputLabel id="packageStatus" value="Status" />
                        <SearchableSelect
                            id="packageStatus"
                            :options="packageStatus"
                            :reduce="(option) => option.id"
                            label="name"
                            v-model="form.status"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.tracking_no"
                        />
                    </div>
                    <div v-if="form.status === 1" class="col-span-2">
                        <InputLabel
                            for="note"
                            value="Package Note (Required)"
                        />
                        <TextInput
                            v-model="form.note"
                            id="note"
                            rows="3"
                            class="w-full p-2 border rounded"
                            placeholder="Enter special note or requested action"
                        />
                        <InputError class="mt-2" :message="form.errors.note" />
                    </div>

                    <div class="col-span-2 mt-4">
                        <h2 class="text-lg font-semibold">
                            Sender Information
                        </h2>
                    </div>

                    <div class="col-span-2">
                        <InputLabel for="customer" value="Select Customer" />
                        <SearchableSelect
                            id="customer_id"
                            class="w-full mt-1"
                            label="suite"
                            :options="props.customers"
                            :reduce="(option) => option.id"
                            v-model="form.customer_id"
                            placeholder="Search by name, suite #, or email..."
                            :filter-by="(option, label, search) => {
                                const query = search.toLowerCase();
                                return (
                                    (option.name && option.name.toLowerCase().includes(query)) ||
                                    (option.email && option.email.toLowerCase().includes(query)) ||
                                    (option.suite && String(option.suite).toLowerCase().includes(query))
                                );
                            }"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.customer_id"
                        />

                        <div
                            v-if="selectedCustomer"
                            class="p-4 mt-4 border rounded-lg bg-gray-50"
                        >
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <p>
                                    <strong>Suite:</strong>
                                    {{ selectedCustomer?.suite ?? "N/A" }}
                                </p>
                                <p>
                                    <strong>Name:</strong>
                                    {{ selectedCustomer?.name ?? "N/A" }}
                                </p>
                                <p>
                                    <strong>Email:</strong>
                                    {{ selectedCustomer?.email ?? "N/A" }}
                                </p>
                                <p>
                                    <strong>Phone:</strong>
                                    {{ selectedCustomer?.phone ?? "N/A" }}
                                </p>
                                <p>
                                    <strong>City:</strong>
                                    {{ selectedCustomer?.city ?? "N/A" }}
                                </p>
                                <p>
                                    <strong>State:</strong>
                                    {{ selectedCustomer?.state ?? "N/A" }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-2" hidden>
                        <InputLabel for="files" value="Upload Package Files" />
                        <TextInput
                            type="file"
                            @change="handleFileChange"
                            class="w-full"
                            multiple
                        />
                        <div
                            class="grid py-4 sm:text-center sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3"
                        >
                            <div
                                class="w-40"
                                v-for="file in editPackage.files"
                                :key="file?.id"
                            >
                                <img :src="file.file_with_url" width="100%" />
                            </div>
                        </div>
                    </div>

                    <div class="col-span-2">
                        <!-- Debug Info - Remove in production -->
                        <div class="mb-2 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs">
                            <strong>Debug Info:</strong> Package ID: {{ editPackage?.id || 'N/A' }} | 
                            Has Invoice Files: {{ hasInvoiceFiles ? 'Yes' : 'No' }} | 
                            Items Count: {{ editPackage?.items?.length || 0 }}
                        </div>
                        
                        <div class="flex justify-between items-center mb-4">
                            <h1 class="text-2xl">Invoices</h1>
                            <button 
                                type="button"
                                @click="handleDownloadMasterPDF"
                                :class="[
                                    'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-colors shadow-sm',
                                    hasInvoiceFiles && editPackage?.id
                                        ? 'bg-blue-600 hover:bg-blue-700 text-white cursor-pointer' 
                                        : 'bg-gray-300 text-gray-500 cursor-not-allowed opacity-50'
                                ]"
                                :disabled="!hasInvoiceFiles || !editPackage?.id"
                                :title="hasInvoiceFiles && editPackage?.id ? 'Download all invoice files as a single merged PDF' : 'No invoice files found or package ID missing'"
                            >
                                <i class="fa-solid fa-file-pdf"></i>
                                <span>Download Master PDF</span>
                            </button>
                        </div>
                        <div
                            class="grid py-4 sm:text-center sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-5"
                        >
                            <!-- Display item-level invoices -->
                            <template v-for="item in editPackage.items" :key="'item-' + item.id">
                                <div
                                    v-for="invoiceFile in (item.invoice_files || item.invoiceFiles || [])"
                                    :key="invoiceFile.id"
                                    class="w-40 mt-2"
                                >
                                    <a
                                        v-if="invoiceFile.file_type === 'pdf'"
                                        :href="invoiceFile.file_with_url || `/storage/${invoiceFile.file}`"
                                        target="_blank"
                                        class="flex items-center justify-center w-full h-32 bg-gray-100 border rounded-lg hover:bg-gray-200"
                                    >
                                        <i class="text-4xl text-gray-500 fa-solid fa-file-pdf"></i>
                                    </a>
                                    <img
                                        v-else
                                        :src="invoiceFile.file_with_url || `/storage/${invoiceFile.file}`"
                                        width="100%"
                                        height="100%"
                                        class="object-cover rounded"
                                    />
                                    <p class="mt-1 text-xs text-gray-500 truncate">{{ item.title }}</p>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="col-span-2 mt-4">
                        <h2 class="text-lg font-semibold">Add package items</h2>
                        <div class="mt-2 text-end">
                            <PrimaryButton
                                type="button"
                                @click="addItem"
                                class="bg-green-600 hover:bg-green-700"
                            >
                                + Add More Items
                            </PrimaryButton>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="relative grid grid-cols-1 gap-4 p-4 mb-4 border rounded sm:grid-cols-1 md:grid-cols-3 lg:grid-cols-4"
                        >
                            <div class="text-right col-span-full">
                                <button
                                    v-if="form.items.length > 1"
                                    type="button"
                                    @click="removeItem(index)"
                                    class="absolute text-sm text-red-600 top-2 right-2"
                                >
                                    Remove
                                </button>
                            </div>

                            <!-- Existing fields -->
                            <div>
                                <InputLabel
                                    :for="'title' + index"
                                    value="Title"
                                />
                                <TextInput
                                    v-model="item.title"
                                    type="text"
                                    class="w-full"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    :for="'description' + index"
                                    value="Description"
                                />
                                <TextInput
                                    v-model="item.description"
                                    type="text"
                                    class="w-full"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    :for="'note' + index"
                                    value="Note"
                                />
                                <TextInput
                                    v-model="item.item_note"
                                    type="text"
                                    class="w-full"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    :for="'quantity' + index"
                                    value="Quantity"
                                />
                                <TextInput
                                    v-model.number="item.quantity"
                                    type="number"
                                    class="w-full"
                                />
                            </div>
                            <div>
                                <InputLabel
                                    :for="'valuePerUnit' + index"
                                    value="Value / Unit"
                                />

                                <div
                                    class="flex items-center gap-2 px-3 bg-gray-200 border rounded-md h-11"
                                >
                                    <div class="pr-2 border-r border-gray-400">
                                        <i
                                            class="text-gray-700 fa-solid fa-dollar-sign"
                                        ></i>
                                    </div>

                                    <TextInput
                                        :id="'valuePerUnit' + index"
                                        v-model.number="item.value_per_unit"
                                        type="number"
                                        step="any"
                                        class="w-full bg-transparent border-none shadow-none outline-none"
                                    />
                                </div>

                                <InputError
                                    class="mt-1"
                                    :message="
                                        form.errors[
                                            `items.${index}.value_per_unit`
                                        ]
                                    "
                                />
                            </div>

                            <div>
                                <InputLabel
                                    :for="'totalLineValue' + index"
                                    value="Total line value"
                                />
                                <div
                                    class="flex items-center gap-2 px-3 bg-gray-200 border rounded-md h-11"
                                >
                                    <div class="pr-2 border-r border-gray-400">
                                        <i
                                            class="text-gray-700 fa-solid fa-dollar-sign"
                                        ></i>
                                    </div>
                                    <TextInput
                                        v-model="item.total_line_value"
                                        readonly
                                        step="any"
                                        class="w-full bg-gray-200"
                                    />
                                </div>
                            </div>

                            <div>
                                <InputLabel
                                    :for="'weight' + index"
                                    value="Weight in lbs"
                                />
                                <TextInput
                                    v-model.number="item.total_line_weight"
                                    type="number"
                                    class="w-full"
                                    step="any"
                                />
                            </div>

                            <div class="col-span-full">
                                <div
                                    class="flex flex-col items-center gap-4 md:flex-row"
                                >
                                    <div class="w-full">
                                        <InputLabel
                                            :for="'itemImages' + index"
                                            value="Item Images"
                                        />
                                        <div class="mb-4">
                                            <input
                                                type="file"
                                                multiple
                                                accept="image/jpeg,image/png,image/webp,image/gif,image/bmp,image/tiff"
                                                @change="
                                                    (e) =>
                                                        handleItemFileChange(
                                                            e,
                                                            index
                                                        )
                                                "
                                                class="w-full p-2"
                                            />
                                            <p
                                                class="mt-1 text-sm text-gray-600"
                                            >
                                                Accepted formats: JPEG, PNG,
                                                WebP (max 2MB each)
                                            </p>
                                        </div>
                                    </div>
                                    <div class="w-full h-full">
                                        <CameraCapture
                                            @add-photo="
                                                (file) =>
                                                    addCameraPhoto(index, file)
                                            "
                                            :isPreview="true"
                                        />
                                    </div>
                                </div>

                                <!-- Image Previews -->
                                <div
                                    class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6"
                                >
                                    <!-- Existing Images from DB -->
                                    <div
                                        v-for="(
                                            file, fIndex
                                        ) in item.package_files"
                                        :key="`existing-${fIndex}`"
                                        class="relative group"
                                    >
                                        <div
                                            class="w-full h-32 overflow-hidden bg-gray-100 border rounded-lg"
                                        >
                                            <img
                                                :src="file.file_with_url"
                                                :alt="file.name"
                                                class="object-cover w-full h-full"
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            @click="
                                                removeItemFile(
                                                    index,
                                                    fIndex,
                                                    true
                                                )
                                            "
                                            class="absolute flex items-center justify-center w-6 h-6 text-xs text-white transition-opacity bg-red-500 rounded-full opacity-0 -top-2 -right-2 group-hover:opacity-100"
                                            title="Delete image"
                                        >
                                            ✕
                                        </button>
                                        <p
                                            class="mt-1 text-xs text-gray-600 truncate"
                                            :title="file.name"
                                        >
                                            {{ file.name }}
                                        </p>
                                    </div>

                                    <div
                                        v-for="(file, fIndex) in item.id
                                            ? item.new_files
                                            : item.files"
                                        :key="`new-${fIndex}`"
                                        class="relative group"
                                    >
                                        <div
                                            class="w-full h-32 overflow-hidden bg-gray-100 border rounded-lg"
                                        >
                                            {{ file }}
                                            <img
                                                :src="URL.createObjectURL(file)"
                                                :alt="file.name"
                                                class="object-cover w-full h-full"
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            @click="
                                                removeItemFile(
                                                    index,
                                                    fIndex,
                                                    false
                                                )
                                            "
                                            class="absolute flex items-center justify-center w-6 h-6 text-xs text-white transition-opacity bg-red-500 rounded-full opacity-0 -top-2 -right-2 group-hover:opacity-100"
                                            title="Remove image"
                                        >
                                            ✕
                                        </button>
                                        <p
                                            class="mt-1 text-xs text-gray-600 truncate"
                                            :title="file.name"
                                        >
                                            {{ file.name }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Image Count Summary -->
                                <div class="mt-2 text-sm text-gray-600">
                                    <span v-if="item.package_files.length > 0">
                                        {{ item.package_files.length }} existing
                                        image{{
                                            item.package_files.length !== 1
                                                ? "s"
                                                : ""
                                        }}
                                    </span>
                                    <span
                                        v-if="
                                            (item.id
                                                ? item.new_files
                                                : item.files
                                            ).length > 0
                                        "
                                    >
                                        <span
                                            v-if="item.package_files.length > 0"
                                        >
                                            +
                                        </span>
                                        {{
                                            (item.id
                                                ? item.new_files
                                                : item.files
                                            ).length
                                        }}
                                        new image{{
                                            (item.id
                                                ? item.new_files
                                                : item.files
                                            ).length !== 1
                                                ? "s"
                                                : ""
                                        }}
                                    </span>
                                    <span
                                        v-if="
                                            item.package_files.length === 0 &&
                                            (item.id
                                                ? item.new_files
                                                : item.files
                                            ).length === 0
                                        "
                                    >
                                        No images uploaded
                                    </span>
                                </div>
                            </div>

                            <div class="col-span-full"></div>
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Total Price" />

                        <div
                            class="flex items-center gap-2 px-3 bg-gray-200 border rounded-md h-11"
                        >
                            <div class="pr-2 border-r border-gray-400">
                                <i
                                    class="text-gray-700 fa-solid fa-dollar-sign"
                                ></i>
                            </div>
                            <TextInput
                                :value="Number(form.totalPrice).toFixed(2)"
                                readonly
                                class="w-full bg-transparent border-none shadow-none outline-none"
                                type="text"
                            />
                        </div>
                    </div>

                    <div class="">
                        <InputLabel value="Total Weight in lbs" />
                        <TextInput
                            :value="form.totalWeight"
                            readonly
                            class="w-full bg-gray-200"
                            step="any"
                        />
                    </div>

                    <div
                        class="flex items-center justify-end col-span-2 gap-2 text-end"
                    >
                        <Delete v-if="can('packages.delete')" @click.prevent.stop :id="editPackage.id">
                            Delete
                        </Delete>
                        <PrimaryButton
                            type="submit"
                            class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700"
                            :disabled="form.processing"
                        >
                            Update package
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </form>
    </EditLayout>
</template>
