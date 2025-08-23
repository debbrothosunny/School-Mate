<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3'; // Added 'router' import
import { ref, watch, computed, watchEffect } from 'vue';


const props = defineProps({
    borrowedBooks: Object, // Paginated borrow records object
    flash: Object,
});

// --- NEW DEBUGGING LOGS ---
console.log('MyBorrowedBooks.vue: All props received:', props);
console.log('MyBorrowedBooks.vue: Value of props.borrowedBooks:', props.borrowedBooks);
console.log('MyBorrowedBooks.vue: Value of props.borrowedBooks?.data?.length:', props.borrowedBooks?.data?.length);
// --- END NEW DEBUGGING LOGS ---


const flash = computed(() => usePage().props.flash || {});

watchEffect(() => {
    if (flash.value && flash.value.message) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: flash.value.type === 'success' ? 'success' : 'error',
                title: flash.value.type === 'success' ? 'Success!' : 'Error!',
                text: flash.value.message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        } else {
            console.warn('Swal (SweetAlert2) is not defined. Flash messages will not be displayed via Swal.');
            alert(flash.value.message);
        }
    }
});

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
        case 4: // New status for return requested
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
            return 'bg-blue-100 text-blue-800'; // Or any other color you prefer
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

// Function to handle return request
const requestReturn = (borrowBookId) => {
    Swal.fire({
        title: 'Confirm Return Request',
        text: "Are you sure you want to request to return this book? An admin will review your request.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Request Return!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('student.books.request-return', borrowBookId), {}, {
                onSuccess: () => {
                    // Flash message will be handled by watchEffect
                },
                onError: (errors) => {
                    console.error("Return request failed:", errors);
                    Swal.fire('Error!', 'Failed to submit return request.', 'error');
                }
            });
        }
    });
};
</script>

<template>
    <Head title="My Borrowed Books" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Borrowed Books</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Your Borrowing History</h3>
                    </div>

                    <!-- Condition for displaying "No books yet" message -->
                    <div v-if="!borrowedBooks || !borrowedBooks.data || borrowedBooks.data.length === 0" class="text-center py-4 text-gray-500">
                        You have not borrowed any books yet.
                    </div>

                    <div v-else class="overflow-x-auto">
                        {{ console.log('MyBorrowedBooks.vue: Rendering table with data. Data length:', borrowedBooks.data.length) }}
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Borrowed Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Return Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="record in borrowedBooks.data" :key="record.id">
                                    {{ console.log('MyBorrowedBooks.vue: Rendering record:', record) }}
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ record.book?.title || 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ record.book?.author || 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatDate(record.borrow_date) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <!-- Display return_date, which now serves as both expected and actual -->
                                        {{ formatDate(record.return_date) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', getStatusBadgeClass(record.status)]">
                                            {{ getStatusString(record.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            v-if="record.status === 0"
                                            @click="requestReturn(record.id)"
                                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150"
                                        >
                                            Request Return
                                        </button>
                                        <span v-else-if="record.status === 4" class="text-gray-500">
                                            Pending Admin Approval
                                        </span>
                                        <span v-else-if="record.status === 1" class="text-green-600">
                                            Returned
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links -->
                    <Pagination v-if="borrowedBooks && borrowedBooks.links && borrowedBooks.links.length > 3" :links="borrowedBooks.links" class="mt-6" />
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
