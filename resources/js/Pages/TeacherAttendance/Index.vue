<script setup>
import { ref, watch, computed, watchEffect } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    classes: Array,
    sessions: Array,
    sections: Array,
    groups: Array,
    students: Array,
    attendanceExists: Boolean,
    selectedClassId: Number,
    selectedSessionId: Number,
    selectedSectionId: Number,
    selectedGroupId: Number,
    selectedAttendanceDate: String,
    initialMessage: Object,
});

const filterForm = useForm({
    class_id: props.selectedClassId || '',
    session_id: props.selectedSessionId || '',
    section_id: props.selectedSectionId || '',
    group_id: props.selectedGroupId || '',
    attendance_date: props.selectedAttendanceDate || new Date().toISOString().slice(0, 10),
});

const attendanceForm = useForm({
    class_id: filterForm.class_id,
    session_id: filterForm.session_id,
    section_id: filterForm.section_id,
    group_id: filterForm.group_id,
    attendance_date: filterForm.attendance_date,
    attendance_data: props.students.map(s => ({
        student_id: s.id,
        status: s.attendance_status || 'absent',
    })),
});

watch(() => filterForm.class_id, val => attendanceForm.class_id = val);
watch(() => filterForm.session_id, val => attendanceForm.session_id = val);
watch(() => filterForm.section_id, val => attendanceForm.section_id = val);
watch(() => filterForm.group_id, val => attendanceForm.group_id = val);
watch(() => filterForm.attendance_date, val => attendanceForm.attendance_date = val);

watch(() => props.students, students => {
    attendanceForm.attendance_data = students.map(s => ({
        student_id: s.id,
        status: s.attendance_status || 'absent',
    }));
}, { deep: true });

const applyFilters = () => {
    router.get(route('teacherattendance.index'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const submitAttendance = () => {
    attendanceForm.post(route('teacherattendance.store'), {
        onSuccess: () => applyFilters(),
    });
};

const canSubmit = computed(() =>
    filterForm.class_id &&
    filterForm.session_id &&
    filterForm.section_id &&
    filterForm.group_id &&
    filterForm.attendance_date &&
    props.students.length > 0
);


</script>


<template>
    <Head title="Manage Attendance" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Student Attendance</h2>
        </template>

        <!-- Flash message -->
        <div v-if="$page.props.flash.message" 
            :class="[
                'p-4 mb-6 rounded border-l-4 font-semibold',
                $page.props.flash.message.type === 'success' ? 'bg-green-100 border-green-500 text-green-700' : '',
                $page.props.flash.message.type === 'error' ? 'bg-red-100 border-red-500 text-red-700' : '',
                $page.props.flash.message.type === 'info' ? 'bg-blue-100 border-blue-500 text-blue-700' : ''
            ]" role="alert">
        {{ $page.props.flash.message.text }}
        </div>

        <div class="py-12 bg-gray-100 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl">
                    <div class="p-6 text-gray-900 space-y-8">
                        <!-- Flash and Initial Messages -->
                        <div v-if="$page.props.flash?.success" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg" role="alert">
                            <p class="font-semibold">{{ $page.props.flash.success }}</p>
                        </div>
                        
                        <div v-if="props.initialMessage" :class="['p-4 border-l-4 rounded-lg', getMessageClass(props.initialMessage.type)]" role="alert">
                            <p class="font-semibold">{{ props.initialMessage.text }}</p>
                        </div>
                        
                        <div v-if="attendanceForm.hasErrors" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg" role="alert">
                            <ul class="list-disc list-inside">
                                <li v-for="error in attendanceForm.errors" :key="error">{{ error }}</li>
                            </ul>
                        </div>
                        
                        <!-- Filter Section -->
                        <div class="bg-gray-50 p-6 rounded-xl shadow-inner">
                            <h3 class="text-xl font-bold text-gray-800 mb-6">Filter Students for Attendance</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                                <!-- Class Dropdown -->
                                <div>
                                    <label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                                    <select id="class_id" v-model="filterForm.class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out">
                                        <option value="">Select Class</option>
                                        <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                                    </select>
                                </div>
                                <!-- Session Dropdown -->
                                <div>
                                    <label for="session_id" class="block text-sm font-medium text-gray-700 mb-1">Session</label>
                                    <select id="session_id" v-model="filterForm.session_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out">
                                        <option value="">Select Session</option>
                                        <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                    </select>
                                </div>
                                <!-- Section Dropdown -->
                                <div>
                                    <label for="section_id" class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                                    <select id="section_id" v-model="filterForm.section_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out">
                                        <option value="">Select Section</option>
                                        <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                    </select>
                                </div>
                                <!-- Group Dropdown -->
                                <div>
                                    <label for="group_id" class="block text-sm font-medium text-gray-700 mb-1">Group</label>
                                    <select id="group_id" v-model="filterForm.group_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out">
                                        <option value="">Select Group</option>
                                        <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                                    </select>
                                </div>
                                <!-- Date Picker -->
                                <div>
                                    <label for="attendance_date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                                    <input type="date" id="attendance_date" v-model="filterForm.attendance_date"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out" />
                                </div>
                            </div>

                            <!-- Apply Filters Button -->
                            <div class="mt-8 flex justify-end">
                                <button type="button" @click="applyFilters"
                                    :disabled="filterForm.processing"
                                    class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-md shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ filterForm.processing ? 'Filtering...' : 'Apply Filters' }}
                                </button>
                            </div>
                        </div>

                        <!-- Student Attendance Section -->
                        <div v-if="props.students && props.students.length > 0">
                            <h3 class="text-xl font-bold text-gray-800 mt-8 mb-6">Student Attendance Entry</h3>
                            <form @submit.prevent="submitAttendance">
                                <div class="overflow-x-auto relative shadow-md sm:rounded-xl">
                                    <table class="w-full text-sm text-left text-gray-500">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-200">
                                            <tr>
                                                <th scope="col" class="py-3 px-6">Student Name</th>
                                                <th scope="col" class="py-3 px-6">Roll</th>
                                                <th scope="col" class="py-3 px-6 text-center">Attendance Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(student, index) in props.students" :key="student.id" class="bg-white border-b hover:bg-gray-50 transition-colors duration-200">
                                                <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap">
                                                    {{ student.name }}
                                                    <input type="hidden" :name="`attendance_data[${index}][student_id]`" :value="student.id">
                                                </td>
                                                <td class="py-4 px-6">{{ student.roll_no }}</td>
                                                <td class="py-4 px-6">
                                                    <div class="flex items-center justify-center space-x-4">
                                                        <!-- Present Radio -->
                                                        <label class="inline-flex items-center cursor-pointer">
                                                            <input type="radio" :name="`attendance_data[${index}][status]`" value="present" v-model="attendanceForm.attendance_data[index].status" class="form-radio h-4 w-4 text-green-600 focus:ring-green-500">
                                                            <span class="ml-2 text-sm text-gray-700">Present</span>
                                                        </label>
                                                        <!-- Absent Radio -->
                                                        <label class="inline-flex items-center cursor-pointer">
                                                            <input type="radio" :name="`attendance_data[${index}][status]`" value="absent" v-model="attendanceForm.attendance_data[index].status" class="form-radio h-4 w-4 text-red-600 focus:ring-red-500">
                                                            <span class="ml-2 text-sm text-gray-700">Absent</span>
                                                        </label>
                                                        <!-- Late Radio -->
                                                        <label class="inline-flex items-center cursor-pointer">
                                                            <input type="radio" :name="`attendance_data[${index}][status]`" value="late" v-model="attendanceForm.attendance_data[index].status" class="form-radio h-4 w-4 text-yellow-600 focus:ring-yellow-500">
                                                            <span class="ml-2 text-sm text-gray-700">Late</span>
                                                        </label>
                                                    </div>
                                                    <div v-if="attendanceForm.errors[`attendance_data.${index}.status`]" class="text-red-500 text-xs text-center mt-1">
                                                        {{ attendanceForm.errors[`attendance_data.${index}.status`] }}
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-8 flex justify-end">
                                    <button type="submit" :disabled="attendanceForm.processing || !canSubmit"
                                        class="px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                                        {{ attendanceForm.processing ? 'Saving...' : 'Save Attendance' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- No Students Found Message -->
                        <div v-else-if="filterForm.class_id && filterForm.session_id && filterForm.section_id && filterForm.group_id && filterForm.attendance_date && !props.initialMessage"
                             class="bg-gray-100 border-l-4 border-gray-400 text-gray-700 p-4 rounded-lg" role="alert">
                            <p class="font-semibold">No students found for the selected criteria.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>



