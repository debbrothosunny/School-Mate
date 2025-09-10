<script setup>
import { useForm, Link, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Swal from 'sweetalert2';

const form = useForm({
  name: '',
  start_time: '',
  end_time: '',
});

const submit = () => {
  form.post(route('exam-time-slots.store'), {
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Exam time slot created successfully.',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
      form.reset(); // Optional: reset form after successful creation
    },
  });
};
</script>


<template>
  <AuthenticatedLayout>
    <Head title="Create Exam Time Slot" />

    <div class="min-h-screen bg-gray-100 p-8 flex items-center justify-center">
      <div class="w-full max-w-lg bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-6 text-center">
          Create Exam Time Slot
        </h1>
        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <label for="name" class="block text-gray-700 font-medium mb-1">
              Name
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.name }"
            />
            <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">
              {{ form.errors.name }}
            </div>
          </div>

          <div>
            <label for="start_time" class="block text-gray-700 font-medium mb-1">
              Start Time
            </label>
            <input
              id="start_time"
              v-model="form.start_time"
              type="time"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.start_time }"
            />
            <div v-if="form.errors.start_time" class="text-red-500 text-sm mt-1">
              {{ form.errors.start_time }}
            </div>
          </div>

          <div>
            <label for="end_time" class="block text-gray-700 font-medium mb-1">
              End Time
            </label>
            <input
              id="end_time"
              v-model="form.end_time"
              type="time"
              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              :class="{ 'border-red-500': form.errors.end_time }"
            />
            <div v-if="form.errors.end_time" class="text-red-500 text-sm mt-1">
              {{ form.errors.end_time }}
            </div>
          </div>

          <div class="flex justify-end space-x-4">
            <Link
              :href="route('exam-time-slots.index')"
              class="px-6 py-3 bg-gray-200 text-gray-800 font-semibold rounded-lg hover:bg-gray-300 transition-colors"
            >
              Cancel
            </Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 transition-colors duration-300 disabled:opacity-50"
            >
              <span v-if="form.processing">Creating...</span>
              <span v-else>Create Slot</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>


