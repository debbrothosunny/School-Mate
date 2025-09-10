<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    studentFeeAssignments: Object, // Paginated list of student fee assignments
});

const flash = computed(() => usePage().props.flash || {});

const form = useForm({});
const showDeleteModal = ref(false);
const assignmentToDelete = ref(null);

const confirmDelete = (assignment) => {
    assignmentToDelete.value = assignment;
    showDeleteModal.value = true;
};

const deleteAssignment = () => {
    if (assignmentToDelete.value) {
        form.delete(route('student-fee-assignments.destroy', assignmentToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
            },
            onError: (errors) => {
                console.error("Error deleting student fee assignment:", errors);
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    assignmentToDelete.value = null;
};

// New function to format the date
const formatDate = (dateString) => {
    if (!dateString) return 'Indefinite';
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};
</script>

<template>
    <Head title="Manage Student Fee Assignments" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Student Fee Assignments</h2>
        </template>

        <div class="py-4">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- NEW: Main container with background, shadow, and rounded corners -->
                <div class="bg-white shadow-md rounded-lg p-4 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Student Fee Assignment List</h3>
                        <Link
                            :href="route('student-fee-assignments.create')"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-xs font-semibold rounded-md hover:bg-gray-700"
                        >
                            Add New Assignment
                        </Link>
                    </div>

                    <div
                        v-if="flash.message"
                        :class="[
                            'p-4 mb-4 text-sm rounded-lg',
                            flash.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                        ]"
                        role="alert"
                    >
                        {{ flash.message }}
                    </div>

                    <div class="overflow-x-auto">
                        <!-- Table also has a shadow and rounded corners -->
                        <table class="min-w-full divide-y divide-gray-200 bg-white shadow-sm rounded-lg">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fee Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applies From</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Applies To</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr
                                    v-for="(assignment, index) in studentFeeAssignments.data"
                                    :key="assignment.id"
                                    :class="{ 'bg-gray-50': index % 2 !== 0 }"
                                >
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ assignment.student ? assignment.student.name : 'N/A' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ assignment.fee_type ? assignment.fee_type.name : 'N/A' }}</td>
                                    <!-- Changed this line -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(assignment.applies_from) }}</td>
                                    <!-- Changed this line to handle null dates -->
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(assignment.applies_to) }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span
                                            :class="{
                                                'bg-green-100 text-green-800': Number(assignment.status) === 0,
                                                'bg-red-100 text-red-800': Number(assignment.status) === 1,
                                            }"
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        >
                                            {{ Number(assignment.status) === 0 ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('student-fee-assignments.edit', assignment.id)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</Link>
                                        <button @click="confirmDelete(assignment)" class="text-red-600 hover:text-red-900">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="!studentFeeAssignments.data.length">
                                    <td colspan="6" class="text-center py-4 text-gray-500">No student fee assignments found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 flex justify-between items-center">
                        <span class="text-sm text-gray-700">
                            Showing {{ studentFeeAssignments.from }} to {{ studentFeeAssignments.to }} of {{ studentFeeAssignments.total }} results
                        </span>
                        <div class="flex">
                            <Link
                                v-for="link in studentFeeAssignments.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-2 text-sm leading-4 font-medium rounded-md',
                                    link.active ? 'bg-indigo-600 text-white' : 'text-gray-700 bg-white hover:bg-gray-100',
                                    !link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''
                                ]"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl w-96">
                <h3 class="text-lg font-semibold mb-4">Confirm Deletion</h3>
                <p class="mb-4">
                    Are you sure you want to delete this fee assignment for
                    <span class="font-bold">{{ assignmentToDelete ? assignmentToDelete.fee_type.name : '' }}</span>
                    for student <span class="font-bold">{{ assignmentToDelete ? assignmentToDelete.student.name : '' }}</span>?
                </p>
                <div class="flex justify-end space-x-4">
                    <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                    <button
                        @click="deleteAssignment"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
