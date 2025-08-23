<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref, computed, watchEffect } from 'vue';

const props = defineProps({
    exams: Object, // Paginated exams data (now includes total_marks, passing_marks)
    sessions: Array, // Array of all active sessions (for display, even if not filtering)
});

const flash = computed(() => usePage().props.flash || {});

// Reactive variables for delete confirmation modal
const showDeleteModal = ref(false);
const examToDelete = ref(null);

// Form for delete action
const form = useForm({});

// Function to get status text
const getStatusText = (status) => {
    return status === 0 ? 'Active' : 'Inactive';
};

// Functions for delete confirmation modal
const confirmDelete = (exam) => {
    examToDelete.value = exam;
    showDeleteModal.value = true;
};

const deleteExam = () => {
    if (examToDelete.value) {
        form.delete(route('exams.destroy', examToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
            },
            onError: (errors) => {
                console.error("Error deleting exam:", errors);
                // Flash message will be handled by watchEffect
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    examToDelete.value = null;
};

// Watch for flash messages and display SweetAlert
watchEffect(() => {
    if (flash.value && flash.value.message) {
        Swal.fire({
            icon: flash.value.type === 'success' ? 'success' : 'error',
            title: flash.value.type === 'success' ? 'Success!' : 'Error!',
            text: flash.value.message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }
});
</script>

<template>
    <Head title="Exam Management" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Exam Management</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title h5 mb-0">All Exams</h3>
                        <Link
                            :href="route('exams.create')"
                            class="btn btn-dark btn-sm rounded"
                        >
                            Add New Exam
                        </Link>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Exam Name</th>
                                    <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Session</th>
                                    <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Total Marks</th> <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Passing Marks</th> <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Status</th>
                                    <th scope="col" class="px-3 py-3 text-end text-uppercase text-muted small">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white" v-if="exams.data.length">
                                <tr v-for="exam in exams.data" :key="exam.id">
                                    <td class="px-3 py-4 text-nowrap">{{ exam.exam_name }}</td>
                                    <td class="px-3 py-4 text-nowrap">{{ exam.session?.name || 'N/A' }}</td>
                                    <td class="px-3 py-4 text-nowrap">{{ exam.total_marks }}</td> <td class="px-3 py-4 text-nowrap">{{ exam.passing_marks }}</td> <td class="px-3 py-4 text-nowrap">
                                        <span
                                            :class="{
                                                'badge bg-success-subtle text-success-emphasis': exam.status === 0,
                                                'badge bg-danger-subtle text-danger-emphasis': exam.status === 1,
                                            }"
                                            class="px-2 py-1 rounded-pill small"
                                        >
                                            {{ getStatusText(exam.status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-4 text-end text-nowrap">
                                        <Link :href="route('exams.edit', exam.id)" class="btn btn-sm btn-info me-2">Edit</Link>
                                        <DangerButton @click="confirmDelete(exam)" class="btn btn-sm btn-danger">Delete</DangerButton>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No exams found.</td> </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <span class="text-muted small" v-if="exams.total > 0">
                            Showing {{ exams.from }} to {{ exams.to }} of {{ exams.total }} results
                        </span>
                        <span v-else class="text-muted small">No results found</span>

                        <nav aria-label="Page navigation" v-if="exams.links && exams.links.length > 3">
                            <ul class="pagination mb-0">
                                <li v-for="link in exams.links" :key="link.label" class="page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
                                    <Link
                                        :href="link.url || '#'"
                                        v-html="link.label"
                                        class="page-link"
                                    />
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" :class="{ 'show d-block': showDeleteModal }" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" @click="closeDeleteModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete exam:
                        <span class="fw-bold">{{ examToDelete ? examToDelete.exam_name : '' }}</span>
                        for session:
                        <span class="fw-bold">{{ examToDelete ? examToDelete.session?.name : '' }}</span>?
                        This action cannot be undone.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeDeleteModal">Cancel</button>
                        <DangerButton @click="deleteExam" class="btn btn-danger">Delete</DangerButton>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showDeleteModal" class="modal-backdrop fade show"></div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No additional scoped styles needed as Bootstrap handles most of it. */
</style>