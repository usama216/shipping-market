<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import VueDatePicker from "@vuepic/vue-datepicker";
import SearchableSelect from "vue-select";
import CameraCapture from "@/Components/CameraCapture.vue";

const props = defineProps({
    packageData: { type: Object, default: null },
    customers: { type: Array, default: () => [] },
    suites: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    defaultWarehouseId: { type: Number, default: null },
});

const emit = defineEmits(["close"]);

const isEdit = computed(() => !!props.packageData);

// Form Initialization
const form = useForm({
    from: "",
    date: new Date(),
    customer_id: null,
    warehouse_id: props.defaultWarehouseId,
    tracking_id: "",
    note: "",
    // Unit defaults for items
    dimension_unit: "in",
    weight_unit: "lb",
    
    // Read-only / Calculated totals for display/submission
    total_value: 0, 

    items: [], // Will initialize with one empty item
});

// Initialize form data if editing
onMounted(() => {
    if (isEdit.value) {
        const pkg = props.packageData;
        form.from = pkg.from;
        form.date = pkg.date_received ? new Date(pkg.date_received) : new Date();
        form.customer_id = pkg.customer_id;
        form.warehouse_id = pkg.warehouse_id;
        form.tracking_id = pkg.tracking_id;
        form.note = pkg.note;
        form.dimension_unit = pkg.dimension_unit || 'in';
        form.weight_unit = pkg.weight_unit || 'lb';
        
        // Map items
        // Note: handling file previews for edit might be complex, simplified for now
        form.items = pkg.items ? pkg.items.map(i => ({
            id: i.id,
            title: i.title,
            description: i.description,
            hs_code: i.hs_code,
            country_of_origin: i.country_of_origin,
            material: i.material,
            item_note: i.item_note,
            quantity: i.quantity,
            value_per_unit: i.value_per_unit,
            weight_per_unit: i.weight_per_unit,
            total_line_weight: i.total_line_weight,
            length: i.length,
            width: i.width,
            height: i.height,
            files: [], // New files
            preview: [],
            existing_files: i.files || [] // To show existing
        })) : [];
    } 
    
    if (form.items.length === 0) {
        addItem();
    }
});

const addItem = () => {
    form.items.push({
        title: "",
        description: "",
        hs_code: "",
        country_of_origin: "",
        material: "",
        item_note: "",
        quantity: 1,
        value_per_unit: 0,
        weight_per_unit: 0,
        total_line_weight: 0,
        length: null,
        width: null,
        height: null,
        files: [],
        preview: [],
    });
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

// Computed Totals
const calculatedTotalValue = computed(() => {
    return form.items.reduce((sum, item) => sum + (Number(item.quantity) * Number(item.value_per_unit) || 0), 0);
});

const calculatedTotalWeight = computed(() => {
    return form.items.reduce((sum, item) => sum + (Number(item.total_line_weight) || 0), 0);
});

// Sync calculated values to form
watch(calculatedTotalValue, (val) => form.total_value = val);

// File Handling
const handleFileChange = (e, index) => {
    const files = Array.from(e.target.files);
    files.forEach((file) => {
        form.items[index].files.push(file);
        form.items[index].preview.push(URL.createObjectURL(file));
    });
};

const addCameraPhoto = (index, file) => {
    form.items[index].files.push(file);
    form.items[index].preview.push(URL.createObjectURL(file));
};

const removeImage = (itemIndex, imgIndex) => {
    form.items[itemIndex].preview.splice(imgIndex, 1);
    form.items[itemIndex].files.splice(imgIndex, 1);
};


const submit = () => {
    // Transform date
    if (form.date) form.date_received = new Date(form.date).toISOString(); // Adjust format as backend expects

    if (isEdit.value) {
        form.post(route("admin.packages.update", props.packageData.id), {
            onSuccess: () => emit("close"),
            preserveScroll: true
            // Note: Update usually uses PUT, but with files we often use POST with _method field. 
            // Inertia handles this, but for file uploads + Laravel resource update, we might need a specific strategy.
            // Using POST with _method: 'PUT' is safer for FormData.
        });
    } else {
        form.post(route("admin.packages.store"), {
             onSuccess: () => emit("close"),
             preserveScroll: true
        });
    }
};

const selectedCustomer = computed(() => {
    return props.customers.find((u) => u.id === form.customer_id);
});

</script>

<template>
    <form @submit.prevent="submit" class="h-full flex flex-col">
        <div class="flex-1 overflow-y-auto custom-scrollbar p-1">
            
            <!-- Sender Info -->
            <div class="mb-6 bg-white p-4 rounded-lg border shadow-sm">
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4 border-b pb-2">Sender Information</h3>
                
                <div class="space-y-4">
                    <div>
                        <InputLabel value="From (Sender)" />
                        <TextInput v-model="form.from" class="w-full mt-1" placeholder="Amazon, eBay, etc." />
                        <InputError :message="form.errors.from" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                             <InputLabel value="Date Received" />
                             <VueDatePicker v-model="form.date" :enable-time-picker="false" auto-apply class="w-full mt-1" />
                        </div>
                        <div>
                             <InputLabel value="Tracking Number" />
                             <TextInput v-model="form.tracking_id" class="w-full mt-1" />
                        </div>
                    </div>

                    <div>
                        <InputLabel value="Assign Customer" />
                        <SearchableSelect
                            id="customer_id"
                            class="w-full mt-1"
                            label="suite"
                            :options="customers"
                            :reduce="(option) => option.id"
                            v-model="form.customer_id"
                        >
                           <template #option="{ suite, name }">
                                <span class="font-bold">{{ suite }}</span> - {{ name }}
                           </template>
                        </SearchableSelect>
                        <InputError :message="form.errors.customer_id" />
                    </div>

                    <!-- Customer Details Preview -->
                    <div v-if="selectedCustomer" class="text-xs bg-blue-50 text-blue-800 p-3 rounded border border-blue-100 grid grid-cols-2 gap-2">
                        <span><strong>Name:</strong> {{ selectedCustomer.name }}</span>
                        <span><strong>Suite:</strong> {{ selectedCustomer.suite }}</span>
                        <span class="col-span-2 truncate"><strong>Address:</strong> {{ selectedCustomer.city }}, {{ selectedCustomer.state }}</span>
                    </div>

                    <div v-if="warehouses.length">
                         <InputLabel value="Origin Warehouse" />
                         <select v-model="form.warehouse_id" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                             <option v-for="w in warehouses" :key="w.id" :value="w.id">
                                 {{ w.name }} ({{ w.code }})
                             </option>
                         </select>
                    </div>
                </div>
            </div>

            <!-- Unit Settings -->
             <div class="mb-6 bg-white p-4 rounded-lg border shadow-sm">
                <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider mb-4 border-b pb-2">Measurement Units</h3>
                <div class="grid grid-cols-2 gap-4">
                     <div>
                        <InputLabel value="Dimension Unit" />
                        <select v-model="form.dimension_unit" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="in">Inches (in)</option>
                            <option value="cm">Centimeters (cm)</option>
                        </select>
                     </div>
                     <div>
                        <InputLabel value="Weight Unit" />
                        <select v-model="form.weight_unit" class="w-full mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="lb">Pounds (lb)</option>
                            <option value="kg">Kilograms (kg)</option>
                        </select>
                     </div>
                </div>
            </div>

            <!-- Items -->
            <div class="mb-2">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="text-sm font-bold text-gray-700 uppercase tracking-wider">Items Content</h3>
                    <button type="button" @click="addItem" class="text-xs text-blue-600 hover:text-blue-800 font-bold">+ Add Item</button>
                </div>

                <div v-for="(item, idx) in form.items" :key="idx" class="bg-gray-50 border rounded-lg p-4 mb-4 relative group">
                    <button type="button" @click="removeItem(idx)" class="absolute top-2 right-2 text-gray-400 hover:text-red-500">
                        <i class="fas fa-times"></i>
                    </button>

                    <div class="grid grid-cols-1 gap-3">
                        <TextInput v-model="item.description" placeholder="Item Description" class="w-full" />
                        
                        <div class="grid grid-cols-4 gap-2">
                            <div>
                                <InputLabel value="Qty" class="text-xs" />
                                <TextInput type="number" v-model="item.quantity" class="w-full" />
                            </div>
                            <div>
                                <InputLabel value="Value/Unit ($)" class="text-xs" />
                                <TextInput type="number" step="0.01" v-model="item.value_per_unit" class="w-full" />
                            </div>
                            <div>
                                <InputLabel :value="`Weight (${form.weight_unit})`" class="text-xs" />
                                <TextInput type="number" step="0.01" v-model="item.total_line_weight" class="w-full" />
                            </div>
                            <div class="col-span-1"></div>
                        </div>
                        <!-- Item Dimensions -->
                        <div class="grid grid-cols-3 gap-2 mt-2">
                            <div>
                                <InputLabel :value="`L (${form.dimension_unit})`" class="text-xs" />
                                <TextInput type="number" step="0.1" v-model="item.length" class="w-full" placeholder="L" />
                            </div>
                            <div>
                                <InputLabel :value="`W (${form.dimension_unit})`" class="text-xs" />
                                <TextInput type="number" step="0.1" v-model="item.width" class="w-full" placeholder="W" />
                            </div>
                            <div>
                                <InputLabel :value="`H (${form.dimension_unit})`" class="text-xs" />
                                <TextInput type="number" step="0.1" v-model="item.height" class="w-full" placeholder="H" />
                            </div>
                        </div>

                         <!-- Photos -->
                        <div class="mt-2">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Photos of Item</label>
                            <div class="flex items-center gap-2 overflow-x-auto pb-1">
                                <label class="flex-shrink-0 w-12 h-12 flex items-center justify-center border-2 border-dashed border-gray-300 rounded cursor-pointer hover:border-gray-400">
                                    <i class="fas fa-camera text-gray-400"></i>
                                    <input type="file" multiple class="hidden" @change="(e) => handleFileChange(e, idx)" />
                                </label>
                                
                                <div v-for="(img, i) in item.preview" :key="i" class="relative flex-shrink-0 w-12 h-12 border rounded overflow-hidden">
                                     <img :src="img" class="w-full h-full object-cover">
                                     <button type="button" @click="removeImage(idx, i)" class="absolute top-0 right-0 bg-red-500 text-white w-4 h-4 flex items-center justify-center text-[10px] rounded-bl">×</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer Actions -->
        <div class="pt-4 border-t mt-auto flex justify-between items-center bg-gray-50 -mx-6 -mb-6 p-6 sticky bottom-0">
             <div class="text-sm">
                 <span class="block text-gray-500">Total Value: <span class="font-bold text-gray-900">${{ calculatedTotalValue.toFixed(2) }}</span></span>
                 <span class="block text-gray-500">Total Weight: <span class="font-bold text-gray-900">{{ calculatedTotalWeight.toFixed(2) }} {{ form.weight_unit }}</span></span>
             </div>
             <div class="flex gap-3">
                 <SecondaryButton @click="emit('close')">Cancel</SecondaryButton>
                 <PrimaryButton :disabled="form.processing" @click="submit">
                     {{ isEdit ? 'Update Package' : 'Create Package' }}
                 </PrimaryButton>
             </div>
        </div>
    </form>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
</style>
