<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue'; // For future actions like 'Mark as Lost'


const props = defineProps({
    borrowRecords: Object, // Paginated borrow records object
    filters: Object, // Current filter values (search, status)
    flash: Object, // Flash messages
});

// Reactive filter state
const search = ref(props.filters.search || '');
const status = ref(props.filters.status !== undefined ? Number(props.filters.status) : ''); // Empty string for 'All Statuses'

// Watch for changes in filters and trigger Inertia visit
watch([search, status], ([newSearch, newStatus]) => {
    router.get(route('borrow-records.index'), {
        search: newSearch,
        status: newStatus,
    }, {
        preserveState: true,
        replace: true,
    });
});

// Function to clear filters
const clearFilters = () => {
    search.value = '';
    status.value = '';
};

// Helper to get status string from integer
const getStatusString = (status) => {
    switch (status) {
        case 0:
            return 'Borrowed';
        case 1:
            return 'Returned';
        case 2:
            return 'Overdue';
        case 3:
            return 'Lost';
        case 4:
            return 'Return Requested';
        default:
            return 'Unknown';
    }
};

// Helper to determine status badge class based on integer status
const getStatusBadgeClass = (status) => {
    switch (status) {
        case 0: // Borrowed
            return 'bg-yellow-100 text-yellow-800';
        case 1: // Returned
            return 'bg-green-100 text-green-800';
        case 2: // Overdue
            return 'bg-red-100 text-red-800';
        case 3: // Lost
            return 'bg-gray-100 text-gray-800';
        case 4: // Return Requested
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

// Helper to format date
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};

// Admin Action: Approve Return (for status 4 - Return Requested)
const approveReturn = (borrowRecordId) => {
    Swal.fire({
        title: 'Approve Return?',
        text: "Are you sure you want to approve this book return? This will mark the book as returned and increase its available quantity.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Approve!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('borrow-records.approve-return', borrowRecordId), {}, {
                onSuccess: () => {
                    // Flash message will be handled by watchEffect in AuthenticatedLayout
                },
                onError: (errors) => {
                    console.error("Approve return failed:", errors);
                    Swal.fire('Error!', 'Failed to approve return.', 'error');
                }
            });
        }
    });
};

// Admin Action: Mark as Lost (for status 0 - Borrowed)
const markAsLost = (borrowRecordId) => {
    Swal.fire({
        title: 'Mark as Lost?',
        text: "Are you sure you want to mark this book as lost? This will update its status and decrement available quantity.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, Mark as Lost!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('borrow-records.mark-lost', borrowRecordId), {}, {
                onSuccess: () => {
                    // Flash message will be handled by watchEffect in AuthenticatedLayout
                },
                onError: (errors) => {
                    console.error("Mark as lost failed:", errors);
                    Swal.fire('Error!', 'Failed to mark book as lost.', 'error');
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Borrow Records Management" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Borrow Records Management</h2>
        </template>

        <div class="py-12">
            <div class="max-w-full mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                    <!-- Flash Message Display (if you have a global flash message component in layout) -->
                    <!-- <FlashMessage v-if="flash && flash.message" :flash="flash" class="mb-4" /> -->

                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">All Borrow Records</h3>
                    </div>

                    <!-- Filter Section -->
                    <div class="mb-6 bg-gray-50 p-4 rounded-md shadow-inner">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                                <TextInput
                                    id="search"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    v-model="search"
                                    placeholder="Search by book title or student name..."
                                />
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                <select
                                    id="status"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                    v-model="status"
                                >
                                    <option value="">All Statuses</option>
                                    <option :value="0">Borrowed</option>
                                    <option :value="1">Returned</option>
                                    <option :value="2">Overdue</option>
                                    <option :value="3">Lost</option>
                                    <option :value="4">Return Requested</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <SecondaryButton @click="clearFilters">
                                    Clear Filters
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- Borrow Records Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrowed Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="borrowRecords.data.length === 0">
                                    <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                        No borrow records found.
                                    </td>
                                </tr>
                                <tr v-for="record in borrowRecords.data" :key="record.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ record.book?.title || 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ record.student?.user?.name || 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(record.borrow_date) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(record.return_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusBadgeClass(record.status)]">
                                            {{ getStatusString(record.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <!-- Actions for Admin -->
                                        <PrimaryButton
                                            v-if="record.status === 4"
                                            @click="approveReturn(record.id)"
                                            class="mr-2"
                                        >
                                            Approve Return
                                        </PrimaryButton>
                                        <DangerButton
                                            v-if="record.status === 0"
                                            @click="markAsLost(record.id)"
                                        >
                                            Mark as Lost
                                        </DangerButton>
                                        <!-- Add more admin actions here as needed -->
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links -->
                    <Pagination :links="borrowRecords.links" class="mt-6" />

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Add custom styles if needed */
.badge { /* Basic badge styling for custom classes */
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.25em 0.4em;
    font-size: 75%;
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;
    border-radius: 0.375rem; /* Equivalent to rounded-md */
}
</style>
