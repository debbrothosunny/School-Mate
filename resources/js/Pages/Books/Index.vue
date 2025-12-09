<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, watch, computed, watchEffect } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

import Swal from 'sweetalert2';

const props = defineProps({
    books: Object, // Paginated books object
    filters: Object, // Current filter values (search, status)
    flash: Object, // Flash messages
});

// Access flash messages from page props
const page = usePage();
const flash = computed(() => ({
    message: page.props.message || props.flash?.message || '',
    type: page.props.type || props.flash?.type || '',
}));

// Watch flash and show SweetAlert2 toast when message exists
watchEffect(() => {
    if (flash.value.message) {
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
    }
});

// Reactive filter state
const search = ref(props.filters.search || '');
const status = ref(props.filters.status !== undefined ? Number(props.filters.status) : 0);

// Watch for changes in filters and trigger Inertia visit
watch([search, status], ([newSearch, newStatus]) => {
    router.get(route('books.index'), {
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
    status.value = 0;
};

// Form for deletion
const deleteForm = useForm({});

// Reactive state for the custom delete modal
const showDeleteModal = ref(false);
const bookToDelete = ref(null);

// Function to open the delete confirmation modal
const openDeleteModal = (book) => {
    bookToDelete.value = book;
    showDeleteModal.value = true;
};

// Function to close the delete confirmation modal
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    bookToDelete.value = null;
};

// Function to handle book deletion
const deleteBook = () => {
    if (bookToDelete.value) {
        deleteForm.delete(route('books.destroy', bookToDelete.value.id), {
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

// --- Inline Borrow Logic ---
const activeBorrowBookId = ref(null);

// Utility to get default due date (7 days from now)
const getDefaultDueDate = () => {
    const defaultDueDate = new Date();
    defaultDueDate.setDate(defaultDueDate.getDate() + 7);
    return defaultDueDate.toISOString().slice(0, 10);
};

// Inertia Form for submission
const borrowForm = useForm({
    student_name: '',
    admission_number: '',
    class_name: '',
    quantity: 1,
    due_date: getDefaultDueDate(),
});

// Toggle the form visibility
const toggleBorrowForm = (bookId) => {
    if (activeBorrowBookId.value === bookId) {
        activeBorrowBookId.value = null;
    } else {
        borrowForm.reset();
        borrowForm.clearErrors();
        borrowForm.quantity = 1;
        borrowForm.due_date = getDefaultDueDate();
        activeBorrowBookId.value = bookId;
    }
};

// Handle the form submission
const submitBorrow = (book) => {
    if (borrowForm.quantity <= 0 || borrowForm.quantity > book.available_quantity) {
        borrowForm.setError('quantity', 'Invalid quantity. Max available: ' + book.available_quantity);
        return;
    }

    borrowForm.post(route('books.borrow.store', book.id), {
        preserveScroll: true,
        onSuccess: () => {
            activeBorrowBookId.value = null;
        },
        onError: (errors) => {
            console.error('Borrow failed:', errors);
        },
    });
};
</script>

<template>
    <Head title="Books" />
    <AuthenticatedLayout>
        <div class="py-12 bg-gray-50 min-h-screen font-sans">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <!-- Flash Message -->
                    <FlashMessage v-if="flash && flash.message" :flash="flash" class="mb-6" />

                    <!-- Header and Actions -->
                    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4 md:mb-0">Book Inventory</h3>
                        <div class="flex space-x-4">
                            <Link :href="route('borrow-records.index')" class="inline-flex items-center px-6 py-3 bg-blue-600 border border-transparent rounded-full font-semibold text-sm text-white uppercase tracking-wider shadow-lg hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-300 transform hover:scale-105">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h.01M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                Borrow Records
                            </Link>
                            <Link :href="route('books.create')" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-full font-semibold text-sm text-white uppercase tracking-wider shadow-lg hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 transform hover:scale-105">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                                Add New Book
                            </Link>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="mb-8 bg-gray-100 p-6 rounded-lg border border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-end">
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <div class="relative">
                                    <TextInput
                                        id="search"
                                        type="text"
                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50"
                                        v-model="search"
                                        placeholder="Title, author, ISBN..."
                                    />
                                    <svg class="w-4 h-4 text-gray-400 absolute right-3 top-1/2 -mt-2.5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path></svg>
                                </div>
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select
                                    id="status"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50"
                                    v-model="status"
                                >
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                            </div>
                            <div>
                                <SecondaryButton @click="clearFilters" class="w-full justify-center">
                                    Clear Filters
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- Books Table -->
                    <div class="rounded-lg shadow-md border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 table-fixed">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="w-2/12 px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                                    <th scope="col" class="w-2/12 px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Author</th>
                                    <th scope="col" class="w-2/12 px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider hidden md:table-cell">Publisher</th>
                                    <th scope="col" class="w-2/12 px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider hidden md:table-cell">ISBN</th>
                                    <th scope="col" class="w-1/12 px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Quantity</th>
                                    <th scope="col" class="w-1/12 px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Available</th>
                                    <th scope="col" class="w-1/12 px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="w-2/12 px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="books.data.length === 0">
                                    <td colspan="8" class="px-4 py-10 text-sm text-gray-500 text-center">
                                        No books found.
                                    </td>
                                </tr>
                                <template v-else v-for="book in books.data" :key="book.id">
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-4 py-4 text-sm font-medium text-gray-900 truncate">{{ book.title }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-500 truncate">{{ book.author }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-500 hidden md:table-cell truncate">{{ book.publisher }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-500 hidden md:table-cell truncate">{{ book.isbn }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-500 text-center">{{ book.quantity }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-500 text-center">{{ book.available_quantity }}</td>
                                        <td class="px-4 py-4 text-sm">
                                            <span :class="{'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full text-white': true, 'bg-green-500': Number(book.status) === 0, 'bg-red-500': Number(book.status) === 1}">
                                                {{ Number(book.status) === 0 ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-sm font-medium text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <button
                                                    @click="toggleBorrowForm(book.id)"
                                                    :disabled="Number(book.status) !== 0 || book.available_quantity <= 0"
                                                    :class="[
                                                        'px-3 py-1.5 text-xs font-semibold rounded-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed',
                                                        activeBorrowBookId === book.id
                                                            ? 'bg-red-500 text-white hover:bg-red-600'
                                                            : 'bg-green-600 text-white hover:bg-green-700'
                                                    ]"
                                                    title="Issue Book to Student"
                                                >
                                                    {{ activeBorrowBookId === book.id ? 'Cancel' : 'Borrow' }}
                                                </button>
                                                <Link :href="route('books.edit', book.id)" class="p-2 text-indigo-600 rounded-full hover:bg-indigo-50 transition-colors duration-200" aria-label="Edit">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                </Link>
                                                <DangerButton @click="openDeleteModal(book)" class="p-2 rounded-full hover:bg-red-50 transition-colors duration-200">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </DangerButton>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="activeBorrowBookId === book.id" class="bg-indigo-50 border-t border-indigo-200">
                                        <td colspan="8" class="p-4 sm:p-6">
                                            <h4 class="font-semibold text-indigo-700 mb-4 text-lg">Issue Book: {{ book.title }}</h4>
                                            <form @submit.prevent="submitBorrow(book)" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-start">
                                                <div class="md:col-span-2">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Student Name</label>
                                                    <TextInput v-model="borrowForm.student_name" type="text" required class="w-full text-sm" placeholder="Student Name" />
                                                    <p v-if="borrowForm.errors.student_name" class="text-red-600 mt-1 text-xs">{{ borrowForm.errors.student_name }}</p>
                                                </div>
                                                <div class="md:col-span-1">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Admission No.</label>
                                                    <TextInput v-model="borrowForm.admission_number" type="text" required class="w-full text-sm" placeholder="Adm. No." />
                                                    <p v-if="borrowForm.errors.admission_number" class="text-red-600 mt-1 text-xs">{{ borrowForm.errors.admission_number }}</p>
                                                </div>
                                                <div class="md:col-span-1">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Class Name</label>
                                                    <TextInput v-model="borrowForm.class_name" type="text" required class="w-full text-sm" placeholder="Class" />
                                                    <p v-if="borrowForm.errors.class_name" class="text-red-600 mt-1 text-xs">{{ borrowForm.errors.class_name }}</p>
                                                </div>
                                                <div class="md:col-span-1">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Qty (Max {{ book.available_quantity }})</label>
                                                    <TextInput v-model.number="borrowForm.quantity" type="number" required :min="1" :max="book.available_quantity" class="w-full text-sm" />
                                                    <p v-if="borrowForm.errors.quantity" class="text-red-600 mt-1 text-xs">{{ borrowForm.errors.quantity }}</p>
                                                </div>
                                                <div class="md:col-span-1">
                                                    <label class="block text-xs font-medium text-gray-700 mb-1">Due Date</label>
                                                    <TextInput v-model="borrowForm.due_date" type="date" required class="w-full text-sm" />
                                                    <p v-if="borrowForm.errors.due_date" class="text-red-600 mt-1 text-xs">{{ borrowForm.errors.due_date }}</p>
                                                </div>
                                                <div class="md:col-span-6 flex justify-end">
                                                    <PrimaryButton type="submit" :disabled="borrowForm.processing || borrowForm.quantity > book.available_quantity" class="px-6 py-2">
                                                        {{ borrowForm.processing ? 'Issuing...' : 'Confirm Issue' }}
                                                    </PrimaryButton>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <Pagination :links="books.links" class="mt-8" />

                    <!-- Delete Confirmation Modal -->
                    <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-75 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4">
                        <div class="bg-white rounded-lg p-6 w-full max-w-sm shadow-2xl">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Confirm Deletion</h3>
                            <p class="text-sm text-gray-600 mb-6">Are you sure you want to delete the book: <span class="font-semibold">{{ bookToDelete?.title }}</span>? This action cannot be undone.</p>
                            <div class="flex justify-end space-x-3">
                                <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
                                <DangerButton @click="deleteBook" :disabled="deleteForm.processing">
                                    {{ deleteForm.processing ? 'Deleting...' : 'Delete' }}
                                </DangerButton>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-6 pt-6 border-t border-gray-300 text-center bg-gray-100">
                        <p class="text-base md:text-lg text-black font-semibold leading-relaxed mx-auto" style="display:inline-block; white-space:nowrap;">
                            © All Rights Reserved. Biddaloy is a product of
                            <a href="https://smithitbd.com/" target="_blank" class="font-semibold text-red-600 hover:text-red-700 transition-colors hover:underline">
                                Smith&nbsp;IT
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div> <!-- <-- THIS WAS THE MISSING CLOSING TAG -->
    </AuthenticatedLayout>
</template>

<style scoped>
/* Ensure table cells wrap text on small screens */
td.truncate {
    max-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
@media (max-width: 768px) {
    td.truncate {
        white-space: normal;
        word-break: break-word;
    }
}
</style>
