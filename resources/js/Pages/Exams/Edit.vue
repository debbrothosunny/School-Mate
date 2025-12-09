<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';

const props = defineProps({
    exam: Object, // The exam object to be edited (now includes total_marks & passing_marks)
    sessions: Array, // List of all active sessions for the dropdown
});

const flash = computed(() => usePage().props.flash || {});

// Initialize the form with the current exam's data
const form = useForm({
    exam_name: props.exam.exam_name,
    session_id: props.exam.session_id,
    status: props.exam.status, // Use the actual numeric status from the database
});

const submit = () => {
    // For updating, use form.put() to send a PUT request
    // but directly using form.put() is clearer.
    form.post(route('exams.update', props.exam.id), { // Changed from form.post to form.put
        onSuccess: () => {
            // Flash messages are handled by watchEffect
        },
        onError: (errors) => {
            console.error("Exam update failed:", errors);
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
    <Head :title="`Edit Exam: ${exam.exam_name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Exam: {{ exam.exam_name }}</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title h5 mb-0">Update Exam Details</h3>
                        <Link :href="route('exams.index')" class="btn btn-secondary btn-sm rounded">
                            Back to Exams
                        </Link>
                    </div>

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
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="btn btn-primary">
                                Update Exam
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