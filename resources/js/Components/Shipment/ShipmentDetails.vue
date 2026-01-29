<script setup>
import ShipmentPackageCard from "./ShipmentPackageCard.vue";

const props = defineProps({
    shipment: Object,
    formatCurrency: Function,
    getFileUrl: Function,
});

const capitalizeFirst = (str) => {
    if (!str) return "";
    return str.charAt(0).toUpperCase() + str.slice(1);
};

const getStatusClasses = (status) => {
    return {
        "text-yellow-600": status === "pending",
        "text-blue-600": status === "shipped",
        "text-green-600": status === "delivered",
        "text-red-600": status === "cancelled",
    };
};
</script>

<template>
    <tr class="bg-gray-50">
        <td colspan="9" class="p-4 border">
            <div class="space-y-4 text-left">
                <!-- Ship Request Information -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-lg font-semibold">
                            Ship Request Information
                        </h3>
                        <div class="space-y-1 text-sm">
                            <p>
                                <strong>Ship Request #:</strong>
                                {{ shipment?.tracking_number || `#${shipment?.id}` }}
                            </p>
                            <p>
                                <strong>Suite Number:</strong>
                                {{ shipment?.customer?.suite ?? "N/A" }}
                            </p>
                            <p>
                                <strong>Customer:</strong>
                                {{ shipment?.customer?.name ?? "N/A" }}
                            </p>
                            <p>
                                <strong>Email:</strong>
                                {{ shipment?.customer?.email ?? "N/A" }}
                            </p>
                        </div>
                    </div>

                    <!-- Shipment Details -->
                    <div>
                        <h3 class="mb-2 text-lg font-semibold">Shipment Details</h3>
                        <div class="space-y-1 text-sm">
                            <p>
                                <strong>Total Weight:</strong>
                                {{ shipment?.total_weight ?? "0" }} kg
                            </p>
                            <p><strong>Total Dimensions:</strong> N/A</p>
                            <p>
                                <strong>Total Price:</strong>
                                {{ formatCurrency(shipment?.total_price) }}
                            </p>
                            <p>
                                <strong>Status:</strong>
                                <span
                                    :class="getStatusClasses(shipment?.status)"
                                    class="ml-2 font-semibold"
                                >
                                    {{ capitalizeFirst(shipment?.status) }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Shipment Method & Address -->
                <div>
                    <h3 class="mb-2 text-lg font-semibold">
                        Shipment Method & Address
                    </h3>
                    <div class="p-3 bg-white border rounded">
                        <p v-if="shipment?.internationalShipping" class="mb-2 text-sm">
                            <strong>Shipping Method:</strong>
                            {{ shipment?.internationalShipping?.title ?? "N/A" }}
                        </p>
                        <div v-if="shipment?.userAddress" class="text-sm">
                            <strong>Delivery Address:</strong>
                            <div class="pl-4 mt-1">
                                <p>{{ shipment?.userAddress?.full_name }}</p>
                                <p>{{ shipment?.userAddress?.address_line_1 }}</p>
                                <p v-if="shipment?.userAddress?.address_line_2">
                                    {{ shipment?.userAddress?.address_line_2 }}
                                </p>
                                <p>
                                    {{ shipment?.userAddress?.city }},
                                    {{ shipment?.userAddress?.state }}
                                    {{ shipment?.userAddress?.postal_code }}
                                </p>
                                <p>{{ shipment?.userAddress?.country }}</p>
                                <p>Phone: {{ shipment?.userAddress?.phone_number }}</p>
                            </div>
                        </div>
                        <p v-else class="text-sm text-gray-500">No address available</p>
                    </div>
                </div>

                <!-- Additional Services -->
                <div>
                    <h3 class="mb-2 text-lg font-semibold">Additional Services</h3>
                    <div class="p-3 bg-white border rounded">
                        <div v-if="shipment?.packing_options?.length > 0" class="mb-3">
                            <strong class="text-sm">Packing Options:</strong>
                            <ul class="pl-6 mt-1 list-disc">
                                <li
                                    v-for="option in shipment?.packing_options"
                                    :key="option.id"
                                    class="text-sm"
                                >
                                    {{ option.title }} - {{ option.description }}
                                    <span v-if="option.price" class="font-semibold text-green-600">
                                        ({{ formatCurrency(option.price) }})
                                    </span>
                                </li>
                            </ul>
                        </div>
                        <div v-if="shipment?.shipping_preference_options?.length > 0">
                            <strong class="text-sm">Shipping Preference Options:</strong>
                            <ul class="pl-6 mt-1 list-disc">
                                <li
                                    v-for="option in shipment?.shipping_preference_options"
                                    :key="option.id"
                                    class="text-sm"
                                >
                                    {{ option.title }} - {{ option.description }}
                                    <span v-if="option.price" class="font-semibold text-green-600">
                                        ({{ formatCurrency(option.price) }})
                                    </span>
                                </li>
                            </ul>
                        </div>
                        <p
                            v-if="
                                (!shipment?.packing_options || shipment?.packing_options?.length === 0) &&
                                (!shipment?.shipping_preference_options || shipment?.shipping_preference_options?.length === 0)
                            "
                            class="text-sm text-gray-500"
                        >
                            No additional services
                        </p>
                    </div>
                </div>

                <!-- Packages Information -->
                <div>
                    <h3 class="mb-2 text-lg font-semibold">
                        Consolidated Packages ({{ shipment?.packages?.length || 0 }})
                    </h3>
                    <div v-if="shipment?.packages?.length > 0" class="space-y-3">
                        <ShipmentPackageCard
                            v-for="pkg in shipment?.packages"
                            :key="pkg.id"
                            :package="pkg"
                            :format-currency="formatCurrency"
                            :get-file-url="getFileUrl"
                        />
                    </div>
                    <p v-else class="text-sm text-gray-500">No packages found</p>
                </div>
            </div>
        </td>
    </tr>
</template>
