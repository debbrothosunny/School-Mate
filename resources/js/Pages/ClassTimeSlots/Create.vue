<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import Swal from 'sweetalert2';

const form = useForm({
    name: '',
    start_time: '',
    end_time: '',
});

const submit = () => {
    form.post(route('class-time-slots.store'), {
        onSuccess: () => form.reset(),
    });
};

// Access page props to catch flash messages
const page = usePage();
const flash = computed(() => ({
    message: page.props.message || '',
    type: page.props.type || '',
}));

// Watch flash message updates to trigger SweetAlert toast
watch(flash, (val) => {
    if (val.message) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            icon: val.type === 'success' ? 'success' : 'error',
            title: val.message,
        });
    }
});
</script>


<template>
    <Head title="Create Time Slot"/>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Time Slot</h2>
        </template>
        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title h5 mb-0">Time Slot Details</h3>
                        <Link :href="route('class-time-slots.index')" class="btn btn-secondary btn-sm rounded">
                            Back to List
                        </Link>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <InputLabel for="name" value="Slot Name" class="form-label" />
                                <TextInput id="name" type="text" class="form-control" v-model="form.name" required autofocus autocomplete="name" />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>
                            <div class="col-md-3">
                                <InputLabel for="start_time" value="Start Time" class="form-label" />
                                <TextInput id="start_time" type="time" class="form-control" v-model="form.start_time" required />
                                <InputError class="mt-2" :message="form.errors.start_time" />
                            </div>
                            <div class="col-md-3">
                                <InputLabel for="end_time" value="End Time" class="form-label" />
                                <TextInput id="end_time" type="time" class="form-control" v-model="form.end_time" required />
                                <InputError class="mt-2" :message="form.errors.end_time" />
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Create Time Slot
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
