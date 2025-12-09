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

ChartJS.register(Title, Tooltip, Legend, ArcElement)

// Props
const props = defineProps({
  cards: Object,
  attendanceStats: Object,
  activeSince: String,
})

// BDT Formatter
const formatBDT = (value) => {
  if (!value) return '৳0'
  return new Intl.NumberFormat('bn-BD', {
    style: 'currency',
    currency: 'BDT',
    minimumFractionDigits: 0,
  }).format(value)
}

// Financial Summaries
const collectionSummary = computed(() => ({
  overall: props.cards?.totalCollection || 0,
  monthly: props.cards?.monthlyCollection || 0,
  yearly: props.cards?.yearlyCollection || 0,
}))

const profitSummary = computed(() => ({
  overall: props.cards?.overallProfit || 0,
  monthly: props.cards?.monthlyProfit || 0,
  yearly: props.cards?.yearlyProfit || 0,
}))

const dueSummary = computed(() => ({
  overall: props.cards?.totalDue || 0,
  monthly: props.cards?.monthlyDue || 0,
  yearly: props.cards?.yearlyDue || 0,
}))

// Fixed: Admission Fee Collected (was missing!)
const admissionFeeCollected = computed(() => props.cards?.totalAdmissionFeeCollected || 0)

// Toggle States
const showCollectionDetails = ref(false)
const showProfitDetails = ref(false)
const showDueDetails = ref(false)
const showAdmissionDetails = ref(false)

const toggleCollectionDetails = () => showCollectionDetails.value = !showCollectionDetails.value
const toggleProfitDetails = () => showProfitDetails.value = !showProfitDetails.value
const toggleDueDetails = () => showDueDetails.value = !showDueDetails.value
const toggleAdmissionDetails = () => showAdmissionDetails.value = !showAdmissionDetails.value

// Count Cards (including Admission Fee only if > 0)
const countAndDueCardItems = computed(() => {
  const items = [
    { title: 'Total Students', value: props.cards?.totalStudents || 0, icon: 'fas fa-user-graduate', color: 'bg-indigo-500' },
    { title: 'Total Teachers', value: props.cards?.totalTeachers || 0, icon: 'fas fa-chalkboard-teacher', color: 'bg-teal-500' },
    { title: 'Total Classes', value: props.cards?.totalActiveClasses || 0, icon: 'fas fa-school', color: 'bg-sky-500' },
    { title: 'Total Sections', value: props.cards?.totalSections || 0, icon: 'fas fa-sitemap', color: 'bg-fuchsia-500' },
    { title: 'Total Groups', value: props.cards?.totalGroups || 0, icon: 'fas fa-users', color: 'bg-orange-500' },
    { title: 'Total Sessions', value: props.cards?.totalSessions || 0, icon: 'fas fa-calendar-alt', color: 'bg-cyan-500' },
    { title: 'Pending Registration', value: props.cards?.totalPendingRegistrations || 0, icon: 'fas fa-user-clock', color: 'bg-amber-600' },
    // Admission Fee as Amount Card
    { 
      title: 'Admission Fee Collected', 
      value: admissionFeeCollected.value, 
      icon: 'fas fa-graduation-cap', 
      color: 'bg-purple-600',
      isAmount: true 
    },
  ]
  return items.filter(item => item.value > 0)
})

// Live Clock & Greeting
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

  currentDate.value = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })
  currentTime.value = `${h12}:${mins}:${secs} ${ampm}`
}

onMounted(() => {
  updateDateTime()
  intervalId = setInterval(updateDateTime, 1000)
  typeWriterEffect("Welcome back, Sir. Let's explore your school data today.")
})

onUnmounted(() => clearInterval(intervalId))

// Typewriter Effect
const typedMessage = ref('')
const isTyping = ref(false)
const typeWriterEffect = (text) => {
  let i = 0
  typedMessage.value = ''
  isTyping.value = true
  const interval = setInterval(() => {
    if (i < text.length) typedMessage.value += text.charAt(i++)
    else { clearInterval(interval); isTyping.value = false }
  }, 50)
}

// Pie Chart Data
const monthlyAttendancePieChartData = {
  labels: props.attendanceStats?.labels || [],
  datasets: [{
    backgroundColor: ['#10B981', '#EF4444', '#F59E0B', '#8B5CF6'],
    data: props.attendanceStats?.data || [],
    hoverOffset: 16,
  }]
}

const monthlyAttendancePieChartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { position: 'right', labels: { color: '#374151' } },
    title: { display: true, text: 'Monthly Student Attendance Overview', color: '#374151', font: { size: 20, weight: 'bold' } }
  }
}
</script>
<template>
  <Head title="Dashboard" />

  <AuthenticatedLayout>
    <div class="text-center text-gray-800 pt-6 pb-4 bg-white/70 backdrop-blur-md sticky top-0 z-10 shadow-lg">
      <div class="text-3xl font-bold mb-2">
        {{ greeting }}
      </div>
      <div class="text-lg font-light flex justify-center items-center space-x-4">
        <span class="text-indigo-600 font-semibold">{{ currentDate }}</span>
        <span class="text-gray-400 text-xl">|</span>
        <span class="text-indigo-600 font-semibold">{{ currentTime }}</span>
        <i :class="timeIconClass" class="text-xl ml-2 transition-all duration-500"></i>
      </div>
      <div v-if="activeSince" class="text-sm mt-1 text-gray-500">
        Account Active Since: {{ activeSince }}
      </div>
    </div>
    
    <div class="dashboard-container w-full min-h-screen bg-gray-100 py-8 px-4 main-bg-style">
      <div class="max-w-7xl mx-auto">
        
        <div class="bg-white/70 shadow-3xl rounded-3xl p-8 mb-8 backdrop-blur-md glass-container">
          
          <h3 class="text-gray-900 text-2xl mb-6 font-extrabold typewriter-container">
            {{ typedMessage }}<span :class="{'typewriter-cursor': isTyping}">|</span>
          </h3>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-8 mb-8">
            
            <div 
              class="collection-card-glass col-span-1 sm:col-span-2 shadow-2xl transition-all duration-300 transform hover:translate-y-[-4px]"
            >
              <div class="flex flex-col items-center justify-center text-center pb-4 border-b border-green-200">
                <div class="flex items-center space-x-6 w-full">
                  <div class="elevated-icon-wrapper bg-green-500/90 shadow-lg transition-all duration-300">
                    <i class="fas fa-hand-holding-usd text-4xl text-white"></i>
                  </div>
                  <div class="text-left flex-grow">
                    <p class="text-lg font-medium text-gray-600">Overall Collection</p>
                    <h4 class="text-3xl font-extrabold text-green-700">৳{{ collectionSummary.overall }}</h4>
                  </div>
                </div>
                
                <button 
                  @click="toggleCollectionDetails" 
                  class="mt-4 px-6 py-2 text-md font-bold rounded-full transition-all duration-300 transform hover:scale-105 shadow-md"
                  :class="showCollectionDetails 
                    ? 'bg-red-500 text-white hover:bg-red-600' 
                    : 'bg-green-500 text-white hover:bg-green-600'"
                >
                  <i :class="showCollectionDetails ? 'fas fa-minus' : 'fas fa-plus'" class="mr-2"></i>
                  {{ showCollectionDetails ? 'Hide Breakdown' : 'Show Breakdown' }}
                </button>
              </div>
              
              <div v-show="showCollectionDetails" class="pt-4 animate-fadeIn">
                <div class="space-y-3">
                  <div class="flex justify-between items-center p-3 rounded-lg bg-white/80 border-l-4 border-green-400">
                    <span class="text-base font-semibold text-gray-700">This Month</span>
                    <span class="text-2xl font-extrabold text-green-600">৳{{ collectionSummary.monthly }}</span>
                  </div>
                  <div class="flex justify-between items-center p-3 rounded-lg bg-white/80 border-l-4 border-green-400">
                    <span class="text-base font-semibold text-gray-700">This Year</span>
                    <span class="text-2xl font-extrabold text-green-600">৳{{ collectionSummary.yearly }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div 
              class="collection-card-glass col-span-1 sm:col-span-2 shadow-2xl transition-all duration-300 transform hover:translate-y-[-4px]"
            >
              <div class="flex flex-col items-center justify-center text-center pb-4 border-b border-indigo-200">
                <div class="flex items-center space-x-6 w-full">
                  <div class="elevated-icon-wrapper bg-indigo-500/90 shadow-lg transition-all duration-300">
                    <i class="fas fa-chart-line text-4xl text-white"></i>
                  </div>
                  <div class="text-left flex-grow">
                    <p class="text-lg font-medium text-gray-600">Overall Net Profit</p>
                    <h4 class="text-3xl font-extrabold text-indigo-700">৳{{ profitSummary.overall }}</h4>
                  </div>
                </div>
                
                <button 
                  @click="toggleProfitDetails" 
                  class="mt-4 px-6 py-2 text-md font-bold rounded-full transition-all duration-300 transform hover:scale-105 shadow-md"
                  :class="showProfitDetails 
                    ? 'bg-red-500 text-white hover:bg-red-600' 
                    : 'bg-indigo-500 text-white hover:bg-indigo-600'"
                >
                  <i :class="showProfitDetails ? 'fas fa-minus' : 'fas fa-plus'" class="mr-2"></i>
                  {{ showProfitDetails ? 'Hide Breakdown' : 'Show Breakdown' }}
                </button>
              </div>
              
              <div v-show="showProfitDetails" class="pt-4 animate-fadeIn">
                <div class="space-y-3">
                  <div class="flex justify-between items-center p-3 rounded-lg bg-white/80 border-l-4 border-indigo-400">
                    <span class="text-base font-semibold text-gray-700">This Month</span>
                    <span class="text-2xl font-extrabold text-indigo-600">৳{{ profitSummary.monthly }}</span>
                  </div>
                  <div class="flex justify-between items-center p-3 rounded-lg bg-white/80 border-l-4 border-indigo-400">
                    <span class="text-base font-semibold text-gray-700">This Year</span>
                    <span class="text-2xl font-extrabold text-indigo-600">৳{{ profitSummary.yearly }}</span>
                  </div>
                </div>
              </div>
            </div>

            <div 
              class="collection-card-glass col-span-1 sm:col-span-2 shadow-2xl transition-all duration-300 transform hover:translate-y-[-4px]"
            >
              <div class="flex flex-col items-center justify-center text-center pb-4 border-b border-red-200">
                <div class="flex items-center space-x-6 w-full">
                  <div class="elevated-icon-wrapper bg-red-500/90 shadow-lg transition-all duration-300">
                    <i class="fas fa-money-bill-wave text-4xl text-white"></i>
                  </div>
                  <div class="text-left flex-grow">
                    <p class="text-lg font-medium text-gray-600">Overall Balance Due</p>
                    <h4 class="text-3xl font-extrabold text-red-700">৳{{ dueSummary.overall }}</h4>
                  </div>
                </div>
                
                <button 
                  @click="toggleDueDetails" 
                  class="mt-4 px-6 py-2 text-md font-bold rounded-full transition-all duration-300 transform hover:scale-105 shadow-md"
                  :class="showDueDetails 
                    ? 'bg-red-500 text-white hover:bg-red-600' 
                    : 'bg-red-500 text-white hover:bg-red-600'"
                >
                  <i :class="showDueDetails ? 'fas fa-minus' : 'fas fa-plus'" class="mr-2"></i>
                  {{ showDueDetails ? 'Hide Breakdown' : 'Show Breakdown' }}
                </button>
              </div>
              
              <div v-show="showDueDetails" class="pt-4 animate-fadeIn">
                <div class="space-y-3">
                  <div class="flex justify-between items-center p-3 rounded-lg bg-white/80 border-l-4 border-red-400">
                    <span class="text-base font-semibold text-gray-700">This Month Due</span>
                    <span class="text-2xl font-extrabold text-red-600">৳{{ dueSummary.monthly }}</span>
                  </div>
                  <div class="flex justify-between items-center p-3 rounded-lg bg-white/80 border-l-4 border-red-400">
                    <span class="text-base font-semibold text-gray-700">This Year Due</span>
                    <span class="text-2xl font-extrabold text-red-600">৳{{ dueSummary.yearly }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="grid grid-cols-2 sm:grid-cols-3 **lg:grid-cols-7** gap-8 mb-8">
            <div
              v-for="(card, idx) in countAndDueCardItems"
              :key="card.title"
              class="card-glass shadow-xl transition-all duration-300 transform hover:translate-y-[-4px] hover:shadow-2xl"
            >
              <div class="p-6 h-full flex flex-col items-center justify-center text-center">
                
                <div :class="['elevated-icon-wrapper', card.color, 'shadow-lg']">
                  <span v-if="card.type === 'amount'" class="text-4xl font-bold text-white">৳</span>
                  <i v-else :class="[card.icon, 'text-4xl text-white']"></i>
                </div>
                
                <h4 class="text-4xl font-extrabold mt-4 text-gray-800">{{ card.value }}</h4>
                
                <p class="mt-1 text-base font-medium text-gray-600">{{ card.title }}</p>
              </div>
            </div>
          </div>
          <h4 class="text-xl font-bold text-gray-700 mt-12 mb-6 border-b pb-2">Attendance Analytics</h4>
          <div class="bg-white/70 rounded-xl shadow-xl p-8 border border-white/40 backdrop-blur-sm">
            <div class="w-full mx-auto" style="max-width:650px; height:500px;">
              <Pie :data="monthlyAttendancePieChartData" :options="monthlyAttendancePieChartOptions" />
            </div>
            <p v-if="attendanceStats.data.every(v => v === 0)" class="text-center text-gray-500 mt-4 text-lg">
              No attendance data available for your current view.
            </p>
          </div>
        </div>
      </div>
      
      <div class="mt-12 pt-6 border-t border-gray-300 text-center bg-gray-100/50 backdrop-blur-sm">
        <p class="text-sm md:text-base text-gray-600 font-semibold leading-relaxed mx-auto">
          © All Rights Reserved. Biddaloy is a product of 
          <a href="https://smithitbd.com/" target="_blank"
            class="font-extrabold text-red-600 hover:text-red-700 transition-colors hover:underline">
            Smith&nbsp;IT
          </a>
        </p>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');

.dashboard-container {
  font-family: 'Poppins', sans-serif;
}

/* ------------------ Base & Animations ------------------ */

.main-bg-style {
    background: linear-gradient(135deg, #f0f4f8 0%, #e0e7ee 100%);
}

.sun-animation { color: #FACC15; animation: rotateSun 5s linear infinite; }
.moon-animation { color: #60A5FA; animation: rotateMoon 5s linear infinite; }
@keyframes rotateSun { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
@keyframes rotateMoon { from { transform: rotate(0deg); } to { transform: rotate(-360deg); } }

.typewriter-cursor {
  display: inline-block;
  vertical-align: bottom;
  width: 0.5em;
  height: 1.2em;
  background-color: currentColor;
  animation: blink 0.7s step-end infinite;
}
@keyframes blink { from, to { opacity: 1; } 50% { opacity: 0; } }
@keyframes fadeSlideIn {
  0% { opacity: 0; transform: translateY(15px); }
  100% { opacity: 1; transform: translateY(0); }
}
.typewriter-container { animation: fadeSlideIn 1.2s ease-out forwards; }

.animate-fadeIn {
  animation: fadeIn 0.4s ease-out forwards;
}
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* ------------------ Card Styles (Glassmorphism & Elevated) ------------------ */

.card-glass, .collection-card-glass {
    border-radius: 1.5rem;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.7);
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.3);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    transition: all 0.3s ease-in-out;
}

.elevated-icon-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
    
    box-shadow: 
        0 10px 20px rgba(0, 0, 0, 0.3),
        inset 0 0 10px rgba(255, 255, 255, 0.5);
    
    line-height: 0;
    font-weight: 700;
}
.card-glass .elevated-icon-wrapper {
    margin-bottom: 1.5rem;
}
</style>
