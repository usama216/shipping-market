<script setup>
/**
 * TrackingTimeline - Reusable tracking events timeline component
 */

const props = defineProps({
    events: {
        type: Array,
        default: () => [],
    },
});

const getStatusIcon = (status) => {
    const icons = {
        pending: "fas fa-clock",
        label_created: "fas fa-tag",
        picked_up: "fas fa-truck-pickup",
        in_transit: "fas fa-shipping-fast",
        out_for_delivery: "fas fa-truck",
        delivered: "fas fa-check-circle",
        exception: "fas fa-exclamation-triangle",
        returned: "fas fa-undo",
    };
    return icons[status] || "fas fa-circle";
};

const getStatusColor = (status) => {
    const colors = {
        pending: "bg-yellow-500",
        label_created: "bg-blue-400",
        picked_up: "bg-blue-500",
        in_transit: "bg-blue-600",
        out_for_delivery: "bg-purple-500",
        delivered: "bg-green-500",
        exception: "bg-red-500",
        returned: "bg-orange-500",
    };
    return colors[status] || "bg-gray-400";
};
</script>

<template>
    <div class="space-y-4">
        <!-- Empty State -->
        <div v-if="events.length === 0" class="text-center py-8 text-gray-500">
            <i class="fas fa-truck text-4xl mb-2"></i>
            <p>No tracking events yet</p>
            <p class="text-sm">Events will appear here once the shipment is processed</p>
        </div>

        <!-- Timeline -->
        <div v-else class="relative">
            <div
                v-for="(event, index) in events"
                :key="event.id"
                class="flex gap-4 pb-6 relative"
            >
                <!-- Timeline Line -->
                <div
                    v-if="index < events.length - 1"
                    class="absolute left-4 top-8 w-0.5 h-full bg-gray-200"
                ></div>

                <!-- Status Icon -->
                <div
                    class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-white z-10"
                    :class="getStatusColor(event.status)"
                >
                    <i :class="getStatusIcon(event.status)" class="text-sm"></i>
                </div>

                <!-- Event Content -->
                <div class="flex-grow">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-semibold text-sm">{{ event.status_label }}</p>
                            <p v-if="event.description" class="text-sm text-gray-600">
                                {{ event.description }}
                            </p>
                            <p v-if="event.location" class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                {{ event.location }}
                            </p>
                        </div>
                        <div class="text-right text-xs text-gray-500 ml-2">
                            <p>{{ event.event_time_formatted }}</p>
                            <p class="capitalize">{{ event.source }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
