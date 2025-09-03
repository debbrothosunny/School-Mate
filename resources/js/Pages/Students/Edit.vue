<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watchEffect } from 'vue';

// Helper function to format date to YYYY-MM-DD
const formatToHtmlDate = (dateString) => {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        // Ensure month and day are padded with a leading zero if needed
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    } catch (e) {
        console.error("Error formatting date:", dateString, e);
        return '';
    }
};

const props = defineProps({
    student: Object,
    classes: Array,
    sessions: Array,
    groups: Array,
    sections: Array,
    availableUsers: Array,
    flash: Object,
    errors: Object,
});

const flash = computed(() => usePage().props.flash || {});

// Reactive variable for image preview
const imagePreviewUrl = ref(props.student.image ? `/storage/${props.student.image}` : null);

// Initialize the form with the current student's data
const form = useForm({
    // Use 'patch' for a more RESTful approach. Inertia will handle the spoofing.
    _method: 'post',
    name: props.student.name,
    admission_number: props.student.admission_number,
    roll_number: props.student.roll_number,
    date_of_birth: formatToHtmlDate(props.student.date_of_birth),
    gender: props.student.gender,
    admission_date: formatToHtmlDate(props.student.admission_date),
    age: props.student.age,
    parent_name: props.student.parent_name,
    contact: props.student.contact,
    address: props.student.address,
    class_id: props.student.class_id,
    session_id: props.student.session_id,
    group_id: props.student.group_id,
    section_id: props.student.section_id,
    status: props.student.status,
    enrollment_status: props.student.enrollment_status,
    image: null, // Add image field for new file upload
    user_id: props.student.user_id,

    // New fields
    admission_fee_amount: props.student.admission_fee_amount ? props.student.admission_fee_amount / 100 : null,
    admission_fee_paid: !!props.student.admission_fee_paid,
    payment_method: props.student.payment_method || '',
});

const handleImageChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreviewUrl.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else {
        form.image = null;
        imagePreviewUrl.value = null;
    }
};

const submit = () => {
    // Send the form data to the update route.
    // Inertia's useForm helper automatically handles method spoofing and FormData.
    form.post(route('students.update', props.student.id), {
        forceFormData: true, // This is crucial for file uploads with Inertia.js
        onSuccess: () => {
            // Optional: you can add a success message or redirect here,
            // but your watchEffect for flash messages already handles this.
        },
        onError: (errors) => {
            console.error("Student update failed:", errors);
        },
    });
};

// Watch for flash messages and display SweetAlert
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
    <Head title="Edit Student" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Student: {{ student.name }}</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form @submit.prevent="submit" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <InputLabel for="admission_number" value="Admission Number" class="form-label" />
                                <TextInput id="admission_number" type="text" class="form-control" v-model="form.admission_number" required disabled />
                                <InputError class="mt-2" :message="form.errors.admission_number" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="roll_number" value="Roll Number" class="form-label" />
                                <TextInput id="roll_number" type="number" class="form-control" v-model.number="form.roll_number" required />
                                <InputError class="mt-2" :message="form.errors.roll_number" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="name" value="Student Name" class="form-label" />
                                <TextInput id="name" type="text" class="form-control" v-model="form.name" required autofocus />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="date_of_birth" value="Date of Birth" class="form-label" />
                                <TextInput id="date_of_birth" type="date" class="form-control" v-model="form.date_of_birth" required />
                                <InputError class="mt-2" :message="form.errors.date_of_birth" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="age" value="Age" class="form-label" />
                                <TextInput id="age" type="number" class="form-control" v-model.number="form.age" min="0" required />
                                <InputError class="mt-2" :message="form.errors.age" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="gender" value="Gender" class="form-label" />
                                <select id="gender" class="form-select" v-model="form.gender" required>
                                    <option value="" disabled>-- Select Gender --</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.gender" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="admission_date" value="Admission Date" class="form-label" />
                                <TextInput id="admission_date" type="date" class="form-control" v-model="form.admission_date" required />
                                <InputError class="mt-2" :message="form.errors.admission_date" />
                            </div>

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
                                <select id="group_id" class="form-select" v-model="form.group_id" required>
                                    <option value="" disabled>-- Select Group --</option>
                                    <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.group_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="section_id" value="Section" class="form-label" />
                                <select id="section_id" class="form-select" v-model="form.section_id" required>
                                    <option value="" disabled>-- Select Section --</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.section_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="parent_name" value="Parent Name" class="form-label" />
                                <TextInput id="parent_name" type="text" class="form-control" v-model="form.parent_name" required />
                                <InputError class="mt-2" :message="form.errors.parent_name" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="contact" value="Contact Number" class="form-label" />
                                <TextInput id="contact" type="text" class="form-control" v-model="form.contact" required />
                                <InputError class="mt-2" :message="form.errors.contact" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="address" value="Address" class="form-label" />
                                <TextInput id="address" type="text" class="form-control" v-model="form.address" required />
                                <InputError class="mt-2" :message="form.errors.address" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="user_id" value="Associated User Account" class="form-label" />
                                <select id="user_id" class="form-select" v-model="form.user_id">
                                    <option value="">-- Select User (Optional) --</option>
                                    <option v-for="user in availableUsers" :key="user.id" :value="user.id">
                                        {{ user.name }} ({{ user.email }})
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.user_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="image" value="Student Image" class="form-label" />
                                <input
                                    type="file"
                                    id="image"
                                    @change="handleImageChange"
                                    class="form-control"
                                    accept="image/*"
                                />
                                <InputError class="mt-2" :message="form.errors.image" />
                                <div v-if="imagePreviewUrl" class="mt-2">
                                    <img :src="imagePreviewUrl" alt="Image Preview" class="img-thumbnail" style="width: 128px; height: 128px; object-fit: cover;" />
                                </div>
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="status" value="Status" class="form-label" />
                                <select id="status" class="form-select" v-model.number="form.status" required>
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="enrollment_status" value="Enrollment Status" class="form-label" />
                                <select id="enrollment_status" class="form-select" v-model="form.enrollment_status" required>
                                    <option value="" disabled>-- Select Status --</option>
                                    <option value="applied">Applied</option>
                                    <option value="under_review">Under Review</option>
                                    <option value="admitted">Admitted</option>
                                    <option value="enrolled">Enrolled</option>
                                    <option value="rejected">Rejected</option>
                                    <option value="waitlisted">Waitlisted</option>
                                    <option value="withdrawn">Withdrawn</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.enrollment_status" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="admission_fee_amount" value="Admission Fee Amount (BDT)" class="form-label" />
                                <TextInput id="admission_fee_amount" type="number" step="0.01" class="form-control" v-model="form.admission_fee_amount" />
                                <InputError class="mt-2" :message="form.errors.admission_fee_amount" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="payment_method" value="Payment Method" class="form-label" />
                                <select id="payment_method" class="form-select" v-model="form.payment_method">
                                    <option value="">-- Select Payment Method (Optional) --</option>
                                    <option value="Cash">Cash</option>
                                    <option value="bKash">bKash</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.payment_method" />
                            </div>

                            <div class="col-md-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="admission_fee_paid" v-model="form.admission_fee_paid">
                                    <label class="form-check-label" for="admission_fee_paid">
                                        Admission Fee Paid
                                    </label>
                                    <InputError class="mt-2" :message="form.errors.admission_fee_paid" />
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <Link :href="route('students.index')" class="btn btn-secondary me-3">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="btn btn-primary">
                                Update Student
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No specific styles needed here, as Bootstrap provides styling */
</style>