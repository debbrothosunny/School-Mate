<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, watch } from 'vue';
import { defineProps } from 'vue';

const props = defineProps({
    classes: Array,
    sections: Array,
    groups: Array,
});

const form = useForm({
    name: '',
    subject_taught: '',
    address: '',
    phone_number: '',
    qualification: '',
    joining_date: '',
    password: '',
    password_confirmation: '',
    image: null,
    status: 0,
    designation: 'Junior Teacher',
    is_class_teacher: false,
    class_id: '',
    section_id: '',
    group_id: '',
});

const imageInput = ref(null);
const imagePreviewUrl = ref(null);
const showClassTeacherFields = ref(false);

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

// Watch checkbox and auto-show/hide fields
watch(() => form.is_class_teacher, (val) => {
    showClassTeacherFields.value = val;
    if (!val) {
        form.class_id = '';
        form.section_id = '';
        form.group_id = '';
    }
});

const submit = () => {
    form.post(route('teachers.store'), {
        forceFormData: true,

        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'শিক্ষক/শিক্ষিকা সফলভাবে যোগ করা হয়েছে!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });

            // Reset form fields
            form.reset();

            // Clear image preview
            imagePreviewUrl.value = null;

            // Clear HTML file input manually
            if (imageInput.value) {
                imageInput.value.value = '';
            }
        },

        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Failed!',
                text: 'Could not add teacher. Please check the fields.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });

            console.error("Teacher creation failed:", errors);
        }
    });

};
</script>

<template>
    <Head title="Create Teacher" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                    Create New Teacher
                </h2>
                <Link :href="route('teachers.index')" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400">
                    Back to Teachers
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-2xl p-8">
                    <form @submit.prevent="submit" class="space-y-8">

                        <!-- Personal Information -->
                        <div class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6 border border-gray-200 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Personal Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="name" value="Full Name *"/>
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
                                        <option value="Junior Teacher" selected>Junior Teacher</option>
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
                                    <select v-model="form.class_id" required
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md shadow-sm focus:ring-teal-500 focus:border-teal-500">
                                        <option value="">Select Class</option>
                                        <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                                    </select>
                                    <InputError :message="form.errors.class_id" class="mt-2" />
                                </div>

                                <div>
                                    <InputLabel for="section_id" value="Section *" />
                                    <select v-model="form.section_id" required
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

                        <!-- Login Credentials -->
                        <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6">
                            <h3 class="text-lg font-semibold text-amber-900 dark:text-amber-300 mb-6">Login Credentials</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="password" value="Password *" />
                                    <TextInput id="password" v-model="form.password" type="password" class="mt-1 block w-full" required />
                                    <InputError :message="form.errors.password" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="password_confirmation" value="Confirm Password *" />
                                    <TextInput id="password_confirmation" v-model="form.password_confirmation" type="password" class="mt-1 block w-full" required />
                                    <InputError :message="form.errors.password_confirmation" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <!-- Photo Upload -->
                        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl p-6">
                            <h3 class="text-lg font-semibold text-purple-900 dark:text-purple-300 mb-4">Teacher Photo (Optional)</h3>
                            <input type="file" ref="imageInput" @change="handleImageChange" accept="image/*"
                                class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-white dark:bg-gray-700 focus:outline-none" />

                            <div v-if="imagePreviewUrl" class="mt-4">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Preview:</p>
                                <img :src="imagePreviewUrl" alt="Preview" class="w-32 h-32 object-cover rounded-lg shadow-lg border-4 border-white dark:border-gray-800" />
                            </div>
                            <InputError :message="form.errors.image" class="mt-2" />
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <Link :href="route('teachers.index')" class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-400">
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing" :class="{ 'opacity-75': form.processing }"
                                class="bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white font-medium px-8">
                                {{ form.processing ? 'Creating...' : 'Create Teacher' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.4s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>