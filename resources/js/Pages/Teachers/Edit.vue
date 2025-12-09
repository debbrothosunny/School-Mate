<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { ref, watch } from 'vue';

const props = defineProps({
    teacher: Object,
    classes: Array,
    sections: Array,
    groups: Array,
});

// Helper function to generate correct image URL
const getImageUrl = (imagePath) => {
    if (!imagePath) return null;
    if (imagePath.startsWith('http')) return imagePath;
    const filename = imagePath.split('/').pop();
    return route('teachers.image.serve', { filename });
};

const form = useForm({
    _method: 'post',
    name: props.teacher.name,
    subject_taught: props.teacher.subject_taught,
    address: props.teacher.address,
    phone_number: props.teacher.phone_number || '',
    qualification: props.teacher.qualification || '',
    joining_date: props.teacher.joining_date || '',
    status: props.teacher.status,
    designation: props.teacher.designation || 'Junior Teacher',
    password: '',
    password_confirmation: '',
    image: null,
    current_image_path: props.teacher.image,
    clear_image: false,
    is_class_teacher: props.teacher.is_class_teacher || false,
    class_id: props.teacher.class_id || '',
    section_id: props.teacher.section_id || '',
    group_id: props.teacher.group_id || '',
});

const imagePreviewUrl = ref(getImageUrl(props.teacher.image));
const showClassTeacherFields = ref(props.teacher.is_class_teacher);

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        form.clear_image = false;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreviewUrl.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const handleClearImage = () => {
    if (form.clear_image) {
        form.image = null;
        imagePreviewUrl.value = null;
    } else {
        imagePreviewUrl.value = getImageUrl(props.teacher.image);
    }
    const input = document.getElementById('image');
    if (input) input.value = '';
};

// Watch Class Teacher checkbox
watch(() => form.is_class_teacher, (val) => {
    showClassTeacherFields.value = val;
    if (!val) {
        form.class_id = '';
        form.section_id = '';
        form.group_id = '';
    }
});

const submit = () => {
    form.post(route('teachers.update', props.teacher.id), {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'তথ্য সফলভাবে আপডেট করা হয়েছে!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });

            // Clear sensitive fields
            form.password = '';
            form.password_confirmation = '';

            // Clear image preview if you have one
            imagePreviewUrl.value = getImageUrl(props.teacher.image);

            // Clear HTML file input manually if using a ref
            if (imageInput.value) imageInput.value.value = '';
        },
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Failed!',
                text: 'Could not update teacher. Please check the fields.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });

            console.error("Teacher update failed:", errors);
        },
    });

};
</script>

<template>
    <Head :title="'Edit Teacher: ' + teacher.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                    Edit Teacher: {{ teacher.name }}
                </h2>
                <Link :href="route('teachers.index')" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400">
                    Back to Teachers
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-2xl sm:rounded-2xl p-8">
                    <form @submit.prevent="submit" class="space-y-8">

                        <!-- Personal Information -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Personal Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="name" value="Full Name *" />
                                    <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                                    <InputError :message="form.errors.name" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="subject_taught" value="Subject Taught *" />
                                    <TextInput id="subject_taught" v-model="form.subject_taught" type="text" class="mt-1 block w-full" required />
                                    <InputError :message="form.errors.subject_taught" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="address" value="Address *" />
                                    <TextInput id="address" v-model="form.address" type="text" class="mt-1 block w-full" required />
                                    <InputError :message="form.errors.address" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="phone_number" value="Phone Number" />
                                    <TextInput id="phone_number" v-model="form.phone_number" type="text" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.phone_number" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="qualification" value="Qualification" />
                                    <TextInput id="qualification" v-model="form.qualification" type="text" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.qualification" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="joining_date" value="Joining Date" />
                                    <TextInput id="joining_date" v-model="form.joining_date" type="date" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.joining_date" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="designation" value="Designation *" />
                                    <select v-model="form.designation" required
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                        <option value="Head Teacher">Head Teacher</option>
                                        <option value="Senior Teacher">Senior Teacher</option>
                                        <option value="Junior Teacher">Junior Teacher</option>
                                        <option value="Assistant Teacher">Assistant Teacher</option>
                                    </select>
                                    <InputError :message="form.errors.designation" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="status" value="Status *" />
                                    <select v-model="form.status" required
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                        <option :value="0">Active</option>
                                        <option :value="1">Inactive</option>
                                    </select>
                                    <InputError :message="form.errors.status" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Class Teacher Assignment -->
                        <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-6">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" v-model="form.is_class_teacher" class="w-5 h-5 text-emerald-600 rounded focus:ring-emerald-500" />
                                <span class="text-lg font-medium text-emerald-900 dark:text-emerald-300">
                                    Is this teacher a <strong>Class Teacher</strong>?
                                </span>
                            </label>

                            <div v-if="showClassTeacherFields" class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6 animate-fade-in">
                                <div>
                                    <InputLabel for="class_id" value="Class *" />
                                    <select v-model="form.class_id" :required="form.is_class_teacher"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                        <option value="">Select Class</option>
                                        <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                                    </select>
                                    <InputError :message="form.errors.class_id" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="section_id" value="Section *" />
                                    <select v-model="form.section_id" :required="form.is_class_teacher"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                        <option value="">Select Section</option>
                                        <option v-for="sec in props.sections" :key="sec.id" :value="sec.id">{{ sec.name }}</option>
                                    </select>
                                    <InputError :message="form.errors.section_id" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="group_id" value="Group (Optional)" />
                                    <select v-model="form.group_id"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                        <option value="">No Group</option>
                                        <option v-for="grp in props.groups" :key="grp.id" :value="grp.id">{{ grp.name }}</option>
                                    </select>
                                    <InputError :message="form.errors.group_id" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Password Update -->
                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6">
                            <h3 class="text-lg font-semibold text-amber-900 dark:text-amber-300 mb-6">Update Password (Optional)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="password" value="New Password" />
                                    <TextInput id="password" v-model="form.password" type="password" placeholder="Leave blank to keep current" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.password" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="password_confirmation" value="Confirm Password" />
                                    <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" />
                                    <InputError :message="form.errors.password_confirmation" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Image Upload -->
                        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-6">
                            <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-300 mb-4">Teacher Photo</h3>
                            <input type="file" id="image" @change="handleImageChange" accept="image/*"
                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-white dark:bg-gray-700 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100" />

                            <div v-if="imagePreviewUrl" class="mt-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Current Preview:</p>
                                <img :src="imagePreviewUrl" alt="Preview" class="w-32 h-32 object-cover rounded-lg shadow-lg border-4 border-white dark:border-gray-800" />
                            </div>

                            <div v-if="props.teacher.image || form.image" class="mt-4">
                                <label class="flex items-center cursor-pointer">
                                    <Checkbox v-model:checked="form.clear_image" @change="handleClearImage" />
                                    <span class="ml-3 text-sm text-gray-700 dark:text-gray-300">Remove current photo</span>
                                </label>
                            </div>

                            <InputError :message="form.errors.image" class="mt-2" />
                        </div>

                        <!-- Submit -->
                        <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <Link :href="route('teachers.index')" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400">
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing"
                                class="bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white font-medium px-8">
                                {{ form.processing ? 'Updating...' : 'Update Teacher' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out;
}
</style>