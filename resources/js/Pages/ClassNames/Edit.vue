<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const props = defineProps({
    className: Object, // The class data passed from the controller, now includes total_classes
});

// Initialize the form with the current class's data
const form = useForm({
    _method: 'post', 
    class_name: props.className.class_name, // Bind to class_name from prop
    status: props.className.status, // status will be 0 or 1 directly
    total_classes: props.className.total_classes, // Initialize with existing total_classes
});

const submit = () => {
    // Only send fields that are in the class_names table
    const dataToSend = {
        class_name: form.class_name,
        status: form.status,
        total_classes: form.total_classes, // Include total_classes in dataToSend
    };

    form.post(route('class-names.update', props.className.id), dataToSend, {
        onSuccess: () => {
            // Optional: show a success message (e.g., using flash messages)
        },
        onError: (errors) => {
            console.error("Update failed:", errors);
        },
    });
};
</script>

<template>
    <Head title="Edit Class" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Class: {{ className.class_name }}</h2>
        </template>

        <div class="py-4 px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-4 text-gray-900">
                    <h2 class="text-xl font-bold mb-4">Edit Class</h2>

                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <InputLabel for="class_name" value="Class Name" />
                            <TextInput id="class_name" type="text" class="mt-1 block w-full" v-model="form.class_name" required autofocus />
                            <InputError :message="form.errors.class_name" class="mt-2" />
                        </div>

                        <div class="mb-4">
                            <InputLabel for="total_classes" value="Total Classes for this Class" />
                            <TextInput
                                id="total_classes"
                                type="number"
                                class="mt-1 block w-full"
                                v-model.number="form.total_classes"
                                required
                                min="0"
                            />
                            <InputError :message="form.errors.total_classes" class="mt-2" />
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
                            <InputError :message="form.errors.status" class="mt-2" />
                        </div>

                        <div class="mt-6 flex justify-end">
                            <Link :href="route('class-names.index')" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</Link>
                            <PrimaryButton :disabled="form.processing">Update Class</PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>