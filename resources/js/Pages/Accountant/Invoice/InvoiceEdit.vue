<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue'; // Assuming you have this component

const props = defineProps({
    invoice: Object,
    feeTypes: Array, // { id, name }
    classes: Array, // { id, class_name }
    sessions: Array, // { id, name }
    groups: Array, // { id, name }
    sections: Array, // { id, name }
});

// Helper function to format currency as BDT
const formatCurrency = (amount) => `BDT ${Number(amount).toFixed(2)}`;

// Map incoming line items to a mutable form structure
const form = useForm({
    issued_at: props.invoice.issued_at,
    due_date: props.invoice.due_date,
    notes: props.invoice.notes,
    
    // Line items must be structured for syncing/saving
    items: props.invoice.invoice_items.map(item => ({
        // Existing ID is required for updating/deleting existing items
        id: item.id, 
        fee_type_id: item.fee_type_id,
        description: item.description || item.fee_type.name, // Use name as fallback description
        amount: item.amount,
    })),
});

const updateInvoice = () => {
    // The PUT method is essential here to match the route definition
    form.put(route('admin.invoices.update', props.invoice.id), {
        onError: () => {
            alert('Please fix the highlighted errors before saving.');
        },
    });
};

const addItem = () => {
    form.items.push({
        id: null, // Indicates a new item for the backend
        fee_type_id: props.feeTypes.length ? props.feeTypes[0].id : null,
        description: '',
        amount: 0,
    });
};

const removeItem = (index) => {
    if (confirm('Are you sure you want to remove this line item?')) {
        form.items.splice(index, 1);
    }
};

const calculateTotal = () => {
    return form.items.reduce((sum, item) => sum + Number(item.amount), 0);
};

const student = props.invoice.student;
</script>

<template>
    <Head :title="`Edit Invoice #${invoice.invoice_number}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Editing Invoice: #{{ invoice.invoice_number }}
                </h2>
                <Link :href="route('admin.invoices.show', invoice.id)" class="text-indigo-600 hover:text-indigo-800 font-semibold transition">
                    Cancel and View
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="updateInvoice" class="bg-white p-8 shadow-2xl rounded-xl space-y-8">
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pb-4 border-b border-gray-200">
                        <div class="col-span-2 md:col-span-1">
                            <h4 class="text-sm font-semibold text-gray-500">Billed To</h4>
                            <p class="text-lg font-bold text-gray-900">{{ student?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500">Class</h4>
                            <p class="text-md font-medium text-gray-800">{{ student?.class_name?.class_name || 'N/A' }}</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500">Session</h4>
                            <p class="text-md font-medium text-gray-800">{{ student?.session?.name || 'N/A' }}</p>
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-gray-500">Paid Amount</h4>
                            <p class="text-md font-bold text-emerald-600">{{ formatCurrency(invoice.amount_paid) }}</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-6">
                        
                        
                        <div></div>
                    </div>

                    <div class="space-y-4 pt-4 border-t">
                        <h3 class="text-xl font-semibold text-gray-800 flex justify-between items-center">
                            Fee Items
                            <button type="button" @click="addItem" class="text-indigo-600 hover:text-indigo-800 flex items-center text-sm font-semibold">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Add Fee Item
                            </button>
                        </h3>
                        
                        <div v-for="(item, index) in form.items" :key="index" class="p-4 border rounded-lg bg-gray-50 grid grid-cols-12 gap-4 items-center shadow-inner">
                            
                            <div class="col-span-4">
                                <label :for="`fee_type_${index}`" class="block text-xs font-medium text-gray-500">Fee Type</label>
                                <select :id="`fee_type_${index}`" v-model="item.fee_type_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                    <option v-for="feeType in feeTypes" :key="feeType.id" :value="feeType.id">
                                        {{ feeType.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors[`items.${index}.fee_type_id`]" class="mt-2" />
                            </div>

                            <div class="col-span-4">
                                <label :for="`desc_${index}`" class="block text-xs font-medium text-gray-500">Description (Optional)</label>
                                <input type="text" :id="`desc_${index}`" v-model="item.description" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm" placeholder="e.g., Q1 Tuition">
                            </div>

                            <div class="col-span-3">
                                <label :for="`amount_${index}`" class="block text-xs font-medium text-gray-500">Amount (BDT)</label>
                                <input type="number" :id="`amount_${index}`" v-model="item.amount" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <InputError :message="form.errors[`items.${index}.amount`]" class="mt-2" />
                            </div>

                            <div class="col-span-1 flex justify-center">
                                <button type="button" @click="removeItem(index)" class="text-red-500 hover:text-red-700 p-2 transition rounded-full hover:bg-red-100">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="text-right pt-4 border-t-2 border-indigo-200">
                            <div class="text-xl font-extrabold text-gray-900">
                                New Total Due: {{ formatCurrency(calculateTotal()) }}
                            </div>
                            <div class="text-sm text-red-500 italic mt-1" v-if="calculateTotal() !== invoice.total_amount_due">
                                Recalculated total differs from previous total.
                            </div>
                        </div>
                    </div>

                   
                    <div class="flex justify-end pt-4">
                        <PrimaryButton type="submit" :disabled="form.processing">
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>Save Changes and Recalculate Totals</span>
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>