<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch, computed, watchEffect } from 'vue';
import { router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue'; // Re-using DangerButton for visual consistency, though it's a borrow action

import InputError from '@/Components/InputError.vue'; // Ensure InputError is imported if used
import InputLabel from '@/Components/InputLabel.vue'; // Ensure InputLabel is imported for consistency

const props = defineProps({
    books: Object, // Paginated books object
    flash: Object, // Flash messages
});

// Computed property for flash messages, similar to other pages
const flash = computed(() => usePage().props.flash || {});

// Reactive state for the custom borrow modal
const showBorrowModal = ref(false);
const bookToBorrow = ref(null);

// Form for borrowing
const borrowForm = useForm({
    book_id: null,
    borrow_date: new Date().toISOString().slice(0, 10), // Default to today
    return_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10), // Default expected return date to 7 days from now
});

// Function to open the borrow confirmation modal
const openBorrowModal = (book) => {
    bookToBorrow.value = book;
    borrowForm.book_id = book.id;
    // Reset dates to current for each new borrow attempt
    borrowForm.borrow_date = new Date().toISOString().slice(0, 10);
    borrowForm.return_date = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10); // Reset expected return date too
    showBorrowModal.value = true;
};

// Function to close the borrow confirmation modal
const closeBorrowModal = () => {
    showBorrowModal.value = false;
    bookToBorrow.value = null; // Clear the book data
    borrowForm.reset(); // Reset the form fields
};

// Function to handle book borrowing
const submitBorrow = () => {
    borrowForm.post(route('student.books.borrow'), {
        onSuccess: () => {
            closeBorrowModal();
            // Flash message will be handled by the watchEffect
        },
        onError: (errors) => {
            console.error('Borrow submission failed. Errors:', errors); // Log the specific errors
            // SweetAlert for general error, specific errors will show under fields via InputError
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Borrow Failed!',
                    text: 'Please check the form for specific errors.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 9000,
                    timerProgressBar: true,
                });
            } else {
                console.warn('Swal (SweetAlert2) is not defined. General error message will not be displayed via Swal.');
                alert('Borrow failed. Check console for details.');
            }
        },
    });
};

// Watch for flash messages and display SweetAlert
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

// Helper to format date for display
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
};
</script>

<template>
    <Head title="Available Books" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Available Books</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                    <!-- Flash Message Display -->
                    <FlashMessage v-if="flash && flash.message" class="mb-4" />

                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Book List for Students</h3>
                    </div>

                    <!-- Books Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Cover
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Title
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Author
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Publisher
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Publication Date
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ISBN
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Available Quantity
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Genre
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="books.data.length === 0">
                                    <td :colspan="9" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                        No active books found.
                                    </td>
                                </tr>
                                <tr v-for="book in books.data" :key="book.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <img v-if="book.cover_image_path" :src="`/storage/${book.cover_image_path}`" alt="Cover" class="h-12 w-auto object-cover rounded-md" />
                                        <span v-else>N/A</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ book.title }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ book.author }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ book.publisher }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ book.publication_date }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ book.isbn }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ book.available_quantity }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ book.genre }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <PrimaryButton
                                            @click="openBorrowModal(book)"
                                            :disabled="book.available_quantity <= 0"
                                            :class="{'opacity-50 cursor-not-allowed': book.available_quantity <= 0}"
                                        >
                                            {{ book.available_quantity <= 0 ? 'Out of Stock' : 'Borrow' }}
                                        </PrimaryButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links -->
                    <Pagination :links="books.links" class="mt-6" />

                </div>
            </div>
        </div>

        <!-- Borrow Confirmation Modal -->
        <div v-if="showBorrowModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50">
            <div class="relative p-5 border w-96 shadow-lg rounded-md bg-white">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Borrow</h3>
                <p class="mb-4">
                    You are about to borrow "{{ bookToBorrow ? bookToBorrow.title : '' }}".
                    <br>
                    Please confirm the borrow and expected return dates:
                </p>
                <form @submit.prevent="submitBorrow" class="space-y-4">
                    <div>
                        <InputLabel for="borrow_date" value="Borrow Date" />
                        <input
                            type="date"
                            id="borrow_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            v-model="borrowForm.borrow_date"
                            required
                        />
                        <InputError class="mt-2" :message="borrowForm.errors.borrow_date" />
                    </div>
                    <div>
                        <InputLabel for="return_date" value="Expected Return Date" />
                        <input
                            type="date"
                            id="return_date"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            v-model="borrowForm.return_date"
                            :min="borrowForm.borrow_date"
                            required
                        />
                        <InputError class="mt-2" :message="borrowForm.errors.return_date" />
                    </div>

                    <div class="mt-4 flex justify-end">
                        <button type="button" @click="closeBorrowModal" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-gray-700 bg-gray-200 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 mr-2">Cancel</button>
                        <PrimaryButton :class="{ 'opacity-25': borrowForm.processing }" :disabled="borrowForm.processing">
                            Confirm Borrow
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
