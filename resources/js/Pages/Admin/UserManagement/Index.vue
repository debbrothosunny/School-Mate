<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// Assuming these components exist from Breeze
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    users: Array,           // List of users with their current roles
    availableRoles: Array,  // All available roles (admin, teacher, accounts)
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
    // Pre-select the user's current primary role, if any, or null
    form.role = user.roles.length > 0 ? user.roles[0] : null;
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
        },
        onError: (errors) => {
            console.error("Error assigning role:", errors);
        },
    });
};

// Search functionality
const search = ref('');

// Computed property to filter users based on search input
const filteredUsers = computed(() => {
    if (!search.value) {
        return props.users;
    }
    const searchTerm = search.value.toLowerCase();
    return props.users.filter(user =>
        user.name.toLowerCase().includes(searchTerm) ||
        user.email.toLowerCase().includes(searchTerm)
    );
});

// Use filtered users for display and pagination
const displayedUsers = computed(() => filteredUsers.value);

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
            <h2 class="font-semibold text-xl text-black leading-tight">User Management</h2>
        </template>

        <div class="py-6 px-4 sm:px-6 lg:px-8 bg-gray-100 min-h-screen font-inter">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Top Bar: Search and Items per page -->
                    <div class="flex items-center justify-between mb-6 space-x-4">
                        <select v-model="itemsPerPage" class="py-2 px-4 rounded-lg bg-white text-gray-900 border border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <div class="relative flex-grow">
                            <input
                                type="text"
                                v-model="search"
                                placeholder="Search User..."
                                class="w-full pl-10 pr-4 py-2 rounded-lg bg-white text-gray-900 placeholder-gray-500 border border-gray-300 focus:ring-orange-500 focus:border-orange-500"
                            >
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-lg border border-gray-300">
                        <table class="min-w-full divide-y divide-gray-300">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">User</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-300">
                                <tr v-for="user in paginatedUsers" :key="user.id" class="hover:bg-gray-100">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 flex items-center">
                                            <svg class="h-4 w-4 text-gray-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            {{ user.name }}
                                        </div>
                                        <div class="text-sm text-gray-600 ml-6">{{ user.email }}</div>
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
                                            <button @click="openRoleModal(user)" class="text-gray-500 hover:text-gray-700">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="displayedUsers.length === 0">
                                    <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No users found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between border-t border-gray-300 bg-white px-4 py-3 sm:px-6 rounded-b-lg">
                        <div class="flex flex-1 justify-between sm:hidden">
                            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="relative inline-flex items-center rounded-md border border-gray-300 bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">Previous</button>
                            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300">Next</button>
                        </div>
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
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
                                    <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-500 ring-1 ring-inset ring-gray-300 hover:bg-gray-200 focus:z-20 focus:outline-offset-0">
                                        <span class="sr-only">Previous</span>
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button v-for="page in totalPages" :key="page" @click="goToPage(page)" :aria-current="currentPage === page ? 'page' : undefined" :class="[
                                        'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 focus:outline-offset-0',
                                        currentPage === page ? 'z-10 bg-orange-500 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-500' : 'text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-200 hover:text-gray-900'
                                    ]">
                                        {{ page }}
                                    </button>
                                    <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-500 ring-1 ring-inset ring-gray-300 hover:bg-gray-200 focus:z-20 focus:outline-offset-0">
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
            <div class="relative p-8 border border-gray-300 w-full max-w-md shadow-lg rounded-lg bg-white text-gray-900">
                <h3 class="text-xl font-bold text-black mb-6">Assign Role to {{ selectedUser?.name }}</h3>
                <form @submit.prevent="assignRole">
                    <div class="mb-6">
                        <InputLabel for="role" value="Select Role" class="text-gray-700 mb-2" />
                        <select
                            id="role"
                            v-model="form.role"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base bg-white border border-gray-300 rounded-md focus:outline-none focus:ring-orange-500 focus:border-orange-500 text-gray-900"
                        >
                            <option :value="null" class="bg-white text-gray-900">No Role</option>
                            <option v-for="roleName in availableRoles" :key="roleName" :value="roleName" class="bg-white text-gray-900">
                                {{ roleName.charAt(0).toUpperCase() + roleName.slice(1) }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button type="button" @click="showRoleModal = false" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 focus:ring-offset-white">Cancel</button>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="bg-orange-600 hover:bg-orange-700 text-white">
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
