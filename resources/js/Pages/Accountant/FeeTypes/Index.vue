<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

// Define props with the feeTypes object
const props = defineProps({
    feeTypes: Object,
});

const flash = computed(() => usePage().props.flash || {});
const form = useForm({});
const showDeleteModal = ref(false);
const feeTypeToDelete = ref(null);

const confirmDelete = (feeType) => {
    feeTypeToDelete.value = feeType;
    showDeleteModal.value = true;
};

const deleteFeeType = () => {
    if (feeTypeToDelete.value) {
        form.delete(route('fee-types.destroy', feeTypeToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
            },
            onError: (errors) => {
                console.error("Error deleting fee type:", errors);
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    feeTypeToDelete.value = null;
};

const formatCurrency = (amountInPaisa) =>
    amountInPaisa == null ? 'N/A' : `BDT ${(amountInPaisa / 100).toFixed(2)}`;
</script>

<template>
  <Head title="Manage Fee Types" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Fee Types</h2>
    </template>

    <div class="py-12 bg-gray-100 min-h-screen">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8 px-4 sm:px-0">
          <h2 class="text-3xl font-bold text-gray-900">Fee Type Management</h2>
          <Link :href="route('fee-types.create')">
            <button class="inline-flex items-center space-x-2 px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"/></svg>
              <span>Add New Fee Type</span>
            </button>
          </Link>
        </div>

        <div v-if="flash.message" :class="[
            'p-4 mb-6 rounded-lg font-medium',
            flash.type === 'success' ? 'bg-green-100 text-green-800 border-l-4 border-green-500' : 'bg-red-100 text-red-800 border-l-4 border-red-500'
          ]" role="alert">
          {{ flash.message }}
        </div>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <div v-for="feeType in feeTypes.data" :key="feeType.id" class="bg-white rounded-lg shadow-md transition-all hover:shadow-lg">
            <div class="p-6 border-b">
              <h3 class="text-lg font-bold flex justify-between items-start">
                {{ feeType.name }}
                <span :class="[
                    'inline-flex px-3 py-1 text-xs font-semibold rounded-full uppercase tracking-wide',
                    feeType.status == 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                  ]">
                  {{ feeType.status == 0 ? 'Active' : 'Inactive' }}
                </span>
              </h3>
              <p class="text-sm text-gray-500">{{ feeType.frequency }}</p>
            </div>
            <div class="p-6">
              <p class="text-sm text-gray-700">{{ feeType.description || 'No description provided.' }}</p>
            </div>
            <div class="p-6 border-t flex justify-end space-x-3">
              <Link :href="route('fee-types.edit', feeType.id)">
                <button class="p-2 border border-gray-300 rounded-md text-gray-500 hover:text-indigo-600 hover:bg-gray-100 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </button>
              </Link>
              <button @click="confirmDelete(feeType)" class="p-2 border border-gray-300 rounded-md text-gray-500 hover:text-red-600 hover:bg-gray-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
              </button>
            </div>
          </div>
          <p v-if="!feeTypes.data.length" class="col-span-full text-center py-8 text-gray-500 text-lg">No fee types found.</p>
        </div>

        <div v-if="feeTypes.links.length > 3" class="mt-8 flex justify-center">
          <div class="flex flex-wrap -mb-1">
            <template v-for="(link, key) in feeTypes.links" :key="key">
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
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 transition-opacity">
      <div class="bg-white p-6 rounded-xl shadow-2xl w-full max-w-sm transform transition-all">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-xl font-bold text-gray-900">Confirm Deletion</h3>
          <button @click="closeDeleteModal" class="text-gray-400 hover:text-gray-600 focus:outline-none">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <p class="mb-6 text-gray-700">
          Are you sure you want to delete the fee type:
          <span class="font-bold">{{ feeTypeToDelete ? feeTypeToDelete.name : '' }}</span>?
          This action cannot be undone and may affect existing fee structures and assignments.
        </p>
        <div class="flex justify-end space-x-3">
          <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition-colors">Cancel</button>
          <button
            @click="deleteFeeType"
            :class="['px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors', { 'opacity-25': form.processing }]"
            :disabled="form.processing"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Copyright footer -->
    <div class="mt-12 py-6 border-t border-gray-300 text-center bg-gray-100 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <p class="text-base md:text-lg text-black font-semibold leading-relaxed" style="display:inline-block; white-space:nowrap;">
        © All Rights Reserved. Biddaloy is a product of
        <a href="https://smithitbd.com/" target="_blank" class="font-semibold text-red-600 hover:text-red-700 transition-colors hover:underline">
          Smith&nbsp;IT
        </a>
      </p>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
/* No additional styles needed */
</style>
