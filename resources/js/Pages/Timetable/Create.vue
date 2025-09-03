<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { computed, watch, watchEffect } from 'vue';

// Define the props that are passed from the controller
const props = defineProps({
    classNames: Array,
    sections: Array,
    sessions: Array,
    subjects: Array,
    teachers: Array,
    rooms: Array,
    timeSlots: Array,
    daysOfWeek: Array,
    // Assuming the backend provides an array of class-subject-teacher assignments
    classSubjects: Array,
    // New prop to check for existing bookings.
    // We add a default empty array to prevent 'undefined' errors.
    existingTimetables: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    class_name_id: '',
    section_id: '',
    session_id: '',
    subject_id: '',
    teacher_id: '',
    room_id: '',
    day_of_week: '',
    class_time_slot_id: '',
    status: true,
});

const submit = () => {
    form.post(route('timetable.store'), {
        onSuccess: () => {
            // Optional: you can perform actions after a successful submission
        },
    });
};

// Accessing flash messages from the Inertia page props
const page = usePage();
const flash = computed(() => page.props.flash || {});

// Watch for flash messages and display them as toasts
watch(() => page.props.flash, (newFlash) => {
    if (newFlash?.message) {
        Swal.fire({
            icon: newFlash.type === 'success' ? 'success' : 'error',
            title: newFlash.type === 'success' ? 'Success!' : 'Error!',
            text: newFlash.message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }
});


// Reactive data for filtered teachers based on selected class and section
const filteredTeachers = computed(() => {
    if (!form.class_name_id || !form.section_id) {
        return props.teachers;
    }

    const assignedTeacherIds = props.classSubjects
        .filter(cs => cs.class_name_id === form.class_name_id && cs.section_id === form.section_id)
        .map(cs => cs.teacher_id);
    
    // Filter the main teachers array to only include those assigned to the selected class and section
    const uniqueTeacherIds = [...new Set(assignedTeacherIds)];
    return props.teachers.filter(teacher => uniqueTeacherIds.includes(teacher.id));
});

// Reactive data for filtered subjects based on selected class, section, AND teacher
const filteredSubjects = computed(() => {
    if (!form.class_name_id || !form.section_id || !form.teacher_id) {
        // If a teacher is not selected, we still show subjects for the class and section
        const assignedSubjectIds = props.classSubjects
            .filter(cs => 
                cs.class_name_id === form.class_name_id && 
                cs.section_id === form.section_id
            )
            .map(cs => cs.subject_id);

        const uniqueSubjectIds = [...new Set(assignedSubjectIds)];
        return props.subjects.filter(subject => uniqueSubjectIds.includes(subject.id));
    }

    const assignedSubjectIds = props.classSubjects
        .filter(cs => 
            cs.class_name_id === form.class_name_id && 
            cs.section_id === form.section_id && 
            cs.teacher_id === form.teacher_id
        )
        .map(cs => cs.subject_id);

    // Filter the main subjects array to only include those assigned to the selected teacher, class, and section
    const uniqueSubjectIds = [...new Set(assignedSubjectIds)];
    return props.subjects.filter(subject => uniqueSubjectIds.includes(subject.id));
});

// A computed property to get the names of subjects for a given teacher based on class-subject assignments
const getTeacherSubjects = computed(() => (teacher) => {
    if (!form.class_name_id || !form.section_id) {
        return 'Select class and section';
    }

    // Find subjects assigned to this teacher in the selected class and section
    const assignedSubjects = props.classSubjects
        .filter(cs => 
            cs.teacher_id === teacher.id &&
            cs.class_name_id === form.class_name_id &&
            cs.section_id === form.section_id
        )
        .map(cs => props.subjects.find(s => s.id === cs.subject_id))
        .filter(Boolean); // Filter out any undefined subjects

    const uniqueSubjectNames = [...new Set(assignedSubjects.map(s => s.name))];
    return uniqueSubjectNames.join(', ') || 'No subjects assigned for this selection';
});

// New computed property to check if the selected time slot, room, and day are already booked
const isBooked = computed(() => {
    // Check if all necessary fields are selected
    if (form.day_of_week && form.class_time_slot_id && form.room_id) {
        return props.existingTimetables.some(item =>
            item.day_of_week === form.day_of_week &&
            item.class_time_slot_id === parseInt(form.class_time_slot_id) &&
            item.room_id === parseInt(form.room_id)
        );
    }
    return false;
});

// Check if a specific time slot is booked for the current room and day
const isTimeSlotBooked = computed(() => (slotId) => {
    if (form.day_of_week && form.room_id) {
        return props.existingTimetables.some(item =>
            item.day_of_week === form.day_of_week &&
            item.class_time_slot_id === slotId &&
            item.room_id === parseInt(form.room_id)
        );
    }
    return false;
});

// Watch for changes in class_name_id and section_id and reset dependent fields
watch([() => form.class_name_id, () => form.section_id], () => {
    form.teacher_id = '';
    form.subject_id = '';
});

// Watch for changes in teacher_id and reset the subject
watch(() => form.teacher_id, (newTeacherId) => {
    if (newTeacherId) {
        const assignedSubjectIds = props.classSubjects
            .filter(cs => 
                cs.class_name_id === form.class_name_id && 
                cs.section_id === form.section_id && 
                cs.teacher_id === newTeacherId
            )
            .map(cs => cs.subject_id);

        // Automatically set the subject if there's only one option
        if (assignedSubjectIds.length === 1) {
            form.subject_id = assignedSubjectIds[0];
        } else {
            form.subject_id = '';
        }
    } else {
        form.subject_id = '';
    }
});
</script>

<template>
    <Head title="Create Class Schedule" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Class Schedule</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        
                        <form @submit.prevent="submit">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="mb-4">
                                    <label for="class_name_id" class="block text-sm font-medium text-gray-700">Class</label>
                                    <select v-model="form.class_name_id" id="class_name_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="" disabled>Select a class</option>
                                        <option v-for="className in classNames" :key="className.id" :value="className.id">{{ className.class_name }}</option>
                                    </select>
                                    <div v-if="form.errors.class_name_id" class="text-red-500 text-sm">{{ form.errors.class_name_id }}</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="section_id" class="block text-sm font-medium text-gray-700">Section</label>
                                    <select v-model="form.section_id" id="section_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="" disabled>Select a section</option>
                                        <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                    </select>
                                    <div v-if="form.errors.section_id" class="text-red-500 text-sm">{{ form.errors.section_id }}</div>
                                </div>

                                <div class="mb-4">
                                    <label for="session_id" class="block text-sm font-medium text-gray-700">Session</label>
                                    <select v-model="form.session_id" id="session_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="" disabled>Select a session</option>
                                        <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                    </select>
                                    <div v-if="form.errors.session_id" class="text-red-500 text-sm">{{ form.errors.session_id }}</div>
                                </div>

                                <div class="mb-4">
                                    <label for="teacher_id" class="block text-sm font-medium text-gray-700">Teacher</label>
                                    <select v-model="form.teacher_id" id="teacher_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="" disabled>Select a teacher</option>
                                        <option v-for="teacher in filteredTeachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }} ({{ getTeacherSubjects(teacher) }})</option>
                                    </select>
                                    <div v-if="form.errors.teacher_id" class="text-red-500 text-sm">{{ form.errors.teacher_id }}</div>
                                </div>
                            </div>

                            <hr class="my-6">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                
                                <div class="mb-4">
                                    <label for="subject_id" class="block text-sm font-medium text-gray-700">Subject</label>
                                    <select v-model="form.subject_id" id="subject_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="" disabled>Select a subject</option>
                                        <option v-for="subject in filteredSubjects" :key="subject.id" :value="subject.id">{{ subject.name }} ({{ subject.code }})</option>
                                    </select>
                                    <div v-if="form.errors.subject_id" class="text-red-500 text-sm">{{ form.errors.subject_id }}</div>
                                </div>

                                <div class="mb-4">
                                    <label for="room_id" class="block text-sm font-medium text-gray-700">Room</label>
                                    <select v-model="form.room_id" id="room_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="" disabled>Select a room</option>
                                        <option v-for="room in rooms" :key="room.id" :value="room.id">{{ room.name }}</option>
                                    </select>
                                    <div v-if="form.errors.room_id" class="text-red-500 text-sm">{{ form.errors.room_id }}</div>
                                </div>

                                <div class="mb-4">
                                    <label for="day_of_week" class="block text-sm font-medium text-gray-700">Day of Week</label>
                                    <select v-model="form.day_of_week" id="day_of_week" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <option value="" disabled>Select a day</option>
                                        <option v-for="day in daysOfWeek" :key="day" :value="day">{{ day }}</option>
                                    </select>
                                    <div v-if="form.errors.day_of_week" class="text-red-500 text-sm">{{ form.errors.day_of_week }}</div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">Time Slot</label>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2 mt-2">
                                        <div
                                            v-for="slot in timeSlots"
                                            :key="slot.id"
                                            @click="!isTimeSlotBooked(slot.id) ? form.class_time_slot_id = slot.id : null"
                                            :class="{
                                                'p-3 border rounded-lg cursor-pointer transition-colors duration-200 ease-in-out': true,
                                                'bg-blue-500 text-white shadow-md transform scale-105': form.class_time_slot_id === slot.id,
                                                'bg-white hover:bg-gray-100': form.class_time_slot_id !== slot.id && !isTimeSlotBooked(slot.id),
                                                'bg-red-500 text-white cursor-not-allowed': isTimeSlotBooked(slot.id)
                                            }"
                                        >
                                            <div class="font-semibold text-center">{{ slot.name }}</div>
                                            <div class="text-xs text-center" :class="{ 'text-white': form.class_time_slot_id === slot.id || isTimeSlotBooked(slot.id), 'text-gray-500': form.class_time_slot_id !== slot.id && !isTimeSlotBooked(slot.id) }">
                                                <span v-if="isTimeSlotBooked(slot.id)">Booked</span>
                                                <span v-else>Free ({{ slot.start_time }} - {{ slot.end_time }})</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="form.errors.class_time_slot_id" class="text-red-500 text-sm">{{ form.errors.class_time_slot_id }}</div>
                                    <div v-if="isBooked" class="p-3 my-4 bg-red-100 border border-red-400 text-red-700 rounded-md">
                                        This room is already booked for the selected day and time slot.
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end mt-4">
                                <button type="submit" :disabled="form.processing || isBooked" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Save Schedule
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
