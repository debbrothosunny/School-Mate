<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watchEffect } from 'vue';

const props = defineProps({
    studentInvoices: Array,
    userName: String,
});

const showPaymentModal = ref(false);
const selectedInvoice = ref(null);

const paymentForm = useForm({
    invoice_id: null,
    amount: 0,
    method: '',
    transaction_ref: '',
});

const paymentMethods = [
    { value: '', label: '-- Select Method --' },
    { value: 'cash', label: 'Cash' },
    { value: 'bank_transfer', label: 'Bank Transfer' },
    { value: 'mobile_banking', label: 'Mobile Banking' },
    { value: 'cheque', label: 'Cheque' },
    { value: 'online_gateway', label: 'Online Gateway' },
];

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return '';
    const date = new Date(dateTimeString);
    return date.toISOString().split('T')[0];
};

const formatInvoiceStatusForDisplay = (statusString) => {
    if (!statusString) return '';
    const formatted = statusString.replace(/_/g, ' ');
    return formatted.charAt(0).toUpperCase() + formatted.slice(1);
};

const openPaymentModal = (invoice) => {
    selectedInvoice.value = invoice;
    paymentForm.invoice_id = invoice.id;
    paymentForm.amount = Number(invoice.balance_due).toFixed(2);
    paymentForm.method = '';
    paymentForm.transaction_ref = '';
    showPaymentModal.value = true;

    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
};

const closePaymentModal = () => {
    showPaymentModal.value = false;
    selectedInvoice.value = null;
    paymentForm.reset();
    const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
    modal?.hide();
};

const submitPayment = () => {
    paymentForm.post(route('student.invoices.process-payment'), {
        onSuccess: () => {
            closePaymentModal();
            usePage().props.inertia.reload({ only: ['studentInvoices'] });
        },
        onError: () => {
            // Flash message from backend will handle the toast, no need for alert here
        },
    });
};

const page = usePage();
const flash = computed(() => page.props.flash || {});

watchEffect(() => {
    if (flash.value.message) {
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

// A new ref to manage the expanded state of invoice items
const expandedInvoices = ref({});

// A function to toggle the expanded state for an invoice
const toggleDetails = (invoiceId) => {
    expandedInvoices.value[invoiceId] = !expandedInvoices.value[invoiceId];
};

/**
 * Initiates the download of a PDF for a given invoice by calling a backend route.
 * @param {object} invoice The invoice object to generate the PDF for.
 */
const downloadPdf = (invoice) => {
    console.log("Attempting to download PDF for invoice ID:", invoice.id);
    // This function now just redirects to the backend route
    window.location.href = route('student.invoices.download-pdf', { invoice: invoice.id });
};
</script>

<template>
    <Head title="My Invoices" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Invoices</h2>
        </template>

        <div class="py-8 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
                    <!-- Page Header with Welcome Message -->
                    <div class="bg-gradient-to-r from-blue-600 to-purple-700 text-white p-8 sm:p-10 rounded-t-lg shadow-md">
                        <h3 class="text-4xl font-extrabold mb-2 tracking-tight">Hello, <span class="font-bold">{{ userName ?? 'Student' }}</span>!</h3>
                        <p class="text-xl text-blue-100">Your invoice summary is below.</p>
                    </div>

                    <!-- Invoices List -->
                    <div v-if="studentInvoices && studentInvoices.length > 0" class="p-6 sm:p-8 space-y-4">
                        <div
                            v-for="invoice in studentInvoices"
                            :key="invoice.id"
                            class="bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow duration-200 overflow-hidden"
                            :class="{
                                'border-blue-300': invoice.status === 'pending',
                                'border-green-300': invoice.status === 'paid',
                                'border-orange-300': invoice.status === 'partially_paid',
                                'border-yellow-300': invoice.status === 'pending_payment_approval',
                                'border-gray-300': invoice.status === 'cancelled'
                            }"
                        >
                            <div class="p-5 flex flex-col md:flex-row justify-between items-start md:items-center space-y-3 md:space-y-0 md:space-x-4">
                                <!-- Invoice Info -->
                                <div class="flex-grow">
                                    <h4 class="text-xl font-bold text-gray-800 mb-1">Invoice #{{ invoice.invoice_number }}</h4>
                                    <p class="text-sm text-gray-600">Billing Period: {{ invoice.billing_period || 'N/A' }}</p>
                                    <p class="text-sm text-gray-600">Due Date: {{ formatDateTime(invoice.due_date) }}</p>
                                    
                                    <!-- New button to toggle details -->
                                    <button @click="toggleDetails(invoice.id)" class="text-sm font-semibold text-indigo-600 mt-2 hover:underline focus:outline-none">
                                        <span v-if="expandedInvoices[invoice.id]">Hide Details</span>
                                        <span v-else>Show Details</span>
                                    </button>
                                </div>

                                <!-- Amounts -->
                                <div class="flex-shrink-0 text-right md:text-left">
                                    <p class="text-md text-gray-700">Total: <span class="font-bold text-indigo-700">BDT {{ Number(invoice.total_amount_due).toFixed(2) }}</span></p>
                                    <p class="text-md text-gray-700">Balance DUE: <span class="font-bold text-red-600">BDT {{ Number(invoice.balance_due).toFixed(2) }}</span></p>
                                </div>

                                <!-- Status and Action -->
                                <div class="flex-shrink-0 flex flex-col items-end md:items-center space-y-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-semibold"
                                        :class="{
                                            'bg-blue-100 text-blue-700': invoice.status === 'pending',
                                            'bg-green-100 text-green-700': invoice.status === 'paid',
                                            'bg-orange-100 text-orange-700': invoice.status === 'partially_paid',
                                            'bg-yellow-100 text-yellow-700': invoice.status === 'pending_payment_approval',
                                            'bg-gray-100 text-gray-700': invoice.status === 'cancelled'
                                        }"
                                    >
                                        {{ formatInvoiceStatusForDisplay(invoice.status) }}
                                    </span>
                                    <div class="flex space-x-2 mt-2">
                                        <!-- UPDATED: Only show the Download PDF button for paid or partially paid invoices -->
                                        <button
                                            v-if="['paid', 'partially_paid'].includes(invoice.status)"
                                            @click="downloadPdf(invoice)"
                                            class="px-3 py-2 bg-gray-600 text-white text-sm font-medium rounded-md shadow-md hover:bg-gray-700
                                                    focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200"
                                        >
                                            Download PDF
                                        </button>
                                        <button
                                            @click="openPaymentModal(invoice)"
                                            :disabled="['paid', 'cancelled', 'pending_payment_approval'].includes(invoice.status)"
                                            class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md shadow-md hover:bg-indigo-700
                                                    focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200
                                                    disabled:opacity-50 disabled:cursor-not-allowed"
                                        >
                                            <span v-if="invoice.status === 'pending_payment_approval'">Awaiting Approval</span>
                                            <span v-else-if="invoice.status === 'paid'">Paid</span>
                                            <span v-else-if="invoice.status === 'cancelled'">Cancelled</span>
                                            <span v-else>Pay Now</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- New section for invoice items (conditionally shown) -->
                            <div v-if="expandedInvoices[invoice.id]" class="p-5 border-t border-gray-200 bg-gray-50">
                                <h5 class="text-lg font-bold text-gray-800 mb-3">Invoice Items</h5>
                                <div class="space-y-3">
                                    <div v-for="item in invoice.invoice_items" :key="item.id" class="flex justify-between items-center bg-white p-4 rounded-md shadow-sm border border-gray-100">
                                        <span class="text-md font-medium text-gray-700">{{ item.fee_type?.name || 'Unknown Fee' }}</span>
                                        <span class="text-md font-semibold text-indigo-600">BDT {{ Number(item.amount).toFixed(2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-16 text-gray-600 italic text-2xl">
                        <p>🎉 You have no invoices at this time. Great job!</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- Bootstrap Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-lg shadow-xl border border-gray-200">
                <div class="modal-header bg-gradient-to-r from-indigo-600 to-blue-600 text-white p-4 rounded-t-lg">
                    <h5 class="modal-title text-xl font-bold" id="paymentModalLabel">Make Payment for Invoice #{{ selectedInvoice?.invoice_number }}</h5>
                    <button type="button" class="btn-close text-white opacity-90 hover:opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form @submit.prevent="submitPayment">
                    <div class="modal-body p-6 space-y-4">
                        <div v-if="selectedInvoice" class="bg-blue-50 p-4 rounded-md border border-blue-200 text-blue-800">
                            <p class="text-base font-semibold mb-1">Invoice Details:</p>
                            <p class="text-sm"><strong>Total Due:</strong> BDT {{ Number(selectedInvoice.total_amount_due).toFixed(2) }}</p>
                            <p class="text-sm"><strong>Balance Due:</strong> <span class="font-bold text-red-700">BDT {{ Number(selectedInvoice.balance_due).toFixed(2) }}</span></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount to Pay (BDT)</label>
                            <input
                                type="number"
                                v-model.number="paymentForm.amount"
                                :min="0.01"
                                :max="selectedInvoice ? Number(selectedInvoice.balance_due) : 0"
                                step="0.01"
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                            />
                            <div v-if="paymentForm.errors.amount" class="text-red-500 text-sm mt-1">{{ paymentForm.errors.amount }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                            <select v-model="paymentForm.method" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150">
                                <option v-for="method in paymentMethods" :key="method.value" :value="method.value">{{ method.label }}</option>
                            </select>
                            <div v-if="paymentForm.errors.method" class="text-red-500 text-sm mt-1">{{ paymentForm.errors.method }}</div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Transaction Reference (optional)</label>
                            <input
                                type="text"
                                v-model="paymentForm.transaction_ref"
                                placeholder="e.g. Cheque No / TXN ID"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 transition duration-150"
                            />
                            <div v-if="paymentForm.errors.transaction_ref" class="text-red-500 text-sm mt-1">{{ paymentForm.errors.transaction_ref }}</div>
                        </div>
                    </div>

                    <div class="modal-footer bg-gray-50 px-6 py-4 flex justify-end space-x-3 rounded-b-lg border-t border-gray-200">
                        <button type="button" class="px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-md shadow-sm hover:bg-gray-300 transition-colors" data-bs-dismiss="modal" @click="closePaymentModal">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md shadow-md hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed" :disabled="paymentForm.processing">
                            <span v-if="paymentForm.processing">Processing...</span>
                            <span v-else>Submit Payment</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* No specific custom CSS needed beyond TailwindCSS for this component */
</style>
