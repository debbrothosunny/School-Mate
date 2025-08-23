<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue'; // Import onMounted for debugging

// Define the props that will be passed from the controller
const props = defineProps({
    teacherName: String, // Name of the teacher
    teacherTimetable: Array, // Array of ClassTime objects for this teacher
});

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

// Computed property to group timetable entries by day for easier rendering
const groupedTeacherTimetable = computed(() => {
    const grouped = {};
    daysOfWeek.forEach(day => {
        // Filter entries for the current day and sort them by start time
        grouped[day.value] = props.teacherTimetable
            .filter(entry => entry.day_of_week === day.value)
            .sort((a, b) => {
                // Compare start times for sorting
                const timeA = a.start_time;
                const timeB = b.start_time;
                if (timeA < timeB) return -1;
                if (timeA > timeB) return 1;
                return 0;
            });
    });
    return grouped;
});

// Computed property to extract unique class assignments for the summary section
const uniqueClassAssignments = computed(() => {
    const assignments = new Set();
    props.teacherTimetable.forEach(entry => {
        // FIX: Changed entry.className?.name to entry.className?.class_name
        const className = entry.className?.class_name || 'N/A'; 
        const sectionName = entry.section?.name || 'N/A';
        const subjectName = entry.subject?.name || 'N/A';
        const sessionName = entry.session?.name || 'N/A';
        const roomName = entry.room?.name || 'N/A';

        // Create a unique string identifier for each assignment combination
        const uniqueId = `${className}-${sectionName}-${subjectName}-${sessionName}-${roomName}`;
        
        assignments.add({
            className: className,
            sectionName: sectionName,
            subjectName: subjectName,
            sessionName: sessionName,
            roomName: roomName,
        });
    });
    return Array.from(assignments); // Convert Set back to Array
});


// --- Debugging Logs ---
onMounted(() => {
    console.log('MyClasses/Index.vue mounted.');
    console.log('Props received:', props);
    console.log('teacherTimetable:', props.teacherTimetable);
    console.log('Grouped Teacher Timetable:', groupedTeacherTimetable.value);
    console.log('Unique Class Assignments for Summary:', uniqueClassAssignments.value);


    if (!props.teacherTimetable || props.teacherTimetable.length === 0) {
        console.warn('teacherTimetable prop is empty or undefined. Check controller data and database.');
    }
});
</script>

<template>
    <Head title="My Classes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                My Classes ({{ props.teacherName }})
            </h2>
        </template>

        <div class="py-4">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- NEW: Simple Design for Class Overview -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Overview of Your Assigned Classes:</h3>
                        
                        <div v-if="uniqueClassAssignments.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="(assignment, index) in uniqueClassAssignments" :key="index"
                                class="border border-gray-200 rounded-lg p-4 bg-gray-50 shadow-sm">
                                <p class="font-semibold text-indigo-700 text-md mb-1">{{ assignment.className }} ({{ assignment.sectionName }})</p>
                                <p class="text-sm text-gray-700">Subject: <span class="font-medium">{{ assignment.subjectName }}</span></p>
                                <p class="text-sm text-gray-700">Session: <span class="font-medium">{{ assignment.sessionName }}</span></p>
                                <p class="text-sm text-gray-700">Room: <span class="font-medium">{{ assignment.roomName }}</span></p>
                            </div>
                        </div>
                        <div v-else>
                            <p class="text-gray-600">No unique class assignments found in your timetable.</p>
                        </div>
                    </div>
                </div>

                <!-- Teacher's Detailed Timetable Section (from class_times) -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mt-4 mb-4">Your Detailed Timetable:</h3>
                        <div class="border p-4 rounded-md bg-gray-50 text-gray-600">
                            <div v-if="teacherTimetable.length === 0" class="text-center py-4 text-muted">
                                No timetable entries found for you.
                            </div>
                            <div v-else class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Day</th>
                                            <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Time</th>
                                            <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Class (Section)</th>
                                            <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Subject</th>
                                            <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Room</th>
                                            <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white">
                                        <template v-for="day in daysOfWeek" :key="day.value">
                                            <!-- Display day header only if there are entries for that day -->
                                            <tr v-if="groupedTeacherTimetable[day.value]?.length > 0">
                                                <td :rowspan="groupedTeacherTimetable[day.value].length + 1" class="align-middle fw-bold bg-light text-center">{{ day.label }}</td>
                                            </tr>
                                            <!-- Loop through timetable entries for the current day -->
                                            <tr v-for="entry in groupedTeacherTimetable[day.value]" :key="entry.id">
                                                <td>{{ entry.start_time?.substring(0, 5) }} - {{ entry.end_time?.substring(0, 5) }}</td>
                                                <td>{{ entry.className?.class_name || 'N/A' }} ({{ entry.section?.name || 'N/A' }})</td>
                                                <td>{{ entry.subject?.name || 'N/A' }}</td>
                                                <td>{{ entry.room?.name || 'N/A' }}</td>
                                                <td>
                                                    <span :class="{
                                                        'badge bg-success-subtle text-success-emphasis': entry.status === 0,
                                                        'badge bg-danger-subtle text-danger-emphasis': entry.status === 1,
                                                    }" class="px-2 py-1 rounded-pill small">
                                                        {{ entry.status === 0 ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <!-- Display "No classes" message for a specific day if no entries -->
                                            <tr v-if="groupedTeacherTimetable[day.value]?.length === 0">
                                                <td class="align-middle fw-bold bg-light text-center">{{ day.label }}</td>
                                                <td colspan="5" class="text-center py-3 text-muted">No classes scheduled for {{ day.label }}.</td>
                                            </tr>
                                        </template>
                                        <!-- Display "No timetable entries found" if no entries for any day -->
                                        <tr v-if="Object.values(groupedTeacherTimetable).every(arr => arr.length === 0)">
                                            <td colspan="6" class="text-center py-4 text-muted">No timetable entries found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
