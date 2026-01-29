<script setup>
import Pagination from "@/Components/Pagination.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    users: Object,
    filters: Object,
});
</script>
<template>
    <AuthenticatedLayout>
        <Head title="Users" />
        <div class="grid grid-cols-1">
            <div class="w-full">
                <div class="card">
                    <h1 class="text-2xl">Users</h1>
                    <!-- <ReportFilter :filters="props?.filters" /> -->
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
                                                        'admin.users.userEdit',
                                                        { user: user?.id }
                                                    )
                                                "
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
