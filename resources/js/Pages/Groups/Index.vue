<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import Swal from 'sweetalert2';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    groups: Object,
});

const page = usePage();
const flash = computed(() => page.props.flash || {});
watch(
    flash,
    (newFlash) => {
        if (newFlash?.message) {
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

const deleteForm = useForm({});
const showDeleteModal = ref(false);
const groupToDelete = ref(null);
const openDeleteModal = (group) => {
    groupToDelete.value = group;
    showDeleteModal.value = true;
};
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    groupToDelete.value = null;
};
const deleteGroup = () => {
    if (!groupToDelete.value) return;
    deleteForm.delete(route('groups.destroy', groupToDelete.value.id), {
        preserveScroll: true,
        onSuccess: closeDeleteModal,
    });
};

const getStatusString = (status) => (status ? 'Inactive' : 'Active');
const getStatusBadgeClass = (status) =>
    status ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800';
</script>

<template>
  <Head title="Groups Management" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white tracking-tight">
        Group Management
      </h2>
    </template>

    <div class="py-8 sm:py-12 min-h-screen bg-gradient-to-br from-gray-50 via-white to-teal-50 dark:from-gray-950 dark:via-gray-900 dark:to-teal-950/20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white/80 dark:bg-gray-900/90 backdrop-blur-xl rounded-2xl shadow-xl border border-gray-200/50 dark:border-gray-800 overflow-hidden">
          <div class="p-6 lg:p-8">

            <!-- Header + Add Button -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 mb-8">
              <h3 class="text-xl font-semibold text-gray-900 dark:text-white">All Groups</h3>
              <Link :href="route('groups.create')">
                <PrimaryButton class="bg-teal-600 hover:bg-teal-700 text-white font-medium shadow-lg shadow-teal-600/30 hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  Add New Group
                </PrimaryButton>
              </Link>
            </div>

            <!-- Mobile Cards -->
            <div v-for="group in props.groups.data" :key="group.id" class="sm:hidden bg-white dark:bg-gray-800/80 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-5 mb-4 hover:shadow-lg transition-all duration-300">
              <div class="flex justify-between items-center mb-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ group.name }}</h4>
                <span :class="group.status === 0 
                  ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300' 
                  : 'bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300'"
                  class="px-3 py-1 rounded-full text-xs font-medium">
                  {{ getStatusString(group.status) }}
                </span>
              </div>
              <div class="flex justify-end gap-3">
                <Link :href="route('groups.edit', group.id)" class="p-3 bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/70 transition">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"/>
                  </svg>
                </Link>
                
              </div>
            </div>

            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50/70 dark:bg-gray-800/70 backdrop-blur">
                  <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Group Name</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white/70 dark:bg-gray-900/70 divide-y divide-gray-200 dark:divide-gray-800">
                  <tr v-if="props.groups.data.length === 0">
                    <td colspan="3" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No groups found.</td>
                  </tr>
                  <tr v-for="group in props.groups.data" :key="group.id" class="hover:bg-teal-50/50 dark:hover:bg-teal-900/20 transition">
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ group.name }}</td>
                    <td class="px-6 py-4">
                      <span :class="group.status === 0 
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300' 
                        : 'bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300'"
                        class="px-3 py-1 rounded-full text-xs font-medium">
                        {{ getStatusString(group.status) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-3">
                      <Link :href="route('groups.edit', group.id)" class="inline-flex p-2.5 bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/70 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"/>
                        </svg>
                      </Link>
                      <button
                        disabled
                        title="Delete feature is currently disabled"
                        class="inline-flex p-2.5 bg-gray-100 dark:bg-gray-800/50 
                              text-gray-400 dark:text-gray-600 
                              rounded-lg cursor-not-allowed opacity-60 
                              border border-gray-300 dark:border-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 4v12m4-12v12"/>
                        </svg>
                      </button>
                      <!-- Delete functionality disabled -->
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="props.groups.data.length" class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
              <span class="text-sm text-gray-600 dark:text-gray-400">
                Showing {{ props.groups.from }}–{{ props.groups.to }} of {{ props.groups.total }} groups
              </span>
              <div class="flex gap-2 flex-wrap">
                <Link v-for="link in props.groups.links" :key="link.label" :href="link.url || '#'"
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
          Are you sure you want to permanently delete the group:<br>
          <strong class="text-teal-600 dark:text-teal-400 text-lg">{{ groupToDelete?.name }}</strong>?<br>
          <span class="text-rose-600 dark:text-rose-400 font-medium">This action cannot be undone.</span>
        </p>
        <div class="flex justify-end gap-3">
          <button @click="closeDeleteModal" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">
            Cancel
          </button>
          <DangerButton @click="deleteGroup" :disabled="deleteForm.processing" class="bg-rose-600 hover:bg-rose-700 text-white font-medium">
            {{ deleteForm.processing ? 'Deleting...' : 'Delete Group' }}
          </DangerButton>
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