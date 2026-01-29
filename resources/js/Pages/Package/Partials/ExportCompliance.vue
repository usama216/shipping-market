<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    form: Object,
});

const incotermOptions = [
    { value: 'DAP', label: 'DAP - Delivered at Place' },
    { value: 'DDP', label: 'DDP - Delivered Duty Paid' },
    { value: 'EXW', label: 'EXW - Ex Works' },
    { value: 'FOB', label: 'FOB - Free On Board' },
    { value: 'CIF', label: 'CIF - Cost, Insurance and Freight' },
];

const usFilingTypeOptions = [
    { value: '30.37(a) - Under $2,500', label: '30.37(a) - Under $2,500' },
    { value: '30.36 - Canada/Mexico Low Value', label: '30.36 - Canada/Mexico Low Value' },
    { value: 'ITN Required (enter below)', label: 'ITN Required (enter below)' },
];

const exporterCodeOptions = [
    { value: 'EXPCZ', label: 'EXPCZ - Export Czech Rep' },
    { value: 'EXPUS', label: 'EXPUS - Export United States' },
    { value: '', label: 'Other (enter below)' },
];

// Initialize default values if not set
if (!props.form.incoterm) {
    props.form.incoterm = 'DAP';
}
if (!props.form.invoice_signature_name) {
    props.form.invoice_signature_name = 'Authorized Shipper';
}
if (!props.form.exporter_id_license) {
    props.form.exporter_id_license = 'EAR99';
}
if (!props.form.us_filing_type) {
    props.form.us_filing_type = '30.37(a) - Under $2,500';
}
</script>

<template>
    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl">
        <div class="flex items-center gap-3 mb-6">
            <div class="flex items-center justify-center w-10 h-10 text-blue-600 bg-blue-50 rounded-xl">
                <i class="fa-solid fa-file-export text-lg"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-800">Export Compliance</h2>
                <p class="text-sm text-gray-500">Required for DHL/International shipments</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Incoterm -->
            <div>
                <InputLabel value="Incoterm" />
                <select
                    v-model="form.incoterm"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option v-for="option in incotermOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="form.errors.incoterm" class="mt-1" />
            </div>

            <!-- Invoice Signature Name -->
            <div>
                <InputLabel value="Invoice Signature Name" />
                <TextInput
                    v-model="form.invoice_signature_name"
                    class="mt-1 w-full"
                    placeholder="Authorized Shipper"
                />
                <InputError :message="form.errors.invoice_signature_name" class="mt-1" />
            </div>

            <!-- Exporter ID / License -->
            <div>
                <InputLabel value="Exporter ID / License" />
                <TextInput
                    v-model="form.exporter_id_license"
                    class="mt-1 w-full"
                    placeholder="EAR99"
                />
                <p class="mt-1 text-xs text-gray-500">EAR99 = No license required</p>
                <InputError :message="form.errors.exporter_id_license" class="mt-1" />
            </div>

            <!-- US Filing Type -->
            <div>
                <InputLabel value="US Filing Type" />
                <select
                    v-model="form.us_filing_type"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option v-for="option in usFilingTypeOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="form.errors.us_filing_type" class="mt-1" />
            </div>

            <!-- Exporter Code -->
            <div>
                <InputLabel value="Exporter Code" />
                <select
                    v-model="form.exporter_code"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
                    <option value="">Select or enter below</option>
                    <option v-for="option in exporterCodeOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
                <TextInput
                    v-if="form.exporter_code === ''"
                    v-model="form.exporter_code"
                    class="mt-2 w-full"
                    placeholder="Enter exporter code"
                />
                <InputError :message="form.errors.exporter_code" class="mt-1" />
            </div>

            <!-- ITN Number (conditional) -->
            <div v-if="form.us_filing_type === 'ITN Required (enter below)'">
                <InputLabel value="ITN Number" />
                <TextInput
                    v-model="form.itn_number"
                    class="mt-1 w-full"
                    placeholder="Enter ITN number"
                />
                <InputError :message="form.errors.itn_number" class="mt-1" />
            </div>
        </div>

        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-xs text-blue-800">
                <i class="fa-solid fa-info-circle mr-1"></i>
                <strong>Note:</strong> These fields are required for generating commercial invoices and DHL API compliance. 
                Edit these fields if carrier submission fails due to export compliance errors.
            </p>
        </div>
    </div>
</template>
