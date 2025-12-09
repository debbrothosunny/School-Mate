<script setup>
import { useForm, Link, Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Swal from 'sweetalert2';

const form = useForm({
    name: '',
    start_time: '',
    end_time: '',
});

const submit = () => {
    form.post(route('exam-time-slots.store'), {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Exam time slot created successfully.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
            // Reset fields but keep them focused for sequential entry if needed
            form.reset('name', 'start_time', 'end_time'); 
        },
    });
};

// Helper function for standardizing required labels
const requiredLabel = (label) => {
    return `${label} `;
};
</script>


<template>
    <Head title="Create Exam Time Slot" />

    <AuthenticatedLayout>
        <!-- Page Header Slot -->
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Create New Time Slot</h2>
        </template>

        <div class="py-8 sm:py-12 bg-gray-50 dark:bg-gray-900">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-2xl sm:rounded-xl">
                    <div class="p-6 sm:p-10">
                        
                        <!-- Header and View Button -->
                        <div class="flex justify-between items-center mb-8 pb-4 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Time Slot Details</h3>
                            <Link :href="route('exam-time-slots.index')"
                                class="inline-flex items-center px-4 py-2 bg-indigo-500 text-white border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest shadow-md hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                View All Slots
                            </Link>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            
                            <!-- Slot Name -->
                            <div>
                                <InputLabel for="name" class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ requiredLabel('Slot Name') }} <span class="text-red-500 font-normal">(Required)</span>
                                </InputLabel>
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg"
                                    :class="{ 'border-red-500': form.errors.name }"
                                    placeholder="e.g., 10AM-12PM, 1PM-3PM"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Start Time -->
                                <div>
                                    <InputLabel for="start_time" class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ requiredLabel('Start Time') }} <span class="text-red-500 font-normal">(Required)</span>
                                    </InputLabel>
                                    <!-- Using TextInput for consistent styling, but setting type to time -->
                                    <TextInput
                                        id="start_time"
                                        v-model="form.start_time"
                                        type="time"
                                        class="mt-1 block w-full rounded-lg"
                                        :class="{ 'border-red-500': form.errors.start_time }"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.start_time" />
                                </div>

                                <!-- End Time -->
                                <div>
                                    <InputLabel for="end_time" class="block mb-1 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                        {{ requiredLabel('End Time') }} <span class="text-red-500 font-normal">(Required)</span>
                                    </InputLabel>
                                    <TextInput
                                        id="end_time"
                                        v-model="form.end_time"
                                        type="time"
                                        class="mt-1 block w-full rounded-lg"
                                        :class="{ 'border-red-500': form.errors.end_time }"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.end_time" />
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end pt-6 border-t border-gray-200 dark:border-gray-700 mt-8">
                                <Link
                                    :href="route('exam-time-slots.index')"
                                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest shadow-sm hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 mr-3"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing">
                                    <span v-if="form.processing">Creating...</span>
                                    <span v-else>Create Slot</span>
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
