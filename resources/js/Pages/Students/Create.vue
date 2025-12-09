<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watchEffect } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    classes: Array,
    sessions: Array,
    groups: Array,
    sections: Array,
    availableUsers: Array,
});

const flash = computed(() => usePage().props.flash || {});
const imagePreviewUrl = ref(null);
const nextRollNumber = ref('—');

const form = useForm({
    name: '',
    class_id: '',
    age: '',
    date_of_birth: '',
    gender: '',
    admission_date: '',
    session_id: '',
    group_id: '',
    section_id: '',
    user_id: '',
    parent_name: '',
    address: '',
    contact: '',
    image: null,
    blood_group: '',
    status: 0,
    enrollment_status: 'admitted',
    admission_fee_amount: null,
    admission_fee_paid: false,
    payment_method: 'Cash',
});

const updateNextRollNumber = async () => {
    if (form.class_id && form.section_id && form.group_id && form.session_id) {
        try {
            const response = await axios.get(route('students.next-roll'), {
                params: {
                    class_id: form.class_id,
                    section_id: form.section_id,
                    group_id: form.group_id,
                    session_id: form.session_id,
                },
            });
            nextRollNumber.value = response.data.next_roll || '1';
        } catch (error) {
            nextRollNumber.value = '?';
        }
    } else {
        nextRollNumber.value = '—';
    }
};

watchEffect(() => {
    updateNextRollNumber();
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
    const dataToSend = { ...form.data() };
    dataToSend.user_id = dataToSend.user_id === '' ? null : dataToSend.user_id;
    dataToSend.admission_fee_amount = dataToSend.admission_fee_amount === '' ? null : parseFloat(dataToSend.admission_fee_amount);

    form.post(route('students.store'), {
        data: dataToSend,
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: `শিক্ষার্থী সফলভাবে যোগ করা হয়েছে! Roll No: ${nextRollNumber.value}`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
            });
            form.reset();
            imagePreviewUrl.value = null;
            nextRollNumber.value = '—';
        },
    });
};
</script>

<template>
    <Head title="Add New Student"/>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">Add New Student</h2>
                    <p class="text-gray-600">Complete student registration form</p>
                </div>
            </div>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto">
                <!-- Main Form Card -->
                <div class="bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-200">
                    <!-- Header Section -->
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-8 text-white">
                        <h3 class="text-3xl font-bold flex items-center gap-3">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h-4m-8 0H3"/>
                            </svg>
                            Student Registration Form
                        </h3>
                        <p class="mt-2 opacity-90">Fill all required fields marked with *</p>
                    </div>

                    <!-- Form Content -->
                    <div class="p-8">
                        <form @submit.prevent="submit" enctype="multipart/form-data">
                            <!-- Academic Details Section -->
                            <div class="mb-12">
                                <h4 class="text-2xl font-bold text-gray-900 mb-8 flex items-center gap-3 border-b-2 border-blue-100 pb-4">
                                    <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center">
                                        <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                        </svg>
                                    </div>
                                    Academic Details
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <!-- Student Name -->
                                    <div>
                                        <InputLabel for="name" value="Student Name *" class="text-lg font-semibold text-gray-800 mb-3 block" />
                                        <TextInput 
                                            id="name" 
                                            type="text" 
                                            class="w-full h-14 px-5 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all duration-200" 
                                            v-model="form.name" 
                                            required 
                                            autofocus 
                                        />
                                        <InputError :message="form.errors.name" class="mt-2 text-red-600 font-medium" />
                                    </div>

                                    <!-- Class -->
                                    <div>
                                        <InputLabel for="class_id" value="Class *" class="text-lg font-semibold text-gray-800 mb-3 block" />
                                        <select id="class_id" class="w-full h-14 px-3 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-blue-500 focus:ring-4 focus:ring-blue-100 appearance-none bg-white" v-model="form.class_id" required>
                                            <option value="" disabled>Select Class</option>
                                            <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                                        </select>
                                        <InputError :message="form.errors.class_id" class="mt-2 text-red-600 font-medium" />
                                    </div>

                                    <!-- Session -->
                                    <div>
                                        <InputLabel for="session_id" value="Session *" class="text-lg font-semibold text-gray-800 mb-3 block" />
                                        <select id="session_id" class="w-full h-14 px-3 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 appearance-none bg-white" v-model="form.session_id" required>
                                            <option value="" disabled>Select Session</option>
                                            <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                        </select>
                                        <InputError :message="form.errors.session_id" class="mt-2 text-red-600 font-medium" />
                                    </div>

                                    <!-- Section -->
                                    <div>
                                        <InputLabel for="section_id" value="Section *" class="text-lg font-semibold text-gray-800 mb-3 block"/>
                                        <select id="section_id" class="w-full h-14 px-3 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-green-500 focus:ring-4 focus:ring-green-100 appearance-none bg-white" v-model="form.section_id" required>
                                            <option value="" disabled>Select Section</option>
                                            <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                        </select>
                                        <InputError :message="form.errors.section_id" class="mt-2 text-red-600 font-medium" />
                                    </div>

                                    <!-- Group -->
                                    <div>
                                        <InputLabel for="group_id" value="Group *" class="text-lg font-semibold text-gray-800 mb-3 block" />
                                        <select id="group_id" class="w-full h-14 px-3 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-purple-500 focus:ring-4 focus:ring-purple-100 appearance-none bg-white" v-model="form.group_id" required>
                                            <option value="" disabled>Select Group</option>
                                            <option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
                                        </select>
                                        <InputError :message="form.errors.group_id" class="mt-2 text-red-600 font-medium" />
                                    </div>

                                    <!-- Roll Number Preview -->
                                    <div class="lg:col-span-3">
                                        <InputLabel value="Next Roll Number" class="text-lg font-semibold text-gray-800 mb-3 block"/>
                                        <div class="p-8 bg-gradient-to-r from-blue-50 to-indigo-50 border-4 border-blue-200 rounded-3xl text-center shadow-xl">
                                            <div class="text-6xl font-black text-blue-800 mb-3 tracking-wide">{{ nextRollNumber }}</div>
                                            <div class="text-blue-700 font-semibold text-lg">Auto-generated roll number</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Personal Details Section -->
                            <div class="mb-12">
                                <h4 class="text-2xl font-bold text-gray-900 mb-8 flex items-center gap-3 border-b-2 border-green-100 pb-4">
                                    <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center">
                                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    Personal Information
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- DOB & Age -->
                                    <div>
                                        <InputLabel for="date_of_birth" value="Date of Birth *" class="text-lg font-semibold text-gray-800 mb-3 block"/>
                                        <TextInput id="date_of_birth" type="date" class="w-full h-14 px-3 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" v-model="form.date_of_birth" required />
                                        <InputError :message="form.errors.date_of_birth" class="mt-2 text-red-600 font-medium"/>
                                    </div>
                                    <div>
                                        <InputLabel for="age" value="Age *" class="text-lg font-semibold text-gray-800 mb-3 block" />
                                        <TextInput id="age" type="number" class="w-full h-14 px-5 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100" v-model.number="form.age" min="0" required />
                                        <InputError :message="form.errors.age" class="mt-2 text-red-600 font-medium" />
                                    </div>

                                    <!-- Gender & Blood Group -->
                                    <div>
                                        <InputLabel for="gender" value="Gender *" class="text-lg font-semibold text-gray-800 mb-3 block"/>
                                        <select id="gender" class="w-full h-14 px-3 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-pink-500 focus:ring-4 focus:ring-pink-100 appearance-none bg-white" v-model="form.gender" required>
                                            <option value="" disabled>Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        <InputError :message="form.errors.gender" class="mt-2 text-red-600 font-medium" />
                                    </div>
                                    <div>
                                        <InputLabel for="blood_group" value="Blood Group" class="text-lg font-semibold text-gray-800 mb-3 block" />
                                        <select id="blood_group" class="w-full h-14 px-3 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-red-500 focus:ring-4 focus:ring-red-100 appearance-none bg-white" v-model="form.blood_group">
                                            <option value="" disabled>Select Blood Group</option>
                                            <option>A+</option><option>A-</option><option>B+</option><option>B-</option>
                                            <option>AB+</option><option>AB-</option><option>O+</option><option>O-</option>
                                        </select>
                                    </div>

                                    <!-- Admission Date -->
                                    <div class="md:col-span-2">
                                        <InputLabel for="admission_date" value="Admission Date *" class="text-lg font-semibold text-gray-800 mb-3 block" />
                                        <TextInput id="admission_date" type="date" class="w-full h-14 px-3 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-orange-500 focus:ring-4 focus:ring-orange-100" v-model="form.admission_date" required />
                                        <InputError :message="form.errors.admission_date" class="mt-2 text-red-600 font-medium" />
                                    </div>
                                </div>
                            </div>

                            <!-- Guardian Details Section -->
                            <div class="mb-12">
                                <h4 class="text-2xl font-bold text-gray-900 mb-8 flex items-center gap-3 border-b-2 border-purple-100 pb-4">
                                    <div class="w-12 h-12 bg-purple-100 rounded-2xl flex items-center justify-center">
                                        <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857"/>
                                        </svg>
                                    </div>
                                    Guardian Information
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div>
                                        <InputLabel for="parent_name" value="Parent/Guardian Name *" class="text-lg font-semibold text-gray-800 mb-3 block" />
                                        <TextInput id="parent_name" type="text" class="w-full h-14 px-5 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-cyan-500 focus:ring-4 focus:ring-cyan-100" v-model="form.parent_name" required />
                                        <InputError :message="form.errors.parent_name" class="mt-2 text-red-600 font-medium" />
                                    </div>
                                    <div>
                                        <InputLabel for="contact" value="Contact Number *" class="text-lg font-semibold text-gray-800 mb-3 block" />
                                        <TextInput id="contact" type="tel" class="w-full h-14 px-5 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-teal-500 focus:ring-4 focus:ring-teal-100" v-model="form.contact" required />
                                        <InputError :message="form.errors.contact" class="mt-2 text-red-600 font-medium" />
                                    </div>
                                    <div>
                                        <InputLabel for="address" value="Address *" class="text-lg font-semibold text-gray-800 mb-3 block" />
                                        <TextInput id="address" type="text" class="w-full h-14 px-5 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-violet-500 focus:ring-4 focus:ring-violet-100" v-model="form.address" required />
                                        <InputError :message="form.errors.address" class="mt-2 text-red-600 font-medium" />
                                    </div>
                                </div>
                            </div>

                            <!-- Photo & Status Section -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                                <!-- Photo Upload -->
                                <div>
                                    <InputLabel value="Student Photo *" class="text-lg font-semibold text-gray-800 mb-4 block" />
                                    <div class="border-2 border-dashed border-gray-300 rounded-3xl p-8 text-center hover:border-blue-400 transition-colors">
                                        <input type="file" id="image" @change="handleImageChange" class="hidden" accept="image/*" />
                                        <label for="image" class="cursor-pointer block">
                                            <div v-if="imagePreviewUrl" class="w-48 h-48 mx-auto rounded-2xl overflow-hidden shadow-lg border-4 border-blue-200">
                                                <img :src="imagePreviewUrl" class="w-full h-full object-cover" />
                                            </div>
                                            <div v-else class="w-48 h-48 mx-auto bg-gray-50 border-4 border-dashed border-gray-300 rounded-2xl flex flex-col items-center justify-center text-gray-500">
                                                <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                                <div class="text-lg font-semibold">Upload Photo</div>
                                                <div class="text-sm text-gray-500 mt-1">JPG, PNG up to 5MB</div>
                                            </div>
                                        </label>
                                    </div>
                                    <InputError :message="form.errors.image" class="mt-3 text-red-600 font-medium" />
                                </div>

                                <!-- Status & Enrollment -->
                                <div class="space-y-6">
                                    <div>
                                        <InputLabel for="status" value="Status" class="text-lg font-semibold text-gray-800 mb-3 block" />
                                        <select id="status" class="w-full h-14 px-3 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-gray-500 focus:ring-4 focus:ring-gray-100 appearance-none bg-white" v-model.number="form.status">
                                            <option :value="0">Active</option>
                                            <option :value="1">Inactive</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel for="enrollment_status *" value="Enrollment Status *" class="text-lg font-semibold text-gray-800 mb-3 block" />
                                        <select id="enrollment_status" class="w-full h-14 px-3 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-indigo-500 focus:ring-4 focus:ring-indigo-100 appearance-none bg-white" v-model="form.enrollment_status" required>
                                            <option value="applied">Applied</option>
                                            <option value="under_review">Under Review</option>
                                            <option value="admitted">Admitted</option>
                                            <option value="enrolled">Enrolled</option>
                                            <option value="rejected">Rejected</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Section -->
                            <div class="mb-12">
                                <h4 class="text-2xl font-bold text-gray-900 mb-8 flex items-center gap-3 border-b-2 border-emerald-100 pb-4">
                                    <div class="w-12 h-12 bg-emerald-100 rounded-2xl flex items-center justify-center">
                                        <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                        </svg>
                                    </div>
                                    Admission Fee Details
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    <div>
                                        <InputLabel for="admission_fee_amount" value="Admission Fee *(BDT)" class="text-lg font-semibold text-gray-800 mb-3 block" />
                                        <TextInput type="number" step="0.01" class="w-full h-14 px-5 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100" v-model="form.admission_fee_amount" />
                                    </div>
                                    <div>
                                        <InputLabel for="payment_method" value="Payment Method *" class="text-lg font-semibold text-gray-800 mb-3 block"/>
                                        <select class="w-full h-14 px-3 py-3 border-2 border-gray-200 rounded-2xl text-lg focus:border-emerald-500 focus:ring-4 focus:ring-emerald-100 appearance-none bg-white" v-model="form.payment_method">
                                            <option value="">Select Method</option>
                                            <option>Cash</option>
                                            <option>bKash</option>
                                            <option>Nagad</option>
                                            <option>Bank Transfer</option>
                                        </select>
                                    </div>
                                    <div class="lg:col-span-1 flex items-end">
                                        <div class="flex items-center p-4 bg-emerald-50 border-2 border-emerald-200 rounded-2xl">
                                            <input class="w-5 h-5 text-emerald-600 border-emerald-400 rounded focus:ring-emerald-500" type="checkbox" id="admission_fee_paid" v-model="form.admission_fee_paid">
                                            <label for="admission_fee_paid" class="ml-3 text-lg font-semibold text-emerald-800">Fee Paid *</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-4 pt-8 border-t-2 border-gray-100">
                                <Link :href="route('students.index')" 
                                    class="px-12 py-4 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-lg rounded-2xl border-2 border-gray-200 flex-1 text-center h-16 flex items-center justify-center shadow-sm">
                                    Cancel
                                </Link>
                                <PrimaryButton :disabled="form.processing" 
                                    class="px-16 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-xl rounded-2xl shadow-xl flex-1 h-16 flex items-center justify-center">
                                    {{ form.processing ? 'Adding Student...' : 'Add Student' }}
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 1rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 3rem;
}

select:focus {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%233b82f6' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
}
</style>
