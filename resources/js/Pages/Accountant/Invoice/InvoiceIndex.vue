<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    invoices: {
        type: Object,
        required: true,
    },
    classNames: {
        type: Array,
        required: true,
    },
    filters: {
        type: Object,
        required: true,
    },
});

const flash = computed(() => usePage().props.flash || {});

// Format currency as BDT
const formatCurrency = (amount) => `BDT ${Number(amount).toFixed(2)}`;

// Format date for display
const formatDate = (dateString) => {
    if (!dateString) {
        return 'N/A';
    }
    const date = new Date(dateString);
    return date.toISOString().slice(0, 10);
};

// Filter state
const selectedClass = ref(props.filters.class_name || '');

// Handle filter change
const applyFilter = () => {
    router.get(
        route('admin.invoices.index'),
        { class_name: selectedClass.value },
        { preserveState: true, preserveScroll: true }
    );
};

// --- FEE DETAILS POPUP FUNCTIONALITY ---
const showFeeDetailsModal = ref(false);
const feeDetails = ref(null); // Holds the invoice object for the fee breakdown

const viewFeeDetails = (invoice) => {
    // Check if the invoice has items to display the breakdown
    if (invoice.invoice_items && invoice.invoice_items.length > 0) {
        feeDetails.value = invoice;
        showFeeDetailsModal.value = true;
    } else {
        alert(`Invoice #${invoice.invoice_number} has no detailed fee items.`);
    }
};

const closeFeeDetailsModal = () => {
    showFeeDetailsModal.value = false;
    feeDetails.value = null;
};
// --- END FEE DETAILS POPUP FUNCTIONALITY ---

// Function to get status styling
const getStatusClasses = (status) => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800 border-yellow-300';
        case 'partially_paid':
            return 'bg-blue-100 text-blue-800 border-blue-300';
        case 'paid':
            return 'bg-emerald-100 text-emerald-800 border-emerald-300';
        case 'overdue':
            return 'bg-red-100 text-red-800 border-red-300';
        case 'cancelled':
            return 'bg-gray-100 text-gray-700 border-gray-300';
        default:
            return 'bg-gray-100 text-gray-700 border-gray-300';
    }
};
</script>

<template>
    <Head title="Manage Invoices" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-3xl text-gray-800 leading-tight">
                Financial Center: Invoices
            </h2>
        </template>
        <div class="bg-gray-50 py-10">
            <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-2xl rounded-2xl">
                    <div class="p-6 sm:p-10 text-gray-900">
                        
                        <div class="flex justify-between items-center mb-8 border-b-4 border-indigo-100 pb-4">
                            <h3 class="text-3xl font-extrabold text-gray-900 flex items-center">
                                Invoice Registry 🧾
                            </h3>
                            <div class="flex items-center space-x-4">
                                <select
                                    v-model="selectedClass"
                                    @change="applyFilter"
                                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 shadow-sm"
                                >
                                    <option value="">All Classes</option>
                                    <option v-for="className in classNames" :key="className" :value="className">
                                        {{ className }}
                                    </option>
                                </select>
                                <Link
                                    :href="route('admin.invoices.create')"
                                    class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-base font-bold rounded-xl shadow-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 transition-all duration-300 ease-in-out"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    New Invoice
                                </Link>
                            </div>
                        </div>

                        <div
                            v-if="flash.message"
                            :class="[
                                'p-4 mb-8 font-semibold text-sm rounded-xl border-l-4 shadow-md',
                                flash.type === 'success' ? 'bg-emerald-50 text-emerald-800 border-emerald-500' : 'bg-red-50 text-red-800 border-red-500'
                            ]"
                            role="alert"
                        >
                            {{ flash.message }}
                        </div>

                        <div class="overflow-x-auto shadow-xl rounded-lg border border-gray-200">
                            <template v-if="invoices.data.length">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice #</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student Details</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class | Sec | Group</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Due</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="invoice in invoices.data" :key="invoice.id" class="hover:bg-indigo-50 transition duration-150">
                                            
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-bold text-indigo-700">#{{ invoice.invoice_number }}</div>
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-base font-semibold text-gray-900">{{ invoice.student?.name || 'N/A' }}</div>
                                            </td>
                                            
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-700 font-medium">
                                                    {{ invoice.student?.class_name || 'N/A' }} | 
                                                    {{ invoice.student?.section_name || 'N/A' }} |
                                                    {{ invoice.student?.group_name || 'N/A' }}
                                                </div>
                                                <div class="text-xs text-gray-500 italic">{{ invoice.student?.session_name || 'N/A' }} Session</div>
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-sm font-bold text-gray-800">{{ formatCurrency(invoice.total_amount_due) }}</div>
                                                <div class="text-xs text-emerald-600">Paid: {{ formatCurrency(invoice.amount_paid) }}</div>
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                                <div class="text-base font-extrabold" :class="{'text-red-600': invoice.balance_due > 0, 'text-gray-500': invoice.balance_due <= 0 }">
                                                    {{ formatCurrency(invoice.balance_due) }}
                                                </div>
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <div class="text-sm text-gray-700">{{ formatDate(invoice.due_date) }}</div>
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <span
                                                    :class="[
                                                        'px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full capitalize shadow-sm border',
                                                        getStatusClasses(invoice.status)
                                                    ]"
                                                >
                                                    {{ invoice.status.replace('_', ' ') }}
                                                </span>
                                            </td>

                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center justify-end space-x-2">
                                                    <Link 
                                                        :href="route('admin.invoices.show', invoice.id)" 
                                                        class="text-indigo-600 hover:text-indigo-900 p-2 rounded-full hover:bg-indigo-100 transition" 
                                                        title="View Details Page"
                                                    >
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                    </Link>
                                                    
                                                    <Link 
                                                        :href="route('admin.invoices.edit', invoice.id)" 
                                                        class="text-emerald-600 hover:text-emerald-900 p-2 rounded-full hover:bg-emerald-100 transition" 
                                                        title="Edit Invoice"
                                                    >
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-7-7l-4 4-2 2 4 4 6-6 4-4-6-6z"></path></svg>
                                                    </Link>
                                                   
                                                    </div>
                                            </td>

                                        </tr>
                                    </tbody>
                                </table>
                            </template>
                            <p v-else class="text-center py-12 text-gray-500 bg-white">
                                😔 No invoices found. Click 'New Invoice' to get started!
                            </p>
                        </div>
                        
                        <div class="mt-12 flex justify-between items-center" v-if="invoices.links && invoices.links.length > 3">
                            <span class="text-sm text-gray-600">
                                Showing <span class="font-bold text-gray-800">{{ invoices.from || 0 }}</span> to <span class="font-bold text-gray-800">{{ invoices.to || 0 }}</span> of <span class="font-bold text-gray-800">{{ invoices.total }}</span> results
                            </span>
                            <div class="flex space-x-2">
                                <Link
                                    v-for="link in invoices.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    v-html="link.label"
                                    :class="[
                                        'px-4 py-2 text-sm leading-4 font-semibold rounded-lg transition-colors shadow-md',
                                        link.active ? 'bg-indigo-600 text-white' : 'text-gray-700 bg-white border border-gray-300 hover:bg-indigo-50 hover:text-indigo-600',
                                        !link.url ? 'opacity-50 cursor-not-allowed pointer-events-none bg-gray-100 text-gray-500' : ''
                                    ]"
                                />
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        
        <div v-if="showFeeDetailsModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white p-6 sm:p-8 rounded-xl shadow-2xl w-full max-w-lg transform transition-all duration-300 scale-100">
                <h3 class="text-2xl font-bold mb-4 text-indigo-700 flex items-center border-b pb-2">
                    <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    Fee Breakdown: #{{ feeDetails?.invoice_number }}
                </h3>

                <div v-if="feeDetails" class="mb-6 space-y-4">
                    <p class="text-sm text-gray-600">
                        **Student:** <span class="font-semibold text-gray-800">{{ feeDetails.student?.name || 'N/A' }}</span> | 
                        **Total Due:** <span class="font-extrabold text-indigo-600">{{ formatCurrency(feeDetails.total_amount_due) }}</span>
                    </p>

                    <div class="max-h-60 overflow-y-auto pr-2 border rounded-lg p-2 bg-gray-50">
                        <div 
                            v-for="(item, index) in feeDetails.invoice_items" 
                            :key="index" 
                            class="flex justify-between items-center py-2 border-b last:border-b-0"
                        >
                            <span class="text-base font-medium text-gray-700">{{ item.fee_type_name || `Fee Item ${index + 1}` }}</span>
                            <span class="text-base font-extrabold text-gray-900">{{ formatCurrency(item.amount) }}</span>
                        </div>
                    </div>

                    <div class="pt-4 border-t-2 border-indigo-100 font-bold text-lg flex justify-between">
                        <span>Total Calculated:</span>
                        <span class="text-indigo-700">{{ formatCurrency(feeDetails.total_amount_due) }}</span>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button 
                        @click="closeFeeDetailsModal" 
                        class="px-5 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium shadow-sm"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
        
    </AuthenticatedLayout>
</template>