<script setup>
import Pagination from "@/Components/Pagination.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
import ReportFilter from "./ReportFilter.vue";

const props = defineProps({
    users: Object,
    filters: Object,
});
</script>
<template>
    <AuthenticatedLayout>
        <Head title="Customers" />
        <div class="grid grid-cols-1">
            <div class="w-full">
                <div class="card">
                    <div class="flex justify-between items-center mb-4">
                        <h1 class="text-2xl">Customers</h1>
                        <Link
                            :href="route('admin.customers.create')"
                            class="btn btn-primary"
                        >
                            <i class="fa fa-plus mr-2"></i>
                            Add New Customer
                        </Link>
                    </div>
                    <ReportFilter :filters="props?.filters" />
                    <div class="card-body">
                        <div class="overflow-x-auto">
                            <table class="table border">
                                <!-- head -->
                                <thead class="text-black">
                                    <tr>
                                        <th class="border">Name</th>
                                        <th class="border">Email</th>
                                        <th class="border">Phone</th>
                                        <th class="border">Suite</th>
                                        <th class="border">Country</th>
                                        <th class="border">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- row 1 -->
                                    <tr
                                        v-for="user in props.users.data"
                                        :key="user?.id"
                                    >
                                        <td class="border">
                                            {{ user?.name ?? "" }}
                                        </td>
                                        <td class="border">
                                            {{ user?.email ?? "" }}
                                        </td>
                                        <td class="border">
                                            {{ user?.phone ?? "" }}
                                        </td>
                                        <td class="border">
                                            {{ user?.suite ?? "" }}
                                        </td>
                                        <td class="border">
                                            {{ user?.country ?? "" }}
                                        </td>
                                        <td class="text-center">
                                            <Link
                                                :href="
                                                    route(
                                                        'admin.customers.edit',
                                                        { customer: user?.id }
                                                    )
                                                "
                                                class="text-blue-600 hover:text-blue-800"
                                                title="Edit Customer"
                                            >
                                                <i
                                                    class="fa fa-angle-right"
                                                    aria-hidden="true"
                                                ></i>
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <Pagination
                            :links="props.users.links"
                            :from="props.users.from"
                            :to="props.users.to"
                            :total="props.users.total"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
