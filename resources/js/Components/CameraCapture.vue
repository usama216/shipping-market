<template>
    <!-- Modal Overlay -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm">
        <div class="relative w-full max-w-lg mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                        <i class="fa-solid fa-camera"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-800">Take Photo</h3>
                        <p class="text-xs text-gray-500">Position item in frame</p>
                    </div>
                </div>
                <button 
                    type="button" 
                    @click="closeCamera"
                    class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Camera View -->
            <div class="p-5">
                <div class="relative rounded-xl overflow-hidden bg-black aspect-video">
                    <video
                        ref="video"
                        autoplay
                        playsinline
                        class="w-full h-full object-cover"
                    ></video>
                    
                    <!-- Camera overlay guide -->
                    <div class="absolute inset-4 border-2 border-white/30 rounded-lg pointer-events-none"></div>
                    
                    <!-- Loading state -->
                    <div v-if="isLoading" class="absolute inset-0 flex items-center justify-center bg-gray-900">
                        <div class="text-center text-white">
                            <i class="fa-solid fa-spinner fa-spin text-2xl mb-2"></i>
                            <p class="text-sm">Starting camera...</p>
                        </div>
                    </div>
                    
                    <!-- Error state -->
                    <div v-if="error" class="absolute inset-0 flex items-center justify-center bg-gray-900">
                        <div class="text-center text-white px-4">
                            <i class="fa-solid fa-video-slash text-3xl mb-3 text-red-400"></i>
                            <p class="font-medium mb-1">Camera Unavailable</p>
                            <p class="text-sm text-gray-400">{{ error }}</p>
                        </div>
                    </div>
                </div>

                <!-- Preview of captured photo -->
                <div v-if="capturedPreview" class="mt-4">
                    <div class="relative rounded-xl overflow-hidden">
                        <img :src="capturedPreview" class="w-full aspect-video object-cover" />
                        <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                        <div class="absolute bottom-3 left-3 text-white text-sm font-medium">
                            <i class="fa-solid fa-check-circle mr-1 text-green-400"></i> Photo captured
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                <button 
                    type="button" 
                    @click="closeCamera"
                    class="px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 transition-colors"
                >
                    Cancel
                </button>
                
                <div class="flex gap-2">
                    <button
                        v-if="capturedPreview"
                        type="button"
                        @click="retake"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                    >
                        <i class="fa-solid fa-rotate-left mr-1"></i> Retake
                    </button>
                    <button
                        v-if="capturedPreview"
                        type="button"
                        @click="confirmPhoto"
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200"
                    >
                        <i class="fa-solid fa-check mr-1"></i> Use Photo
                    </button>
                    <button
                        v-else
                        type="button"
                        @click="capturePhoto"
                        :disabled="isLoading || error"
                        class="px-5 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-lg shadow-blue-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <i class="fa-solid fa-camera mr-1"></i> Capture
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from "vue";

const emit = defineEmits(["photo-captured", "close"]);

const video = ref(null);
const stream = ref(null);
const isLoading = ref(true);
const error = ref(null);
const capturedPreview = ref(null);
const capturedFile = ref(null);

onMounted(async () => {
    await startCamera();
});

onUnmounted(() => {
    stopCamera();
});

const startCamera = async () => {
    isLoading.value = true;
    error.value = null;
    
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                facingMode: "environment",
                width: { ideal: 1280 },
                height: { ideal: 720 }
            } 
        });
        
        if (video.value) {
            video.value.srcObject = stream.value;
        }
        isLoading.value = false;
    } catch (err) {
        isLoading.value = false;
        if (err.name === 'NotAllowedError') {
            error.value = 'Camera access denied. Please allow camera permissions.';
        } else if (err.name === 'NotFoundError') {
            error.value = 'No camera found on this device.';
        } else {
            error.value = 'Could not access camera. Please try again.';
        }
        console.error('Camera error:', err);
    }
};

const stopCamera = () => {
    if (stream.value) {
        stream.value.getTracks().forEach((track) => track.stop());
        stream.value = null;
    }
};

const closeCamera = () => {
    stopCamera();
    emit("close");
};

const capturePhoto = () => {
    if (!video.value) return;
    
    const canvas = document.createElement("canvas");
    canvas.width = video.value.videoWidth || 1280;
    canvas.height = video.value.videoHeight || 720;

    const ctx = canvas.getContext("2d");
    ctx.drawImage(video.value, 0, 0, canvas.width, canvas.height);

    const base64 = canvas.toDataURL("image/jpeg", 0.9);
    capturedPreview.value = base64;
    capturedFile.value = base64ToFile(base64, `photo-${Date.now()}.jpg`);
};

const retake = () => {
    capturedPreview.value = null;
    capturedFile.value = null;
};

const confirmPhoto = () => {
    if (capturedFile.value) {
        emit("photo-captured", capturedFile.value);
        closeCamera();
    }
};

function base64ToFile(dataurl, filename) {
    let arr = dataurl.split(",");
    let mime = arr[0].match(/:(.*?);/)[1];
    let bstr = atob(arr[1]);
    let n = bstr.length;
    let u8arr = new Uint8Array(n);

    while (n--) {
        u8arr[n] = bstr.charCodeAt(n);
    }
    return new File([u8arr], filename, { type: mime });
}
</script>
