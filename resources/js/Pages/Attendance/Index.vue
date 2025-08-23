<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch, onMounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

// Props passed from Laravel Controller
const props = defineProps({
    classes: Array,
    sessions: Array,
    sections: Array,
    groups: Array, // Prop for groups array
    subjects: Array, // ✨ New prop for subjects array
    selectedClassId: [Number, String],
    selectedSessionId: [Number, String],
    selectedSectionId: [Number, String],
    selectedGroupId: [Number, String], // Prop for selected group ID
    selectedSubjectId: [Number, String], // ✨ New prop for selected subject ID
    selectedDate: String,
    students: Array, // This will be the filtered students with their current status
    initialMessage: Object, // { text: String, type: 'success' | 'error' | 'info' }
    flash: Object, // Laravel flash messages (e.g., from redirect)
});

// Form state for filters - useForm makes it easy to submit via Inertia
const filterForm = useForm({
    class_id: props.selectedClassId,
    session_id: props.selectedSessionId,
    section_id: props.selectedSectionId,
    group_id: props.selectedGroupId, // Group ID in filter form
    subject_id: props.selectedSubjectId, // ✨ Subject ID in filter form
    date: props.selectedDate,
});

// Form state for attendance submission
const attendanceForm = useForm({
    class_id: filterForm.class_id,
    session_id: filterForm.session_id,
    section_id: filterForm.section_id,
    group_id: filterForm.group_id,
    subject_id: filterForm.subject_id, // ✨ Subject ID in attendance form
    date: filterForm.date,
    attendance_data: props.students ? props.students.map(student => ({
        student_id: student.id,
        status: student.status
    })) : [],
});

// Reactive state to control when the student list is shown
const showStudentList = ref(false);

// Reactive state for displaying messages (combines initial and flash messages)
const currentMessage = ref(props.initialMessage || null);

// Watch for flash messages from Laravel and display them
watch(() => props.flash, (newFlash) => {
    if (newFlash && (newFlash.success || newFlash.error)) {
        currentMessage.value = {
            text: newFlash.success || newFlash.error,
            type: newFlash.success ? 'success' : 'error'
        };
        // Clear message after 5 seconds
        setTimeout(() => {
            currentMessage.value = null;
        }, 5000);
    }
}, { immediate: true });

// Attendance status options for radio buttons
const attendanceStatuses = [
    { value: 'present', label: 'Present' },
    { value: 'absent', label: 'Absent' },
    { value: 'late', label: 'Late' },
];

// Function to fetch students based on current filterForm data
const fetchStudents = () => {
    // Now includes group_id and subject_id as REQUIRED selection criteria
    if (filterForm.class_id && filterForm.session_id && filterForm.section_id && filterForm.group_id && filterForm.subject_id && filterForm.date) {
        router.get(route('attendance.index'), filterForm.data(), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                attendanceForm.attendance_data = page.props.students.map(student => ({
                    student_id: student.id,
                    status: student.status
                }));
                attendanceForm.class_id = page.props.selectedClassId;
                attendanceForm.session_id = page.props.selectedSessionId;
                attendanceForm.section_id = page.props.selectedSectionId;
                attendanceForm.group_id = page.props.selectedGroupId;
                attendanceForm.subject_id = page.props.selectedSubjectId; // ✨ Update attendanceForm with selected subject ID
                attendanceForm.date = page.props.selectedDate;
                currentMessage.value = page.props.initialMessage || null;
                showStudentList.value = true;
            },
            onError: (errors) => {
                console.error('Error fetching students:', errors);
                currentMessage.value = { text: 'Failed to fetch students. Please try again.', type: 'error' };
                attendanceForm.attendance_data = [];
                showStudentList.value = false;
            }
        });
    } else {
        attendanceForm.attendance_data = [];
        currentMessage.value = { text: 'Please select Class, Session, Section, Group, Subject, and Date to view students.', type: 'info' }; // ✨ Updated message
        showStudentList.value = false;
    }
};

// --- Watchers for Filter Changes ---
watch([
    filterForm.class_id,
    filterForm.session_id,
    filterForm.section_id,
    filterForm.group_id,
    filterForm.subject_id, // ✨ Watch subject_id
    filterForm.date
], (newValues, oldValues) => {
    // Update attendanceForm's internal filter values
    attendanceForm.class_id = filterForm.class_id;
    attendanceForm.session_id = filterForm.session_id;
    attendanceForm.section_id = filterForm.section_id;
    attendanceForm.group_id = filterForm.group_id;
    attendanceForm.subject_id = filterForm.subject_id; // ✨ Update attendanceForm with subject_id
    attendanceForm.date = filterForm.date;

    // Hide the student list if filters change without clicking the button again
    showStudentList.value = false;
    currentMessage.value = { text: 'Filters changed. Click "Filter Students" to update the list.', type: 'info' };

}, { deep: true });

// --- On Mounted: Initial Display State (No automatic fetch) ---
onMounted(() => {
    // On initial mount, always hide the student list by default.
    showStudentList.value = false;
    // Set an initial message guiding the user.
    currentMessage.value = { text: 'Please select Class, Session, Section, Group, Subject, and Date, then click "Filter Students" to view data.', type: 'info' }; // ✨ Updated message
});


// --- Save Attendance ---
const saveAttendance = () => {
    // The 'attendanceForm' already contains class_id, session_id, section_id, group_id, subject_id, date, and attendance_data
    attendanceForm.post(route('attendance.store'), {
        onSuccess: (page) => {
            showStudentList.value = true;
            fetchStudents();
        },
        onError: (errors) => {
            let errorMessage = 'Failed to save attendance.';
            if (errors && Object.keys(errors).length > 0) {
                errorMessage += ' Please check input fields.';
            }
            currentMessage.value = { text: errorMessage, type: 'error' };
            console.error('Error saving attendance:', errors);
        },
        onFinish: () => {}
    });
};

</script>

<template>
    <AuthenticatedLayout>
        <div class="container mx-auto p-6 bg-gray-100 min-h-screen font-[Inter]">
            <div class="bg-white p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-extrabold text-gray-900 mb-8 text-center">Manage Student Attendance</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-8">
                    <div>
                        <label for="class" class="block text-sm font-medium text-gray-700 mb-1">Select Class</label>
                        <select
                            id="class"
                            v-model="filterForm.class_id"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm"
                            :disabled="filterForm.processing"
                        >
                            <option :value="null" disabled>-- Select Class --</option>
                            <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="session" class="block text-sm font-medium text-gray-700 mb-1">Select Session</label>
                        <select
                            id="session"
                            v-model="filterForm.session_id"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm"
                            :disabled="filterForm.processing"
                        >
                            <option :value="null" disabled>-- Select Session --</option>
                            <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="section" class="block text-sm font-medium text-gray-700 mb-1">Select Section</label>
                        <select
                            id="section"
                            v-model="filterForm.section_id"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm"
                            :disabled="filterForm.processing"
                        >
                            <option :value="null" disabled>-- Select Section --</option>
                            <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="group" class="block text-sm font-medium text-gray-700 mb-1">Select Group</label>
                        <select
                            id="group"
                            v-model="filterForm.group_id"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm"
                            :disabled="filterForm.processing"
                        >
                            <option :value="null" disabled>-- Select Group --</option>
                            <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-1">Select Subject</label>
                        <select
                            id="subject"
                            v-model="filterForm.subject_id"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm"
                            :disabled="filterForm.processing"
                        >
                            <option :value="null" disabled>-- Select Subject --</option>
                            <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Select Date</label>
                        <input
                            type="date"
                            id="date"
                            v-model="filterForm.date"
                            class="mt-1 block w-full pl-3 pr-3 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm"
                            :disabled="filterForm.processing"
                        />
                    </div>
                </div>

                <div class="mt-6 mb-8 text-center">
                    <button
                        @click="fetchStudents"
                        :disabled="filterForm.processing || !filterForm.class_id || !filterForm.session_id || !filterForm.section_id || !filterForm.group_id || !filterForm.subject_id || !filterForm.date"
                        :class="{ 'opacity-50 cursor-not-allowed': filterForm.processing || !filterForm.class_id || !filterForm.session_id || !filterForm.section_id || !filterForm.group_id || !filterForm.subject_id || !filterForm.date }"
                        class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                    >
                        {{ filterForm.processing ? 'Filtering...' : 'Filter Students' }}
                    </button>
                </div>

                <div v-if="currentMessage" :class="[
                    'p-3 mb-6 rounded-md text-sm font-medium',
                    currentMessage.type === 'success' ? 'bg-green-100 text-green-800' :
                    currentMessage.type === 'error' ? 'bg-red-100 text-red-800' :
                    'bg-blue-100 text-blue-800'
                ]">
                    {{ currentMessage.text }}
                </div>

                <div v-if="filterForm.processing" class="text-center py-8 text-indigo-600 font-semibold">
                    Loading students...
                </div>

                <div v-else-if="!showStudentList && (!filterForm.class_id || !filterForm.session_id || !filterForm.section_id || !filterForm.group_id || !filterForm.subject_id || !filterForm.date)" class="text-center py-8 text-gray-600">
                    Please select all required filters (Class, Session, Section, Group, Subject, and Date) and click "Filter Students" to view data.
                </div>

                <div v-else-if="showStudentList && attendanceForm.attendance_data.length === 0" class="text-center py-8 text-gray-600">
                    No students found for the selected criteria.
                </div>

                <div v-else-if="showStudentList">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Mark Attendance for {{ filterForm.date }}</h3>
                    <div class="overflow-x-auto bg-white rounded-lg shadow">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Student Name
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="student in attendanceForm.attendance_data" :key="student.student_id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ props.students.find(s => s.id === student.student_id)?.name || 'Unknown Student' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex space-x-4">
                                            <label v-for="statusOpt in attendanceStatuses" :key="statusOpt.value" class="inline-flex items-center">
                                                <input
                                                    type="radio"
                                                    :name="`status-${student.student_id}`"
                                                    :value="statusOpt.value"
                                                    v-model="student.status"
                                                    class="form-radio h-4 w-4 text-indigo-600 transition duration-150 ease-in-out"
                                                />
                                                <span class="ml-2 text-sm text-gray-700">{{ statusOpt.label }}</span>
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-8 text-center">
                        <button
                            @click="saveAttendance"
                            :disabled="attendanceForm.processing || filterForm.processing"
                            :class="{ 'opacity-50 cursor-not-allowed': attendanceForm.processing || filterForm.processing }"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                        >
                            {{ attendanceForm.processing ? 'Saving Attendance...' : 'Save Attendance' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No specific styles needed beyond TailwindCSS for this component */
</style>