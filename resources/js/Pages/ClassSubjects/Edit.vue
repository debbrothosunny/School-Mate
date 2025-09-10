<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, watchEffect } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
  classSubject: Object,
  classes: Array,
  subjects: Array,
  teachers: Array,
  sessions: Array,
  sections: Array,
  groups: Array,
});

const flash = computed(() => usePage().props.flash || {});

// Reactive refs for IDs of special classes and group 'None'
const class9Id = ref(null);
const class10Id = ref(null);
const noneGroupId = ref(null);

watchEffect(() => {
  if (props.classes.length) {
    class9Id.value = props.classes.find(c => c.class_name === 'Class 9')?.id || null;
    class10Id.value = props.classes.find(c => c.class_name === 'Class 10')?.id || null;
  }
  if (props.groups.length) {
    noneGroupId.value = props.groups.find(g => g.name === 'None')?.id || null;
  }
});

const form = useForm({
  _method: 'post', // Use HTTP PUT for update
  class_name_id: props.classSubject.class_name_id,
  subject_id: props.classSubject.subject_id,
  teacher_id: props.classSubject.teacher_id || '',
  session_id: props.classSubject.session_id,
  section_id: props.classSubject.section_id,
  group_id: props.classSubject.group_id || '',
  status: Number(props.classSubject.status),
});

const isClass9Or10 = computed(() => {
  return form.class_name_id === class9Id.value || form.class_name_id === class10Id.value;
});

watch(
  () => form.class_name_id,
  (newClassId) => {
    if (!newClassId) {
      form.group_id = '';
      return;
    }
    if (!isClass9Or10.value) {
      form.group_id = noneGroupId.value || '';
    } else {
      const validGroup = props.groups.some(g => g.id === form.group_id && g.name !== 'None');
      if (!validGroup) form.group_id = '';
    }
  },
  { immediate: true },
);

const submit = () => {
  const dataToSend = { ...form.data() };
  dataToSend.teacher_id = dataToSend.teacher_id === '' ? null : dataToSend.teacher_id;
  dataToSend.group_id = dataToSend.group_id === '' ? null : dataToSend.group_id;

  form.post(route('class-subjects.update', props.classSubject.id), {
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Class subject assignment updated successfully.',
        toast: true,
        position: 'top-end',
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false,
      });
    },
    onError: (errors) => {
      console.error('Class Subject update failed:', errors);
    },
  });
};

watchEffect(() => {
  if (flash.value.message) {
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
    <Head :title="`Edit Class Subject: ${classSubject.className?.class_name} - ${classSubject.subject?.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Class Subject: {{ classSubject.className?.class_name }} - {{ classSubject.subject?.name }}
                <span v-if="classSubject.group?.name && classSubject.group.name !== 'None'"> ({{ classSubject.group.name }})</span>
            </h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title h5 mb-0">Update Class Subject Assignment</h3>
                        <Link :href="route('class-subjects.index')" class="btn btn-secondary btn-sm rounded">
                            Back to Class Subjects
                        </Link>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <InputLabel for="class_name_id" value="Select Class" class="form-label" />
                                <select id="class_name_id" class="form-select" v-model="form.class_name_id" required>
                                    <option value="" disabled>-- Select Class --</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option> <!-- Assuming 'name' for class_name -->
                                </select>
                                <InputError class="mt-2" :message="form.errors.class_name_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="subject_id" value="Select Subject" class="form-label" />
                                <select id="subject_id" class="form-select" v-model="form.subject_id" required>
                                    <option value="" disabled>-- Select Subject --</option>
                                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }} ({{ subject.code || 'N/A' }})</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.subject_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="teacher_id" value="Select Teacher (Optional)" class="form-label" />
                                <select id="teacher_id" class="form-select" v-model="form.teacher_id">
                                    <option value="">-- Select Teacher --</option>
                                    <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                        {{ teacher.name }} (Teaches: {{ teacher.subject_taught || 'N/A' }})
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.teacher_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="session_id" value="Select Session" class="form-label" />
                                <select id="session_id" class="form-select" v-model="form.session_id" required>
                                    <option value="" disabled>-- Select Session --</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.session_id" />
                            </div>

                            <!-- NEW: Select Section Dropdown -->
                            <div class="col-md-6">
                                <InputLabel for="section_id" value="Select Section" class="form-label" />
                                <select id="section_id" class="form-select" v-model="form.section_id" required>
                                    <option value="" disabled>-- Select Section --</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.section_id" />
                            </div>

                            <!-- NEW: Group Selection Field -->
                            <div class="col-md-6">
                                <InputLabel for="group_id" value="Select Group" class="form-label" />
                                <select
                                    id="group_id"
                                    class="form-select"
                                    v-model="form.group_id"
                                    :required="isClass9Or10"
                                    :disabled="!isClass9Or10 && noneGroupId !== null"
                                >
                                    <option value="" disabled v-if="isClass9Or10">-- Select Group --</option>
                                    <option :value="noneGroupId" v-else-if="noneGroupId !== null">-- None --</option>
                                    <option value="" disabled v-else>-- No Groups Available --</option>

                                    <!-- Filter groups based on isClass9Or10 -->
                                    <template v-if="isClass9Or10">
                                        <option v-for="group in groups.filter(g => g.name !== 'None')" :key="group.id" :value="group.id">
                                            {{ group.name }}
                                        </option>
                                    </template>
                                </select>
                                <InputError class="mt-2" :message="form.errors.group_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="status" value="Status" class="form-label" />
                                <select id="status" class="form-select" v-model.number="form.status" required>
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <Link :href="route('class-subjects.index')" class="btn btn-secondary me-3">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="btn btn-primary">
                                Update Assignment
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
