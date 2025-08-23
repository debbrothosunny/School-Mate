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

const form = useForm({
    _method: 'post', 
    title: props.notice.title,
    content: props.notice.content,
    notice_date: props.notice.notice_date, // Date should already be in YYYY-MM-DD format from Laravel casting
    status: props.notice.status,
    // IMPORTANT FIX: Convert existing target_user roles to lowercase on initialization
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
            alert(flash.value.message);
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
    <Head :title="`Edit Notice: ${notice.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Notice: {{ notice.title }}</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="title" value="Title" />
                            <TextInput id="title" type="text" class="mt-1 block w-full" v-model="form.title" required autofocus />
                            <InputError class="mt-2" :message="form.errors.title" />
                        </div>

                        <div>
                            <InputLabel for="content" value="Content" />
                            <textarea id="content" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" v-model="form.content" rows="6" required></textarea>
                            <InputError class="mt-2" :message="form.errors.content" />
                        </div>

                        <div>
                            <InputLabel for="notice_date" value="Notice Date" />
                            <TextInput id="notice_date" type="date" class="mt-1 block w-full" v-model="form.notice_date" />
                            <InputError class="mt-2" :message="form.errors.notice_date" />
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
