<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import DatePicker from "@/Components/DatePicker.vue";

const props = defineProps({
    form: Object,
});
</script>

<template>
    <div class="p-6 bg-white border border-gray-100 shadow-sm rounded-2xl">
        <div class="flex items-center gap-3 mb-6">
            <div class="flex items-center justify-center w-10 h-10 text-purple-600 bg-purple-50 rounded-xl">
                <i class="fa-solid fa-box-open text-lg"></i>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-800">Package Details</h2>
                <p class="text-sm text-gray-500">Tracking and origin information</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Source Info -->
            <div class="col-span-full">
                <InputLabel for="from" value="From (Merchant/Sender)" />
                <div class="relative mt-1.5">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="text-gray-400 fa-solid fa-store"></i>
                    </div>
                    <TextInput
                        id="from"
                        v-model="form.from"
                        class="pl-10 w-full"
                        placeholder="e.g. Amazon, eBay, Walmart"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.from" />
            </div>

            <!-- Date -->
            <div>
                <DatePicker
                    v-model="form.date_received"
                    label="Date Received"
                    placeholder="Select date received"
                    :error="form.errors.date_received"
                    required
                />
            </div>

            <!-- Tracking ID -->
            <div>
                <InputLabel value="Store Tracking ID" />
                <div class="relative mt-1.5">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="text-gray-400 fa-solid fa-barcode"></i>
                    </div>
                    <TextInput
                        v-model="form.store_tracking_id"
                        class="pl-10 w-full"
                        placeholder="Tracking number from merchant"
                    />
                </div>
                 <InputError class="mt-2" :message="form.errors.store_tracking_id" />
            </div>
        </div>
    </div>
</template>

