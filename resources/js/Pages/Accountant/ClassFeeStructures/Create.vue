<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, watchEffect, watch } from 'vue'; // Import 'watch'

const props = defineProps({
    classes: Array,
    sessions: Array,
    groups: Array,
    sections: Array,
    feeTypes: Array, // This prop should contain default_amount for each fee type
});

const flash = computed(() => usePage().props.flash || {});

const form = useForm({
    class_id: '',
    session_id: '',
    group_id: null, // Nullable
    section_id: null, // Nullable
    fee_type_id: '',
    amount: null, // <-- ADDED: For the class-specific amount
    status: 0, // Default to Active
});

// Watch for changes in fee_type_id to pre-fill the amount field
watch(() => form.fee_type_id, (newFeeTypeId) => {
    if (newFeeTypeId) {
        const selectedFeeType = props.feeTypes.find(ft => ft.id === newFeeTypeId);
        if (selectedFeeType && selectedFeeType.default_amount !== null) {
            // IMPORTANT: If default_amount from backend is in paisa,
            // you *still need* to divide by 100 for display.
            // If default_amount from backend is *already* a float (e.g., 2000.00),
            // then remove the / 100.
            // Assuming default_amount is coming from backend in paisa (e.g., 200000 for 2000 BDT)
            form.amount = (selectedFeeType.default_amount / 100).toFixed(2);
        } else {
            form.amount = null; // Clear if no default amount or fee type not found
        }
    } else {
        form.amount = null; // Clear if no fee type is selected
    }
});

const submit = () => {
    // Create a copy of the form data to ensure we don't modify the original reactivity
    const dataToSend = { ...form.data() };

    // REMOVED PAISA CONVERSION LOGIC HERE as per your request.
    // dataToSend.amount will now be sent as a floating point number.
    // Ensure your backend validation and database column type can handle decimals.

    form.post(route('class-fee-structures.store'), {
        data: dataToSend, // Pass the modified data
        onSuccess: () => {
            form.reset();
        },
        onError: (errors) => {
            console.error("Error creating class fee structure:", errors);
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
    <Head title="Add Class Fee Structure" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add Class Fee Structure</h2>
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
                                        {{ feeType.name }} ({{ feeType.frequency }}) 
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
                                    This will be pre-filled with the Fee Type's default amount. Adjust if needed.
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
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing">
                                Add Fee Structure
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>