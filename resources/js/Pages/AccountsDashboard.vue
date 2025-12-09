<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';


const props = defineProps({
    message: String,
    userName: String,
    totalStudents: Number,
    pendingFeesCount: Number,
    totalBalanceDueThisMonth: Number,
    totalAmountPaidThisMonth: Number,
    totalBalanceDueThisYear: Number,
    totalAmountPaidThisYear: Number,
    overallTotalAmountPaid: Number,
    overallTotalBalanceDue: Number,
    reportLinks: Object,
});


const formatBDT = (value) => {
    if (typeof value !== 'number' || isNaN(value)) return '৳ 0.00';
    return `৳ ${Number(value).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};


const cardItems = computed(() => [

    { title: 'Collected (This Month)', value: props.totalAmountPaidThisMonth, color: 'emerald', icon: 'fa-sack-dollar', gradient: 'from-emerald-500 to-teal-600' },
    { title: 'Due (This Month)', value: props.totalBalanceDueThisMonth, color: 'amber', icon: 'fa-scale-unbalanced-flip', gradient: 'from-amber-500 to-orange-600' },

    { title: 'Due (This Year)', value: props.totalBalanceDueThisYear, color: 'rose', icon: 'fa-piggy-bank', gradient: 'from-rose-500 to-pink-600' },
].filter(card => card.value > 0));


const overallTotals = computed(() => [
    { title: 'Total Paid All Time', value: props.overallTotalAmountPaid, icon: 'fa-money-bill-trend-up', gradient: 'from-green-500 to-emerald-600' },
    { title: 'Total Due All Time', value: props.overallTotalBalanceDue, icon: 'fa-hand-holding-dollar', gradient: 'from-red-500 to-rose-600' },
]);

const typedMessage = ref('');
const typingSpeed = 50;
const isTyping = ref(false);

const typeWriterEffect = (text) => {
    let i = 0;
    typedMessage.value = '';
    isTyping.value = true;
    const interval = setInterval(() => {
        if (i < text.length) {
            typedMessage.value += text.charAt(i);
            i++;
        } else {
            clearInterval(interval);
            isTyping.value = false;
        }
    }, typingSpeed);
};

watch(() => props.userName, (newName) => {
    const welcomeText = newName 
        ? `Welcome back, ${newName.split(' ')[0]}! 👋` 
        : 'Welcome to your Financial Data Center';
    typeWriterEffect(welcomeText);
}, { immediate: true });
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Accounts Dashboard" />

        <template #header>
            <h2 class="text-3xl font-extrabold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                Financial Dashboard
            </h2>
        </template>

        <div class="min-h-screen bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 py-12 px-4">
            <div class="max-w-7xl mx-auto">
                <!-- Hero Greeting Card -->
                <div class="relative overflow-hidden rounded-3xl bg-white/10 backdrop-blur-xl border border-white/20 shadow-2xl mb-10">
                    <div class="absolute inset-0 bg-gradient-to-r from-violet-600/30 to-indigo-600/30"></div>
                    <div class="relative p-10 md:p-16 text-white">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
                            <div class="max-w-3xl">
                                <h1 class="text-4xl md:text-6xl font-black tracking-tight leading-tight">
                                    {{ typedMessage }}
                                    <span class="inline-block w-1 h-12 ml-2 bg-purple-400 animate-pulse"></span>
                                </h1>
                                <p class="mt-4 text-xl opacity-90 font-light">
                                    Real-time insights into your institution's financial performance
                                </p>
                            </div>
                            <div class="text-8xl opacity-20">
                                <i class="fa-solid fa-chart-pie"></i>
                            </div>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-purple-400 to-transparent"></div>
                </div>

                <!-- Overall Totals - Premium Glass Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
                    <div v-for="total in overallTotals" :key="total.title"
                         class="group relative overflow-hidden rounded-3xl bg-white/10 backdrop-blur-md border border-white/20 shadow-2xl transform transition-all duration-500 hover:scale-105 hover:shadow-purple-500/25">
                        <div :class="`absolute inset-0 bg-gradient-to-br ${total.gradient} opacity-80`"></div>
                        <div class="relative p-8 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-lg font-medium opacity-90">{{ total.title }}</p>
                                    <p class="text-4xl md:text-5xl font-black mt-3 tracking-tight">
                                        {{ formatBDT(Number(total.value)) }}
                                    </p>
                                </div>
                                <div class="text-6xl opacity-30 group-hover:scale-110 transition-transform duration-500">
                                    <i :class="`fa-solid ${total.icon}`"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-12">
                    <div v-for="card in cardItems" :key="card.title"
                         class="relative group overflow-hidden rounded-2xl bg-white/5 backdrop-blur-xl border border-white/10 shadow-xl transition-all duration-500 hover:shadow-2xl hover:shadow-purple-600/30 hover:-translate-y-2">
                        <div :class="`absolute inset-0 bg-gradient-to-br ${card.gradient} opacity-60 group-hover:opacity-80 transition-opacity`"></div>
                        <div class="relative p-6 text-white">
                            <div class="flex items-center justify-between mb-4">
                                <i :class="`fa-solid ${card.icon} text-3xl opacity-80`"></i>
                                <span class="text-xs font-medium bg-white/20 px-3 py-1 rounded-full backdrop-blur">
                                    {{ card.title.includes('Month') ? 'Monthly' : card.title.includes('Year') ? 'Yearly' : 'Total' }}
                                </span>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Quick Reports Section -->
                <section v-if="reportLinks" class="mt-16">
                    <h3 class="text-2xl font-bold text-white mb-8 flex items-center gap-3">
                        <i class="fa-solid fa-folder-open text-yellow-400"></i>
                        Instant Financial Reports
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-5">
                        <a v-for="(url, key) in reportLinks" :key="key" :href="url"
                           class="group relative overflow-hidden rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 p-6 text-center text-white font-semibold transition-all duration-300 hover:bg-white/20 hover:border-purple-400 hover:shadow-xl hover:shadow-purple-500/30">
                            <div class="text-4xl mb-4 group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-file-pdf text-rose-400"></i>
                            </div>
                            <span class="block text-sm">{{ key.replace(/([A-Z])/g, ' $1').trim() }}</span>
                            <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-purple-500 to-pink-500 transform scale-x-0 group-hover:scale-x-100 transition-transform"></div>
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes blink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}
</style>