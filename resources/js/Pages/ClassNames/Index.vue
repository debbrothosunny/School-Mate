<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed, watch, watchEffect } from 'vue'; // Added 'watch' and 'router' import
import TextInput from '@/Components/TextInput.vue';
import Swal from 'sweetalert2';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';


const props = defineProps({
    // Changed prop name to match controller output 'classNames'
    classNames: {
        type: Object,
        required: true,
    },
    // Added prop for search value passed back from the server
    currentSearch: {
        type: String,
        default: '',
    },
});

const form = useForm({});
const showDeleteModal = ref(false);
const classToDelete = ref(null);
const flash = computed(() => usePage().props.flash || {});

// Initialize search query with the value from the server
const searchQuery = ref(props.currentSearch); 
const classes = computed(() => props.classNames);

// --- SERVER-SIDE SEARCH LOGIC ---
let searchTimeout = null;

const searchClasses = () => {
    // Clear the timeout to prevent duplicate requests
    clearTimeout(searchTimeout);

    // Perform a GET request to the same URL, updating the 'search' query parameter
    // The server will handle filtering and repaginating the results.
    router.get(route('class-names.index'),
        { search: searchQuery.value },
        {
            preserveState: true, // Keep the search input value in the URL/form
            replace: true,       // Replace the current history state
        }
    );
};

watch(searchQuery, (newQuery) => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        searchClasses();
    }, 300); // 300ms debounce
});
// --- END SERVER-SIDE SEARCH LOGIC ---


// Computed properties now reference the server-paginated data properties
const displayTotal = computed(() => classes.value.total || 0);
const displayFrom = computed(() => classes.value.from || 0);
const displayTo = computed(() => classes.value.to || 0);

const getStatusText = (status) => (status === 0 ? 'Active' : 'Inactive');

const confirmClassDeletion = (classItem) => {
    classToDelete.value = classItem;
    showDeleteModal.value = true;
};
const deleteClass = () => {
    if (classToDelete.value) {
        form.delete(route('class-names.destroy', classToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
            },
            onError: (errors) => {
                console.error("Error deleting class:", errors);
                closeDeleteModal();
            },
        });
    }
};
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    classToDelete.value = null;
};
watchEffect(() => {
    if (flash.value.message) {
        Swal.fire({
            icon: flash.value.type === 'success' ? 'success' : 'error',
            title: flash.value.type === 'success' ? 'Success!' : 'Error!',
            text: flash.value.message,
            toast: true,
            position: 'top-end',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false,
        });
    }
});
</script>

<template>
    <Head title="Class Management" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white tracking-tight">
                Class Management
            </h2>
        </template>

        <div class="py-8 sm:py-12 min-h-screen bg-gradient-to-br from-gray-50 via-white to-teal-50 dark:from-gray-950 dark:via-gray-900 dark:to-teal-950/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

             

                <div class="bg-white/80 dark:bg-gray-900/90 backdrop-blur-xl rounded-2xl shadow-xl border border-gray-200/50 dark:border-gray-800 overflow-hidden">
                    <div class="p-6 lg:p-8">

                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 mb-8">
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">All Classes</h3>
                            <Link :href="route('class-names.create')">
                                <PrimaryButton class="bg-teal-600 hover:bg-teal-700 text-white font-medium shadow-lg shadow-teal-600/30 hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add New Class
                                </PrimaryButton>
                            </Link>
                        </div>

                        <div class="relative mb-8">
                            <TextInput
                                v-model="searchQuery"
                                placeholder="Search by class name or status..." class="pl-14 pr-6 py-4 rounded-xl border-gray-300 dark:border-gray-700 bg-white/70 dark:bg-gray-800/70 backdrop-blur focus:ring-2 focus:ring-teal-500 focus:border-teal-500 text-base shadow-inner w-full"
                            />
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                <svg class="w-7 h-7 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <div v-for="(classItem, index) in classes.data" :key="classItem.id"
                            class="sm:hidden bg-white dark:bg-gray-800/80 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-5 mb-4 hover:shadow-lg transition-all duration-300">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <div class="text-sm text-gray-500">Class #{{ classes.from + index }}</div>
                                    <h4 class="text-lg font-bold text-gray-900 dark:text-white mt-1">{{ classItem.class_name }}</h4>
                                </div>
                                <span :class="classItem.status === 0
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300'
                                    : 'bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300'"
                                    class="px-3 py-1 rounded-full text-xs font-medium">
                                    {{ getStatusText(classItem.status) }}
                                </span>
                            </div>

                            <div class="flex justify-end gap-3">
                                <Link :href="route('class-names.edit', classItem.id)"
                                    class="p-3 bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/70 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"/>
                                    </svg>
                                </Link>
                            </div>
                        </div>

                        <div class="hidden sm:block overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50/70 dark:bg-gray-800/70 backdrop-blur">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">S/N</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Class Name</th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white/70 dark:bg-gray-900/70 divide-y divide-gray-200 dark:divide-gray-800">
                                    <tr v-if="!classes.data.length">
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No classes found.</td> 
                                    </tr>
                                    <tr v-for="(classItem, index) in classes.data" :key="classItem.id"
                                        class="hover:bg-teal-50/50 dark:hover:bg-teal-900/20 transition">
                                        <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                            {{ classes.from + index }} 
                                        </td>
                                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ classItem.class_name }}</td>
                                        <td class="px-6 py-4">
                                            <span :class="classItem.status === 0
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300'
                                                : 'bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300'"
                                                class="px-3 py-1 rounded-full text-xs font-medium">
                                                {{ getStatusText(classItem.status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right space-x-3">
                                            <Link :href="route('class-names.edit', classItem.id)"
                                                class="inline-flex p-2.5 bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/70 transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"/>
                                                </svg>
                                            </Link>
                                            
                                            <button 
                                                disabled
                                                title="Delete feature is currently disabled"
                                                class="inline-flex p-2.5 bg-gray-100 dark:bg-gray-800 text-gray-400 dark:text-gray-600 
                                                    rounded-lg cursor-not-allowed opacity-60 
                                                    border border-gray-300 dark:border-gray-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 4v12m4-12v12"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                <template v-if="displayTotal > 0">
                                    Showing {{ displayFrom }} to {{ displayTo }} of {{ displayTotal }} results
                                </template>
                                <template v-else>No results found</template>
                            </span>
                            <div v-if="classes.links.length > 3" class="flex gap-2 flex-wrap"> 
                                <Link v-for="link in classes.links" :key="link.label" :href="link.url || '#'"
                                    :class="[link.active ? 'bg-teal-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700', !link.url ? 'opacity-50 cursor-not-allowed' : '', 'px-4 py-2.5 rounded-lg text-sm font-medium transition']"
                                    v-html="link.label" />
                            </div>
                        </div>
                    </div>
                </div>

                <footer class="mt-12 text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        © All Rights Reserved. Biddaloy is a product of
                        <a href="https://smithitbd.com/" target="_blank" class="font-semibold text-teal-600 dark:text-teal-400 hover:underline">Smith IT</a>
                    </p>
                </footer>
            </div>
        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-6 w-full max-w-md border border-gray-200 dark:border-gray-700">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Confirm Deletion
                    </h3>
                    <button @click="closeDeleteModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <p class="text-gray-700 dark:text-gray-300 mb-6 leading-relaxed">
                    Are you sure you want to permanently delete the class:<br>
                    <strong class="text-teal-600 dark:text-teal-400 text-lg">{{ classToDelete?.class_name }}</strong>?<br>
                    <span class="text-rose-600 dark:text-rose-400 font-medium">This action cannot be undone.</span>
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="closeDeleteModal" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">
                        Cancel
                    </button>
                    <DangerButton @click="deleteClass" :disabled="form.processing" class="bg-rose-600 hover:bg-rose-700 text-white font-medium">
                        {{ form.processing ? 'Deleting...' : 'Delete Class' }}
                    </DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>