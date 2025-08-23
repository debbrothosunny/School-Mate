<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue'; // Make sure InputLabel is imported
import { ref, computed, watch } from 'vue'; // Import ref and watch for dynamic filtering
import axios from 'axios'; // Import axios for API calls

const props = defineProps({
    timetableEntry: Object, // The specific timetable entry being edited
    classes: Array,
    sessions: Array,
    sections: Array,
    subjects: Array, // Initial subjects list (might be all or pre-filtered)
    teachers: Array, // Initial teachers list (might be all or pre-filtered)
    rooms: Array,    // List of available rooms
    errors: Object,  // Validation errors from Inertia
    flash: Object,   // Flash messages
});

// Use useForm to manage the form state, pre-populating with existing entry data
const form = useForm({
    class_name_id: props.timetableEntry.class_name_id,
    session_id: props.timetableEntry.session_id,
    section_id: props.timetableEntry.section_id,
    subject_id: props.timetableEntry.subject_id,
    teacher_id: props.timetableEntry.teacher_id,
    day_of_week: props.timetableEntry.day_of_week,
    start_time: props.timetableEntry.start_time ? props.timetableEntry.start_time.substring(0, 5) : '',
    end_time: props.timetableEntry.end_time ? props.timetableEntry.end_time.substring(0, 5) : '',
    room_id: props.timetableEntry.room_id || null, // Changed from room_number to room_id, initialize with null if empty
    status: props.timetableEntry.status, // Directly bind to 0 or 1, no boolean conversion here
});

const daysOfWeek = [
    { value: 'MONDAY', label: 'Monday' },
    { value: 'TUESDAY', label: 'Tuesday' },
    { value: 'WEDNESDAY', label: 'Wednesday' },
    { value: 'THURSDAY', label: 'Thursday' },
    { value: 'FRIDAY', label: 'Friday' },
    { value: 'SATURDAY', label: 'Saturday' },
    { value: 'SUNDAY', label: 'Sunday' },
];

// Reactive variables for dynamically filtered subjects and teachers
const filteredSubjects = ref(props.subjects);
const filteredTeachers = ref(props.teachers);

// Function to fetch filtered subjects and teachers based on class, section, session
const fetchFilteredData = async () => {
    // Only fetch if all three required fields are selected
    if (form.class_name_id && form.section_id && form.session_id) {
        try {
            const response = await axios.get(route('timetable.getFilteredData'), {
                params: {
                    class_name_id: form.class_name_id,
                    section_id: form.section_id,
                    session_id: form.session_id,
                }
            });
            filteredSubjects.value = response.data.subjects;
            filteredTeachers.value = response.data.teachers;

            // Optional: If the current subject/teacher is no longer in the filtered list, clear it
            if (!filteredSubjects.value.some(s => s.id === form.subject_id)) {
                form.subject_id = '';
            }
            if (!filteredTeachers.value.some(t => t.id === form.teacher_id)) {
                form.teacher_id = '';
            }

        } catch (error) {
            console.error('Error fetching filtered data:', error);
            // On error, clear dropdowns to prevent selecting invalid options
            filteredSubjects.value = [];
            filteredTeachers.value = [];
            form.subject_id = '';
            form.teacher_id = '';
        }
    } else {
        // Clear filtered options if dependencies are not selected
        filteredSubjects.value = [];
        filteredTeachers.value = [];
        form.subject_id = '';
        form.teacher_id = '';
    }
};

// Watch for changes in class, section, or session to re-fetch filtered subjects/teachers
// { immediate: true } ensures it runs once on component mount with initial props data
watch([() => form.class_name_id, () => form.section_id, () => form.session_id], () => {
    fetchFilteredData();
}, { immediate: true });


const submit = () => {
    // No need to convert status here, as it's already 0 or 1 from the select dropdown
    form.post(route('timetable.update', props.timetableEntry.id), {
        onSuccess: () => {
            // Display success message using Swal
            if (usePage().props.flash && usePage().props.flash.message) {
                Swal.fire({
                    icon: usePage().props.flash.type,
                    title: usePage().props.flash.message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                });
            }
        },
        onError: (errors) => {
            // Display error message using Swal if a general error exists
            if (usePage().props.flash && usePage().props.flash.message) {
                 Swal.fire({
                    icon: usePage().props.flash.type, // 'error'
                    title: usePage().props.flash.message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                });
            }
            console.error('Validation Errors:', errors);
        },
    });
};
</script>

<template>
    <Head :title="`Edit Timetable Entry - ${props.timetableEntry.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Timetable Entry</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <!-- Class Name Dropdown -->
                            <div class="col-md-6">
                                <InputLabel for="class_name_id" value="Class" class="form-label" />
                                <select id="class_name_id" v-model="form.class_name_id" class="form-select" :class="{ 'is-invalid': form.errors.class_name_id }" required>
                                    <option value="">Select Class</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                </select>
                                <InputError :message="form.errors.class_name_id" class="mt-2" />
                            </div>

                            <!-- Session Dropdown -->
                            <div class="col-md-6">
                                <InputLabel for="session_id" value="Session" class="form-label" />
                                <select id="session_id" v-model="form.session_id" class="form-select" :class="{ 'is-invalid': form.errors.session_id }" required>
                                    <option value="">Select Session</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                                <InputError :message="form.errors.session_id" class="mt-2" />
                            </div>

                            <!-- Section Dropdown -->
                            <div class="col-md-6">
                                <InputLabel for="section_id" value="Section" class="form-label" />
                                <select id="section_id" v-model="form.section_id" class="form-select" :class="{ 'is-invalid': form.errors.section_id }" required>
                                    <option value="">Select Section</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                                <InputError :message="form.errors.section_id" class="mt-2" />
                            </div>

                            <!-- Subject Dropdown (now dynamically filtered) -->
                            <div class="col-md-6">
                                <InputLabel for="subject_id" value="Subject" class="form-label" />
                                <select id="subject_id" v-model="form.subject_id" class="form-select" :class="{ 'is-invalid': form.errors.subject_id }" required>
                                    <option value="">Select Subject</option>
                                    <option v-for="subject in filteredSubjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                                </select>
                                <InputError :message="form.errors.subject_id" class="mt-2" />
                            </div>

                            <!-- Teacher Dropdown (now dynamically filtered) -->
                            <div class="col-md-6">
                                <InputLabel for="teacher_id" value="Teacher" class="form-label" />
                                <select id="teacher_id" v-model="form.teacher_id" class="form-select" :class="{ 'is-invalid': form.errors.teacher_id }" required>
                                    <option value="">Select Teacher</option>
                                    <option v-for="teacher in filteredTeachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                                </select>
                                <InputError :message="form.errors.teacher_id" class="mt-2" />
                            </div>

                            <!-- Day of Week Dropdown -->
                            <div class="col-md-6">
                                <InputLabel for="day_of_week" value="Day of Week" class="form-label" />
                                <select id="day_of_week" v-model="form.day_of_week" class="form-select" :class="{ 'is-invalid': form.errors.day_of_week }" required>
                                    <option value="">Select Day</option>
                                    <option v-for="day in daysOfWeek" :key="day.value" :value="day.value">{{ day.label }}</option>
                                </select>
                                <InputError :message="form.errors.day_of_week" class="mt-2" />
                            </div>

                            <!-- Start Time Input -->
                            <div class="col-md-6">
                                <InputLabel for="start_time" value="Start Time" class="form-label" />
                                <TextInput
                                    id="start_time"
                                    type="time"
                                    class="form-control"
                                    v-model="form.start_time"
                                    :class="{ 'is-invalid': form.errors.start_time }"
                                    required
                                />
                                <InputError :message="form.errors.start_time" class="mt-2" />
                            </div>

                            <!-- End Time Input -->
                            <div class="col-md-6">
                                <InputLabel for="end_time" value="End Time" class="form-label" />
                                <TextInput
                                    id="end_time"
                                    type="time"
                                    class="form-control"
                                    v-model="form.end_time"
                                    :class="{ 'is-invalid': form.errors.end_time }"
                                    required
                                />
                                <InputError :message="form.errors.end_time" class="mt-2" />
                            </div>

                            <!-- Room Dropdown (Corrected to room_id) -->
                            <div class="col-md-6">
                                <InputLabel for="room_id" value="Room (Optional)" class="form-label" />
                                <select
                                    id="room_id"
                                    class="form-select"
                                    v-model="form.room_id"
                                    :class="{ 'is-invalid': form.errors.room_id }"
                                >
                                    <option :value="null">-- Select Room (Optional) --</option>
                                    <option v-for="room in rooms" :key="room.id" :value="room.id">
                                        {{ room.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.room_id" class="mt-2" />
                            </div>


                            <!-- Status Dropdown (Corrected to directly bind 0/1) -->
                            <div class="col-md-6">
                                <InputLabel for="status" value="Status" class="form-label" />
                                <select
                                    id="status"
                                    class="form-select"
                                    v-model.number="form.status"
                                    required
                                >
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError :message="form.errors.status" class="mt-2" />
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <Link :href="route('timetable.index')" class="btn btn-secondary me-2">Cancel</Link>
                            <PrimaryButton :disabled="form.processing">Update Entry</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Add any specific styles here if needed */
</style>
