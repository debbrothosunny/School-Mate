<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { useTheme } from '@/Composables/useTheme';

const { isDark } = useTheme();

const props = defineProps({
  section: Object,
});

const form = useForm({
  name: props.section.name,
  status: props.section.status,
});

const submit = () => {
  form.post(route('sections.update', props.section.id), {
    onSuccess: () => {
      // Optional success handling
    },
    onError: (errors) => {
      console.error("Update failed:", errors);
    },
  });
};
</script>

<template>
  <Head title="Edit Section" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl leading-tight"
          :class="isDark ? 'text-gray-100' : 'text-gray-800'">
        Edit Section: {{ section.name }}
      </h2>
    </template>

    <div class="py-6 px-4 sm:px-6 lg:px-8 min-h-screen font-inter transition-colors duration-300"
         :class="isDark ? 'bg-black text-white' : 'bg-gray-100 text-gray-900'">
      <div class="max-w-xl mx-auto overflow-hidden shadow-lg sm:rounded-lg p-8 border"
           :class="isDark ? 'bg-neutral-900 border-gray-700' : 'bg-white border-gray-200'">
        <h3 class="text-2xl font-bold mb-6 text-center"
            :class="isDark ? 'text-gray-100' : 'text-gray-900'">
          Edit Section Details
        </h3>

        <form @submit.prevent="submit" class="space-y-6">
          <div>
            <InputLabel for="name" value="Section Name"
                        :class="isDark ? 'text-gray-300' : 'text-gray-700'" />
            <TextInput
              id="name"
              type="text"
              v-model="form.name"
              required
              autofocus
              placeholder="e.g., Introduction to Programming"
              class="mt-1 block w-full rounded-md shadow-sm"
              :class="isDark
                ? 'bg-neutral-800 text-white placeholder-gray-400 border-gray-600 focus:border-indigo-400 focus:ring-indigo-400'
                : 'bg-gray-50 text-gray-900 placeholder-gray-500 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'"
            />
            <InputError class="mt-2" :message="form.errors.name" />
          </div>

          <div>
            <InputLabel for="status" value="Status"
                        :class="isDark ? 'text-gray-300' : 'text-gray-700'" />
            <select
              id="status"
              v-model="form.status"
              required
              class="mt-1 block w-full rounded-md shadow-sm"
              :class="isDark
                ? 'bg-neutral-800 text-white border-gray-600 focus:border-indigo-400 focus:ring-indigo-400'
                : 'bg-gray-50 text-gray-900 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500'"
            >
              <option :value="0">Active</option>
              <option :value="1">Inactive</option>
            </select>
            <InputError class="mt-2" :message="form.errors.status" />
          </div>

          <div class="flex items-center justify-end mt-8 space-x-4">
            <Link :href="route('sections.index')"
                  class="transition duration-150 ease-in-out font-medium"
                  :class="isDark
                    ? 'text-gray-400 hover:text-white'
                    : 'text-gray-600 hover:text-gray-900'">
              Cancel
            </Link>
            <PrimaryButton
              :class="[
                'shadow-md',
                form.processing ? 'opacity-25' : '',
                isDark
                  ? 'bg-indigo-500 hover:bg-indigo-600 focus:ring-indigo-400'
                  : 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500'
              ]"
              :disabled="form.processing"
            >
              <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
              </svg>
              {{ form.processing ? 'Updating...' : 'Update Section' }}
            </PrimaryButton>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
.font-inter {
  font-family: 'Inter', sans-serif;
}
</style>
