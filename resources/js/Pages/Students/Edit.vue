<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue'; // Import ref for reactive variables

const props = defineProps({
    student: Object, // The student object to be edited
    classes: Array,  // List of available classes
    sessions: Array, // List of available sessions
    groups: Array,   // List of available groups
    sections: Array, // List of available sections
});

const flash = computed(() => usePage().props.flash || {});

// Reactive variable for image preview, initialized with existing student image if available
const imagePreviewUrl = ref(props.student.image ? `/storage/${props.student.image}` : null);

// Initialize the form with the current student's data
const form = useForm({
    name: props.student.name,
    age: props.student.age,
    parent_name: props.student.parent_name,
    contact: props.student.contact,
    class_id: props.student.class_id,
    session_id: props.student.session_id,
    group_id: props.student.group_id,
    section_id: props.student.section_id,
    status: props.student.status, // 0 for active, 1 for inactive
    address: props.student.address,
    image: null, // Add image field for new file upload, will be FormData
});

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file; // Assign the selected file to the form data
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreviewUrl.value = e.target.result; // Set the preview URL
        };
        reader.readAsDataURL(file); // Read the file as a data URL
    } else {
        form.image = null; // Clear image from form if no file selected
        imagePreviewUrl.value = null; // Clear image preview
    }
};

const submit = () => {
    // Inertia.js automatically handles FormData for file uploads when the form contains a File object.
    // We use post and Inertia will spoof a PUT/PATCH request behind the scenes.
    form.post(route('students.update', props.student.id), {
        onSuccess: () => {
            // Optional: reset form or show success message after submission
            // Inertia's flash messages handle this automatically
            // If the image was updated, update the preview URL to reflect the new image
            if (form.image) {
                // This might be tricky if the backend returns a new path immediately,
                // but usually, Inertia's response reloads the props, making this unnecessary.
                // For direct preview update without full page reload, you might need
                // the backend to return the new image URL.
            }
        },
        onError: (errors) => {
            console.error("Student update failed:", errors);
        },
    });
};
</script>

<template>
    <Head title="Edit Student" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Student: {{ student.name }}</h2>
        </template>

        <!-- Removed the redundant py-12 as AuthenticatedLayout now provides the main padding. -->
        <!-- Changed max-w-md to max-w-3xl to increase the width of the form. -->
        <div class="max-w-3xl sm:px-0 lg:px-0">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Adjusted inner padding from p-6 to p-4 for a more compact internal layout -->
                <div class="p-4 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">Update Student Details</h3>
                        <Link :href="route('students.index')" class="text-indigo-600 hover:text-indigo-900">
                            Back to Students
                        </Link>
                    </div>

                    <div v-if="flash.message" :class="`p-4 mb-4 text-sm rounded-lg ${flash.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`" role="alert">
                        {{ flash.message }}
                    </div>

                    <form @submit.prevent="submit" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="name" value="Student Name" />
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
                                <InputLabel for="age" value="Age" />
                                <TextInput
                                    id="age"
                                    type="number"
                                    class="mt-1 block w-full"
                                    v-model.number="form.age"
                                    min="0"
                                />
                                <InputError class="mt-2" :message="form.errors.age" />
                            </div>

                            <div>
                                <InputLabel for="parent_name" value="Parent Name" />
                                <TextInput
                                    id="parent_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.parent_name"
                                />
                                <InputError class="mt-2" :message="form.errors.parent_name" />
                            </div>

                            <div>
                                <InputLabel for="contact" value="Contact Number" />
                                <TextInput
                                    id="contact"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.contact"
                                />
                                <InputError class="mt-2" :message="form.errors.contact" />
                            </div>

                            <div>
                                <InputLabel for="address" value="Address" />
                                <TextInput
                                    id="address"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.address"
                                />
                                <InputError class="mt-2" :message="form.errors.address" />
                            </div>

                            <div>
                                <InputLabel for="class_id" value="Class" />
                                <select
                                    id="class_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    v-model="form.class_id"
                                >
                                    <option value="" disabled>Select a Class</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">
                                        {{ cls.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.class_id" />
                            </div>

                            <div>
                                <InputLabel for="session_id" value="Session" />
                                <select
                                    id="session_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    v-model="form.session_id"
                                >
                                    <option value="" disabled>Select a Session</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">
                                        {{ session.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.session_id" />
                            </div>

                            <div>
                                <InputLabel for="group_id" value="Group" />
                                <select
                                    id="group_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    v-model="form.group_id"
                                >
                                    <option value="" disabled>Select a Group</option>
                                    <option v-for="group in groups" :key="group.id" :value="group.id">
                                        {{ group.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.group_id" />
                            </div>

                            <div>
                                <InputLabel for="section_id" value="Section" />
                                <select
                                    id="section_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    v-model="form.section_id"
                                >
                                    <option value="" disabled>Select a Section</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">
                                        {{ section.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.section_id" />
                            </div>

                            <div>
                                <InputLabel for="image" value="Student Image" />
                                <input
                                    type="file"
                                    id="image"
                                    @change="handleImageChange"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                    accept="image/*"
                                />
                                <InputError class="mt-2" :message="form.errors.image" />
                                <div v-if="imagePreviewUrl" class="mt-2">
                                    <img :src="imagePreviewUrl" alt="Image Preview" class="w-32 h-32 object-cover rounded-md" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="status" value="Status" />
                                <select
                                    id="status"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    v-model.number="form.status"
                                >
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Update Student
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
