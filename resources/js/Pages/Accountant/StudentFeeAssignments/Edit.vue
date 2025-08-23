<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';

const props = defineProps({
    studentFeeAssignment: Object, // The student fee assignment object to be edited
    students: Array,
    feeTypes: Array,
});

const flash = computed(() => usePage().props.flash || {});

// A more robust way to handle the status value
const initialStatus = props.studentFeeAssignment.status !== null 
    ? parseInt(props.studentFeeAssignment.status, 10) 
    : 0; // Default to 0 (active) if status is null or undefined

const form = useForm({
    student_id: props.studentFeeAssignment.student_id,
    fee_type_id: props.studentFeeAssignment.fee_type_id,
    // Fix for date inputs: Extract the 'YYYY-MM-DD' part of the date string
    applies_from: props.studentFeeAssignment.applies_from ? props.studentFeeAssignment.applies_from.slice(0, 10) : null,
    applies_to: props.studentFeeAssignment.applies_to ? props.studentFeeAssignment.applies_to.slice(0, 10) : null,
    // Use the robust initial status value
    status: initialStatus,
});

const submit = () => {
    form.post(route('student-fee-assignments.update', props.studentFeeAssignment.id), {
        onSuccess: () => {
            // No need to reset form, Inertia will re-render with updated data
        },
        onError: (errors) => {
            console.error("Error updating student fee assignment:", errors);
        },
    });
};

watchEffect(() => {
    if (typeof Swal !== 'undefined' && flash.value && flash.value.message) {
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
    <Head title="Edit Student Fee Assignment" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Student Fee Assignment</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <InputLabel for="student_id" value="Student" class="form-label" />
                                <select id="student_id" class="form-select" v-model="form.student_id" required>
                                    <option value="" disabled>-- Select Student --</option>
                                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.name }} ({{ student.admission_number }})</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.student_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="fee_type_id" value="Fee Type" class="form-label" />
                                <select id="fee_type_id" class="form-select" v-model="form.fee_type_id" required>
                                    <option value="" disabled>-- Select Fee Type --</option>
                                    <option v-for="feeType in feeTypes" :key="feeType.id" :value="feeType.id">
                                        {{ feeType.name }} ({{ feeType.frequency }})
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.fee_type_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="applies_from" value="Applies From Date" class="form-label" />
                                <TextInput id="applies_from" type="date" class="form-control" v-model="form.applies_from" required />
                                <InputError class="mt-2" :message="form.errors.applies_from" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="applies_to" value="Applies To Date (Optional)" class="form-label" />
                                <TextInput id="applies_to" type="date" class="form-control" v-model="form.applies_to" />
                                <InputError class="mt-2" :message="form.errors.applies_to" />
                                
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="status" value="Status" class="form-label" />
                                <select id="status" class="form-select" v-model.number="form.status" required>
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <Link :href="route('student-fee-assignments.index')" class="btn btn-secondary me-3">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="btn btn-primary">
                                Update Assignment
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>