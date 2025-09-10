<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    session: Object,
    errors: Object,
});

const flash = computed(() => usePage().props.flash || {});

console.log('Sessions/Edit.vue: Component loaded.');
console.log('Sessions/Edit.vue: props.session:', props.session);
if (props.session) {
    console.log('Sessions/Edit.vue: props.session.name:', props.session.name);
    console.log('Sessions/Edit.vue: props.session.status:', props.session.status, ' (Type:', typeof props.session.status + ')');
} else {
    console.log('Sessions/Edit.vue: props.session is null or undefined.');
}

// Initialize the form with current session data
const form = useForm({
    _method: 'post', 
    name: props.session.name ?? '',
    status: Number(props.session.status),
});

const submit = () => {
    form.post(route('sessions.update', props.session.id), {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Session updated successfully.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        },
        onError: (errors) => {
            console.error("Session update failed:", errors);
        },
    });
};

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
    <Head :title="`Edit Session: ${session.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Session: {{ session.name }}
            </h2>
        </template>
        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title h5 mb-0">Update Session</h3>
                        <Link :href="route('sessions.index')" class="btn btn-secondary btn-sm rounded">
                            Back to Sessions
                        </Link>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <InputLabel for="name" value="Session Name" class="form-label" />
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
                                <InputLabel for="status" value="Status" class="form-label" />
                                <select id="status" class="form-select" v-model.number="form.status" required>
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="btn btn-primary">
                                <span v-if="form.processing">Updating...</span>
                                <span v-else>Update Session</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
