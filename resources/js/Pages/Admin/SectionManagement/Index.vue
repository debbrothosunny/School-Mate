<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref, onMounted, watch, computed } from 'vue';
import { useTheme } from '@/Composables/useTheme';

const { isDark } = useTheme();

const props = defineProps({
  sections: Object,
  flash: Object,
});

const flash = computed(() => usePage().props.flash || {});

const showDeleteModal = ref(false);
const sectionToDelete = ref(null);

const openDeleteModal = (section) => {
  sectionToDelete.value = section;
  showDeleteModal.value = true;
};

const deleteSection = () => {
  if (!sectionToDelete.value) return;
  useForm({}).delete(route('sections.destroy', sectionToDelete.value.id), {
    preserveScroll: true,
    onSuccess: () => {
      showDeleteModal.value = false;
      sectionToDelete.value = null;
    },
    onError: (errors) => {
      console.error('Deletion failed:', errors);
      Swal.fire({
        icon: 'error',
        title: 'Deletion Failed!',
        text: 'An error occurred during deletion. Please try again.',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
    },
  });
};

// Apply theme on mount and watch for changes
onMounted(() => {
  document.documentElement.classList.toggle('dark', isDark.value);
});
watch(isDark, (val) => {
  document.documentElement.classList.toggle('dark', val);
});

// Watch for flash messages and show SweetAlert
watch(() => flash.value, (newFlash) => {
  if (newFlash && newFlash.message) {
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
}, { deep: true });
</script>


<template>
  <div class="min-h-screen flex flex-col bg-white dark:bg-black text-gray-900 dark:text-white transition-colors duration-300">
    <Head title="Section Management" />

    <AuthenticatedLayout>
      <template #header>
        <h2 class="font-semibold text-xl leading-tight text-gray-900 dark:text-gray-100">
          Section Management
        </h2>
      </template>

      <!-- Main content area that grows -->
      <div class="flex-1 flex flex-col p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">All Sections</h3>
          <Link :href="route('sections.create')">
            <PrimaryButton
              class="!bg-transparent dark:!bg-transparent !text-gray-900 dark:!text-white !border !border-gray-900 dark:!border-gray-200 hover:!bg-gray-900/5 dark:hover:!bg-white/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-800 dark:focus:ring-gray-200 dark:focus:ring-offset-black"
            >
              Add New Section
            </PrimaryButton>
          </Link>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead class="bg-transparent">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-800 dark:text-gray-200">Name</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-800 dark:text-gray-200">Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-800 dark:text-gray-200">Actions</th>
              </tr>
            </thead>

            <tbody class="divide-y divide-gray-200/60 dark:divide-white/10">
              <tr v-for="section in sections" :key="section.id">
                <td class="px-4 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">{{ section.name }}</td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span
                    :class="section.status === 0
                      ? 'bg-green-100 text-green-800 dark:bg-green-800/40 dark:text-green-200'
                      : 'bg-red-100 text-red-800 dark:bg-red-800/40 dark:text-red-200'"
                    class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                  >
                    {{ section.status === 0 ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium flex gap-3 items-center">
                  <Link :href="route('sections.edit', section.id)"
                        class="inline-flex items-center gap-1 text-indigo-600 dark:text-indigo-300 hover:underline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z" />
                    </svg>
                    Edit
                  </Link>

                  <DangerButton
                    class="inline-flex items-center gap-1 bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 focus:ring-offset-white dark:focus:ring-offset-black"
                    @click="openDeleteModal(section)"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                      <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0a2 2 0 00-2-2H9a2 2 0 00-2 2m12 0H5" />
                    </svg>
                    Delete
                  </DangerButton>
                </td>
              </tr>
            </tbody>
          </table>

          <div v-if="!sections?.length" class="text-center py-6 text-gray-500 dark:text-gray-400">
            No sections found.
          </div>

          <!-- ✅ Pagination -->
          <div class="mt-6">
            <Pagination :links="sections.links" />
          </div>
        </div>
      </div>

      <!-- Optional Footer -->
      <div class="mt-auto py-4 text-center text-sm text-gray-500 dark:text-gray-400">
        &copy; 2025 Your App Name
      </div>

      <!-- Modal -->
      <div
        v-if="showDeleteModal"
        class="fixed inset-0 z-50 flex items-center justify-center"
      >
        <!-- Backdrop -->
        <div
          class="absolute inset-0 bg-black/50 dark:bg-black/60 backdrop-blur-sm"
          aria-hidden="true"
          @click="showDeleteModal = false"
        />

        <!-- Panel -->
        <div
          class="relative z-10 w-full max-w-md rounded-xl border shadow-xl
                 bg-white dark:bg-neutral-900
                 text-neutral-900 dark:text-neutral-100
                 border-gray-300 dark:border-gray-700
                 dark:[color-scheme:dark] p-6"
          role="dialog"
          aria-modal="true"
          aria-labelledby="modal-title"
        >
          <h3 id="modal-title" class="text-xl font-semibold mb-4">
            Confirm Deletion
          </h3>

          <p class="mb-5 text-sm text-neutral-700 dark:text-neutral-300">
            Are you sure you want to permanently delete
            <span class="font-semibold text-red-600 dark:text-red-400">
              "{{ sectionToDelete?.name }}"
            </span>
            ?
          </p>

          <div class="flex justify-end gap-4">
            <button
              @click="showDeleteModal = false"
              class="px-4 py-2 text-sm rounded-md border
                     border-gray-400 dark:border-gray-600
                     text-gray-800 dark:text-gray-100
                     bg-transparent hover:bg-gray-100 dark:hover:bg-white/10
                     focus:outline-none focus:ring-2 focus:ring-offset-2
                     focus:ring-gray-800 dark:focus:ring-gray-200
                     focus:ring-offset-white dark:focus:ring-offset-black"
            >
              Cancel
            </button>

            <DangerButton
              @click="deleteSection"
              class="px-4 py-2 text-sm rounded-md
                     bg-red-600 hover:bg-red-700 text-white
                     focus:outline-none focus:ring-2 focus:ring-offset-2
                     focus:ring-red-500 focus:ring-offset-white dark:focus:ring-offset-black"
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
/* Tailwind handles dark variants via the html.dark class */
</style>
