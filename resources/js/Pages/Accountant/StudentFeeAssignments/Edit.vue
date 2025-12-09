```vue
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';

const props = defineProps({
    studentFeeAssignment: Object,
    students: Array,
    feeTypes: Array,
});

const flash = computed(() => usePage().props.flash || {});

const initialStatus = props.studentFeeAssignment.status !== null && props.studentFeeAssignment.status !== undefined
    ? parseInt(props.studentFeeAssignment.status, 10)
    : 0; // Default to 0 (active) if status is null or undefined

const form = useForm({
    student_id: props.studentFeeAssignment.student_id || '',
    fee_type_id: props.studentFeeAssignment.fee_type_id || '',
    status: initialStatus,
});

const submit = () => {
    console.log('Form data:', form.data()); // Debugging
    form.post(route('student-fee-assignments.update', props.studentFeeAssignment.id), {
        preserveScroll: true,
        onSuccess: () => {
            // No need to reset form, Inertia will re-render with updated data
        },
        onError: (errors) => {
            console.error("Error updating student fee assignment:", errors);
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
    // Debug initial status
    console.log('Initial status:', initialStatus);
    console.log('Form status:', form.status);
});
</script>

<template>
    <Head title="Edit Student Fee Assignment" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-lg sm:text-xl md:text-2xl text-gray-800 leading-tight">Edit Student Fee Assignment</h2>
        </template>
        <div class="py-4 sm:py-6 lg:py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow-md rounded-lg p-4 sm:p-5 lg:p-6">
                    <!-- Flash Message -->
                    <div
                        v-if="flash.message"
                        :class="[
                            'p-3 sm:p-4 mb-4 sm:mb-5 text-sm rounded-lg flex justify-between items-center',
                            flash.type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800',
                        ]"
                        role="alert"
                    >
                        <span>{{ flash.message }}</span>
                        <button @click="flash.message = null" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-4 sm:mb-6">
                            <div>
                                <InputLabel for="student_id" value="Student" class="form-label text-sm font-medium text-gray-700" />
                                <select id="student_id" class="form-select w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" v-model="form.student_id" required>
                                    <option value="" disabled>-- Select Student --</option>
                                    <option v-for="student in students" :key="student.id" :value="student.id">{{ student.name }} ({{ student.admission_number }})</option>
                                </select>
                                <InputError class="mt-1 text-xs text-red-600" :message="form.errors.student_id" />
                            </div>
                            <div>
                                <InputLabel for="fee_type_id" value="Fee Type" class="form-label text-sm font-medium text-gray-700" />
                                <select id="fee_type_id" class="form-select w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" v-model="form.fee_type_id" required>
                                    <option value="" disabled>-- Select Fee Type --</option>
                                    <option v-for="feeType in feeTypes" :key="feeType.id" :value="feeType.id">
                                        {{ feeType.name }} ({{ feeType.frequency }})
                                    </option>
                                </select>
                                <InputError class="mt-1 text-xs text-red-600" :message="form.errors.fee_type_id" />
                            </div>
                            <div>
                                <InputLabel for="status" value="Status" class="form-label text-sm font-medium text-gray-700" />
                                <select id="status" class="form-select w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500" v-model.number="form.status" required>
                                    <option value="0">Active</option>
                                    <option value="1">Inactive</option>
                                </select>
                                <InputError class="mt-1 text-xs text-red-600" :message="form.errors.status" />
                            </div>
                        </div>
                        <div class="flex justify-end mt-4 sm:mt-6 gap-2 sm:gap-3">
                            <Link :href="route('student-fee-assignments.index')" class="px-3 py-1.5 sm:px-4 sm:py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors text-sm font-medium">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="px-3 py-1.5 sm:px-4 sm:py-2 text-sm font-medium">
                                Update Assignment
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
:root {
    --primary-color: #4F46E5; /* Indigo-600 */
    --secondary-color: #10B981; /* Green-600 */
    --text-color: #1F2937; /* Gray-800 */
    --light-bg: #F9FAFB; /* Gray-50 */
}

.form-select {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236B7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.5rem center;
    background-size: 1.5em;
}

@media (max-width: 768px) {
    .grid-cols-2 {
        grid-template-columns: 1fr;
    }

    .text-xl {
        font-size: 1.125rem;
    }

    .text-lg {
        font-size: 1rem;
    }

    .text-base {
        font-size: 0.875rem;
    }

    .px-4 {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }

    .py-2 {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
}

@media (max-width: 640px) {
    .text-sm {
        font-size: 0.75rem;
    }

    .px-3 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .py-1\.5 {
        padding-top: 0.375rem;
        padding-bottom: 0.375rem;
    }
}

@media (max-width: 480px) {
    .text-base {
        font-size: 0.75rem;
    }

    .text-sm {
        font-size: 0.7rem;
    }
    
    .px-2\.5 {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }

    .py-1\.5 {
        padding-top: 0.25rem;
        padding-bottom: 0.25rem;
    }
}
</style>
```