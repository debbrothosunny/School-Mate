<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, Link } from '@inertiajs/vue3'; // <-- Added Link here
import { ref, watch } from 'vue';
// Assuming you have these components globally available or imported
// import FlashMessage from '@/Components/FlashMessage.vue';
// import Pagination from '@/Components/Pagination.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    borrows: Object, // Paginated borrow records (includes 'book' relationship)
    filters: Object, // { status: 0 }
    currentDate: String, // Today's date string for comparison
    flash: Object, // Flash messages
});

// Reactive filter state
const status = ref(props.filters.status !== undefined ? Number(props.filters.status) : 0);

// Watch for changes in status filter and trigger Inertia visit
watch(status, (newStatus) => {
    router.get(route('borrows.index'), {
        status: newStatus,
    }, {
        preserveState: true,
        replace: true,
    });
});

// Form for handling the return action
const returnForm = useForm({});

// Function to handle the book return
const handleReturn = (borrow) => {
    // Replaced standard confirm() with a custom confirmation for better UI in a real app
    // Assuming a custom modal or library-provided confirm is used, but keeping window.confirm for now
    if (!confirm(`Are you sure you want to mark "${borrow.book.title}" borrowed by ${borrow.student_name} as returned?`)) {
        return;
    }

    // Use the PUT route defined for returning a book
    returnForm.put(route('borrows.return', borrow.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Inertia will automatically reload the page (re-fetch data)
        },
        onError: (errors) => {
            console.error('Return failed:', errors);
        }
    });
};

// Utility to check if a book is overdue
const isOverdue = (borrow) => {
    // Only check if it hasn't been returned yet
    return borrow.returned_at === null && borrow.due_date < props.currentDate;
};
</script>

<template>
    <Head title="Borrow Records" />

    <AuthenticatedLayout>
        <div class="py-12 bg-gray-50 min-h-screen font-sans">
            <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">

                    <!-- Assuming FlashMessage component exists -->
                    <!-- <FlashMessage v-if="flash && flash.message" :flash="flash" class="mb-6" /> -->

                    <div class="flex justify-between items-center mb-8 border-b pb-4">
                        <h3 class="text-2xl font-extrabold text-gray-900 tracking-tight">Borrow & Return Records</h3>
                        
                        <!-- NEW BACK BUTTON ADDED HERE -->
                        <Link :href="route('books.index')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest shadow-md transition duration-150 ease-in-out transform hover:scale-[1.02] hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4 mr-2">
                                <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.09 1.05l-3.328 3.32a.75.75 0 0 0-.013 1.05l3.336 3.337a.75.75 0 1 1-1.06 1.06l-3.337-3.336a.75.75 0 0 1 0-1.06l3.337-3.336a.75.75 0 0 1 1.05-.09Z" clip-rule="evenodd" />
                            </svg>
                            Back to Book List
                        </Link>
                    </div>

                    <div class="mb-8 bg-gray-100 p-6 rounded-xl border border-gray-200 shadow-inner w-full md:w-1/3">
                        <label for="status-filter" class="block text-sm font-semibold text-gray-700 mb-2">Filter by Status</label>
                        <select
                            id="status-filter"
                            class="block w-full rounded-lg border-gray-300 shadow-sm transition duration-150 ease-in-out focus:border-indigo-500 focus:ring focus:ring-indigo-500 focus:ring-opacity-50"
                            v-model="status"
                        >
                            <option :value="0">Active (Not Returned)</option>
                            <option :value="1">Returned (History)</option>
                            <option :value="2">All Records</option>
                        </select>
                    </div>
                    
                    <div class="overflow-x-auto rounded-xl shadow-2xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-indigo-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Book Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Student Details</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider text-center">Qty</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Return Date</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider min-w-[150px]">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="borrows.data.length === 0">
                                    <td :colspan="6" class="px-6 py-10 whitespace-nowrap text-sm text-gray-500 text-center">
                                        No borrow records found for the selected status.
                                    </td>
                                </tr>
                                <tr v-else v-for="borrow in borrows.data" :key="borrow.id" :class="{'bg-red-50 hover:bg-red-100 transition-colors': isOverdue(borrow)}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ borrow.book.title }}
                                        <p class="text-xs text-gray-500 mt-1 italic">ISBN: {{ borrow.book.isbn }}</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <p class="font-semibold text-gray-700">{{ borrow.student_name }}</p>
                                        <p class="text-xs">Adm. No: {{ borrow.admission_number }} ({{ borrow.class_name }})</p>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center font-bold">
                                        {{ borrow.quantity }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm" :class="{'font-extrabold text-red-600': isOverdue(borrow)}">
                                        {{ borrow.due_date }}
                                        <span v-if="isOverdue(borrow)" class="ml-2 px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full bg-red-200 text-red-800 shadow-sm animate-pulse">
                                            OVERDUE!
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span v-if="borrow.returned_at" class="text-green-600 font-medium">
                                            {{ new Date(borrow.returned_at).toLocaleDateString() }}
                                        </span>
                                        <span v-else class="text-yellow-600 font-medium italic">
                                            Pending
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                        <PrimaryButton 
                                            v-if="!borrow.returned_at" 
                                            @click="handleReturn(borrow)" 
                                            :disabled="returnForm.processing"
                                            class="px-4 py-2 text-xs transition duration-150 ease-in-out"
                                        >
                                            <span v-if="returnForm.processing">
                                                <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Processing...
                                            </span>
                                            <span v-else>Mark as Returned</span>
                                        </PrimaryButton>
                                        <span v-else class="text-gray-500 text-xs italic">
                                            Completed
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Assuming Pagination component exists -->
                    <!-- <Pagination :links="borrows.links" class="mt-8" /> -->
                </div>
                
                <div class="mt-6 pt-6 border-t border-gray-300 text-center bg-gray-100 rounded-lg p-4 shadow-inner">
                    <p class="text-sm md:text-base text-gray-600 font-medium leading-relaxed mx-auto" style="display:inline-block; white-space:nowrap;">
                        © All Rights Reserved. Biddaloy is a product of
                        <a href="https://smithitbd.com/" target="_blank" class="font-bold text-indigo-600 hover:text-indigo-700 transition-colors hover:underline">
                            Smith&nbsp;IT
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
