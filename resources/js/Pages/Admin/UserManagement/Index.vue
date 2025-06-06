<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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
            // as the put request implies a redirect back to the current page.
        },
        onError: (errors) => {
            console.error("Error assigning role:", errors);
        },
    });
};
</script>

<template>
    <Head title="User Management" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">User Management</h2>
        </template>

        <!-- Removed the redundant py-12 and inner max-w-7xl mx-auto divs
             as AuthenticatedLayout now provides the main padding and structure. -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Manage System Users</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roles</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="user in users" :key="user.id">
                                <td class="px-6 py-4 whitespace-nowrap">{{ user.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ user.email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ user.contact_info || 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-for="role in user.roles" :key="role" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 mr-1">
                                        {{ role }}
                                    </span>
                                    <span v-if="user.roles.length === 0" class="text-gray-500 text-xs">No Role</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button @click="openRoleModal(user)" class="text-indigo-600 hover:text-indigo-900">Edit Role</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Role Assignment Modal -->
        <div v-if="showRoleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
            <div class="relative p-5 border w-96 shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Role to {{ selectedUser?.name }}</h3>
                <form @submit.prevent="assignRole">
                    <div class="mb-4">
                        <InputLabel for="role" value="Select Role" />
                        <select
                            id="role"
                            v-model="form.role"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                        >
                            <option value="">No Role</option>
                            <option v-for="roleName in availableRoles" :key="roleName" :value="roleName">
                                {{ roleName.charAt(0).toUpperCase() + roleName.slice(1) }}
                            </option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.role" />
                    </div>
                    <div class="mt-4 flex justify-end">
                        <button type="button" @click="showRoleModal = false" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 mr-2">Cancel</button>
                        <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
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
</style>
