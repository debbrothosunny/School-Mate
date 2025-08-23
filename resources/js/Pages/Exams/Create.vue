<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';

const props = defineProps({
    sessions: Array, // List of active sessions for the dropdown
});

const flash = computed(() => usePage().props.flash || {});

const form = useForm({
    exam_name: '',
    session_id: '', // To be selected from dropdown
    total_marks: 0, // ✨ NEW: Default total marks for the exam (e.g., 50) ✨
    passing_marks: 0, // ✨ NEW: Default passing marks for the exam (e.g., 30) ✨
    status: 0, // Default to Active
});

const submit = () => {
    form.post(route('exams.store'), {
        onSuccess: () => {
            form.reset(); // Clear form fields after successful submission
        },
        onError: (errors) => {
            console.error("Exam creation failed:", errors);
        },
    });
};

// Watch for flash messages and display SweetAlert
watchEffect(() => {
    if (flash.value && flash.value.message) {
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
    }
});
</script>

<template>
    <Head title="Add New Exam" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Exam</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <div class="col-12">
                                <InputLabel for="exam_name" value="Exam Name" class="form-label" />
                                <TextInput
                                    id="exam_name"
                                    type="text"
                                    class="form-control"
                                    v-model="form.exam_name"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.exam_name" />
                            </div>

                            <div class="col-12">
                                <InputLabel for="session_id" value="Select Session" class="form-label" />
                                <select
                                    id="session_id"
                                    class="form-select"
                                    v-model="form.session_id"
                                    required
                                >
                                    <option value="" disabled>-- Select Session --</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.session_id" />
                            </div>

                            <!-- ✨ NEW: Total Marks Input ✨ -->
                            <div class="col-12">
                                <InputLabel for="total_marks" value="Total Marks (for each subject in this exam)" class="form-label" />
                                <TextInput
                                    id="total_marks"
                                    type="number"
                                    class="form-control"
                                    v-model.number="form.total_marks"
                                    required
                                    min="0"
                                />
                                <InputError class="mt-2" :message="form.errors.total_marks" />
                            </div>

                            <!-- ✨ NEW: Passing Marks Input ✨ -->
                            <div class="col-12">
                                <InputLabel for="passing_marks" value="Passing Marks (for each subject in this exam)" class="form-label" />
                                <TextInput
                                    id="passing_marks"
                                    type="number"
                                    class="form-control"
                                    v-model.number="form.passing_marks"
                                    required
                                    min="0"
                                />
                                <InputError class="mt-2" :message="form.errors.passing_marks" />
                            </div>

                            <div class="col-12">
                                <InputLabel for="status" value="Status" class="form-label" />
                                <select
                                    id="status"
                                    class="form-select"
                                    v-model.number="form.status"
                                    required
                                >
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <Link :href="route('exams.index')" class="btn btn-secondary me-3">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="btn btn-primary">
                                Add Exam
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No additional scoped styles needed as Bootstrap handles most of it. */
</style>