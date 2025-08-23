<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';

const props = defineProps({
    classFeeStructure: Object, // The class fee structure object to be edited
    classes: Array,
    sessions: Array,
    groups: Array,
    sections: Array,
    feeTypes: Array, // This prop should contain default_amount for each fee type
});

const flash = computed(() => usePage().props.flash || {});

const form = useForm({
    class_id: props.classFeeStructure.class_id,
    session_id: props.classFeeStructure.session_id,
    group_id: props.classFeeStructure.group_id,
    section_id: props.classFeeStructure.section_id,
    fee_type_id: props.classFeeStructure.fee_type_id,
    // Initialize amount directly from the prop, assuming it's already a decimal (BDT)
    amount: parseFloat(props.classFeeStructure.amount), // Use parseFloat to ensure it's a number
    status: Number(props.classFeeStructure.status), // Ensure status is a number (0 or 1)
});

const submit = () => {
    // No paisa conversion needed here, as per your instruction.
    // The 'amount' will be sent as a decimal directly from form.amount.

    form.post(route('class-fee-structures.update', props.classFeeStructure.id), {
        onSuccess: () => {
            // No need to reset form, it will be re-rendered with updated data
        },
        onError: (errors) => {
            console.error("Error updating class fee structure:", errors);
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
    <Head title="Edit Class Fee Structure" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Class Fee Structure</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <InputLabel for="class_id" value="Class" class="form-label" />
                                <select id="class_id" class="form-select" v-model="form.class_id" required>
                                    <option value="" disabled>-- Select Class --</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.class_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="session_id" value="Session" class="form-label" />
                                <select id="session_id" class="form-select" v-model="form.session_id" required>
                                    <option value="" disabled>-- Select Session --</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.session_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="group_id" value="Group" class="form-label" />
                                <select id="group_id" class="form-select" v-model="form.group_id">
                                    <option :value="null">-- Select Group --</option>
                                    <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.group_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="section_id" value="Section" class="form-label" />
                                <select id="section_id" class="form-select" v-model="form.section_id">
                                    <option :value="null">-- Select Section --</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.section_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="fee_type_id" value="Fee Type" class="form-label" />
                                <select id="fee_type_id" class="form-select" v-model="form.fee_type_id" required>
                                    <option value="" disabled>-- Select Fee Type --</option>
                                    <option v-for="feeType in feeTypes" :key="feeType.id" :value="feeType.id">
                                        {{ feeType.name }} ({{ feeType.frequency }}) - Default: BDT {{ (feeType.default_amount / 100).toFixed(2) }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.fee_type_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="amount" value="Amount (BDT)" class="form-label" />
                                <TextInput
                                    id="amount"
                                    type="number"
                                    step="0.01"
                                    class="form-control"
                                    v-model="form.amount"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.amount" />
                                <small class="text-gray-500">
                                    Enter the specific amount for this Class/Session/Fee Type combination.
                                </small>
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
                            <Link :href="route('class-fee-structures.index')" class="btn btn-secondary me-3">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="btn btn-primary">
                                Update Fee Structure
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>