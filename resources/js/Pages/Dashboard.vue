<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
// Changed Pie to Bar for a different chart type
import { Bar } from 'vue-chartjs'; 
import { Chart as ChartJS, Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale } from 'chart.js';

// Register Chart.js components required for a Bar chart
// Added Title, BarElement, CategoryScale, LinearScale
ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    cards: Object,       // Object with totalTeachers, totalSections, etc.
    attendanceStats: Object, // Object with labels and data for the chart
    message: String,     // Welcome message based on role
});

// Chart data setup - remains largely the same, but for a Bar chart
const chartData = {
    labels: props.attendanceStats.labels, // e.g., ['Present', 'Absent', 'Late']
    datasets: [
        {
            label: 'Attendance Count', // Label for the dataset
            backgroundColor: ['#42A5F5', '#FF6384', '#FFCE56'], // Colors for Present, Absent, Late
            data: props.attendanceStats.data, // e.g., [100, 20, 5]
            borderRadius: 8, // Add rounded corners to bars
        }
    ]
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false, // Allow Chart.js to manage aspect ratio more freely
    plugins: {
        legend: {
            display: false, // Often hidden for single-dataset bar charts if labels are clear
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (context.parsed.y !== null) {
                        label += context.parsed.y; // Display the raw count for bar chart
                    }
                    return label;
                }
            }
        },
        title: {
            display: true,
            text: 'Monthly Student Attendance Overview', // Chart title
            font: {
                size: 16,
                weight: 'bold'
            },
            padding: {
                top: 10,
                bottom: 20
            }
        }
    },
    scales: {
        x: {
            grid: {
                display: false // Hide x-axis grid lines
            }
        },
        y: {
            beginAtZero: true, // Start y-axis at 0
            ticks: {
                precision: 0 // Ensure integer ticks for count
            },
            grid: {
                color: '#e2e8f0' // Light gray grid lines
            }
        }
    }
};

// Card data array for easier iteration
const cardItems = [
    { title: 'Total Teachers', value: props.cards.totalTeachers },
    { title: 'Total Sections', value: props.cards.totalSections },
    { title: 'Total Students', value: props.cards.totalStudents },
    { title: 'Total Active Classes', value: props.cards.totalActiveClasses },
].filter(item => item.value !== 0); // Filter out cards with 0 value if not relevant for a role
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
        </template>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ message }}</h3>

                <!-- Dashboard Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                    <div v-for="card in cardItems" :key="card.title" 
                         class="bg-blue-100 p-4 rounded-lg shadow-md text-center flex flex-col justify-center items-center">
                        <h4 class="text-3xl font-extrabold text-blue-800">{{ card.value }}</h4>
                        <p class="text-blue-600 mt-1 text-lg">{{ card.title }}</p>
                    </div>
                </div>

                <!-- Monthly Student Attendance Statistics Section -->
                <div class="mb-8">
                    
                    
                    <!-- Bar Chart Container - Centered and responsive -->
                    <div class="flex justify-center items-center w-full">
                        <div class="relative w-full max-w-2xl mx-auto" style="height: 400px;"> <!-- Increased max-width and height for bar chart -->
                            <Bar
                                :data="chartData"
                                :options="chartOptions"
                            />
                        </div>
                    </div>

                    <p v-if="props.attendanceStats.data.every(val => val === 0)" class="text-center text-gray-500 mt-4">
                        No attendance data available for your current view.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* You might need additional styles for responsiveness if default Tailwind is not enough,
   but most common alignment issues are handled by flexbox/grid classes. */
</style>
