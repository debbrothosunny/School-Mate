<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { computed, watch } from 'vue';


const props = defineProps({
    settings: Object,
});

// Create an Inertia form with the initial data from the props
const form = useForm({
    school_name: props.settings.school_name || '',
    address: props.settings.address || '',
    phone_number: props.settings.phone_number || '',
    email: props.settings.email || '',
    principal_name: props.settings.principal_name || '',
    principal_signature: props.settings.principal_signature || '',
    school_logo: props.settings.school_logo || '',
    current_session: props.settings.current_session || '',
});

const submit = () => {
    // The form submission method should be `post` to match your 'settings.update' route
    form.post(route('settings.update'), {
        preserveScroll: true,
    });
};

// Watch for flash messages and show a SweetAlert toast
const flash = computed(() => usePage().props.flash || {});

watch(() => flash.value, (newFlash) => {
    if (newFlash && newFlash.message) {
        Swal.fire({
            icon: newFlash.type === 'success' ? 'success' : 'error',
            title: newFlash.type === 'success' ? 'Success!' : 'Error!',
            text: newFlash.message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }
}, { deep: true });
</script>

<template>
    <Head title="Edit School Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit School Settings
            </h2>
        </template>

        <div class="py-12 bg-gray-100">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- School Name -->
                            <div>
                                <label for="school_name" class="block text-sm font-medium text-gray-700">School Name</label>
                                <input id="school_name" type="text" v-model="form.school_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                            </div>

                            <!-- Principal Name -->
                            <div>
                                <label for="principal_name" class="block text-sm font-medium text-gray-700">Principal Name</label>
                                <input id="principal_name" type="text" v-model="form.principal_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                            </div>

                            <!-- Address -->
                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                                <textarea id="address" v-model="form.address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"></textarea>
                            </div>

                            <!-- Phone Number -->
                            <div>
                                <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                <input id="phone_number" type="text" v-model="form.phone_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                            </div>

                            <!-- Email -->
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input id="email" type="email" v-model="form.email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                            </div>

                            

                            <!-- Current Session -->
                            <div>
                                <label for="current_session" class="block text-sm font-medium text-gray-700">Current Session</label>
                                <input id="current_session" type="text" v-model="form.current_session" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                            </div>

                            <!-- TODO: Add file inputs for principal_signature and school_logo here -->
                            <!-- This will require updating the form to handle file uploads. -->
                        </div>

                        <div class="mt-6 flex justify-end">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Save Settings
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
