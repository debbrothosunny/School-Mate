
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import { ref } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
  timeSlots: {
    type: Array,
    required: true,
  },
});

const modal = ref({
  show: false,
  id: null,
  name: '',
});

const confirmDelete = (slot) => {
  modal.value.show = true;
  modal.value.id = slot.id;
  modal.value.name = slot.name;
};

const cancelDelete = () => {
  modal.value.show = false;
  modal.value.id = null;
  modal.value.name = '';
};

const deleteTimeSlot = () => {
  router.delete(route('exam-time-slots.destroy', modal.value.id), {
    onSuccess: () => {
      cancelDelete();
      Swal.fire({
        icon: 'success',
        title: 'Deleted!',
        text: `Time slot "${modal.value.name}" has been deleted successfully.`,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
    },
  });
};

// Time formatting function remains unchanged
const formatTime = (timeString) => {
  if (!timeString) {
    return '';
  }
  const [hourString, minuteString] = timeString.split(':');
  const hour = parseInt(hourString, 10);
  const ampm = hour >= 12 ? 'PM' : 'AM';
  const displayHour = hour % 12 || 12; // Convert 0 (midnight) or 12 (noon) to 12
  return `${displayHour}:${minuteString} ${ampm}`;
};
</script>

<template>
  <Head title="Exam Time Slots" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-lg sm:text-xl md:text-2xl font-semibold text-gray-800 leading-tight">Exam Time Slots</h2>
    </template>
    <div class="py-6 sm:py-12 bg-gray-100 min-h-screen font-inter">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
          <div class="p-4 sm:p-6 lg:p-8">
            <!-- Header + Add Button -->
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-base sm:text-lg font-medium text-gray-900">Exam Time Slots</h3>
              <Link :href="route('exam-time-slots.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 transition">
                Add New Slot
              </Link>
            </div>
            <!-- Time Slots Table -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="slot in timeSlots" :key="slot.id" class="hover:bg-gray-50 transition">
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ slot.name }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatTime(slot.start_time) }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatTime(slot.end_time) }}</td>
                    <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-right flex items-center justify-end gap-2">
                      <Link :href="route('exam-time-slots.edit', slot.id)" class="text-indigo-600 hover:text-indigo-800 transition p-1 rounded" aria-label="Edit time slot">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" >
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"></path>
                        </svg>
                      </Link>
                      <button
                        disabled
                        title="Delete time slot is currently disabled"
                        class="text-gray-400 dark:text-gray-600 p-1 rounded cursor-not-allowed opacity-50"
                        aria-label="Delete time slot (disabled)"
                      >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 2 0 01-1.995-1.858L5 7m5-4h4m-4 4v12m4-12v12"></path>
                        </svg>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-if="timeSlots.length === 0" class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-md text-center text-sm mt-4" role="alert">
              <p class="mb-0">No exam time slots found. Please create one to get started.</p>
            </div>
          </div>
        </div>
        <!-- Footer -->
        <footer class="mt-8 sm:mt-12 pt-4 sm:pt-6 border-t border-gray-200 text-center">
          <p class="text-xs sm:text-sm text-gray-900 font-medium leading-relaxed">
            © All Rights Reserved. Biddaloy is a product of
            <a href="https://smithitbd.com/" target="_blank" class="font-semibold text-indigo-600 hover:text-indigo-700 transition-colors hover:underline">
              Smith IT
            </a>
          </p>
        </footer>
      </div>
      <!-- Delete Confirmation Modal -->
      <div v-if="modal.show" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900 bg-opacity-50 transition-opacity duration-300">
        <div class="bg-white rounded-lg shadow-xl p-4 sm:p-6 w-full max-w-sm sm:max-w-md transform transition-all duration-300 scale-100">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg sm:text-xl font-semibold text-gray-900">Confirm Deletion</h3>
            <button @click="cancelDelete" class="text-gray-500 hover:text-gray-700 transition" aria-label="Close modal">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          <p class="mb-6 text-sm sm:text-base text-gray-700">
            Are you sure you want to permanently delete the time slot: 
            <span class="font-bold">{{ modal.name }}</span>?
            This action cannot be undone.
          </p>
          <div class="flex justify-end gap-4">
            <button
              @click="cancelDelete"
              class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm sm:text-base hover:bg-gray-300 transition focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
              aria-label="Cancel deletion"
            >
              Cancel
            </button>
            <button
              @click="deleteTimeSlot"
              class="px-4 py-2 bg-red-600 text-white rounded-md text-sm sm:text-base hover:bg-red-700 transition focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
              aria-label="Confirm delete time slot"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.font-inter {
  font-family: 'Inter', sans-serif;
}

/* Table styling */
table {
  border-collapse: separate;
  border-spacing: 0;
  border-radius: 8px;
  overflow: hidden;
}
th,
td {
  padding: 12px 16px;
  text-align: left;
}
th {
  background-color: #f9fafb;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  font-size: 0.75rem;
}
td {
  font-size: 0.875rem;
  color: #4b5563;
}
tr:hover td {
  background-color: #f9fafb;
}

/* Button styling */
button,
a {
  min-height: 36px;
  min-width: 36px;
  transition: color 0.2s, background-color 0.2s;
}
button:hover,
a:hover {
  background-color: #f3f4f6;
  border-radius: 4px;
}

/* Header button */
a[href] {
  background-color: #4f46e5;
  color: white;
  padding: 8px 16px;
  border-radius: 6px;
}
a[href]:hover {
  background-color: #4338ca;
}

/* Responsive adjustments */
@media (max-width: 640px) {
  .max-w-4xl {
    max-width: 100%;
  }
  th,
  td {
    padding: 8px 12px;
  }
}

/* Footer */
footer {
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
  text-align: center;
}
footer p {
  font-size: 0.75rem;
  color: #374151;
}
footer a {
  color: #4f46e5;
}
footer a:hover {
  color: #4338ca;
  text-decoration: underline;
}

/* Modal animations */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
  transition: transform 0.3s ease;
}
.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
  transform: scale(0.95);
}
</style>
