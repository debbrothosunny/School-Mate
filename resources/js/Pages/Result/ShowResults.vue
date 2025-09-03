<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed, watch, onMounted } from 'vue'; // Import onMounted

// Define the props that will be passed from the ResultController@showResults method
const props = defineProps({
    sessions: Array,        // List of academic sessions
    classes: Array,         // List of class names
    sections: Array,        // List of sections
    groups: Array,          // List of groups
    exams: Array,           // List of exams (filtered by selected session)
    selected: Object,       // Object containing currently selected filter IDs (e.g., selected.session_id)
    examDetails: Object,    // Details of the selected exam (exam_name, total_marks, passing_marks etc.)
    studentResultsData: Array, // Array of student results, each containing student info and subject-wise data
    allSubjects: Array,     // All subjects for the selected class, used for dynamic table headers
    initialMessage: Object, // Message to display before filters are selected
});

// Access flash messages from Inertia's page props
const flash = computed(() => usePage().props.flash || {});

// Initialize Inertia form for filtering
const filterForm = useForm({
    session_id: props.selected.session_id || '',
    class_id: props.selected.class_id || '',
    section_id: props.selected.section_id || '',
    group_id: props.selected.group_id || '',
    exam_id: props.selected.exam_id || '',
});

// Initialize Inertia form for storing results
const storeForm = useForm({
    exam_id: props.selected.exam_id || '',
    session_id: props.selected.session_id || '',
    class_id: props.selected.class_id || '',
    section_id: props.selected.section_id || null, // Ensure nullable matches controller validation
    group_id: props.selected.group_id || null,      // Ensure nullable matches controller validation
    results: [], // This will hold the processed student results for storage
});

// Watch for changes in session_id. When session changes, clear the exam_id
// and trigger a page visit to update the 'exams' prop based on the new session.
watch(() => filterForm.session_id, (newSessionId) => {
    if (newSessionId) {
        filterForm.exam_id = ''; // Reset exam selection
        // Use Inertia.get to reload the page with the new session_id,
        // preserving other form data and scroll position.
        filterForm.get(route('results.show'), {
            preserveState: true,
            preserveScroll: true,
            replace: true, // Replace history entry to avoid back button issues with partial reloads
        });
    }
});

// When the component is mounted or props change, populate the storeForm.results
onMounted(() => {
    console.log('Component mounted. Populating storeForm results.'); // Added log
    populateStoreFormResults();
});

watch(() => props.studentResultsData, () => {
    console.log('studentResultsData changed. Re-populating storeForm results.'); // Added log
    populateStoreFormResults();
}, { deep: true }); // Watch deeply for changes within the array

function populateStoreFormResults() {
    // Only populate if all necessary filters are selected and we have student data
    if (props.selected.session_id && props.selected.class_id && props.selected.exam_id && props.studentResultsData.length > 0) {
        storeForm.exam_id = props.selected.exam_id;
        storeForm.session_id = props.selected.session_id;
        storeForm.class_id = props.selected.class_id;
        storeForm.section_id = props.selected.section_id || null;
        storeForm.group_id = props.selected.group_id || null;

        storeForm.results = props.studentResultsData.map(studentResult => {
            return {
                student_id: studentResult.student.id,
                total_marks_obtained: studentResult.overall_total_obtained,
                total_possible_marks: studentResult.overall_total_possible,
                percentage: studentResult.overall_percentage,
                final_grade_point: studentResult.overall_gpa,
                final_letter_grade: studentResult.overall_letter_grade,
                overall_status: studentResult.overall_status,
                // Subject-wise data should be passed as an array of objects
                subject_wise_data: studentResult.subjects_data.map(subject => ({
                    subject_id: subject.subject_id,
                    subject_name: subject.subject_name,
                    marks_obtained: subject.marks_obtained,
                    total_marks: subject.total_marks,
                    passing_marks: subject.passing_marks,
                    percentage: subject.percentage,
                    letter_grade: subject.letter_grade,
                    grade_point: subject.grade_point,
                    pass_status: subject.pass_status,
                })),
                // IMPORTANT: If you ever fetch existing ExamResult IDs from the backend
                // pass them here for updateOrCreate to work correctly.
                // For now, it will always create new records if not manually added to props.
                exam_result_id: studentResult.exam_result_id || null, // Assuming you might add this to studentResultsData later
            };
        });
        // --- ADDED LOG ---
        console.log('storeForm.results after population:', storeForm.results);
        // --- END ADDED LOG ---
    } else {
        storeForm.results = []; // Clear results if filters aren't met
        // --- ADDED LOG ---
        console.log('storeForm.results cleared or not populated. Current conditions:', {
            session_id: props.selected.session_id,
            class_id: props.selected.class_id,
            exam_id: props.selected.exam_id,
            studentResultsDataLength: props.studentResultsData.length
        });
        // --- END ADDED LOG ---
    }
}


// Function to handle form submission for filtering results
const submitFilter = () => {
    filterForm.get(route('results.show'), {
        preserveState: true,   // Keep form state on subsequent visits
        preserveScroll: true,  // Keep scroll position
    });
};

// Function to handle storing the results
const storeResults = () => {
    // --- IMPORTANT: ADDED DEBUGGER AND LOGS HERE ---
    debugger; // This will pause execution right here if DevTools is open
    console.log('storeResults function started.');
    // --- END IMPORTANT ADDITIONS ---

    // Basic validation before sending
    if (!storeForm.exam_id || !storeForm.session_id || !storeForm.class_id || storeForm.results.length === 0) {
        alert('Please select all filters and ensure results are displayed before storing.');
        console.warn('Frontend validation failed. Not sending request.');
        console.log('storeForm state at failure:', storeForm.data()); // <--- VERY IMPORTANT: SHOWS WHY VALIDATION FAILED
        return;
    }

    console.log('Frontend validation passed. Attempting to post with storeForm data:', storeForm.data());

    // Original storeForm.post call
    storeForm.post(route('results.store', { exam: storeForm.exam_id }), { // IMPORTANT: check this route. If 'exam' is a route parameter, it's correct. If not, it should just be route('results.store')
        onSuccess: () => {
            console.log('Store successful!');
            // After successful storage, you might want to refresh the page
            // to show any updated state or flash messages.
            // Or simply rely on the flash message system.
            // filterForm.get(route('results.show'), { preserveState: true, preserveScroll: true });
        },
        onError: (errors) => {
            console.error('Error storing results:', errors);
            // Flash message for error is already handled by the layout.
        },
        onFinish: () => {
            console.log('Store request finished (success or error)');
        },
        preserveScroll: true,
        preserveState: false, // Set to false to force a re-render and potentially clear form or show updated state
    });
};

// Helper function to dynamically apply CSS classes based on overall status
const getOverallStatusClass = (status) => {
    return status === 'Pass' ? 'bg-success-subtle text-success-emphasis' : 'bg-danger-subtle text-danger-emphasis';
};

// Function to trigger PDF download for a specific student and exam
const downloadPdf = (studentId, examId) => {
    // Open the PDF download URL in a new tab/window
    window.open(route('results.download-pdf', { student: studentId, exam: examId }), '_blank');
};
</script>

<template>
    <Head title="View Exam Results" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">View Exam Results</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <h3 class="card-title h5 mb-4">Filter Results</h3>

                    <div v-if="flash?.success" class="p-4 mb-4 text-sm rounded-lg bg-green-100 border border-green-400 text-green-700" role="alert">
                        {{ flash.success.message || flash.success }}
                    </div>
                    <div v-if="flash?.error" class="p-4 mb-4 text-sm rounded-lg bg-red-100 border border-red-400 text-red-700" role="alert">
                        {{ flash.error.message || flash.error }}
                    </div>
                    <div v-if="initialMessage" class="p-4 mb-4 text-sm rounded-lg bg-blue-100 border border-blue-400 text-blue-700" role="alert">
                        {{ initialMessage.text }}
                    </div>
                    <div v-if="filterForm.hasErrors" class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                        <p v-for="error in filterForm.errors" :key="error">{{ error }}</p>
                    </div>

                    <form @submit.prevent="submitFilter" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <InputLabel for="session_id" value="Session" class="form-label" />
                                <select id="session_id" class="form-select" v-model="filterForm.session_id" required>
                                    <option value="" disabled>Select Session</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="filterForm.errors.session_id" />
                            </div>
                            <div class="col-md-3">
                                <InputLabel for="class_id" value="Class" class="form-label" />
                                <select id="class_id" class="form-select" v-model="filterForm.class_id" required>
                                    <option value="" disabled>Select Class</option>
                                    <option v-for="classItem in classes" :key="classItem.id" :value="classItem.id">{{ classItem.class_name }}</option>
                                </select>
                                <InputError class="mt-2" :message="filterForm.errors.class_id" />
                            </div>
                            <div class="col-md-2">
                                <InputLabel for="section_id" value="Section" class="form-label" />
                                <select id="section_id" class="form-select" v-model="filterForm.section_id">
                                    <option value="">All Sections</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="filterForm.errors.section_id" />
                            </div>
                            <div class="col-md-2">
                                <InputLabel for="group_id" value="Group" class="form-label" />
                                <select id="group_id" class="form-select" v-model="filterForm.group_id">
                                    <option value="">All Groups</option>
                                    <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="filterForm.errors.group_id" />
                            </div>
                            <div class="col-md-2">
                                <InputLabel for="exam_id" value="Exam" class="form-label" />
                                <select id="exam_id" class="form-select" v-model="filterForm.exam_id" required>
                                    <option value="" disabled>Select Exam</option>
                                    <option v-for="examItem in exams" :key="examItem.id" :value="examItem.id">{{ examItem.exam_name }}</option>
                                </select>
                                <InputError class="mt-2" :message="filterForm.errors.exam_id" />
                            </div>
                            <div class="col-md-auto">
                                <PrimaryButton :class="{ 'opacity-75': filterForm.processing }" :disabled="filterForm.processing">
                                    Show Results
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>

                    <hr class="my-4">

                    <div v-if="studentResultsData.length > 0">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0">Results for {{ examDetails.exam_name }}</h4>
                            <PrimaryButton @click="storeResults" :class="{ 'opacity-75': storeForm.processing }" :disabled="storeForm.processing">
                                <i class="fas fa-save me-2"></i> Finalize & Store Results
                            </PrimaryButton>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle caption-top">
                                <thead>
                                    <tr>
                                        <th rowspan="2" class="px-3 py-3 text-uppercase text-muted small text-nowrap">Student Name</th>
                                        <th rowspan="2" class="px-3 py-3 text-uppercase text-muted small text-center">Roll No.</th>
                                        <th rowspan="2" class="px-3 py-3 text-uppercase text-muted small text-center">Admission No.</th>
                                        <th rowspan="2" class="px-3 py-3 text-uppercase text-muted small text-center">Class</th>
                                        <th rowspan="2" class="px-3 py-3 text-uppercase text-muted small text-center">Section</th>
                                        <th rowspan="2" class="px-3 py-3 text-uppercase text-muted small text-center">Session</th>

                                        <template v-for="subject in allSubjects" :key="subject.id">
                                            <th colspan="3" class="px-3 py-3 text-uppercase text-muted small text-center">{{ subject.name }}</th>
                                        </template>

                                        <th rowspan="2" class="px-3 py-3 text-uppercase text-muted small text-center">Total Marks</th>
                                        <th rowspan="2" class="px-3 py-3 text-uppercase text-muted small text-center">Overall %</th>
                                        <th rowspan="2" class="px-3 py-3 text-uppercase text-muted small text-center">Overall GPA</th>
                                        <th rowspan="2" class="px-3 py-3 text-uppercase text-muted small text-center">Overall Status</th>
                                        <th rowspan="2" class="px-3 py-3 text-uppercase text-muted small text-center">Overall Letter Grade</th> <th rowspan="2" class="px-3 py-3 text-uppercase text-muted small text-center">Actions</th>
                                    </tr>
                                    <tr>
                                        <template v-for="subject in allSubjects" :key="subject.id + '-subheaders'">
                                            <th class="px-2 py-2 text-center small text-uppercase text-muted">Marks</th>
                                            <th class="px-2 py-2 text-center small text-uppercase text-muted">Grade</th>
                                            <th class="px-2 py-2 text-center small text-uppercase text-muted">GP</th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="studentResult in studentResultsData" :key="studentResult.student.id">
                                        <td class="px-3 py-3 text-nowrap">{{ studentResult.student.name }}</td>
                                        <td class="px-3 py-3 text-center">{{ studentResult.student.roll_number }}</td>
                                        <td class="px-3 py-3 text-center">{{ studentResult.student.admission_number || 'N/A' }}</td>
                                        <td class="px-3 py-3 text-center">{{ studentResult.student.class_name?.class_name || 'N/A' }}</td>
                                        <td class="px-3 py-3 text-center">{{ studentResult.student.section?.name || 'N/A' }}</td>
                                        <td class="px-3 py-3 text-center">{{ studentResult.student.session?.name || 'N/A' }}</td>

                                        <template v-for="subjectData in studentResult.subjects_data" :key="subjectData.subject_name">
                                            <td class="px-2 py-2 text-center">
                                                {{ subjectData.marks_obtained !== null ? subjectData.marks_obtained : '-' }}
                                                <small class="text-muted">/{{ subjectData.total_marks }}</small>
                                            </td>
                                            <td class="px-2 py-2 text-center">{{ subjectData.letter_grade }}</td>
                                            <td class="px-2 py-2 text-center">{{ subjectData.grade_point.toFixed(2) }}</td>
                                        </template>

                                        <td class="px-3 py-3 text-center">
                                            {{ studentResult.overall_total_obtained }} / {{ studentResult.overall_total_possible }}
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            {{ studentResult.overall_percentage.toFixed(2) }}%
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            {{ studentResult.overall_gpa.toFixed(2) }}
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <span
                                                :class="['badge rounded-pill px-2 py-1 small', getOverallStatusClass(studentResult.overall_status)]"
                                            >
                                                {{ studentResult.overall_status }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            {{ studentResult.overall_letter_grade }}
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <button @click="downloadPdf(studentResult.student.id, examDetails.id)" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-file-pdf"></i> PDF
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div v-else-if="filterForm.session_id && filterForm.class_id && filterForm.exam_id && !initialMessage" class="text-center py-4 text-muted">
                        No students found for the selected criteria, or no marks entered yet.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Scoped styles for this component */
.table th, .table td {
    vertical-align: middle; /* Vertically align content in table cells */
}

/* Optional: Add some basic styling for the PDF button icon if Font Awesome is used */
.btn-outline-info .fas {
    margin-right: 5px; /* Space between icon and text */
}
</style>