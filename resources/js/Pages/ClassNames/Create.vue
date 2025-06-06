<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    teachers: Array, // List of teachers from the controller
    sections: Array, // List of sections from the controller
});

const form = useForm({
    teacher_id: '',
    section_id: '',
    name: '',
    class_time: '',
    day: '',
    room_number: '', // Added room_number to the form
    // subject is NOT a form field, it's derived/displayed from teacher
    status: 0, // Default to Active
});

// Reactive variable to display the subject, derived from the selected teacher
const displayedSubject = watch(() => form.teacher_id, (newTeacherId) => {
    const selectedTeacher = props.teachers.find(teacher => teacher.id === newTeacherId);
    // This value is for display only; it's not bound to form.subject for submission
    form.subject = selectedTeacher ? selectedTeacher.subject_taught : ''; // Temporarily using form.subject to hold display value
}, { immediate: true }); // Run immediately to set initial subject if teacher_id is pre-filled

const submit = () => {
    // Only send fields that are in the class_names table
    const dataToSend = {
        teacher_id: form.teacher_id,
        section_id: form.section_id,
        name: form.name,
        class_time: form.class_time,
        day: form.day, // Include day in data to send
        room_number: form.room_number, // Include room_number in data to send
        status: form.status,
    };

    form.post(route('class-names.store'), dataToSend, {
        onFinish: () => form.reset('teacher_id', 'section_id', 'name', 'class_time', 'day', 'room_number', 'status'), // Reset day and room_number as well
    });
};
</script>

<template>
    <Head title="Create New Class" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Class</h2>
        </template>

        <!-- Removed the redundant py-12 as AuthenticatedLayout now provides the main padding. -->
        <!-- Changed max-w-xl to max-w-3xl to increase the width of the form, and removed mx-auto for left alignment. -->
        <div class="max-w-3xl sm:px-0 lg:px-0">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Adjusted inner padding from p-6 to p-4 for a more compact internal layout -->
                <div class="p-4 text-gray-900">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="name" value="Class Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="teacher_id" value="Select Teacher" />
                                <select
                                    id="teacher_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    v-model="form.teacher_id"
                                    required
                                >
                                    <option value="">-- Select a Teacher --</option>
                                    <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                        {{ teacher.name }} ({{ teacher.subject_taught }})
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.teacher_id" />
                            </div>

                            <div>
                                <InputLabel for="subject_display" value="Subject Taught (Auto-filled)" />
                                <TextInput
                                    id="subject_display"
                                    type="text"
                                    class="mt-1 block w-full bg-gray-100"
                                    :value="form.subject"
                                    readonly
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.subject" />
                            </div>

                            <div>
                                <InputLabel for="class_time" value="Class Time" />
                                <TextInput
                                    id="class_time"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.class_time"
                                    placeholder="e.g., MWF 9:00-10:00 AM"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.class_time" />
                            </div>

                            <!-- NEW: Day Input Field -->
                            <div>
                                <InputLabel for="day" value="Day(s)" />
                                <TextInput
                                    id="day"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.day"
                                    placeholder="e.g., Monday, Wednesday, Friday"
                                />
                                <InputError class="mt-2" :message="form.errors.day" />
                            </div>

                            <!-- NEW: Room Number Input Field -->
                            <div>
                                <InputLabel for="room_number" value="Room Number" />
                                <TextInput
                                    id="room_number"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.room_number"
                                    placeholder="e.g., A101, Lab 2"
                                />
                                <InputError class="mt-2" :message="form.errors.room_number" />
                            </div>

                            <div>
                                <InputLabel for="section_id" value="Select Section" />
                                <select
                                    id="section_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    v-model="form.section_id"
                                    required
                                >
                                    <option value="">-- Select a Section --</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">
                                        {{ section.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.section_id" />
                            </div>

                            <div>
                                <InputLabel for="status" value="Status" />
                                <select
                                    id="status"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    v-model="form.status"
                                    required
                                >
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <Link :href="route('class-names.index')" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Add Class
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
