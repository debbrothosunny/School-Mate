<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, watchEffect } from 'vue';
import axios from 'axios';

const props = defineProps({
    classes: Array,
    sessions: Array,
    groups: Array,
    sections: Array,
});

const academicContextForm = ref({
    class_id: '',
    session_id: '',
    section_id: '',
    group_id: '',
});

const availableStudents = ref([]);
const availableFeeTypesWithAmounts = ref([]);
const isFetchingData = ref(false);
const fetchError = ref(null);

const invoiceForm = useForm({
    selected_students: [], // Changed from student_id to an array for multiple students
    due_date: '',
    selected_fee_types: [],
    billing_period: '',
});

const totalInvoiceAmount = computed(() => {
    return invoiceForm.selected_fee_types.reduce((sum, feeTypeId) => {
        const fee = availableFeeTypesWithAmounts.value.find(f => f.id === feeTypeId);
        return sum + (fee ? Number(fee.amount) : 0);
    }, 0);
});

// Computed property to check if all academic context fields are selected
const allAcademicContextSelected = computed(() => {
    return academicContextForm.value.class_id &&
           academicContextForm.value.session_id &&
           academicContextForm.value.section_id &&
           academicContextForm.value.group_id;
});

// Watch for changes in academic context to fetch students and fees
watch(
    () => [
        academicContextForm.value.class_id,
        academicContextForm.value.session_id,
        academicContextForm.value.section_id,
        academicContextForm.value.group_id
    ],
    async ([class_id, session_id, section_id, group_id]) => {
        // Only proceed if all academic context fields are selected
        if (!allAcademicContextSelected.value) {
            availableStudents.value = [];
            availableFeeTypesWithAmounts.value = [];
            invoiceForm.selected_students = []; // Clear students array
            invoiceForm.due_date = '';
            invoiceForm.selected_fee_types = [];
            invoiceForm.billing_period = '';
            fetchError.value = null;
            return;
        }

        isFetchingData.value = true;
        fetchError.value = null;
        availableStudents.value = [];
        availableFeeTypesWithAmounts.value = [];
        invoiceForm.selected_students = [];
        invoiceForm.due_date = '';
        invoiceForm.selected_fee_types = [];
        invoiceForm.billing_period = '';

        try {
            const response = await axios.get(route('admin.invoices.get-academic-data'), {
                params: {
                    class_id,
                    session_id,
                    section_id,
                    group_id,
                }
            });

            availableStudents.value = response.data.students;
            availableFeeTypesWithAmounts.value = response.data.fee_structures.map(fs => ({
                id: fs.fee_type_id,
                name: fs.fee_type ? fs.fee_type.name : 'Unknown Fee Type',
                amount: fs.amount,
                display_amount: `BDT ${Number(fs.amount).toFixed(2)}`
            }));
        } catch (err) {
            console.error("Error fetching academic data:", err);
            fetchError.value = "Failed to load students or fees. Please try again.";
            if (err.response && err.response.data && err.response.data.message) {
                fetchError.value = err.response.data.message;
            }
        } finally {
            isFetchingData.value = false;
        }
    },
    { immediate: false }
);

// --- Toast Notification Logic ---
const page = usePage();
const flash = computed(() => page.props.flash || {});

watchEffect(() => {
    if (flash.value && flash.value.message) {
        // Ensure Swal (SweetAlert2) is available globally
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
            console.warn('SweetAlert2 (Swal) is not defined. Toast notifications will not work.');
        }
    }
});
// --- End Toast Notification Logic ---

// Toggle student selection
const toggleStudentSelection = (studentId) => {
    const index = invoiceForm.selected_students.indexOf(studentId);
    if (index === -1) {
        invoiceForm.selected_students.push(studentId);
    } else {
        invoiceForm.selected_students.splice(index, 1);
    }
};

const submitInvoice = () => {
    invoiceForm.post(route('admin.invoices.store'), {
        onSuccess: () => {
            // Reset the form after successful submission
            invoiceForm.reset('selected_students', 'due_date', 'selected_fee_types', 'billing_period');
            // Reset academic context form to clear selections and re-trigger data fetch
            academicContextForm.value = {
                class_id: '',
                session_id: '',
                section_id: '',
                group_id: '',
            };
            availableStudents.value = [];
            availableFeeTypesWithAmounts.value = [];
        },
        onError: (errors) => {
            console.error("Invoice creation failed:", errors);
        },
    });
};
</script>

<template>
    <Head title="Create New Invoice" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Invoice</h2>
        </template>

        <div class="py-6 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow-sm rounded-md">
                <form @submit.prevent="submitInvoice">
                    <!-- Academic Context -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                        <div>
                            <InputLabel for="class_id" value="Class" />
                            <select v-model="academicContextForm.class_id" id="class_id" class="form-select mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                <option value="" disabled>-- Select Class --</option>
                                <option v-for="cls in props.classes" :key="cls.id" :value="cls.id">
                                    {{ cls.class_name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="session_id" value="Session" />
                            <select v-model="academicContextForm.session_id" id="session_id" class="form-select mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                <option value="" disabled>-- Select Session --</option>
                                <option v-for="session in props.sessions" :key="session.id" :value="session.id">
                                    {{ session.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="section_id" value="Section" />
                            <select v-model="academicContextForm.section_id" id="section_id" class="form-select mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                <option value="" disabled>-- Select Section --</option>
                                <option v-for="section in props.sections" :key="section.id" :value="section.id">
                                    {{ section.name }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <InputLabel for="group_id" value="Group" />
                            <select v-model="academicContextForm.group_id" id="group_id" class="form-select mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                <option value="" disabled>-- Select Group --</option>
                                <option v-for="group in props.groups" :key="group.id" :value="group.id">
                                    {{ group.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Conditional rendering for dynamic data sections -->
                    <div v-if="!allAcademicContextSelected" class="text-center py-6 text-gray-600 italic">
                        Please select a **Class, Session, Section, and Group** to load students and fees.
                    </div>
                    <div v-else-if="isFetchingData" class="text-center py-6 text-indigo-500">
                        Loading students and fees...
                    </div>
                    <div v-else-if="fetchError" class="text-center py-6 text-red-600">
                        Error: {{ fetchError }}
                    </div>
                    <template v-else>
                        <!-- Student Selection -->
                        <div class="mb-6">
                            <InputLabel value="Select Students" />
                            <InputError :message="invoiceForm.errors.selected_students" class="mt-1" />
                            <div v-if="availableStudents.length === 0" class="text-red-500 mt-2">No students found matching the selected criteria.</div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-2">
                                <div
                                    v-for="student in availableStudents"
                                    :key="student.id"
                                    class="p-3 border rounded-md cursor-pointer transition-colors duration-200"
                                    :class="{
                                        'bg-indigo-600 text-white shadow-md': invoiceForm.selected_students.includes(student.id),
                                        'hover:bg-gray-100': !invoiceForm.selected_students.includes(student.id)
                                    }"
                                    @click="toggleStudentSelection(student.id)"
                                >
                                    <div class="font-semibold">{{ student.name }}</div>
                                    <div class="text-sm text-gray-400" :class="{ 'text-indigo-200': invoiceForm.selected_students.includes(student.id) }">Admission: {{ student.admission_number }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Due Date and Billing Period -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <InputLabel for="due_date" value="Due Date" />
                                <TextInput type="date" id="due_date" class="mt-1 block w-full" v-model="invoiceForm.due_date" required />
                                <InputError :message="invoiceForm.errors.due_date" class="mt-1" />
                            </div>
                            <div>
                                <InputLabel for="billing_period" value="Billing Period (e.g., Aug 2025, 2025-2026 Annual)" />
                                <TextInput type="text" id="billing_period" class="mt-1 block w-full" v-model="invoiceForm.billing_period" />
                                <InputError :message="invoiceForm.errors.billing_period" class="mt-1" />
                            </div>
                        </div>

                        <!-- Fee Selection -->
                        <div class="mb-6">
                            <InputLabel value="Select Fees" />
                            <div v-if="availableFeeTypesWithAmounts.length === 0" class="text-red-500 mt-2">No fee structures found for the selected criteria.</div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-3">
                                <div
                                    v-for="fee in availableFeeTypesWithAmounts"
                                    :key="fee.id"
                                    class="flex items-center bg-gray-100 p-3 rounded-md transition-colors duration-200"
                                    :class="{ 'bg-indigo-100 border-indigo-500': invoiceForm.selected_fee_types.includes(fee.id) }"
                                >
                                    <input
                                        type="checkbox"
                                        :id="`fee_${fee.id}`"
                                        :value="fee.id"
                                        v-model="invoiceForm.selected_fee_types"
                                        class="form-checkbox h-5 w-5 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500"
                                    />
                                    <label :for="`fee_${fee.id}`" class="ml-2 text-sm text-gray-700">
                                        {{ fee.name }} — <span class="font-semibold">{{ fee.display_amount }}</span>
                                    </label>
                                </div>
                            </div>
                            <InputError :message="invoiceForm.errors.selected_fee_types" class="mt-2" />
                            <InputError :message="invoiceForm.errors['selected_fee_types.*']" class="mt-2" />
                        </div>
                    </template>

                    <!-- Footer Buttons -->
                    <div class="flex justify-between items-center border-t pt-4">
                        <div>
                            <span class="font-bold text-gray-700">Total:</span>
                            <span class="text-xl text-indigo-600 font-bold ml-2">BDT {{ totalInvoiceAmount.toFixed(2) }}</span>
                        </div>
                        <div>
                            <Link :href="route('admin.invoices.index')" class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-400 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 me-3">Cancel</Link>
                            <PrimaryButton
                                :disabled="invoiceForm.processing || invoiceForm.selected_students.length === 0 || invoiceForm.selected_fee_types.length === 0"
                                :class="{ 'opacity-50': invoiceForm.processing }"
                            >
                                Create Invoice
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
