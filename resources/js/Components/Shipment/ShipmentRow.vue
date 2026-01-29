<script setup>
import { usePermissions } from "@/Composables/usePermissions";

const { can } = usePermissions();

const props = defineProps({
    shipment: Object,
    isExpanded: Boolean,
    formatCurrency: Function,
});

const emit = defineEmits(["toggle", "updateStatus"]);

const toggleExpand = () => {
    emit("toggle", props.shipment.id);
};

const handleStatusUpdate = (status) => {
    emit("updateStatus", props.shipment.id, status);
};

const getStatusClasses = (status) => {
    return {
        "bg-yellow-100 text-yellow-800": status === "pending",
        "bg-blue-100 text-blue-800": status === "shipped",
        "bg-green-100 text-green-800": status === "delivered",
        "bg-red-100 text-red-800": status === "cancelled",
    };
};

const capitalizeFirst = (str) => {
    if (!str) return "";
    return str.charAt(0).toUpperCase() + str.slice(1);
};
</script>

<template>
    <tr class="hover:bg-gray-50">
        <td class="border">
            <button
                @click="toggleExpand"
                class="text-[rgb(158,29,34)] hover:text-[rgb(120,20,25)]"
            >
                <i
                    :class="
                        isExpanded
                            ? 'fas fa-chevron-down'
                            : 'fas fa-chevron-right'
                    "
                ></i>
            </button>
        </td>
        <td class="border">
            {{ shipment?.tracking_number || `#${shipment?.id}` }}
        </td>
        <td class="border">
            {{ shipment?.customer?.suite ?? "N/A" }}
        </td>
        <td class="border">
            {{ shipment?.customer?.name ?? "N/A" }}
        </td>
        <td class="border">{{ shipment?.total_weight ?? "0" }} kg</td>
        <td class="border">
            {{ formatCurrency(shipment?.total_price) }}
        </td>
        <td class="border">
            <span
                :class="getStatusClasses(shipment?.status)"
                class="px-2 py-1 text-xs font-medium rounded-full"
            >
                {{ capitalizeFirst(shipment?.status) }}
            </span>
        </td>
        <td class="text-center border">
            <div class="flex items-center justify-center gap-2">
                <button
                    v-if="shipment?.status === 'pending' && can('shipments.status.update')"
                    @click="handleStatusUpdate('shipped')"
                    class="px-3 py-1 bg-[rgb(158,29,34)] text-white rounded hover:bg-[rgb(120,20,25)] text-sm"
                >
                    Ready to Ship
                </button>
                <Link
                    v-if="can('shipments.update')"
                    :href="route('admin.shipments.edit', { ship: shipment?.id })"
                    class="text-[rgb(158,29,34)] hover:text-[rgb(120,20,25)]"
                >
                    <i class="fa fa-angle-right" aria-hidden="true"></i>
                </Link>
            </div>
        </td>
    </tr>
</template>

<script>
import { Link } from "@inertiajs/vue3";
export default {
    components: { Link },
};
</script>
