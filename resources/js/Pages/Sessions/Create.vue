<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({ errors: Object });
const flash = computed(() => usePage().props.flash || {});
const form = useForm({
  name: '',
  status: 0,
});

const submit = () => {
  form.post(route('sessions.store'), {
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Session created successfully.',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
      form.reset();
    },
    onError: (errors) => console.error('Session creation failed:', errors),
  });
};

watchEffect(() => {
  if (flash.value && flash.value.message) {
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
  <Head title="Create New Session" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Create New Session</h2>
    </template>

    <div class="max-w-3xl mx-auto p-6 bg-white rounded-lg shadow-md dark:bg-gray-800">
      <form @submit.prevent="submit" class="space-y-6">
        <div>
          <InputLabel for="name" value="Session Name" class="block mb-2 font-semibold text-gray-700 dark:text-gray-300" />
          <TextInput
            id="name"
            type="text"
            v-model="form.name"
            required
            autofocus
            class="w-full rounded-md border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100"
          />
          <InputError :message="form.errors.name" class="mt-1 text-sm text-red-600" />
        </div>

        <div>
          <InputLabel for="status" value="Status" class="block mb-2 font-semibold text-gray-700 dark:text-gray-300" />
          <select
            id="status"
            v-model.number="form.status"
            required
            class="w-full rounded-md border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:bg-gray-900 dark:border-gray-600 dark:text-gray-100"
          >
            <option :value="0">Active</option>
            <option :value="1">Inactive</option>
          </select>
          <InputError :message="form.errors.status" class="mt-1 text-sm text-red-600" />
        </div>

        <div class="flex justify-end space-x-4">
          <Link
            :href="route('sessions.index')"
            class="px-6 py-2 rounded-md bg-gray-300 text-gray-800 font-semibold hover:bg-gray-400 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
          >
            Cancel
          </Link>
          <PrimaryButton
            :disabled="form.processing"
            :class="form.processing ? 'opacity-75 cursor-not-allowed' : ''"
            class="px-6 py-2 rounded-md bg-indigo-600 text-white font-semibold hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500"
          >
            <span v-if="form.processing">Creating...</span>
            <span v-else>Create Session</span>
          </PrimaryButton>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>
