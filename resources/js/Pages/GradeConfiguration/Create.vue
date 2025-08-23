<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    flash: Object,
});

const form = useForm({
    class_interval: '',
    letter_grade: '',
    grade_point: '',
    status: 0, // Default to active
});

const submit = () => {
    form.post(route('grade-configurations.store'), {
        onSuccess: () => {
            form.reset(); // Clear form after successful submission
        },
        onError: (errors) => {
            console.error('Create errors:', errors);
        },
    });
};

// Helper to display flash messages
const getMessageClass = (type) => {
    if (type === 'success') return 'bg-green-100 border border-green-400 text-green-700';
    if (type === 'error') return 'bg-red-100 border border-red-400 text-red-700';
    return '';
};
</script>

<template>
    <Head title="Create Grade Configuration" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Grade Configuration</h2>
        </template>

        <div class="py-12">
            <div class="max-w-md mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                    <!-- Flash Messages -->
                    <div v-if="props.flash?.success" :class="['p-4 mb-4 text-sm rounded-lg', getMessageClass('success')]" role="alert">
                        {{ props.flash.success }}
                    </div>
                    <div v-if="props.flash?.error" :class="['p-4 mb-4 text-sm rounded-lg', getMessageClass('error')]" role="alert">
                        {{ props.flash.error }}
                    </div>
                    <div v-if="form.hasErrors" class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
                        <p v-for="error in form.errors" :key="error">{{ error }}</p>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <InputLabel for="class_interval" value="Class Interval (e.g., 80-100)" />
                            <TextInput
                                id="class_interval"
                                v-model="form.class_interval"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.class_interval" />
                        </div>

                        <div class="mb-4">
                            <InputLabel for="letter_grade" value="Letter Grade (e.g., A+)" />
                            <TextInput
                                id="letter_grade"
                                v-model="form.letter_grade"
                                type="text"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.letter_grade" />
                        </div>

                        <div class="mb-4">
                            <InputLabel for="grade_point" value="Grade Point (e.g., 5.0)" />
                            <TextInput
                                id="grade_point"
                                v-model="form.grade_point"
                                type="number"
                                step="0.1"
                                min="0"
                                max="5"
                                class="mt-1 block w-full"
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.grade_point" />
                        </div>

                        <div class="mb-4">
                            <InputLabel for="status" value="Status" />
                            <select
                                id="status"
                                v-model="form.status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                                required
                            >
                                <option :value="0">Active</option>
                                <option :value="1">Inactive</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <Link :href="route('grade-configurations.index')" class="mr-4 text-gray-600 hover:text-gray-900">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Save Grade
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>