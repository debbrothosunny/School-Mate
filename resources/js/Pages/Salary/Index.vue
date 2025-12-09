<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import { defineProps, computed, watchEffect, ref } from 'vue';
import Swal from 'sweetalert2';

// Define the props passed from the SalaryController@index
const props = defineProps(['staffList']);

// Debug: Log staffList to verify admin users
console.log('Staff List:', props.staffList);

// --- Search State ---
const searchQuery = ref('');

// --- Helper Functions ---
// Helper function for currency formatting. Uses Taka symbol (৳) but formats the number
// using the English/US locale for Western numerals and comma separation.
const formatCurrency = (amount) => {
    if (amount !== null && !isNaN(amount)) {
        const roundedAmount = Math.round(amount);
        const formattedNumber = roundedAmount.toLocaleString('en-US', { minimumFractionDigits: 0 });
        return `৳${formattedNumber}`;
    }
    return '৳0';
};

// Helper function to format date
const formatDate = (date) => {
    return date
        ? new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
        : 'Not Assigned';
};

// --- Computed Properties for Filtering ---
const filteredStaffList = computed(() => {
    if (!searchQuery.value) {
        return props.staffList;
    }
    const searchLower = searchQuery.value.toLowerCase();
    return props.staffList.filter(staff => {
        const nameMatch = (staff.name || '').toLowerCase().includes(searchLower);
        const designationMatch = staff.current_salary?.designation_name &&
                                staff.current_salary.designation_name.toLowerCase().includes(searchLower);
        const roleMatch = (staff.role || '').toLowerCase().includes(searchLower);
        return nameMatch || designationMatch || roleMatch;
    });
});

// --- Flash Message Handling ---
const flash = computed(() => {
    const p = usePage().props;
    return {
        message: p.flash?.success || p.flash?.error || '',
        type: p.flash?.success ? 'success' : 'error',
    };
});

watchEffect(() => {
    if (flash.value.message) {
        Swal.fire({
            icon: flash.value.type,
            title: flash.value.type === 'success' ? 'Success!' : 'Error!',
            text: flash.value.message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
        });
    }
});

// --- Salary Details Modal ---
const viewDetails = (staff) => {
    const salary = staff.current_salary;
    if (!salary) {
        Swal.fire({
            icon: 'info',
            title: 'No Salary Assigned',
            text: `${staff.name} does not have a current salary structure.`,
            position: 'center',
            showConfirmButton: true,
            confirmButtonColor: '#4F46E5',
        });
        return;
    }
    const totalAmount = (
        (salary.basic_salary ?? 0) +
        (salary.house_rent_allowance ?? 0) +
        (salary.medical_allowance ?? 0) +
        (salary.academic_allowance ?? 0) +
        (salary.transport_allowance ?? 0) +
        (salary.festival_bonus ?? 0)
    );
    const netPay = staff.net_pay ?? totalAmount;
    const allowanceDetails = `
        <div class="text-left p-2">
            <h4 class="text-xl font-bold mb-4 text-indigo-700 border-b-2 border-indigo-200 pb-2">Salary Breakdown</h4>
            <ul class="space-y-3 text-gray-800">
                <li class="flex justify-between items-center bg-gray-50 p-3 rounded-lg shadow-sm">
                    <span class="font-semibold text-lg flex items-center"><i class="fas fa-coins text-yellow-500 mr-3"></i> Basic Salary:</span>
                    <span class="text-lg font-bold text-green-700">${formatCurrency(salary.basic_salary)}</span>
                </li>
                <li class="flex justify-between border-b border-gray-100 pb-2 pt-1">
                    <span class="font-medium flex items-center"><i class="fas fa-house-chimney text-blue-400 mr-3"></i> HRA (House Rent):</span>
                    <span>${formatCurrency(salary.house_rent_allowance)}</span>
                </li>
                <li class="flex justify-between border-b border-gray-100 pb-2 pt-1">
                    <span class="font-medium flex items-center"><i class="fas fa-briefcase-medical text-red-400 mr-3"></i> Medical Allowance:</span>
                    <span>${formatCurrency(salary.medical_allowance)}</span>
                </li>
                <li class="flex justify-between border-b border-gray-100 pb-2 pt-1">
                    <span class="font-medium flex items-center"><i class="fas fa-user-graduate text-purple-400 mr-3"></i> Academic Allowance:</span>
                    <span>${formatCurrency(salary.academic_allowance)}</span>
                </li>
                <li class="flex justify-between border-b border-gray-100 pb-2 pt-1">
                    <span class="font-medium flex items-center"><i class="fas fa-car text-orange-400 mr-3"></i> Transport Allowance:</span>
                    <span>${formatCurrency(salary.transport_allowance)}</span>
                </li>
                <li class="flex justify-between border-b border-gray-100 pb-2 pt-1">
                    <span class="font-medium flex items-center"><i class="fas fa-gifts text-pink-400 mr-3"></i> Festival Bonus:</span>
                    <span>${formatCurrency(salary.festival_bonus)}</span>
                </li>
            </ul>
            <div class="mt-6 pt-4 border-t-2 border-indigo-500 bg-indigo-50 p-4 rounded-xl shadow-inner">
                <div class="flex justify-between text-2xl font-extrabold text-indigo-800">
                    <span><i class="fas fa-money-bill-transfer mr-3"></i> Gross Pay (Total):</span>
                    <span>${formatCurrency(totalAmount)}</span>
                </div>
                <div class="flex justify-between text-xl font-bold text-indigo-700 mt-2">
                    <span><i class="fas fa-money-check mr-3"></i> Net Pay:</span>
                    <span>${formatCurrency(netPay)}</span>
                </div>
            </div>
        </div>
        <p class="mt-4 text-sm text-center text-gray-500">
            <span class="font-semibold">Effective Date:</span>
            <span class="text-indigo-600 font-bold">${formatDate(salary.effective_date)}</span>
        </p>
    `;
    Swal.fire({
        title: `
            <div class="text-2xl font-extrabold text-indigo-600">${staff.name}</div>
            <div class="text-base font-normal text-gray-500 mt-1">${salary.designation_name || staff.role || 'N/A'}</div>
        `,
        html: allowanceDetails,
        icon: 'info',
        showConfirmButton: true,
        confirmButtonColor: '#4F46E5',
        customClass: {
            title: 'pb-0',
            content: 'pt-0'
        },
        width: '90%',
        maxWidth: '550px',
    });
};

// --- Edit Salary Modal ---
const editSalary = (staff) => {
    const salary = staff.current_salary;
    if (!salary) {
        Swal.fire({
            icon: 'info',
            title: 'No Salary Assigned',
            text: `${staff.name} does not have a current salary structure to edit.`,
            position: 'center',
            showConfirmButton: true,
            confirmButtonColor: '#4F46E5',
        });
        return;
    }

    // Initialize form with existing salary data
    const form = useForm({
        salariable_id: salary.salariable_id,
        salariable_type: salary.salariable_type,
        designation_name: salary.designation_name,
        effective_date: salary.effective_date,
        basic_salary: salary.basic_salary,
        house_rent_allowance: salary.house_rent_allowance || 0,
        medical_allowance: salary.medical_allowance || 0,
        academic_allowance: salary.academic_allowance || 0,
        transport_allowance: salary.transport_allowance || 0,
        festival_bonus: salary.festival_bonus || 0,
    });

    // Create form HTML for SweetAlert2
    const formHtml = `
        <form id="editSalaryForm" class="text-left p-2">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Staff Member</label>
                <input
                    type="text"
                    value="${staff.name} (${staff.role.charAt(0).toUpperCase() + staff.role.slice(1)})"
                    class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-lg bg-gray-100"
                    disabled
                />
                <input type="hidden" name="salariable_id" value="${form.salariable_id}" />
                <input type="hidden" name="salariable_type" value="${form.salariable_type}" />
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Designation</label>
                <input
                    type="text"
                    id="designation_name"
                    value="${form.designation_name}"
                    class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    required
                />
                <p id="error_designation_name" class="text-red-600 text-sm mt-1 hidden"></p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Effective Date</label>
                <input
                    type="date"
                    id="effective_date"
                    value="${form.effective_date}"
                    class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    required
                />
                <p id="error_effective_date" class="text-red-600 text-sm mt-1 hidden"></p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Basic Salary (৳)</label>
                <input
                    type="number"
                    id="basic_salary"
                    value="${form.basic_salary}"
                    min="0"
                    class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                    required
                />
                <p id="error_basic_salary" class="text-red-600 text-sm mt-1 hidden"></p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">House Rent Allowance (৳)</label>
                <input
                    type="number"
                    id="house_rent_allowance"
                    value="${form.house_rent_allowance}"
                    min="0"
                    class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                />
                <p id="error_house_rent_allowance" class="text-red-600 text-sm mt-1 hidden"></p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Medical Allowance (৳)</label>
                <input
                    type="number"
                    id="medical_allowance"
                    value="${form.medical_allowance}"
                    min="0"
                    class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                />
                <p id="error_medical_allowance" class="text-red-600 text-sm mt-1 hidden"></p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Academic Allowance (৳)</label>
                <input
                    type="number"
                    id="academic_allowance"
                    value="${form.academic_allowance}"
                    min="0"
                    class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                />
                <p id="error_academic_allowance" class="text-red-600 text-sm mt-1 hidden"></p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Transport Allowance (৳)</label>
                <input
                    type="number"
                    id="transport_allowance"
                    value="${form.transport_allowance}"
                    min="0"
                    class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                />
                <p id="error_transport_allowance" class="text-red-600 text-sm mt-1 hidden"></p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Festival Bonus (৳)</label>
                <input
                    type="number"
                    id="festival_bonus"
                    value="${form.festival_bonus}"
                    min="0"
                    class="mt-1 block w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                />
                <p id="error_festival_bonus" class="text-red-600 text-sm mt-1 hidden"></p>
            </div>
        </form>
    `;

    Swal.fire({
        title: `
            <div class="text-2xl font-extrabold text-indigo-600">Edit Salary for ${staff.name}</div>
            <div class="text-base font-normal text-gray-500 mt-1">${salary.designation_name || staff.role || 'N/A'}</div>
        `,
        html: formHtml,
        icon: 'info',
        showCancelButton: true,
        showConfirmButton: true,
        confirmButtonText: '<i class="fas fa-save mr-2"></i> Update',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#4F46E5',
        cancelButtonColor: '#6B7280',
        width: '90%',
        maxWidth: '600px',
        preConfirm: () => {
            // Update form data with input values
            form.designation_name = document.getElementById('designation_name').value;
            form.effective_date = document.getElementById('effective_date').value;
            form.basic_salary = parseInt(document.getElementById('basic_salary').value) || 0;
            form.house_rent_allowance = parseInt(document.getElementById('house_rent_allowance').value) || 0;
            form.medical_allowance = parseInt(document.getElementById('medical_allowance').value) || 0;
            form.academic_allowance = parseInt(document.getElementById('academic_allowance').value) || 0;
            form.transport_allowance = parseInt(document.getElementById('transport_allowance').value) || 0;
            form.festival_bonus = parseInt(document.getElementById('festival_bonus').value) || 0;

            // Validate required fields
            if (!form.designation_name || !form.effective_date || !form.basic_salary) {
                Swal.showValidationMessage('Please fill in all required fields (Designation, Effective Date, Basic Salary).');
                return false;
            }

            // Submit form via Inertia
            return new Promise((resolve) => {
                form.put(route('salaries.update', salary.id), {
                    onSuccess: () => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Salary structure updated successfully.',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3500,
                            timerProgressBar: true,
                        });
                        resolve();
                    },
                    onError: (errors) => {
                        // Display validation errors
                        Object.keys(errors).forEach(key => {
                            const errorElement = document.getElementById(`error_${key}`);
                            if (errorElement) {
                                errorElement.textContent = errors[key];
                                errorElement.classList.remove('hidden');
                            }
                        });
                        Swal.showValidationMessage('Please correct the errors in the form.');
                        resolve(false);
                    },
                });
            });
        },
    });
};
</script>

<template>
    <Head title="Staff Salary Index" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

    <AuthenticatedLayout>
        <div class="font-['Inter']">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center pb-4 border-b border-gray-200">
                    <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 mb-4 md:mb-0">
                        <i class="fas fa-money-bill-transfer text-indigo-600 mr-3"></i> Staff Salary Overview
                    </h1>
                    <Link
                        :href="route('salaries.create')"
                        class="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-xl shadow-lg hover:bg-indigo-700 transition duration-300 ease-in-out flex items-center transform hover:scale-[1.02]"
                    >
                        <i class="fas fa-plus-circle mr-2"></i> Assign New Salary
                    </Link>
                </div>
            </div>
            <div class="max-w-7xl mx-auto p-6 lg:p-8">
                <div class="mb-8 flex flex-col sm:flex-row justify-between items-center bg-white p-5 rounded-2xl shadow-xl border border-gray-100">
                    <div class="relative w-full sm:max-w-lg mb-4 sm:mb-0">
                        <input
                            type="text"
                            v-model="searchQuery"
                            placeholder="Search staff by name, role, or designation..."
                            class="w-full py-2 pl-10 pr-4 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 transition duration-150 shadow-sm"
                        />
                        <i class="fas fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <div class="text-base font-medium text-gray-600">
                        Total Staff Members: <span class="text-indigo-600 font-bold text-xl">{{ props.staffList.length }}</span>
                    </div>
                </div>
                <div v-if="props.staffList.length === 0" class="bg-yellow-50 border border-yellow-300 text-yellow-800 p-6 mb-8 rounded-2xl shadow-lg flex items-center">
                    <i class="fas fa-triangle-exclamation text-3xl mr-4 flex-shrink-0"></i>
                    <p class="font-medium">
                        No staff members found. Please add users and assign appropriate roles (e.g., teacher, accountant, front-desk, admin) to manage their salary status here.
                    </p>
                </div>
                <div v-else-if="filteredStaffList.length === 0" class="bg-red-50 border border-red-red-300 text-red-800 p-6 mb-8 rounded-2xl shadow-lg flex items-center">
                    <i class="fas fa-circle-exclamation text-3xl mr-4 flex-shrink-0"></i>
                    <p class="font-medium">
                        No results found for <strong>"{{ searchQuery }}"</strong>. Please refine your search criteria.
                    </p>
                </div>
                <div v-else class="bg-white shadow-2xl rounded-2xl overflow-hidden border border-gray-200">
                    <div class="hidden md:grid grid-cols-7 gap-4 px-6 py-4 bg-indigo-50 font-extrabold text-sm text-indigo-800 uppercase tracking-wider border-b border-indigo-200">
                        <div class="col-span-2">Staff Name</div>
                        <div>Designation/Role</div>
                        <div>Basic Salary</div>
                        <div>Effective From</div>
                        <div class="text-center col-span-2">Actions</div>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="(staff, index) in filteredStaffList"
                            :key="staff.id + '-' + staff.salariable_type"
                            :class="[
                                'grid grid-cols-1 md:grid-cols-7 gap-4 p-4 md:px-6 md:py-4 text-sm transition duration-150',
                                { 'bg-gray-50 hover:bg-indigo-100/50': index % 2 !== 0, 'hover:bg-indigo-50': index % 2 === 0 }
                            ]"
                        >
                            <div class="col-span-2 font-bold text-gray-900 flex items-center">
                                <i class="fas fa-user-circle text-2xl text-indigo-400 mr-3 hidden md:inline"></i>
                                <span>{{ staff.name || 'Unknown' }}</span>
                            </div>
                            <div class="text-gray-600">
                                <span class="md:hidden font-semibold mr-2">Designation/Role:</span>
                                <span v-if="staff.current_salary && staff.current_salary.designation_name" class="inline-block px-3 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800 border border-indigo-300">
                                    {{ staff.current_salary.designation_name }}
                                </span>
                                <span v-else-if="staff.role" class="inline-block px-3 py-1 text-xs font-medium rounded-full bg-gray-200 text-gray-700">
                                    {{ staff.role.charAt(0).toUpperCase() + staff.role.slice(1) }}
                                </span>
                                <span v-else class="text-gray-500 text-xs italic">
                                    N/A
                                </span>
                            </div>
                            <div class="text-gray-900 font-medium">
                                <span class="md:hidden font-semibold mr-2">Basic Salary:</span>
                                <span v-if="staff.current_salary" class="text-base text-green-700 font-extrabold">
                                    {{ formatCurrency(staff.current_salary.basic_salary) }}
                                </span>
                                <span v-else class="text-gray-500 italic">
                                    N/A
                                </span>
                            </div>
                            <div class="text-gray-500 flex items-center">
                                <span class="md:hidden font-semibold mr-2">Effective From:</span>
                                <i class="fas fa-calendar-alt text-gray-400 mr-2 hidden md:inline"></i>
                                <span class="font-medium text-gray-700">
                                    {{ staff.current_salary ? formatDate(staff.current_salary.effective_date) : 'Not Assigned' }}
                                </span>
                            </div>
                            <div class="text-right flex justify-start md:justify-center items-center col-span-2 space-x-2">
                                <button
                                    @click.prevent="viewDetails(staff)"
                                    :disabled="!staff.current_salary"
                                    :class="{
                                        'px-4 py-2 text-xs font-bold rounded-xl transition duration-300 shadow-lg flex items-center justify-center min-w-[120px]': true,
                                        'bg-blue-600 text-white hover:bg-blue-700 ring-2 ring-blue-300': staff.current_salary,
                                        'bg-gray-300 text-gray-600 cursor-not-allowed shadow-none': !staff.current_salary
                                    }"
                                >
                                    <i :class="['mr-1', staff.current_salary ? 'fas fa-money-check-alt' : 'fas fa-circle-info']"></i>
                                    {{ staff.current_salary ? 'View Details' : 'Assign Salary' }}
                                </button>
                                <button
                                    v-if="staff.current_salary"
                                    @click.prevent="editSalary(staff)"
                                    class="px-4 py-2 text-xs font-bold rounded-xl transition duration-300 shadow-lg flex items-center justify-center min-w-[120px] bg-yellow-600 text-white hover:bg-yellow-700 ring-2 ring-yellow-300"
                                >
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 mt-6 border-t border-gray-200 text-center text-sm text-gray-500">
                Data provided is based on the current active salary structure for each staff member.
            </div>
        </div>
    </AuthenticatedLayout>
</template>