<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';


const props = defineProps({
    books: Object, // Paginated books object
    filters: Object, // Current filter values (search, status)
    flash: Object, // Flash messages
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
    bookToDelete = ref(null);
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
</script>

<template>
    <Head title="Books" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Books</h2>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen font-sans">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                    <!-- Flash Message Display -->
                    <FlashMessage v-if="flash && flash.message" :flash="flash" class="mb-6" />

                    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4 md:mb-0">Book Inventory</h3>
                        <Link :href="route('books.create')" class="inline-flex items-center px-6 py-3 bg-indigo-600 border border-transparent rounded-full font-semibold text-sm text-white uppercase tracking-wider shadow-lg hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 transform hover:scale-105">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                            Add New Book
                        </Link>
                    </div>

                    <!-- Filter Section -->
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
                    <div class="overflow-x-auto rounded-lg shadow-md border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Author</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider hidden sm:table-cell">Publisher</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider hidden sm:table-cell">ISBN</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">Quantity</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider text-center">Available</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="books.data.length === 0">
                                    <td :colspan="8" class="px-6 py-10 whitespace-nowrap text-sm text-gray-500 text-center">
                                        No books found.
                                    </td>
                                </tr>
                                <tr v-else v-for="book in books.data" :key="book.id" class="hover:bg-gray-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ book.title }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ book.author }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden sm:table-cell">{{ book.publisher }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 hidden sm:table-cell">{{ book.isbn }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ book.quantity }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">{{ book.available_quantity }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span :class="{'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full text-white': true, 'bg-green-500': Number(book.status) === 0, 'bg-red-500': Number(book.status) === 1}">
                                            {{ Number(book.status) === 0 ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-2">
                                            <Link :href="route('books.edit', book.id)" class="p-2 text-indigo-600 rounded-full hover:bg-indigo-50 transition-colors duration-200" aria-label="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </Link>
                                            <DangerButton @click="openDeleteModal(book)" class="p-2 rounded-full hover:bg-red-50 transition-colors duration-200">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </DangerButton>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Links -->
                    <Pagination :links="books.links" class="mt-8" />
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full flex items-center justify-center z-50 transition-all duration-300">
            <div class="relative p-8 border w-full max-w-lg shadow-2xl rounded-2xl bg-white animate-modal-pop-in">
                <h3 class="text-2xl font-bold text-gray-900 mb-4 text-center">Confirm Deletion</h3>
                <p class="mb-6 text-gray-700 text-center">
                    Are you sure you want to permanently delete "<span class="font-semibold text-indigo-600">{{ bookToDelete ? bookToDelete.title : '' }}</span>"? This action cannot be undone.
                </p>
                <div class="flex justify-center space-x-4">
                    <SecondaryButton @click="closeDeleteModal" class="rounded-full">Cancel</SecondaryButton>
                    <DangerButton @click="deleteBook" class="rounded-full">Delete</DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No additional styles needed, everything is handled by TailwindCSS */
</style>
