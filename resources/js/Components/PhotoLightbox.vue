<script setup>
import { computed, watch, onMounted, onUnmounted } from "vue";

const props = defineProps({
    photos: {
        type: Array,
        default: () => [],
    },
    show: {
        type: Boolean,
        default: false,
    },
    initialIndex: {
        type: Number,
        default: 0,
    },
    title: {
        type: String,
        default: "Package Photos",
    },
});

const emit = defineEmits(["close", "update:index"]);

const currentIndex = defineModel("index", { default: 0 });

// Initialize index when lightbox opens
watch(
    () => props.show,
    (isOpen) => {
        if (isOpen) {
            currentIndex.value = props.initialIndex;
        }
    }
);

const currentItem = computed(() => {
    if (props.photos.length === 0) return null;
    return props.photos[currentIndex.value] || null;
});

const currentImage = computed(() => {
    if (!currentItem.value) return null;
    return currentItem.value.file_with_url || null;
});

const isPdf = computed(() => {
    return currentItem.value?.type === 'pdf';
});

const canGoPrev = computed(() => currentIndex.value > 0);
const canGoNext = computed(() => currentIndex.value < props.photos.length - 1);

const close = () => {
    emit("close");
};

const prevImage = () => {
    if (canGoPrev.value) {
        currentIndex.value--;
    }
};

const nextImage = () => {
    if (canGoNext.value) {
        currentIndex.value++;
    }
};

const goToImage = (index) => {
    currentIndex.value = index;
};

// Keyboard navigation
const handleKeydown = (e) => {
    if (!props.show) return;
    
    switch (e.key) {
        case "Escape":
            close();
            break;
        case "ArrowLeft":
            prevImage();
            break;
        case "ArrowRight":
            nextImage();
            break;
    }
};

onMounted(() => {
    window.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleKeydown);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            leave-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show && photos.length > 0"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/95"
                @click.self="close"
            >
                <!-- Close Button -->
                <button
                    @click="close"
                    class="absolute top-4 right-4 w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-all hover:scale-110 z-10"
                >
                    <i class="fa-solid fa-xmark text-2xl"></i>
                </button>

                <!-- Header -->
                <div
                    class="absolute top-4 left-1/2 -translate-x-1/2 px-6 py-3 rounded-full bg-white/10 backdrop-blur-sm text-white flex items-center gap-3"
                >
                    <i class="fa-solid fa-images text-primary-400"></i>
                    <span class="font-medium">{{ title }}</span>
                </div>

                <!-- Navigation: Previous -->
                <button
                    v-if="canGoPrev"
                    @click="prevImage"
                    class="absolute left-4 md:left-8 w-14 h-14 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-all hover:scale-110 z-10"
                >
                    <i class="fa-solid fa-chevron-left text-2xl"></i>
                </button>

                <!-- Navigation: Next -->
                <button
                    v-if="canGoNext"
                    @click="nextImage"
                    class="absolute right-4 md:right-8 w-14 h-14 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-all hover:scale-110 z-10"
                >
                    <i class="fa-solid fa-chevron-right text-2xl"></i>
                </button>

                <!-- Main Image or PDF -->
                <div class="relative max-w-[90vw] max-h-[75vh] flex items-center justify-center">
                    <!-- PDF Display -->
                    <a 
                        v-if="isPdf"
                        :href="currentImage"
                        target="_blank"
                        class="flex flex-col items-center justify-center gap-4 p-12 bg-white/10 rounded-2xl hover:bg-white/20 transition-colors cursor-pointer"
                    >
                        <i class="fa-solid fa-file-pdf text-red-500 text-8xl"></i>
                        <span class="text-white text-lg font-medium">{{ currentItem?.name || 'Invoice PDF' }}</span>
                        <span class="text-white/70 text-sm">Click to open PDF</span>
                    </a>
                    <!-- Image Display -->
                    <img
                        v-else
                        :src="currentImage"
                        class="max-w-full max-h-[75vh] object-contain rounded-xl shadow-2xl ring-1 ring-white/10"
                        alt="Photo"
                    />
                </div>

                <!-- Bottom Bar: Thumbnails + Counter -->
                <div
                    class="absolute bottom-6 left-1/2 -translate-x-1/2 flex flex-col items-center gap-4"
                >
                    <!-- Thumbnail Strip -->
                    <div
                        v-if="photos.length > 1"
                        class="flex gap-2 p-2 rounded-xl bg-black/50 backdrop-blur-sm max-w-[90vw] overflow-x-auto"
                    >
                        <button
                            v-for="(photo, index) in photos"
                            :key="index"
                            @click="goToImage(index)"
                            class="w-14 h-14 flex-shrink-0 rounded-lg overflow-hidden transition-all"
                            :class="
                                index === currentIndex
                                    ? 'ring-2 ring-primary-500 opacity-100 scale-110'
                                    : 'opacity-50 hover:opacity-80'
                            "
                        >
                            <!-- PDF Thumbnail -->
                            <div 
                                v-if="photo.type === 'pdf'"
                                class="w-full h-full bg-gray-800 flex items-center justify-center"
                            >
                                <i class="fa-solid fa-file-pdf text-red-500 text-xl"></i>
                            </div>
                            <!-- Image Thumbnail -->
                            <img
                                v-else
                                :src="photo.file_with_url"
                                class="w-full h-full object-cover"
                            />
                        </button>
                    </div>

                    <!-- Counter -->
                    <div
                        class="px-5 py-2 rounded-full bg-white/10 backdrop-blur-sm text-white text-sm font-medium"
                    >
                        {{ currentIndex + 1 }} / {{ photos.length }}
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
