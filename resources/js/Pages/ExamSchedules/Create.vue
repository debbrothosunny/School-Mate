<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, watchEffect } from 'vue';
import axios from 'axios'; // Make sure axios is imported for API calls

const props = defineProps({
    exams: Array, // Your list of exams
    classes: Array, // Your list of classes
    sections: Array, // Your list of sections
    sessions: Array, // Your list of sessions
    teachers: Array, // All available teachers
    subjects: Array, // All available subjects
    rooms: Array, // All available rooms
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
    start_time: '',
    end_time: '',
    status: 0, // Default to Active
    day_of_week: '', // This should be derived from exam_date
});

// Reactive state for tracking loading and updating room/teacher status
const fetchingResources = ref(false);

// Initialize allRoomsWithStatus and allTeachersWithStatus
// with all available props, marked as 'Available' initially.
// This is critical for the dropdowns to render correctly from the start.
const allRoomsWithStatus = ref([]);
const allTeachersWithStatus = ref([]);

// Initialize room and teacher statuses on component load/props change
watchEffect(() => {
    if (props.rooms && props.rooms.length > 0) {
        allRoomsWithStatus.value = props.rooms.map(room => ({ ...room, status_text: 'Available' }));
    }
    if (props.teachers && props.teachers.length > 0) {
        allTeachersWithStatus.value = props.teachers.map(teacher => ({ ...teacher, status_text: 'Available' }));
    }
}, { immediate: true }); // Run immediately to set initial status

// Function to calculate day of week
const getDayOfWeek = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const days = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];
    return days[date.getDay()];
};

// Watch for changes in exam_date, start_time, end_time to trigger real-time availability check
watch([() => form.exam_date, () => form.start_time, () => form.end_time], ([newDate, newStartTime, newEndTime]) => {
    form.day_of_week = getDayOfWeek(newDate); // Update day of week based on selected date

    // Only fetch resources if all necessary fields are filled
    if (newDate && newStartTime && newEndTime) {
        fetchAvailableResources();
    } else {
        // If fields are cleared, reset all to 'Available' status
        allRoomsWithStatus.value = props.rooms.map(room => ({ ...room, status_text: 'Available' }));
        allTeachersWithStatus.value = props.teachers.map(teacher => ({ ...teacher, status_text: 'Available' }));
    }
}, { immediate: true }); // Ensure this runs on initial component load if initial form values are present

// Function to fetch available rooms and teachers from the backend
const fetchAvailableResources = async () => {
    if (!form.exam_date || !form.start_time || !form.end_time) {
        // Reset if any time field is empty, don't make API call
        allRoomsWithStatus.value = props.rooms.map(room => ({ ...room, status_text: 'Available' }));
        allTeachersWithStatus.value = props.teachers.map(teacher => ({ ...teacher, status_text: 'Available' }));
        return;
    }

    fetchingResources.value = true;
    try {
        const response = await axios.get(route('exam-schedules.available-resources'), {
            params: {
                exam_date: form.exam_date,
                start_time: form.start_time,
                end_time: form.end_time,
                // If editing, pass the current exam schedule ID to exclude it from conflict checks:
                // exclude_schedule_id: form.id, // Assuming form.id holds the current schedule's ID if editing
            }
        });

        const { occupiedRoomIds, occupiedTeacherIds } = response.data;
        console.log('Occupied Rooms IDs from backend:', occupiedRoomIds);
        console.log('Occupied Teachers IDs from backend:', occupiedTeacherIds);

        // Reset all statuses to 'Available' first based on original props data
        let updatedRooms = props.rooms.map(room => ({ ...room, status_text: 'Available' }));
        let updatedTeachers = props.teachers.map(teacher => ({ ...teacher, status_text: 'Available' }));

        // Mark as 'Ongoing' if their ID is in the occupied list
        allRoomsWithStatus.value = updatedRooms.map(room => {
            if (occupiedRoomIds.includes(room.id)) {
                return { ...room, status_text: 'Ongoing' };
            }
            return room;
        });

        allTeachersWithStatus.value = updatedTeachers.map(teacher => {
            if (occupiedTeacherIds.includes(teacher.id)) {
                return { ...teacher, status_text: 'Ongoing' };
            }
            return teacher;
        });

    } catch (error) {
        console.error("Error fetching available resources:", error);
        if (error.response) {
            if (error.response.status === 422) {
                console.error("Validation Errors from Backend (real-time check):", error.response.data.errors);
                // Display validation errors in a Swal.fire
                Swal.fire({
                    icon: 'error',
                    title: 'Input Error!',
                    text: 'Please check your date and time inputs. ' + (error.response.data.message || ''),
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                });
            } else if (error.response.status === 500) {
                // Specific handling for 500: this is your internal server error
                Swal.fire({
                    icon: 'error',
                    title: 'Server Error!',
                    text: 'An internal server error occurred while checking availability. Please check server logs for details.',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                });
            } else {
                // Catch any other HTTP errors (e.g., 404, 403)
                Swal.fire({
                    icon: 'error',
                    title: 'Request Failed!',
                    text: 'Could not fetch availability data. Status: ' + error.response.status,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 5000,
                    timerProgressBar: true,
                });
            }
        } else {
            // Network error (e.g., server not reachable)
            Swal.fire({
                icon: 'error',
                title: 'Network Error!',
                text: 'Could not connect to the server to check availability. Please check your internet connection.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 5000,
                timerProgressBar: true,
            });
        }
        // On any error, reset to show all as available, or handle error gracefully
        allRoomsWithStatus.value = props.rooms.map(room => ({ ...room, status_text: 'Available' }));
        allTeachersWithStatus.value = props.teachers.map(teacher => ({ ...teacher, status_text: 'Available' }));
    } finally {
        fetchingResources.value = false;
    }
};

const submit = () => {
    form.post(route('exam-schedules.store'), { // Ensure this route is correct for your ExamSchedule store
        onSuccess: () => {
            form.reset();
            // Reset status after successful submission
            allRoomsWithStatus.value = props.rooms.map(room => ({ ...room, status_text: 'Available' }));
            allTeachersWithStatus.value = props.teachers.map(teacher => ({ ...teacher, status_text: 'Available' }));
        },
        onError: (errors) => {
            console.error("Exam schedule creation failed:", errors);
            // Handle specific errors returned by the ExamSchdeuleStore method
        },
    });
};

// ... (watchEffect for flash messages as you had it) ...
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
    <Head title="Create Exam Schedule" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Exam Schedule</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title h5 mb-0">Add Exam Schedule Slot</h3>
                        <Link :href="route('exam-schedules.index')" class="btn btn-secondary btn-sm rounded">
                            Back to Exam Schedules
                        </Link>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <InputLabel for="exam_date" value="Exam Date" class="form-label" />
                                <TextInput id="exam_date" type="date" class="form-control" v-model="form.exam_date" :class="{ 'is-invalid': form.errors.exam_date }" required />
                                <InputError class="mt-2" :message="form.errors.exam_date" />
                            </div>

                            <div class="col-md-4">
                                <InputLabel for="start_time" value="Start Time" class="form-label" />
                                <TextInput id="start_time" type="time" class="form-control" v-model="form.start_time" :class="{ 'is-invalid': form.errors.start_time }" required />
                                <InputError class="mt-2" :message="form.errors.start_time" />
                            </div>

                            <div class="col-md-4">
                                <InputLabel for="end_time" value="End Time" class="form-label" />
                                <TextInput id="end_time" type="time" class="form-control" v-model="form.end_time" :class="{ 'is-invalid': form.errors.end_time }" required />
                                <InputError class="mt-2" :message="form.errors.end_time" />
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <InputLabel for="room_id" value="Select Room" class="form-label" />
                            <select id="room_id" class="form-select" v-model="form.room_id" :class="{ 'is-invalid': form.errors.room_id }">
                                <option value="">-- Select Room --</option>
                                <option v-for="room in allRoomsWithStatus" :key="room.id" :value="room.id" :disabled="room.status_text === 'Ongoing'">
                                    {{ room.name }} <span v-if="room.status_text === 'Ongoing'">(Ongoing)</span>
                                </option>
                                <option v-if="fetchingResources" disabled>Loading rooms...</option>
                                <option v-else-if="allRoomsWithStatus.length === 0" disabled>No rooms available.</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.room_id" />
                            <div v-if="fetchingResources" class="text-muted small">Checking room availability...</div>
                        </div>

                        <div class="mb-3">
                            <InputLabel for="teacher_id" value="Select Teacher" class="form-label" />
                                <select id="teacher_id" class="form-select" v-model="form.teacher_id" :class="{ 'is-invalid': form.errors.teacher_id }" required>
                                    <option value="" disabled>-- Select Teacher --</option>
                                    <option v-for="teacher in allTeachersWithStatus" :key="teacher.id" :value="teacher.id" :disabled="teacher.status_text === 'Ongoing'">
                                        {{ teacher.name }} <span v-if="teacher.subject_taught">({{ teacher.subject_taught }})</span> <span v-if="teacher.status_text === 'Ongoing'">(Ongoing)</span>
                                    </option>
                                    <option v-if="fetchingResources" disabled>Loading teachers...</option>
                                    <option v-else-if="allTeachersWithStatus.length === 0 && props.teachers.length > 0" disabled>No teachers available.</option>
                                    <option v-else-if="props.teachers.length === 0" disabled>No teachers loaded from props.</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.teacher_id" />
                                <div v-if="fetchingResources" class="text-muted small">Checking teacher availability...</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <InputLabel for="exam_id" value="Select Exam" class="form-label" />
                                <select id="exam_id" class="form-select" v-model="form.exam_id" :class="{ 'is-invalid': form.errors.exam_id }" required>
                                    <option value="">-- Select Exam --</option>
                                    <option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.exam_name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.exam_id" />
                            </div>
                             <div class="col-md-6">
                                <InputLabel for="class_id" value="Select Class" class="form-label" />
                                <select id="class_id" class="form-select" v-model="form.class_id" :class="{ 'is-invalid': form.errors.class_id }" required>
                                    <option value="" disabled>-- Select Class --</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.class_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="session_id" value="Select Session" class="form-label" />
                                <select id="session_id" class="form-select" v-model="form.session_id" :class="{ 'is-invalid': form.errors.session_id }" required>
                                    <option value="" disabled>-- Select Session --</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.session_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="section_id" value="Select Section" class="form-label" />
                                <select id="section_id" class="form-select" v-model="form.section_id" :class="{ 'is-invalid': form.errors.section_id }" required>
                                    <option value="" disabled>-- Select Section --</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.section_id" />
                            </div>
                            <div class="col-md-6">
                                <InputLabel for="subject_id" value="Select Subject" class="form-label" />
                                <select id="subject_id" class="form-select" v-model="form.subject_id" :class="{ 'is-invalid': form.errors.subject_id }" required>
                                    <option value="">-- Select Subject --</option>
                                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.subject_id" />
                            </div>
                             <div class="col-md-6">
                                <InputLabel for="status" value="Status" class="form-label" />
                                <select id="status" class="form-select" v-model.number="form.status" :class="{ 'is-invalid': form.errors.status }" required>
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                    <option :value="2">Rescheduled</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="btn btn-primary">
                                Create Exam Schedule
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Add any custom styles here */
</style>