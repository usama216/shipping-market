<template>
    <AuthenticatedLayout>
        <Head title="Package Kanban Board" />

        <div class="container-fluid">
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            Package Status Management
                        </h1>
                        <p class="mt-1 text-gray-600">
                            Drag and drop packages to update their status
                        </p>
                    </div>
                    <div class="flex space-x-3">
                        <Link
                            :href="route('admin.packages')"
                            class="btn btn-outline"
                        >
                            <i class="mr-2 fas fa-list"></i>
                            Table View
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Kanban Board -->
            <div class="bg-white border border-gray-200 rounded-lg shadow-sm">
                <div class="p-6">
                    <KanbanBoard
                        :packages="packages"
                        :can-update="can('packages.kanban.update')"
                        @quick-view="handleQuickView"
                        @add-note="handleAddNote"
                    />
                </div>
            </div>
        </div>

        <QuickViewModal 
            :show="showQuickView" 
            :package-data="selectedPackage" 
            @close="closeQuickView" 
        />

        <PackageNoteModal
            :show="showNoteModal"
            :package-data="selectedPackage"
            @close="closeNoteModal"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import KanbanBoard from "@/Pages/Admin/Packages/Components/KanbanBoard.vue";
import QuickViewModal from "@/Pages/Admin/Packages/Components/QuickViewModal.vue";
import PackageNoteModal from "@/Pages/Admin/Packages/Components/PackageNoteModal.vue";
import { usePermissions } from "@/Composables/usePermissions";

const { can } = usePermissions();

defineProps({
    packages: {
        type: Array, // Changed from Object to Array as per Repository::allPackages() return type (Collection -> Array)
        required: true,
    },
});

const showQuickView = ref(false);
const showNoteModal = ref(false);
const selectedPackage = ref(null);

const handleQuickView = (pkg) => {
    selectedPackage.value = pkg;
    showQuickView.value = true;
};

const closeQuickView = () => {
    showQuickView.value = false;
    selectedPackage.value = null;
};

const handleAddNote = (pkg) => {
    selectedPackage.value = pkg;
    showNoteModal.value = true;
};

const closeNoteModal = () => {
    showNoteModal.value = false;
    selectedPackage.value = null;
};
</script>

<style scoped>
/* Custom styles for the Kanban page */
.btn {
    @apply px-4 py-2 rounded-lg font-medium transition-colors duration-200;
}

.btn-outline {
    @apply border border-gray-300 text-gray-700 hover:bg-gray-50;
}
</style>
