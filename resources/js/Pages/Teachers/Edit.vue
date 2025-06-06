<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import Checkbox from '@/Components/Checkbox.vue';
import { ref } from 'vue';

const props = defineProps({
    teacher: Object, // The teacher object passed from the controller
    availableUsers: Array, // Users available to be linked, passed from controller
});

const form = useForm({
    _method: 'post',
    name: props.teacher.name,
    user_id: props.teacher.user_id || '', // Pre-fill, or empty string if null
    subject_taught: props.teacher.subject_taught,
    image: null, // For new file upload
    status: props.teacher.status, // Use the actual numeric status from the database
    current_image_path: props.teacher.image, // To display current image and check for removal
    clear_image: false, // Checkbox to explicitly clear the image
});

// Reactive variable for image preview (current or new)
const imagePreviewUrl = ref(props.teacher.image ? `/storage/${props.teacher.image}` : null);

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        form.clear_image = false; // If new image is selected, don't clear old one
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreviewUrl.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        form.image = null; // No file selected, clear the form.image
        // If the user cleared the file input, but didn't check "clear_image",
        // we should show the original image unless it was explicitly removed.
        if (!form.clear_image) {
            imagePreviewUrl.value = props.teacher.image ? `/storage/${props.teacher.image}` : null;
        } else {
             imagePreviewUrl.value = null; // If clear_image is true and input is empty
        }
    }
};

const handleClearImageCheckbox = () => {
    if (form.clear_image) {
        form.image = null; // Clear the selected new image if checkbox is checked
        imagePreviewUrl.value = null; // Clear image preview
    } else {
        // If unchecking "clear_image", revert to original image if one existed
        imagePreviewUrl.value = props.teacher.image ? `/storage/${props.teacher.image}` : null;
    }
    // Also clear file input element itself if clearing the image
    const fileInput = document.getElementById('image');
    if (fileInput) {
        fileInput.value = null;
    }
};

const submit = () => {
    // Prepare data for submission.
    const dataToSend = { ...form.data() };

    dataToSend.user_id = dataToSend.user_id === '' ? null : dataToSend.user_id; // Convert empty string to null

    form.post(route('teachers.update', props.teacher.id), dataToSend, {
        // Inertia automatically handles _method: 'put' when using form.post for files
        // For file uploads, we must use POST method, and Inertia will spoof PUT.
        onSuccess: () => {
            // Flash message will appear from controller redirect
            // Optionally, reset only the image field if you want to allow re-upload easily
            form.image = null;
            // If update was successful and image was cleared, update the current_image_path prop
            if (form.clear_image) {
                form.current_image_path = null;
                // Note: props.teacher.image_path will be refreshed by the controller's redirect after update.
            }
        },
        onError: (errors) => {
            console.error("Teacher update failed:", errors);
        },
    });
};
</script>

<template>
    <Head :title="'Edit Teacher: ' + teacher.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Teacher: {{ teacher.name }}</h2>
        </template>

        <!-- Removed the redundant py-12 div.
             Changed max-w-xl mx-auto sm:px-6 lg:px-8 to max-w-md and removed mx-auto and explicit px classes. -->
        <div class="max-w-md sm:px-0 lg:px-0"> <!-- Now relies on AuthenticatedLayout for outer padding -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Adjusted inner padding from p-6 to p-4 for a more compact layout -->
                <div class="p-4 text-gray-900">
                    <form @submit.prevent="submit" enctype="multipart/form-data">
                        <div class="mb-4">
                            <InputLabel for="name" value="Teacher Name" />
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

                        <div class="mb-4">
                            <InputLabel for="user_id" value="Link to Existing User (Optional)" />
                            <select
                                id="user_id"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                v-model="form.user_id"
                            >
                                <option value="">-- Select User --</option>
                                <option v-if="teacher.user_id && !availableUsers.some(u => u.id === teacher.user_id)" :value="teacher.user_id">
                                    {{ teacher.user.name }} (Currently Linked)
                                </option>
                                <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                    {{ user.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.user_id" />
                        </div>

                        <div class="mb-4">
                            <InputLabel for="subject_taught" value="Subject Taught" />
                            <TextInput
                                id="subject_taught"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.subject_taught"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.subject_taught" />
                        </div>

                        <div class="mb-4">
                            <InputLabel for="image" value="Teacher Image (Optional)" />
                            <input
                                type="file"
                                id="image"
                                @change="handleImageChange"
                                class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                accept="image/*"
                            />
                            <InputError class="mt-2" :message="form.errors.image" />

                            <div v-if="imagePreviewUrl" class="mt-2">
                                <p class="text-sm text-gray-600 mb-1">Current Image:</p>
                                <img :src="imagePreviewUrl" alt="Current Image" class="w-32 h-32 object-cover rounded-md" />
                            </div>
                            <div v-else-if="!form.image && !form.current_image_path" class="mt-2 text-sm text-gray-500">
                                No image uploaded.
                            </div>

                            <div v-if="form.image" class="mt-2">
                                <label class="flex items-center">
                                    <Checkbox v-model:checked="form.clear_image" name="clear_image" @change="handleClearImageCheckbox" />
                                    <span class="ms-2 text-sm text-gray-600">Remove Current Image</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-4">
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

                        <div class="flex items-center justify-end mt-4">
                            <Link :href="route('teachers.index')" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Update Teacher
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No specific styles needed beyond TailwindCSS for this component */
</style>
