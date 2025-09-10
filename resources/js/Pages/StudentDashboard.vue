<script setup>
import { computed, ref, watch, defineProps } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const Swal = window.Swal;

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

const formatDate = (dateString) => {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString();
    } catch {
        return dateString;
    }
};

watch(() => props.upcomingFeeNotice, (newValue) => {
    if (newValue && Swal) {
        // Format the due_date before it's used in the pop-up
        const formattedDueDate = formatDate(newValue.due_date);
        Swal.fire({
            icon: 'warning',
            title: 'Urgent: Fee Payment Due',
            html: `
                <div class="text-left leading-relaxed">
                    <p class="font-semibold text-lg">Hello ${props.userName},</p>
                    <p class="mt-2 text-gray-700">
                        This is an urgent reminder that your <strong>${newValue.invoice_type || 'fee'}</strong>
                        with a balance of <strong>BDT ${newValue.balance_due}</strong> is due on <strong>${formattedDueDate}</strong>.
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
}, { immediate: true });


const daysOfWeek = [
    { value: 'MONDAY', label: 'Monday' },
    { value: 'TUESDAY', label: 'Tuesday' },
    { value: 'WEDNESDAY', label: 'Wednesday' },
    { value: 'THURSDAY', label: 'Thursday' },
    { value: 'FRIDAY', label: 'Friday' },
    { value: 'SATURDAY', label: 'Saturday' },
    { value: 'SUNDAY', label: 'Sunday' },
];

const formatTime = (timeString) => {
    if (!timeString) return 'N/A';
    try {
        const [hours, minutes] = timeString.split(':');
        const date = new Date();
        date.setHours(parseInt(hours));
        date.setMinutes(parseInt(minutes));
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } catch {
        return timeString;
    }
};


const groupedStudentTimetable = computed(() => {
    const grouped = {};
    daysOfWeek.forEach(day => {
        grouped[day.value] = props.studentTimetable
            .filter(entry => entry.day_of_week?.toUpperCase() === day.value)
            .sort((a, b) => a.class_time_slot.start_time.localeCompare(b.class_time_slot.start_time));
    });
    return grouped;
});

const uniqueSubjects = computed(() => {
    const subjects = new Set();
    props.studentTimetable.forEach(entry => {
        if (entry.subject && entry.subject.name) {
            subjects.add(entry.subject.name);
        }
    });
    return Array.from(subjects).sort();
});

const typedMessage = ref('');
const isTyping = ref(false);
const typingSpeed = 50;
const openDay = ref(null);

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

const toggleAccordion = (dayValue) => {
    openDay.value = openDay.value === dayValue ? null : dayValue;
};

watch(() => groupedStudentTimetable.value, (newTimetable) => {
    const today = new Date().toLocaleString('en-US', { weekday: 'long' }).toUpperCase();
    if (newTimetable[today]?.length > 0) {
        openDay.value = today;
    } else if (newTimetable.MONDAY.length > 0) {
        openDay.value = 'MONDAY';
    }
}, { immediate: true });

watch(() => props.message, (newMessage) => {
    if (newMessage) typeWriterEffect(newMessage);
}, { immediate: true });
</script>

<template>
    <Head title="My Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-gray-800 dark:text-gray-200 leading-tight">Student Dashboard</h2>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl p-8">
                    <div class="mb-10 text-center">
                        <h3 class="text-5xl font-extrabold text-gray-900 dark:text-gray-100 mb-4 animate-fade-in-up">
                            Welcome, <span class="text-blue-600 dark:text-blue-400">{{ userName ?? 'Student' }}</span>
                            <span class="inline-block animate-wave origin-[70%_70%]">👋</span>
                        </h3>
                        <p class="text-lg text-gray-700 dark:text-gray-300 font-mono select-none">
                            <span class="inline-block animate-float mr-2 text-yellow-500">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-7 h-7">
                                    <path fill-rule="evenodd" d="M2.25 6a3 3 0 013-3h12a3 3 0 013 3v2.25a2.25 2.25 0 000 4.5V18a3 3 0 01-3 3H5.25a3 3 0 01-3-3V10.5a2.25 2.25 0 000-4.5H2.25zm1.5 2.25a.75.75 0 01.75-.75h14.25a.75.75 0 01.75.75v.75a.75.75 0 01-.75.75H4.5a.75.75 0 01-.75-.75V8.25zM15 12a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75h-.008a.75.75 0 01-.75-.75V12zm3.75 0a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75h-.008a.75.75 0 01-.75-.75V12zM6.75 12a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75H7.5a.75.75 0 01-.75-.75V12zm3.75 0a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75h-.008a.75.75 0 01-.75-.75V12zm1.5 6.75a.75.75 0 000 1.5h1.5a.75.75 0 000-1.5h-1.5z" clip-rule="evenodd" />
                                </svg>
                            </span>
                            {{ typedMessage }}<span :class="{'typewriter-cursor': isTyping}">|</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                        <div class="p-6 rounded-xl border-2 border-blue-500 bg-blue-50 dark:bg-gray-700 dark:border-blue-400 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 cursor-default">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-xl font-bold text-blue-700 dark:text-blue-300">Attendance</h4>
                                <svg class="w-10 h-10 text-blue-400 dark:text-blue-300 opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <span class="text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ studentAttendance.percentage.toFixed(0) }}<span class="text-2xl font-normal">%</span></span>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ studentAttendance.attendedClasses }} / {{ studentAttendance.totalClasses }} classes attended</p>
                        </div>
                        <div class="p-6 rounded-xl border-2 border-emerald-500 bg-emerald-50 dark:bg-gray-700 dark:border-emerald-400 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 cursor-default">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-xl font-bold text-emerald-700 dark:text-emerald-300">Subjects</h4>
                                <svg class="w-10 h-10 text-emerald-400 dark:text-emerald-300 opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6.253v13m0-13C10.832 5.468 9.563 5 8 5a4 4 0 000 8h4m-4 0a4 4 0 010 8h4m-4 0v-6m-4 6H5a2 2 0 01-2-2v-4a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2h-4"/></svg>
                            </div>
                            <span class="text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ uniqueSubjects.length }}</span>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Total assigned subjects</p>
                        </div>
                        <div class="p-6 rounded-xl border-2 border-purple-500 bg-purple-50 dark:bg-gray-700 dark:border-purple-400 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 cursor-default">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-xl font-bold text-purple-700 dark:text-purple-300">Exams</h4>
                                <svg class="w-10 h-10 text-purple-400 dark:text-purple-300 opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                            </div>
                            <span class="text-4xl font-extrabold text-gray-900 dark:text-gray-100">{{ studentExams.length }}</span>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Upcoming exams scheduled</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                        <section class="rounded-xl p-6 bg-gray-100 dark:bg-gray-700 shadow-lg">
                            <h4 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">Class Timetable ⏰</h4>
                            <div v-if="studentTimetable.length === 0" class="italic text-gray-400 text-center py-14">
                                No class times available.
                            </div>
                            <div v-else class="space-y-4">
                                <div v-for="(day, index) in daysOfWeek" :key="day.value" class="rounded-xl shadow-sm overflow-hidden border border-gray-200 dark:border-gray-600">
                                    <button
                                        @click="toggleAccordion(day.value)"
                                        class="flex justify-between items-center w-full px-4 py-3 text-left font-semibold text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-800 transition"
                                    >
                                        <span>{{ day.label }}</span>
                                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ groupedStudentTimetable[day.value]?.length || 0 }} classes</span>
                                        <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{'rotate-180': openDay === day.value}" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>
                                    <div :class="{'hidden': openDay !== day.value}">
                                        <div class="p-4 bg-gray-50 dark:bg-gray-700">
                                            <ul v-if="groupedStudentTimetable[day.value]?.length" class="space-y-3">
                                                <li v-for="entry in groupedStudentTimetable[day.value]" :key="entry.id" class="p-3 rounded-lg bg-white dark:bg-gray-800 shadow-sm border border-gray-200 dark:border-gray-600 transition hover:shadow-md">
                                                    <p class="font-bold text-md text-blue-600 dark:text-blue-400">{{ formatTime(entry.class_time_slot.start_time) }} - {{ formatTime(entry.class_time_slot.end_time) }}</p>
                                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                                        <span class="font-medium">{{ entry.subject?.name || 'N/A' }}</span>
                                                        <span class="text-gray-500 dark:text-gray-400"> ({{ entry.class_name.class_name || 'N/A' }}, {{ entry.section?.name || 'N/A' }})</span>
                                                        <br>
                                                        <span v-if="entry.room?.name" class="text-xs text-gray-500 dark:text-gray-400">Room: {{ entry.room.name }}</span>
                                                    </p>
                                                </li>
                                            </ul>
                                            <p v-else class="italic text-center text-gray-400 py-4">No classes on this day.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-xl p-6 bg-gray-100 dark:bg-gray-700 shadow-lg">
                            <h4 class="text-2xl font-bold mb-6 text-gray-900 dark:text-gray-100">Exam Schedule 📝</h4>
                            <div v-if="studentExams.length === 0" class="italic text-gray-400 text-center py-14">
                                No exam schedule available.
                            </div>
                            <div v-else class="space-y-4 max-h-[600px] overflow-y-auto custom-scrollbar">
                                <div v-for="exam in studentExams" :key="exam.id" class="p-4 bg-white dark:bg-gray-800 border-l-4 border-red-500 rounded-lg shadow-sm hover:shadow-md transition">
                                    <p class="font-semibold text-lg text-red-700 dark:text-red-400 mb-1">{{ exam.name }}</p>
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <span v-if="exam.subject?.name" class="font-medium">Subject: {{ exam.subject.name }}</span>
                                        <br>
                                        <span class="font-medium">Date:</span> {{ formatDate(exam.exam_date) }}
                                    </p>
                                    <p v-if="exam.start_time || exam.end_time" class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-medium">Time:</span> {{ formatTime(exam.start_time) }} - {{ formatTime(exam.end_time) }}
                                    </p>
                                    <p v-if="exam.room?.name" class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-medium">Room:</span> {{ exam.room.name }}
                                    </p>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
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
    animation: blink 0.7s infinite step-end;
    display: inline-block;
    width: 1ch;
    vertical-align: bottom;
}
@keyframes wave {
    0%, 100% {
        transform: rotate(0deg);
    }
    10% {
        transform: rotate(14deg);
    }
    20% {
        transform: rotate(-8deg);
    }
    30% {
        transform: rotate(14deg);
    }
    40% {
        transform: rotate(-4deg);
    }
    50% {
        transform: rotate(10deg);
    }
    60% {
        transform: rotate(0deg);
    }
}
.animate-wave {
    animation: wave 2s infinite;
}
@keyframes float {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-5px);
    }
}
.animate-float {
    animation: float 2s infinite ease-in-out;
}

/* Custom Scrollbar for Exam Schedule */
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #e2e8f0; /* bg-gray-200 */
    border-radius: 4px;
}
.dark .custom-scrollbar::-webkit-scrollbar-track {
    background: #4a5568; /* bg-gray-600 */
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #94a3b8; /* bg-slate-400 */
    border-radius: 4px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #64748b; /* bg-slate-500 */
}
</style>
