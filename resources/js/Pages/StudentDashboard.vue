<script setup>
import { computed, ref, watch, defineProps } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
// Access SweetAlert2 from the global window object.
const Swal = window.Swal;

// Define the props for the component
const props = defineProps({
    message: String,
    userName: String,
    studentTimetable: Array,
    studentExams: {
        type: Array,
        default: () => [],
    },
    studentAttendance: {
        type: Object,
        default: () => ({
            totalClasses: 0,
            attendedClasses: 0,
            percentage: 0,
        }),
    },
    upcomingFeeNotice: {
        type: Object,
        default: null,
    },
});

// Watch for changes in the upcomingFeeNotice prop.
// If it's not null, display the modal.
watch(() => props.upcomingFeeNotice, (newValue) => {
    // Check if both the new value and SweetAlert2 are available
    if (newValue && Swal) {
        // Use SweetAlert2 to show the fee notice popup
        Swal.fire({
            icon: 'warning',
            title: 'Urgent: Fee Payment Due',
            html: `
                <div class="text-left leading-relaxed">
                    <p class="font-semibold text-lg">Hello ${props.userName},</p>
                    <p class="mt-2 text-gray-700">
                        This is an urgent reminder that your <strong>${newValue.invoice_type || 'fee'}</strong>
                        with a balance of <strong>BDT ${newValue.balance_due}</strong> is due on <strong>${newValue.due_date}</strong>.
                    </p>
                    <p class="mt-4 text-sm text-gray-500">
                        Your attention is kindly requested to settle this payment at your earliest convenience. Please proceed to your My Invoices page to make a payment.
                    </p>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'Understood',
            customClass: {
                title: 'text-red-600',
                confirmButton: 'bg-red-500 hover:bg-red-600 focus:ring-red-500',
            }
        });
    }
}, { immediate: true }); // immediate: true ensures the watcher runs on initial component load

// Define days of the week for grouping and display
const daysOfWeek = [
    { value: 'MONDAY', label: 'Monday' },
    { value: 'TUESDAY', label: 'Tuesday' },
    { value: 'WEDNESDAY', label: 'Wednesday' },
    { value: 'THURSDAY', label: 'Thursday' },
    { value: 'FRIDAY', label: 'Friday' },
    { value: 'SATURDAY', label: 'Saturday' },
    { value: 'SUNDAY', label: 'Sunday' },
];

// Helper function to format time (e.g., "09:00" to "09:00 AM")
const formatTime = (timeString) => {
    if (!timeString) return 'N/A';
    try {
        const [hours, minutes] = timeString.split(':');
        const date = new Date();
        date.setHours(parseInt(hours));
        date.setMinutes(parseInt(minutes));
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch (e) {
        console.error("Error formatting time:", e);
        return timeString; // Fallback
    }
};

// Helper function to format date
const formatDate = (dateString) => {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString(); // Formats to a user-friendly date string
    } catch (e) {
        console.error("Error formatting date:", e);
        return dateString; // Fallback
    }
};

// Computed property to group timetable entries by day for easier rendering
const groupedStudentTimetable = computed(() => {
    const grouped = {};
    daysOfWeek.forEach(day => {
        grouped[day.value] = props.studentTimetable
            .filter(entry => entry.day_of_week === day.value)
            .sort((a, b) => {
                const timeA = a.start_time;
                const timeB = b.start_time;
                if (timeA < timeB) return -1;
                if (timeA > timeB) return 1;
                return 0;
            });
    });
    return grouped;
});

// Computed property to get unique subjects
const uniqueSubjects = computed(() => {
    const subjects = new Set();
    props.studentTimetable.forEach(entry => {
        if (entry.subject && entry.subject.name) {
            subjects.add(entry.subject.name);
        }
    });
    return Array.from(subjects).sort();
});

// Typewriter effect logic
const typedMessage = ref('');
const isTyping = ref(false);
const typingSpeed = 50; // Milliseconds per character

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

// Watch for changes in the 'message' prop and trigger the typewriter effect
watch(() => props.message, (newMessage) => {
    if (newMessage) {
        typeWriterEffect(newMessage);
    }
}, { immediate: true });
</script>

<template>
    <Head title="My Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Student Dashboard</h2>
        </template>

        <div class="py-12 bg-gray-100 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8">

                    <!-- Welcome Section with typewriter effect -->
                    <div class="mb-8">
                        <!-- Heading with a fade-in animation -->
                        <h3 class="text-3xl font-extrabold text-gray-900 mb-2 animate-fade-in-up">
                            Welcome, <span class="text-indigo-600">{{ userName ?? 'Student' }}</span>
                        </h3>
                        <!-- Paragraph with typewriter effect and conditional blinking cursor -->
                        <p class="text-lg text-gray-600 font-mono">
                            {{ typedMessage }}<span :class="{'typewriter-cursor': isTyping}">|</span>
                        </p>
                    </div>

                    <!-- Top Row: Summary Cards (Attendance & Subjects) - Styled like the accountant dashboard -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <!-- Attendance Card -->
                        <div class="bg-white p-6 rounded-xl shadow-md transform transition-transform duration-300 hover:scale-105 border-t-4 border-indigo-400">
                            <h4 class="text-lg font-semibold text-indigo-700 mb-2">Attendance</h4>
                            <div class="flex items-center justify-between">
                                <p class="text-3xl font-extrabold text-gray-900 mt-2">
                                    {{ studentAttendance.percentage.toFixed(0) }}<span class="text-xl font-normal">%</span>
                                </p>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-indigo-500 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                {{ studentAttendance.attendedClasses }} / {{ studentAttendance.totalClasses }} attended
                            </p>
                        </div>

                        <!-- Subjects Card -->
                        <div class="bg-white p-6 rounded-xl shadow-md transform transition-transform duration-300 hover:scale-105 border-t-4 border-emerald-400">
                            <h4 class="text-lg font-semibold text-emerald-700 mb-2">My Subjects</h4>
                            <div class="flex items-center justify-between">
                                <p class="text-3xl font-extrabold text-gray-900 mt-2">{{ uniqueSubjects.length }}</p>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-emerald-500 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.468 9.563 5 8 5a4 4 0 000 8h4m-4 0a4 4 0 010 8h4m-4 0v-6m-4 6H5a2 2 0 01-2-2v-4a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2h-4" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                Total assigned subjects
                            </p>
                        </div>
                    </div>

                    <!-- Timetable and Exams Sections -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Class Time Card -->
                        <div class="bg-white rounded-xl p-6 shadow-md border-t-4 border-sky-400">
                            <h4 class="text-xl font-bold text-gray-900 mb-5">Your Class Times</h4>
                            <div v-if="studentTimetable.length === 0" class="text-gray-400 italic text-lg">
                                No class times found.
                            </div>
                            <div v-else class="space-y-6">
                                <template v-for="day in daysOfWeek" :key="day.value">
                                    <div v-if="groupedStudentTimetable[day.value]?.length > 0">
                                        <h5 class="text-lg font-bold text-sky-600 mb-3">{{ day.label }}</h5>
                                        <ul class="space-y-4">
                                            <li v-for="entry in groupedStudentTimetable[day.value]" :key="entry.id" class="bg-gray-50 p-4 rounded-lg border-l-4 border-sky-500 transition-shadow duration-300 hover:shadow-xl">
                                                <p class="font-bold text-gray-900 mb-1">
                                                    <span class="text-lg">{{ formatTime(entry.start_time) }} - {{ formatTime(entry.end_time) }}</span>
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    {{ entry.class_name.class_name || 'N/A' }} ({{ entry.section?.name || 'N/A' }}) - {{ entry.subject?.name || 'N/A' }}
                                                    <span v-if="entry.room?.name"> | Room: {{ entry.room.name }}</span>
                                                </p>
                                            </li>
                                        </ul>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Exam Schedule Card -->
                        <div class="bg-white rounded-xl p-6 shadow-md border-t-4 border-red-400">
                            <h4 class="text-xl font-bold text-gray-900 mb-5">Exam Schedule</h4>
                            <div v-if="studentExams && studentExams.length > 0" class="space-y-4">
                                <div v-for="exam in studentExams" :key="exam.id" class="bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded-lg shadow-sm transition-shadow duration-300 hover:shadow-lg">
                                    <p class="font-bold text-lg">{{ exam.name }}</p>
                                    <p class="text-sm mt-1">
                                        <span v-if="exam.subject && exam.subject.name" class="font-medium">Subject: {{ exam.subject.name }} | </span>
                                        Date: {{ formatDate(exam.exam_date) }}
                                    </p>
                                    <p class="text-sm">Time: {{ formatTime(exam.start_time) }} - {{ formatTime(exam.end_time) }}</p>
                                    <p class="text-sm" v-if="exam.room?.name">Room: {{ exam.room.name }}</p>
                                </div>
                            </div>
                            <p v-else class="text-gray-400 italic text-lg">
                                No exam schedule available at this time.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/*
 * Tailwind CSS is a utility-first framework.
 * All styling is handled directly in the HTML classes.
 */

/* Animation for a subtle fade-in and slide-up effect */
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

/* Base animation class for the heading */
.animate-fade-in-up {
 animation: fadeInUp 0.6s ease-out forwards;
}

/* Blinking cursor effect for the typewriter */
@keyframes blink {
    50% { opacity: 0; }
}
.typewriter-cursor {
    animation: blink 0.7s infinite step-end;
}
</style>
