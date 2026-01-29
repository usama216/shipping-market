<script setup>
import Pagination from "@/Components/Pagination.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Edit from "../Edit.vue";
import { Head, usePage } from "@inertiajs/vue3";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import Refund from "../components/Refund.vue";

const props = defineProps({
    transactions: Object,
    user: Object,
});

const authUser = usePage().props.auth.user;
const isSuperAdmin = usePage().props.auth.isSuperAdmin;
</script>
<template>
    <AuthenticatedLayout>
        <Edit :user="props?.user">
            <Head title="User transaction" />
            <div class="grid grid-cols-1">
                <div class="w-full">
                    <div class="card">
                        <div class="card-body">
                            <div class="overflow-x-auto">
                                <table class="table border">
                                    <!-- head -->
                                    <thead class="text-black">
                                        <tr>
                                            <th class="border">
                                                Transaction Id
                                            </th>
                                            <th class="border">Email</th>
                                            <th class="border">Status</th>
                                            <th class="border">Amount</th>
                                            <th class="border">Description</th>
                                            <th class="border">
                                                Transaction Date
                                            </th>
                                            <th
                                                class="border"
                                                v-if="isSuperAdmin"
                                            >
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- row 1 -->
                                        <tr
                                            v-for="transaction in props
                                                .transactions.data"
                                            :key="transaction?.id"
                                        >
                                            <td class="border">
                                                {{
                                                    transaction?.transaction_id ??
                                                    ""
                                                }}
                                            </td>
                                            <td class="border">
                                                {{
                                                    transaction?.user?.email ??
                                                    ""
                                                }}
                                            </td>
                                            <td class="border">
                                                <div
                                                    class="badge badge-success"
                                                    v-if="
                                                        transaction?.status_name ==
                                                        'Success'
                                                    "
                                                >
                                                    {{
                                                        transaction?.status_name
                                                    }}
                                                </div>
                                                <div
                                                    class="badge badge-soft badge-error"
                                                    v-if="
                                                        transaction?.status_name ==
                                                        'Failed'
                                                    "
                                                >
                                                    {{
                                                        transaction?.status_name
                                                    }}
                                                </div>
                                                <div
                                                    class="badge badge-soft badge-warning"
                                                    v-if="
                                                        transaction?.status_name ==
                                                        'Refunded'
                                                    "
                                                >
                                                    {{
                                                        transaction?.status_name
                                                    }}
                                                </div>
                                            </td>
                                            <td class="border">
                                                {{
                                                    __to_fixed_number(
                                                        transaction?.amount
                                                    ) ?? ""
                                                }}
                                            </td>
                                            <td class="border">
                                                {{
                                                    transaction?.description ??
                                                    ""
                                                }}
                                            </td>
                                            <td class="border">
                                                {{
                                                    __format_date_time(
                                                        transaction?.transaction_date
                                                    ) ?? ""
                                                }}
                                            </td>
                                            <td
                                                class="border"
                                                v-if="isSuperAdmin"
                                            >
                                                <template
                                                    v-if="
                                                        transaction?.status == 1
                                                    "
                                                >
                                                    <Refund
                                                        :id="transaction?.id"
                                                    />
                                                </template>
                                                <span v-else>Refunded</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <Pagination
                                :links="props.transactions.links"
                                :from="props.transactions.from"
                                :to="props.transactions.to"
                                :total="props.transactions.total"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </Edit>
    </AuthenticatedLayout>
</template>
