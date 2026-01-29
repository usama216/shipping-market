<script setup>
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import { ref, watch } from "vue";

const props = defineProps({
    filters: Object,
});

const search = ref(props?.filters?.search || '');

// Debounce search to avoid too many requests
let searchTimeout = null;
watch([search], ([searchValue]) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    searchTimeout = setTimeout(() => {
        router.get(route("admin.customers"), {
            search: searchValue || '',
        }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    }, 500); // Wait 500ms after user stops typing
});

const handleSearch = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    router.get(route("admin.customers"), {
        search: search.value || '',
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>
<template>
    <div
        class="flex max-lg:flex-col justify-between gap-3 mt-2 p-5 items-center"
    >
        <form @submit.prevent="handleSearch">
            <div class="flex gap-2 items-end">
                <div>
                    <InputLabel value="Search" class="text-xl" />
                    <TextInput
                        placeholder="Search name, suite, email"
                        v-model="search"
                        @keyup.enter="handleSearch"
                    />
                </div>
                <PrimaryButton type="submit" class="mb-0">
                    <i class="fa fa-search mr-2"></i>
                    Search
                </PrimaryButton>
            </div>
        </form>
    </div>
</template>
