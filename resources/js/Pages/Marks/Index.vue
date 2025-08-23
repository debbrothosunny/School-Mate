<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, watch, onMounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    classes: Array,
    sessions: Array,
    sections: Array,
    groups: Array,
    exams: Array, // New prop for exams
    subjects: Array, // New prop for subjects
    selectedClassId: [Number, String],
    selectedSessionId: [Number, String],
    selectedSectionId: [Number, String],
    selectedGroupId: [Number, String],
    selectedExamId: [Number, String], // New prop for selected exam
    selectedSubjectId: [Number, String], // New prop for selected subject
    students: Array, // Students with their marks data (now includes exam_marks)
    initialMessage: Object,
    flash: Object,
});

const filterForm = useForm({
    class_id: props.selectedClassId,
    session_id: props.selectedSessionId,
    section_id: props.selectedSectionId,
    group_id: props.selectedGroupId,
    exam_id: props.selectedExamId, // Add to filter form
    subject_id: props.selectedSubjectId, // Add to filter form
});

const marksForm = useForm({
    class_id: filterForm.class_id,
    session_id: filterForm.session_id,
    section_id: filterForm.section_id,
    group_id: filterForm.group_id,
    exam_id: filterForm.exam_id,
    subject_id: filterForm.subject_id,
    marks_data: props.students ? props.students.map(student => ({
        student_id: student.id,
        class_test_marks: student.class_test_marks,
        assignment_marks: student.assignment_marks,
        exam_marks: student.exam_marks, // ✨ Initialize with existing exam_marks
        // Attendance marks will be displayed but not sent back from frontend for saving
    })) : [],
});

const showStudentList = ref(false);
const currentMessage = ref(props.initialMessage || null);

watch(() => props.flash, (newFlash) => {
    if (newFlash && (newFlash.success || newFlash.error)) {
        currentMessage.value = {
            text: newFlash.success || newFlash.error,
            type: newFlash.success ? 'success' : 'error'
        };
        setTimeout(() => {
            currentMessage.value = null;
        }, 5000);
    }
}, { immediate: true });

const fetchStudentsForMarks = () => {
    if (filterForm.class_id && filterForm.session_id && filterForm.section_id && filterForm.group_id && filterForm.exam_id && filterForm.subject_id) {
        router.get(route('marks.index'), filterForm.data(), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: (page) => {
                marksForm.marks_data = page.props.students.map(student => ({
                    student_id: student.id,
                    class_test_marks: student.class_test_marks,
                    assignment_marks: student.assignment_marks,
                    exam_marks: student.exam_marks, // ✨ Map exam_marks from fetched data
                    // Attendance marks are for display only, not part of form submission data
                }));
                marksForm.class_id = page.props.selectedClassId;
                marksForm.session_id = page.props.selectedSessionId;
                marksForm.section_id = page.props.selectedSectionId;
                marksForm.group_id = page.props.selectedGroupId;
                marksForm.exam_id = page.props.selectedExamId;
                marksForm.subject_id = page.props.selectedSubjectId;
                currentMessage.value = page.props.initialMessage || null;
                showStudentList.value = true;
            },
            onError: (errors) => {
                console.error('Error fetching students for marks:', errors);
                currentMessage.value = { text: 'Failed to fetch students. Please try again.', type: 'error' };
                marksForm.marks_data = [];
                showStudentList.value = false;
            }
        });
    } else {
        marksForm.marks_data = [];
        currentMessage.value = { text: 'Please select Class, Session, Section, Group, Exam, and Subject to view students for marks entry.', type: 'info' };
        showStudentList.value = false;
    }
};

watch([
    filterForm.class_id,
    filterForm.session_id,
    filterForm.section_id,
    filterForm.group_id,
    filterForm.exam_id, // Watch exam_id
    filterForm.subject_id // Watch subject_id
], () => {
    marksForm.class_id = filterForm.class_id;
    marksForm.session_id = filterForm.session_id;
    marksForm.section_id = filterForm.section_id;
    marksForm.group_id = filterForm.group_id;
    marksForm.exam_id = filterForm.exam_id;
    marksForm.subject_id = filterForm.subject_id;

    showStudentList.value = false;
    currentMessage.value = { text: 'Filters changed. Click "Filter Students" to update the list.', type: 'info' };
}, { deep: true });

onMounted(() => {
    showStudentList.value = false;
    currentMessage.value = { text: 'Please select Class, Session, Section, Group, Exam, and Subject, then click "Filter Students" to view data.', type: 'info' };
});

const saveMarks = () => {
    marksForm.post(route('marks.store'), {
        onSuccess: (page) => {
            showStudentList.value = true;
            // Re-fetch students to update attendance marks and any other changes
            fetchStudentsForMarks();
        },
        onError: (errors) => {
            let errorMessage = 'Failed to save marks.';
            if (errors && Object.keys(errors).length > 0) {
                errorMessage += ' Please check input fields.';
            }
            currentMessage.value = { text: errorMessage, type: 'error' };
            console.error('Error saving marks:', errors);
        },
        onFinish: () => {}
    });
};
</script>

<template>
    <AuthenticatedLayout>
        <div class="container mx-auto p-6 bg-gray-100 min-h-screen font-[Inter]">
            <div class="bg-white p-8 rounded-lg shadow-lg">
                <h2 class="text-3xl font-extrabold text-gray-900 mb-8 text-center">Manage Student Marks</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
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
                        <label for="exam" class="block text-sm font-medium text-gray-700 mb-1">Select Exam</label>
                        <select
                            id="exam"
                            v-model="filterForm.exam_id"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md shadow-sm"
                            :disabled="filterForm.processing"
                        >
                            <option :value="null" disabled>-- Select Exam --</option>
                            <option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.exam_name }}</option>
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
                </div>

                <div class="mt-6 mb-8 text-center">
                    <button
                        @click="fetchStudentsForMarks"
                        :disabled="filterForm.processing || !filterForm.class_id || !filterForm.session_id || !filterForm.section_id || !filterForm.group_id || !filterForm.exam_id || !filterForm.subject_id"
                        :class="{ 'opacity-50 cursor-not-allowed': filterForm.processing || !filterForm.class_id || !filterForm.session_id || !filterForm.section_id || !filterForm.group_id || !filterForm.exam_id || !filterForm.subject_id }"
                        class="inline-flex items-center px-8 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                    >
                        {{ filterForm.processing ? 'Filtering...' : 'Filter Students for Marks' }}
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

                <div v-else-if="!showStudentList && (!filterForm.class_id || !filterForm.session_id || !filterForm.section_id || !filterForm.group_id || !filterForm.exam_id || !filterForm.subject_id)" class="text-center py-8 text-gray-600">
                    Please select all required filters (Class, Session, Section, Group, Exam, and Subject) and click "Filter Students for Marks" to view data.
                </div>

                <div v-else-if="showStudentList && marksForm.marks_data.length === 0" class="text-center py-8 text-gray-600">
                    No students found for the selected criteria.
                </div>

                <div v-else-if="showStudentList">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-6 text-center">Enter Marks</h3>
                    <div class="overflow-x-auto bg-white rounded-lg shadow">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Student Name
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Class Test Marks
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Assignment Marks
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Exam Marks </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Attendance Marks
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="(student, index) in marksForm.marks_data" :key="student.student_id">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ props.students.find(s => s.id === student.student_id)?.name || 'Unknown Student' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input
                                            type="number"
                                            v-model.number="student.class_test_marks"
                                            class="mt-1 block w-24 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            min="0"
                                            max="100"
                                        />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input
                                            type="number"
                                            v-model.number="student.assignment_marks"
                                            class="mt-1 block w-24 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            min="0"
                                            max="100"
                                        />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input
                                            type="number"
                                            v-model.number="student.exam_marks" class="mt-1 block w-24 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            min="0"
                                            max="100"
                                        />
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-medium">
                                        {{ props.students.find(s => s.id === student.student_id)?.attendance_marks || 0 }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-8 text-center">
                        <button
                            @click="saveMarks"
                            :disabled="marksForm.processing || filterForm.processing"
                            :class="{ 'opacity-50 cursor-not-allowed': marksForm.processing || filterForm.processing }"
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200"
                        >
                            {{ marksForm.processing ? 'Saving Marks...' : 'Save Marks' }}
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