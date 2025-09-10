<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { computed, watchEffect, ref } from 'vue';


const props = defineProps({
    notice: Object, // The notice object to edit
    availableRoles: Array, // Array of roles (e.g., ['all', 'student', 'teacher'])
    flash: Object, // Flash messages
    errors: Object, // Validation errors
});

const flash = computed(() => usePage().props.flash || {});

// Function to format date string to YYYY-MM-DD
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const form = useForm({
    _method: 'post',
    notice_title: props.notice.notice_title,
    content: props.notice.content,
    // FIX: Format the date string for the HTML date input
    start_date: formatDate(props.notice.start_date),
    end_date: formatDate(props.notice.end_date),
    status: props.notice.status,
    target_user: props.notice.target_user ? props.notice.target_user.map(role => role.toLowerCase()) : [],
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
        }
    }
});

const submit = () => {
    form.post(route('notices.update', props.notice.id), {
        onSuccess: () => {
            // No need to reset form on edit success, data should persist
        },
        onError: (errors) => {
            console.error("Notice update failed:", errors);
        },
    });
};
</script>

<template>
    <Head :title="`Edit Notice: ${notice.notice_title}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Notice: {{ notice.notice_title }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="notice_title" value="Title" />
                            <TextInput id="notice_title" type="text" class="mt-1 block w-full" v-model="form.notice_title" required autofocus />
                            <InputError class="mt-2" :message="form.errors.notice_title" />
                        </div>

                        <div>
                            <InputLabel for="content" value="Content" />
                            <textarea id="content" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" v-model="form.content" rows="6" required></textarea>
                            <InputError class="mt-2" :message="form.errors.content" />
                        </div>

                        <div>
                            <InputLabel for="start_date" value="Start Date" />
                            <TextInput id="start_date" type="date" class="mt-1 block w-full" v-model="form.start_date" />
                            <InputError class="mt-2" :message="form.errors.start_date" />
                        </div>

                        <div>
                            <InputLabel for="end_date" value="End Date" />
                            <TextInput id="end_date" type="date" class="mt-1 block w-full" v-model="form.end_date" />
                            <InputError class="mt-2" :message="form.errors.end_date" />
                        </div>

                        <div>
                            <InputLabel for="status" value="Status" />
                            <select id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" v-model.number="form.status" required>
                                <option :value="0">Active</option>
                                <option :value="1">Inactive</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>

                        <div>
                            <InputLabel value="Target Users" class="mb-2" />
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                <div v-for="role in availableRoles" :key="role" class="flex items-center">
                                    <Checkbox :id="`target_user_${role}`" :value="role" v-model:checked="form.target_user" />
                                    <InputLabel :for="`target_user_${role}`" class="ml-2 capitalize">{{ role }}</InputLabel>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.target_user" />
                            <InputError v-if="form.errors['target_user.0']" class="mt-2" :message="form.errors['target_user.0']" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <Link :href="route('notices.index')" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Update Notice
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
