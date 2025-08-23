<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { watch, ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3'; // Import usePage for flash messages

// Props received from the controller
const props = defineProps({
    groups: Object, // Paginated groups object
});

// Computed property for flash messages, ensuring reactivity
const flash = computed(() => usePage().props.flash || {});

// Watch for flash messages and display SweetAlert
watch(() => flash.value, (newFlash) => {
    if (newFlash && newFlash.message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: newFlash.type === 'success' ? 'success' : 'error',
                title: newFlash.type === 'success' ? 'Success!' : 'Error!',
                text: newFlash.message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        } else {
            console.warn('Swal (SweetAlert2) is not defined. Flash messages will not be displayed via Swal.');
            // Fallback to a simple alert if Swal is not available
            alert(newFlash.message);
        }
    }
}, { deep: true }); // Deep watch for nested changes in flash object

// Form for deletion, using Inertia's useForm hook
const deleteForm = useForm({});

// Reactive state for the custom delete modal
const showDeleteModal = ref(false);
const groupToDelete = ref(null);

// Function to open the delete confirmation modal
const openDeleteModal = (group) => {
    groupToDelete.value = group;
    showDeleteModal.value = true;
};

// Function to close the delete confirmation modal
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    groupToDelete.value = null; // Clear the group data
};

// Function to handle group deletion
const deleteGroup = () => {
    if (groupToDelete.value) {
        deleteForm.delete(route('groups.destroy', groupToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
            },
            onError: (errors) => {
                console.error('Deletion failed:', errors);
                // Swal message will be shown by the watchEffect
            }
        });
    }
};

// Helper to get status string from boolean
const getStatusString = (status) => {
    return status ? 'Inactive' : 'Active';
};

// Helper to determine status badge class based on boolean status
const getStatusBadgeClass = (status) => {
    return status ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
};
</script>

<template>
    <Head title="Groups" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Groups Management</h2>
        </template>

        <div class="py-12 bg-gray-100">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">

                    <!-- Flash Message Display -->
                    <FlashMessage v-if="flash && flash.message" :flash="flash" class="mb-4" />

                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">Group List</h3>
                        <Link :href="route('groups.create')">
                            <PrimaryButton class="inline-flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                Add New Group
                            </PrimaryButton>
                        </Link>
                    </div>

                    <!-- Groups Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 rounded-lg">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="relative px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="groups.data.length === 0">
                                    <td colspan="3" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                        No groups found.
                                    </td>
                                </tr>
                                <tr v-for="group in groups.data" :key="group.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ group.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusBadgeClass(group.status)]">
                                            {{ getStatusString(group.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                        <Link :href="route('groups.edit', group.id)" class="text-indigo-600 hover:text-indigo-900 transition-colors">
                                            Edit
                                        </Link>
                                        <DangerButton @click="openDeleteModal(group)" class="text-red-600 hover:text-red-900 transition-colors">
                                            Delete
                                        </DangerButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links -->
                    <div v-if="groups.links.length > 3" class="mt-6 flex justify-center">
                        <div class="flex flex-wrap -mb-1">
                            <template v-for="(link, key) in groups.links" :key="key">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded-lg" v-html="link.label" />
                                <Link v-else class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded-lg hover:bg-gray-200 focus:border-indigo-500 focus:text-indigo-500" :class="{ 'bg-gray-200 font-semibold': link.active }" :href="link.url" v-html="link.label" />
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity">
            <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-sm transform transition-all">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Confirm Deletion</h3>
                    <button @click="closeDeleteModal" class="text-gray-400 hover:text-gray-600 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p class="mb-6 text-gray-700">
                    Are you sure you want to permanently delete the group "<strong>{{ groupToDelete ? groupToDelete.name : '' }}</strong>"? This action cannot be undone.
                </p>
                <div class="flex justify-end space-x-3">
                    <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <DangerButton @click="deleteGroup">
                        Delete
                    </DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
