<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

// Props coming from your Laravel controller
const props = defineProps({
    message: String,
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

// A helper function to format a number as BDT currency with two decimal places.
const formatBDT = (value) => {
    if (typeof value !== 'number' || isNaN(value)) {
        return '৳ 0.00';
    }
    return `৳ ${Number(value).toFixed(2)}`;
};

// Define the cards in a structured way for a clean and beautiful design.
const cardItems = computed(() => {
    // List of card data, categorized by purpose and assigned a Tailwind color class for the border.
    const items = [
        {
            title: 'Total Students',
            value: props.totalStudents ?? 'N/A',
            color: 'indigo',
            icon: 'fas fa-user-graduate',
            isCurrency: false,
        },
        {
            title: 'Collected (This Month)',
            value: props.totalAmountPaidThisMonth,
            color: 'emerald',
            icon: 'fas fa-calendar-check',
            isCurrency: true,
        },
        {
            title: 'Due (This Month)',
            value: props.totalBalanceDueThisMonth,
            color: 'amber',
            icon: 'fas fa-balance-scale-right',
            isCurrency: true,
        },
        {
            title: 'Collected (This Year)',
            value: props.totalAmountPaidThisYear,
            color: 'cyan',
            icon: 'fas fa-chart-line',
            isCurrency: true,
        },
        {
            title: 'Due (This Year)',
            value: props.totalBalanceDueThisYear,
            color: 'rose',
            icon: 'fas fa-piggy-bank',
            isCurrency: true,
        },
    ];
    // Filter out cards with a value of 0 for a cleaner dashboard view.
    return items.filter(card => card.value !== 0);
});

// Overall totals for a separate, prominent card.
const overallTotals = computed(() => [
    {
        title: 'Total Paid',
        value: props.overallTotalAmountPaid,
        icon: 'fas fa-money-bill-wave',
    },
    {
        title: 'Total Due',
        value: props.overallTotalBalanceDue,
        icon: 'fas fa-hand-holding-usd',
    },
]);

// Typewriter effect logic
const typedMessage = ref('');
const typingSpeed = 50; // Milliseconds per character

const typeWriterEffect = (text) => {
    let i = 0;
    typedMessage.value = '';
    const interval = setInterval(() => {
        if (i < text.length) {
            typedMessage.value += text.charAt(i);
            i++;
        } else {
            clearInterval(interval);
        }
    }, typingSpeed);
};

// Watch for changes in the 'message' prop and trigger the typewriter effect
watch(() => props.message, (newMessage) => {
    if (newMessage) {
        typeWriterEffect(newMessage);
    }
}, { immediate: true });
</script>

<template>
    <Head title="Accounts Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Accounts Dashboard
            </h2>
        </template>

        <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-8">

                    <!-- Welcome Section with typewriter effect -->
                    <div class="mb-8">
                        <h3 class="text-3xl font-extrabold text-gray-900 dark:text-gray-100 mb-2 animate-fade-in-up">Hello, Accounts Staff!</h3>
                        <p class="text-lg text-gray-600 dark:text-gray-400 font-mono">
                            {{ typedMessage }}<span class="typewriter-cursor">|</span>
                        </p>
                    </div>

                    <!-- Overall Totals Hero Card - New Clean Design -->
                    <div class="mb-10">
                        <div class="bg-gray-900 text-white rounded-2xl shadow-xl p-8 transform transition-transform duration-300 hover:scale-[1.01]">
                            <h4 class="text-2xl font-bold mb-6">Overall Financial Summary</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                                <div v-for="total in overallTotals" :key="total.title" class="flex items-center p-4 bg-gray-800 rounded-lg">
                                    <i :class="[total.icon, 'text-3xl text-indigo-400 mr-4']"></i>
                                    <div>
                                        <p class="text-sm font-medium text-gray-400">{{ total.title }}</p>
                                        <p class="text-3xl font-extrabold text-white">{{ formatBDT(Number(total.value)) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Card Grid with new design -->
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <div v-for="card in cardItems" :key="card.title"
                             :class="[`relative p-6 rounded-xl shadow-md border-t-4 border-${card.color}-400 bg-gray-50 dark:bg-gray-700 transform transition-transform duration-300 hover:scale-105`]">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 :class="[`text-sm font-semibold text-${card.color}-700 dark:text-${card.color}-300 mb-2`]">{{ card.title }}</h4>
                                    <p class="text-2xl font-extrabold text-gray-900 dark:text-white">
                                        {{ card.isCurrency ? formatBDT(Number(card.value)) : card.value }}
                                    </p>
                                </div>
                                <div class="text-4xl">
                                     <i :class="[card.icon, `text-${card.color}-400 opacity-60`]" v-if="!card.isCurrency"></i>
                                     <span :class="[`text-${card.color}-400 opacity-60`]" v-else>৳</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Report Links -->
                    <div v-if="reportLinks" class="mt-10">
                        <h4 class="text-xl font-bold mb-3 text-gray-800 dark:text-gray-200">📂 Quick Financial Reports</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <a v-for="(url, key) in reportLinks" :key="key" :href="url"
                               class="block p-4 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-300 shadow-sm text-center">
                                <span class="font-semibold capitalize text-gray-800 dark:text-gray-200">{{ key.replace(/([A-Z])/g, ' $1') }}</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Scoped styles from your original code */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fade-in-up {
    animation: fadeInUp 0.6s ease-out forwards;
}
@keyframes blink {
    50% { opacity: 0; }
}
.typewriter-cursor {
    font-weight: 300;
    font-size: 1.25rem;
    animation: blink 0.7s infinite step-end;
}
</style>
