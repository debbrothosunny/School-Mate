<script setup>
import { ref, watch, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    classes: Array,
    sessions: Array,
    sections: Array,
    groups: Array,
    students: Array,
    selectedClassId: Number,
    selectedSessionId: Number,
    selectedSectionId: Number,
    selectedGroupId: Number,
    selectedStartDate: String,
    selectedEndDate: String,
    initialMessage: Object,
    flash: Object,
});

const filterForm = useForm({
    class_id: props.selectedClassId || '',
    session_id: props.selectedSessionId || '',
    section_id: props.selectedSectionId || '',
    group_id: props.selectedGroupId || '',
    start_date: props.selectedStartDate || '',
    end_date: props.selectedEndDate || '',
});

const applyFilters = () => {
    router.get(route('attendance.report'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
    });
};

const exportPDF = () => {
    const query = new URLSearchParams(filterForm.data()).toString();
    window.open(route('attendance.report.pdf') + `?${query}`, '_blank');
};

const canGenerateReport = computed(() =>
    filterForm.class_id &&
    filterForm.session_id &&
    filterForm.section_id &&
    filterForm.group_id &&
    filterForm.start_date &&
    filterForm.end_date
);

const getPercentageClass = (percentage) => {
    if (percentage >= 90) return 'bg-green-100 text-green-800';
    if (percentage >= 70) return 'bg-yellow-100 text-yellow-800';
    return 'bg-red-100 text-red-800';
};
</script>

<template>
    <Head title="Attendance Report" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Attendance Report</h2>
        </template>
        <div class="py-12 bg-gray-100 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl">
                    <div class="p-6 text-gray-900 space-y-8">
                        <!-- Flash and Initial Messages -->
                        <div v-if="props.flash?.message" :class="[
                            'p-4 mb-6 rounded border-l-4 font-semibold',
                            props.flash.message.type === 'success' ? 'bg-green-100 border-green-500 text-green-700' :
                            props.flash.message.type === 'error' ? 'bg-red-100 border-red-500 text-red-700' :
                            'bg-blue-100 border-blue-500 text-blue-700'
                        ]" role="alert">
                            {{ props.flash.message.text }}
                        </div>
                        <div v-if="props.initialMessage" :class="[
                            'p-4 border-l-4 rounded-lg',
                            props.initialMessage.type === 'success' ? 'bg-green-100 border-green-500 text-green-700' :
                            props.initialMessage.type === 'error' ? 'bg-red-100 border-red-500 text-red-700' :
                            'bg-blue-100 border-blue-500 text-blue-700'
                        ]" role="alert">
                            <p class="font-semibold">{{ props.initialMessage.text }}</p>
                        </div>
                        <!-- Filter Section -->
                        <div class="bg-gray-50 p-6 rounded-xl shadow-inner">
                            <h3 class="text-xl font-bold text-gray-800 mb-6">Filter Attendance Report</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                                <div>
                                    <label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                                    <select id="class_id" v-model="filterForm.class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out">
                                        <option value="">Select Class</option>
                                        <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="session_id" class="block text-sm font-medium text-gray-700 mb-1">Session</label>
                                    <select id="session_id" v-model="filterForm.session_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out">
                                        <option value="">Select Session</option>
                                        <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="section_id" class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                                    <select id="section_id" v-model="filterForm.section_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out">
                                        <option value="">Select Section</option>
                                        <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="group_id" class="block text-sm font-medium text-gray-700 mb-1">Group</label>
                                    <select id="group_id" v-model="filterForm.group_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out">
                                        <option value="">Select Group</option>
                                        <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                    <input type="date" id="start_date" v-model="filterForm.start_date" :max="filterForm.end_date || '2025-11-30'" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out" />
                                </div>
                                <div>
                                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                    <input type="date" id="end_date" v-model="filterForm.end_date" :min="filterForm.start_date" :max="new Date().toISOString().slice(0, 10)" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out" />
                                </div>
                            </div>
                            <div class="mt-8 flex justify-end space-x-4">
                                <button type="button" @click="applyFilters" :disabled="filterForm.processing || !canGenerateReport" class="px-6 py-2 bg-blue-600 text-white font-semibold rounded-md shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                                    {{ filterForm.processing ? 'Generating...' : 'Generate Report' }}
                                </button>
                                <button type="button" @click="exportPDF" :disabled="!canGenerateReport" class="px-6 py-2 bg-green-600 text-white font-semibold rounded-md shadow-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-150 ease-in-out disabled:opacity-50 disabled:cursor-not-allowed">
                                    Export to PDF
                                </button>
                            </div>
                        </div>
                        <div v-if="props.students && props.students.length > 0" class="overflow-x-auto relative shadow-md sm:rounded-xl">
                            <h3 class="text-xl font-bold text-gray-800 mt-8 mb-6">Attendance Summary Report</h3>
                            <table class="w-full text-sm text-left text-gray-500">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-200">
                                    <tr>
                                        <th scope="col" class="py-3 px-6">Student Name</th>
                                        <th scope="col" class="py-3 px-6">Student ID </th>
                                        <th scope="col" class="py-3 px-6">Roll No.</th>
                                        <th scope="col" class="py-3 px-6 text-center">Recorded Days</th>
                                        <th scope="col" class="py-3 px-6 text-center">Present</th>
                                        <th scope="col" class="py-3 px-6 text-center">Absent</th>
                                        <th scope="col" class="py-3 px-6 text-center">Late</th>
                                        <th scope="col" class="py-3 px-6 text-center">Attendance %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="student in props.students" :key="student.id" class="bg-white border-b hover:bg-gray-50 transition-colors duration-200">
                                        <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap">{{ student.name }}</td>
                                        <td class="py-4 px-6">{{ student.admission_number }}</td>
                                        <td class="py-4 px-6">{{ student.roll_number }}</td>
                                        <td class="py-4 px-6 text-center">{{ student.recorded_days }}</td>
                                        <td class="py-4 px-6 text-center">{{ student.present_days }}</td>
                                        <td class="py-4 px-6 text-center">{{ student.absent_days }}</td>
                                        <td class="py-4 px-6 text-center">{{ student.late_days }}</td>
                                        <td class="py-4 px-6 text-center">
                                            <span :class="getPercentageClass(student.attendance_percentage)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                                {{ student.attendance_percentage }}%
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else-if="canGenerateReport" class="bg-gray-100 border-l-4 border-gray-400 text-gray-700 p-4 rounded-lg" role="alert">
                            <p class="font-semibold">No attendance data found for the selected criteria.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.percentage-cell {
    padding: 4px 8px;
    border-radius: 4px;
    font-weight: bold;
}
</style>