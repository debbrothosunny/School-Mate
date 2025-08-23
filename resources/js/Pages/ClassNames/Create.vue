<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';


// Props are no longer needed as we're not fetching teachers/sections
// const props = defineProps({
//     teachers: Array,
//     sections: Array,
// });

const form = useForm({
    class_name: '', // Renamed 'name' to 'class_name' for consistency with DB
    status: 0, // Default to Active
    total_classes: 0, // Added total_classes with a default value
});

// The displayedSubject watch is removed as teacher selection is no longer part of this form.

const submit = () => {
    // Only send fields that are in the class_names table
    const dataToSend = {
        class_name: form.class_name,
        status: form.status,
        total_classes: form.total_classes, // Include total_classes in dataToSend
    };

    form.post(route('class-names.store'), dataToSend, {
        onFinish: () => form.reset('class_name', 'status', 'total_classes'), // Reset all relevant fields
    });
};
</script>

<template>
    <Head title="Create New Class" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-weight-semibold fs-4 text-dark leading-tight">Create New Class</h2>
        </template>

        <div class="container-fluid py-4 px-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4 text-dark">
                    <form @submit.prevent="submit">
                        <div class="row g-4">
                            <!-- Class Name -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <InputLabel for="class_name" value="Class Name" class="form-label" />
                                    <TextInput
                                        id="class_name"
                                        type="text"
                                        class="form-control"
                                        v-model="form.class_name"
                                        required
                                        autofocus
                                    />
                                    <InputError class="mt-2" :message="form.errors.class_name" />
                                </div>
                            </div>

                            <!-- Total Classes -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <InputLabel for="total_classes" value="Total Classes for this Class" class="form-label" />
                                    <TextInput
                                        id="total_classes"
                                        type="number"
                                        class="form-control"
                                        v-model.number="form.total_classes"
                                        required
                                        min="0"
                                    />
                                    <InputError class="mt-2" :message="form.errors.total_classes" />
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <InputLabel for="status" value="Status" class="form-label" />
                                    <select
                                        id="status"
                                        class="form-select"
                                        v-model="form.status"
                                        required
                                    >
                                        <option :value="0">Active</option>
                                        <option :value="1">Inactive</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.status" />
                                </div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-end mt-4">
                            <Link :href="route('class-names.index')" class="btn btn-link text-secondary me-3">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Add Class
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