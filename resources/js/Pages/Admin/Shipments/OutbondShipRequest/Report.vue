<script setup>
import Pagination from "@/Components/Pagination.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";

const props = defineProps({
    shipments: Object,
});
</script>
<template>
    <AuthenticatedLayout>
        <Head title="Shipments" />
        <div class="grid grid-cols-1">
            <div class="w-full">
                <div class="card">
                    <h1 class="text-2xl">Shipments</h1>

                    <div class="card-body">
                        <div class="overflow-x-auto">
                            <table class="table text-center border">
                                <!-- head -->
                                <thead class="text-black">
                                    <tr>
                                        <th class="border">Tracking Number</th>
                                        <th class="border">Customer</th>
                                        <th class="border">Suite</th>
                                        <th class="border">Total weight</th>
                                        <th class="border">Total Price</th>
                                        <th class="border">Status</th>
                                        <!-- <th class="border">Invoice Status</th> -->
                                        <td class="border">Action</td>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- row 1 -->
                                    <tr
                                        v-for="shipment in props.shipments.data"
                                        :key="shipment?.id"
                                    >
                                        <td class="border">
                                            {{
                                                shipment?.tracking_number ?? ""
                                            }}
                                        </td>
                                        <td class="border">
                                            {{ shipment?.user?.name ?? "" }}
                                        </td>
                                        <td class="border">
                                            {{ shipment?.user?.suite ?? "" }}
                                        </td>
                                        <td class="border">
                                            {{ shipment?.total_weight ?? "" }}
                                        </td>
                                        <td class="border">
                                            ${{
                                                shipment?.total_price.toFixed(
                                                    2
                                                ) ?? ""
                                            }}
                                        </td>
                                        <td class="border">
                                            {{ shipment?.status ?? "" }}
                                        </td>
                                        <!-- <td class="border">
                                            {{ shipment?.invoice_status ?? "" }}
                                        </td> -->
                                        <td class="text-center">
                                            <Link
                                                :href="
                                                    route(
                                                        'admin.shipments.edit',
                                                        { ship: shipment?.id }
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
                            :links="props.shipments.links"
                            :from="props.shipments.from"
                            :to="props.shipments.to"
                            :total="props.shipments.total"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
