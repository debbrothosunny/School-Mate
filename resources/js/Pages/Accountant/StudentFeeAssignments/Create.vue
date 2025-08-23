<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
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
    applies_from: '',
    applies_to: null,
    status: 0,
});

const filteredStudents = ref([]);
const isFiltering = ref(false);

watch(() => filterForm, async (newFilters) => {
    isFiltering.value = true;
    filteredStudents.value = [];
    form.student_ids = [];

    if (newFilters.class_id && newFilters.session_id && newFilters.section_id) {
        try {
            // Note the change in route name
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
    // We must manually add the CSRF token for a POST request on a web route
    form.transform((data) => ({
        ...data,
        _token: csrfToken,
    })).post(route('bulk-store-assignments'), {
        onSuccess: () => {
            form.reset();
            filterForm.reset();
            filteredStudents.value = [];
        },
        onError: (errors) => {
            console.error("Error creating bulk assignments:", errors);
        },
    });
};
</script>

<template>
    <Head title="Bulk Student Fee Assignment" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bulk Student Fee Assignment</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form @submit.prevent="submitBulkAssignment">
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <InputLabel for="class_id" value="Class" class="form-label" />
                                <select id="class_id" class="form-select" v-model="filterForm.class_id" required>
                                    <option value="" disabled>-- Select Class --</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <InputLabel for="session_id" value="Session" class="form-label" />
                                <select id="session_id" class="form-select" v-model="filterForm.session_id" required>
                                    <option value="" disabled>-- Select Session --</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <InputLabel for="section_id" value="Section" class="form-label" />
                                <select id="section_id" class="form-select" v-model="filterForm.section_id" required>
                                    <option value="" disabled>-- Select Section --</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <InputLabel for="group_id" value="Group (Optional)" class="form-label" />
                                <select id="group_id" class="form-select" v-model="filterForm.group_id">
                                    <option value="">-- No Group --</option>
                                    <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="filteredStudents.length > 0">
                            <hr class="my-4">
                            <h5 class="mb-3">Students in Selected Class ({{ filteredStudents.length }} found)</h5>
                            <ul class="list-group mb-4">
                                <li class="list-group-item d-flex justify-content-between align-items-center" v-for="student in filteredStudents" :key="student.id">
                                    <span>{{ student.name }} (Adm. No: {{ student.admission_number }})</span>
                                </li>
                            </ul>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <InputLabel for="fee_type_id" value="Fee Type" class="form-label" />
                                    <select id="fee_type_id" class="form-select" v-model="form.fee_type_id" required>
                                        <option value="" disabled>-- Select Fee Type --</option>
                                        <option v-for="feeType in feeTypes" :key="feeType.id" :value="feeType.id">
                                            {{ feeType.name }} ({{ feeType.frequency }})
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.fee_type_id" />
                                </div>

                                <div class="col-md-6">
                                    <InputLabel for="applies_from" value="Applies From Date" class="form-label" />
                                    <TextInput id="applies_from" type="date" class="form-control" v-model="form.applies_from" required />
                                    <InputError class="mt-2" :message="form.errors.applies_from" />
                                </div>

                                <div class="col-md-6">
                                    <InputLabel for="applies_to" value="Applies To Date (Optional)" class="form-label" />
                                    <TextInput id="applies_to" type="date" class="form-control" v-model="form.applies_to" />
                                    <InputError class="mt-2" :message="form.errors.applies_to" />
                                    <small class="text-gray-500">Leave empty for indefinite assignment.</small>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <Link :href="route('student-fee-assignments.index')" class="btn btn-secondary me-3">Cancel</Link>
                                <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing">
                                    Assign to All Students
                                </PrimaryButton>
                            </div>
                        </div>

                        <div v-else-if="isFiltering" class="text-center mt-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>

                        <div v-else class="text-center mt-5 text-gray-500">
                            Please select a class, session, and section to view students.
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>