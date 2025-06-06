<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
// Removed Checkbox import as it's no longer used for status

const props = defineProps({
    section: Object, // The section data passed from the controller
});

const form = useForm({
    name: props.section.name,
    // --- ADJUSTED THIS LINE FOR SELECT DROPDOWN ---
    status: props.section.status, // status will be 0 or 1 directly
});

const submit = () => {
    // Optional: Add console.logs for debugging the form data before submission
    // console.log('Form data before submission:', form.data());
    // console.log('Current form.status before submission:', form.status);

    form.put(route('sections.update', props.section.id), {
        onSuccess: () => {
            // Optional: show a success message (e.g., using flash messages)
            // console.log('Section updated successfully!');
        },
        onError: (errors) => {
            console.error("Update failed:", errors);
        },
    });
};
</script>

<template>
    <Head title="Edit Section" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Section: {{ section.name }}</h2>
        </template>

        <!-- Removed mx-auto to align the form to the left within the AuthenticatedLayout's padding -->
        <div class="max-w-md sm:px-0 lg:px-0"> <!-- Removed mx-auto here -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Adjusted padding from p-6 to p-4 for a more compact internal layout -->
                <div class="p-4 text-gray-900">
                    <form @submit.prevent="submit">
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

                        <div class="mt-4">
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
                        <div class="flex items-center justify-end mt-4">
                            <Link :href="route('sections.index')" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Update Section
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
