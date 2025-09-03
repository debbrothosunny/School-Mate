<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, computed, watchEffect } from 'vue';

const props = defineProps({
    classSubjects: Object,
});

const flash = computed(() => usePage().props.flash || {});
const searchQuery = ref('');
const showDeleteModal = ref(false);
const classSubjectToDelete = ref(null);
const form = useForm({});

const filteredClassSubjects = computed(() => {
    // Check if the data exists before trying to filter
    if (!props.classSubjects || !props.classSubjects.data) {
        return [];
    }

    if (!searchQuery.value) {
        return props.classSubjects.data;
    }

    const lowerCaseQuery = searchQuery.value.toLowerCase();

    return props.classSubjects.data.filter(cs => {
        // Accessing the class name through the nested property 'class_name'
        const className = cs.class_name?.class_name?.toLowerCase() || '';
        const subjectName = cs.subject?.name?.toLowerCase() || '';
        const teacherName = cs.teacher?.name?.toLowerCase() || '';
        const sessionName = cs.session?.name?.toLowerCase() || '';
        const sectionName = cs.section?.name?.toLowerCase() || '';
        const groupName = cs.group?.name?.toLowerCase() || '';
        const statusText = getStatusText(cs.status).toLowerCase();

        return (
            className.includes(lowerCaseQuery) ||
            subjectName.includes(lowerCaseQuery) ||
            teacherName.includes(lowerCaseQuery) ||
            sessionName.includes(lowerCaseQuery) ||
            sectionName.includes(lowerCaseQuery) ||
            groupName.includes(lowerCaseQuery) ||
            statusText.includes(lowerCaseQuery)
        );
    });
});

const getStatusText = (status) => {
    return status === 0 ? 'Active' : 'Inactive';
};

const confirmDelete = (classSubject) => {
    classSubjectToDelete.value = classSubject;
    showDeleteModal.value = true;
};
  
const deleteClassSubject = () => {
    if (classSubjectToDelete.value) {
        form.delete(route('class-subjects.destroy', classSubjectToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
            },
            onError: (errors) => {
                console.error("Error deleting class subject:", errors);
                console.log('An error occurred during deletion. Please try again.');
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    classSubjectToDelete.value = null;
};

// Debugging code to inspect the incoming data
watchEffect(() => {
    if (props.classSubjects) {
        console.log("Class Subjects Prop Data:", props.classSubjects.data);
    }
});
</script>

<template>
    <Head title="Class Subject Management" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Class Subject Management</h2>
        </template>
        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title h5 mb-0">All Class Subjects</h3>
                        <Link
                            :href="route('class-subjects.create')"
                            class="btn btn-dark btn-sm rounded"
                        >
                            Assign New Subject to Class
                        </Link>
                    </div>
                    <div class="mb-4">
                        <TextInput
                            id="search"
                            type="text"
                            class="form-control"
                            v-model="searchQuery"
                            placeholder="Search by class, subject, teacher, session, section, or group..."
                        />
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Class</th>
                                    <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Subject</th>
                                    <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Teacher</th>
                                    <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Session</th>
                                    <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Section</th>
                                    <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Group</th>
                                    <th scope="col" class="px-3 py-3 text-uppercase text-muted small">Status</th>
                                    <th scope="col" class="px-3 py-3 text-end text-uppercase text-muted small">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white" v-if="filteredClassSubjects.length">
                                <tr v-for="cs in filteredClassSubjects" :key="cs.id">
                                    <td class="px-3 py-4 text-nowrap">
                                        {{ cs.class_name?.class_name || 'N/A' }}
                                    </td>
                                    <td class="px-3 py-4 text-nowrap">{{ cs.subject?.name || 'N/A' }}</td>
                                    <td class="px-3 py-4 text-nowrap">{{ cs.teacher?.name || 'N/A' }}</td>
                                    <td class="px-3 py-4 text-nowrap">{{ cs.session?.name || 'N/A' }}</td>
                                    <td class="px-3 py-4 text-nowrap">{{ cs.section?.name || 'N/A' }}</td>
                                    <td class="px-3 py-4 text-nowrap">{{ cs.group?.name || 'N/A' }}</td>
                                    <td class="px-3 py-4 text-nowrap">
                                        <span
                                            :class="{
                                                'badge bg-success-subtle text-success-emphasis': cs.status === 0,
                                                'badge bg-danger-subtle text-danger-emphasis': cs.status === 1,
                                            }"
                                            class="px-2 py-1 rounded-pill small"
                                        >
                                            {{ getStatusText(cs.status) }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-4 text-end text-nowrap">
                                        <Link :href="route('class-subjects.edit', cs.id)" class="btn btn-sm btn-info me-2">Edit</Link>
                                        <DangerButton @click="confirmDelete(cs)" class="btn btn-sm btn-danger">Delete</DangerButton>
                                    </td>
                                </tr>
                            </tbody>
                            <tbody v-else>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No class subjects found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-4" v-if="!searchQuery && props.classSubjects.data.length > 0">
                        <span class="text-muted small" v-if="classSubjects.total > 0">
                            Showing {{ classSubjects.from }} to {{ classSubjects.to }} of {{ classSubjects.total }} results
                        </span>
                        <span v-else class="text-muted small">No results found</span>
                        <nav aria-label="Page navigation" v-if="classSubjects.links && classSubjects.links.length > 3">
                            <ul class="pagination mb-0">
                                <li v-for="link in classSubjects.links" :key="link.label" class="page-item" :class="{ 'active': link.active, 'disabled': !link.url }">
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
                        Are you sure you want to delete this class subject assignment?
                        <br>
                        <span class="fw-bold">{{ classSubjectToDelete?.class_name?.class_name || '' }}</span> -
                        <span class="fw-bold">{{ classSubjectToDelete?.subject?.name || '' }}</span>
                        for Session: <span class="fw-bold">{{ classSubjectToDelete?.session?.name || '' }}</span>
                        and Section: <span class="fw-bold">{{ classSubjectToDelete?.section?.name || '' }}</span>
                        <span v-if="classSubjectToDelete?.group?.name"> and Group: <span class="fw-bold">{{ classSubjectToDelete?.group?.name || '' }}</span></span>?
                        This action cannot be undone.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="closeDeleteModal">Cancel</button>
                        <DangerButton @click="deleteClassSubject" class="btn btn-danger">Delete</DangerButton>
                    </div>
                </div>
            </div>
        </div>
        <div v-if="showDeleteModal" class="modal-backdrop fade show"></div>
    </AuthenticatedLayout>
</template>
<style scoped>
</style>
