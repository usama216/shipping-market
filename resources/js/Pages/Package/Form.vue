<script setup>
import { Head, useForm, Link, router } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { watch, computed } from "vue";

// Partials
import SenderInfo from "./Partials/SenderInfo.vue";
import PackageDetails from "./Partials/PackageDetails.vue";
import PackageItems from "./Partials/PackageItems.vue";
import PackageSummary from "./Partials/PackageSummary.vue";
import ExportCompliance from "./Partials/ExportCompliance.vue";

const props = defineProps({
    // For Edit mode - existing package data
    package: {
        type: Object,
        default: null,
    },
    // Common props
    users: Array,
    customers: Array, // Alias for users in edit mode
    warehouses: {
        type: Array,
        default: () => [],
    },
    defaultWarehouseId: {
        type: Number,
        default: null,
    },
    pendingPackageId: {
        type: String,
        default: null,
    },
    status: {
        type: String,
    },
});

// Determine mode
const isEditMode = computed(() => !!props.package);
const editPackage = props.package;

// Helper to get default item structure
const getDefaultItem = () => ({
    id: null,
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
    dangerous_goods_class: "",
    is_fragile: false,
    is_oversized: false,
    classification_notes: "",
    files: [],
    preview: [],
    // Edit mode specific
    package_files: [],
    new_files: [],
    delete_file_ids: [],
    // Invoice
    invoice_files: [],
    invoice_previews: [],
    skip_action_required: false,
});

// Initialize form with edit data if available
// Field names match database columns exactly for seamless Inertia submission
const form = useForm({
    from: editPackage?.from || "",
    date_received: editPackage?.date_received || null,
    customer_id: editPackage?.customer_id || null,
    warehouse_id: editPackage?.warehouse_id || props.defaultWarehouseId,
    // Package dimensions
    length: editPackage?.length || null,
    width: editPackage?.width || null,
    height: editPackage?.height || null,
    dimension_unit: editPackage?.dimension_unit || "in",
    weight_unit: editPackage?.weight_unit || "lb",
    package_type: editPackage?.package_type || "YOUR_PACKAGING",
    // Totals - named to match database
    total_value: editPackage?.total_value || 0,
    weight: editPackage?.weight || 0,
    store_tracking_id: editPackage?.store_tracking_id || "",
    // Items - map existing or create default
    items: editPackage?.items?.map((item) => ({
        id: item.id,
        title: item.title || "",
        description: item.description || "",
        hs_code: item.hs_code || "",
        material: item.material || "",
        un_code: item.un_code || "",
        quantity: item.quantity || 1,
        value_per_unit: item.value_per_unit || 0,
        weight_per_unit: item.weight_per_unit || 0,
        weight_unit: item.weight_unit || "lb",
        total_line_value: item.total_line_value || 0,
        total_line_weight: item.total_line_weight || 0,
        length: item.length || null,
        width: item.width || null,
        height: item.height || null,
        dimension_unit: item.dimension_unit || "in",
        is_dangerous: item.is_dangerous || false,
        un_code: item.un_code || "",
        dangerous_goods_class: item.dangerous_goods_class || "",
        is_fragile: item.is_fragile || false,
        is_oversized: item.is_oversized || false,
        classification_notes: item.classification_notes || "",
        package_files: item.package_files || [],
        files: [],
        preview: [],
        new_files: [],
        delete_file_ids: [],
        // Item-level invoice files
        invoice_files_existing: item.invoice_files || [],
        invoice_files: [],
        invoice_previews: [],
        delete_invoice_file_ids: [],
        skip_action_required: item.skip_action_required || false,
    })) || [getDefaultItem()],

    // Edit mode specific
    status: editPackage?.status || null,
    note: editPackage?.note || "",
    // Draft flag
    is_draft: false,
    // Export compliance fields
    incoterm: editPackage?.incoterm || "DAP",
    invoice_signature_name: editPackage?.invoice_signature_name || "Authorized Shipper",
    exporter_id_license: editPackage?.exporter_id_license || "EAR99",
    us_filing_type: editPackage?.us_filing_type || "30.37(a) - Under $2,500",
    exporter_code: editPackage?.exporter_code || "",
    itn_number: editPackage?.itn_number || "",
});

// Combined users list (for compatibility)
const usersList = computed(() => props.users || props.customers || []);

// Helper to format date as YYYY-MM-DD string
const formatDate = (date) => {
    if (!date) return null;
    const d = new Date(date);
    if (isNaN(d.getTime())) return null;
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

// Watchers to keep form totals in sync
const calculatedTotals = computed(() => {
    const price = form.items.reduce((sum, item) => sum + (item.quantity * item.value_per_unit), 0);
    const weight = form.items.reduce((sum, item) => sum + (item.quantity * (parseFloat(item.weight_per_unit) || 0)), 0);
    return { price, weight };
});

watch(calculatedTotals, (newTotals) => {
    form.total_value = parseFloat(newTotals.price.toFixed(2));
    // Only auto-update weight if it was 0 or equals the calculated value
    if (form.weight === 0 || form.weight === calculatedTotals.value.weight) {
        form.weight = parseFloat(newTotals.weight.toFixed(2));
    }
});

// Form submission state
const isSubmitting = computed(() => form.processing);

// Submit form using Inertia's native form.post()
const submitForm = () => {
    const url = isEditMode.value 
        ? route("admin.packages.update", props.package.id) 
        : route("admin.packages.store");
    
    form.transform((data) => {
        const payload = {
            from: data.from,
            date_received: formatDate(data.date_received),
            customer_id: data.customer_id,
            warehouse_id: data.warehouse_id,
            length: data.length,
            width: data.width,
            height: data.height,
            dimension_unit: data.dimension_unit,
            weight_unit: data.weight_unit,
            package_type: data.package_type,
            total_value: data.total_value,
            weight: data.weight,
            store_tracking_id: data.store_tracking_id,
            is_draft: data.is_draft,
            // Export compliance fields
            incoterm: data.incoterm || "DAP",
            invoice_signature_name: data.invoice_signature_name || "Authorized Shipper",
            exporter_id_license: data.exporter_id_license || "EAR99",
            us_filing_type: data.us_filing_type || "30.37(a) - Under $2,500",
            exporter_code: data.exporter_code || "",
            itn_number: data.itn_number || "",
            // Items WITH files - Inertia handles nested files with forceFormData
            items: data.items.map((item, idx) => ({
                id: item.id,
                title: item.title,
                description: item.description,
                hs_code: item.hs_code,
                eei_code: item.eei_code || "",
                material: item.material,
                quantity: item.quantity,
                value_per_unit: item.value_per_unit,
                weight_per_unit: item.weight_per_unit,
                weight_unit: item.weight_unit,
                total_line_value: (item.quantity || 1) * (item.value_per_unit || 0),
                total_line_weight: (item.quantity || 1) * (item.weight_per_unit || 0),
                length: item.length,
                width: item.width,
                height: item.height,
                dimension_unit: item.dimension_unit,
                is_dangerous: item.is_dangerous ? 1 : 0,
                un_code: item.un_code || "",
                dangerous_goods_class: item.dangerous_goods_class || "",
                is_fragile: item.is_fragile ? 1 : 0,
                is_oversized: item.is_oversized ? 1 : 0,
                classification_notes: item.classification_notes,
                delete_file_ids: item.delete_file_ids || [],
                // Include item photos
                files: item.files || [],
                // Include item-level invoice files
                delete_invoice_file_ids: item.delete_invoice_file_ids || [],
                invoice_files: item.invoice_files || [],
            })),
        };
        
        // Only include status/note in edit mode
        if (isEditMode.value) {
            payload._method = 'put'; // Method spoofing for multipart/form-data file uploads
            // Always include status in edit mode (backend expects it)
            payload.status = parseInt(data.status) || data.status;
            payload.note = data.note;
        } else {
            // Include pre-generated package ID in create mode
            if (props.pendingPackageId) {
                payload.pending_package_id = props.pendingPackageId;
            }
        }
        
        // Debug: Log the payload to see if files are present
        console.log('📦 Submitting payload:', payload);
        console.log('📎 Items with files:', payload.items.map((item, i) => ({ idx: i, files: item.files?.length || 0 })));
        
        return payload;
    }).post(url, {
        forceFormData: true, // Ensure multipart/form-data for file uploads
        preserveScroll: true,
        onSuccess: () => {
            console.log(isEditMode.value ? "Package updated successfully ✅" : "Package created successfully 🚀");
        },
        onError: (errors) => {
            console.error("Form errors:", errors);
        },
    });
};

// Save as Draft - sets is_draft flag and submits
const saveDraft = () => {
    form.transform((data) => {
        // Filter items first to get consistent indices
        const filteredItems = data.items.filter(item => 
            item.title || item.description || item.quantity > 1 || item.value_per_unit > 0
        );
        
        const payload = {
            is_draft: true,
            from: data.from || "Draft",
            date_received: formatDate(data.date_received) || formatDate(new Date()),
            customer_id: data.customer_id,
            warehouse_id: data.warehouse_id,
            length: data.length,
            width: data.width,
            height: data.height,
            dimension_unit: data.dimension_unit,
            weight_unit: data.weight_unit,
            package_type: data.package_type,
            total_value: data.total_value,
            weight: data.weight,
            store_tracking_id: data.store_tracking_id,
            // Export compliance fields
            incoterm: data.incoterm || "DAP",
            invoice_signature_name: data.invoice_signature_name || "Authorized Shipper",
            exporter_id_license: data.exporter_id_license || "EAR99",
            us_filing_type: data.us_filing_type || "30.37(a) - Under $2,500",
            exporter_code: data.exporter_code || "",
            itn_number: data.itn_number || "",
            // Items - WITHOUT files (files are flattened to top level)
            items: filteredItems.map((item, idx) => ({
                id: item.id,
                title: item.title || "Untitled Item",
                description: item.description,
                hs_code: item.hs_code,
                eei_code: item.eei_code || "",
                material: item.material,
                quantity: item.quantity,
                value_per_unit: item.value_per_unit,
                weight_per_unit: item.weight_per_unit,
                weight_unit: item.weight_unit,
                total_line_value: (item.quantity || 1) * (item.value_per_unit || 0),
                total_line_weight: (item.quantity || 1) * (item.weight_per_unit || 0),
                length: item.length,
                width: item.width,
                height: item.height,
                dimension_unit: item.dimension_unit,
                is_dangerous: item.is_dangerous ? 1 : 0,
                un_code: item.un_code || "",
                dangerous_goods_class: item.dangerous_goods_class || "",
                is_fragile: item.is_fragile ? 1 : 0,
                is_oversized: item.is_oversized ? 1 : 0,
                classification_notes: item.classification_notes,
                delete_file_ids: item.delete_file_ids || [],
                delete_invoice_file_ids: item.delete_invoice_file_ids || [],
            })),
        };
        
        // Flatten item files to top-level
        filteredItems.forEach((item, idx) => {
            if (item.files && item.files.length > 0) {
                payload[`item_${idx}_files`] = item.files;
            }
            if (item.new_files && item.new_files.length > 0) {
                payload[`item_${idx}_new_files`] = item.new_files;
            }
            // Flatten item invoice files to top-level
            if (item.invoice_files && item.invoice_files.length > 0) {
                payload[`item_${idx}_invoice_files`] = item.invoice_files;
            }
        });
        
        return payload;
    }).post(route("admin.packages.store"), {
        preserveScroll: true,
        onSuccess: () => {
            console.log("Package saved as draft 📝");
        },
        onError: (errors) => {
            console.error("Draft errors:", errors);
        },
    });
};

// Page title and header text
const pageTitle = computed(() => isEditMode.value ? `Edit Package #${props.package?.package_id}` : "Create New Package");
const pageSubtitle = computed(() => isEditMode.value ? "Update package information" : "Log an incoming shipment to the warehouse");
</script>

<template>
    <Head :title="pageTitle" />
    <AuthenticatedLayout>
        <div class="py-8 bg-gray-50/50 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header with Back Button -->
                <div class="flex items-center justify-between mb-8">
                    <div class="flex items-center gap-4">
                        <Link 
                            :href="route('admin.packages')" 
                            class="flex items-center justify-center w-10 h-10 text-gray-500 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 hover:text-gray-700 transition-colors shadow-sm"
                        >
                            <i class="fa-solid fa-arrow-left"></i>
                        </Link>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ pageTitle }}</h1>
                            <p class="text-sm text-gray-500 mt-1">{{ pageSubtitle }}</p>
                        </div>
                    </div>
                    <Link 
                        :href="route('admin.packages')" 
                        class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-2"
                    >
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </Link>
                </div>

                <!-- Status Message -->
                <transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="transform -translate-y-2 opacity-0"
                    enter-to-class="transform translate-y-0 opacity-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="transform translate-y-0 opacity-100"
                    leave-to-class="transform -translate-y-2 opacity-0"
                >
                    <div
                        v-if="status"
                        class="p-4 mb-6 text-sm font-medium text-emerald-700 border border-emerald-200 rounded-xl bg-emerald-50 flex items-center gap-3 shadow-sm"
                    >
                        <i class="fa-solid fa-circle-check text-lg"></i>
                        {{ status }}
                    </div>
                </transition>

                <!-- Validation Errors Summary -->
                <transition
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="transform -translate-y-2 opacity-0"
                    enter-to-class="transform translate-y-0 opacity-100"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="transform translate-y-0 opacity-100"
                    leave-to-class="transform -translate-y-2 opacity-0"
                >
                    <div
                        v-if="Object.keys(form.errors).length > 0"
                        class="p-4 mb-6 text-sm font-medium text-red-700 border border-red-200 rounded-xl bg-red-50 shadow-sm"
                    >
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-triangle-exclamation text-lg mt-0.5"></i>
                            <div>
                                <p class="font-semibold mb-2">Please fix the following errors:</p>
                                <ul class="list-disc list-inside space-y-1 text-red-600">
                                    <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </transition>

                <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                    <div class="lg:col-span-8 space-y-6">
                        <SenderInfo :form="form" :users="usersList" :warehouses="warehouses" />
                        <PackageDetails :form="form" :is-edit-mode="isEditMode" />
                        <PackageItems :form="form" :is-edit-mode="isEditMode" />
                        <ExportCompliance :form="form" />
                    </div>

                    <!-- Sidebar (Right) -->
                    <div class="lg:col-span-4">
                        <PackageSummary 
                            :form="form" 
                            :is-edit-mode="isEditMode"
                            :is-submitting="isSubmitting"
                            :package-id="package?.package_id"
                            :package-db-id="package?.id"
                            :pending-package-id="pendingPackageId"
                            :special-request="package?.special_request"
                            @save-draft="saveDraft" 
                        />
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
