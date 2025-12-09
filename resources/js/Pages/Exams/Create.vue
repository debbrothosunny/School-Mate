<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import Swal from 'sweetalert2'; // Ensure Swal is available

const props = defineProps({
    sessions: Array, // List of active sessions for the dropdown
});

const form = useForm({
    exam_name: '',
    session_id: '',
    status: 0, // Default to Active (0)
});

// Helper function for standardizing required labels
const requiredLabel = (label) => {
    return `${label} `;
};

const submit = () => {
    form.post(route('exams.store'), {
        onSuccess: () => {
            // Only reset non-essential fields, keeping default marks for quick entry
            form.reset('exam_name', 'session_id'); 
        },
        onError: (errors) => {
            console.error("Exam creation failed:", errors);
        },
    });
};

// Accessing flash messages from the Inertia page props
const page = usePage();
const flash = computed(() => page.props.flash || {});

// Watch for flash messages and display SweetAlert
watchEffect(() => {
    if (flash.value && flash.value.message) {
        Swal.fire({
            icon: flash.value.type === 'success' ? 'success' : 'error',
            title: flash.value.type === 'success' ? 'Success!' : 'Error!',
            text: flash.value.message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }
});

// Custom select component class for consistency and dark mode support
const selectClass = "mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out";

// Watch total_marks to ensure passing_marks is never greater
watchEffect(() => {
    // Robust validation logic: Only run comparison if both values are set (not null)
    if (form.total_marks != null && form.passing_marks != null) {
        if (form.passing_marks > form.total_marks) {
            form.passing_marks = form.total_marks;
        }
    }
});
</script>

<template>
    <Head title="Add New Exam" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Add New Exam</h2>
        </template>

        <div class="py-8 sm:py-12 bg-gray-50 dark:bg-gray-900">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-2xl sm:rounded-xl">
                    <div class="p-6 sm:p-10 border-b border-gray-200 dark:border-gray-700">
                        
                        <div class="flex justify-between items-center mb-6 pb-4 border-b dark:border-gray-700">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Exam Details</h3>
                            <Link :href="route('exams.index')"
                                class="inline-flex items-center px-4 py-2 bg-indigo-500 text-white border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest shadow-md hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                View All Exams
                            </Link>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <div>
                                <InputLabel for="exam_name" class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ requiredLabel('Exam Name') }} <span class="text-red-500 font-normal">(Required)</span>
                                </InputLabel>
                                <TextInput
                                    id="exam_name"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg"
                                    v-model="form.exam_name"
                                    required
                                    autofocus
                                    autocomplete="off"
                                    placeholder="e.g., Annual Final Examination 2025"
                                />
                                <InputError class="mt-2" :message="form.errors.exam_name" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel for="session_id" class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ requiredLabel('Academic Session') }} <span class="text-red-500 font-normal">(Required)</span>
                                    </InputLabel>
                                    <select v-model="form.session_id" id="session_id" :class="selectClass" required>
                                        <option value="" disabled>-- Select Academic Session --</option>
                                        <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.session_id" />
                                </div>
                                
                                <div>
                                    <InputLabel for="status" class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ requiredLabel('Status') }} 
                                    </InputLabel>
                                    <select v-model.number="form.status" id="status" :class="selectClass" required>
                                        <option :value="0">Active</option>
                                        <option :value="1">Inactive</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.status" />
                                </div>
                            </div>
                            
                            

                            <div class="flex items-center justify-end pt-6 border-t border-gray-200 dark:border-gray-700 mt-8">
                                <Link :href="route('exams.index')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-3">
                                    Cancel
                                </Link>
                                <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing">
                                    Add Exam
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
