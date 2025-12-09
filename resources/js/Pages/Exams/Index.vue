<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref, computed, watchEffect } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    exams: Object,
    sessions: Array,
});

const flash = computed(() => usePage().props.flash || {});
const showDeleteModal = ref(false);
const examToDelete = ref(null);
const form = useForm({});

const getStatusText = (status) => {
    return status === 0 ? 'Active' : 'Inactive';
};

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
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'The exam has been deleted.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
            },
            onError: (errors) => {
                console.error("Error deleting exam:", errors);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Failed to delete the exam.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                });
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    examToDelete.value = null;
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
    <Head title="Exam Management" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg sm:text-xl md:text-2xl font-semibold text-gray-800 dark:text-gray-100 leading-tight">Exam Management</h2>
        </template>
        <div class="py-6 sm:py-12 bg-gray-100 dark:bg-gray-900 min-h-screen font-inter">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="p-4 sm:p-6 lg:p-8">
                        <!-- Header + Add Button -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                            <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100">All Exams</h3>
                            <Link :href="route('exams.create')">
                                <PrimaryButton
                                    class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md transition bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm hover:shadow-md"
                                    aria-label="Add new exam"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add New Exam
                                </PrimaryButton>
                            </Link>
                        </div>
                        <!-- Exams Table -->
                        <div class="space-y-4 sm:space-y-0">
                            <div v-if="exams.data.length" class="sm:hidden">
                                <div v-for="exam in exams.data" :key="exam.id" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4 shadow-sm transition hover:shadow-md">
                                    <div class="grid gap-2 text-sm">
                                        <div class="flex justify-between items-center">
                                            <span class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[70%]">{{ exam.exam_name }}</span>
                                            <span :class="['px-2 py-1 text-xs font-semibold rounded-full', exam.status === 0 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200']">
                                                {{ getStatusText(exam.status) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Session:</span>
                                            <span class="ml-2 text-gray-500 dark:text-gray-400">{{ exam.session?.name || 'N/A' }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Total Marks:</span>
                                            <span class="ml-2 text-gray-500 dark:text-gray-400">{{ exam.total_marks }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-gray-700 dark:text-gray-300">Passing Marks:</span>
                                            <span class="ml-2 text-gray-500 dark:text-gray-400">{{ exam.passing_marks }}</span>
                                        </div>
                                        <div class="flex justify-end gap-2 mt-3">
                                            <Link
                                                :href="route('exams.edit', exam.id)"
                                                class="inline-flex items-center justify-center min-w-10 min-h-10 p-2 bg-indigo-50 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-800 rounded-md transition focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                                aria-label="Edit exam"
                                            >
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"></path>
                                                </svg>
                                            </Link>
                                            
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="exams.data.length" class="overflow-x-auto hidden sm:block">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Exam Name</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Session</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="exam in exams.data" :key="exam.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100 truncate max-w-xs">{{ exam.exam_name }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ exam.session?.name || 'N/A' }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm">
                                                <span :class="['px-2 py-1 text-xs font-semibold rounded-full', exam.status === 0 ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200']">
                                                    {{ getStatusText(exam.status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-right flex justify-end gap-3">
                                                <Link
                                                    :href="route('exams.edit', exam.id)"
                                                    class="inline-flex items-center justify-center min-w-10 min-h-10 p-2 bg-indigo-50 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-800 rounded-md transition focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                                    aria-label="Edit exam"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"></path>
                                                    </svg>
                                                </Link>
                                                <button
                                                    disabled
                                                    title="Delete time slot is currently disabled"
                                                    class="inline-flex items-center justify-center min-w-10 min-h-10 p-2 
                                                        bg-gray-100 dark:bg-gray-800 
                                                        text-gray-400 dark:text-gray-600 
                                                        rounded-md 
                                                        cursor-not-allowed 
                                                        opacity-60 
                                                        border border-gray-300 dark:border-gray-700"
                                                    aria-label="Delete time slot (disabled)"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 4v12m4-12v12"></path>
                                                    </svg>
                                                </button>
                                                <!-- Time slot deletion disabled -->
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="bg-indigo-50 dark:bg-indigo-900 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-200 px-4 py-3 rounded-md text-center text-sm" role="alert">
                                <p class="mb-0">No exams found. Please create one to get started.</p>
                            </div>
                        </div>
                        <!-- Pagination -->
                        <div v-if="exams.data.length" class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                Showing {{ exams.from }}–{{ exams.to }} of {{ exams.total }} results
                            </span>
                            <div class="flex flex-wrap gap-2" v-if="exams.links && exams.links.length > 3">
                                <Link
                                    v-for="link in exams.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    :class="[
                                        'px-3 py-2 text-sm font-medium rounded-md transition',
                                        link.active ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700',
                                        !link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''
                                    ]"
                                    v-html="link.label"
                                    :aria-label="link.label === '&laquo; Previous' ? 'Previous page' : link.label === 'Next &raquo;' ? 'Next page' : `Page ${link.label}`"
                                />
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Footer -->
                <footer class="mt-8 sm:mt-12 pt-4 sm:pt-6 border-t border-gray-200 dark:border-gray-700 text-center bg-gray-100 dark:bg-gray-900">
                    <p class="text-xs sm:text-sm md:text-base text-gray-900 dark:text-gray-100 font-medium leading-relaxed">
                        © All Rights Reserved. Biddaloy is a product of
                        <a href="https://smithitbd.com/" target="_blank" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors hover:underline">
                            Smith IT
                        </a>
                    </p>
                </footer>
            </div>
        </div>
        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 transition-opacity duration-300">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-4 sm:p-6 w-full max-w-sm sm:max-w-md transform transition-all duration-300 scale-100">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100">Confirm Deletion</h3>
                    <button @click="closeDeleteModal" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition" aria-label="Close modal">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="mb-6 text-sm sm:text-base text-gray-700 dark:text-gray-300">
                    Are you sure you want to permanently delete the exam: 
                    <span class="font-bold">{{ examToDelete ? examToDelete.exam_name : '' }}</span>
                    for session: <span class="font-bold">{{ examToDelete ? examToDelete.session?.name || 'N/A' : '' }}</span>?
                    This action cannot be undone.
                </p>
                <div class="flex justify-end gap-4">
                    <button
                        @click="closeDeleteModal"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md text-sm sm:text-base hover:bg-gray-300 dark:hover:bg-gray-600 transition focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                        aria-label="Cancel deletion"
                    >
                        Cancel
                    </button>
                    <DangerButton
                        @click="deleteExam"
                        :disabled="form.processing"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        class="inline-flex items-center px-4 py-2 text-sm sm:text-base"
                        aria-label="Confirm delete exam"
                    >
                        <svg v-if="form.processing" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Delete
                    </DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.font-inter {
    font-family: 'Inter', sans-serif;
}

/* Mobile card layout */
@media (max-width: 639px) {
    .overflow-x-auto {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .sm\:hidden {
        display: block;
    }
    .sm\:block {
        display: none;
    }
}

/* Tablet and up */
@media (min-width: 640px) {
    .sm\:hidden {
        display: none;
    }
    .sm\:block {
        display: block;
    }
}

/* Modal animations */
.modal-enter-active, .modal-leave-active {
    transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
    opacity: 0;
}
.modal-enter-active .modal-content, .modal-leave-active .modal-content {
    transition: transform 0.3s ease;
}
.modal-enter-from .modal-content, .modal-leave-to .modal-content {
    transform: scale(0.95);
}

/* Touch targets and hover effects */
button, a {
    min-height: 44px;
    min-width: 44px;
    transition: color 0.2s, background-color 0.2s, transform 0.2s;
}
button:hover:not(:disabled), a:hover:not(.opacity-50) {
    transform: scale(1.05);
}

/* Truncate long text */
.truncate {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Responsive padding and font sizes */
@media (max-width: 639px) {
    .max-w-sm {
        max-width: 90%;
    }
    .p-6 {
        padding: 1rem;
    }
    .text-sm {
        font-size: 0.875rem;
    }
    .px-3 {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    .py-2 {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
}

/* Footer adjustments */
@media (max-width: 639px) {
    footer p {
        white-space: normal;
        max-width: 100%;
    }
}

/* Table and card styling */
.table-hover tr:hover {
    background-color: #f9fafb;
}
.bg-success-subtle {
    background-color: #dcfce7;
    color: #15803d;
}
.bg-danger-subtle {
    background-color: #fee2e2;
    color: #b91c1c;
}

/* Smooth transitions for cards */
.transition {
    transition: all 0.2s ease-in-out;
}
</style>