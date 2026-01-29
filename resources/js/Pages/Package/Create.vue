<script setup>
import { Head, useForm, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { watch, computed } from "vue";

// Partials
import SenderInfo from "./Partials/SenderInfo.vue";
import PackageDetails from "./Partials/PackageDetails.vue";
import PackageItems from "./Partials/PackageItems.vue";
import PackageSummary from "./Partials/PackageSummary.vue";
import ExportCompliance from "./Partials/ExportCompliance.vue";

const props = defineProps({
    users: Array,
    warehouses: {
        type: Array,
        default: () => [],
    },
    defaultWarehouseId: {
        type: Number,
        default: null,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    from: "",
    date: null,
    customer_id: null,
    warehouse_id: props.defaultWarehouseId,
    // Package dimensions for carrier volumetric pricing
    length: null,
    width: null,
    height: null,
    dimension_unit: "in",
    weight_unit: "lb",
    package_type: "YOUR_PACKAGING",
    items: [
        {
            title: "",
            description: "",
            hs_code: "",
            material: "",
            item_note: "",
            quantity: 1,
            value_per_unit: 0,
            weight_per_unit: 0,
            weight_unit: "lb",
            total_line_value: 0,
            total_line_weight: 0,
            // Item dimensions
            length: null,
            width: null,
            height: null,
            dimension_unit: "in",
            // Item classification
            is_dangerous: false,
            un_code: "",
            dangerous_goods_class: "",
            is_fragile: false,
            is_oversized: false,
            classification_notes: "",
            files: [],
            preview: [],
            invoice_files: [],
            invoice_previews: [],
            skip_action_required: false,
        },
    ],
    totalPrice: 0,
    totalWeight: 0,
    store_tracking_id: "",
    // Export compliance fields
    incoterm: "DAP",
    invoice_signature_name: "Authorized Shipper",
    exporter_id_license: "EAR99",
    us_filing_type: "30.37(a) - Under $2,500",
    exporter_code: "",
    itn_number: "",
});

// Watchers to keep form totals in sync
const calculatedTotals = computed(() => {
    const price = form.items.reduce((sum, item) => sum + (item.quantity * item.value_per_unit), 0);
    const weight = form.items.reduce((sum, item) => sum + (item.quantity * (parseFloat(item.weight_per_unit) || 0)), 0);
    return { price, weight };
});

watch(calculatedTotals, (newTotals) => {
    form.totalPrice = parseFloat(newTotals.price.toFixed(2));
    // Only auto-update weight if no manual override (check if current weight matches previous calculated)
    // For simplicity, we'll auto-update unless user has manually set a different value
    if (form.totalWeight === 0 || form.totalWeight === calculatedTotals.value.weight) {
        form.totalWeight = parseFloat(newTotals.weight.toFixed(2));
    }
});

const submitForm = () => {
    if (form.date) {
        form.date = new Date(form.date).toLocaleString("en-US");
    }

    form.transform((data) => {
        const payload = new FormData();

        payload.append("from", data.from);
        payload.append("date_received", data.date || '');
        payload.append("customer_id", data.customer_id);
        if (data.warehouse_id) payload.append("warehouse_id", data.warehouse_id);
        payload.append("total_value", data.totalPrice);
        payload.append("weight", data.totalWeight);
        payload.append("store_tracking_id", data.store_tracking_id);
        
        // Package dimensions
        if (data.length) payload.append("length", data.length);
        if (data.width) payload.append("width", data.width);
        if (data.height) payload.append("height", data.height);
        payload.append("dimension_unit", data.dimension_unit);
        payload.append("weight_unit", data.weight_unit);
        payload.append("package_type", data.package_type);
        
        // Export compliance fields
        payload.append("incoterm", data.incoterm || "DAP");
        payload.append("invoice_signature_name", data.invoice_signature_name || "Authorized Shipper");
        payload.append("exporter_id_license", data.exporter_id_license || "EAR99");
        payload.append("us_filing_type", data.us_filing_type || "30.37(a) - Under $2,500");
        if (data.exporter_code) payload.append("exporter_code", data.exporter_code);
        if (data.itn_number) payload.append("itn_number", data.itn_number);

        // Items
        data.items.forEach((item, itemIndex) => {
            payload.append(`items[${itemIndex}][title]`, item.title);
            payload.append(`items[${itemIndex}][description]`, item.description);
            payload.append(`items[${itemIndex}][hs_code]`, item.hs_code || "");
            payload.append(`items[${itemIndex}][material]`, item.material || "");
            payload.append(`items[${itemIndex}][un_code]`, item.un_code || "");
            payload.append(`items[${itemIndex}][item_note]`, item.item_note);
            payload.append(`items[${itemIndex}][quantity]`, item.quantity);
            payload.append(`items[${itemIndex}][value_per_unit]`, item.value_per_unit);
            payload.append(`items[${itemIndex}][weight_per_unit]`, item.weight_per_unit || 0);
            payload.append(`items[${itemIndex}][weight_unit]`, item.weight_unit || 'lb');
            payload.append(`items[${itemIndex}][total_line_value]`, item.quantity * item.value_per_unit);
            payload.append(`items[${itemIndex}][total_line_weight]`, item.quantity * (item.weight_per_unit || 0));
            
            // Item dimensions
            if (item.length) payload.append(`items[${itemIndex}][length]`, item.length);
            if (item.width) payload.append(`items[${itemIndex}][width]`, item.width);
            if (item.height) payload.append(`items[${itemIndex}][height]`, item.height);
            payload.append(`items[${itemIndex}][dimension_unit]`, item.dimension_unit);
            
            // Item classification
            payload.append(`items[${itemIndex}][is_dangerous]`, item.is_dangerous ? 1 : 0);
            payload.append(`items[${itemIndex}][un_code]`, item.un_code || "");
            payload.append(`items[${itemIndex}][dangerous_goods_class]`, item.dangerous_goods_class || "");
            payload.append(`items[${itemIndex}][is_fragile]`, item.is_fragile ? 1 : 0);
            payload.append(`items[${itemIndex}][is_oversized]`, item.is_oversized ? 1 : 0);
            if (item.classification_notes) {
                payload.append(`items[${itemIndex}][classification_notes]`, item.classification_notes);
            }

            if (item.files && item.files.length > 0) {
                Array.from(item.files).forEach((file, fileIndex) => {
                    payload.append(`items[${itemIndex}][files][${fileIndex}]`, file);
                });
            }
        });

        // Invoices
        data.invoices.forEach((invoice, invoiceIndex) => {
            payload.append(`invoices[${invoiceIndex}][type]`, invoice.type || 'received');
            if (invoice.invoice_number) payload.append(`invoices[${invoiceIndex}][invoice_number]`, invoice.invoice_number);
            if (invoice.vendor_name) payload.append(`invoices[${invoiceIndex}][vendor_name]`, invoice.vendor_name);
            if (invoice.invoice_date) payload.append(`invoices[${invoiceIndex}][invoice_date]`, invoice.invoice_date);
            if (invoice.invoice_amount) payload.append(`invoices[${invoiceIndex}][invoice_amount]`, invoice.invoice_amount);
            if (invoice.notes) payload.append(`invoices[${invoiceIndex}][notes]`, invoice.notes);
            // Handle multiple files per invoice
            if (invoice.files && invoice.files.length > 0) {
                invoice.files.forEach((file, fileIndex) => {
                    payload.append(`invoices[${invoiceIndex}][files][${fileIndex}]`, file);
                });
            }
        });

        return payload;
    }).post(route("admin.packages.store"), {
        preserveScroll: true,
        onSuccess: () => console.log("Package created successfully 🚀"),
    });
};

// Save as Draft - minimal validation
const saveDraft = () => {
    form.transform((data) => {
        const payload = new FormData();
        
        // Mark as draft
        payload.append("is_draft", "1");
        
        // Only include basic info
        payload.append("from", data.from || "Draft");
        payload.append("date_received", data.date_received || new Date().toISOString().split('T')[0]);
        payload.append("customer_id", data.customer_id || "");
        if (data.warehouse_id) payload.append("warehouse_id", data.warehouse_id);
        payload.append("total_value", data.totalPrice || 0);
        payload.append("weight", data.totalWeight || 0);
        payload.append("store_tracking_id", data.store_tracking_id || "");
        
        // Package dimensions
        if (data.length) payload.append("length", data.length);
        if (data.width) payload.append("width", data.width);
        if (data.height) payload.append("height", data.height);
        payload.append("dimension_unit", data.dimension_unit);
        payload.append("weight_unit", data.weight_unit);
        payload.append("package_type", data.package_type);

        // Items - include even partial items
        data.items.forEach((item, itemIndex) => {
            if (item.title || item.description || item.quantity > 1 || item.value_per_unit > 0) {
                payload.append(`items[${itemIndex}][title]`, item.title || "Untitled Item");
                payload.append(`items[${itemIndex}][description]`, item.description || "");
                payload.append(`items[${itemIndex}][hs_code]`, item.hs_code || "");
                payload.append(`items[${itemIndex}][eei_code]`, item.eei_code || "");
                payload.append(`items[${itemIndex}][material]`, item.material || "");
                payload.append(`items[${itemIndex}][item_note]`, item.item_note || "");
                payload.append(`items[${itemIndex}][quantity]`, item.quantity || 1);
                payload.append(`items[${itemIndex}][value_per_unit]`, item.value_per_unit || 0);
                payload.append(`items[${itemIndex}][weight_per_unit]`, item.weight_per_unit || 0);
                payload.append(`items[${itemIndex}][weight_unit]`, item.weight_unit || 'lb');
                payload.append(`items[${itemIndex}][total_line_value]`, (item.quantity || 1) * (item.value_per_unit || 0));
                payload.append(`items[${itemIndex}][total_line_weight]`, (item.quantity || 1) * (item.weight_per_unit || 0));
                payload.append(`items[${itemIndex}][dimension_unit]`, item.dimension_unit);
                payload.append(`items[${itemIndex}][is_dangerous]`, item.is_dangerous ? 1 : 0);
                payload.append(`items[${itemIndex}][un_code]`, item.un_code || "");
                payload.append(`items[${itemIndex}][dangerous_goods_class]`, item.dangerous_goods_class || "");
                payload.append(`items[${itemIndex}][is_fragile]`, item.is_fragile ? 1 : 0);
                payload.append(`items[${itemIndex}][is_oversized]`, item.is_oversized ? 1 : 0);
                
                // Item files
                if (item.files?.length > 0) {
                    item.files.forEach((file, fileIdx) => {
                        payload.append(`items[${itemIndex}][files][${fileIdx}]`, file);
                    });
                }
            }
        });

        return payload;
    }).post(route("admin.packages.store"), {
        preserveScroll: true,
        onSuccess: () => console.log("Package saved as draft 📝"),
    });
};
</script>

<template>
    <Head title="Create Package" />
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
                            <h1 class="text-2xl font-bold text-gray-900">Create New Package</h1>
                            <p class="text-sm text-gray-500 mt-1">Log an incoming shipment to the warehouse</p>
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

                <form @submit.prevent="submitForm" class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    <!-- Main Content (Left) -->
                    <div class="lg:col-span-8 space-y-6">
                        <SenderInfo :form="form" :users="users" :warehouses="warehouses" />
                        <PackageDetails :form="form" />
                        <PackageItems :form="form" />
                        <ExportCompliance :form="form" />
                    </div>

                    <!-- Sidebar (Right) -->
                    <div class="lg:col-span-4">
                        <PackageSummary :form="form" @save-draft="saveDraft" />
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
