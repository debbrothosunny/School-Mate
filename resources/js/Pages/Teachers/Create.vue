<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

import { ref } from 'vue'; // Import ref for file input

const props = defineProps({
    availableUsers: Array, // Users not yet assigned as teachers, passed from controller
});

const form = useForm({
    user_id: '', // Nullable, so can be empty string initially or null
    name: '',
    subject_taught: '',
    image: null, // For file upload
    status: 0, // <--- CHANGE 1: Default to 0 (Active) as per your requirement
});

const imagePreviewUrl = ref(null);

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreviewUrl.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        form.image = null;
        imagePreviewUrl.value = null;
    }
};

const submit = () => {
    // Prepare data for submission. Inertia handles FormData for file uploads.
    // user_id is null if not selected, instead of empty string.
    const dataToSend = { ...form.data() }; // Get all current form data

    // <--- CHANGE 2: REMOVE THIS LINE.
    // dataToSend.status = dataToSend.status ? 0 : 1;
    // The <select> dropdown already binds form.status directly to 0 or 1,
    // so no boolean to 0/1 conversion is needed anymore.
    // If you keep this line, it will incorrectly convert 0 to 1 and 1 to 0 due to truthiness/falsiness.

    dataToSend.user_id = dataToSend.user_id === '' ? null : dataToSend.user_id;

    form.post(route('teachers.store'), dataToSend, {
        onSuccess: () => {
            form.reset(); // Clear the form fields after successful submission
            imagePreviewUrl.value = null; // Clear image preview
        },
        onError: (errors) => {
            console.error("Teacher creation failed:", errors);
        },
    });
};
</script>

<template>
    <Head title="Create Teacher" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Teacher</h2>
        </template>

        <div class="py-12">
            <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
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
                                <InputLabel for="address" value="Address" />
                                <TextInput
                                    id="address"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.address"
                                />
                                <InputError class="mt-2" :message="form.errors.address" />
                            </div>

                           

                            <div class="mb-4">
                                <InputLabel for="image" value="Teacher Image" />
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
                                    Create Teacher
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>