<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { computed, watchEffect } from 'vue';


// Define props received from the controller
const props = defineProps({
    book: Object, // The book object to edit
});

// Computed property for flash messages, similar to Exams/Create.vue
const flash = computed(() => usePage().props.flash || {});

// Form data using Inertia's useForm helper, initialized with existing book data
const form = useForm({
    _method: 'post', // Important for PUT request
    title: props.book.title,
    author: props.book.author,
    publisher: props.book.publisher,
    publication_date: props.book.publication_date,
    isbn: props.book.isbn,
    quantity: props.book.quantity,
    available_quantity: props.book.available_quantity, // Added available_quantity
    genre: props.book.genre,
    cover_image: null, // For new file upload
    remove_cover_image: false, // Checkbox to explicitly remove existing image
    status: props.book.status, // Directly use the numeric status (0 or 1)
});

// Watch for flash messages and display SweetAlert, using watchEffect for immediate execution
watchEffect(() => {
    if (flash.value && flash.value.message) {
        // Ensure Swal is available globally (e.g., via a CDN in app.blade.php)
        if (typeof Swal !== 'undefined') {
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
        } else {
            console.warn('Swal (SweetAlert2) is not defined. Flash messages will not be displayed via Swal.');
            // Fallback for displaying messages if Swal is not available
            alert(flash.value.message);
        }
    }
});

// Function to handle form submission
const submit = () => {
    // --- DEBUGGING LOGS START ---
    console.log('Current form.status (numeric from select):', form.status);
    // --- DEBUGGING LOGS END ---

    // No need for conversion here, as v-model directly provides 0 or 1
    form.post(route('books.update', props.book.id), {
        // data: dataToSend, // Removed, as form.data() already contains the correct numeric status
        forceFormData: true, // Important for file uploads
        onSuccess: () => {
            // No need to reset form here, as it's an edit page and data should persist
            // Flash message will be handled by watchEffect
        },
        onError: (errors) => {
            console.error("Book update failed:", errors);
        },
    });
};

// Handle file input change
const handleCoverImageChange = (event) => {
    form.cover_image = event.target.files[0];
    form.remove_cover_image = false; // If a new image is selected, don't remove existing
};

// Handle remove image checkbox change
const handleRemoveImageChange = () => {
    if (form.remove_cover_image) {
        form.cover_image = null; // If removing, clear any selected new image
    }
};
</script>

<template>
    <Head :title="`Edit Book: ${book.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Book: {{ book.title }}</h2>
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
                                    min="0"
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

                                <div v-if="book.cover_image_path" class="mt-4 flex items-center space-x-4">
                                    <img :src="`/storage/${book.cover_image_path}`" alt="Current Cover" class="h-24 w-auto object-cover rounded-md" />
                                    <div class="flex items-center">
                                        <Checkbox id="remove_cover_image" v-model:checked="form.remove_cover_image" @change="handleRemoveImageChange" class="form-check-input" />
                                        <InputLabel for="remove_cover_image" class="form-check-label ml-2">Remove current image</InputLabel>
                                    </div>
                                </div>
                            </div>

                            <!-- Status (Select Dropdown) -->
                            <div class="col-12">
                                <InputLabel for="status" value="Status" class="form-label" />
                                <select
                                    id="status"
                                    class="form-select"
                                    v-model="form.status"
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
                                Update Book
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
