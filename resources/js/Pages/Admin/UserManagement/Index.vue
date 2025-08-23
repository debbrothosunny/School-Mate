<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// Assuming these components exist and are correctly imported
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    users: Array,
    availableRoles: Array,
});

// Dummy user data for demonstration if no users are provided
const dummyUsers = [
    { id: 1, name: 'John Doe', email: 'john.doe@example.com', roles: ['admin'] },
    { id: 2, name: 'Jane Smith', email: 'jane.smith@example.com', roles: ['teacher'] },
    { id: 3, name: 'Peter Jones', email: 'peter.jones@example.com', roles: ['student'] },
    { id: 4, name: 'Mary Brown', email: 'mary.brown@example.com', roles: ['accounts'] },
    { id: 5, name: 'Chris Evans', email: 'chris.evans@example.com', roles: ['editor'] },
    { id: 6, name: 'Sarah Miller', email: 'sarah.m@example.com', roles: ['maintainer'] },
    { id: 7, name: 'David Wilson', email: 'david.w@example.com', roles: ['author'] },
    { id: 8, name: 'Emily White', email: 'emily.w@example.com', roles: ['subscriber'] },
    { id: 9, name: 'Michael Chen', email: 'michael.c@example.com', roles: [] },
];

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
    if (!selectedUser.value) {
        console.error("No user selected to assign role.");
        return;
    }

    // Capture the current selected user and the role before resetting the form
    const userToUpdate = selectedUser.value;
    const newRole = form.role;
    
    // Simulate API call for demonstration
    // In a real app, replace this with your Inertia form.post
    // form.post(route('users.assign-role', selectedUser.value.id), {
    //    onSuccess: () => { ... }
    // });
    
    // Simulating success and updating the local data
    userToUpdate.roles = newRole ? [newRole] : [];
    
    // Log the success message using the captured data
    console.log(`Role '${newRole || 'No Role'}' assigned successfully to ${userToUpdate.name}.`);

    // Reset the state after a slight delay to ensure the log is captured correctly
    setTimeout(() => {
        showRoleModal.value = false;
        selectedUser.value = null;
        form.reset('role');
    }, 100);
};

// Placeholder for search input
const search = ref('');

// Filtered and searched users
const filteredUsers = computed(() => {
    const usersToDisplay = props.users && props.users.length > 0 ? props.users : dummyUsers;
    if (!search.value) {
        return usersToDisplay;
    }
    const searchTerm = search.value.toLowerCase();
    return usersToDisplay.filter(user =>
        user.name.toLowerCase().includes(searchTerm) ||
        user.email.toLowerCase().includes(searchTerm)
    );
});

// Dynamic list of available roles for the button group
const availableRolesDisplay = computed(() => {
    return props.availableRoles && props.availableRoles.length > 0
        ? props.availableRoles
        : ['admin', 'teacher', 'accounts', 'editor', 'subscriber', 'maintainer', 'author', 'student'];
});

// Pagination (simple client-side for demonstration)
const currentPage = ref(1);
const itemsPerPage = ref(8); // Default items per page
const totalUsers = computed(() => filteredUsers.value.length);
const totalPages = computed(() => Math.ceil(totalUsers.value / itemsPerPage.value));

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage.value;
    const end = start + itemsPerPage.value;
    return filteredUsers.value.slice(start, end);
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// Map roles to colors for badges
const roleColors = {
    admin: 'bg-purple-600 text-white',
    teacher: 'bg-green-600 text-white',
    accounts: 'bg-indigo-600 text-white',
    editor: 'bg-yellow-600 text-white',
    subscriber: 'bg-blue-600 text-white',
    maintainer: 'bg-teal-600 text-white',
    author: 'bg-orange-600 text-white',
    student: 'bg-pink-600 text-white',
    'no role': 'bg-gray-400 text-white',
};
</script>

<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">User Management</h2>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8 bg-gray-50 min-h-screen font-inter">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white overflow-hidden shadow-2xl rounded-3xl p-8 transition-all duration-300 transform hover:scale-[1.005]">
                    
                    <!-- Header and Search -->
                    <div class="flex flex-col sm:flex-row items-center justify-between mb-8 space-y-4 sm:space-y-0">
                        <h3 class="text-2xl font-extrabold text-gray-900">User List</h3>
                        <div class="relative w-full sm:w-auto">
                            <input
                                type="text"
                                v-model="search"
                                placeholder="Search users..."
                                class="w-full sm:w-64 pl-10 pr-4 py-2 rounded-full border border-gray-300 shadow-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all duration-300"
                            >
                            <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-inner">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider rounded-tl-xl">
                                        User
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider rounded-tr-xl">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="user in paginatedUsers" :key="user.id" class="hover:bg-gray-50 transition-colors duration-150">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 font-bold">
                                                    {{ user.name.charAt(0).toUpperCase() }}
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                                                <div class="text-xs text-gray-500">{{ user.email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <span v-for="role in user.roles" :key="role" :class="[
                                                'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold shadow-sm',
                                                roleColors[role] || 'bg-gray-400 text-white'
                                            ]">
                                                {{ role.charAt(0).toUpperCase() + role.slice(1) }}
                                            </span>
                                            <span v-if="user.roles.length === 0" class="text-gray-400 text-xs">No Role</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <button @click="openRoleModal(user)" class="text-blue-600 hover:text-blue-800 font-bold transition-colors duration-150">
                                                Edit Role
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredUsers.length === 0">
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No users found matching your search.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="flex items-center justify-between border-t border-gray-200 bg-white px-4 py-3 rounded-b-xl sm:px-6 mt-6">
                        <div class="flex flex-1 justify-between sm:hidden">
                            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</button>
                            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</button>
                        </div>
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing <span class="font-medium">{{ (currentPage - 1) * itemsPerPage + 1 }}</span> to <span class="font-medium">{{ Math.min(currentPage * itemsPerPage, filteredUsers.length) }}</span> of <span class="font-medium">{{ filteredUsers.length }}</span> entries
                                </p>
                            </div>
                            <div>
                                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                    <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 transition-colors duration-150">
                                        <span class="sr-only">Previous</span>
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button v-for="page in totalPages" :key="page" @click="goToPage(page)" :aria-current="currentPage === page ? 'page' : undefined" :class="[
                                        'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 focus:outline-offset-0 transition-colors duration-150',
                                        currentPage === page ? 'z-10 bg-orange-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-orange-600' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50'
                                    ]">
                                        {{ page }}
                                    </button>
                                    <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 transition-colors duration-150">
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
        <!-- We use Vue's built-in <Transition> component for a smoother animation. -->
        <!-- The name 'fade' links it to the CSS transition classes. -->
        <Transition name="fade">
            <div v-if="showRoleModal" class="fixed inset-0 bg-gray-900 bg-opacity-80 backdrop-blur-sm h-full w-full flex items-center justify-center z-50">
                <div class="relative p-8 border border-gray-800 w-full max-w-lg shadow-2xl rounded-2xl bg-gray-950 text-white">
                    <h3 class="text-3xl font-bold text-gray-100 mb-6">Assign Role to <span class="text-orange-400">{{ selectedUser?.name }}</span></h3>
                    <form @submit.prevent="assignRole">
                        <div class="mb-6">
                            <InputLabel for="role" value="Select Role" class="text-gray-300 mb-4" />
                            <div class="flex flex-wrap gap-2">
                                <button
                                    type="button"
                                    @click="form.role = roleName"
                                    v-for="roleName in availableRolesDisplay"
                                    :key="roleName"
                                    :class="[
                                        'px-4 py-2 text-sm font-semibold rounded-full shadow-lg transition-all duration-200 transform',
                                        roleName === form.role 
                                            ? 'bg-orange-500 text-white scale-105 ring-2 ring-orange-500 ring-offset-2 ring-offset-gray-950' 
                                            : 'bg-gray-800 text-gray-300 hover:bg-gray-700 hover:text-white',
                                        'focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 focus:ring-offset-gray-950'
                                    ]"
                                >
                                    {{ roleName.charAt(0).toUpperCase() + roleName.slice(1) }}
                                </button>
                                <!-- Optional: Button for "No Role" -->
                                <button
                                    type="button"
                                    @click="form.role = ''"
                                    :class="[
                                        'px-4 py-2 text-sm font-semibold rounded-full shadow-lg transition-all duration-200 transform',
                                        form.role === ''
                                            ? 'bg-gray-600 text-white scale-105 ring-2 ring-gray-600 ring-offset-2 ring-offset-gray-950'
                                            : 'bg-gray-800 text-gray-300 hover:bg-gray-700 hover:text-white',
                                        'focus:outline-none focus:ring-2 focus:ring-gray-600 focus:ring-offset-2 focus:ring-offset-gray-950'
                                    ]"
                                >
                                    No Role
                                </button>
                            </div>
                            <InputError class="mt-4" :message="form.errors.role" />
                        </div>
                        <div class="flex justify-end space-x-4">
                            <button type="button" @click="showRoleModal = false" class="inline-flex justify-center py-2 px-4 border border-gray-700 shadow-sm text-sm font-medium rounded-full text-gray-300 bg-gray-800 hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-600 focus:ring-offset-gray-950">
                                Cancel
                            </button>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="bg-orange-600 hover:bg-orange-700 rounded-full">
                                {{ form.processing ? 'Assigning...' : 'Assign Role' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
        
        <!-- We also use a Transition for the backdrop for a consistent feel -->
        <Transition name="fade-backdrop">
            <div v-if="showRoleModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40"></div>
        </Transition>
    </AuthenticatedLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');

.font-inter {
    font-family: 'Inter', sans-serif;
}

/* * The custom keyframe animation has been replaced by Vue's <Transition> component.
* We define classes for the modal's enter and leave states.
*/

/* Transition classes for the main modal content (named 'fade') */
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: scale(0.95);
}
.fade-enter-to,
.fade-leave-from {
    opacity: 1;
    transform: scale(1);
}

/* Transition classes for the backdrop (named 'fade-backdrop') */
.fade-backdrop-enter-active,
.fade-backdrop-leave-active {
    transition: opacity 0.3s ease;
}
.fade-backdrop-enter-from,
.fade-backdrop-leave-to {
    opacity: 0;
}
.fade-backdrop-enter-to,
.fade-backdrop-leave-from {
    opacity: 1;
}
</style>
