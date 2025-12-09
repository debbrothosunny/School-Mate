<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref, computed, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
  sections: Array,
  flash: Object,
});

const flash = computed(() => usePage().props.flash || {});
const showDeleteModal = ref(false);
const sectionToDelete = ref(null);
const form = useForm({});

const openDeleteModal = (section) => {
  sectionToDelete.value = section;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  sectionToDelete.value = null;
};

const deleteSection = () => {
  if (!sectionToDelete.value) return;

  form.delete(route('sections.destroy', sectionToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      sectionToDelete.value = null;
    },
    onError: () => {
      alert('Deletion failed. Please try again.');
    },
  });
};

watch(
  flash,
  (newFlash) => {
    if (newFlash.message) {
      Swal.fire({
        icon: newFlash.type === 'success' ? 'success' : 'error',
        title: newFlash.type === 'success' ? 'Success!' : 'Error!',
        text: newFlash.message,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
    }
  },
  { deep: true }
);
</script>

<template>
  <div class="min-h-screen bg-gray-100 text-gray-900 font-sans">
    <Head title="Section Management" />
    <AuthenticatedLayout>
      <header
        class="max-w-7xl mx-auto flex justify-between items-center py-6 px-4 sm:px-6 lg:px-8 bg-white shadow rounded mt-6"
      >
        <h1 class="text-3xl font-extrabold">Sections</h1>
        <Link
          :href="route('sections.create')"
          class="inline-block bg-indigo-600 text-white font-semibold rounded px-6 py-2 hover:bg-indigo-700 transition"
        >
          + Add Section
        </Link>
      </header>

      <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
          <div
            v-for="section in sections"
            :key="section.id"
            class="bg-white rounded-lg shadow p-6 flex flex-col justify-between"
          >
            <div>
              <h2 class="text-xl font-semibold mb-2">{{ section.name }}</h2>
              <span
                :class="section.status === 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                class="inline-block rounded-full px-3 py-1 text-sm font-semibold"
              >
                {{ section.status === 0 ? 'Active' : 'Inactive' }}
              </span>
            </div>

            <div class="mt-6 flex justify-between items-center">
              <Link
                :href="route('sections.edit', section.id)"
                class="text-indigo-600 hover:text-indigo-900 font-semibold flex items-center gap-2"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"
                  />
                </svg>
                Edit
              </Link>

              <button
                disabled
                class="bg-gray-400 dark:bg-gray-700 text-gray-200 dark:text-gray-500 
                      cursor-not-allowed rounded px-3 py-1 flex items-center gap-2 text-sm 
                      opacity-60"
                title="Delete functionality is currently disabled"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0a2 2 0 00-2-2H9a2 2 0 00-2 2m12 0H5"/>
                </svg>
                Delete
              </button>

            </div>
          </div>
        </div>

        <div v-if="!sections?.length" class="text-center py-20 text-gray-500 text-lg">
          No sections found.
        </div>

        <div class="mt-12 pt-6 border-t border-gray-300 text-center bg-gray-100">
          <p class="text-base md:text-lg text-black font-semibold leading-relaxed mx-auto" style="display:inline-block; white-space:nowrap;">
            © All Rights Reserved. Biddaloy is a product of
            <a href="https://smithitbd.com/" target="_blank" class="font-semibold text-red-600 hover:text-red-700 transition-colors hover:underline">
              Smith&nbsp;IT
            </a>
          </p>
        </div>
      </main>

      <div
        v-if="showDeleteModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm p-6"
        role="dialog"
        aria-modal="true"
        aria-labelledby="modal-title"
      >
        <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full">
          <h3 id="modal-title" class="text-xl font-semibold mb-4">
            Confirm Deletion
          </h3>
          <p class="text-gray-700 mb-6">
            Are you sure you want to delete
            <strong class="text-red-600">&quot;{{ sectionToDelete?.name }}&quot;</strong>?
          </p>

          <div class="flex justify-end gap-4">
            <button
              @click="showDeleteModal = false"
              class="px-4 py-2 border border-gray-400 rounded hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-600"
            >
              Cancel
            </button>

            <DangerButton
              @click="deleteSection"
              class="bg-red-600 hover:bg-red-700 text-white rounded px-4 py-2"
            >
              Delete
            </DangerButton>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  </div>
</template>

<style scoped>
</style>
