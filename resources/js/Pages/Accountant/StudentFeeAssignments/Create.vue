<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import axios from 'axios';

// Get the CSRF token from the page props
const csrfToken = usePage().props.csrf_token;

const props = defineProps({
    classes: Array,
    sessions: Array,
    groups: Array,
    sections: Array,
    feeTypes: Array,
});

const filterForm = useForm({
    class_id: '',
    session_id: '',
    group_id: '',
    section_id: '',
});

const form = useForm({
    student_ids: [],
    fee_type_id: '',
    status: 0,
});

const filteredStudents = ref([]);
const isFiltering = ref(false);
const flash = computed(() => usePage().props.flash || {});

watch(() => filterForm, async (newFilters) => {
    isFiltering.value = true;
    filteredStudents.value = [];
    form.student_ids = [];
    if (newFilters.class_id && newFilters.session_id && newFilters.section_id) {
        try {
            const response = await axios.get(route('get-students-by-class'), {
                params: newFilters
            });
            filteredStudents.value = response.data;
            form.student_ids = response.data.map(student => student.id);
        } catch (error) {
            console.error("Error fetching students:", error);
            filteredStudents.value = [];
        }
    }
    isFiltering.value = false;
}, { deep: true });

const submitBulkAssignment = () => {
    // CRITICAL FIX: Merge filterForm data into the submission form payload
    form.transform((data) => ({
        ...data,
        class_id: filterForm.class_id,       
        session_id: filterForm.session_id,  
        section_id: filterForm.section_id,   
        _token: csrfToken,
    })).post(route('bulk-store-assignments'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            filterForm.reset();
            filteredStudents.value = [];
        },
        onError: (errors) => {
            console.error("Error creating bulk assignments:", errors);
            // Optional: If you want to explicitly check for filter errors after submission:
            if (errors.class_id) filterForm.errors.class_id = errors.class_id;
            if (errors.session_id) filterForm.errors.session_id = errors.session_id;
            if (errors.section_id) filterForm.errors.section_id = errors.section_id;
        },
    });
};
</script>


<template>
    <Head title="Bulk Student Fee Assignment" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-lg sm:text-xl md:text-2xl text-gray-800 leading-tight">Bulk Student Fee Assignment</h2>
        </template>
        <div class="py-4 sm:py-6 lg:py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow-md rounded-lg p-4 sm:p-5 lg:p-6">
                    <!-- Flash Message -->
                    <div
                        v-if="flash.message"
                        :class="[
                            'p-3 sm:p-4 mb-4 sm:mb-5 text-sm rounded-lg flex justify-between items-center',
                            flash.type === 'success' || flash.type === 'warning' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800',
                        ]"
                        role="alert"
                    >
                        <span>{{ flash.message }}</span>
                        <button @click="flash.message = null" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submitBulkAssignment">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-4 sm:mb-6">
                            <div>
                                <InputLabel for="class_id" value="Class" class="form-label text-sm font-medium text-gray-700" />
                                <select id="class_id" class="form-select w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" v-model="filterForm.class_id" required>
                                    <option value="" disabled>-- Select Class --</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                                </select>
                                <!-- Check both form and filterForm for errors on filters -->
                                <InputError class="mt-1 text-xs text-red-600" :message="filterForm.errors.class_id || form.errors.class_id" />
                            </div>
                            <div>
                                <InputLabel for="session_id" value="Session" class="form-label text-sm font-medium text-gray-700" />
                                <select id="session_id" class="form-select w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" v-model="filterForm.session_id" required>
                                    <option value="" disabled>-- Select Session --</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                                <InputError class="mt-1 text-xs text-red-600" :message="filterForm.errors.session_id || form.errors.session_id" />
                            </div>
                            <div>
                                <InputLabel for="section_id" value="Section" class="form-label text-sm font-medium text-gray-700" />
                                <select id="section_id" class="form-select w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" v-model="filterForm.section_id" required>
                                    <option value="" disabled>-- Select Section --</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                                <InputError class="mt-1 text-xs text-red-600" :message="filterForm.errors.section_id || form.errors.section_id" />
                            </div>
                            <div>
                                <InputLabel for="group_id" value="Group" class="form-label text-sm font-medium text-gray-700" />
                                <select id="group_id" class="form-select w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" v-model="filterForm.group_id">
                                    <option value="">-- No Group --</option>
                                    <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                                </select>
                                <InputError class="mt-1 text-xs text-red-600" :message="filterForm.errors.group_id" />
                            </div>
                        </div>
                        <div v-if="filteredStudents.length > 0">
                            <hr class="my-4 sm:my-6 border-gray-200">
                            <h5 class="mb-3 text-base sm:text-lg font-semibold text-gray-900">Students in Selected Class ({{ filteredStudents.length }} found)</h5>
                            <ul class="list-group mb-4 sm:mb-6 max-h-60 overflow-y-auto border border-gray-200 rounded-md">
                                <li class="list-group-item flex justify-between items-center px-3 py-2 text-sm text-gray-700 bg-white hover:bg-gray-50 transition-colors" v-for="student in filteredStudents" :key="student.id">
                                    <span>{{ student.name }} (Adm. No: {{ student.admission_number }})</span>
                                </li>
                            </ul>
                            <div class="grid grid-cols-1 gap-3 sm:gap-4">
                                <div>
                                    <InputLabel for="fee_type_id" value="Fee Type" class="form-label text-sm font-medium text-gray-700" />
                                    <select id="fee_type_id" class="form-select w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" v-model="form.fee_type_id" required>
                                        <option value="" disabled>-- Select Fee Type --</option>
                                        <option v-for="feeType in feeTypes" :key="feeType.id" :value="feeType.id">
                                            {{ feeType.name }} ({{ feeType.frequency }})
                                        </option>
                                    </select>
                                    <InputError class="mt-1 text-xs text-red-600" :message="form.errors.fee_type_id" />
                                </div>
                            </div>
                            <div class="flex justify-end mt-4 sm:mt-6 gap-2 sm:gap-3">
                                <Link :href="route('student-fee-assignments.index')" class="px-3 py-1.5 sm:px-4 sm:py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors text-sm font-medium">Cancel</Link>
                                <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="px-3 py-1.5 sm:px-4 sm:py-2 text-sm font-medium">
                                    Assign to All Students
                                </PrimaryButton>
                            </div>
                        </div>
                        <div v-else-if="isFiltering" class="text-center mt-5 sm:mt-6">
                            <div class="spinner-border inline-block w-6 h-6 border-2 border-t-indigo-600 rounded-full animate-spin" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div v-else class="text-center mt-5 sm:mt-6 text-gray-500">
                            <p class="text-base sm:text-lg font-medium">No students found</p>
                            <p class="text-sm mt-1">Please select a class, session, and section to view students.</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<style scoped>
/* ... (your styles are unchanged) ... */
:root {
    --primary-color: #4F46E5; /* Indigo-600 */
    --secondary-color: #10B981; /* Green-600 */
    --text-color: #1F2937; /* Gray-800 */
    --light-bg: #F9FAFB; /* Gray-50 */
}

.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    background-size: 1.5em;
}

.list-group {
    scrollbar-width: thin;
    scrollbar-color: var(--primary-color) var(--light-bg);
}

.list-group::-webkit-scrollbar {
    width: 6px;
}

.list-group::-webkit-scrollbar-track {
    background: var(--light-bg);
}

.list-group::-webkit-scrollbar-thumb {
    background: var(--primary-color);
    border-radius: 3px;
}

@media (max-width: 768px) {
    .grid-cols-4 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .text-xl {
        font-size: 1.125rem;
    }

    .text-lg {
        font-size: 1rem;
    }

    .text-base {
        font-size: 0.875rem;
    }

    .px-4 {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    .py-2 {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    .list-group {
        max-height: 12rem;
    }
}

@media (max-width: 640px) {
    .grid-cols-2 {
        grid-template-columns: 1fr;
    }

    .text-sm {
        font-size: 0.75rem;
    }

    .px-3 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .py-1\.5 {
        padding-top: 0.375rem;
        padding-bottom: 0.375rem;
    }

    .list-group {
        max-height: 10rem;
    }

    .spinner-border {
        width: 1.25rem;
        height: 1.25rem;
        border-width: 0.2rem;
    }
}

@media (max-width: 480px) {
    .text-base {
        font-size: 0.75rem;
    }

    .text-sm {
        font-size: 0.7rem;
    }

    .px-2\.5 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .py-1\.5 {
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
    }

    .list-group-item {
        font-size: 0.7rem;
    }

    .spinner-border {
        width: 1rem;
        height: 1rem;
        border-width: 0.15rem;
    }
}
</style>