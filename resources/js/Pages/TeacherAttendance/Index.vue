<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    classes: Array,
    sessions: Array,
    sections: Array,
    groups: Array,
    subjects: Array,
    students: Array, // Students for the selected criteria
    selectedClassId: Number,
    selectedSessionId: Number,
    selectedSectionId: Number,
    selectedGroupId: Number,
    selectedSubjectId: Number,
    selectedAttendanceDate: String, // Date string (YYYY-MM-DD)
    initialMessage: Object, // { text: '...', type: 'info' | 'error' }
});

// Form state for filters
const filterForm = useForm({
    class_id: props.selectedClassId || '',
    session_id: props.selectedSessionId || '',
    section_id: props.selectedSectionId || '',
    group_id: props.selectedGroupId || '',
    subject_id: props.selectedSubjectId || '',
    attendance_date: props.selectedAttendanceDate || new Date().toISOString().slice(0, 10), // Default to today
});

// Form state for attendance submission
const attendanceForm = useForm({
    class_id: filterForm.class_id,
    session_id: filterForm.session_id,
    section_id: filterForm.section_id,
    group_id: filterForm.group_id,
    subject_id: filterForm.subject_id,
    attendance_date: filterForm.attendance_date,
    attendance_data: props.students.map(student => ({
        student_id: student.id,
        status: student.attendance_status || 'absent', // Default to 'absent'
    })),
});

// Watch for changes in filter form and update attendanceForm accordingly
watch(() => filterForm.class_id, (newValue) => { attendanceForm.class_id = newValue; });
watch(() => filterForm.session_id, (newValue) => { attendanceForm.session_id = newValue; });
watch(() => filterForm.section_id, (newValue) => { attendanceForm.section_id = newValue; });
watch(() => filterForm.group_id, (newValue) => { attendanceForm.group_id = newValue; });
watch(() => filterForm.subject_id, (newValue) => { attendanceForm.subject_id = newValue; });
watch(() => filterForm.attendance_date, (newValue) => { attendanceForm.attendance_date = newValue; });


// When students prop changes, re-initialize attendanceForm.attendance_data
watch(() => props.students, (newStudents) => {
    attendanceForm.attendance_data = newStudents.map(student => ({
        student_id: student.id,
        status: student.attendance_status || 'absent',
    }));
}, { deep: true });

// Function to apply filters
const applyFilters = () => {
    router.get(route('teacherattendance.index'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // After successful filter, if students are loaded, attendanceForm will re-init via watcher
        },
    });
};

// Function to submit attendance
const submitAttendance = () => {
    attendanceForm.post(route('teacherattendance.store'), {
        onSuccess: () => {
            applyFilters(); // Re-fetch students to show updated attendance
        },
        onError: (errors) => {
            console.error("Submission errors:", errors);
            // Inertia automatically shows validation errors
        },
    });
};

// Determine if the form can be submitted (i.e., all filters selected and students loaded)
const canSubmit = computed(() =>
    filterForm.class_id &&
    filterForm.session_id &&
    filterForm.section_id &&
    filterForm.group_id &&
    filterForm.subject_id &&
    filterForm.attendance_date &&
    props.students && props.students.length > 0
);

const getMessageClass = (type) => {
    if (type === 'success') return 'bg-green-100 border border-green-400 text-green-700';
    if (type === 'error') return 'bg-red-100 border border-red-400 text-red-700';
    if (type === 'info') return 'bg-blue-100 border border-blue-400 text-blue-700';
    return '';
};
</script>

<template>
    <Head title="Manage Attendance" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Student Attendance</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">

                        <div v-if="$page.props.flash?.success" class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                            {{ $page.props.flash.success }}
                        </div>
                        <div v-if="props.initialMessage" :class="['p-4 mb-4 text-sm rounded-lg', getMessageClass(props.initialMessage.type)]" role="alert">
                            {{ props.initialMessage.text }}
                        </div>
                        <div v-if="attendanceForm.hasErrors" class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                            <p v-for="error in attendanceForm.errors" :key="error">{{ error }}</p>
                        </div>


                        <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Students for Attendance</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div>
                                <label for="class_id" class="block text-sm font-medium text-gray-700">Class</label>
                                <select id="class_id" v-model="filterForm.class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Select Class</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="session_id" class="block text-sm font-medium text-gray-700">Session</label>
                                <select id="session_id" v-model="filterForm.session_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Select Session</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="section_id" class="block text-sm font-medium text-gray-700">Section</label>
                                <select id="section_id" v-model="filterForm.section_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Select Section</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="group_id" class="block text-sm font-medium text-gray-700">Group</label>
                                <select id="group_id" v-model="filterForm.group_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Select Group</option>
                                    <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="subject_id" class="block text-sm font-medium text-gray-700">Subject</label>
                                <select id="subject_id" v-model="filterForm.subject_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Select Subject</option>
                                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="attendance_date" class="block text-sm font-medium text-gray-700">Date</label>
                                <input type="date" id="attendance_date" v-model="filterForm.attendance_date"
                                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                            </div>
                        </div>

                        <!-- Apply Filters Button -->
                        <div class="mt-4 flex justify-end">
                            <button type="button" @click="applyFilters"
                                :disabled="filterForm.processing"
                                class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-md shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50">
                                {{ filterForm.processing ? 'Filtering...' : 'Apply Filters' }}
                            </button>
                        </div>


                        <div v-if="props.students && props.students.length > 0">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 mt-8">Student Attendance Entry</h3>
                            <form @submit.prevent="submitAttendance">
                                <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                                    <table class="w-full text-sm text-left text-gray-500">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                            <tr>
                                                <th scope="col" class="py-3 px-6">Student Name</th>
                                                <th scope="col" class="py-3 px-6">Roll</th>
                                                <th scope="col" class="py-3 px-6">Attendance Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(student, index) in props.students" :key="student.id" class="bg-white border-b hover:bg-gray-50">
                                                <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap">
                                                    {{ student.name }}
                                                    <input type="hidden" :name="`attendance_data[${index}][student_id]`" :value="student.id">
                                                </td>
                                                <td class="py-4 px-6">{{ student.roll_no }}</td>
                                                <td class="py-4 px-6">
                                                    <div class="flex items-center space-x-4">
                                                        <label class="inline-flex items-center">
                                                            <input type="radio" :name="`attendance_data[${index}][status]`" value="present" v-model="attendanceForm.attendance_data[index].status" class="form-radio text-green-600">
                                                            <span class="ml-2 text-green-700">Present</span>
                                                        </label>
                                                        <label class="inline-flex items-center">
                                                            <input type="radio" :name="`attendance_data[${index}][status]`" value="absent" v-model="attendanceForm.attendance_data[index].status" class="form-radio text-red-600">
                                                            <span class="ml-2 text-red-700">Absent</span>
                                                        </label>
                                                        <label class="inline-flex items-center">
                                                            <input type="radio" :name="`attendance_data[${index}][status]`" value="late" v-model="attendanceForm.attendance_data[index].status" class="form-radio text-yellow-600">
                                                            <span class="ml-2 text-yellow-700">Late</span>
                                                        </label>
                                                    </div>
                                                    <div v-if="attendanceForm.errors[`attendance_data.${index}.status`]" class="text-red-500 text-xs mt-1">
                                                        {{ attendanceForm.errors[`attendance_data.${index}.status`] }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-6 flex justify-end">
                                    <button type="submit" :disabled="attendanceForm.processing || !canSubmit"
                                        class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50">
                                        {{ attendanceForm.processing ? 'Saving...' : 'Save Attendance' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div v-else-if="filterForm.class_id && filterForm.session_id && filterForm.section_id && filterForm.group_id && filterForm.subject_id && filterForm.attendance_date && !props.initialMessage"
                             class="p-4 text-sm text-gray-700 bg-gray-100 rounded-lg" role="alert">
                            No students found for the selected criteria.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>