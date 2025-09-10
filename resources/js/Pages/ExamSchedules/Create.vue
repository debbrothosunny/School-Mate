<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, watchEffect } from 'vue';
import axios from 'axios';

const props = defineProps({
  exams: Array,
  classes: Array,
  sections: Array,
  sessions: Array,
  teachers: Array,
  subjects: Array,
  rooms: Array,
  exam_slots: Array,
  flash: Object,
});

const flash = computed(() => usePage().props.flash || {});

const form = useForm({
  exam_id: '',
  class_id: '',
  section_id: '',
  session_id: '',
  teacher_id: '',
  subject_id: '',
  room_id: '',
  exam_date: '',
  exam_slot_id: '',
  status: 0,
  day_of_week: '',
});

// Reactive state for tracking loading status and resources
const fetchingResources = ref(false);
const allRoomsWithStatus = ref([]);
const allTeachersWithStatus = ref([]);
const examSlotsWithStatus = ref([]);

// Initialize rooms, teachers, and exam slots with a default 'Available' status on load
watchEffect(() => {
  if (props.rooms && props.rooms.length > 0) {
    allRoomsWithStatus.value = props.rooms.map(room => ({ ...room, status_text: 'Available' }));
  }
  if (props.teachers && props.teachers.length > 0) {
    allTeachersWithStatus.value = props.teachers.map(teacher => ({ ...teacher, status_text: 'Available' }));
  }
  if (props.exam_slots && props.exam_slots.length > 0) {
    examSlotsWithStatus.value = props.exam_slots.map(slot => ({ ...slot, status_text: 'Available' }));
  }
});

// Function to calculate day of week
const getDayOfWeek = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  const days = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];
  return days[date.getDay()];
};

// Helper function to format a 24-hour time string to 12-hour with AM/PM
const formatTime = (timeString) => {
  if (!timeString) return '';
  const [hours, minutes] = timeString.split(':');
  let h = parseInt(hours, 10);
  const ampm = h >= 12 ? 'PM' : 'AM';
  h = h % 12;
  h = h ? h : 12; // The hour '0' should be '12'
  return `${h}:${minutes} ${ampm}`;
};

// Watch for changes in date and room to fetch available time slots
watch([() => form.exam_date, () => form.room_id], async ([newDate, newRoomId]) => {
  form.day_of_week = getDayOfWeek(newDate);
  form.exam_slot_id = '';
  allTeachersWithStatus.value = props.teachers.map(teacher => ({ ...teacher, status_text: 'Available' }));
  if (newDate && newRoomId) {
    await fetchAvailableSlotsForRoom();
  } else {
    examSlotsWithStatus.value = props.exam_slots.map(slot => ({ ...slot, status_text: 'Available' }));
  }
});

// Watch for changes in selected exam_slot_id to fetch available teachers
watch(() => form.exam_slot_id, async (newSlotId) => {
  allTeachersWithStatus.value = props.teachers.map(teacher => ({ ...teacher, status_text: 'Available' }));
  if (form.exam_date && newSlotId) {
    await fetchAvailableTeachers();
  }
});

// Fetch available slots for selected date and room
const fetchAvailableSlotsForRoom = async () => {
  if (!form.exam_date || !form.room_id) return;
  fetchingResources.value = true;
  try {
    const fetchPromises = props.exam_slots.map(slot =>
      axios.get(route('exam-schedules.available-resources'), {
        params: { exam_date: form.exam_date, exam_slot_id: slot.id },
      })
    );
    const responses = await Promise.all(fetchPromises);
    examSlotsWithStatus.value = props.exam_slots.map((slot, index) => {
      const responseData = responses[index].data;
      const isBooked = responseData.occupiedRoomIds.includes(form.room_id);
      return {
        ...slot,
        status_text: isBooked ? 'Booked' : 'Available',
      };
    });
  } catch (error) {
    console.error('Error fetching available slots for room:', error);
    examSlotsWithStatus.value = props.exam_slots.map(slot => ({ ...slot, status_text: 'Available' }));
  } finally {
    fetchingResources.value = false;
  }
};

// Fetch available teachers for specific date and slot
const fetchAvailableTeachers = async () => {
  if (!form.exam_date || !form.exam_slot_id) return;
  fetchingResources.value = true;
  try {
    const response = await axios.get(route('exam-schedules.available-resources'), {
      params: { exam_date: form.exam_date, exam_slot_id: form.exam_slot_id },
    });
    const { occupiedTeacherIds } = response.data;
    allTeachersWithStatus.value = props.teachers.map(teacher => ({
      ...teacher,
      status_text: occupiedTeacherIds.includes(teacher.id) ? 'Booked' : 'Available',
    }));
  } catch (error) {
    console.error('Error fetching available teachers:', error);
    allTeachersWithStatus.value = props.teachers.map(teacher => ({ ...teacher, status_text: 'Available' }));
  } finally {
    fetchingResources.value = false;
  }
};

const selectExamSlot = (slot) => {
  form.exam_slot_id = slot.id;
};

const submit = () => {
  form.post(route('exam-schedules.store'), {
    onSuccess: () => {
      form.reset();
      allRoomsWithStatus.value = props.rooms.map(room => ({ ...room, status_text: 'Available' }));
      allTeachersWithStatus.value = props.teachers.map(teacher => ({ ...teacher, status_text: 'Available' }));
      examSlotsWithStatus.value = props.exam_slots.map(slot => ({ ...slot, status_text: 'Available' }));
    },
    onError: (errors) => {
      console.error('Exam schedule creation failed:', errors);
    },
  });
};

watchEffect(() => {
  if (flash.value && flash.value.message) {
    if (flash.value.type === 'success') {
      console.log('Success:', flash.value.message);
    } else {
      console.error('Error:', flash.value.message);
    }
  }
});
</script>

<template>
  <AuthenticatedLayout>
    <Head title="Create Exam Schedule" />
    <template #header>
      <h2 class="text-2xl font-semibold text-gray-900">Create New Exam Schedule</h2>
    </template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-10 px-6">
      <div class="bg-white shadow-xl rounded-2xl max-w-5xl w-full p-8">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8">
          <h3 class="text-3xl font-extrabold text-gray-800 mb-4 md:mb-0">Add Exam Schedule Slot</h3>
          <Link
            :href="route('exam-schedules.index')"
            class="inline-block bg-gray-200 px-6 py-2 rounded-lg text-gray-700 font-semibold hover:bg-gray-300 transition"
          >
            Back to Exam Schedules
          </Link>
        </div>
        <form @submit.prevent="submit" class="space-y-8">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <InputLabel for="exam_date" value="Exam Date" class="block mb-2 text-sm font-medium text-gray-700" />
              <TextInput
                id="exam_date"
                type="date"
                class="w-full rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:ring-2 focus:ring-blue-500 transition"
                v-model="form.exam_date"
                :class="{ 'border-red-500': form.errors.exam_date }"
                required
              />
              <InputError class="mt-1 text-sm text-red-600" :message="form.errors.exam_date" />
            </div>
            <div>
              <InputLabel for="room_id" value="Select Room" class="block mb-2 text-sm font-medium text-gray-700" />
              <select
                id="room_id"
                v-model="form.room_id"
                :class="[
                  'w-full rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:ring-2 focus:ring-blue-500 transition',
                  form.errors.room_id ? 'border-red-500' : ''
                ]"
                required
              >
                <option value="" disabled>-- Select Room --</option>
                <option
                  v-for="room in allRoomsWithStatus"
                  :key="room.id"
                  :value="room.id"
                  :disabled="room.status_text === 'Booked'"
                  :class="room.status_text === 'Booked' ? 'text-gray-400' : ''"
                >
                  {{ room.name }} <span v-if="room.status_text === 'Booked'">(Booked)</span>
                </option>
              </select>
              <InputError class="mt-1 text-sm text-red-600" :message="form.errors.room_id" />
            </div>
          </div>

          <div>
            <InputLabel value="Select Exam Slot" class="block mb-2 text-sm font-medium text-gray-700" />
            <div v-if="fetchingResources" class="p-6 bg-gray-100 rounded-lg text-center text-gray-500 animate-pulse">Loading time slots...</div>
            <div v-else-if="examSlotsWithStatus && examSlotsWithStatus.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-4">
              <button
                v-for="slot in examSlotsWithStatus"
                :key="slot.id"
                type="button"
                class="relative rounded-lg border p-4 cursor-pointer transition duration-300 flex flex-col"
                :class="[
                  form.exam_slot_id === slot.id ? 'bg-blue-600 border-blue-500 text-white shadow-lg' : 'bg-gray-100 border-transparent hover:bg-gray-200',
                  slot.status_text === 'Booked' && form.exam_slot_id !== slot.id ? 'opacity-60 cursor-not-allowed pointer-events-none' : ''
                ]"
                @click="selectExamSlot(slot)"
              >
                <h4 class="font-semibold text-lg mb-1 truncate">{{ slot.name }}</h4>
                <p class="text-sm truncate">Time: {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}</p>
                <p
                  :class="{
                    'text-red-600': slot.status_text === 'Booked',
                    'text-green-700': slot.status_text === 'Available'
                  }"
                  class="absolute top-2 right-3 text-xs font-semibold uppercase select-none"
                >
                  {{ slot.status_text }}
                </p>
              </button>
            </div>
            <div v-else class="p-6 bg-gray-100 rounded-lg text-center text-gray-500">No exam slots available.</div>
            <InputError class="mt-2 text-sm text-red-600" :message="form.errors.exam_slot_id" />
          </div>

          <div>
            <InputLabel for="teacher_id" value="Select Teacher" class="block mb-2 text-sm font-medium text-gray-700" />
            <select
              id="teacher_id"
              v-model="form.teacher_id"
              :disabled="!form.exam_slot_id"
              :class="[
                'w-full rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:ring-2 focus:ring-blue-500 transition',
                form.errors.teacher_id ? 'border-red-500' : ''
              ]"
              required
            >
              <option value="" disabled>-- Select Teacher --</option>
              <option
                v-for="teacher in allTeachersWithStatus"
                :key="teacher.id"
                :value="teacher.id"
                :disabled="teacher.status_text === 'Booked'"
                :class="teacher.status_text === 'Booked' ? 'text-gray-400' : ''"
              >
                {{ teacher.name }} <span v-if="teacher.subject_taught">({{ teacher.subject_taught }})</span>
                <span v-if="teacher.status_text === 'Booked'">(Booked)</span>
              </option>
            </select>
            <InputError class="mt-1 text-sm text-red-600" :message="form.errors.teacher_id" />
            <p v-if="fetchingResources" class="mt-1 text-xs text-gray-500 italic">Checking teacher availability...</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6 mt-6">
            <div>
              <InputLabel for="exam_id" value="Select Exam" class="block mb-2 text-sm font-medium text-gray-700" />
              <select
                id="exam_id"
                v-model="form.exam_id"
                :class="[
                  'w-full rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:ring-2 focus:ring-blue-500 transition',
                  form.errors.exam_id ? 'border-red-500' : ''
                ]"
                required
              >
                <option value="">-- Select Exam --</option>
                <option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.exam_name }}</option>
              </select>
              <InputError class="mt-1 text-sm text-red-600" :message="form.errors.exam_id" />
            </div>
            <div>
              <InputLabel for="class_id" value="Select Class" class="block mb-2 text-sm font-medium text-gray-700" />
              <select
                id="class_id"
                v-model="form.class_id"
                :class="[
                  'w-full rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:ring-2 focus:ring-blue-500 transition',
                  form.errors.class_id ? 'border-red-500' : ''
                ]"
                required
              >
                <option value="" disabled>-- Select Class --</option>
                <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
              </select>
              <InputError class="mt-1 text-sm text-red-600" :message="form.errors.class_id" />
            </div>
            <div>
              <InputLabel for="session_id" value="Select Session" class="block mb-2 text-sm font-medium text-gray-700" />
              <select
                id="session_id"
                v-model="form.session_id"
                :class="[
                  'w-full rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:ring-2 focus:ring-blue-500 transition',
                  form.errors.session_id ? 'border-red-500' : ''
                ]"
                required
              >
                <option value="" disabled>-- Select Session --</option>
                <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
              </select>
              <InputError class="mt-1 text-sm text-red-600" :message="form.errors.session_id" />
            </div>
            <div>
              <InputLabel for="section_id" value="Select Section" class="block mb-2 text-sm font-medium text-gray-700" />
              <select
                id="section_id"
                v-model="form.section_id"
                :class="[
                  'w-full rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:ring-2 focus:ring-blue-500 transition',
                  form.errors.section_id ? 'border-red-500' : ''
                ]"
                required
              >
                <option value="" disabled>-- Select Section --</option>
                <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
              </select>
              <InputError class="mt-1 text-sm text-red-600" :message="form.errors.section_id" />
            </div>
            <div>
              <InputLabel for="subject_id" value="Select Subject" class="block mb-2 text-sm font-medium text-gray-700" />
              <select
                id="subject_id"
                v-model="form.subject_id"
                :class="[
                  'w-full rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:ring-2 focus:ring-blue-500 transition',
                  form.errors.subject_id ? 'border-red-500' : ''
                ]"
                required
              >
                <option value="">-- Select Subject --</option>
                <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
              </select>
              <InputError class="mt-1 text-sm text-red-600" :message="form.errors.subject_id" />
            </div>
            <div>
              <InputLabel for="status" value="Status" class="block mb-2 text-sm font-medium text-gray-700" />
              <select
                id="status"
                v-model.number="form.status"
                :class="[
                  'w-full rounded-lg border border-gray-300 bg-gray-50 text-gray-900 focus:ring-2 focus:ring-blue-500 transition',
                  form.errors.status ? 'border-red-500' : ''
                ]"
                required
              >
                <option :value="0">Active</option>
                <option :value="1">Inactive</option>
                <option :value="2">Rescheduled</option>
              </select>
              <InputError class="mt-1 text-sm text-red-600" :message="form.errors.status" />
            </div>
          </div>
          <div class="flex justify-end mt-10">
            <PrimaryButton
              :class="{ 'opacity-70 cursor-not-allowed': form.processing }"
              :disabled="form.processing"
              class="bg-gradient-to-r from-indigo-600 to-blue-600 text-white font-semibold rounded-lg px-8 py-3 hover:from-indigo-700 hover:to-blue-700 transition"
            >
              Create Exam Schedule
            </PrimaryButton>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
