<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, watch, computed, watchEffect } from 'vue';

const props = defineProps({
    examSchedule: Object, // The exam schedule object to be edited
    exams: Array,
    classes: Array,
    sections: Array,
    sessions: Array,
    teachers: Array,
    subjects: Array,
    rooms: Array,
});

const flash = computed(() => usePage().props.flash || {});

// Initialize form with existing exam schedule data
const form = useForm({
    exam_id: props.examSchedule.exam_id,
    class_id: props.examSchedule.class_id,
    section_id: props.examSchedule.section_id,
    session_id: props.examSchedule.session_id,
    teacher_id: props.examSchedule.teacher_id,
    subject_id: props.examSchedule.subject_id,
    room_id: props.examSchedule.room_id,
    exam_date: props.examSchedule.exam_date,
    start_time: props.examSchedule.start_time,
    end_time: props.examSchedule.end_time,
    day_of_week: props.examSchedule.day_of_week,
    status: props.examSchedule.status,
});

// Reactive state for available rooms and teachers based on date/time selection
const availableRooms = ref([]);
const availableTeachers = ref([]);
const fetchingResources = ref(false);

// Map initial teachers and rooms to include a property for display if they are booked
// This assumes all props.teachers and props.rooms are initially 'available'
const allRoomsWithStatus = ref([]);
const allTeachersWithStatus = ref([]);

// Function to calculate day of week
const getDayOfWeek = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const days = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];
    return days[date.getDay()];
};

// Watch for changes in exam_date, start_time, end_time to update day_of_week and fetch available resources
watch([() => form.exam_date, () => form.start_time, () => form.end_time], ([newDate, newStartTime, newEndTime]) => {
    // Update day_of_week automatically
    form.day_of_week = getDayOfWeek(newDate);

    // Fetch available resources if all time-related fields are filled
    if (newDate && newStartTime && newEndTime) {
        fetchAvailableResources();
    } else {
        // Reset availability states if fields are not fully filled
        availableRooms.value = [];
        availableTeachers.value = [];
        // Re-map all original rooms/teachers with default 'Available' status
        allRoomsWithStatus.value = props.rooms.map(room => ({ ...room, status_text: 'Available' }));
        allTeachersWithStatus.value = props.teachers.map(teacher => ({ ...teacher, status_text: 'Available' }));
    }
}, { immediate: true }); // Run immediately on component mount to populate initial state

// Function to fetch available rooms and teachers
const fetchAvailableResources = async () => {
    fetchingResources.value = true;
    try {
        const response = await axios.get(route('exam-schedules.available-resources'), {
            params: {
                exam_date: form.exam_date,
                start_time: form.start_time,
                end_time: form.end_time,
                exclude_schedule_id: props.examSchedule.id, // Exclude current schedule from conflict check
            }
        });

        const { availableRooms: fetchedAvailableRooms, availableTeachers: fetchedAvailableTeachers } = response.data;

        // Reset all statuses to default 'Available' first using the full list from props
        allRoomsWithStatus.value = props.rooms.map(room => ({ ...room, status_text: 'Available' }));
        allTeachersWithStatus.value = props.teachers.map(teacher => ({ ...teacher, status_text: 'Available' }));

        // Update status for booked rooms. Check against original full list of rooms.
        allRoomsWithStatus.value = allRoomsWithStatus.value.map(room => {
            // If this room's ID is NOT found in the `fetchedAvailableRooms` list, it means it's booked.
            if (!fetchedAvailableRooms.some(ar => ar.id === room.id)) {
                return { ...room, status_text: 'Ongoing' };
            }
            return room;
        });

        // Update status for booked teachers. Check against original full list of teachers.
        allTeachersWithStatus.value = allTeachersWithStatus.value.map(teacher => {
            // If this teacher's ID is NOT found in the `fetchedAvailableTeachers` list, it means they're booked.
            if (!fetchedAvailableTeachers.some(at => at.id === teacher.id)) {
                return { ...teacher, status_text: 'Ongoing' };
            }
            return teacher;
        });

        // Store the raw available lists (optional, if you want to use them for actual filtering later)
        availableRooms.value = fetchedAvailableRooms;
        availableTeachers.value = fetchedAvailableTeachers;

    } catch (error) {
        console.error("Error fetching available resources:", error);
        // Optionally display an error message
    } finally {
        fetchingResources.value = false;
    }
};

const submit = () => {
    form.put(route('exam-schedules.update', props.examSchedule.id), {
        onSuccess: () => {
            // Flash messages handled by watchEffect
        },
        onError: (errors) => {
            console.error("Exam schedule update failed:", errors);
        },
    });
};

// Watch for flash messages and display SweetAlert
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
    <Head :title="`Edit Exam Schedule: ${examSchedule.subject?.name || 'N/A'}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Exam Schedule</h2>
        </template>

        <div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Exam Dropdown -->
                            <div>
                                <InputLabel for="exam_id" value="Exam" />
                                <select id="exam_id" v-model="form.exam_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                    <option value="">Select Exam</option>
                                    <option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.exam_name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.exam_id" />
                            </div>

                            <!-- Class Dropdown -->
                            <div>
                                <InputLabel for="class_id" value="Class" />
                                <select id="class_id" v-model="form.class_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                    <option value="">Select Class</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.class_id" />
                            </div>

                            <!-- Section Dropdown -->
                            <div>
                                <InputLabel for="section_id" value="Section" />
                                <select id="section_id" v-model="form.section_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                    <option value="">Select Section</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.section_id" />
                            </div>

                            <!-- Session Dropdown -->
                            <div>
                                <InputLabel for="session_id" value="Session" />
                                <select id="session_id" v-model="form.session_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                    <option value="">Select Session</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.session_id" />
                            </div>

                            <!-- Subject Dropdown -->
                            <div>
                                <InputLabel for="subject_id" value="Subject" />
                                <select id="subject_id" v-model="form.subject_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                    <option value="">Select Subject</option>
                                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.subject_id" />
                            </div>

                            <!-- Room Dropdown with availability -->
                            <div>
                                <InputLabel for="room_id" value="Room" />
                                <select id="room_id" v-model="form.room_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" :disabled="fetchingResources" required>
                                    <option value="">Select Room</option>
                                    <option v-if="fetchingResources" disabled>Loading rooms...</option>
                                    <option v-else v-for="room in allRoomsWithStatus" :key="room.id" :value="room.id" :disabled="room.status_text === 'Ongoing'">
                                        {{ room.name }} <span v-if="room.status_text === 'Ongoing'" class="text-red-500">(Ongoing)</span>
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.room_id" />
                            </div>

                            <!-- Teacher Dropdown with availability -->
                            <div>
                                <InputLabel for="teacher_id" value="Teacher" />
                                <select id="teacher_id" v-model="form.teacher_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" :disabled="fetchingResources" required>
                                    <option value="">Select Teacher</option>
                                    <option v-if="fetchingResources" disabled>Loading teachers...</option>
                                    <option v-else v-for="teacher in allTeachersWithStatus" :key="teacher.id" :value="teacher.id" :disabled="teacher.status_text === 'Ongoing'">
                                        {{ teacher.name }} <span v-if="teacher.status_text === 'Ongoing'" class="text-red-500">(Ongoing)</span>
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.teacher_id" />
                            </div>

                            <!-- Exam Date -->
                            <div>
                                <InputLabel for="exam_date" value="Exam Date" />
                                <TextInput id="exam_date" type="date" class="mt-1 block w-full" v-model="form.exam_date" required />
                                <InputError class="mt-2" :message="form.errors.exam_date" />
                            </div>

                            <!-- Start Time -->
                            <div>
                                <InputLabel for="start_time" value="Start Time" />
                                <TextInput id="start_time" type="time" class="mt-1 block w-full" v-model="form.start_time" required />
                                <InputError class="mt-2" :message="form.errors.start_time" />
                            </div>

                            <!-- End Time -->
                            <div>
                                <InputLabel for="end_time" value="End Time" />
                                <TextInput id="end_time" type="time" class="mt-1 block w-full" v-model="form.end_time" required />
                                <InputError class="mt-2" :message="form.errors.end_time" />
                            </div>

                            <!-- Day of Week (Auto-filled) -->
                            <div>
                                <InputLabel for="day_of_week" value="Day of Week" />
                                <TextInput id="day_of_week" type="text" class="mt-1 block w-full bg-gray-100" v-model="form.day_of_week" readonly />
                                <InputError class="mt-2" :message="form.errors.day_of_week" />
                            </div>

                            <!-- Status Dropdown -->
                            <div>
                                <InputLabel for="status" value="Status" />
                                <select id="status" v-model="form.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                    <option :value="0">Active</option>
                                    <option :value="1">Canceled</option>
                                    <option :value="2">Rescheduled</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6 space-x-4">
                            <Link :href="route('exam-schedules.index')" class="text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Update Schedule
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
