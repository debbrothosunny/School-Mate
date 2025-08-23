<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';

const props = defineProps({
    frequencies: Array, // Array of available fee frequencies (e.g., ['monthly', 'biannual', ...])
});

const flash = computed(() => usePage().props.flash || {});

const form = useForm({
    name: '',
    description: '',
    // default_amount field has been removed
    frequency: '',
    status: 0, // Default to Active
});

const submit = () => {
    // No conversion logic for default_amount is needed as it's no longer in the form
    const dataToSend = { ...form.data() };

    form.post(route('fee-types.store'), {
        onSuccess: () => {
            form.reset();
        },
        onError: (errors) => {
            console.error("Error creating fee type:", errors);
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
    <Head title="Add Fee Type" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Fee Type</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <InputLabel for="name" value="Fee Type Name" class="form-label" />
                                <TextInput id="name" type="text" class="form-control" v-model="form.name" required autofocus />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="frequency" value="Frequency" class="form-label" />
                                <select id="frequency" class="form-select" v-model="form.frequency" required>
                                    <option value="" disabled>-- Select Frequency --</option>
                                    <option v-for="freq in frequencies" :key="freq" :value="freq">
                                        {{ freq.charAt(0).toUpperCase() + freq.slice(1).replace('_', ' ') }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.frequency" />
                            </div>
                            <div class="col-md-6">
                                <InputLabel for="status" value="Status" class="form-label" />
                                <select id="status" class="form-select" v-model.number="form.status" required>
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>

                            <div class="col-md-12">
                                <InputLabel for="description" value="Description (Optional)" class="form-label" />
                                <textarea id="description" class="form-control" v-model="form.description"></textarea>
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <Link :href="route('fee-types.index')" class="btn btn-secondary me-3">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="btn btn-primary">
                                Add Fee Type
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>