<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },
});

// Helper function to format currency as BDT
const formatCurrency = (amount) => {
    return `BDT ${Number(amount).toFixed(2)}`;
};

// Helper function to format date
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

// Function to get status styling
const getStatusClasses = (status) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 border-yellow-400';
        case 'partially_paid':
            return 'bg-blue-100 text-blue-800 border-blue-400';
        case 'paid':
            return 'bg-emerald-100 text-emerald-800 border-emerald-400';
        case 'overdue':
            return 'bg-red-100 text-red-800 border-red-400';
        default:
            return 'bg-gray-100 text-gray-700 border-gray-400';
    }
};

const student = props.invoice.student;
</script>

<template>
    <Head :title="`Invoice #${invoice.invoice_number} Details`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    <span class="text-indigo-600">Invoice</span> #{{ invoice.invoice_number }}
                </h2>
                <Link :href="route('admin.invoices.index')" class="text-indigo-600 hover:text-indigo-800 font-semibold flex items-center transition">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Registry
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-lg rounded-xl overflow-hidden">
                    <div class="p-6 md:p-8 space-y-6">
                        
                        <div class="flex justify-between items-start pb-4 border-b border-gray-100">
                            <div>
                                <p class="text-sm text-gray-500 font-medium">Invoice Date: {{ formatDate(invoice.created_at) }}</p>
                                <p class="text-sm text-gray-500 font-medium">Due Date: <span class="font-bold text-red-500">{{ formatDate(invoice.due_date) }}</span></p>
                            </div>
                            <span
                                :class="[
                                    'px-3 py-1 text-sm leading-5 font-bold rounded-full capitalize shadow-sm border',
                                    getStatusClasses(invoice.status)
                                ]"
                            >
                                {{ invoice.status.replace('_', ' ') }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pb-4 border-b border-gray-100">
                            <div class="col-span-2 md:col-span-1">
                                <h4 class="text-sm font-semibold text-gray-500">Billed To</h4>
                                <p class="text-lg font-bold text-gray-900">{{ student?.name || 'N/A' }}</p>
                            </div>
                            <div>
                                <h4 class="text-sm font-semibold text-gray-500">Roll No</h4>
                                <p class="text-md font-medium text-gray-800">{{ student?.roll_number || 'N/A' }}</p>
                            </div>
                           
                            
                        </div>

                        <div>
                            <h4 class="text-xl font-bold text-gray-700 mb-3">Fee Breakdown</h4>
                            <div class="overflow-x-auto shadow-md rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fee Type Name</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        <tr v-for="(item, index) in invoice.invoice_items" :key="index">
                                            <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">
                                                {{ item.fee_type?.name || 'Fee Type N/A' }}
                                            </td>
                                            <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-semibold text-gray-800">
                                                {{ formatCurrency(item.amount) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="bg-indigo-50 border-t border-indigo-200">
                                        <tr>
                                            <td class="px-6 py-3 text-left text-base font-extrabold text-gray-900">Total Invoice Amount</td>
                                            <td class="px-6 py-3 text-right text-lg font-extrabold text-indigo-700">
                                                {{ formatCurrency(invoice.total_amount_due) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="mt-8">
                            <h4 class="text-xl font-bold text-gray-700 mb-3">Payment History</h4>
                            <template v-if="invoice.payments && invoice.payments.length">
                                <div class="overflow-x-auto shadow-md rounded-lg border border-gray-200">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Paid</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-100">
                                            <tr v-for="payment in invoice.payments" :key="payment.id">
                                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700">{{ formatDate(payment.payment_date) }}</td>
                                                <td class="px-6 py-3 whitespace-nowrap text-right text-sm font-semibold text-emerald-600">
                                                    {{ formatCurrency(payment.amount) }}
                                                </td>
                                                <td class="px-6 py-3 whitespace-nowrap text-sm text-gray-700 capitalize">{{ payment.payment_method || 'Cash' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                            <div v-else class="p-3 bg-yellow-50 rounded-lg text-yellow-700 border border-yellow-200 text-sm">
                                🔔 No payments recorded yet for this invoice.
                            </div>
                        </div>

                        <div class="flex justify-end pt-6 border-t border-gray-200">
                            <div class="w-full max-w-sm space-y-3">
                                <div class="flex justify-between text-base font-semibold text-gray-700">
                                    <span>Total Paid:</span>
                                    <span class="text-emerald-600">{{ formatCurrency(invoice.amount_paid) }}</span>
                                </div>
                                <div class="flex justify-between text-xl font-extrabold p-2 rounded-lg" 
                                     :class="{'bg-red-50 text-red-700': invoice.balance_due > 0, 'bg-emerald-50 text-gray-900': invoice.balance_due <= 0 }">
                                    <span>Balance Due:</span>
                                    <span>{{ formatCurrency(invoice.balance_due) }}</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>