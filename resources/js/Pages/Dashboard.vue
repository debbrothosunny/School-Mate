<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head } from '@inertiajs/vue3'
import { Pie } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  ArcElement
} from 'chart.js'
import { ref, onMounted, onUnmounted, computed } from 'vue'
// Register Chart.js components
ChartJS.register(Title, Tooltip, Legend, ArcElement)
// Define incoming props
const props = defineProps({
  cards: Object,
  attendanceStats: Object,
  activeSince: String,
  presentCount: Number,
  absentCount: Number,
  lateCount: Number,
})
// Prepare pie-chart data and options
const monthlyAttendancePieChartData = {
  labels: props.attendanceStats.labels,
  datasets: [
    {
      backgroundColor: [
        '#8B5CF6', '#FACC15', '#F97316', '#3B82F6',
        '#4B5563', '#2DD4BF', '#EC4899', '#60A5FA',
        '#10B981', '#F87171', '#A8A29E', '#6EE7B7',
      ],
      data: props.attendanceStats.data,
      hoverOffset: 16,
    },
  ],
}
const monthlyAttendancePieChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'right',
      labels: { color: '#374151' },
    },
    tooltip: {
      callbacks: {
        label(context) {
          let label = context.label ?? ''
          if (label) label += ': '
          if (context.parsed !== null) {
            const total = context.dataset.data.reduce((sum, v) => sum + v, 0)
            const value = context.parsed
            const pct = total > 0
              ? ((value / total) * 100).toFixed(2)
              : 0
            label += `${value} (${pct}%)`
          }
          return label
        },
      },
    },
    title: {
      display: true,
      text: 'Monthly Student Attendance Overview',
      color: '#374151',
      font: { size: 20, weight: 'bold' },
      padding: { top: 10, bottom: 20 },
    },
  },
}
// Pastel background colors only
const cardBgColors = [
  'bg-blue-50',
  'bg-green-50',
  'bg-yellow-50',
  'bg-purple-50',
  'bg-red-50',
  'bg-indigo-50',
  'bg-pink-50',
]
// Build cards array, filter out zeros
const cardItems = computed(() => {
  const items = [
    { title: 'Total Teachers', value: props.cards?.totalTeachers, icon: 'fas fa-chalkboard-teacher', type: 'count' },
    { title: 'Total Sections', value: props.cards?.totalSections, icon: 'fas fa-sitemap', type: 'count' },
    { title: 'Total Sessions', value: props.cards?.totalSessions, icon: 'fas fa-calendar-alt', type: 'count' },
    { title: 'Total Groups', value: props.cards?.totalGroups, icon: 'fas fa-users', type: 'count' },
    { title: 'Total Students', value: props.cards?.totalStudents, icon: 'fas fa-user-graduate', type: 'count' },
    { title: 'Total Classes', value: props.cards?.totalActiveClasses, icon: 'fas fa-school', type: 'count' },
    { title: 'This Month Collection', value: props.cards?.monthlyCollection, icon: 'fas fa-money-check-alt', type: 'amount' },
    { title: 'This Month Due', value: props.cards?.monthlyDue, icon: 'fas fa-exclamation-circle', type: 'amount' },
    { title: 'This Year Collection', value: props.cards?.yearlyCollection, icon: 'fas fa-chart-line', type: 'amount' },
    { title: 'Overall Collection', value: props.cards?.totalCollection, icon: 'fas fa-piggy-bank', type: 'amount' },
    { title: 'Total Due', value: props.cards?.totalDue, icon: 'fas fa-balance-scale', type: 'amount' },
  ];
  return items.filter(item => item.value !== 0);
});
// Greeting & live clock
const greeting = ref('')
const timeIconClass = ref('')
const currentDate = ref('')
const currentTime = ref('')
let intervalId = null
function updateDateTime() {
  const now = new Date()
  const hrs = now.getHours()
  const mins = String(now.getMinutes()).padStart(2, '0')
  const secs = String(now.getSeconds()).padStart(2, '0')
  const ampm = hrs >= 12 ? 'PM' : 'AM'
  const h12 = hrs % 12 || 12
  if (hrs >= 5 && hrs < 12) {
    greeting.value = 'Good Morning!'
    timeIconClass.value = 'fas fa-sun sun-animation'
  } else if (hrs < 17) {
    greeting.value = 'Good Afternoon!'
    timeIconClass.value = 'fas fa-sun sun-animation'
  } else if (hrs < 21) {
    greeting.value = 'Good Evening!'
    timeIconClass.value = 'fas fa-moon moon-animation'
  } else {
    greeting.value = 'Good Night!'
    timeIconClass.value = 'fas fa-moon moon-animation'
  }
  currentDate.value = now.toLocaleDateString('en-US', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
  })
  currentTime.value = `${h12}:${mins}:${secs} ${ampm}`
}
onMounted(() => {
  updateDateTime()
  intervalId = setInterval(updateDateTime, 1000)
})
onUnmounted(() => {
  clearInterval(intervalId)
})
// Typewriter effect logic
const typedMessage = ref('');
const isTyping = ref(false);
const typingSpeed = 50; // Milliseconds per character
const messageToType = "Good to have you back, Admin. Let's explore your school's data today."
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
// Start the typewriter effect when the component is mounted
onMounted(() => {
  typeWriterEffect(messageToType);
});
</script>

<template>
  <Head title="Dashboard" />
  <AuthenticatedLayout>
    <div class="dashboard-container w-full min-h-screen bg-white py-8 px-4">
      <div class="max-w-7xl mx-auto">
        <!-- Header & Cards -->
        <div class="bg-white shadow-xl rounded-lg p-6 mb-8">
          <!-- Dynamic message with typewriter effect -->
          <h3 class="text-gray-800 text-xl mb-4 font-semibold typewriter-container">
            {{ typedMessage }}<span :class="{'typewriter-cursor': isTyping}">|</span>
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div
              v-for="(card, idx) in cardItems"
              :key="card.title"
              :class="[cardBgColors[idx % cardBgColors.length], 'rounded-lg p-4 shadow-lg transition transform hover:scale-105']"
              class="flex flex-col items-center justify-center"
            >
              <!-- Conditionally render icon or Tk symbol -->
              <span v-if="card.type === 'amount'" class="text-3xl font-bold text-black mb-2">৳</span>
              <i v-else :class="[card.icon, 'text-2xl mb-2 text-black']"></i>
              
              <h4 class="text-3xl font-bold text-black">{{ card.value }}</h4>
              <p class="mt-2 text-lg font-medium text-black">{{ card.title }}</p>
            </div>
          </div>
          <!-- Attendance Pie Chart -->
          <div class="bg-white rounded-lg shadow-md p-6">
            <div class="w-full mx-auto" style="max-width:600px; height:500px;">
              <Pie :data="monthlyAttendancePieChartData" :options="monthlyAttendancePieChartOptions" />
            </div>
            <p v-if="attendanceStats.data.every(v => v === 0)" class="text-center text-gray-500 mt-4">
              No attendance data available for your current view.
            </p>
          </div>
        </div>
        <!-- Greeting & Time -->
        <div class="text-center text-gray-700 mt-8">
          <div class="text-2xl font-semibold">
            {{ greeting }}
            <i :class="timeIconClass" class="ml-2"></i>
          </div>
          <div class="text-lg font-light mt-2">
            <span class="text-indigo-500">{{ currentDate }}</span>
            <span class="mx-2">|</span>
            <span class="text-indigo-500">{{ currentTime }}</span>
          </div>
          <div v-if="activeSince" class="text-lg mt-2 text-gray-500">
            Active Since: {{ activeSince }}
          </div>
          <div class="mt-4 italic text-gray-400">
            <small>The journey of a thousand miles begins with a single step.</small>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap');

.dashboard-container {
  font-family: 'Poppins', sans-serif;
}

.sun-animation {
  color: #FACC15;
  animation: rotateSun 5s linear infinite;
}
.moon-animation {
  color: #60A5FA;
  animation: rotateMoon 5s linear infinite;
}
@keyframes rotateSun {
  from { transform: rotate(0deg); }
  to    { transform: rotate(360deg); }
}
@keyframes rotateMoon {
  from { transform: rotate(0deg); }
  to    { transform: rotate(-360deg); }
}

/* New CSS for the typewriter effect */
.typewriter-cursor {
  display: inline-block;
  vertical-align: bottom;
  width: 0.5em;
  height: 1.2em;
  background-color: currentColor;
  animation: blink 0.7s step-end infinite;
}
@keyframes blink {
  from, to { opacity: 1; }
  50% { opacity: 0; }
}

/* Fade and slide animation for typewriter container */
@keyframes fadeSlideIn {
  0% {
    opacity: 0;
    transform: translateY(20px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}
.typewriter-container {
  animation: fadeSlideIn 1s ease forwards;
}

</style>
