<script setup>
import { ref, watch, computed } from "vue";
import draggable from "vuedraggable";
import PackageCard from "./PackageCard.vue";
import { useToast } from "vue-toastification";

const toast = useToast();

const props = defineProps({
    packages: { type: Array, required: true },
    canUpdate: { type: Boolean, default: true },
});

const emit = defineEmits(['add-note', 'quick-view']);

// Column definitions
const columns = ref([
    { id: 1, title: 'Action Required', items: [], color: 'red', icon: 'fas fa-exclamation-triangle', bgLight: 'bg-red-50', borderColor: 'border-red-200' },
    { id: 2, title: 'In Review', items: [], color: 'yellow', icon: 'fas fa-clock', bgLight: 'bg-yellow-50', borderColor: 'border-yellow-200' },
    { id: 3, title: 'Ready to Send', items: [], color: 'blue', icon: 'fas fa-paper-plane', bgLight: 'bg-blue-50', borderColor: 'border-blue-200' },
]);

// Initialize columns from props
const distributePackages = () => {
    columns.value.forEach(col => col.items = []);
    props.packages.forEach(pkg => {
        const col = columns.value.find(c => c.id === pkg.status);
        if (col) col.items.push(pkg);
    });
};

watch(() => props.packages, distributePackages, { immediate: true, deep: true });

// Calculate column totals
const getColumnTotal = (items) => {
    return items.reduce((sum, pkg) => sum + (parseFloat(pkg.total_value) || 0), 0);
};

const formatCurrency = (value) => {
    return `$${value.toFixed(2)}`;
};

const onDragChange = async (event, columnId) => {
    if (event.added) {
        const pkg = event.added.element;
        const newStatus = columnId;
        const packageId = pkg.id;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
            
            const response = await fetch(`/package/${packageId}/status`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: JSON.stringify({ status: newStatus }),
            });

            if (!response.ok) throw new Error('Failed to update');
            
            toast.success("Status updated!");
            pkg.status = newStatus; 
            pkg.status_name = getStatusName(newStatus);

        } catch (e) {
            console.error(e);
            toast.error("Failed to move package. Please try again.");
        }
    }
};

const getStatusName = (id) => {
    const map = { 1: 'Action Required', 2: 'In Review', 3: 'Ready to Send' };
    return map[id] || 'Unknown';
};

const handleAddNote = (pkg) => {
    emit('add-note', pkg);
};

const handleQuickView = (pkg) => {
    emit('quick-view', pkg);
};
</script>

<template>
    <div class="flex gap-5 overflow-x-auto pb-4 px-1">
        <div 
            v-for="col in columns" 
            :key="col.id" 
            class="flex-shrink-0 w-80 flex flex-col min-h-[500px]"
        >
            <!-- Column Header -->
            <div class="mb-3 px-1">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full" :class="`bg-${col.color}-500`"></span>
                        <h3 class="font-bold text-gray-700 text-sm uppercase tracking-wide">{{ col.title }}</h3>
                        <span class="bg-gray-200 text-gray-600 text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[20px] text-center">
                            {{ col.items.length }}
                        </span>
                    </div>
                </div>
                <!-- Column Value Total -->
                <div v-if="getColumnTotal(col.items) > 0" class="mt-1 text-[11px] text-gray-500">
                    <i class="fas fa-coins mr-1"></i>
                    Total: <span class="font-semibold text-gray-700">{{ formatCurrency(getColumnTotal(col.items)) }}</span>
                </div>
            </div>

            <!-- Draggable Area -->
            <div 
                class="flex-1 rounded-xl p-2.5 border-2 border-dashed transition-colors duration-200 overflow-y-auto custom-scrollbar"
                :class="[col.bgLight, col.borderColor, { 'border-solid bg-opacity-50': col.items.length > 0 }]"
            >
                <draggable
                    v-model="col.items"
                    group="packages"
                    item-key="id"
                    class="flex flex-col gap-3 min-h-[200px]"
                    ghost-class="ghost-card"
                    drag-class="drag-card"
                    @change="(e) => onDragChange(e, col.id)"
                    :disabled="!canUpdate"
                >
                    <template #item="{ element }">
                        <PackageCard 
                            :package-data="element" 
                            @add-note="handleAddNote"
                            @quick-view="handleQuickView"
                        />
                    </template>
                </draggable>

                <!-- Empty State -->
                <div 
                    v-if="col.items.length === 0" 
                    class="h-full flex flex-col items-center justify-center text-center py-10"
                >
                    <div class="w-14 h-14 rounded-full bg-white/80 flex items-center justify-center mb-3 shadow-sm">
                        <i :class="[col.icon, `text-${col.color}-300`, 'text-xl']"></i>
                    </div>
                    <p class="text-sm font-medium text-gray-400">No packages</p>
                    <p class="text-xs text-gray-300 mt-0.5">Drag packages here</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent; 
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1; 
  border-radius: 4px;
}
.custom-scrollbar:hover::-webkit-scrollbar-thumb {
  background: #94a3b8; 
}

/* Draggable Styles */
.ghost-card {
    opacity: 0.4;
    background: #e2e8f0 !important;
    border: 2px dashed #94a3b8 !important;
    border-radius: 12px;
    transform: rotate(0deg) !important;
}
.drag-card {
    transform: rotate(3deg) scale(1.02);
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    cursor: grabbing;
}
</style>
