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
    if (typeof value !== 'number' || isNaN(value)) {
        return '৳ 0.00';
    }
    return `৳ ${Number(value).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
};
const cardItems = computed(() => {
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
    return items.filter(card => card.value !== 0);
});
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
const typedMessage = ref('');
const typingSpeed = 45;
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

// Compose dynamic welcome text including userName prop
watch(() => props.userName, (newName) => {
    if (newName) {
        const welcomeText = `Welcome, ${newName}! This is your financial overview dashboard.`;
        typeWriterEffect(welcomeText);
    } else {
        typeWriterEffect('Welcome! This is your financial overview dashboard.');
    }
}, { immediate: true });
</script>

<template>
  <Head title="Accounts Dashboard" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-extrabold text-3xl text-gray-900 dark:text-gray-100 leading-tight mb-10">
        Accounts Dashboard
      </h2>
    </template>
    <section class="min-h-screen py-16 px-6 sm:px-12 lg:px-16 rounded-3xl max-w-7xl mx-auto">
      
      <!-- Welcome Section -->
      <section class="flex flex-col md:flex-row items-center justify-center space-y-6 md:space-y-0 md:space-x-10 bg-gradient-to-r from-purple-600 via-indigo-600 to-blue-600 rounded-3xl p-14 shadow-lg text-white select-none mb-16">
        <div class="bg-white bg-opacity-25 rounded-full p-5 flex items-center justify-center shadow-md">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
            <path d="M12 14l9-5-9-5-9 5 9 5z" />
            <path d="M12 14l6.16-3.422a12.083 12.083 0 011.5 6.695M12 14v7M6.837 17.382a12.086 12.086 0 011.5-6.695L12 14m-5.163 3.382V5.5l-3.655 2.085" />
          </svg>
        </div>
        <div>
          <h1 class="text-2xl font-extrabold leading-tight tracking-wider">
            {{ typedMessage }}<span class="typewriter-cursor">|</span>
          </h1>
          <p class="mt-4 max-w-xl opacity-80 font-light tracking-wide leading-relaxed text-white text-lg">
            Manage your finances effectively with a clear vision of your collections and dues.
          </p>
        </div>
      </section>
      <!-- Overall Summary -->
      <section class="mb-12 grid grid-cols-1 md:grid-cols-2 gap-10">
        <div class="rounded-3xl p-10 border border-gray-300 dark:border-gray-700 shadow-lg text-gray-900 dark:text-white">
          <h3 class="text-2xl font-bold mb-6">Overall Financial Summary</h3>
          <div class="space-y-8">
            <div v-for="total in overallTotals" :key="total.title" class="flex items-center space-x-5">
              <i :class="[total.icon, 'text-5xl text-indigo-400 flex-shrink-0']"></i>
              <div>
                <div class="text-sm opacity-70">{{ total.title }}</div>
                <div class="text-4xl font-extrabold">{{ formatBDT(Number(total.value)) }}</div>
              </div>
            </div>
          </div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
          <div v-for="card in cardItems" :key="card.title"
               :class="[
                  'p-8 rounded-2xl border-t-4 border-gray-300 dark:border-gray-700 shadow-md hover:shadow-xl transition cursor-pointer bg-white dark:bg-gray-800',
                  `border-${card.color}-400`,
                  `hover:border-${card.color}-600`
               ]" >
            <div class="flex justify-between items-center">
              <div>
                <h4 :class="[`text-md font-semibold mb-3 text-${card.color}-600 dark:text-${card.color}-400`]">
                  {{ card.title }}
                </h4>
                <p class="text-3xl font-extrabold text-gray-900 dark:text-white">
                  {{ card.isCurrency ? formatBDT(Number(card.value)) : card.value }}
                </p>
              </div>
              <div class="text-5xl text-opacity-25" :class="`text-${card.color}-400`">
                <i :class="card.icon" v-if="!card.isCurrency"></i>
                <span v-else class="text-4xl font-bold">৳</span>
              </div>
            </div>
          </div>
        </div>
      </section>
      <!-- Quick Reports -->
      <section v-if="reportLinks" class="mt-10">
        <h3 class="font-semibold text-xl mb-6 text-gray-900 dark:text-gray-100">📂 Quick Financial Reports</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
          <a v-for="(url, key) in reportLinks" :key="key" :href="url"
             class="py-4 px-6 rounded-xl border border-gray-300 dark:border-gray-700 hover:bg-indigo-600 hover:text-white transition text-center font-semibold capitalize">
            {{ key.replace(/([A-Z])/g, ' $1') }}
          </a>
        </div>
      </section>
    </section>
  </AuthenticatedLayout>
</template>
<style scoped>
@keyframes blink {
  50% { opacity: 0; }
}
.typewriter-cursor {
  font-weight: 600;
  font-size: 1.4rem;
  animation: blink 0.7s infinite step-end;
  display: inline-block;
  vertical-align: bottom;
  color: #a78bfa; /* violet-400 */
}
</style>
