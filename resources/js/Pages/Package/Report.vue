<script setup>
import Pagination from "@/Components/Pagination.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import PackageFilter from "@/Components/PackageFilter.vue";
import NoResults from "@/Components/NoResults.vue";
import { Head, Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Modal from "@/Components/Modal.vue";
import { usePermissions } from "@/Composables/usePermissions";

const { can } = usePermissions();

const props = defineProps({
    status: {
        type: String,
    },
    packages: Object,
    customers: {
        type: Array,
        default: () => [],
    },
    suites: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const clearAllFilters = () => {
    router.get(
        route("admin.packages"),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const handleNavigate = (id) => {
    router.visit(route("admin.packages.edit", id));
};
</script>
<template>
    <AuthenticatedLayout>
        <Head title="Packages" />
        <div class="container-fluid">
            <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
                {{ status }}
            </div>
            <div class="grid grid-cols-1">
                <div class="w-full">
                    <!-- Filter Section -->
                    <PackageFilter
                        :customers="customers"
                        :suites="suites"
                        :current-filters="filters"
                    />

                    <div class="card">
                        <div class="flex justify-between gap-3 max-lg:flex-col">
                            <div class="card-title">Packages</div>
                            <div class="flex gap-2">
                                <Link v-if="can('packages.kanban.view')" :href="route('admin.packages.kanban')">
                                    <SecondaryButton>
                                        <i
                                            class="mr-2 fa-solid fa-chess-board"
                                        ></i>
                                        Card View</SecondaryButton
                                    >
                                </Link>
                            </div>
                        </div>
                        <div class="card-body">
                            <NoResults
                                v-if="packages.data.length === 0"
                                icon="fas fa-box-open"
                                title="No Packages Found"
                                message="Try adjusting your filters or create a new package."
                                :show-action="Object.keys(filters).length > 0"
                                action-text="Clear All Filters"
                                @action="clearAllFilters"
                            />

                            <div v-else class="overflow-x-auto">
                                <table class="table border">
                                    <thead class="text-black">
                                        <tr>
                                            <th class="border">Suite</th>
                                            <th class="border">From</th>
                                            <th class="border">Package Id</th>
                                            <th class="border">Tracking Id</th>
                                            <th class="border">
                                                Date Received
                                            </th>
                                            <th class="border">Status</th>
                                            <th class="border">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- row 1 -->
                                        <tr
                                            v-for="list in props.packages.data"
                                            :key="list?.id"
                                            class="cursor-pointer"
                                            @click="handleNavigate(list?.id)"
                                        >
                                            <td class="border">
                                                {{ list?.customer?.suite ?? "N/A" }}
                                            </td>
                                            <td class="border">
                                                {{ list?.from }}
                                            </td>
                                            <td class="border">
                                                {{ list?.package_id }}
                                            </td>
                                            <td class="border">
                                                {{ list?.tracking_id }}
                                            </td>
                                            <td class="border">
                                                {{ list?.date_received }}
                                            </td>
                                            <td class="border">
                                                {{ list?.status_name }}
                                            </td>
                                            <td
                                                class="flex items-center justify-center space-x-2 text-center border"
                                            >
                                                <Link
                                                    v-if="can('packages.update')"
                                                    :href="
                                                        route(
                                                            'admin.packages.edit',
                                                            list?.id
                                                        )
                                                    "
                                                >
                                                    <i
                                                        class="fa fa-angle-right"
                                                        aria-hidden="true"
                                                    ></i>
                                                </Link>
                                                <span v-else class="text-gray-400">—</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <Pagination
                                :links="props.packages.links"
                                :from="props.packages.from"
                                :to="props.packages.to"
                                :total="props.packages.total"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
    <Modal></Modal>
</template>
