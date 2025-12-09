<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue'; // Import watch

import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    users: Array,
    availableRoles: Array,
});

const showRoleModal = ref(false);
const selectedUser = ref(null);
const form = useForm({
    role: '',
});

const openRoleModal = (user) => {
    selectedUser.value = user;
    // Note: This assumes a user can only have one role for simplicity in this UI
    form.role = user.roles.length > 0 ? user.roles[0] : null;
    showRoleModal.value = true;
};

const assignRole = () => {
    if (!selectedUser.value) return;
    form.post(route('users.assign-role', selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showRoleModal.value = false;
            selectedUser.value = null;
            form.reset('role');
        },
        onError: (errors) => {
            console.error("Error assigning role:", errors);
        },
    });
};

const search = ref('');
// 1. New ref for role filter
const selectedRoleFilter = ref('all'); // 'all' by default

// List of roles for the filter buttons, including 'all'
// MODIFICATION: Updated filterRoles array to only include your specified roles.
const filterRoles = ['all', 'admin', 'teacher', 'student', 'accounts', 'front-desk'];

// 2. Updated computed property for filtering
const filteredUsers = computed(() => {
    let usersToFilter = props.users;

    // Apply search filter
    if (search.value) {
        const searchTerm = search.value.toLowerCase();
        usersToFilter = usersToFilter.filter(user =>
            user.name.toLowerCase().includes(searchTerm) ||
            user.email.toLowerCase().includes(searchTerm)
        );
    }

    // Apply role filter
    if (selectedRoleFilter.value !== 'all') {
        usersToFilter = usersToFilter.filter(user =>
            user.roles.includes(selectedRoleFilter.value)
        );
    }

    return usersToFilter;
});

const displayedUsers = computed(() => filteredUsers.value);
const currentPage = ref(1);
const itemsPerPage = ref(10);
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

// 3. Watchers to reset pagination when filters/search change
watch(search, () => {
    currentPage.value = 1;
});

watch(selectedRoleFilter, () => {
    currentPage.value = 1;
});

// Watch itemsPerPage to adjust current page if needed
watch(itemsPerPage, () => {
    if (currentPage.value > totalPages.value) {
        currentPage.value = 1;
    }
});
</script>

<template>
    <Head title="User Management" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl sm:text-3xl text-gray-900 leading-tight tracking-tight">User Management Dashboard</h2>
        </template>
        <div class="py-8 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-gray-50 to-gray-200 min-h-screen font-sans">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white overflow-hidden shadow-2xl rounded-3xl p-8 border border-gray-200">
                    
                    <div class="flex flex-wrap gap-3 mb-8 justify-start">
                        <button
                            v-for="role in filterRoles"
                            :key="role"
                            @click="selectedRoleFilter = role"
                            :class="[
                                'py-2 px-5 text-sm font-medium rounded-full transition-all duration-300 shadow-md',
                                selectedRoleFilter === role
                                    ? 'bg-blue-600 text-white hover:bg-blue-700'
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                            ]"
                        >
                            {{ role.charAt(0).toUpperCase() + role.slice(1) }}
                        </button>
                    </div>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 space-y-4 sm:space-y-0 sm:space-x-6">
                        <select v-model="itemsPerPage" class="w-full sm:w-36 py-3 px-5 rounded-xl bg-white text-gray-900 border border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-base shadow-md transition-all duration-300 hover:shadow-lg">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <div class="relative w-full sm:flex-grow">
                            <input
                                type="text"
                                v-model="search"
                                placeholder="Search by name or email..."
                                class="w-full pl-12 pr-5 py-3 rounded-xl bg-white text-gray-900 placeholder-gray-400 border border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-base shadow-md transition-all duration-300 hover:shadow-lg"
                            >
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-md">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider">User</th>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider">Role</th>
                                    <th scope="col" class="px-6 py-4 text-left text-sm font-semibold text-gray-900 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="user in paginatedUsers" :key="user.id" class="hover:bg-blue-50/50 transition-all duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap" data-label="User">
                                        <div class="text-base font-medium text-gray-900 flex items-center">
                                            <svg class="h-6 w-6 text-blue-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <span class="truncate">{{ user.name }}</span>
                                        </div>
                                        <div class="text-sm text-gray-600 ml-9 truncate">{{ user.email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap" data-label="Role">
                                        <span v-for="role in user.roles" :key="role" class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium mr-2 mb-2 shadow-sm bg-blue-600 text-white">
                                            <span v-if="role === 'editor'" class="mr-1">✏️</span>
                                            <span v-else-if="role === 'student'" class="mr-1">🎓</span>
                                            <span v-else-if="role === 'teacher'" class="mr-1">🧑‍🏫</span>
                                            <span v-else-if="role === 'accounts'" class="mr-1">💰</span>
                                            <span v-else-if="role === 'front-desk'" class="mr-1">🛎️</span>
                                            <span v-else class="mr-1">🏷️</span>
                                            {{ role.charAt(0).toUpperCase() + role.slice(1) }}
                                        </span>
                                        <span v-if="user.roles.length === 0" class="text-gray-500 text-sm">No Role Assigned</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right" data-label="Actions">
                                        <div class="flex items-center justify-end space-x-3">
                                            <button @click="openRoleModal(user)" class="text-blue-600 hover:text-blue-800 transition-all duration-200">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="displayedUsers.length === 0">
                                    <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center">No users found matching your search or filter.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 bg-white px-4 py-4 rounded-b-xl mt-4">
                        <div class="flex flex-1 justify-between sm:hidden mb-4">
                            <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="relative inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-blue-50 disabled:opacity-50 transition-all duration-200">Previous</button>
                            <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" class="relative inline-flex items-center rounded-xl border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-blue-50 disabled:opacity-50 transition-all duration-200">Next</button>
                        </div>
                        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-semibold">{{ (currentPage - 1) * itemsPerPage + 1 }}</span>
                                    to
                                    <span class="font-semibold">{{ Math.min(currentPage * itemsPerPage, displayedUsers.length) }}</span>
                                    of
                                    <span class="font-semibold">{{ displayedUsers.length }}</span>
                                    entries
                                </p>
                            </div>
                            <div>
                                <nav class="isolate inline-flex -space-x-px rounded-xl shadow-md" aria-label="Pagination">
                                    <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1" class="relative inline-flex items-center rounded-l-xl px-3 py-2 text-gray-600 ring-1 ring-gray-300 hover:bg-blue-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 transition-all duration-200">
                                        <span class="sr-only">Previous</span>
                                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01-.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button v-for="page in totalPages" :key="page" @click="goToPage(page)" :aria-current="currentPage === page ? 'page' : undefined" :class="[
                                        'relative inline-flex items-center px-4 py-2 text-sm font-semibold focus:z-20 focus:outline-offset-0',
                                        currentPage === page ? 'z-10 bg-blue-600 text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600' : 'text-gray-700 ring-1 ring-gray-300 hover:bg-blue-50 hover:text-gray-900 transition-all duration-200'
                                    ]">
                                        {{ page }}
                                    </button>
                                    <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages" class="relative inline-flex items-center rounded-r-xl px-3 py-2 text-gray-600 ring-1 ring-gray-300 hover:bg-blue-50 focus:z-20 focus:outline-offset-0 disabled:opacity-50 transition-all duration-200">
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
        <Transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to-class="opacity-100 translate-y-0 sm:scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 translate-y-0 sm:scale-100"
            leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
        >
            <div v-if="showRoleModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 overflow-y-auto h-full w-full flex items-center justify-center z-50">
                <div class="relative p-8 border border-gray-200 w-full max-w-lg shadow-2xl rounded-2xl bg-white text-gray-900">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Assign Role to {{ selectedUser?.name }}</h3>
                    <form @submit.prevent="assignRole">
                        <div class="mb-6">
                            <InputLabel for="role" value="Select Role" class="text-gray-700 mb-2 text-base font-medium" />
                            <select
                                id="role"
                                v-model="form.role"
                                class="mt-1 block w-full pl-4 pr-10 py-3 text-base bg-white border border-gray-300 rounded-xl focus:outline-none focus:ring-blue-500 focus:border-blue-500 text-gray-900 shadow-md transition-all duration-300 hover:shadow-lg"
                            >
                                <option :value="null" class="bg-white text-gray-900">No Role</option>
                                <option v-for="roleName in availableRoles" :key="roleName" :value="roleName" class="bg-white text-gray-900">
                                    {{ roleName.charAt(0).toUpperCase() + roleName.slice(1) }}
                                </option>
                            </select>
                            <InputError class="mt-2 text-sm text-red-600" :message="form.errors.role" />
                        </div>
                        <div class="flex justify-end space-x-4">
                            <button type="button" @click="showRoleModal = false" class="inline-flex justify-center py-2 px-6 border border-gray-300 shadow-sm text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">Cancel</button>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-6 py-2 rounded-xl shadow-md transition-all duration-300 hover:shadow-lg">
                                {{ form.processing ? 'Assigning...' : 'Assign Role' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Existing styles remain the same */
.font-sans {
    font-family: 'Inter', sans-serif;
}

/* Mobile-specific adjustments */
@media (max-width: 639px) {
    .overflow-x-auto {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    table {
        display: block;
    }
    thead {
        display: none;
    }
    tbody, tr {
        display: block;
    }
    td {
        display: flex;
        flex-direction: column;
        padding: 1.5rem;
        border-bottom: 1px solid #e5e7eb;
    }
    td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.75rem;
        text-transform: uppercase;
        font-size: 0.875rem;
    }
    td:last-child {
        border-bottom: none;
    }
}

/* Tablet and up */
@media (min-width: 640px) {
    td::before {
        display: none;
    }
}

/* Modal adjustments */
@media (max-width: 640px) {
    .max-w-lg {
        max-width: 95%;
    }
    .p-8 {
        padding: 1.5rem;
    }
}

/* Hover effects and transitions */
button svg {
    transition: transform 0.3s ease;
}
button:hover svg {
    transform: scale(1.15);
}

/* Truncate styles */
.truncate {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>