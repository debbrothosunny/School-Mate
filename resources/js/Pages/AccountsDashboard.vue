<script setup>
// The script setup remains the same, as the logic (props, computed, methods) is robust.
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

// **Updated cardItems - simplified color classification for the new theme**
const cardItems = computed(() => [
    { title: 'Collected (This Month)', value: props.totalAmountPaidThisMonth, color: 'text-green-600', accent: 'border-green-400', icon: 'fa-sack-dollar' },
    { title: 'Due (This Month)', value: props.totalBalanceDueThisMonth, color: 'text-yellow-600', accent: 'border-yellow-400', icon: 'fa-scale-unbalanced-flip' },
    { title: 'Due (This Year)', value: props.totalBalanceDueThisYear, color: 'text-red-600', accent: 'border-red-400', icon: 'fa-piggy-bank' },
    // Adding the student and fee count cards back for a richer overview
    { title: 'Total Students', value: props.totalStudents, color: 'text-indigo-600', accent: 'border-indigo-400', icon: 'fa-users', isCount: true },
    { title: 'Pending Fees', value: props.pendingFeesCount, color: 'text-orange-600', accent: 'border-orange-400', icon: 'fa-hourglass-half', isCount: true },
].filter(card => card.value !== null && card.value !== undefined));


// **Updated overallTotals - simplified color classification for the new theme**
const overallTotals = computed(() => [
    { title: 'Total Paid All Time', value: props.overallTotalAmountPaid, icon: 'fa-money-bill-trend-up', accent: 'bg-green-100 border-green-400' },
    { title: 'Total Due All Time', value: props.overallTotalBalanceDue, icon: 'fa-hand-holding-dollar', accent: 'bg-red-100 border-red-400' },
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
        : 'Welcome to your Financial Reporting and Analysis Center';
    typeWriterEffect(welcomeText);
}, { immediate: true });
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Accounts Dashboard" />

        <template #header>
            <h2 class="text-3xl font-bold text-gray-800">
                Accounts Dashboard
            </h2>
        </template>

        <div class="min-h-screen bg-gray-50 py-12 px-4">
            <div class="max-w-7xl mx-auto">
                <div class="relative overflow-hidden rounded-2xl bg-white shadow-lg border border-gray-200 mb-10">
                    <div class="absolute top-0 left-0 w-full h-2 bg-indigo-600"></div> <div class="relative p-10 md:p-12 text-gray-800">
                        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
                            <div class="max-w-3xl">
                                <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight leading-tight text-gray-900">
                                    {{ typedMessage }}
                                    <span :class="['inline-block w-1 h-10 ml-2 bg-indigo-600', { 'animate-typing': isTyping }]"></span>
                                </h1>
                                <p class="mt-4 text-lg text-gray-600 font-light">
                                    Real-time insights into your institution's financial performance.
                                </p>
                            </div>
                            <div class="text-7xl text-indigo-400/50 hidden sm:block">
                                <i class="fa-solid fa-chart-line"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
                    <div v-for="total in overallTotals" :key="total.title"
                        :class="['group relative overflow-hidden rounded-2xl bg-white shadow-xl border-l-4 p-8 transform transition-all duration-300 hover:shadow-2xl hover:-translate-y-1', total.accent]">
                        <div class="relative text-gray-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">{{ total.title }}</p>
                                    <p class="text-4xl font-bold mt-2 tracking-tight text-gray-900">
                                        {{ formatBDT(Number(total.value)) }}
                                    </p>
                                </div>
                                <div class="text-5xl opacity-40 group-hover:opacity-70 transition-opacity duration-300" :class="total.accent.includes('green') ? 'text-green-500' : 'text-red-500'">
                                    <i :class="`fa-solid ${total.icon}`"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-12">
                    <div v-for="card in cardItems" :key="card.title"
                        class="relative group overflow-hidden rounded-xl bg-white shadow-lg border-t-4 border-gray-200 p-6 transition-all duration-300 hover:shadow-xl hover:border-indigo-400">
                        <div :class="['absolute top-0 left-0 w-full h-1', card.accent.replace('border', 'bg')]"></div>

                        <div class="relative text-gray-800">
                            <div class="flex items-center justify-between mb-4">
                                <div :class="`text-2xl ${card.color}`">
                                    <i :class="`fa-solid ${card.icon}`"></i>
                                </div>
                                <span class="text-xs font-semibold uppercase text-gray-500 tracking-wider">
                                    {{ card.title.includes('Month') ? 'Monthly' : card.title.includes('Year') ? 'Yearly' : 'Summary' }}
                                </span>
                            </div>
                            
                            <p class="text-3xl font-bold mt-3 text-gray-900">
                                <span v-if="card.isCount">
                                    {{ Number(card.value).toLocaleString() }}
                                </span>
                                <span v-else>
                                    {{ formatBDT(Number(card.value)) }}
                                </span>
                            </p>

                            <p class="text-sm text-gray-500 mt-1 truncate">{{ card.title }}</p>
                        </div>
                    </div>
                </div>

                <section v-if="reportLinks" class="mt-16">
                    <h3 class="text-2xl font-bold text-gray-800 mb-8 flex items-center gap-3">
                        <i class="fa-solid fa-file-invoice text-indigo-600"></i>
                        Instant Financial Reports
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-5">
                        <a v-for="(url, key) in reportLinks" :key="key" :href="url"
                            class="group relative overflow-hidden rounded-xl bg-white shadow-lg p-6 text-center text-gray-700 font-semibold transition-all duration-300 hover:bg-indigo-50 hover:shadow-xl hover:text-indigo-600 border border-gray-200">
                            
                            <div class="text-3xl mb-3 text-indigo-500 group-hover:text-indigo-600 transition-colors">
                                <i class="fa-solid fa-file-pdf"></i>
                            </div>
                            <span class="block text-sm">{{ key.replace(/([A-Z])/g, ' $1').trim() }}</span>
                            <div class="absolute inset-x-0 bottom-0 h-1 bg-indigo-500 transform scale-x-0 group-hover:scale-x-100 transition-transform"></div>
                        </a>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Define the typing animation for the light theme */
@keyframes typing {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}
.animate-typing {
    animation: typing 0.7s infinite;
}
</style>