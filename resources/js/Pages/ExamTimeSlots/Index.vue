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
  <!-- The main content is now wrapped in the AuthenticatedLayout component -->
  <AuthenticatedLayout>
    <div class="min-h-screen bg-gray-100 p-8">
      <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
          <h1 class="text-3xl font-bold text-gray-800">Exam Time Slots</h1>
          <Link
            :href="route('exam-time-slots.create')"
            class="inline-block px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition-colors duration-300"
          >
            Add New Slot
          </Link>
        </div>

        <div v-if="timeSlots.length" class="overflow-x-auto rounded-lg shadow-md">
          <table class="min-w-full bg-white border-collapse">
            <thead>
              <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                <th class="py-3 px-6 text-left">Name</th>
                <th class="py-3 px-6 text-left">Start Time</th>
                <th class="py-3 px-6 text-left">End Time</th>
                <th class="py-3 px-6 text-center">Actions</th>
              </tr>
            </thead>
            <tbody class="text-gray-600 text-sm font-light">
              <tr
                v-for="slot in timeSlots"
                :key="slot.id"
                class="border-b border-gray-200 hover:bg-gray-50 transition-colors duration-200"
              >
                <td class="py-3 px-6 text-left whitespace-nowrap">{{ slot.name }}</td>
                <!-- Use the new formatTime function here -->
                <td class="py-3 px-6 text-left">{{ formatTime(slot.start_time) }}</td>
                <td class="py-3 px-6 text-left">{{ formatTime(slot.end_time) }}</td>
                <td class="py-3 px-6 text-center">
                  <div class="flex item-center justify-center space-x-2">
                    <Link
                      :href="route('exam-time-slots.edit', slot.id)"
                      class="w-8 h-8 flex items-center justify-center rounded-full bg-yellow-400 text-white hover:bg-yellow-500 transition-colors duration-300"
                      title="Edit"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                        />
                      </svg>
                    </Link>
                    <button
                      @click="confirmDelete(slot)"
                      class="w-8 h-8 flex items-center justify-center rounded-full bg-red-500 text-white hover:bg-red-600 transition-colors duration-300"
                      title="Delete"
                    >
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                      >
                        <path
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                        />
                      </svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="text-center py-12 text-gray-500 text-lg">
          No exam time slots found.
        </div>
      </div>

      <!-- Custom Delete Confirmation Modal -->
      <div
        v-if="modal.show"
        class="fixed inset-0 z-50 overflow-y-auto bg-gray-900 bg-opacity-50 flex items-center justify-center"
      >
        <div class="relative bg-white rounded-lg shadow-xl max-w-sm w-full p-6 mx-auto">
          <h3 class="text-xl font-bold text-gray-800 mb-4">Confirm Deletion</h3>
          <p class="text-gray-600 mb-6">
            Are you sure you want to delete the time slot "{{ modal.name }}"? This action cannot be undone.
          </p>
          <div class="flex justify-end space-x-3">
            <button
              @click="cancelDelete"
              class="px-4 py-2 bg-gray-200 text-gray-800 font-semibold rounded-md hover:bg-gray-300 transition-colors"
            >
              Cancel
            </button>
            <button
              @click="deleteTimeSlot"
              class="px-4 py-2 bg-red-600 text-white font-semibold rounded-md hover:bg-red-700 transition-colors"
            >
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
