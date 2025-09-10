<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
// Assuming these components also exist and are properly styled.
// import Pagination from '@/Components/Pagination.vue';
// import FlashMessage from '@/Components/FlashMessage.vue';


const props = defineProps({
    notices: Object, // Paginated notices data
    filters: Object, // Current filter values (search, status, target_user)
    availableRoles: Array, // Array of roles for the target_user filter dropdown
    flash: Object, // Flash messages
});

// Reactive filter state. Defaults status to "Active" (1) as requested.
const search = ref(props.filters.search || '');
const status = ref(props.filters.status !== undefined ? Number(props.filters.status) : 0);
const target_user = ref(props.filters.target_user || '');

// Watch for changes in filters and trigger Inertia visit
watch([search, status, target_user], ([newSearch, newStatus, newTargetUser]) => {
    router.get(route('notices.index'), {
        search: newSearch,
        status: newStatus,
        target_user: newTargetUser,
    }, {
        preserveState: true,
        replace: true,
    });
});

// Function to clear filters
const clearFilters = () => {
    search.value = '';
    status.value = '';
    target_user.value = '';
};

// Helper to get status string from integer (0: Active, 1: Inactive)
const getStatusString = (status) => {
    switch (status) {
        case 0: return 'Active';
        case 1: return 'Inactive';
        default: return 'Unknown';
    }
};

// Helper to determine status badge class based on integer status
const getStatusBadgeClass = (status) => {
    switch (status) {
        case 0: return 'bg-green-100 text-green-800'; // Corrected: 0 is Active, so use green badge
        case 1: return 'bg-gray-100 text-gray-800';  // Corrected: 1 is Inactive, so use gray badge
        default: return 'bg-gray-100 text-gray-800';
    }
};

// Helper to display target users from array
const displayTargetUsers = (usersArray) => {
    if (!usersArray || usersArray.length === 0) return 'N/A';
    return usersArray.map(user => user.charAt(0).toUpperCase() + user.slice(1)).join(', ');
};

// Helper: Function to format date range for display
const formatDateRange = (startDateString, endDateString) => {
    if (!startDateString || !endDateString) return 'N/A';
    const startDate = new Date(startDateString);
    const endDate = new Date(endDateString);

    const startFormatted = startDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    const endFormatted = endDate.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });

    if (startFormatted === endFormatted) {
        return startFormatted; // If dates are the same, show only one
    }

    return `${startFormatted} - ${endFormatted}`;
};


// Form for deletion
const deleteForm = useForm({});

// Reactive state for the custom delete modal
const showDeleteModal = ref(false);
const noticeToDelete = ref(null);

// Function to open the delete confirmation modal
const openDeleteModal = (notice) => {
    noticeToDelete.value = notice;
    showDeleteModal.value = true;
};

// Function to close the delete confirmation modal
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    noticeToDelete.value = null;
};

// Function to handle notice deletion
const deleteNotice = () => {
    if (noticeToDelete.value) {
        deleteForm.delete(route('notices.destroy', noticeToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
            },
            onError: (errors) => {
                console.error('Deletion failed:', errors);
            }
        });
    }
};
</script>

<template>
    <Head title="Notices Management" />

    <AuthenticatedLayout>
        <div class="py-12 bg-gray-100 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">

                    <!-- Page Header Section -->
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="font-bold text-2xl text-gray-900 leading-tight">Notices Management</h2>
                            <Link :href="route('notices.create')">
                                <PrimaryButton class="bg-indigo-600 hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 text-white transition ease-in-out duration-150">
                                    Add New Notice
                                </PrimaryButton>
                            </Link>
                        </div>
                    </div>

                    <!-- Flash Message Display -->
                    <div v-if="flash && flash.message" class="p-6">
                        <!-- Assumes a FlashMessage component exists with props `flash` -->
                        <div :class="[
                            'p-4 rounded-lg text-sm text-center font-medium',
                            flash.message.includes('success') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                        ]">
                            {{ flash.message }}
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="p-6 bg-gray-50 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Filter Notices</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                            <!-- Search Input -->
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                                <TextInput
                                    id="search"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50"
                                    v-model="search"
                                    placeholder="Search by title.."
                                />
                            </div>
                            <!-- Status Dropdown -->
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                <select
                                    id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50"
                                    v-model="status"
                                >
                                    <option value="">All Statuses</option>
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                            </div>
                            <!-- Target User Dropdown -->
                            <div>
                                <label for="target_user" class="block text-sm font-medium text-gray-700">Target User</label>
                                <select
                                    id="target_user"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50"
                                    v-model="target_user"
                                >
                                    <option value="">All Roles</option>
                                    <option v-for="role in availableRoles" :key="role" :value="role">
                                        {{ role.charAt(0).toUpperCase() + role.slice(1) }}
                                    </option>
                                </select>
                            </div>
                            <!-- Clear Filters Button -->
                            <div class="flex items-end">
                                <SecondaryButton @click="clearFilters" class="w-full">
                                    Clear Filters
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- Notices Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Title
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Message
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Date Range
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Target Users
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Created By
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="notices.data.length === 0">
                                    <td :colspan="7" class="px-6 py-10 whitespace-nowrap text-sm text-gray-500 text-center">
                                        No notices found.
                                    </td>
                                </tr>
                                <tr v-for="notice in notices.data" :key="notice.id" class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ notice.notice_title }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs overflow-hidden text-ellipsis">
                                        {{ notice.content.length > 100 ? notice.content.substring(0, 100) + '...' : notice.content }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDateRange(notice.start_date, notice.end_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusBadgeClass(notice.status)]">
                                            {{ getStatusString(notice.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ displayTargetUsers(notice.target_user) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ notice.creator ? notice.creator.name : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <Link :href="route('notices.edit', notice.id)" class="text-indigo-600 hover:text-indigo-900 mr-3 transition-colors duration-200">
                                            Edit
                                        </Link>
                                        <DangerButton @click="openDeleteModal(notice)">
                                            Delete
                                        </DangerButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links (assuming it's a separate component) -->
                    <div class="p-6 bg-white border-t border-gray-200">
                        <!-- <Pagination :links="notices.links" /> -->
                    </div>

                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-gray-900 opacity-75"></div>
            <div class="relative p-8 bg-white w-full max-w-md mx-auto rounded-lg shadow-2xl animate-fade-in-up">
                <h3 class="text-2xl font-bold text-gray-900 mb-4 text-center">Confirm Deletion</h3>
                <p class="text-gray-700 mb-6 text-center">
                    Are you sure you want to permanently delete the notice "<span class="font-semibold">{{ noticeToDelete ? noticeToDelete.notice_title : '' }}</span>"? This action cannot be undone.
                </p>
                <div class="flex justify-center gap-4">
                    <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
                    <DangerButton @click="deleteNotice">Delete</DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
