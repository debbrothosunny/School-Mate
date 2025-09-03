<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

import PrimaryButton from '@/Components/PrimaryButton.vue'; // Assuming these components exist from Breeze
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    users: Array,           // List of users with their current roles
    availableRoles: Array, // All available roles (admin, teacher, accounts)
});

// Reactive state for the role assignment modal
const showRoleModal = ref(false);
const selectedUser = ref(null);

// Form to handle role assignment
const form = useForm({
    role: '', // The role to assign
});

// Function to open the modal and pre-fill user data
const openRoleModal = (user) => {
    selectedUser.value = user;
    // Pre-select the user's current primary role, if any
    form.role = user.roles.length > 0 ? user.roles[0] : '';
    showRoleModal.value = true;
};

// Function to submit the role update
const assignRole = () => {
    if (!selectedUser.value) return;

    form.post(route('users.assign-role', selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRoleModal.value = false;
            selectedUser.value = null; // Clear selected user
            form.reset('role'); // Reset form field
            // Inertia will automatically re-render the page with updated data
        },
        onError: (errors) => {
            console.error("Error assigning role:", errors);
        },
    });
};

// Placeholder for search input
const search = ref('');

// Removed selectedRoleFilter as all filters are being removed

const availableRolesDisplay = computed(() => {
    // If availableRoles prop is provided, use it. Otherwise, use a default set.
    return props.availableRoles && props.availableRoles.length > 0
        ? props.availableRoles
        : ['admin', 'teacher', 'accounts', 'editor', 'subscriber', 'maintainer', 'author', 'student'];
});

const displayedUsers = ref(props.users.length > 0 ? props.users : dummyUsers); // Use dummy data if no users are passed

// Pagination (simple client-side for demonstration)
const currentPage = ref(1);
const itemsPerPage = ref(10); // Default items per page
const totalPages = computed(() => Math.ceil(displayedUsers.value.length / itemsPerPage.value));

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return displayedUsers.value.slice(start, end);
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

</script>

<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-100 leading-tight">User Management</h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 bg-gray-950 min-h-screen font-inter">
            <div class="max-w-7xl mx-auto">
                <div class="bg-gray-900 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Top Bar: Search, Items per page, Actions -->
                    <div class="flex flex-col md:flex-row items-center justify-between mb-6 space-y-4 md:space-y-0 md:space-x-4">
                        <div class="flex items-center space-x-4 w-full md:w-auto">
                            <select v-model="itemsPerPage" class="py-2 px-4 rounded-lg bg-gray-700 text-gray-200 border border-gray-600 focus:ring-blue-500 focus:border-blue-500">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <div class="relative flex-grow">
                                <input
                                    type="text"
                                    v-model="search"
                                    placeholder="Search User"
                                    class="w-full pl-10 pr-4 py-2 rounded-lg bg-gray-700 text-gray-200 placeholder-gray-400 border border-gray-600 focus:ring-blue-500 focus:border-blue-500"
                                >
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex space-x-2 w-full md:w-auto justify-end">
                            <button class="flex items-center px-4 py-2 bg-gray-700 text-gray-200 rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:ring-offset-gray-800 border border-gray-600">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Export
                            </button>
                            <button class="flex items-center px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus:ring-offset-gray-800">
                                <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                Add New User
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-lg border border-gray-700">
                        <table class="min-w-full divide-y divide-gray-700">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                        <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 bg-gray-800 border-gray-600 rounded">
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">User</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray-800 divide-y divide-gray-700">
                                <tr v-for="user in paginatedUsers" :key="user.id" class="hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="checkbox" class="form-checkbox h-4 w-4 text-blue-600 bg-gray-900 border-gray-700 rounded">
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-200 flex items-center">
                                            <svg class="h-4 w-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            {{ user.name }}
                                        </div>
                                        <div class="text-sm text-gray-400 ml-6">{{ user.email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span v-for="role in user.roles" :key="role" :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mr-1',
                                            role === 'admin' ? 'bg-purple-600 text-white' :
                                            role === 'editor' ? 'bg-yellow-600 text-white' :
                                            role === 'subscriber' ? 'bg-blue-600 text-white' :
                                            role === 'maintainer' ? 'bg-teal-600 text-white' :
                                            role === 'author' ? 'bg-orange-600 text-white' :
                                            role === 'student' ? 'bg-pink-600 text-white' : // Student role color
                                            role === 'teacher' ? 'bg-green-600 text-white' : // Teacher role color
                                            role === 'accounts' ? 'bg-indigo-600 text-white' : // Accounts role color
                                            'bg-gray-500 text-white'
                                        ]">
                                            <span v-if="role === 'editor'" class="mr-1">✏️</span>
                                            <span v-else-if="role === 'subscriber'" class="mr-1">👤</span>
                                            <span v-else-if="role === 'admin'" class="mr-1">👑</span>
                                            <span v-else-if="role === 'maintainer'" class="mr-1">⚙️</span>
                                            <span v-else-if="role === 'author'" class="mr-1">✍️</span>
                                            <span v-else-if="role === 'student'" class="mr-1">🎓</span>
                                            <span v-else-if="role === 'teacher'" class="mr-1">🧑‍🏫</span>
                                            <span v-else-if="role === 'accounts'" class="mr-1">💰</span>
                                            {{ role.charAt(0).toUpperCase() + role.slice(1) }}
                                        </span>
                                        <span v-if="user.roles.length === 0" class="text-gray-500 text-xs">No Role</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <button @click="openRoleModal(user)" class="text-gray-400 hover:text-gray-300">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"></path></svg>
                                            </button>
                                            <button class="text-gray-400 hover:text-gray-300">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                            <button class="text-gray-400 hover:text-gray-300">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                            <button class="text-gray-400 hover:text-gray-300">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="displayedUsers.length === 0">
                                    <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-400 text-center">No users found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between border-t border-gray-700 bg-gray-800 px-4 py-3 sm:px-6 rounded-b-lg">
                        <div class="flex flex-1 justify-between sm:hidden">
                            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="relative inline-flex items-center rounded-md border border-gray-600 bg-gray-700 px-4 py-2 text-sm font-medium text-gray-300 hover:bg-gray-600">Previous</button>
                            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" class="relative ml-3 inline-flex items-center rounded-md border border-gray-600 bg-gray-700 px-4 py-2 text-sm font-medium text-gray-300 hover:bg-gray-600">Next</button>
                        </div>
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-300">
                                    Showing
                                    <span class="font-medium">{{ (currentPage - 1) * itemsPerPage + 1 }}</span>
                                    to
                                    <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, displayedUsers.length) }}</span>
                                    of
                                    <span class="font-medium">{{ displayedUsers.length }}</span>
                                    entries
                                </p>
                            </div>
                            <div>
                                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                    <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-700 hover:bg-gray-700 focus:z-20 focus:outline-offset-0">
                                        <span class="sr-only">Previous</span>
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button v-for="page in totalPages" :key="page" @click="goToPage(page)" :aria-current="currentPage === page ? 'page' : undefined" :class="[
                                        'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 focus:outline-offset-0',
                                        currentPage === page ? 'z-10 bg-orange-500 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500' : 'text-gray-300 ring-1 ring-inset ring-gray-700 hover:bg-gray-700 hover:text-white'
                                    ]">
                                        {{ page }}
                                    </button>
                                    <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-700 hover:bg-gray-700 focus:z-20 focus:outline-offset-0">
                                        <span class="sr-only">Next</span>
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Role Assignment Modal -->
        <div v-if="showRoleModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full flex items-center justify-center z-50">
            <div class="relative p-8 border border-gray-700 w-full max-w-md shadow-lg rounded-lg bg-gray-800 text-gray-200">
                <h3 class="text-xl font-bold text-white mb-6">Assign Role to {{ selectedUser?.name }}</h3>
                <form @submit.prevent="assignRole">
                    <div class="mb-6">
                        <InputLabel for="role" value="Select Role" class="text-gray-300 mb-2" />
                        <select
                            id="role"
                            v-model="form.role"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base bg-gray-700 border border-gray-600 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-gray-200"
                        >
                            <option value="" class="bg-gray-700 text-gray-200">No Role</option>
                            <option v-for="roleName in availableRolesDisplay" :key="roleName" :value="roleName" class="bg-gray-700 text-gray-200">
                                {{ roleName.charAt(0).toUpperCase() + roleName.slice(1) }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button type="button" @click="showRoleModal = false" class="inline-flex justify-center py-2 px-4 border border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-300 bg-gray-700 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 focus:ring-offset-gray-800">Cancel</button>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700">
                            {{ form.processing ? 'Assigning...' : 'Assign Role' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No specific styles needed beyond TailwindCSS for this component */
.font-inter {
    font-family: 'Inter', sans-serif;
}
</style>
