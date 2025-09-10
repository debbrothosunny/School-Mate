<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { defineProps, watchEffect, computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
  className: Object,
  teachers: Array, // Ensure passed from backend
});

const flash = computed(() => usePage().props.flash || {});

// Initialize form with existing class data
const form = useForm({
  class_name: props.className.class_name,
  status: props.className.status,
  total_classes: props.className.total_classes,
  teacher_id: props.className.teacher_id,
});

const submit = () => {
  form.post(route('class-names.update', props.className.id), {
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Updated!',
        text: 'Class updated successfully.',
        toast: true,
        position: 'top-end',
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false,
      });
    },
    onError: (errors) => {
      Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: 'Failed to update class. Please check the form.',
        toast: true,
        position: 'top-end',
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false,
      });
    },
  });
};

// Also watch flash for any backend-set messages after redirect
watchEffect(() => {
  if (flash.value && flash.value.message) {
    Swal.fire({
      icon: flash.value.type === 'success' ? 'success' : 'error',
      title: flash.value.type === 'success' ? 'Success!' : 'Error!',
      text: flash.value.message,
      toast: true,
      position: 'top-end',
      timer: 3000,
      timerProgressBar: true,
      showConfirmButton: false,
    });
  }
});
</script>

<template>
  <Head title="Edit Class" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Class: {{ className.class_name }}</h2>
    </template>
    <div class="py-4 px-4 sm:px-6 lg:px-8">
      <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-4 text-gray-900">
          <h2 class="text-xl font-bold mb-4">Edit Class</h2>
          <form @submit.prevent="submit">
            <div class="mb-4">
              <InputLabel for="class_name" value="Class Name" />
              <TextInput
                id="class_name"
                type="text"
                class="mt-1 block w-full"
                v-model="form.class_name"
                required
                autofocus
              />
              <InputError :message="form.errors.class_name" class="mt-2" />
            </div>

            <div class="mb-4">
              <InputLabel for="teacher" value="Assign Teacher" />
              <select
                id="teacher"
                v-model="form.teacher_id"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                :class="{ 'border-red-500': form.errors.teacher_id }"
              >
                <option :value="null">-- Select a Teacher --</option>
                <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                  {{ teacher.name }}
                </option>
              </select>
              <InputError :message="form.errors.teacher_id" class="mt-2" />
            </div>

            <div class="mb-4">
              <InputLabel for="total_classes" value="Total Classes for this Class" />
              <TextInput
                id="total_classes"
                type="number"
                class="mt-1 block w-full"
                v-model.number="form.total_classes"
                required
                min="0"
              />
              <InputError :message="form.errors.total_classes" class="mt-2" />
            </div>

            <div class="mb-4">
              <InputLabel for="status" value="Status" />
              <select
                id="status"
                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                v-model="form.status"
                required
              >
                <option :value="0">Active</option>
                <option :value="1">Inactive</option>
              </select>
              <InputError :message="form.errors.status" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
              <Link :href="route('class-names.index')" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</Link>
              <PrimaryButton :disabled="form.processing">Update Class</PrimaryButton>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
