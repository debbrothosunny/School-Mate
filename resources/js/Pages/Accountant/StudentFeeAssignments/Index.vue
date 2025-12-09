```vue
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    studentFeeAssignments: Object,
});

const flash = computed(() => usePage().props.flash || {});
const form = useForm({});
const showDeleteModal = ref(false);
const assignmentToDelete = ref(null);

const confirmDelete = (assignment) => {
    assignmentToDelete.value = assignment;
    showDeleteModal.value = true;
};

const deleteAssignment = () => {
    if (assignmentToDelete.value) {
        form.delete(route('student-fee-assignments.destroy', assignmentToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => closeDeleteModal(),
            onError: (errors) => {
                console.error("Error deleting student fee assignment:", errors);
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    assignmentToDelete.value = null;
};
</script>

<template>
  <Head title="Manage Student Fee Assignments" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white tracking-tight">
        Student Fee Assignments
      </h2>
    </template>

    <div class="py-8 sm:py-12 min-h-screen bg-gradient-to-br from-gray-50 via-white to-teal-50 dark:from-gray-950 dark:via-gray-900 dark:to-teal-950/20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Flash Message -->
        <div v-if="flash.message" :class="flash.type === 'success' 
          ? 'bg-emerald-100/80 text-emerald-800 border-emerald-200' 
          : 'bg-rose-100/80 text-rose-800 border-rose-200'"
          class="mb-6 px-6 py-4 rounded-xl border backdrop-blur-sm text-sm font-medium shadow-sm flex justify-between items-center">
          <span>{{ flash.message }}</span>
          <button @click="flash.message = null" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="bg-white/80 dark:bg-gray-900/90 backdrop-blur-xl rounded-2xl shadow-xl border border-gray-200/50 dark:border-gray-800 overflow-hidden">
          <div class="p-6 lg:p-8">

            <!-- Header + Add Button -->
            <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-8">
              <div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white">All Fee Assignments</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                  {{ studentFeeAssignments.total || 0 }} assignment{{ studentFeeAssignments.total !== 1 ? 's' : '' }} in total
                </p>
              </div>
              <Link :href="route('student-fee-assignments.create')">
                <PrimaryButton class="bg-teal-600 hover:bg-teal-700 text-white font-medium shadow-lg shadow-teal-600/30 hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                  </svg>
                  Add New Assignment
                </PrimaryButton>
              </Link>
            </div>

            <!-- Mobile Cards -->
            <div v-for="(assignment, index) in studentFeeAssignments.data" :key="assignment.id"
              class="sm:hidden bg-white dark:bg-gray-800/80 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-5 mb-4 hover:shadow-lg transition-all duration-300">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h4 class="font-bold text-gray-900 dark:text-white">
                    {{ assignment.student?.name || 'Unknown Student' }}
                  </h4>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ assignment.fee_type?.name || 'Unknown Fee' }}
                  </p>
                </div>
                <span :class="Number(assignment.status) === 0 
                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300' 
                  : 'bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300'"
                  class="px-3 py-1 rounded-full text-xs font-medium">
                  {{ Number(assignment.status) === 0 ? 'Active' : 'Inactive' }}
                </span>
              </div>

              <div class="flex justify-end gap-3">
                <Link :href="route('student-fee-assignments.edit', assignment.id)" class="p-3 bg-teal-50 dark:bg-teal-900/40 text-teal-600 dark:text-teal-400 rounded-lg hover:bg-teal-100 dark:hover:bg-teal-900/70 transition">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"/>
                  </svg>
                </Link>
                <button @click="confirmDelete(assignment)" class="p-3 bg-rose-50 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/70 transition">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 4v12m4-12v12"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50/70 dark:bg-gray-800/70 backdrop-blur">
                  <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Student</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Fee Type</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white/70 dark:bg-gray-900/70 divide-y divide-gray-200 dark:divide-gray-800">
                  <tr v-for="assignment in studentFeeAssignments.data" :key="assignment.id" class="hover:bg-teal-50/50 dark:hover:bg-teal-900/20 transition">
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                      {{ assignment.student?.name || '—' }}
                    </td>
                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">
                      {{ assignment.fee_type?.name || '—' }}
                    </td>
                    <td class="px-6 py-4">
                      <span :class="Number(assignment.status) === 0 
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300' 
                        : 'bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300'"
                        class="px-3 py-1 rounded-full text-xs font-medium">
                        {{ Number(assignment.status) === 0 ? 'Active' : 'Inactive' }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-3">
                      <Link :href="route('student-fee-assignments.edit', assignment.id)" class="inline-flex p-2.5 bg-teal-50 dark:bg-teal-900/40 text-teal-600 dark:text-teal-400 rounded-lg hover:bg-teal-100 dark:hover:bg-teal-900/70 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"/>
                        </svg>
                      </Link>
                      <button @click="confirmDelete(assignment)" class="inline-flex p-2.5 bg-rose-50 dark:bg-rose-900/40 text-rose-600 dark:text-rose-400 rounded-lg hover:bg-rose-100 dark:hover:bg-rose-900/70 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 4v12m4-12v12"/>
                        </svg>
                      </button>
                    </td>
                  </tr>
                  <tr v-if="!studentFeeAssignments.data?.length">
                    <td colspan="4" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400 text-lg">
                      <div class="font-medium">No fee assignments found</div>
                      <div class="text-sm mt-2">Click "Add New Assignment" to get started.</div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
              <span class="text-sm text-gray-600 dark:text-gray-400">
                Showing {{ studentFeeAssignments.from || 0 }}–{{ studentFeeAssignments.to || 0 }} of {{ studentFeeAssignments.total || 0 }} assignments
              </span>
              <div class="flex gap-2 flex-wrap">
                <Link v-for="link in studentFeeAssignments.links" :key="link.label" :href="link.url || '#'"
                  :class="[link.active ? 'bg-teal-600 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700', !link.url ? 'opacity-50 cursor-not-allowed' : '', 'px-4 py-2.5 rounded-lg text-sm font-medium transition']"
                  v-html="link.label" />
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <footer class="mt-12 text-center">
          <p class="text-sm text-gray-600 dark:text-gray-400">
            © All Rights Reserved. Biddaloy is a product of
            <a href="https://smithitbd.com/" target="_blank" class="font-semibold text-teal-600 dark:text-teal-400 hover:underline">Smith IT</a>
          </p>
        </footer>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-6 w-full max-w-md border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center mb-6">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
            <svg class="w-8 h-8 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
          Are you sure you want to <strong class="text-rose-600">permanently delete</strong> the fee assignment for:<br>
          <strong class="text-teal-600 dark:text-teal-400 text-lg">{{ assignmentToDelete?.fee_type?.name }}</strong><br>
          assigned to student <strong class="text-teal-600 dark:text-teal-400">{{ assignmentToDelete?.student?.name }}</strong>?
        </p>
        <div class="flex justify-end gap-3">
          <button @click="closeDeleteModal" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">
            Cancel
          </button>
          <button @click="deleteAssignment" :disabled="form.processing" class="bg-rose-600 hover:bg-rose-700 text-white px-5 py-2.5 rounded-lg font-medium transition">
            {{ form.processing ? 'Deleting...' : 'Delete Assignment' }}
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.font-geist {
  font-family: 'Geist', 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
}
* { @apply font-geist; }

*, *::before, *::after {
  transition: all 0.2s ease;
}
</style>
```