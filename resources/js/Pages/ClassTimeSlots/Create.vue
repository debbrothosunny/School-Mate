<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Swal from 'sweetalert2';

const form = useForm({
    name: '',
    start_time: '',
    end_time: '',
});

const submit = () => {
    form.post(route('class-time-slots.store'), {
        onSuccess: () => form.reset(),
    });
};

// Access page props to catch flash messages
const page = usePage();
const flash = computed(() => ({
    // Adjusted access to handle common Inertia flash message structures
    message: page.props.flash?.message || '',
    type: page.props.flash?.type || '',
}));

// Watch flash message updates to trigger SweetAlert toast
watch(flash, (val) => {
    if (val.message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            icon: val.type === 'success' ? 'success' : 'error',
            title: val.message,
        });
    }
}, { deep: true });
</script>

<template>
    <Head title="Create Time Slot"/>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Create New Time Slot</h2>
        </template>
        
        <div class="py-12 bg-gray-100 dark:bg-gray-900">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 sm:p-8">
                    
                    <div class="flex justify-between items-center mb-6 border-b pb-4 dark:border-gray-700">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Time Slot Details</h3>
                        <Link :href="route('class-time-slots.index')" 
                              class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-700 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 focus:bg-gray-300 dark:focus:bg-gray-600 active:bg-gray-400 dark:active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Back to List
                        </Link>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            
                            <div class="md:col-span-4 lg:col-span-2">
                                <InputLabel for="name" class="block mb-1">
                                    Slot Name <span class="font-bold text-red-500 dark:text-red-400">(Required)</span>
                                </InputLabel>
                                <TextInput 
                                    id="name" 
                                    type="text" 
                                    class="mt-1 block w-full" 
                                    v-model="form.name" 
                                    required 
                                    autofocus 
                                    autocomplete="name"
                                    placeholder="e.g., Class 1, Recess, Lunch Break" 
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div class="md:col-span-2 lg:col-span-1">
                                <InputLabel for="start_time" class="block mb-1">
                                    Start Time <span class="font-bold text-red-500 dark:text-red-400">(Required)</span>
                                </InputLabel>
                                <TextInput 
                                    id="start_time" 
                                    type="time" 
                                    class="mt-1 block w-full" 
                                    v-model="form.start_time" 
                                    required 
                                    placeholder="08:00"
                                />
                                <InputError class="mt-2" :message="form.errors.start_time" />
                            </div>

                            <div class="md:col-span-2 lg:col-span-1">
                                <InputLabel for="end_time" class="block mb-1">
                                    End Time <span class="font-bold text-red-500 dark:text-red-400">(Required)</span>
                                </InputLabel>
                                <TextInput 
                                    id="end_time" 
                                    type="time" 
                                    class="mt-1 block w-full" 
                                    v-model="form.end_time" 
                                    required 
                                    placeholder="08:45"
                                />
                                <InputError class="mt-2" :message="form.errors.end_time" />
                            </div>
                        </div>

                        <div class="flex justify-end pt-6 border-t dark:border-gray-700 mt-6">
                            <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Create Time Slot
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>