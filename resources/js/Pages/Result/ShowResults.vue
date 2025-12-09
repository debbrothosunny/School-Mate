<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch, onMounted } from 'vue';

// Props and form logic unchanged

const props = defineProps({
    sessions: Array,
    classes: Array,
    sections: Array,
    groups: Array,
    exams: Array,
    selected: Object,
    examDetails: Object,
    studentResultsData: Array,
    allSubjects: Array,
    initialMessage: Object,
});

const flash = computed(() => usePage().props.flash || {});

const filterForm = useForm({
    session_id: props.selected.session_id || '',
    class_id: props.selected.class_id || '',
    section_id: props.selected.section_id || '',
    group_id: props.selected.group_id || '',
    exam_id: props.selected.exam_id || '',
});

const storeForm = useForm({
    exam_id: props.selected.exam_id || '',
    session_id: props.selected.session_id || '',
    class_id: props.selected.class_id || '',
    section_id: props.selected.section_id || null,
    group_id: props.selected.group_id || null,
    results: [],
});

watch(() => filterForm.session_id, (newSessionId) => {
    if (newSessionId) {
        filterForm.exam_id = '';
        filterForm.get(route('results.show'), { preserveState: true, preserveScroll: true, replace: true });
    }
});

onMounted(() => { populateStoreFormResults(); });
watch(() => props.studentResultsData, () => { populateStoreFormResults(); }, { deep: true });

function populateStoreFormResults() {
    if (props.selected.session_id && props.selected.class_id && props.selected.exam_id && props.studentResultsData.length > 0) {
        storeForm.exam_id = props.selected.exam_id;
        storeForm.session_id = props.selected.session_id;
        storeForm.class_id = props.selected.class_id;
        storeForm.section_id = props.selected.section_id || null;
        storeForm.group_id = props.selected.group_id || null;
        storeForm.results = props.studentResultsData.map(sr => ({
            student_id: sr.student.id,
            total_marks_obtained: sr.overall_total_obtained,
            total_possible_marks: sr.overall_total_possible,
            percentage: sr.overall_percentage,
            final_grade_point: sr.overall_gpa,
            final_letter_grade: sr.overall_letter_grade,
            overall_status: sr.overall_status,
            subject_wise_data: sr.subjects_data.map((subj, i) => ({
                subject_id: props.allSubjects[i]?.id || null,
                subject_name: subj.subject_name,
                marks_obtained: subj.marks_obtained,
                total_marks: subj.total_marks,
                passing_marks: subj.passing_marks,
                percentage: subj.percentage,
                letter_grade: subj.letter_grade,
                grade_point: subj.grade_point,
                pass_status: subj.pass_status,
            })),
            exam_result_id: sr.exam_result_id || null,
        }));
    } else {
        storeForm.results = [];
    }
}

const submitFilter = () => {
    filterForm.get(route('results.show'), { preserveState: true, preserveScroll: true });
};

const storeResults = () => {
    if (!storeForm.exam_id || !storeForm.session_id || !storeForm.class_id || storeForm.results.length === 0) {
        alert('Please select all filters and ensure results are displayed before storing.');
        return;
    }
    storeForm.post(route('results.store', { exam: storeForm.exam_id }), {
        preserveScroll: true,
        preserveState: false,
    });
};

const getOverallStatusClass = (status) => {
    return status === 'Pass' ? 'bg-green-100 text-green-800 border border-green-300 rounded-lg px-3 py-1 inline-block font-semibold' : 'bg-red-100 text-red-800 border border-red-300 rounded-lg px-3 py-1 inline-block font-semibold';
};

const downloadPdf = (studentId, examId) => {
    window.open(route('results.download-pdf', { student: studentId, exam: examId }), '_blank');
};

const downloadAllResultsPdf = () => {
    if (!filterForm.session_id || !filterForm.class_id || !filterForm.exam_id || props.studentResultsData.length === 0) {
        alert('Please select all filters and ensure results are displayed before generating the PDF.');
        return;
    }
    window.open(route('results.download-all-pdf', {
        session_id: filterForm.session_id,
        class_id: filterForm.class_id,
        section_id: filterForm.section_id || null,
        group_id: filterForm.group_id || null,
        exam_id: filterForm.exam_id,
    }), '_blank');
};
</script>

<template>
    <Head title="View Exam Results" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-4xl font-extrabold text-gray-900 tracking-tight">Exam Results Overview</h2>
        </template>
        <div class="min-h-screen bg-gradient-to-r from-indigo-50 to-white py-10 px-6 w-full max-w-full">
            <!-- Filter Section Full Width -->
            <section class="max-w-full mb-10">
                <form @submit.prevent="submitFilter" class="grid grid-cols-1 md:grid-cols-6 gap-6 items-end max-w-full">
                    <div>
                        <InputLabel for="session_id" value="Session" />
                        <select id="session_id" v-model="filterForm.session_id" required class="input-select-full">
                            <option value="" disabled>Select Session</option>
                            <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                        </select>
                        <InputError :message="filterForm.errors.session_id" />
                    </div>
                    <div>
                        <InputLabel for="class_id" value="Class" />
                        <select id="class_id" v-model="filterForm.class_id" required class="input-select-full">
                            <option value="" disabled>Select Class</option>
                            <option v-for="classItem in classes" :key="classItem.id" :value="classItem.id">{{ classItem.class_name }}</option>
                        </select>
                        <InputError :message="filterForm.errors.class_id" />
                    </div>
                    <div>
                        <InputLabel for="section_id" value="Section" />
                        <select id="section_id" v-model="filterForm.section_id" class="input-select-full">
                            <option value="">All Sections</option>
                            <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                        </select>
                        <InputError :message="filterForm.errors.section_id" />
                    </div>
                    <div>
                        <InputLabel for="group_id" value="Group" />
                        <select id="group_id" v-model="filterForm.group_id" class="input-select-full">
                            <option value="">All Groups</option>
                            <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                        </select>
                        <InputError :message="filterForm.errors.group_id" />
                    </div>
                    <div>
                        <InputLabel for="exam_id" value="Exam" />
                        <select id="exam_id" v-model="filterForm.exam_id" required class="input-select-full">
                            <option value="" disabled>Select Exam</option>
                            <option v-for="examItem in exams" :key="examItem.id" :value="examItem.id">{{ examItem.exam_name }}</option>
                        </select>
                        <InputError :message="filterForm.errors.exam_id" />
                    </div>
                    <div>
                        <PrimaryButton :disabled="filterForm.processing" class="w-full py-3 text-lg font-semibold" aria-label="Show exam results">
                            <template v-if="filterForm.processing">
                                <svg class="animate-spin h-5 w-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke-width="4"></circle>
                                  <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor"></path>
                                </svg>
                                Loading...
                            </template>
                            <template v-else>Show Results</template>
                        </PrimaryButton>
                    </div>
                </form>
            </section>

            <!-- Flash and messages -->
            <section v-if="flash.success || flash.error || initialMessage" class="max-w-full mb-6">
                <div v-if="flash.success" class="alert-success"><strong>Success:</strong> {{ flash.success.message || flash.success }}</div>
                <div v-if="flash.error" class="alert-error"><strong>Error:</strong> {{ flash.error.message || flash.error }}</div>
                <div v-if="initialMessage" class="alert-info">{{ initialMessage.text }}</div>
            </section>

            <!-- Results Section -->
            <section v-if="studentResultsData.length > 0" class="max-w-full overflow-x-auto">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-3xl font-bold text-gray-900">{{ examDetails.exam_name }} Results</h3>
                    <div class="flex gap-3 flex-wrap sm:flex-nowrap">
                        <PrimaryButton @click="storeResults" :disabled="storeForm.processing" class="flex items-center gap-2">
                            <svg v-if="storeForm.processing" class="animate-spin h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke-width="4"></circle>
                                <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor"></path>
                            </svg>
                            <span>ফলাফল চূড়ান্ত করুন ও সংরক্ষণ করুন</span>
                        </PrimaryButton>
                        <PrimaryButton @click="downloadAllResultsPdf" :disabled="filterForm.processing" class="bg-green-600 hover:bg-green-700 flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                            </svg>
                            <span>Print All</span>
                        </PrimaryButton>
                    </div>
                </div>

                <!-- Desktop Table Full Width and Scrollable -->
                <table class="min-w-full border-collapse border border-gray-300 text-left text-sm font-medium">
                    <thead class="bg-gray-100 sticky top-0 z-20 border-b border-gray-300">
                        <tr>
                            <th class="border border-gray-300 p-2 text-center">S/N</th>
                            <th class="border border-gray-300 p-2">Student Name</th>
                            <th class="border border-gray-300 p-2 text-center">Roll / ID</th>
                            <th class="border border-gray-300 p-2 text-center">Class / Section / Session</th>
                            <template v-for="subject in allSubjects" :key="subject.id">
                                <th colspan="3" class="border border-gray-300 p-2 text-center text-xs font-semibold tracking-tight">{{ subject.name }}</th>
                            </template>
                            <th class="border border-gray-300 p-2 text-center">Total Marks</th>
                            <th class="border border-gray-300 p-2 text-center">Overall %</th>
                            <th class="border border-gray-300 p-2 text-center">Overall GPA</th>
                            <th class="border border-gray-300 p-2 text-center">Status</th>
                            <th class="border border-gray-300 p-2 text-center">Letter Grade</th>
                        </tr>
                        <tr>
                            <template v-for="subject in allSubjects" :key="subject.id + '-subhead'">
                                <th class="border border-gray-300 p-1 text-center text-xs font-semibold">Marks</th>
                                <th class="border border-gray-300 p-1 text-center text-xs font-semibold">Grade</th>
                                <th class="border border-gray-300 p-1 text-center text-xs font-semibold">GP</th>
                            </template>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(studentResult, idx) in studentResultsData" :key="studentResult.student.id" class="hover:bg-indigo-50">
                            <td class="border border-gray-300 p-2 text-center">{{ idx + 1 }}</td>
                            <td class="border border-gray-300 p-2 truncate max-w-xs">{{ studentResult.student.name }}</td>
                            <td class="border border-gray-300 p-2 text-center">
                                {{ studentResult.student.roll_number }} / {{ studentResult.student.admission_number || 'N/A' }}
                            </td>
                            <td class="border border-gray-300 p-2 text-center">
                                {{ studentResult.student.class_name?.class_name || 'N/A' }} / {{ studentResult.student.section?.name || 'N/A' }} / {{ studentResult.student.session?.name || 'N/A' }}
                            </td>
                            <template v-for="subjectData in studentResult.subjects_data" :key="subjectData.subject_name">
                                <td class="border border-gray-300 p-1 text-center">{{ subjectData.marks_obtained !== null ? subjectData.marks_obtained : '-' }}/{{ subjectData.total_marks }}</td>
                                <td class="border border-gray-300 p-1 text-center">{{ subjectData.letter_grade }}</td>
                                <td class="border border-gray-300 p-1 text-center">{{ subjectData.grade_point.toFixed(2) }}</td>
                            </template>
                            <td class="border border-gray-300 p-2 text-center">{{ studentResult.overall_total_obtained }}/{{ studentResult.overall_total_possible }}</td>
                            <td class="border border-gray-300 p-2 text-center">{{ studentResult.overall_percentage.toFixed(2) }}%</td>
                            <td class="border border-gray-300 p-2 text-center">{{ studentResult.overall_gpa.toFixed(2) }}</td>
                            <td class="border border-gray-300 p-2 text-center">
                                <span :class="getOverallStatusClass(studentResult.overall_status)">{{ studentResult.overall_status }}</span>
                            </td>
                            <td class="border border-gray-300 p-2 text-center">{{ studentResult.overall_letter_grade }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <!-- No results message -->
            <section v-else-if="filterForm.session_id && filterForm.class_id && filterForm.exam_id && !initialMessage" class="max-w-full mt-20 text-center">
                <p class="text-lg font-semibold text-gray-700">নির্বাচিত শর্তাবলী অনুযায়ী কোনো শিক্ষার্থীর ফলাফল পাওয়া যায়নি</p>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.input-select-full {
    width: 100%;
    padding: 0.5rem 1rem;
    border: 2px solid #cbd5e1;
    border-radius: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: #334155;
    background-color: #f9fafb;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
.input-select-full:focus {
    border-color: #6366f1;
    box-shadow: 0 0 5px #6366f1aa;
    outline: none;
}
.alert-success {
    background-color: #dcfce7;
    border: 1px solid #4ade80;
    padding: 1rem;
    border-radius: 0.5rem;
    color: #166534;
    font-weight: 600;
}
.alert-error {
    background-color: #fee2e2;
    border: 1px solid #f87171;
    padding: 1rem;
    border-radius: 0.5rem;
    color: #b91c1c;
    font-weight: 600;
}
.alert-info {
    background-color: #dbEafe;
    border: 1px solid #3b82f6;
    padding: 1rem;
    border-radius: 0.5rem;
    color: #1e40af;
    font-weight: 600;
}
table {
    border-collapse: collapse;
    min-width: 100%;
}
th, td {
    border-width: 1px;
    border-style: solid;
    border-color: #cbd5e1;
}
th {
    background-color: #f1f5f9;
    font-weight: 700;
    color: #475569;
    padding: 0.75rem;
}
td {
    padding: 0.75rem;
    font-weight: 600;
    color: #334155;
}
.hover\:bg-indigo-50:hover {
    background-color: #e0e7ff;
}
.border {
    border-color: #cbd5e1;
}
.badge {
    @apply px-3 py-1 rounded text-white font-semibold;
}
</style>
