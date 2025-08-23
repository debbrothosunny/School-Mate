<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    gradeConfigurations: Array,
    flash: Object,
});

// State for the delete confirmation modal
const showDeleteModal = ref(false);
const gradeToDeleteId = ref(null);

// --- Delete Functions ---
const confirmDelete = (id) => {
    gradeToDeleteId.value = id;
    showDeleteModal.value = true;
};

const deleteGrade = () => {
    if (gradeToDeleteId.value) {
        useForm({}).delete(route('grade-configurations.destroy', gradeToDeleteId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                gradeToDeleteId.value = null;
            },
            onError: (errors) => {
                console.error('Delete errors:', errors);
                showDeleteModal.value = false;
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    gradeToDeleteId.value = null;
};

// Helper to display flash messages
const getMessageClass = (type) => {
    if (type === 'success') return 'bg-green-100 border border-green-400 text-green-700';
    if (type === 'error') return 'bg-red-100 border border-red-400 text-red-700';
    return '';
};
</script>

<template>
    <Head title="Grade Configurations">
        <!-- Font Awesome CDN for icons -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" xintegrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2xreKzXhSj5u6I5fI5fJ5dFz5dFz5dFz5dFz5dFz5dFz5dFz5dFz5dFz5dFz5dFz5dFz==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Grade Configurations</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                    <!-- Flash Messages -->
                    <div v-if="props.flash?.success" :class="['p-4 mb-4 text-sm rounded-lg', getMessageClass('success')]" role="alert">
                        {{ props.flash.success }}
                    </div>
                    <div v-if="props.flash?.error" :class="['p-4 mb-4 text-sm rounded-lg', getMessageClass('error')]" role="alert">
                        {{ props.flash.error }}
                    </div>

                    <!-- Add New Grade Button -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">Current Grade Configurations</h3>
                        <Link :href="route('grade-configurations.create')">
                            <PrimaryButton>Add New Grade</PrimaryButton>
                        </Link>
                    </div>

                    <!-- List of Grade Configurations -->
                    <div class="overflow-x-auto rounded-lg shadow-sm border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Class Interval</th>
                                    <th scope="col" class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Letter Grade</th>
                                    <th scope="col" class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Grade Point</th>
                                    <th scope="col" class="py-3 px-6 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="py-3 px-6 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="grade in gradeConfigurations" :key="grade.id" class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="py-4 px-6 whitespace-nowrap">{{ grade.class_interval }}</td>
                                    <td class="py-4 px-6 whitespace-nowrap">{{ grade.letter_grade }}</td>
                                    <td class="py-4 px-6 whitespace-nowrap">{{ grade.grade_point }}</td>
                                    <td class="py-4 px-6 whitespace-nowrap">
                                        <span :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${!grade.status ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`">
                                            {{ grade.status ? 'Inactive' : 'Active' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right whitespace-nowrap font-medium">
                                        <Link :href="route('grade-configurations.edit', grade.id)" class="text-indigo-600 hover:text-indigo-900 mr-4">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                            <span class="sr-only">Edit</span>
                                        </Link>
                                        <button @click="confirmDelete(grade.id)" class="text-red-600 hover:text-red-900">
                                            <i class="fa-solid fa-trash-can"></i>
                                            <span class="sr-only">Delete</span>
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="gradeConfigurations.length === 0">
                                    <td colspan="5" class="py-4 px-6 text-center text-gray-500">No grade configurations found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">Confirm Deletion</h3>
                <p class="mb-6 text-gray-600">
                    Are you sure you want to delete this grade configuration? This action cannot be undone.
                </p>
                <div class="flex justify-end space-x-3">
                    <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                    <DangerButton @click="deleteGrade">Delete</DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
