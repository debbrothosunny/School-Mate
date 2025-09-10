<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    groupTypes: Array,
    errors: Object,
});

const flash = usePage().props.flash || {};

const form = useForm({
    name: '',
    status: 0, // Default to Active
});

const submit = () => {
    form.post(route('groups.store'), {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Group created successfully.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
            form.reset(); // Reset form after successful creation
        },
        onError: (errors) => {
            console.error('Group creation failed:', errors);
        },
    });
};
</script>

<template>
    <Head title="Create Group" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Group</h2>
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
                                <span v-if="form.processing">Creating...</span>
                                <span v-else>Create Group</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
