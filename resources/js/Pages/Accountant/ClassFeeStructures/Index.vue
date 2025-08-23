<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    classFeeStructures: Object, // Paginated list of class fee structures
});

const flash = computed(() => usePage().props.flash || {});

const form = useForm({});
const showDeleteModal = ref(false);
const structureToDelete = ref(null);

const confirmDelete = (structure) => {
    structureToDelete.value = structure;
    showDeleteModal.value = true;
};

const deleteStructure = () => {
    if (structureToDelete.value) {
        form.delete(route('class-fee-structures.destroy', structureToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
            },
            onError: (errors) => {
                console.error("Error deleting class fee structure:", errors);
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    structureToDelete.value = null;
};

// Adjusted formatCurrency: Assumes amount is already in BDT (decimal), not paisa.
const formatCurrency = (amount) => {
    if (amount === null || amount === undefined) {
        return 'BDT 0.00'; // Or handle as you prefer if a fee structure could genuinely have a null amount
    }
    return `BDT ${parseFloat(amount).toFixed(2)}`;
};
</script>

<template>
    <Head title="Manage Class Fee Structures" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Class Fee Structures</h2>
        </template>

        <div class="py-12 bg-gray-100 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="flex justify-between items-center mb-8 px-4 sm:px-0">
                    <h2 class="text-3xl font-bold text-gray-900">Fee Structures</h2>
                    <Link :href="route('class-fee-structures.create')">
                        <button class="inline-flex items-center space-x-2 px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            <span>Add New Structure</span>
                        </button>
                    </Link>
                </div>

                <!-- Flash Message Display -->
                <div v-if="flash.message" :class="[
                    'p-4 mb-6 rounded-lg font-medium mx-4 sm:mx-0',
                    flash.type === 'success' ? 'bg-green-100 text-green-800 border-l-4 border-green-500' : 'bg-red-100 text-red-800 border-l-4 border-red-500'
                ]" role="alert">
                    {{ flash.message }}
                </div>

                <!-- Fee Structures Grid -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="structure in classFeeStructures.data" :key="structure.id" class="bg-white rounded-lg shadow-md transition-all hover:shadow-lg">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-lg font-bold text-gray-900">{{ structure.fee_type ? structure.fee_type.name : 'N/A' }}</h3>
                                <span :class="[
                                    'inline-flex px-3 py-1 text-xs font-semibold rounded-full uppercase tracking-wide',
                                    parseInt(structure.status) === 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                                ]">
                                    {{ parseInt(structure.status) === 0 ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                            <p class="text-3xl font-extrabold text-indigo-600">{{ formatCurrency(structure.amount) }}</p>
                            <p class="text-sm text-gray-500 mt-1">{{ structure.fee_type ? structure.fee_type.frequency : 'N/A' }}</p>
                        </div>
                        <div class="p-6 text-sm text-gray-600">
                            <div class="flex items-center mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                                <span>Class: <span class="font-semibold text-gray-800">{{ structure.class_name ? structure.class_name.class_name : 'N/A' }}</span></span>
                            </div>
                            <div class="flex items-center mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h.01M16 11h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Session: <span class="font-semibold text-gray-800">{{ structure.session ? structure.session.name : 'N/A' }}</span></span>
                            </div>
                            <div class="flex items-center mb-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h2a2 2 0 002-2V4a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h2m3-4H9.5a1.5 1.5 0 010-3H12m0 0v-3m0 3a1.5 1.5 0 00-3-3v3m3 3v3m3-3a1.5 1.5 0 010 3H12"/></svg>
                                <span>Group: <span class="font-semibold text-gray-800">{{ structure.group ? structure.group.name : 'N/A' }}</span></span>
                            </div>
                            <div class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.485 9.166 5 7.5 5S4.167 5.485 3 6.253v13C4.167 18.485 5.833 18 7.5 18s3.333.485 4.5 1.253m0-13C13.168 5.485 14.834 5 16.5 5s3.333.485 4.5 1.253v13C19.833 18.485 18.167 18 16.5 18s-3.333.485-4.5 1.253"/></svg>
                                <span>Section: <span class="font-semibold text-gray-800">{{ structure.section ? structure.section.name : 'N/A' }}</span></span>
                            </div>
                        </div>
                        <div class="p-6 border-t border-gray-200 flex justify-end space-x-3">
                            <Link :href="route('class-fee-structures.edit', structure.id)">
                                <button class="p-2 border border-gray-300 rounded-md text-gray-500 hover:text-indigo-600 hover:bg-gray-100 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                </button>
                            </Link>
                            <button @click="confirmDelete(structure)" class="p-2 border border-gray-300 rounded-md text-gray-500 hover:text-red-600 hover:bg-gray-100 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                            </button>
                        </div>
                    </div>
                    <p v-if="!classFeeStructures.data.length" class="col-span-full text-center py-8 text-gray-500 text-lg">No class fee structures found.</p>
                </div>

                <!-- Pagination Links -->
                <div v-if="classFeeStructures.links.length > 3" class="mt-8 flex justify-center">
                    <div class="flex flex-wrap -mb-1">
                        <template v-for="(link, key) in classFeeStructures.links" :key="key">
                            <div
                                v-if="link.url === null"
                                class="mr-1 mb-1 px-4 py-2 text-sm leading-4 text-gray-400 border rounded-lg"
                                v-html="link.label"
                            />
                            <Link
                                v-else
                                class="mr-1 mb-1 px-4 py-2 text-sm leading-4 border rounded-lg transition-colors"
                                :class="{ 'bg-indigo-600 text-white border-indigo-600': link.active, 'bg-white text-gray-700 hover:bg-gray-100': !link.active }"
                                :href="link.url"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-xl shadow-xl w-96">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Confirm Deletion</h3>
                    <button @click="closeDeleteModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <p class="mb-6 text-gray-700">
                    Are you sure you want to delete this fee structure for
                    <span class="font-bold">{{ structureToDelete && structureToDelete.fee_type ? structureToDelete.fee_type.name : '' }}</span>
                    in Class <span class="font-bold">{{ structureToDelete && structureToDelete.class_name ? structureToDelete.class_name.class_name : '' }}</span>?
                </p>
                <div class="flex justify-end space-x-3">
                    <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors">
                        Cancel
                    </button>
                    <button
                        @click="deleteStructure"
                        :class="['px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors', { 'opacity-25': form.processing }]"
                        :disabled="form.processing"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
