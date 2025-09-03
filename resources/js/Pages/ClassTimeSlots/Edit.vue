<script setup>
import { ref } from 'vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'

const props = defineProps({
  timeSlot: Object,
})

const form = useForm({
  name: props.timeSlot.name,
  start_time: props.timeSlot.start_time,
  end_time: props.timeSlot.end_time,
})

const submit = () => {
  // CORRECTED: Use .put() to match the Laravel route method
  form.put(
    route('class-time-slots.update', props.timeSlot.id),
    {
      onSuccess: () => form.reset('start_time', 'end_time'),
    }
  )
}
</script>

<template>
  <Head :title="`Edit Time Slot: ${timeSlot.name}`" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Edit Time Slot
      </h2>
    </template>

    <div class="container-fluid py-4">
      <div class="card shadow-sm rounded-lg">
        <div class="card-body p-4">

          <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="card-title h5 mb-0">
              Update Time Slot: {{ timeSlot.name }}
            </h3>
            <Link
              :href="route('class-time-slots.index')"
              class="btn btn-secondary btn-sm rounded"
            >
              Back to List
            </Link>
          </div>

          <form @submit.prevent="submit">
            <div class="row g-3">
              <div class="col-md-6">
                <InputLabel
                  for="name"
                  value="Slot Name"
                  class="form-label"
                />
                <TextInput
                  id="name"
                  type="text"
                  class="form-control"
                  v-model="form.name"
                  required
                  autofocus
                  autocomplete="name"
                />
                <InputError
                  class="mt-2"
                  :message="form.errors.name"
                />
              </div>

              <div class="col-md-3">
                <InputLabel
                  for="start_time"
                  value="Start Time"
                  class="form-label"
                />
                <TextInput
                  id="start_time"
                  type="time"
                  class="form-control"
                  v-model="form.start_time"
                  required
                />
                <InputError
                  class="mt-2"
                  :message="form.errors.start_time"
                />
              </div>

              <div class="col-md-3">
                <InputLabel
                  for="end_time"
                  value="End Time"
                  class="form-label"
                />
                <TextInput
                  id="end_time"
                  type="time"
                  class="form-control"
                  v-model="form.end_time"
                  required
                />
                <InputError
                  class="mt-2"
                  :message="form.errors.end_time"
                />
              </div>
            </div>

            <div class="d-flex justify-content-end mt-4">
              <PrimaryButton
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
              >
                Update Time Slot
              </PrimaryButton>
            </div>
          </form>

        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
/* nothing extra needed—type="time" will return "HH:mm" */
</style>