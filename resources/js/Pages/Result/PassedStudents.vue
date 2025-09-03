<script setup>
/* global Swal */
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { ref, computed } from 'vue'

const props = defineProps({
    passedStudents: Array,
})

// State for sorting
const sortBy = ref('name')
const sortDirection = ref('asc')

// Form for promoting students
const promoteForm = useForm({});

const sortedStudents = computed(() => {
    const students = [...props.passedStudents]
    return students.sort((a, b) => {
        // Handle nested properties for sorting
        const getSortValue = (obj, path) => {
            return path.split('.').reduce((o, key) => o ? o[key] : null, obj)
        }

        const aValue = getSortValue(a, sortBy.value);
        const bValue = getSortValue(b, sortBy.value);

        if (aValue < bValue) {
            return sortDirection.value === 'asc' ? -1 : 1
        }
        if (aValue > bValue) {
            return sortDirection.value === 'asc' ? 1 : -1
        }
        return 0
    })
})

const sortTable = (column) => {
    if (sortBy.value === column) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
    } else {
        sortBy.value = column
        sortDirection.value = 'asc'
    }
}

// Inline SVG Icons for sorting and promotion
const icons = {
    sortAsc: `<svg xmlns="http://www.w3.org/2000/svg" class="ml-1 w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m5 17 4-4 4 4"/><path d="M9 13V3"/></svg>`,
    sortDesc: `<svg xmlns="http://www.w3.org/2000/svg" class="ml-1 w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 7-4 4-4-4"/><path d="M5 11V21"/></svg>`,
    sortBoth: `<svg xmlns="http://www.w3.org/2000/svg" class="ml-1 w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 3 3 3-3"/><path d="m9 6 3-3 3 3"/><path d="M12 21V3"/></svg>`,
    promote: `<svg xmlns="http://www.w3.org/0000/svg" class="w-4 h-4 mr-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 17.5v-11H8a4 4 0 0 1 4 4v5"/><path d="m9 17.5 3 3 3-3"/></svg>`
};

const getSortIcon = (column) => {
    if (sortBy.value === column) {
        return sortDirection.value === 'asc' ? icons.sortDesc : icons.sortAsc
    }
    return icons.sortBoth
}

const promoteStudents = () => {
    Swal.fire({
        title: 'Are you sure?',
        text: "This action will promote all passed students. This cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10B981',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, promote them!'
    }).then((result) => {
        if (result.isConfirmed) {
            promoteForm.post(route('students.promote'), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        title: 'Promoted!',
                        text: 'All passed students have been successfully promoted.',
                        icon: 'success',
                        timer: 3000,
                        showConfirmButton: false
                    })
                },
                onError: (errors) => {
                    let errorMessage = 'An error occurred during promotion. Please try again.';
                    if (errors && errors.message) {
                        errorMessage = errors.message;
                    }
                    Swal.fire({
                        title: 'Promotion Failed!',
                        text: errorMessage,
                        icon: 'error'
                    });
                }
            })
        }
    })
}
</script>

<template>
    <Head title="Passed Students" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Passed Students List</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2 sm:mb-0">Students who Passed Final Exam</h3>
                        <div v-if="passedStudents && passedStudents.length > 0">
                            <button @click="promoteStudents" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-4 rounded-xl shadow-lg transition duration-300 flex items-center">
                                <div v-html="icons.promote"></div>
                                Promote Passed Students
                            </button>
                        </div>
                    </div>

                    <div v-if="!passedStudents || passedStudents.length === 0" class="text-center py-10 text-gray-500">
                        <p class="text-xl font-medium mb-2">No students have passed their final exam yet.</p>
                        <p>Results will appear here once they are published.</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 rounded-lg">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                        @click="sortTable('name')"
                                    >
                                        <div class="flex items-center">
                                            <span>Name</span>
                                            <div v-html="getSortIcon('name')"></div>
                                        </div>
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                        @click="sortTable('className.class_name')"
                                    >
                                        <div class="flex items-center">
                                            <span>Class</span>
                                            <div v-html="getSortIcon('className.class_name')"></div>
                                        </div>
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                        @click="sortTable('overall_status')"
                                    >
                                        <div class="flex items-center">
                                            <span>Final Status</span>
                                            <div v-html="getSortIcon('overall_status')"></div>
                                        </div>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="student in sortedStudents" :key="student.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ student.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ student.className.class_name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            {{ student.overall_status }}
                                        </span>
                                    </td>
                                    
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
