<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, watchEffect } from 'vue';

const props = defineProps({
    classes: Array,
    sessions: Array,
    sections: Array,
    rooms: Array,
    flash: Object,
});

const flash = computed(() => usePage().props.flash || {});

const form = useForm({
    class_name_id: '',
    subject_id: '',
    teacher_id: '',
    section_id: '',
    session_id: '',
    day_of_week: '',
    start_time: '',
    end_time: '',
    room_id: '',
    status: 0, // Default to Active
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

// Reactive data for dynamically filtered subjects and teachers
const availableSubjects = ref([]);
const availableTeachers = ref([]);

// Reactive data for conflict checking (blocking submission)
const isConflict = ref(false);
const conflictMessage = ref('');
const conflictDetails = ref(null);

// NEW: Reactive data for real-time availability display
const assignedSlots = ref([]);
const loadingAvailability = ref(false);
const showAvailability = ref(false); // To control visibility of availability section


// Watch for changes in class, session, section to dynamically fetch subjects/teachers
watch([
    () => form.class_name_id,
    () => form.session_id,
    () => form.section_id
], async ([newClassId, newSessionId, newSectionId]) => {
    form.subject_id = '';
    form.teacher_id = '';
    availableSubjects.value = [];
    availableTeachers.value = [];
    isConflict.value = false;
    conflictMessage.value = '';
    assignedSlots.value = []; // Reset availability too
    showAvailability.value = false;

    if (newClassId && newSessionId && newSectionId) {
        try {
            const response = await fetch(route('timetable.getFilteredData', {
                class_name_id: newClassId,
                session_id: newSessionId,
                section_id: newSectionId,
            }), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                let errorMessage = `Server responded with status ${response.status}: ${response.statusText}`;
                try {
                    const errorJson = JSON.parse(errorText);
                    errorMessage = errorJson.message || errorMessage;
                    if (response.status === 422 && errorJson.errors) {
                        console.error('Validation errors:', errorJson.errors);
                    }
                } catch (e) { /* Not JSON, use default error message */ }
                throw new Error(errorMessage);
            }

            const data = await response.json();
            availableSubjects.value = data.subjects;
            availableTeachers.value = data.teachers;
        } catch (error) {
            console.error('Error fetching filtered data:', error);
            Swal.fire({
                icon: 'error',
                title: 'Data Fetch Error!',
                text: error.message || 'Failed to load subjects and teachers for the selected criteria.',
            });
        }
    }
}, { immediate: true });

// Watch for changes in all relevant fields for CONFLICT detection (blocking submission)
watch([
    () => form.class_name_id,
    () => form.subject_id,
    () => form.teacher_id,
    () => form.section_id,
    () => form.session_id,
    () => form.day_of_week,
    () => form.start_time,
    () => form.end_time,
    () => form.room_id,
], async (
    [classId, subjectId, teacherId, sectionId, sessionId, day, startTime, endTime, roomId]
) => {
    isConflict.value = false;
    conflictMessage.value = '';
    conflictDetails.value = null;

    if (classId && subjectId && teacherId && sectionId && sessionId && day && startTime && endTime) {
        if (endTime && startTime && endTime <= startTime) {
            isConflict.value = true;
            conflictMessage.value = 'End time must be after start time.';
            return;
        }

        try {
            const response = await fetch(route('timetable.checkConflicts'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    class_name_id: classId,
                    subject_id: subjectId,
                    teacher_id: teacherId,
                    section_id: sectionId,
                    session_id: sessionId,
                    day_of_week: day,
                    start_time: startTime,
                    end_time: endTime,
                    room_id: roomId,
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                if (response.status === 409 && errorData.isConflict) {
                    isConflict.value = true;
                    conflictMessage.value = errorData.message;
                    conflictDetails.value = errorData.details;
                } else if (response.status === 422) {
                    console.error('Conflict check validation error:', errorData.errors);
                } else {
                    console.error('Error checking conflicts:', errorData);
                }
            } else {
                isConflict.value = false;
                conflictMessage.value = '';
                conflictDetails.value = null;
            }
        } catch (error) {
            console.error('Network or parsing error during conflict check:', error);
            isConflict.value = true;
            conflictMessage.value = 'Failed to perform real-time conflict check.';
            conflictDetails.value = null;
        }
    }
});

// NEW: Watch for changes in time, day, and room for REAL-TIME AVAILABILITY display
watch([
    () => form.day_of_week,
    () => form.start_time,
    () => form.end_time,
    () => form.room_id,
    () => form.session_id, // Session ID is crucial for this query too
], async (
    [day, startTime, endTime, roomId, sessionId]
) => {
    assignedSlots.value = [];
    showAvailability.value = false;
    loadingAvailability.value = false;

    // Only fetch occupied slots if all necessary parameters are filled
    if (day && startTime && endTime && sessionId) {
        // Basic client-side check to prevent immediate invalid requests
        if (endTime <= startTime) {
            // Don't show availability for invalid time ranges
            return;
        }

        loadingAvailability.value = true;
        try {
            // Construct query parameters
            const queryParams = new URLSearchParams({
                day_of_week: day,
                start_time: startTime,
                end_time: endTime,
                session_id: sessionId,
            });
            if (roomId) {
                queryParams.append('room_id', roomId);
            }

            const response = await fetch(route('timetable.getOccupiedSlots') + '?' + queryParams.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error fetching occupied slots:', errorText);
                // Handle error for fetching availability, perhaps a small message
            } else {
                const data = await response.json();
                assignedSlots.value = data;
                showAvailability.value = true; // Show the section once data is loaded
            }
        } catch (error) {
            console.error('Network error fetching occupied slots:', error);
        } finally {
            loadingAvailability.value = false;
        }
    }
});


const submit = () => {
    form.post(route('timetable.store'), {
        onSuccess: () => {
            form.reset();
            availableSubjects.value = [];
            availableTeachers.value = [];
            isConflict.value = false;
            conflictMessage.value = '';
            conflictDetails.value = null;
            assignedSlots.value = []; // Clear availability display on successful submission
            showAvailability.value = false;
        },
        onError: (errors) => {
            console.error("Timetable entry creation failed:", errors);
            if (errors.general) {
                Swal.fire({
                    icon: 'error',
                    title: 'Creation Failed!',
                    text: errors.general,
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Creation Failed!',
                    text: 'Please check the form for errors.',
                });
            }
        },
    });
};

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
    <Head title="Create Timetable Entry" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Timetable Entry</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title h5 mb-0">Add Timetable Slot</h3>
                        <Link :href="route('timetable.index')" class="btn btn-secondary btn-sm rounded">
                            Back to Timetable Overview
                        </Link>
                    </div>

                    <form @submit.prevent="submit">
                        <!-- General Error Display (for submission errors) -->
                        <div v-if="form.errors.general" class="alert alert-danger" role="alert">
                            {{ form.errors.general }}
                        </div>

                        <!-- Conflict Display (blocking submission) -->
                        <div v-if="isConflict" class="alert alert-warning mb-3" role="alert">
                            <strong>{{ conflictMessage }}</strong>
                            <div v-if="conflictDetails">
                                <ul class="list-unstyled mb-0">
                                    <li><strong>Class:</strong> {{ conflictDetails.class_name }} ({{ conflictDetails.section_name }})</li>
                                    <li><strong>Subject:</strong> {{ conflictDetails.subject_name }}</li>
                                    <li><strong>Teacher:</strong> {{ conflictDetails.teacher_name }}</li>
                                    <li><strong>Room:</strong> {{ conflictDetails.room_name || 'N/A' }}</li>
                                    <li><strong>Day:</strong> {{ conflictDetails.day_of_week }}</li>
                                    <li><strong>Time:</strong> {{ conflictDetails.start_time }} - {{ conflictDetails.end_time }}</li>
                                </ul>
                            </div>
                        </div>

                        <div class="row g-3">

                        <div class="col-md-6">
                                <InputLabel for="day_of_week" value="Day of Week" class="form-label" />
                                <select id="day_of_week" class="form-select" v-model="form.day_of_week" :class="{ 'is-invalid': form.errors.day_of_week }" required>
                                    <option value="" disabled>-- Select Day --</option>
                                    <option v-for="day in daysOfWeek" :key="day.value" :value="day.value">{{ day.label }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.day_of_week" />
                            </div>

                            <div class="col-md-3">
                                <InputLabel for="start_time" value="Start Time" class="form-label" />
                                <TextInput id="start_time" type="time" class="form-control" v-model="form.start_time" :class="{ 'is-invalid': form.errors.start_time || (isConflict && conflictMessage === 'End time must be after start time.') }" required />
                                <InputError class="mt-2" :message="form.errors.start_time" />
                            </div>

                            <div class="col-md-3">
                                <InputLabel for="end_time" value="End Time" class="form-label" />
                                <TextInput id="end_time" type="time" class="form-control" v-model="form.end_time" :class="{ 'is-invalid': form.errors.end_time || (isConflict && conflictMessage === 'End time must be after start time.') }" required />
                                <InputError class="mt-2" :message="form.errors.end_time" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="room_id" value="Select Room" class="form-label" />
                                <select id="room_id" class="form-select" v-model="form.room_id" :class="{ 'is-invalid': form.errors.room_id || (isConflict && conflictDetails && conflictDetails.room_name) }">
                                    <option value="">-- Select Room (Optional) --</option>
                                    <option v-for="room in rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.room_id" />
                            </div>
                            <div class="col-md-6">
                                <InputLabel for="class_name_id" value="Select Class" class="form-label" />
                                <select id="class_name_id" class="form-select" v-model="form.class_name_id" :class="{ 'is-invalid': form.errors.class_name_id }" required>
                                    <option value="" disabled>-- Select Class --</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.class_name_id" />
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
                                <select id="subject_id" class="form-select" v-model="form.subject_id" :class="{ 'is-invalid': form.errors.subject_id }" required :disabled="!form.class_name_id || !form.session_id || !form.section_id">
                                    <option value="" disabled>-- Select Subject --</option>
                                    <option v-for="subject in availableSubjects" :key="subject.id" :value="subject.id">{{ subject.name }} ({{ subject.code || 'N/A' }})</option>
                                    <option v-if="availableSubjects.length === 0 && (form.class_name_id && form.session_id && form.section_id)" disabled>No subjects found for this combination.</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.subject_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="teacher_id" value="Select Teacher" class="form-label" />
                                <select id="teacher_id" class="form-select" v-model="form.teacher_id" :class="{ 'is-invalid': form.errors.teacher_id }" required :disabled="!form.class_name_id || !form.session_id || !form.section_id">
                                    <option value="" disabled>-- Select Teacher --</option>
                                    <option v-for="teacher in availableTeachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }} ({{ teacher.subject_taught || 'N/A' }})</option>
                                    <option v-if="availableTeachers.length === 0 && (form.class_name_id && form.session_id && form.section_id)" disabled>No teachers found for this combination.</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.teacher_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="status" value="Status" class="form-label" />
                                <select id="status" class="form-select" v-model.number="form.status" :class="{ 'is-invalid': form.errors.status }" required>
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <!-- NEW: Real-time Availability Display Section -->
                        <div v-if="showAvailability" class="mt-4 p-3 bg-light rounded shadow-sm">
                            <h5 class="mb-3">Existing Bookings for Selected Time/Room:</h5>
                            <div v-if="loadingAvailability" class="text-center text-muted">Loading availability...</div>
                            <div v-else-if="assignedSlots.length > 0">
                                <ul class="list-group list-group-flush">
                                    <li v-for="slot in assignedSlots" :key="slot.id" class="list-group-item">
                                        <strong>{{ slot.start_time }} - {{ slot.end_time }}</strong>:
                                        {{ slot.class_name }} ({{ slot.section_name }}) -
                                        {{ slot.subject_name }} by {{ slot.teacher_name }}
                                        <span v-if="slot.room_name !== 'N/A'"> in {{ slot.room_name }}</span>
                                    </li>
                                </ul>
                            </div>
                            <div v-else class="alert alert-info mb-0">
                                This time slot appears to be **FREE** for the selected day and room and Time !
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing || isConflict" class="btn btn-primary">
                                Create Timetable Entry
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No additional scoped styles needed as Bootstrap handles most of it. */
</style>