<script setup>
import { ref, watch } from "vue";

const props = defineProps({
    pageProps: Object,
});

const showInfoMessage = ref(false);
const infoMessage = ref("");
const showErrorMessage = ref(false);
const errorMessage = ref("");

const closeAlert = () => {
    showInfoMessage.value = false;
    showErrorMessage.value = false;
};

if (props.pageProps.alert) {
    showInfoMessage.value = true;
    infoMessage.value = props.pageProps.alert;

    setTimeout(closeAlert, 5 * 1000);
}
if (props.pageProps.errors?.message) {
    showErrorMessage.value = true;
    errorMessage.value = props?.pageProps.errors?.message;

    setTimeout(closeAlert, 5 * 1000);
}

watch(
    () => props.pageProps,
    (pageProps) => {
        if (pageProps.alert) {
            showInfoMessage.value = true;
            infoMessage.value = pageProps.alert;
        } else if (pageProps.errors.message) {
            showErrorMessage.value = true;
            errorMessage.value = pageProps.errors.message;
        }

        setTimeout(closeAlert, 5 * 1000);
    }
);
</script>

<template>
    <div
        v-if="showInfoMessage"
        class="fixed top-0 left-0 w-full h-full bg-black/60 z-[1000] flex items-center justify-center"
    >
        <div
            class="p-8 border border-gray-300 rounded-lg bg-gray-50"
            role="alert"
        >
            <div class="flex items-center">
                <h3 class="font-semibold text-3xl text-gray-800 leading-tight">
                    Success
                </h3>
                <div
                    class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-green-500"
                >
                    <svg
                        class="w-5 h-5"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 8.207-4 4a1 1 0 0 1-1.414 0l-2-2a1 1 0 0 1 1.414-1.414L9 10.586l3.293-3.293a1 1 0 0 1 1.414 1.414Z"
                        />
                    </svg>
                    <span class="sr-only">Check icon</span>
                </div>
                <button
                    @click="closeAlert"
                    type="button"
                    class="ms-auto -mx-1.5 -my-1.5 bg-gray-50 text-gray-500 rounded-lg focus:ring-2 focus:ring-gray-400 p-1.5 hover:bg-gray-200 inline-flex items-center justify-center h-8 w-8"
                    aria-label="Close"
                >
                    <span class="sr-only">Close</span>
                    <svg
                        class="w-3 h-3"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 14 14"
                    >
                        <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"
                        />
                    </svg>
                </button>
            </div>
            <div class="font-semibold text-xl" v-html="infoMessage"></div>
        </div>
    </div>

    <div
        v-if="showErrorMessage"
        class="fixed top-0 left-0 w-full h-full bg-black/60 z-[1000] flex items-center justify-center"
    >
        <div
            class="p-8 border border-gray-300 rounded-lg bg-gray-50"
            role="alert"
        >
            <div class="flex items-center">
                <h3 class="font-semibold text-3xl text-gray-800 leading-tight">
                    Error
                </h3>
                <div
                    class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-red-500 rounded-lg"
                >
                    <svg
                        class="w-5 h-5"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm3.707 11.793a1 1 0 1 1-1.414 1.414L10 11.414l-2.293 2.293a1 1 0 0 1-1.414-1.414L8.586 10 6.293 7.707a1 1 0 0 1 1.414-1.414L10 8.586l2.293-2.293a1 1 0 0 1 1.414 1.414L11.414 10l2.293 2.293Z"
                        />
                    </svg>
                    <span class="sr-only">Error icon</span>
                </div>
                <button
                    @click="closeAlert"
                    type="button"
                    class="ms-auto -mx-1.5 -my-1.5 bg-gray-50 text-gray-500 rounded-lg focus:ring-2 focus:ring-gray-400 p-1.5 hover:bg-gray-200 inline-flex items-center justify-center h-8 w-8"
                    aria-label="Close"
                >
                    <span class="sr-only">Close</span>
                    <svg
                        class="w-3 h-3"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 14 14"
                    >
                        <path
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"
                        />
                    </svg>
                </button>
            </div>
            <div class="font-semibold text-xl" v-html="errorMessage"></div>
        </div>
    </div>
</template>
