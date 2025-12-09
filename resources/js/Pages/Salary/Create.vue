<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';
import { defineProps, watch, ref, computed } from 'vue';
import Swal from 'sweetalert2'; 

// Define the props passed from the SalaryController@create
const props = defineProps(['staffList']);

// Define the form structure for submission
const form = useForm({
    // Polymorphic and Reference Fields
    salariable_id: null,
    salariable_type: null,
    designation_name: null,
    effective_date: new Date().toISOString().split('T')[0], // Default to today

    // Salary Components (all integers/round amounts)
    basic_salary: 0,
    house_rent_allowance: 0,
    medical_allowance: 0,
    academic_allowance: 0,
    transport_allowance: 0,
    festival_bonus: 0,
});

// Local state to store the currently selected staff member object
const selectedStaff = ref(null);

// Watch for changes in the selectedStaff object and update the form data
watch(selectedStaff, (newStaff) => {
    if (newStaff) {
        form.salariable_id = newStaff.id;
        form.salariable_type = newStaff.salariable_type;
        form.designation_name = newStaff.role;
        // Optionally, load existing salary data here if editing
    } else {
        form.reset('salariable_id', 'salariable_type', 'designation_name');
    }
});

// Calculate Total Gross Salary (computed property)
const grossSalary = computed(() => {
    return (
        (form.basic_salary || 0) +
        (form.house_rent_allowance || 0) +
        (form.medical_allowance || 0) +
        (form.academic_allowance || 0) +
        (form.transport_allowance || 0) +
        (form.festival_bonus || 0)
    );
});

// Helper function for currency formatting in Bangladeshi Taka (TK)
const formatCurrency = (amount) => {
    if (amount !== null && !isNaN(amount)) {
        return amount.toLocaleString('en-BD', { style: 'currency', currency: 'BDT', minimumFractionDigits: 0 });
    }
    return '৳0';
};

// Submission handler
const submit = () => {
    form.post(route('salaries.store'), {
        preserveScroll: true,
        onSuccess: () => {
            // Use Swal for success message after submission
            Swal.fire({
                icon: 'success',
                title: 'Salary Assigned!',
                text: `${selectedStaff.value.name}'s salary structure has been successfully saved.`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
            });
            form.reset();
            selectedStaff.value = null;
        },
        onError: () => {
            // Show a generic error message if submission fails
            Swal.fire({
                icon: 'error',
                title: 'Submission Failed',
                text: 'There was an issue saving the salary. Please check the form data.',
                position: 'center',
                showConfirmButton: true,
            });
        }
    });
};
</script>

<template>
    <!-- Include Font Awesome for icons and Inter for better typography -->
    <Head title="Assign Salary Structure" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">
    
    <AuthenticatedLayout>
        <div class="max-w-full mx-auto p-6 lg:p-8 bg-white shadow-2xl rounded-2xl border border-gray-200 font-['Inter']">
            <h1 class="text-3xl font-extrabold tracking-tight text-gray-900 mb-6 border-b pb-3">
                <i class="fas fa-file-invoice-dollar text-indigo-600 mr-3"></i> Assign New Salary Structure
            </h1>

            <form @submit.prevent="submit">
                <!-- Staff Selection and Reference -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="relative">
                        <label for="staff" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-users-viewfinder mr-2 text-indigo-500"></i>Select Staff Member
                        </label>
                        <select 
                            id="staff" 
                            v-model="selectedStaff" 
                            :class="{ 'border-red-500 ring-red-500': form.errors.salariable_id }"
                            class="mt-1 block w-full pl-4 pr-10 py-2.5 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-xl shadow-md transition duration-150 appearance-none bg-white"
                        >
                            <option :value="null" disabled>-- Select a Staff Member --</option>
                            <option v-for="staff in props.staffList" :key="staff.id" :value="staff">
                                {{ staff.name }} ({{ staff.role || 'N/A Role' }})
                            </option>
                        </select>
                        <i class="fas fa-caret-down absolute right-3 top-[42px] text-gray-400 pointer-events-none"></i>
                        <p v-if="form.errors.salariable_id" class="mt-2 text-sm text-red-600 font-medium">{{ form.errors.salariable_id }}</p>
                    </div>

                    <!-- Designation and Effective Date -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-id-badge mr-2 text-gray-500"></i>Designation (Role)
                            </label>
                            <input 
                                type="text" 
                                :value="form.designation_name || 'N/A'" 
                                disabled 
                                class="mt-1 block w-full bg-gray-100 rounded-xl border-gray-300 shadow-inner px-4 py-2.5 text-base text-gray-700 cursor-not-allowed" 
                            />
                        </div>
                        <div>
                            <label for="effective_date" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="fas fa-calendar-check mr-2 text-indigo-500"></i>Effective Date
                            </label>
                            <input 
                                type="date" 
                                id="effective_date" 
                                v-model="form.effective_date" 
                                :class="{ 'border-red-500 ring-red-500': form.errors.effective_date }"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-md px-4 py-2.5 focus:ring-indigo-500 focus:border-indigo-500 transition duration-150" 
                            />
                            <p v-if="form.errors.effective_date" class="mt-2 text-sm text-red-600 font-medium">{{ form.errors.effective_date }}</p>
                        </div>
                    </div>
                </div>

                <h2 class="text-xl font-bold mb-4 border-b-2 border-indigo-100 pb-2 text-indigo-700 flex items-center">
                    <i class="fas fa-money-bill-transfer mr-2"></i> Salary Components (Enter Round Amounts)
                </h2>

                <!-- Salary Components Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Basic Salary -->
                    <div class="component-card">
                        <label for="basic_salary" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-coins mr-2 text-yellow-600"></i> Basic Salary <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="number" 
                            id="basic_salary" 
                            v-model.number="form.basic_salary" 
                            min="0" 
                            step="1"
                            placeholder="e.g., 50000"
                            :class="{ 'border-red-500': form.errors.basic_salary }"
                            class="input-field" 
                        />
                        <p v-if="form.errors.basic_salary" class="mt-1 text-xs text-red-600">{{ form.errors.basic_salary }}</p>
                    </div>

                    <!-- HRA -->
                    <div class="component-card">
                        <label for="house_rent_allowance" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-house-chimney mr-2 text-blue-500"></i> HRA (House Rent)
                        </label>
                        <input 
                            type="number" 
                            id="house_rent_allowance" 
                            v-model.number="form.house_rent_allowance" 
                            min="0" 
                            step="1" 
                            class="input-field" 
                        />
                    </div>

                    <!-- Medical Allowance -->
                    <div class="component-card">
                        <label for="medical_allowance" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-briefcase-medical mr-2 text-red-500"></i> Medical Allowance
                        </label>
                        <input 
                            type="number" 
                            id="medical_allowance" 
                            v-model.number="form.medical_allowance" 
                            min="0" 
                            step="1" 
                            class="input-field" 
                        />
                    </div>

                    <!-- Academic Allowance -->
                    <div class="component-card">
                        <label for="academic_allowance" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-user-graduate mr-2 text-purple-500"></i> Academic Allowance
                        </label>
                        <input 
                            type="number" 
                            id="academic_allowance" 
                            v-model.number="form.academic_allowance" 
                            min="0" 
                            step="1" 
                            class="input-field" 
                        />
                    </div>

                    <!-- Transport Allowance -->
                    <div class="component-card">
                        <label for="transport_allowance" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-car mr-2 text-orange-500"></i> Transport Allowance
                        </label>
                        <input 
                            type="number" 
                            id="transport_allowance" 
                            v-model.number="form.transport_allowance" 
                            min="0" 
                            step="1" 
                            class="input-field" 
                        />
                    </div>

                    <!-- Festival Bonus -->
                    <div class="component-card">
                        <label for="festival_bonus" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-gifts mr-2 text-pink-500"></i> Festival Bonus
                        </label>
                        <input 
                            type="number" 
                            id="festival_bonus" 
                            v-model.number="form.festival_bonus" 
                            min="0" 
                            step="1" 
                            :class="{ 'border-red-500': form.errors.festival_bonus }"
                            class="input-field" 
                        />
                        <p v-if="form.errors.festival_bonus" class="mt-1 text-xs text-red-600">{{ form.errors.festival_bonus }}</p>
                    </div>
                </div>

                <!-- Total Gross Salary Display -->
                <div class="mt-8 p-5 bg-indigo-50 border border-indigo-200 rounded-xl shadow-xl">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-bold text-indigo-800 flex items-center">
                            <i class="fas fa-calculator mr-3 text-indigo-600"></i> Estimated Gross Monthly Pay:
                        </h3>
                        <span class="text-3xl font-extrabold text-green-700">
                            {{ formatCurrency(grossSalary) }}
                        </span>
                    </div>
                    <p class="text-sm text-indigo-500 mt-2">
                        This is the sum of all components and does not include any deductions or taxes.
                    </p>
                </div>

                <!-- Submission Button -->
                <div class="mt-8 flex justify-end space-x-4">
                    <button
                        type="button"
                        @click="form.reset()"
                        class="px-6 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none transition duration-150"
                    >
                        Reset Form
                    </button>
                    <button 
                        type="submit" 
                        :disabled="form.processing || !form.salariable_id || form.basic_salary <= 0" 
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing || !form.salariable_id || form.basic_salary <= 0 }"
                        class="px-6 py-2 inline-flex justify-center border border-transparent shadow-lg text-sm font-bold rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 transform hover:scale-[1.01]"
                    >
                        <i class="fas fa-check-circle mr-2" v-if="!form.processing"></i>
                        {{ form.processing ? 'Saving...' : 'Confirm & Save Salary' }}
                    </button>
                </div>
            </form>
        </div>
        
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-4 mt-6 text-center text-sm text-gray-500">
            Ensure all salary amounts are correct before submission.
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Custom styles for the input fields to improve aesthetics */
.input-field {
    @apply mt-1 block w-full rounded-xl border-gray-300 shadow-md px-4 py-2.5 text-base focus:ring-indigo-500 focus:border-indigo-500 transition duration-150;
}

.component-card {
    @apply p-4 bg-white rounded-lg border border-gray-100 shadow-sm transition duration-200 hover:shadow-lg hover:border-indigo-300;
}
</style>