<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

const props = defineProps({
    classes: Array,
    sessions: Array,
    sections: Array,
    groups: Array,
    exams: Array,
    subjects: Array,
    students: Array, // This will be the paginated students
    selectedClassId: Number,
    selectedSessionId: Number,
    selectedSectionId: Number,
    selectedGroupId: Number,
    selectedExamId: Number,
    selectedSubjectId: Number,
    initialMessage: Object, // { text: '...', type: 'info' | 'error' }
    selectedExamTotalMarks: { // New prop for exam total marks from exams table (e.g., 30 or 40)
        type: Number,
        default: 0,
    },
});

// Form state for filters
const filterForm = useForm({
    class_id: props.selectedClassId || '',
    session_id: props.selectedSessionId || '',
    section_id: props.selectedSectionId || '',
    group_id: props.selectedGroupId || '',
    exam_id: props.selectedExamId || '',
    subject_id: props.selectedSubjectId || '',
});

// Form state for marks submission
// Initialize marksData with existing student marks if available
const marksForm = useForm({
    class_id: filterForm.class_id,
    session_id: filterForm.session_id,
    section_id: filterForm.section_id,
    group_id: filterForm.group_id,
    exam_id: filterForm.exam_id,
    subject_id: filterForm.subject_id,
    marks_data: props.students.map(student => ({
        student_id: student.id,
        class_test_marks: student.class_test_marks,
        assignment_marks: student.assignment_marks,
        exam_marks: student.exam_marks,
        // attendance_marks is calculated in backend, not input by teacher
    })),
});

// Watch for changes in filter form and update marksForm accordingly
watch(() => filterForm.class_id, (newValue) => { marksForm.class_id = newValue; });
watch(() => filterForm.session_id, (newValue) => { marksForm.session_id = newValue; });
watch(() => filterForm.section_id, (newValue) => { marksForm.section_id = newValue; });
watch(() => filterForm.group_id, (newValue) => { marksForm.group_id = newValue; });
watch(() => filterForm.exam_id, (newValue) => { marksForm.exam_id = newValue; });
watch(() => filterForm.subject_id, (newValue) => { marksForm.subject_id = newValue; });

// When students prop changes, re-initialize marksForm.marks_data
watch(() => props.students, (newStudents) => {
    marksForm.marks_data = newStudents.map(student => ({
        student_id: student.id,
        class_test_marks: student.class_test_marks,
        assignment_marks: student.assignment_marks,
        exam_marks: student.exam_marks,
    }));
}, { deep: true });


// Function to apply filters
const applyFilters = () => {
    router.get(route('teachermarks.index'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // After successful filter, if students are loaded, marksForm will re-init via watcher
        },
    });
};

// Function to submit marks
const submitMarks = () => {
    marksForm.post(route('teachermarks.store'), {
        onSuccess: () => {
            applyFilters(); // Re-fetch students to show updated marks and calculated attendance marks
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
    filterForm.exam_id &&
    filterForm.subject_id &&
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
    <Head title="Manage Marks" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Student Marks</h2>
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
                        <div v-if="marksForm.hasErrors" class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                            <p v-for="error in marksForm.errors" :key="error">{{ error }}</p>
                        </div>


                        <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Students</h3>
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
                                <label for="exam_id" class="block text-sm font-medium text-gray-700">Exam</label>
                                <select id="exam_id" v-model="filterForm.exam_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Select Exam</option>
                                    <option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.exam_name }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="subject_id" class="block text-sm font-medium text-gray-700">Subject</label>
                                <select id="subject_id" v-model="filterForm.subject_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    <option value="">Select Subject</option>
                                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button type="button" @click="applyFilters"
                                :disabled="filterForm.processing"
                                class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-md shadow-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50">
                                {{ filterForm.processing ? 'Filtering...' : 'Apply Filters' }}
                            </button>
                        </div>

                        <div v-if="filterForm.exam_id && props.selectedExamTotalMarks > 0" class="mt-6 p-3 bg-indigo-50 border border-indigo-200 text-indigo-800 rounded-md">
                            <p class="font-semibold">Marks Breakdown:</p>
                            <ul class="list-disc list-inside ml-4 mt-1 text-sm">
                                <li>**Class Test:** Max 5 Marks</li>
                                <li>**Assignment:** Max 5 Marks</li>
                                <li>**Attendance:** Max 10 Marks</li>
                                <li>**Main Exam:** Max {{ props.selectedExamTotalMarks }} Marks</li>
                            </ul>
                            <p class="text-sm mt-2 font-bold">The total marks for this exam (Class Test + Assignment + Attendance + Main Exam) must not exceed **50**.</p>
                        </div>


                        <div v-if="props.students && props.students.length > 0">
                            <h3 class="text-lg font-medium text-gray-900 mb-4 mt-8">Student Marks Entry</h3>
                            <form @submit.prevent="submitMarks">
                                <div class="overflow-x-auto relative shadow-md sm:rounded-lg">
                                    <table class="w-full text-sm text-left text-gray-500">
                                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                            <tr>
                                                <th scope="col" class="py-3 px-6">Student Name</th>
                                                <th scope="col" class="py-3 px-6">Roll</th>
                                                <th scope="col" class="py-3 px-6">Class Test (5)</th>
                                                <th scope="col" class="py-3 px-6">Assignment (5)</th>
                                                <th scope="col" class="py-3 px-6">Exam</th> <th scope="col" class="py-3 px-6">Attendance (10)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="(student, index) in props.students" :key="student.id" class="bg-white border-b hover:bg-gray-50">
                                                <td class="py-4 px-6 font-medium text-gray-900 whitespace-nowrap">
                                                    {{ student.name }}
                                                    <input type="hidden" :name="`marks_data[${index}][student_id]`" :value="student.id">
                                                </td>
                                                <td class="py-4 px-6">{{ student.roll_no }}</td>
                                                <td class="py-4 px-6">
                                                    <input type="number" step="0.01" min="0" max="5" v-model="marksForm.marks_data[index].class_test_marks"
                                                        :name="`marks_data[${index}][class_test_marks]`"
                                                        class="w-24 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                                                    <div v-if="marksForm.errors[`marks_data.${index}.class_test_marks`]" class="text-red-500 text-xs mt-1">
                                                        {{ marksForm.errors[`marks_data.${index}.class_test_marks`] }}
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <input type="number" step="0.01" min="0" max="5" v-model="marksForm.marks_data[index].assignment_marks"
                                                        :name="`marks_data[${index}][assignment_marks]`"
                                                        class="w-24 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                                                    <div v-if="marksForm.errors[`marks_data.${index}.assignment_marks`]" class="text-red-500 text-xs mt-1">
                                                        {{ marksForm.errors[`marks_data.${index}.assignment_marks`] }}
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <input type="number" min="0" :max="props.selectedExamTotalMarks" v-model="marksForm.marks_data[index].exam_marks"
                                                    :name="`marks_data[${index}][exam_marks]`"
                                                    class="w-24 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                                                    <div v-if="marksForm.errors[`marks_data.${index}.exam_marks`]" class="text-red-500 text-xs mt-1">
                                                        {{ marksForm.errors[`marks_data.${index}.exam_marks`] }}
                                                    </div>
                                                    <div v-if="marksForm.errors[`marks_data.${index}.total_marks`]" class="text-red-500 text-xs mt-1">
                                                        {{ marksForm.errors[`marks_data.${index}.total_marks`] }}
                                                    </div>
                                                </td>
                                                <td class="py-4 px-6">
                                                    <span class="font-bold text-indigo-600">{{ student.attendance_marks }}</span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-6 flex justify-end">
                                    <button type="submit" :disabled="marksForm.processing || !canSubmit"
                                        class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50">
                                        {{ marksForm.processing ? 'Saving...' : 'Save Marks' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                        <div v-else-if="filterForm.class_id && filterForm.session_id && filterForm.section_id && filterForm.group_id && filterForm.exam_id && filterForm.subject_id && !props.initialMessage"
                             class="p-4 text-sm text-gray-700 bg-gray-100 rounded-lg" role="alert">
                            No students found for the selected criteria.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>