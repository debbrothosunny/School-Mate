<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue'; // Import ref for reactive variables

const props = defineProps({
    classes: Array,
    sessions: Array,
    groups: Array,
    sections: Array, // <--- Add sections to props
});

const form = useForm({
    name: '',
    class_id: '',
    age: '',
    session_id: '',
    group_id: '',
    section_id: '', // <--- Add section_id to form
    parent_name: '',
    address: '',
    contact: '',
    image: null, // Add image field for file upload
    status: 0,
});

const imagePreviewUrl = ref(null); // Reactive variable for image preview

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
    form.post(route('students.store'), {
        onFinish: () => {
            form.reset(); // Clear form fields after successful submission
            imagePreviewUrl.value = null; // Clear image preview as well
        },
    });
};
</script>

<template>
    <Head title="Add New Student" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Student</h2>
        </template>

        <!-- Removed the redundant py-12 as AuthenticatedLayout now provides the main padding. -->
        <!-- Changed max-w-xl to max-w-3xl to increase the width of the form, and removed mx-auto for left alignment. -->
        <div class="max-w-3xl sm:px-0 lg:px-0">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Adjusted inner padding from p-6 to p-4 for a more compact internal layout -->
                <div class="p-4 text-gray-900">
                    <form @submit.prevent="submit" enctype="multipart/form-data">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="name" value="Student Name" />
                                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="class_id" value="Class" />
                                <select id="class_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" v-model="form.class_id">
                                    <option value="">-- Select Class --</option>
                                    <option v-for="classItem in classes" :key="classItem.id" :value="classItem.id">{{ classItem.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.class_id" />
                            </div>

                            <div>
                                <InputLabel for="age" value="Age" />
                                <TextInput id="age" type="number" class="mt-1 block w-full" v-model="form.age" min="0" />
                                <InputError class="mt-2" :message="form.errors.age" />
                            </div>

                            <div>
                                <InputLabel for="session_id" value="Session" />
                                <select id="session_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" v-model="form.session_id">
                                    <option value="">-- Select Session --</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.session_id" />
                            </div>

                            <div>
                                <InputLabel for="group_id" value="Group" />
                                <select id="group_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" v-model="form.group_id">
                                    <option value="">-- Select Group --</option>
                                    <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
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
                                    <option value="">-- Select Section --</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">
                                        {{ section.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.section_id" />
                            </div>

                            <div>
                                <InputLabel for="parent_name" value="Parent's Name" />
                                <TextInput id="parent_name" type="text" class="mt-1 block w-full" v-model="form.parent_name" />
                                <InputError class="mt-2" :message="form.errors.parent_name" />
                            </div>

                            <div>
                                <InputLabel for="address" value="Address" />
                                <TextInput id="address" type="text" class="mt-1 block w-full" v-model="form.address" />
                                <InputError class="mt-2" :message="form.errors.address" />
                            </div>

                            <div>
                                <InputLabel for="contact" value="Contact" />
                                <TextInput id="contact" type="text" class="mt-1 block w-full" v-model="form.contact" />
                                <InputError class="mt-2" :message="form.errors.contact" />
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
                                <select id="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" v-model="form.status" required>
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <Link :href="route('students.index')" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Add Student
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
