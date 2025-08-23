<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, computed, watchEffect } from 'vue';

const props = defineProps({
    subjects: Object,
});

const flash = computed(() => usePage().props.flash || {});
const searchQuery = ref('');
const showDeleteModal = ref(false);
const subjectToDelete = ref(null);
const form = useForm({});

const filteredSubjects = computed(() => {
    if (!searchQuery.value) return props.subjects.data;
    const q = searchQuery.value.toLowerCase();
    return props.subjects.data.filter(subject =>
        (subject.name && subject.name.toLowerCase().includes(q)) ||
        (subject.code && subject.code.toLowerCase().includes(q)) ||
        (subject.status === 0 ? 'active' : 'inactive').includes(q) ||
        subject.full_marks.toString().includes(q) ||
        subject.passing_marks.toString().includes(q)
    );
});

const getStatusText = status => status === 0 ? 'Active' : 'Inactive';

const confirmDelete = subject => {
    subjectToDelete.value = subject;
    showDeleteModal.value = true;
};

const deleteSubject = () => {
    if (subjectToDelete.value) {
        form.delete(route('subjects.destroy', subjectToDelete.value.id), {
            preserveScroll: true,
            onSuccess: closeDeleteModal,
            onError: () => closeDeleteModal(),
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    subjectToDelete.value = null;
};

watchEffect(() => {
    if (flash.value?.message) {
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
    <Head title="Subject Management" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Subject Management</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title h5 mb-0">All Subjects</h3>
                        <Link :href="route('subjects.create')" class="btn btn-dark btn-sm rounded">
                            Add New Subject
                        </Link>
                    </div>

                    <!-- Search Input -->
                    <div class="mb-4">
                        <TextInput
                            id="search"
                            type="text"
                            class="form-control"
                            v-model="searchQuery"
                            placeholder="Search by name, code, status, or marks..."
                        />
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-3 py-3 text-uppercase text-muted small">Name</th>
                                    <th class="px-3 py-3 text-uppercase text-muted small">Code</th>
                                    <th class="px-3 py-3 text-uppercase text-muted small">Full Marks</th>
                                    <th class="px-3 py-3 text-uppercase text-muted small">Passing Marks</th>
                                    <th class="px-3 py-3 text-uppercase text-muted small">Status</th>
                                    <th class="px-3 py-3 text-end text-uppercase text-muted small">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white" v-if="filteredSubjects.length">
                                <tr v-for="subject in filteredSubjects" :key="subject.id">
                                    <td class="px-3 py-4 text-nowrap">{{ subject.name }}</td>
                                    <td class="px-3 py-4 text-nowrap text-muted">{{ subject.code || 'N/A' }}</td>
                                    <td class="px-3 py-4 text-nowrap">{{ subject.full_marks }}</td>
                                    <td class="px-3 py-4 text-nowrap">{{ subject.passing_marks }}</td>
                                    <td class="px-3 py-4 text-nowrap">
                                        <span
                                            :class="{
                                                'badge bg-success-subtle text-success-emphasis': subject.status === 0,
                                                'badge bg-danger-subtle text-danger-emphasis': subject.status === 1,
                                            }"
                                            class="px-2 py-1 rounded-pill small"
                                        >
                                            {{ getStatusText(subject.status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-4 text-end text-nowrap">
                                        <Link :href="route('subjects.edit', subject.id)" class="btn btn-sm btn-info me-2">Edit</Link>
                                        <DangerButton @click="confirmDelete(subject)" class="btn btn-sm btn-danger">Delete</DangerButton>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No subjects found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4" v-if="!searchQuery">
                        <span class="text-muted small" v-if="subjects.total > 0">
                            Showing {{ subjects.from }} to {{ subjects.to }} of {{ subjects.total }} results
                        </span>
                        <span v-else class="text-muted small">No results found</span>

                        <nav aria-label="Page navigation" v-if="subjects.links?.length > 3">
                            <ul class="pagination mb-0">
                                <li v-for="link in subjects.links" :key="link.label" class="page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
                                    <Link :href="link.url || '#'" v-html="link.label" class="page-link" />
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal fade" :class="{ 'show d-block': showDeleteModal }" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                        <button type="button" class="btn-close" @click="closeDeleteModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to delete subject:
                        <span class="fw-bold">{{ subjectToDelete?.name }}</span>?
                        This action cannot be undone.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeDeleteModal">Cancel</button>
                        <DangerButton @click="deleteSubject" class="btn btn-danger">Delete</DangerButton>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showDeleteModal" class="modal-backdrop fade show"></div>
    </AuthenticatedLayout>
</template>

<style scoped>
.text-break {
    word-break: break-word;
}
</style>
