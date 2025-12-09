<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

// The only prop needed is the class record being edited
const props = defineProps({
    className: Object,
});

// --- Form Initialization ---
const form = useForm({
    // Simplified form state to match the table
    class_name: props.className.class_name || '',
    status: Number(props.className.status),
});

// --- Submission Logic: Using PUT for update ---
const submit = () => {
    // Use form.put() for update actions
    form.post(route('class-names.update', props.className.id), { 
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'সফল!',
                text: 'ক্লাসের নাম সফলভাবে আপডেট হয়েছে!', // Adjusted message
                toast: true,
                position: 'top-end',
                timer: 3000,
                showConfirmButton: false,
                timerProgressBar: true,
            });
        },
        onError: () => {
            let errorMessage = 'ক্লাসের নাম আপডেট করতে সমস্যা হয়েছে।';
            if (form.errors && Object.keys(form.errors).length > 0) {
                errorMessage = 'দয়া করে ফর্মের ভুলগুলো ঠিক করুন।';
            }
            
            Swal.fire({
                icon: 'error',
                title: 'ভুল!',
                text: errorMessage,
                toast: true,
                position: 'top-end',
                timer: 3000,
                showConfirmButton: false,
                timerProgressBar: true,
            });
        },
    });
};
</script>

<template>
    <Head title="Edit Class Name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">
                Edit Class Name: **{{ className.class_name }}**
            </h2>
        </template>

        <div class="py-8 px-4">
            <div class="max-w-lg mx-auto bg-white dark:bg-gray-900 rounded-2xl shadow-xl p-8">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Class Name Input -->
                    <div>
                        <InputLabel for="class_name">Class Name <span class="text-red-500 text-xs">(required)</span></InputLabel>
                        <TextInput
                            id="class_name"
                            v-model="form.class_name"
                            required
                            placeholder="যেমন: Six (or 'ষষ্ঠ শ্রেণী')" 
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.class_name" class="mt-2" />
                    </div>
                    
                    <!-- Status Dropdown -->
                    <div>
                        <InputLabel for="status">Status</InputLabel>
                        <select
                            id="status"
                            v-model="form.status"
                            class="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2.5 dark:bg-gray-800 dark:text-gray-200"
                        >
                            <option :value="0">Active</option>
                            <option :value="1">Inactive</option>
                        </select>
                        <InputError :message="form.errors.status" class="mt-2" />
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-4 mt-8">
                        <Link :href="route('class-names.index')" class="text-gray-600 dark:text-gray-400 hover:text-teal-600 dark:hover:text-teal-400 transition duration-150">Cancel</Link>
                        <PrimaryButton :disabled="form.processing" class="bg-teal-600 hover:bg-teal-700">
                            {{ form.processing ? 'Updating...' : 'Update Class Name' }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>