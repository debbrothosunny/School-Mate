<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref } from 'vue';

const props = defineProps({
    sections: Array,
});

const showDeleteModal = ref(false); // Renamed for clarity
const sectionToDelete = ref(null);  // Renamed for clarity

const openDeleteModal = (section) => { // Renamed for clarity
    sectionToDelete.value = section;
    showDeleteModal.value = true;
};

// Function to handle section deletion
const deleteSection = () => {
    if (sectionToDelete.value) {
        // Use an empty form for a DELETE request
        useForm({}).delete(route('sections.destroy', sectionToDelete.value.id), {
            preserveScroll: true, // Keep scroll position after action
            onSuccess: () => {
                // Close modal and clear data on successful deletion
                showDeleteModal.value = false;
                sectionToDelete.value = null;
            },
            onError: (errors) => {
                console.error('Deletion failed:', errors);
                // Optionally, display a user-friendly error message
            }
        });
    }
};
</script>

<template>
    <Head title="Section Management" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Section Management</h2>
        </template>

        <!-- Removed the redundant py-12 and max-w-7xl mx-auto divs.
             AuthenticatedLayout now provides the main padding and structure. -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <!-- Adjusted padding from p-6 to p-4 for a more compact internal layout -->
            <div class="p-4 text-gray-900">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">All Sections</h3>
                    <Link :href="route('sections.create')">
                        <PrimaryButton>Add New Section</PrimaryButton>
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <!-- Reduced horizontal padding from px-6 to px-4 -->
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="section in sections" :key="section.id">
                                <!-- Reduced horizontal padding from px-6 to px-4 -->
                                <td class="px-4 py-4 whitespace-nowrap">{{ section.name }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span :class="{'bg-green-100 text-green-800': section.status === 0, 'bg-red-100 text-red-800': section.status === 1}" class="px-2.5 py-0.5 rounded-full text-xs font-medium">
                                        {{ section.status === 0 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                    <Link :href="route('sections.edit', section.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</Link>
                                    <DangerButton @click="openDeleteModal(section)">Delete</DangerButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="!sections.length" class="text-center py-4 text-gray-500">
                        No sections found.
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
            <div class="relative p-5 border w-96 shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Deletion</h3>
                <p class="mb-4">
                    Are you sure you want to permanently delete "{{ sectionToDelete.name }}"? This action cannot be undone.
                </p>
                <div class="mt-4 flex justify-end">
                    <button type="button" @click="showDeleteModal = false" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 mr-2">Cancel</button>
                    <DangerButton @click="deleteSection">Delete</DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No specific styles needed beyond TailwindCSS for this component */
</style>
