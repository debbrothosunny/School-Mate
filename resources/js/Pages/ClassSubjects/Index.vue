<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, computed, watchEffect } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
  classSubjects: Object,
});

const flash = computed(() => usePage().props.flash || {});
const searchQuery = ref('');
const showDeleteModal = ref(false);
const classSubjectToDelete = ref(null);
const form = useForm({});

const filteredClassSubjects = computed(() => {
  if (!props.classSubjects || !props.classSubjects.data) {
    return [];
  }
  if (!searchQuery.value) {
    return props.classSubjects.data;
  }
  const lowerCaseQuery = searchQuery.value.toLowerCase();
  return props.classSubjects.data.filter(cs => {
    const className = cs.class_name?.class_name?.toLowerCase() || '';
    const subjectName = cs.subject?.name?.toLowerCase() || '';
    const teacherName = cs.teacher?.name?.toLowerCase() || '';
    const sessionName = cs.session?.name?.toLowerCase() || '';
    const sectionName = cs.section?.name?.toLowerCase() || '';
    const groupName = cs.group?.name?.toLowerCase() || '';
    const statusText = getStatusText(cs.status).toLowerCase();
    return (
      className.includes(lowerCaseQuery) ||
      subjectName.includes(lowerCaseQuery) ||
      teacherName.includes(lowerCaseQuery) ||
      sessionName.includes(lowerCaseQuery) ||
      sectionName.includes(lowerCaseQuery) ||
      groupName.includes(lowerCaseQuery) ||
      statusText.includes(lowerCaseQuery)
    );
  });
});

const getStatusText = (status) => (status === 0 ? 'Active' : 'Inactive');

const confirmDelete = (classSubject) => {
  classSubjectToDelete.value = classSubject;
  showDeleteModal.value = true;
};

const deleteClassSubject = () => {
  if (classSubjectToDelete.value) {
    form.delete(route('class-subjects.destroy', classSubjectToDelete.value.id), {
      preserveScroll: true,
      onSuccess: () => {
        closeDeleteModal();
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: 'Class subject deleted successfully.',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
        });
      },
      onError: (errors) => {
        console.error('Error deleting class subject:', errors);
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: 'Failed to delete class subject. Please try again.',
          toast: true,
          position: 'top-end',
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
        });
        closeDeleteModal();
      },
    });
  }
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  classSubjectToDelete.value = null;
};

watchEffect(() => {
  if (props.classSubjects) {
    console.log('Class Subjects Prop Data:', props.classSubjects.data);
  }
  if (flash.value?.message) {
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
</script>

<template>
  <Head title="Class Subject Management" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl md:text-3xl font-bold text-gray-800 dark:text-white tracking-tight">
        Class Subject Management
      </h2>
    </template>

    <div class="py-8 sm:py-12 min-h-screen bg-gradient-to-br from-gray-50 via-white to-teal-50 dark:from-gray-950 dark:via-gray-900 dark:to-teal-950/20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white/80 dark:bg-gray-900/90 backdrop-blur-xl rounded-2xl shadow-xl border border-gray-200/50 dark:border-gray-800 overflow-hidden">
          <div class="p-6 lg:p-8">

            <!-- Header + Add Button -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6 mb-8">
              <h3 class="text-xl font-semibold text-gray-900 dark:text-white">All Class Subjects</h3>
              <Link :href="route('class-subjects.create')">
                <PrimaryButton class="bg-teal-600 hover:bg-teal-700 text-white font-medium shadow-lg shadow-teal-600/30 hover:shadow-xl transition-all duration-200 flex items-center gap-2">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                  Assign New Subject
                </PrimaryButton>
              </Link>
            </div>

            <!-- Search Bar -->
            <div class="relative mb-8">
              <TextInput
                id="search"
                type="text"
                v-model="searchQuery"
                placeholder="Search by class, subject, teacher, session, section, group..."
                class="pl-12 pr-5 py-3.5 rounded-xl border-gray-300 dark:border-gray-700 bg-white/70 dark:bg-gray-800/70 backdrop-blur focus:ring-2 focus:ring-teal-500 focus:border-teal-500 shadow-inner text-base placeholder-gray-500"
              />
              <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
              </div>
            </div>

            <!-- Mobile Cards -->
            <div v-for="cs in filteredClassSubjects" :key="cs.id" class="sm:hidden bg-white dark:bg-gray-800/80 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-5 mb-4 hover:shadow-lg transition-all duration-300">
              <!-- Same mobile card content as before but with new colors -->
              <div class="flex justify-between items-start mb-3">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">{{ cs.class_name?.class_name || 'N/A' }}</h4>
                <span :class="cs.status === 0 ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300'"
                      class="px-3 py-1 rounded-full text-xs font-medium">
                  {{ getStatusText(cs.status) }}
                </span>
              </div>
              <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                <p><span class="font-medium text-gray-700 dark:text-gray-300">Subject:</span> {{ cs.subject?.name || 'N/A' }}</p>
                <p><span class="font-medium text-gray-700 dark:text-gray-300">Teacher:</span> {{ cs.teacher?.name || 'N/A' }}</p>
                <p><span class="font-medium text-gray-700 dark:text-gray-300">Session:</span> {{ cs.session?.name || 'N/A' }}</p>
                <p><span class="font-medium text-gray-700 dark:text-gray-300">Section:</span> {{ cs.section?.name || 'N/A' }}</p>
                <p v-if="cs.group?.name"><span class="font-medium text-gray-700 dark:text-gray-300">Group:</span> {{ cs.group?.name }}</p>
              </div>
              <div class="flex justify-end gap-3 mt-5">
                <Link :href="route('class-subjects.edit', cs.id)" class="p-3 bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/70 transition">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"/></svg>
                </Link>
                <button
                  disabled
                  title="Delete functionality is currently disabled"
                  class="inline-flex p-2.5 
                        bg-gray-100 dark:bg-gray-800/50 
                        text-gray-400 dark:text-gray-600 
                        rounded-lg 
                        cursor-not-allowed 
                        opacity-60 
                        border border-gray-300 dark:border-gray-700 
                        transition">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" 
                          stroke-linejoin="round" 
                          stroke-width="2" 
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 4v12m4-12v12"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Desktop Table -->
            <div class="hidden sm:block overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
              <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50/70 dark:bg-gray-800/70 backdrop-blur">
                  <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">S/N</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Class</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Subject</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Teacher</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Session</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Section</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Group</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white/70 dark:bg-gray-900/70 divide-y divide-gray-200 dark:divide-gray-800">
                  <tr v-if="filteredClassSubjects.length === 0">
                    <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No class subjects found.</td>
                  </tr>
                  <tr v-for="(cs, index) in filteredClassSubjects" :key="cs.id" class="hover:bg-teal-50/50 dark:hover:bg-teal-900/20 transition">
                    <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                      {{ ((props.classSubjects.current_page - 1) * props.classSubjects.per_page) + index + 1 }}
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">{{ cs.class_name?.class_name || 'N/A' }}</td>
                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ cs.subject?.name || 'N/A' }}</td>
                    <td class="px-6 py-4 text-gray-700 dark:text-gray-300">{{ cs.teacher?.name || 'N/A' }}</td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ cs.session?.name || 'N/A' }}</td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ cs.section?.name || 'N/A' }}</td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ cs.group?.name || 'N/A' }}</td>
                    <td class="px-6 py-4">
                      <span :class="cs.status === 0 
                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300' 
                        : 'bg-rose-100 text-rose-700 dark:bg-rose-900/60 dark:text-rose-300'"
                        class="px-3 py-1 rounded-full text-xs font-medium">
                        {{ getStatusText(cs.status) }}
                      </span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-3">
                      <Link :href="route('class-subjects.edit', cs.id)" class="inline-flex p-2.5 bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/70 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"/></svg>
                      </Link>
                      <button
                        disabled
                        title="Delete functionality is currently disabled"
                        class="inline-flex p-2.5 
                              bg-gray-100 dark:bg-gray-800/50 
                              text-gray-400 dark:text-gray-600 
                              rounded-lg 
                              cursor-not-allowed 
                              opacity-60 
                              border border-gray-300 dark:border-gray-700 
                              transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" 
                                stroke-linejoin="round" 
                                stroke-width="2" 
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 4v12m4-12v12"/>
                        </svg>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination & Footer unchanged (just better spacing) -->
            <div v-if="filteredClassSubjects.length" class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
              <span class="text-sm text-gray-600 dark:text-gray-400">
                Showing {{ searchQuery ? filteredClassSubjects.length : props.classSubjects.from }}–{{ searchQuery ? filteredClassSubjects.length : props.classSubjects.to }} of {{ searchQuery ? filteredClassSubjects.length : props.classSubjects.total }} results
              </span>
              <div v-if="!searchQuery" class="flex gap-2 flex-wrap">
                <Link v-for="link in props.classSubjects.links" :key="link.label" :href="link.url || '#'"
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

    <!-- Delete Modal (updated colors) -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
      <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl p-6 w-full max-w-md border border-gray-200 dark:border-gray-700">
        <div class="flex justify-between items-center mb-5">
          <h3 class="text-xl font-bold text-gray-900 dark:text-white">Confirm Deletion</h3>
          <button @click="closeDeleteModal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
        <p class="text-gray-700 dark:text-gray-300 mb-6 leading-relaxed">
          Are you sure you want to delete this assignment?<br>
          <strong class="text-teal-600 dark:text-teal-400">{{ classSubjectToDelete?.class_name?.class_name }} - {{ classSubjectToDelete?.subject?.name }}</strong>
        </p>
        <div class="flex justify-end gap-3">
          <button @click="closeDeleteModal" class="px-5 py-2.5 bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-700 transition">
            Cancel
          </button>
          <DangerButton @click="deleteClassSubject" :disabled="form.processing" class="bg-rose-600 hover:bg-rose-700 text-white font-medium">
            {{ form.processing ? 'Deleting...' : 'Delete' }}
          </DangerButton>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
/* Premium modern font stack (Geist → Inter → system) */
.font-geist {
  font-family: 'Geist', 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
}
* { @apply font-geist; }

/* Smooth transitions */
*, *::before, *::after {
  transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, transform 0.15s ease;
}
</style>