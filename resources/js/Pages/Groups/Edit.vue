<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { computed, watchEffect } from 'vue';



const props = defineProps({
    group: Object, // The group object to edit
    groupTypes: Array, // Array of allowed group names (e.g., ['Science', 'Arts', 'Commerce', 'None'])
    flash: Object, // Flash messages
    errors: Object, // Validation errors
});

// Computed property for flash messages, ensuring reactivity
const flash = computed(() => usePage().props.flash || {});

// Form data using Inertia's useForm helper, initialized with existing group data
const form = useForm({
    _method: 'post', 
    name: props.group.name,
    status: props.group.status, // Boolean (0/1) from Laravel will be handled by select correctly
});

// Watch for flash messages and display SweetAlert
watchEffect(() => {
    if (flash.value && flash.value.message) {
        if (typeof Swal !== 'undefined') {
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
        } else {
            console.warn('Swal (SweetAlert2) is not defined. Flash messages will not be displayed via Swal.');
            alert(flash.value.message);
        }
    }
});

// Function to handle form submission
const submit = () => {
    form.post(route('groups.update', props.group.id), {
        onSuccess: () => {
            // No need to reset form on edit success, data should persist
        },
        onError: (errors) => {
            console.error("Group update failed:", errors);
        },
    });
};
</script>

<template>
    <Head :title="`Edit Group: ${group.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Group: {{ group.name }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Group Name" />
                            <select
                                id="name"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                v-model="form.name"
                                required
                            >
                                <option value="" disabled>-- Select Group Type --</option>
                                <option v-for="type in groupTypes" :key="type" :value="type">{{ type }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div>
                            <InputLabel for="status" value="Status" />
                            <select
                                id="status"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                v-model.number="form.status"
                                required
                            >
                                <option :value="0">Active</option>
                                <option :value="1">Inactive</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <Link :href="route('groups.index')" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Update Group
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No specific styles needed beyond TailwindCSS for this component */
</style>
