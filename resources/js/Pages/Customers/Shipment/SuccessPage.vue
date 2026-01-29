<script setup>
import { Link } from "@inertiajs/vue3";
import { onMounted, ref, computed } from "vue";
import { useFacebookPixel } from "@/Composables/useFacebookPixel";
import { useAdRoll } from "@/Composables/useAdRoll";

const props = defineProps({
    shipment: Object,
    receiptUrl: String,
});

const { trackPurchase: trackFBPurchase } = useFacebookPixel();
const { trackPurchase: trackAdRollPurchase, setCustomerData } = useAdRoll();

// Animation state
const showConfetti = ref(false);
const showContent = ref(false);

// Computed values
const customerName = computed(() => 
    props.shipment?.customer?.name || props.shipment?.user?.name || 'Customer'
);

const customerEmail = computed(() => 
    props.shipment?.customer?.email || props.shipment?.user?.email || ''
);

const carrierName = computed(() => {
    const carrier = props.shipment?.carrier_name || props.shipment?.carrier_service?.carrier_code;
    if (!carrier) return 'Processing...';
    const names = {
        fedex: 'FedEx',
        dhl: 'DHL Express',
        ups: 'UPS',
    };
    return names[carrier?.toLowerCase()] || carrier.toUpperCase();
});

const totalPackages = computed(() => props.shipment?.packages?.length || 0);
const totalWeight = computed(() => props.shipment?.total_weight || 0);

const address = computed(() => {
    const addr = props.shipment?.customer_address || props.shipment?.user_address;
    if (!addr) return null;
    return {
        name: addr.full_name || addr.name || customerName.value,
        line1: addr.address_line_1 || addr.address || '',
        line2: addr.address_line_2 || '',
        city: addr.city || '',
        state: addr.state || '',
        zip: addr.postal_code || addr.zip || '',
        country: addr.country || '',
        phone: addr.phone_number || addr.phone || '',
    };
});

const formatCurrency = (amount) => 
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);

// Progress steps
const steps = [
    { key: 'paid', label: 'Paid', icon: 'fa-credit-card' },
    { key: 'processing', label: 'Processing', icon: 'fa-cog' },
    { key: 'shipped', label: 'Shipped', icon: 'fa-box' },
    { key: 'in_transit', label: 'In Transit', icon: 'fa-truck' },
    { key: 'delivered', label: 'Delivered', icon: 'fa-check-circle' },
];

const currentStep = computed(() => {
    const status = props.shipment?.status || 'pending';
    const stepMap = { pending: 0, paid: 0, processing: 1, submitted: 1, shipped: 2, in_transit: 3, delivered: 4 };
    return stepMap[status] ?? 0;
});

// Confetti animation
const createConfetti = () => {
    const canvas = document.getElementById('confetti-canvas');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    
    const particles = [];
    const colors = ['#22c55e', '#3b82f6', '#f59e0b', '#ec4899', '#8b5cf6'];
    
    for (let i = 0; i < 150; i++) {
        particles.push({
            x: canvas.width / 2,
            y: canvas.height / 2,
            vx: (Math.random() - 0.5) * 15,
            vy: (Math.random() - 0.5) * 15 - 5,
            color: colors[Math.floor(Math.random() * colors.length)],
            size: Math.random() * 8 + 4,
            rotation: Math.random() * 360,
            rotationSpeed: (Math.random() - 0.5) * 10,
        });
    }
    
    let frame = 0;
    const animate = () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        particles.forEach(p => {
            p.x += p.vx;
            p.y += p.vy;
            p.vy += 0.3; // gravity
            p.rotation += p.rotationSpeed;
            
            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate((p.rotation * Math.PI) / 180);
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size / 2);
            ctx.restore();
        });
        
        frame++;
        if (frame < 120) {
            requestAnimationFrame(animate);
        } else {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            showConfetti.value = false;
        }
    };
    
    animate();
};

onMounted(() => {
    showConfetti.value = true;
    setTimeout(() => {
        showContent.value = true;
        createConfetti();
    }, 100);

    // Track Facebook Pixel Purchase event
    if (props.shipment) {
        const packageIds = props.shipment?.packages?.map(pkg => pkg.id.toString()) || [];
        const customerEmail = props.shipment?.customer?.email || props.shipment?.user?.email || '';
        
        // Facebook Pixel Purchase
        trackFBPurchase({
            value: props.shipment?.estimated_shipping_charges || 0,
            currency: 'USD',
            content_ids: packageIds.length > 0 ? packageIds : [props.shipment.id.toString()],
            content_name: `Shipment #${props.shipment?.tracking_number || props.shipment?.id}`,
            content_type: 'shipment',
            num_items: props.shipment?.packages?.length || 1,
            customerData: {
                em: customerEmail,
                ph: props.shipment?.customer?.phone || props.shipment?.user?.phone || '',
                fn: props.shipment?.customer?.first_name || props.shipment?.user?.first_name || '',
                ln: props.shipment?.customer?.last_name || props.shipment?.user?.last_name || '',
            }
        });

        // AdRoll Purchase
        trackAdRollPurchase({
            order_id: props.shipment?.tracking_number || props.shipment?.id?.toString() || '',
            total: props.shipment?.estimated_shipping_charges || 0,
            currency: 'USD',
            product_ids: packageIds.length > 0 ? packageIds : [props.shipment.id.toString()],
            email: customerEmail
        });

        // Set customer data for AdRoll
        if (customerEmail) {
            setCustomerData({
                email: customerEmail,
                phone: props.shipment?.customer?.phone || props.shipment?.user?.phone || '',
                first_name: props.shipment?.customer?.first_name || props.shipment?.user?.first_name || '',
                last_name: props.shipment?.customer?.last_name || props.shipment?.user?.last_name || '',
            });
        }
    }
});
</script>

<template>
    <div class="bg-gradient-to-br from-green-50 via-white to-blue-50 min-h-screen py-8 px-4">
        <!-- Confetti Canvas -->
        <canvas 
            v-if="showConfetti" 
            id="confetti-canvas" 
            class="fixed inset-0 pointer-events-none z-50"
        ></canvas>

        <div class="max-w-2xl mx-auto">
            <!-- Success Header -->
            <div 
                class="text-center mb-8 transition-all duration-700"
                :class="showContent ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-4'"
            >
                <!-- Animated Checkmark -->
                <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-green-500 shadow-lg shadow-green-200 mb-6 animate-bounce-once">
                    <i class="fa-solid fa-check text-white text-4xl"></i>
                </div>
                
                <h1 class="text-3xl font-bold text-green-600 mb-2">Payment Successful!</h1>
                <p class="text-gray-600">
                    Thank you, <span class="font-semibold">{{ customerName }}</span>!
                </p>
            </div>

            <!-- Main Card -->
            <div 
                class="bg-white rounded-2xl shadow-xl overflow-hidden transition-all duration-700 delay-200"
                :class="showContent ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            >
                <!-- Shipment Header -->
                <div class="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-4 text-white">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-box text-2xl opacity-75"></i>
                            <div>
                                <p class="text-sm opacity-75">Shipment</p>
                                <p class="text-lg font-bold">#{{ shipment?.tracking_number }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm opacity-75">{{ carrierName }}</p>
                            <p class="text-sm">{{ totalPackages }} package{{ totalPackages !== 1 ? 's' : '' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Receipt Section -->
                <div class="p-6 border-b">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">
                        <i class="fa-solid fa-receipt mr-2"></i>Payment Summary
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between text-gray-600">
                            <span>Shipping Cost</span>
                            <span>{{ formatCurrency(shipment?.estimated_shipping_charges - (shipment?.handling_fee || 10)) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Handling Fee</span>
                            <span>{{ formatCurrency(shipment?.handling_fee || 10) }}</span>
                        </div>
                        <div v-if="shipment?.addon_charges > 0" class="flex justify-between text-gray-600">
                            <span>Additional Services</span>
                            <span>{{ formatCurrency(shipment?.addon_charges) }}</span>
                        </div>
                        <div class="border-t pt-3 flex justify-between font-bold text-lg">
                            <span>Total Paid</span>
                            <span class="text-green-600">{{ formatCurrency(shipment?.estimated_shipping_charges) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Delivery Address -->
                <div v-if="address" class="p-6 border-b bg-gray-50">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                        <i class="fa-solid fa-location-dot mr-2"></i>Delivering To
                    </h3>
                    <div class="text-gray-700">
                        <p class="font-semibold">{{ address.name }}</p>
                        <p>{{ address.line1 }}</p>
                        <p v-if="address.line2">{{ address.line2 }}</p>
                        <p>{{ address.city }}, {{ address.state }} {{ address.zip }}</p>
                        <p>{{ address.country }}</p>
                        <p v-if="address.phone" class="text-gray-500 mt-1">
                            <i class="fa-solid fa-phone mr-1"></i>{{ address.phone }}
                        </p>
                    </div>
                </div>

                <!-- What's Next Section -->
                <div class="p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">
                        <i class="fa-solid fa-route mr-2"></i>What Happens Next?
                    </h3>
                    
                    <!-- Progress Steps -->
                    <div class="flex items-center justify-between relative mb-6">
                        <!-- Progress Line -->
                        <div class="absolute top-5 left-0 right-0 h-1 bg-gray-200 rounded"></div>
                        <div 
                            class="absolute top-5 left-0 h-1 bg-green-500 rounded transition-all duration-500"
                            :style="{ width: `${(currentStep / (steps.length - 1)) * 100}%` }"
                        ></div>
                        
                        <!-- Steps -->
                        <div 
                            v-for="(step, idx) in steps" 
                            :key="step.key"
                            class="relative flex flex-col items-center z-10"
                        >
                            <div 
                                class="w-10 h-10 rounded-full flex items-center justify-center transition-all"
                                :class="idx <= currentStep 
                                    ? 'bg-green-500 text-white shadow-md' 
                                    : 'bg-gray-200 text-gray-400'"
                            >
                                <i :class="['fa-solid', step.icon, 'text-sm']"></i>
                            </div>
                            <span 
                                class="text-xs mt-2 font-medium"
                                :class="idx <= currentStep ? 'text-green-600' : 'text-gray-400'"
                            >
                                {{ step.label }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Status Message -->
                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center">
                        <i class="fa-solid fa-truck text-blue-500 text-2xl mb-2"></i>
                        <p class="text-blue-700 font-medium">
                            Your shipment is being submitted to {{ carrierName }}.
                        </p>
                        <p class="text-blue-600 text-sm mt-1">
                            You'll receive an email with tracking details shortly.
                        </p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="px-6 pb-6 flex flex-wrap gap-3 justify-center">
                    <Link
                        :href="route('customer.shipment.details', { ship: shipment?.id })"
                        class="inline-flex items-center px-6 py-3 bg-primary-500 text-white font-semibold rounded-xl hover:bg-primary-600 transition shadow-md hover:shadow-lg"
                    >
                        <i class="fa-solid fa-box-open mr-2"></i>
                        View Shipment Details
                    </Link>
                    <Link
                        :href="route('customer.shipment.myShipments')"
                        class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition"
                    >
                        <i class="fa-solid fa-list mr-2"></i>
                        My Shipments
                    </Link>
                    <Link
                        :href="route('customer.dashboard')"
                        class="inline-flex items-center px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-xl hover:bg-gray-200 transition"
                    >
                        <i class="fa-solid fa-home mr-2"></i>
                        Home
                    </Link>
                </div>
            </div>

            <!-- Email Confirmation -->
            <p 
                class="text-center text-gray-500 text-sm mt-6 transition-all duration-700 delay-500"
                :class="showContent ? 'opacity-100' : 'opacity-0'"
            >
                <i class="fa-solid fa-envelope mr-1"></i>
                A confirmation email has been sent to <strong>{{ customerEmail }}</strong>
            </p>
        </div>
    </div>
</template>

<style scoped>
@keyframes bounce-once {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.animate-bounce-once {
    animation: bounce-once 0.6s ease-in-out;
}
</style>
