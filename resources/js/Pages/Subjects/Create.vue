<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';

const flash = computed(() => usePage().props.flash || {});

const form = useForm({
    name: '',
    code: '',
    full_marks: '',
    passing_marks: '',
    status: 0,
});

const submit = () => {
    form.post(route('subjects.store'), {
        onSuccess: () => form.reset(),
        onError: (errors) => console.error("Subject creation failed:", errors),
    });
};

watchEffect(() => {
    if (flash.value?.message) {
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
    <Head title="Add New Subject" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Subject</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <InputLabel for="name" value="Subject Name" class="form-label" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="form-control"
                                    v-model="form.name"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="code" value="Subject Code" class="form-label" />
                                <TextInput
                                    id="code"
                                    type="text"
                                    class="form-control"
                                    v-model="form.code"
                                />
                                <InputError class="mt-2" :message="form.errors.code" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="full_marks" value="Full Marks" class="form-label" />
                                <TextInput
                                    id="full_marks"
                                    type="number"
                                    min="1"
                                    class="form-control"
                                    v-model.number="form.full_marks"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.full_marks" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="passing_marks" value="Passing Marks" class="form-label" />
                                <TextInput
                                    id="passing_marks"
                                    type="number"
                                    min="0"
                                    :max="form.full_marks"
                                    class="form-control"
                                    v-model.number="form.passing_marks"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.passing_marks" />
                            </div>

                            <div class="col-md-6">
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
                            <Link :href="route('subjects.index')" class="btn btn-secondary me-3">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="btn btn-primary">
                                Add Subject
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Bootstrap handles layout and spacing */
</style>
