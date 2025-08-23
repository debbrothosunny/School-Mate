<script setup lang="ts">
import { ref, reactive, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Bar } from 'vue-chartjs';
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale,
  ChartOptions,
  ChartData
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps<{
  filters: { start_date: string; end_date: string };
  datasets: { label: string; data: number[]; backgroundColor: string; stack: string }[];
  labels: string[];
  totalAmountPaidThisYear: number;
  totalAmountPaidAllTime: number;
  totalAmountPaidSelectedRange: number;
}>();

const filterDates = reactive({
  start_date: props.filters.start_date,
  end_date: props.filters.end_date,
});

const chartData = computed<ChartData<'bar'>>(() => ({
  labels: props.labels,
  datasets: props.datasets,
}));

const chartOptions = ref<ChartOptions<'bar'>>({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'top',
      align: 'start',
      labels: {
        color: '#A0AEC0',
        boxWidth: 16,
        boxHeight: 16,
        padding: 20
      },
    },
    title: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: (context) => {
          const value = context.parsed.y as number;
          return `${context.dataset.label}: ${value}`;
        }
      },
    },
  },
  scales: {
    x: {
      stacked: true,
      grid: {
        color: 'rgba(255, 255, 255, 0.1)'
      },
      ticks: {
        color: '#A0AEC0'
      }
    },
    y: {
      stacked: true,
      beginAtZero: true,
      grid: {
        color: 'rgba(255, 255, 255, 0.1)'
      },
      ticks: {
        color: '#A0AEC0'
      }
    }
  }
});

watch(filterDates, () => {
  console.log('Fetching new data for date range:', filterDates.start_date, 'to', filterDates.end_date);
  router.get(route('reports.income_statement'), {
    start_date: filterDates.start_date,
    end_date: filterDates.end_date,
  }, {
    preserveState: true,
    replace: true,
  });
});
</script>

<template>
  <div class="bg-gray-900 min-h-screen p-6 font-inter text-gray-100">
    <div class="max-w-6xl mx-auto space-y-6">
      
      <!-- Header -->
      <header class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0 p-4 bg-gray-800 rounded-lg shadow-lg">
        <h1 class="text-3xl font-bold">Income Statement</h1>
        <div class="flex items-center space-x-4">
          <input
            type="date"
            v-model="filterDates.start_date"
            class="bg-gray-700 text-gray-100 border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
          <input
            type="date"
            v-model="filterDates.end_date"
            class="bg-gray-700 text-gray-100 border-gray-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>
      </header>

      <!-- Income Summary Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
          <p class="text-lg font-semibold text-gray-300 mb-2">Income for Selected Range</p>
          <p class="text-3xl font-bold text-blue-400">
            ৳ {{ totalAmountPaidSelectedRange.toLocaleString() }}
          </p>
        </div>
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
          <p class="text-lg font-semibold text-gray-300 mb-2">This Year's Income</p>
          <p class="text-3xl font-bold text-green-400">
            ৳ {{ totalAmountPaidThisYear.toLocaleString() }}
          </p>
        </div>
        <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
          <p class="text-lg font-semibold text-gray-300 mb-2">Total Paid Amount (All Time)</p>
          <p class="text-3xl font-bold text-indigo-400">
            ৳ {{ totalAmountPaidAllTime.toLocaleString() }}
          </p>
        </div>
      </div>

      <!-- Chart Section -->
      <div class="bg-gray-800 p-6 rounded-lg shadow-lg">
        <p class="text-lg font-semibold mb-4 text-gray-200">Income Breakdown</p>
        <div class="h-96">
          <Bar :data="chartData" :options="chartOptions" />
        </div>
      </div>

      <!-- Back to Dashboard Button -->
      <div class="text-center mt-8">
        <button
          @click="router.visit(route('accounts.dashboard'))"
          class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg shadow transition-transform transform hover:scale-105"
        >
          ← Back to Dashboard
        </button>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* Add any scoped styles here if needed */
</style>
