<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, watchEffect } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
  classes: Array,
  subjects: Array,
  teachers: Array,
  sessions: Array,
  sections: Array,
  groups: Array,
});

const flash = computed(() => usePage().props.flash || {});

// Find IDs for special classes and group "None"
const class9Id = ref(null);
const class10Id = ref(null);
const noneGroupId = ref(null);

watchEffect(() => {
  if (props.classes.length > 0) {
    class9Id.value = props.classes.find(cls => cls.class_name === 'Class 9')?.id || null;
    class10Id.value = props.classes.find(cls => cls.class_name === 'Class 10')?.id || null;
  }
  if (props.groups.length > 0) {
    noneGroupId.value = props.groups.find(g => g.name === 'None')?.id || null;
  }
});

const form = useForm({
  class_name_id: '',
  subject_id: '',
  teacher_id: '',
  session_id: '',
  section_id: '',
  group_id: '',
  status: 0,
});

const isClass9Or10 = computed(() => {
  return form.class_name_id === class9Id.value || form.class_name_id === class10Id.value;
});

// Automatically adjust group selection based on selected class
watch(
  () => form.class_name_id,
  (newClassId) => {
    if (!newClassId) {
      form.group_id = '';
      return;
    }
    if (!isClass9Or10.value) {
      // When class is not 9 or 10, force group to 'None'
      form.group_id = noneGroupId.value || '';
    } else {
      // For classes 9 or 10, clear group_id to force explicit selection
      if (!props.groups.some(g => g.id === form.group_id && g.name !== 'None')) {
        form.group_id = '';
      }
    }
  },
  { immediate: true },
);

const submit = () => {
  const dataToSend = { ...form.data() };
  // Convert empty teacher_id or group_id to null for backend compatibility
  dataToSend.teacher_id = dataToSend.teacher_id === '' ? null : dataToSend.teacher_id;
  dataToSend.group_id = dataToSend.group_id === '' ? null : dataToSend.group_id;

  form.post(route('class-subjects.store'), {
    data: dataToSend,
    onSuccess: () => {
      form.reset();
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Class subject assigned successfully.',
        toast: true,
        position: 'top-end',
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false,
      });
    },
    onError: errors => {
      console.error('Class Subject creation failed:', errors);
    },
  });
};

// Show flash messages from server redirects
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
  <Head title="Assign New Class Subject" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Assign New Class Subject</h2>
    </template>
    <div class="container-fluid py-4">
      <div class="card shadow-sm rounded-lg">
        <div class="card-body p-4">
          <form @submit.prevent="submit" class="row g-3">
            <div class="col-md-6">
              <InputLabel for="class_name_id" value="Select Class" class="form-label" />
              <select id="class_name_id" v-model="form.class_name_id" class="form-select" required>
                <option value="" disabled>-- Select Class --</option>
                <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
              </select>
              <InputError :message="form.errors.class_name_id" class="mt-2" />
            </div>

            <div class="col-md-6">
              <InputLabel for="subject_id" value="Select Subject" class="form-label" />
              <select id="subject_id" v-model="form.subject_id" class="form-select" required>
                <option value="" disabled>-- Select Subject --</option>
                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                  {{ subject.name }} ({{ subject.code || 'N/A' }})
                </option>
              </select>
              <InputError :message="form.errors.subject_id" class="mt-2" />
            </div>

            <div class="col-md-6">
              <InputLabel for="teacher_id" value="Select Teacher (Optional)" class="form-label" />
              <select id="teacher_id" v-model="form.teacher_id" class="form-select">
                <option value="">-- Select Teacher --</option>
                <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                  {{ teacher.name }} (Subject: {{ teacher.subject_taught || 'N/A' }})
                </option>
              </select>
              <InputError :message="form.errors.teacher_id" class="mt-2" />
            </div>

            <div class="col-md-6">
              <InputLabel for="session_id" value="Select Session" class="form-label" />
              <select id="session_id" v-model="form.session_id" class="form-select" required>
                <option value="" disabled>-- Select Session --</option>
                <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
              </select>
              <InputError :message="form.errors.session_id" class="mt-2" />
            </div>

            <div class="col-md-6">
              <InputLabel for="section_id" value="Select Section" class="form-label" />
              <select id="section_id" v-model="form.section_id" class="form-select" required>
                <option value="" disabled>-- Select Section --</option>
                <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
              </select>
              <InputError :message="form.errors.section_id" class="mt-2" />
            </div>

            <div class="col-md-6">
              <InputLabel for="group_id" value="Select Group" class="form-label" />
              <select
                id="group_id"
                v-model="form.group_id"
                :disabled="!isClass9Or10 && noneGroupId !== null"
                :required="isClass9Or10"
                class="form-select"
              >
                <option v-if="isClass9Or10" value="" disabled>-- Select Group --</option>
                <option v-else-if="noneGroupId !== null" :value="noneGroupId">-- None --</option>
                <option v-else disabled>-- No Groups Available --</option>

                <template v-if="isClass9Or10">
                  <option v-for="group in groups.filter(g => g.name !== 'None')" :key="group.id" :value="group.id">
                    {{ group.name }}
                  </option>
                </template>
              </select>
              <InputError :message="form.errors.group_id" class="mt-2" />
            </div>

            <div class="col-md-6">
              <InputLabel for="status" value="Status" class="form-label" />
              <select id="status" v-model.number="form.status" class="form-select" required>
                <option :value="0">Active</option>
                <option :value="1">Inactive</option>
              </select>
              <InputError :message="form.errors.status" class="mt-2" />
            </div>

            <div class="d-flex justify-content-end mt-4 col-12">
              <Link :href="route('class-subjects.index')" class="btn btn-secondary me-3">Cancel</Link>
              <PrimaryButton :disabled="form.processing" :class="{ 'opacity-75': form.processing }" class="btn btn-primary">
                Assign Subject
              </PrimaryButton>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
