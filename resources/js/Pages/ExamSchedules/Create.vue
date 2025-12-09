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
    groups: Array, // ⬅️ NEW: Added groups prop
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
    group_id: '', // ⬅️ NEW: Added group_id to the form
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
            <h2 class="font-extrabold text-3xl text-gray-800 leading-tight">
                Exam Scheduling Dashboard
            </h2>
        </template>
        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto bg-white shadow-2xl rounded-xl p-6 sm:p-10">
                <div class="flex flex-col md:flex-row justify-between items-center mb-10 border-b pb-4">
                    <h3 class="text-4xl font-extrabold text-indigo-700 mb-4 md:mb-0">
                        Create New Schedule Slot
                    </h3>
                    <Link
                        :href="route('exam-schedules.index')"
                        class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-full font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Back to List
                    </Link>
                </div>
                <form @submit.prevent="submit" class="space-y-10">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-6 bg-indigo-50/50 border border-indigo-200 rounded-xl shadow-inner">
                        <div>
                            <InputLabel for="exam_date" class="mb-2 text-sm font-bold text-gray-700">
                                Exam Date <span class="text-red-500">*</span>
                            </InputLabel>
                            <TextInput
                                id="exam_date"
                                type="date"
                                class="w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-md focus:border-indigo-500 focus:ring-indigo-500 transition"
                                v-model="form.exam_date"
                                :class="{ 'border-red-500': form.errors.exam_date }"
                                required
                            />
                            <InputError class="mt-1 text-sm text-red-600" :message="form.errors.exam_date" />
                            <p v-if="form.exam_date" class="mt-2 text-sm font-medium text-indigo-600">Day: {{ form.day_of_week }}</p>
                        </div>

                        <div>
                            <InputLabel for="room_id" class="mb-2 text-sm font-bold text-gray-700">
                                Select Room <span class="text-red-500">*</span>
                            </InputLabel>
                            <select
                                id="room_id"
                                v-model="form.room_id"
                                :class="[
                                    'w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-md focus:border-indigo-500 focus:ring-indigo-500 transition',
                                    form.errors.room_id ? 'border-red-500' : ''
                                ]"
                                required
                            >
                                <option value="" disabled>-- Select Room --</option>
                                <option
                                    v-for="room in allRoomsWithStatus"
                                    :key="room.id"
                                    :value="room.id"
                                    :disabled="room.status_text === 'Booked' && room.id !== form.room_id"
                                    :class="room.status_text === 'Booked' && room.id !== form.room_id ? 'bg-red-50 text-gray-500' : 'text-gray-900'"
                                >
                                    {{ room.name }} <span v-if="room.status_text === 'Booked'">(Booked)</span>
                                </option>
                            </select>
                            <InputError class="mt-1 text-sm text-red-600" :message="form.errors.room_id" />
                        </div>
                    </div>
                    <div>
                        <InputLabel class="mb-4 text-xl font-bold text-gray-700">
                            Select Exam Slot <span class="text-red-500">*</span>
                            <span v-if="!form.exam_date || !form.room_id" class="text-sm font-normal text-red-500 ml-2">(Select date and room first)</span>
                        </InputLabel>
                        <div v-if="fetchingResources" class="p-8 bg-gray-100 rounded-xl text-center text-indigo-600 font-semibold animate-pulse shadow-inner">
                            <svg class="inline w-6 h-6 mr-3 text-indigo-500 animate-spin" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M100 50.5908c0 27.6142-22.3858 50-50 50s-50-22.3858-50-50 22.3858-50 50-50 50 22.3858 50 50zm-9.3889 0c0-22.327-18.103-40.4301-40.4301-40.4301S10 28.2638 10 50.5908c0 22.327 18.103 40.4301 40.4301 40.4301s40.4301-18.103 40.4301-40.4301z" fill="currentColor"/>
                                <path d="M93.949 20.8415C87.487 7.7475 74.072 1.0965 59.907 1.0965c-15.65 0-29.355 6.463-39.467 17.618C10.334 33.36 4.907 46.994 4.907 60.5908c0 1.258.118 2.503.351 3.737l-4.102.736c-1.228.22-1.996 1.43-1.777 2.659.22 1.228 1.43 1.996 2.659 1.777l4.102-.736c1.228.22 2.44.118 3.655.118 13.597 0 27.23 5.427 39.467 17.618 10.112 11.155 23.817 17.618 39.467 17.618 14.165 0 27.579-6.651 34.041-19.745l-4.577-2.31c-6.194 12.347-19.658 18.995-34.964 18.995-15.305 0-28.769-6.648-34.964-18.995-5.913-11.808-14.777-22.822-26.689-32.062 11.808 9.24 20.672 20.254 26.585 32.062 6.194 12.347 19.658 18.995 34.964 18.995 15.305 0 28.769-6.648 34.964-18.995L93.949 20.8415z" fill="currentFill"/>
                            </svg>
                            Checking available slots...
                        </div>
                        <div v-else-if="examSlotsWithStatus && examSlotsWithStatus.length > 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                            <button
                                v-for="slot in examSlotsWithStatus"
                                :key="slot.id"
                                type="button"
                                :disabled="slot.status_text === 'Booked'"
                                :class="[
                                    // BASE CLASSES - MOVED FROM LITERAL 'class' TO AVOID DUPLICATE ATTRIBUTE ERROR
                                    'relative rounded-xl border-2 p-5 cursor-pointer transition duration-300 transform hover:scale-[1.02] active:scale-[0.98] flex flex-col items-start shadow-lg',
                                    // DYNAMIC CLASSES
                                    form.exam_slot_id === slot.id && slot.status_text !== 'Booked'
                                        ? 'bg-indigo-600 border-indigo-700 text-white ring-4 ring-indigo-300 shadow-indigo-400/50'
                                        : 'bg-white border-gray-200 text-gray-800 hover:bg-gray-50',
                                    slot.status_text === 'Booked' ? 'opacity-50 cursor-not-allowed bg-red-50 hover:bg-red-50 pointer-events-none' : ''
                                ]"
                                @click="selectExamSlot(slot)"
                            >
                                <h4 class="font-bold text-xl mb-1 truncate">{{ slot.name }}</h4>
                                <p :class="form.exam_slot_id === slot.id ? 'text-indigo-200' : 'text-gray-500'" class="text-sm font-mono truncate">
                                    {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
                                </p>
                                <span
                                    :class="{
                                        'text-white bg-red-600': slot.status_text === 'Booked',
                                        'text-white bg-green-600': slot.status_text === 'Available' && form.exam_slot_id === slot.id,
                                        'text-green-700 bg-green-100': slot.status_text === 'Available' && form.exam_slot_id !== slot.id,
                                    }"
                                    class="absolute top-2 right-2 px-2 py-0.5 text-xs font-semibold rounded-full uppercase select-none"
                                >
                                    {{ slot.status_text }}
                                </span>
                                <svg v-if="form.exam_slot_id === slot.id" class="absolute top-1 left-1 w-5 h-5 text-yellow-300" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            </button>
                        </div>
                        <div v-else class="p-8 bg-gray-100 rounded-xl text-center text-gray-500 shadow-inner">
                            Please select an **Exam Date** and **Room** to check slot availability.
                        </div>
                        <InputError class="mt-2 text-sm text-red-600" :message="form.errors.exam_slot_id" />
                    </div>

                    <div class="p-6 bg-gray-50 border border-gray-200 rounded-xl shadow-inner">
                        <InputLabel for="teacher_id" class="mb-2 text-sm font-bold text-gray-700">
                            Select Teacher/Invigilator <span class="text-red-500">*</span>
                        </InputLabel>
                        <select
                            id="teacher_id"
                            v-model="form.teacher_id"
                            :disabled="!form.exam_slot_id || fetchingResources"
                            :class="[
                                'w-full rounded-lg border-gray-300 text-gray-900 shadow-md focus:border-indigo-500 focus:ring-indigo-500 transition',
                                !form.exam_slot_id ? 'bg-gray-200 cursor-not-allowed' : 'bg-white',
                                form.errors.teacher_id ? 'border-red-500' : ''
                            ]"
                            required
                        >
                            <option value="" disabled>
                                {{ !form.exam_slot_id ? 'Select an Exam Slot first' : '-- Select Teacher --' }}
                            </option>
                            <option
                                v-for="teacher in allTeachersWithStatus"
                                :key="teacher.id"
                                :value="teacher.id"
                                :disabled="teacher.status_text === 'Booked' && teacher.id !== form.teacher_id"
                                :class="teacher.status_text === 'Booked' && teacher.id !== form.teacher_id ? 'bg-red-50 text-gray-500' : 'text-gray-900'"
                            >
                                {{ teacher.name }} <span v-if="teacher.subject_taught" class="text-xs italic">({{ teacher.subject_taught }})</span>
                                <span v-if="teacher.status_text === 'Booked'">(Unavailable)</span>
                            </option>
                        </select>
                        <InputError class="mt-1 text-sm text-red-600" :message="form.errors.teacher_id" />
                        <p v-if="fetchingResources" class="mt-1 text-xs text-indigo-500 italic flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Checking teacher availability...
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 p-6 bg-gray-50 border border-gray-200 rounded-xl shadow-inner">
                        <div>
                            <InputLabel for="exam_id" class="block mb-2 text-sm font-bold text-gray-700">
                                Select Exam <span class="text-red-500">*</span>
                            </InputLabel>
                            <select
                                id="exam_id"
                                v-model="form.exam_id"
                                :class="[
                                    'w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-md focus:border-indigo-500 focus:ring-indigo-500 transition',
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
                            <InputLabel for="class_id" class="block mb-2 text-sm font-bold text-gray-700">
                                Select Class <span class="text-red-500">*</span>
                            </InputLabel>
                            <select
                                id="class_id"
                                v-model="form.class_id"
                                :class="[
                                    'w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-md focus:border-indigo-500 focus:ring-indigo-500 transition',
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
                            <InputLabel for="session_id" class="block mb-2 text-sm font-bold text-gray-700">
                                Select Session <span class="text-red-500">*</span>
                            </InputLabel>
                            <select
                                id="session_id"
                                v-model="form.session_id"
                                :class="[
                                    'w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-md focus:border-indigo-500 focus:ring-indigo-500 transition',
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
                            <InputLabel for="section_id" class="block mb-2 text-sm font-bold text-gray-700">
                                Select Section <span class="text-red-500">*</span>
                            </InputLabel>
                            <select
                                id="section_id"
                                v-model="form.section_id"
                                :class="[
                                    'w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-md focus:border-indigo-500 focus:ring-indigo-500 transition',
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
                            <InputLabel for="group_id" class="block mb-2 text-sm font-bold text-gray-700">
                                Select Group
                            </InputLabel>
                            <select
                                id="group_id"
                                v-model="form.group_id"
                                :class="[
                                    'w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-md focus:border-indigo-500 focus:ring-indigo-500 transition',
                                    form.errors.group_id ? 'border-red-500' : ''
                                ]"
                            >
                                <option value="">-- Select Group (Optional) --</option>
                                <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                            </select>
                            <InputError class="mt-1 text-sm text-red-600" :message="form.errors.group_id" />
                        </div>
                        <div>
                            <InputLabel for="subject_id" class="block mb-2 text-sm font-bold text-gray-700">
                                Select Subject <span class="text-red-500">*</span>
                            </InputLabel>
                            <select
                                id="subject_id"
                                v-model="form.subject_id"
                                :class="[
                                    'w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-md focus:border-indigo-500 focus:ring-indigo-500 transition',
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
                            <InputLabel for="status" class="block mb-2 text-sm font-bold text-gray-700">
                                Status <span class="text-red-500">*</span>
                            </InputLabel>
                            <select
                                id="status"
                                v-model.number="form.status"
                                :class="[
                                    'w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-md focus:border-indigo-500 focus:ring-indigo-500 transition',
                                    form.errors.status ? 'border-red-500' : ''
                                ]"
                                required
                            >
                                <option :value="0">Active</option>
                                <option :value="1">Inactive</option>
                                </select>
                            <InputError class="mt-1 text-sm text-red-600" :message="form.errors.status" />
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <PrimaryButton
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                            :disabled="form.processing"
                            class="bg-indigo-600 text-white font-extrabold rounded-full px-10 py-3 shadow-xl hover:bg-indigo-700 transition transform hover:scale-[1.01] active:scale-[0.99] focus:outline-none focus:ring-4 focus:ring-indigo-500/50"
                        >
                            <span v-if="form.processing" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Creating...
                            </span>
                            <span v-else>Create Exam Schedule</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>