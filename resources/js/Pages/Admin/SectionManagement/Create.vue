<script setup>
import { useForm, usePage, Head, Link } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import Swal from 'sweetalert2';

// Components
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const page = usePage();
const flash = computed(() => page.props.flash || {});

const form = useForm({
    name: '',
    status: 0,
});

const submit = () => {
    form.post(route('sections.store'), {
    forceFormData: true, // optional if you might upload files in future
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'সেকশন সফলভাবে তৈরি করা হয়েছে!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
            form.reset(); // Clear the form fields
        },
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Failed!',
                text: 'Could not create section. Please check the fields.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
            console.error("Section creation failed:", errors);
        },
        onFinish: () => {
            // You can perform any additional cleanup here if needed
        },
    });

};

watchEffect(() => {
    if (flash.value?.message) {
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

</script>

<template>
    <Head title="Add Section" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-3xl font-bold text-gray-900 dark:text-gray-100 tracking-tight">
                Add New Section
            </h2>
        </template>

        <div class="py-8 px-4 sm:px-6 lg:px-8">
            <div 
                class="card bg-white dark:bg-gray-900 rounded-2xl shadow-xl max-w-lg mx-auto overflow-hidden transition-all duration-300 transform hover:scale-[1.01]"
            >
                <div class="card-body p-8">
                    <h3 class="text-center text-3xl font-bold text-gray-900 dark:text-white mb-8">
                        Create New Section
                    </h3>

                    <form @submit.prevent="submit" class="space-y-6" novalidate>
                        <div class="relative">
                            <InputLabel 
                                for="name" 
                                class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center gap-1"
                            >
                                Section Name 
                                <span class="text-red-500 text-xs">(required)</span>
                            </InputLabel>
                            <TextInput
                                id="name"
                                type="text"
                                v-model="form.name"
                                placeholder="e.g., A"
                                required
                                autofocus
                                class="mt-1 block w-full rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-300 py-2.5 px-3"
                            />
                            <InputError class="mt-1.5 text-xs" :message="form.errors.name" />
                        </div>

                        <div class="relative">
                            <InputLabel 
                                for="status" 
                                class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center gap-1"
                            >
                                Status 
                                
                            </InputLabel>
                            <select
                                id="status"
                                v-model="form.status"
                                required
                                class="mt-1 block w-full rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-200 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-300 py-2.5 px-3"
                            >
                                <option :value="0">Active</option>
                                <option :value="1">Inactive</option>
                            </select>
                            <InputError class="mt-1.5 text-xs" :message="form.errors.status" />
                        </div>

                        <div class="flex items-center justify-end space-x-4 pt-6">
                            <Link
                                :href="route('sections.index')"
                                class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors duration-200"
                            >
                                Cancel
                            </Link>
                            <PrimaryButton
                                :class="[
                                    'flex items-center justify-center space-x-2 px-6 py-2.5 text-base font-bold rounded-lg shadow-md transform transition-all duration-200',
                                    form.processing ? 'opacity-50 cursor-not-allowed' : 'hover:scale-105 active:scale-95',
                                    'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 text-white',
                                ]"
                                :disabled="form.processing"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"
                                    ></path>
                                </svg>
                                <span>{{ form.processing ? 'Adding...' : 'Add Section' }}</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Scoped styles for the card hover effect and red text */
.card {
    transition: all 0.3s ease-in-out;
}

.card:hover {
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
}

.text-red-500 {
    font-size: 0.75rem;
    line-height: 1;
}

input, select {
    transition: all 0.3s ease-in-out;
}

input:focus, select:focus {
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
}
</style>