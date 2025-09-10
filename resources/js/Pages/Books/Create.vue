<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { computed, watchEffect } from 'vue';
import Swal from 'sweetalert2';

// Form state
const form = useForm({
    title: '',
    author: '',
    publisher: '',
    publication_date: '',
    isbn: '',
    quantity: 1,
    available_quantity: 1,
    genre: '',
    cover_image: null,
    status: 0,
});

// Handle file input change
const handleCoverImageChange = (event) => {
    form.cover_image = event.target.files[0];
};

// Submit handler using Inertia
const submit = () => {
    form.post(route('books.store'), {
        forceFormData: true, // important for file uploads
        onSuccess: () => form.reset(),
        onError: (errors) => {
            console.error('Book creation failed:', errors);
        },
    });
};

// Access flash messages from Inertia page props explicitly mapping message and type
const page = usePage();
const flash = computed(() => ({
    message: page.props.message || '',
    type: page.props.type || '',
}));

// Watch flash and show SweetAlert2 toast when message exists
watchEffect(() => {
    if (flash.value.message) {
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
    <Head title="Add New Book" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Book</h2>
        </template>
        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <!-- Title -->
                            <div class="col-12">
                                <InputLabel for="title" value="Title" class="form-label" />
                                <TextInput
                                    id="title"
                                    type="text"
                                    class="form-control"
                                    v-model="form.title"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.title" />
                            </div>
                            <!-- Author -->
                            <div class="col-12">
                                <InputLabel for="author" value="Author" class="form-label" />
                                <TextInput
                                    id="author"
                                    type="text"
                                    class="form-control"
                                    v-model="form.author"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.author" />
                            </div>
                            <!-- Publisher -->
                            <div class="col-12">
                                <InputLabel for="publisher" value="Publisher" class="form-label" />
                                <TextInput
                                    id="publisher"
                                    type="text"
                                    class="form-control"
                                    v-model="form.publisher"
                                />
                                <InputError class="mt-2" :message="form.errors.publisher" />
                            </div>
                            <!-- Publication Date -->
                            <div class="col-12">
                                <InputLabel for="publication_date" value="Publication Date" class="form-label" />
                                <TextInput
                                    id="publication_date"
                                    type="date"
                                    class="form-control"
                                    v-model="form.publication_date"
                                />
                                <InputError class="mt-2" :message="form.errors.publication_date" />
                            </div>
                            <!-- ISBN -->
                            <div class="col-12">
                                <InputLabel for="isbn" value="ISBN" class="form-label" />
                                <TextInput
                                    id="isbn"
                                    type="text"
                                    class="form-control"
                                    v-model="form.isbn"
                                />
                                <InputError class="mt-2" :message="form.errors.isbn" />
                            </div>
                            <!-- Quantity -->
                            <div class="col-12">
                                <InputLabel for="quantity" value="Quantity" class="form-label" />
                                <TextInput
                                    id="quantity"
                                    type="number"
                                    class="form-control"
                                    v-model="form.quantity"
                                    required
                                    min="1"
                                    @input="form.available_quantity = form.quantity"
                                />
                                <InputError class="mt-2" :message="form.errors.quantity" />
                            </div>
                            <!-- Available Quantity -->
                            <div class="col-12">
                                <InputLabel for="available_quantity" value="Available Quantity" class="form-label" />
                                <TextInput
                                    id="available_quantity"
                                    type="number"
                                    class="form-control"
                                    v-model="form.available_quantity"
                                    required
                                    min="0"
                                    readonly
                                />
                                <InputError class="mt-2" :message="form.errors.available_quantity" />
                            </div>
                            <!-- Genre -->
                            <div class="col-12">
                                <InputLabel for="genre" value="Genre" class="form-label" />
                                <TextInput
                                    id="genre"
                                    type="text"
                                    class="form-control"
                                    v-model="form.genre"
                                />
                                <InputError class="mt-2" :message="form.errors.genre" />
                            </div>
                            <!-- Cover Image -->
                            <div class="col-12">
                                <InputLabel for="cover_image" value="Cover Image" class="form-label" />
                                <input
                                    type="file"
                                    id="cover_image"
                                    @change="handleCoverImageChange"
                                    class="form-control"
                                />
                                <InputError class="mt-2" :message="form.errors.cover_image" />
                            </div>
                            <!-- Status -->
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
                            <Link :href="route('books.index')" class="btn btn-secondary me-3">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="btn btn-primary">
                                Add Book
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No additional styles required */
</style>
