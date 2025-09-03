<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { ref, computed, watch, watchEffect } from 'vue';

// Define the component's props, just like the original code.
const props = defineProps({
    examSchedules: Object, // Paginated data of exam schedules
    exams: Array,
    classes: Array,
    sections: Array,
    sessions: Array,
    teachers: Array,
    subjects: Array,
    rooms: Array,
    selectedFilters: Object, // Current filters applied to the index
    flash: Object, // Laravel flash messages
});

// Use the useForm hook from Inertia.js to manage filter state.
const filterForm = useForm({
    exam_id: props.selectedFilters.exam_id || '',
    class_id: props.selectedFilters.class_id || '',
    section_id: props.selectedFilters.section_id || '',
    session_id: props.selectedFilters.session_id || '',
    teacher_id: props.selectedFilters.teacher_id || '',
    subject_id: props.selectedFilters.subject_id || '',
    room_id: props.selectedFilters.room_id || '',
    exam_date: props.selectedFilters.exam_date || '',
    status: props.selectedFilters.status || '',
});

// Reactive state for the delete confirmation modal.
const showDeleteModal = ref(false);
const scheduleToDelete = ref(null);

// Form for the delete action.
const deleteForm = useForm({});

// --- Utility Functions for Display ---

/**
 * Returns a human-readable string for the exam status.
 * @param {number} status - The status code (0: Active, 1: Canceled, 2: Rescheduled).
 * @returns {string} The status text.
 */
const getStatusText = (status) => {
    switch (status) {
        case 0: return 'Active';
        case 1: return 'Canceled';
        case 2: return 'Rescheduled';
        default: return 'Unknown';
    }
};

/**
 * Formats a date string into DD-MM-YYYY format.
 * @param {string} dateString - The date string to format.
 * @returns {string} The formatted date or 'N/A' if invalid.
 */
const formatExamDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    } catch (e) {
        console.error("Error formatting date:", e);
        return 'Invalid Date';
    }
};

/**
 * Formats a time string into a 12-hour format with AM/PM.
 * @param {string} timeString - The time string to format (e.g., "10:00:00", "13:30").
 * @returns {string} The formatted time (e.g., "10:00 AM", "01:30 PM").
 */
const formatTime = (timeString) => {
    if (!timeString) return 'N/A';
    try {
        const date = new Date(`2000-01-01T${timeString}`);
        let hours = date.getHours();
        const minutes = date.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const formattedMinutes = minutes < 10 ? '0' + minutes : minutes;
        return `${hours}:${formattedMinutes} ${ampm}`;
    } catch (e) {
        console.error("Error formatting time:", e);
        return 'Invalid Time';
    }
};

/**
 * Formats a time range (start and end) into a single string.
 * @param {string} start - The start time string.
 * @param {string} end - The end time string.
 * @returns {string} The formatted time range (e.g., "10:00 AM - 12:00 PM").
 */
const formatTimeRange = (start, end) => {
    const formattedStart = formatTime(start);
    const formattedEnd = formatTime(end);
    return `${formattedStart} - ${formattedEnd}`;
};

// --- Filter Logic ---

// This watch effect will automatically submit the form whenever a filter value changes.
watch([
    () => filterForm.exam_id,
    () => filterForm.class_id,
    () => filterForm.section_id,
    () => filterForm.session_id,
    () => filterForm.teacher_id,
    () => filterForm.subject_id,
    () => filterForm.room_id,
    () => filterForm.exam_date,
    () => filterForm.status,
], () => {
    applyFilters();
});

const applyFilters = () => {
    filterForm.get(route('exam-schedules.index'), {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.exam_id = '';
    filterForm.class_id = '';
    filterForm.section_id = '';
    filterForm.session_id = '';
    filterForm.teacher_id = '';
    filterForm.subject_id = '';
    filterForm.room_id = '';
    filterForm.exam_date = '';
    filterForm.status = '';
    applyFilters();
};

// --- Delete Logic ---

const confirmDelete = (schedule) => {
    scheduleToDelete.value = schedule;
    showDeleteModal.value = true;
};

const deleteSchedule = () => {
    if (scheduleToDelete.value) {
        deleteForm.delete(route('exam-schedules.destroy', scheduleToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
            },
            onError: (errors) => {
                console.error("Error deleting schedule:", errors);
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    scheduleToDelete.value = null;
};

// Watch for flash messages and display SweetAlert (if available).
watchEffect(() => {
    if (props.flash && props.flash.message) {
        // Check if SweetAlert2 (Swal) is loaded.
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: props.flash.type === 'success' ? 'success' : 'error',
                title: props.flash.type === 'success' ? 'Success!' : 'Error!',
                text: props.flash.message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        } else {
            console.warn('SweetAlert2 (Swal) is not defined. Flash message not displayed as toast.');
            alert(`${props.flash.type.toUpperCase()}: ${props.flash.message}`); // Fallback
        }
    }
});
</script>

<template>
    <Head title="Exam Schedules" />
    <AuthenticatedLayout>
        <div class="bg-gray-100 min-h-screen py-6 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <!-- Header and Add Button -->
                <div class="flex justify-between items-center mb-8 border-b-2 pb-4 border-gray-200">
                    <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight">Exam Schedules</h3>
                    <Link :href="route('exam-schedules.create')">
                        <PrimaryButton class="shadow-lg hover:shadow-xl transition-shadow duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Add New Schedule
                        </PrimaryButton>
                    </Link>
                </div>

                <!-- Filter Section - Card Design -->
                <div class="mb-8 p-6 bg-white rounded-2xl shadow-lg">
                    <h4 class="text-xl font-bold text-gray-800 mb-6">Filter Schedules</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <div class="space-y-1">
                            <InputLabel for="filter_exam" value="Exam" />
                            <select id="filter_exam" v-model="filterForm.exam_id" class="block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Exams</option>
                                <option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.exam_name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="filter_class" value="Class" />
                            <select id="filter_class" v-model="filterForm.class_id" class="block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Classes</option>
                                <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="filter_section" value="Section" />
                            <select id="filter_section" v-model="filterForm.section_id" class="block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Sections</option>
                                <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="filter_session" value="Session" />
                            <select id="filter_session" v-model="filterForm.session_id" class="block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Sessions</option>
                                <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="filter_teacher" value="Teacher" />
                            <select id="filter_teacher" v-model="filterForm.teacher_id" class="block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Teachers</option>
                                <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="filter_subject" value="Subject" />
                            <select id="filter_subject" v-model="filterForm.subject_id" class="block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Subjects</option>
                                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="filter_room" value="Room" />
                            <select id="filter_room" v-model="filterForm.room_id" class="block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Rooms</option>
                                <option v-for="room in rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="filter_date" value="Exam Date" />
                            <TextInput id="filter_date" type="date" v-model="filterForm.exam_date" class="block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>
                        <div class="space-y-1">
                            <InputLabel for="filter_status" value="Status" />
                            <select id="filter_status" v-model="filterForm.status" class="block w-full rounded-xl border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">All Statuses</option>
                                <option value="0">Active</option>
                                <option value="1">Canceled</option>
                                <option value="2">Rescheduled</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end mt-6 space-x-4">
                        <PrimaryButton @click="applyFilters" :disabled="filterForm.processing" class="bg-blue-600 hover:bg-blue-700 focus:ring-blue-500">Apply Filters</PrimaryButton>
                        <button type="button" @click="resetFilters" class="inline-flex items-center px-6 py-3 bg-gray-200 border border-gray-300 rounded-xl font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">Reset Filters</button>
                    </div>
                </div>

                <!-- Exam Schedules Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-if="examSchedules.data.length === 0" class="col-span-full text-center py-10 text-lg text-gray-500">No exam schedules found.</div>
                    <div v-for="schedule in examSchedules.data" :key="schedule.id" class="bg-white rounded-2xl shadow-lg p-6 flex flex-col justify-between hover:shadow-xl transition-shadow duration-300">
                        <div class="flex-grow">
                            <div class="flex justify-between items-center mb-4">
                                <h4 class="text-xl font-bold text-gray-900">{{ schedule.subject?.name || 'N/A' }}</h4>
                                <span :class="{
                                    'bg-green-100 text-green-800': schedule.status === 0,
                                    'bg-red-100 text-red-800': schedule.status === 1,
                                    'bg-blue-100 text-blue-800': schedule.status === 2
                                }" class="px-3 py-1 inline-flex text-sm font-semibold rounded-full">
                                    {{ getStatusText(schedule.status) }}
                                </span>
                            </div>
                            <div class="space-y-2 text-gray-700 mb-4">
                                <p><span class="font-medium text-gray-900">Exam:</span> {{ schedule.exam?.exam_name || 'N/A' }}</p>
                                <p><span class="font-medium text-gray-900">Class:</span> {{ schedule.class_name?.class_name || 'N/A' }} ({{ schedule.section?.name || 'N/A' }})</p>
                                <p><span class="font-medium text-gray-900">Session:</span> {{ schedule.session?.name || 'N/A' }}</p>
                                <p><span class="font-medium text-gray-900">Teacher:</span> {{ schedule.teacher?.name || 'N/A' }}</p>
                                <p><span class="font-medium text-gray-900">Room:</span> {{ schedule.room?.name || 'N/A' }}</p>
                                <p><span class="font-medium text-gray-900">Date:</span> {{ formatExamDate(schedule.exam_date) }} ({{ schedule.day_of_week }})</p>
                                <p><span class="font-medium text-gray-900">Time:</span> {{ formatTimeRange(schedule.start_time, schedule.end_time) }}</p>
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 mt-4 pt-4 border-t border-gray-100">
                            <Link :href="route('exam-schedules.edit', schedule.id)" class="text-blue-600 hover:text-blue-800 font-medium transition duration-150">Edit</Link>
                            <Link :href="route('exam-seat-plan.show', schedule.id)" class="text-indigo-600 hover:text-indigo-800 font-medium transition duration-150">Seat Plan</Link>
                            <DangerButton @click="confirmDelete(schedule)" class="py-1 px-3 text-sm">Delete</DangerButton>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="mt-8 flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                    <span class="text-sm text-gray-600" v-if="examSchedules.total > 0">
                        Showing <span class="font-semibold">{{ examSchedules.from }}</span> to <span class="font-semibold">{{ examSchedules.to }}</span> of <span class="font-semibold">{{ examSchedules.total }}</span> results
                    </span>
                    
                    <div class="flex flex-wrap items-center space-x-2">
                        <Link
                            v-for="link in examSchedules.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-4 py-2 text-sm leading-5 font-medium rounded-lg',
                                link.active ? 'bg-blue-600 text-white shadow' : 'text-gray-700 bg-gray-100 hover:bg-gray-200 transition duration-150 ease-in-out',
                                !link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 flex items-center justify-center z-50 transition-opacity duration-300">
            <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-sm transform transition-transform duration-300 scale-100">
                <h3 class="text-xl font-bold mb-4 text-gray-900">Confirm Deletion</h3>
                <p class="mb-6 text-gray-700">
                    Are you sure you want to permanently delete the exam schedule for <span class="font-bold text-red-600">{{ scheduleToDelete?.subject?.name || 'N/A' }}</span>
                    on <span class="font-bold">{{ formatExamDate(scheduleToDelete?.exam_date || '') }}</span>
                    at <span class="font-bold">{{ scheduleToDelete?.room?.name || 'N/A' }}</span>?
                    This action cannot be undone.
                </p>
                <div class="flex justify-end space-x-4">
                    <button type="button" @click="closeDeleteModal" class="px-5 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition duration-150 ease-in-out">Cancel</button>
                    <DangerButton @click="deleteSchedule" :disabled="deleteForm.processing">
                        {{ deleteForm.processing ? 'Deleting...' : 'Delete' }}
                    </DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* All styles are handled by Tailwind CSS */
</style>
