<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed} from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

// SAFELY get flash (prevents undefined error)
const page = usePage();
const flash = computed(() => page.props.flash || {});

// Props
const props = defineProps({
    classes: Array,
    sessions: Array,
    groups: Array,
    sections: Array,
    feeTypes: Array,
});

// Forms
const filterForm = useForm({
    class_id: '',
    session_id: '',
    group_id: '',
    section_id: '',
});

const form = useForm({
    student_ids: [],
    fee_type_id: '',
    status: 1,
});

// State
const students = ref([]);
const loading = ref(false);

// Load students when filters change
watch(filterForm, async (newVal) => {
    const { class_id, session_id, section_id, group_id } = newVal;

    students.value = [];
    form.student_ids = [];

    if (!class_id || !session_id || !section_id) return;

    loading.value = true;

    try {
        const response = await axios.get(route('admin.students.active'), {
            params: { class_id, session_id, section_id, group_id: group_id || null },
        });

        // Always ensure we have an array
        students.value = response.data?.students || [];

        if (students.value.length > 0) {
            form.student_ids = students.value.map(s => s.id);
        }
    } catch (error) {
        console.error('Load failed:', error);
        students.value = [];
        Swal.fire('Error', 'Failed to load students', 'error');
    } finally {
        loading.value = false;
    }
}, { deep: true });

// Select All / None
const selectAll = () => form.student_ids = students.value.map(s => s.id);
const deselectAll = () => form.student_ids = [];

// Submit
const submitBulkAssignment = () => {
    if (!form.student_ids.length) {
        return Swal.fire('Warning', 'Please select at least one student', 'warning');
    }
    if (!form.fee_type_id) {
        return Swal.fire('Warning', 'Please select a fee type', 'warning');
    }

    form.transform(data => ({
        ...data,
        class_id: filterForm.class_id,
        session_id: filterForm.session_id,
        section_id: filterForm.section_id,
        group_id: filterForm.group_id || null,
    })).post(route('bulk-store-assignments'), {
        onSuccess: () => {
            Swal.fire('Success!', `Assigned to ${form.student_ids.length} students!`, 'success');
            form.reset();
            filterForm.reset();
            students.value = [];
        },
        onError: () => {
            Swal.fire('Failed', 'Some students already have this fee', 'error');
        }
    });
};
</script>


<template>
    <Head title="Bulk Student Fee Assignment" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Bulk Student Fee Assignment
            </h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-lg shadow p-6">

                    <!-- Flash Message (Now Safe!) -->
                    <div v-if="flash?.message" class="mb-5 p-4 rounded-lg flex justify-between items-center"
                         :class="flash.type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                        <span>{{ flash.message }}</span>
                        <button @click="page.props.flash = null" class="text-gray-500 hover:text-gray-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form @submit.prevent="submitBulkAssignment">
                        <!-- Filters -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div>
                                <InputLabel value="Class" />
                                <select v-model="filterForm.class_id" class="form-select" required>
                                    <option value="">-- Select Class --</option>
                                    <option v-for="c in classes" :key="c.id" :value="c.id">{{ c.class_name }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Session" />
                                <select v-model="filterForm.session_id" class="form-select" required>
                                    <option value="">-- Select Session --</option>
                                    <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Section" />
                                <select v-model="filterForm.section_id" class="form-select" required>
                                    <option value="">-- Select Section --</option>
                                    <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                            <div>
                                <InputLabel value="Group" />
                                <select v-model="filterForm.group_id" class="form-select">
                                    <option value="">-- All Groups --</option>
                                    <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Students List -->
                        <div v-if="loading" class="text-center py-8 text-gray-500">
                            Loading students...
                        </div>
                        <div v-else-if="students.length === 0 && filterForm.class_id" class="text-center py-8 text-gray-500">
                            No active students found
                        </div>
                        <div v-else-if="students.length > 0" class="mb-6">
                            <div class="flex justify-between items-center mb-3">
                                <h3 class="font-semibold">Students ({{ students.length }})</h3>
                                <div class="space-x-3">
                                    <button type="button" @click="selectAll" class="text-blue-600 text-sm">Select All</button>
                                    <button type="button" @click="deselectAll" class="text-red-600 text-sm">Clear</button>
                                </div>
                            </div>
                            <div class="border rounded-lg bg-gray-50 p-4 max-h-96 overflow-y-auto">
                                <label v-for="student in students" :key="student.id"
                                       class="flex items-center space-x-3 p-2 hover:bg-white rounded cursor-pointer">
                                    <input type="checkbox" :value="student.id" v-model="form.student_ids"
                                           class="rounded text-blue-600">
                                    <span class="text-sm">{{ student.roll_number }} - {{ student.name }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Fee Type -->
                        <div class="mb-6">
                            <InputLabel value="Fee Type" />
                            <select v-model="form.fee_type_id" class="form-select" required>
                                <option value="">-- Select Fee Type --</option>
                                <option v-for="f in feeTypes" :key="f.id" :value="f.id">
                                    {{ f.name }} ({{ f.amount }} BDT)
                                </option>
                            </select>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end gap-3">
                            <Link :href="route('student-fee-assignments.index')" class="btn btn-secondary">
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing || !form.student_ids.length">
                                {{ form.processing ? 'Saving...' : `Assign to ${form.student_ids.length} Students` }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>


<style scoped>

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