<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3'; // Import usePage for flash messages
import { watch, computed } from 'vue'; // Import watch and computed for reactivity

const props = defineProps({
    className: Object, // The class object to be edited
    teachers: Array,
    sections: Array,
});

const flash = computed(() => usePage().props.flash || {});

// Initialize the form with the current class's data
const form = useForm({
    name: props.className.name ?? '',
    teacher_id: props.className.teacher_id ?? '',
    subject_display: props.className.teacher?.subject_taught || '', // Initialize with existing subject or empty
    class_time: props.className.class_time ?? '',
    day: props.className.day ?? '', // Initialize 'day' from existing data
    room_number: props.className.room_number ?? '', // Initialize 'room_number' from existing data
    section_id: props.className.section_id ?? '',
    status: props.className.status ?? 0, // Default to 0 (Active) if status is null
});

// Watch for changes in teacher_id to auto-fill subject_taught
watch(() => form.teacher_id, (newTeacherId) => {
    const selectedTeacher = props.teachers.find(teacher => teacher.id === newTeacherId);
    form.subject_display = selectedTeacher ? selectedTeacher.subject_taught : '';
}, { immediate: true }); // immediate: true to run on initial load

const submit = () => {
    // For updating, use form.put()
    form.post(route('class-names.update', props.className.id), {
        onSuccess: () => {
            // Optional: show success message or perform other actions
            // Flash messages are handled by Inertia
        },
        onError: (errors) => {
            console.error('Update failed:', errors);
        },
    });
};
</script>

<template>
    <Head :title="`Edit Class: ${className.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Class: {{ className.name }}</h2>
        </template>

        <div class="max-w-3xl sm:px-0 lg:px-0">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Update Class Details</h3>
                        <Link :href="route('class-names.index')" class="text-indigo-600 hover:text-indigo-900">
                            Back to Classes
                        </Link>
                    </div>

                    <div v-if="flash.message" :class="`p-4 mb-4 text-sm rounded-lg ${flash.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`" role="alert">
                        {{ flash.message }}
                    </div>

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

                            <!-- Day Input Field -->
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
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Update Class
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
