<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    teachers: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    class_name: '',
    status: 0,
    total_classes: 0,
    teacher_id: '',
});

const submit = () => {
    form.post(route('class-names.store'), {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Class created successfully.',
                toast: true,
                position: 'top-end',
                timer: 3000,
                showConfirmButton: false,
                timerProgressBar: true,
            });
            form.reset('class_name', 'status', 'total_classes', 'teacher_id');
        },
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to create the class. Please check the form and try again.',
                toast: true,
                position: 'top-end',
                timer: 3000,
                showConfirmButton: false,
                timerProgressBar: true,
            });
        }
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
                            <!-- Teacher Name Dropdown -->
                            <div class="col-12">
                                <div class="mb-3">
                                    <InputLabel for="teacher_id" value="Assigned Class Teacher" class="form-label" />
                                    <select
                                        id="teacher_id"
                                        class="form-select"
                                        v-model="form.teacher_id"
                                        required
                                    >
                                        <option value="" disabled>Select a Teacher</option>
                                        <!-- Loop through the teachers prop to create dropdown options -->
                                        <option
                                            v-for="teacher in teachers"
                                            :key="teacher.id"
                                            :value="teacher.id"
                                        >
                                            {{ teacher.name }} ({{ teacher.subject_taught }})
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.teacher_id" />
                                </div>
                            </div>

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
