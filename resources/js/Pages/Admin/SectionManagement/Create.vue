<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue'; // Still needed for name
import { Head, useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    status: 0, // Add status, default to 0 (Active)
});

const submit = () => {
    form.post(route('sections.store'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Add Section" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Section</h2>
        </template>

        <!-- Removed the redundant py-12 as AuthenticatedLayout now provides the main padding. -->
        <!-- Changed max-w-md mx-auto to max-w-3xl and removed mx-auto for left alignment, removed sm:px-6 and lg:px-8 for direct control -->
        <div class="max-w-3xl sm:px-0 lg:px-0">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Adjusted inner padding from p-6 to p-4 for a more compact internal layout -->
                <div class="p-4 text-gray-900">
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <InputLabel for="name" value="Section Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div class="mb-4">
                                <InputLabel for="status" value="Status" />
                                <select
                                    id="status"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    v-model="form.status"
                                    required
                                >
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <Link :href="route('sections.index')" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Add Section
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
