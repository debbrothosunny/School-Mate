<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Swal from 'sweetalert2';

// Define the props received from the controller
const props = defineProps({
    students: Array,
    filters: Object,
    flash: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();

// Payment form state. We keep amounts to manage the individual inputs in the template.
const paymentForm = useForm({
    amounts: {},
    payment_method: 'cash',
});

// Search form state
const searchForm = useForm({
    search: props.filters.search || null,
});

// State for the selected student
const selectedStudent = ref(null);

// Watch for flash messages from the server
watch(
    () => page.props.flash,
    (newFlash) => {
        if (newFlash && newFlash.type && newFlash.message) {
            Swal.fire({
                title: newFlash.type === 'success' ? 'Success!' : 'Error!',
                text: newFlash.message,
                icon: newFlash.type,
                showConfirmButton: false,
                timer: 3000,
            });
        }
    },
    { deep: true, immediate: true }
);

// Watch for changes in the students prop to update the selected student
watch(
    () => props.students,
    (newStudents) => {
        if (selectedStudent.value) {
            const updatedStudent = newStudents.find(s => s.id === selectedStudent.value.id);
            if (updatedStudent) {
                selectedStudent.value = updatedStudent;
            } else {
                selectedStudent.value = null;
            }
        }
    },
    { deep: true }
);

// Helper function to format the date with a consistent "Month Day, Year" format
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    // Use Intl.DateTimeFormat for a consistent, readable format
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(date);
};

/**
 * Generates a clean, temporary print window containing only the receipt details
 * and triggers the native print dialog.
 * @param {Object} invoice The invoice object including current balance and amounts.
 */
const printReceipt = (invoice) => {
    // We must ensure the invoice data is fresh. We'll use the current data.
    const student = selectedStudent.value;

    if (!invoice || !student) {
        Swal.fire('Error', 'Missing student or invoice data for printing.', 'error');
        return;
    }
    
    // --- 1. Construct the Printable HTML Content (Receipt Mockup) ---
    const printContent = `
        <style>
            @media print {
                body {
                    font-family: Arial, sans-serif;
                    margin: 0;
                    padding: 0;
                    color: #333;
                }
                .receipt-container {
                    width: 80mm; /* Standard receipt width */
                    margin: 0 auto;
                    padding: 10px;
                    border: 1px solid #ccc;
                    box-sizing: border-box;
                }
                .header {
                    text-align: center;
                    border-bottom: 2px dashed #888;
                    padding-bottom: 10px;
                    margin-bottom: 10px;
                }
                .header h3 {
                    margin: 0;
                    font-size: 1.2em;
                }
                .details p {
                    margin: 3px 0;
                    font-size: 0.9em;
                }
                .amounts {
                    margin-top: 10px;
                    border-top: 2px dashed #888;
                    padding-top: 10px;
                }
                .amounts div {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 5px;
                    font-size: 1em;
                    font-weight: bold;
                }
                .amounts .label {
                    font-weight: normal;
                }
                .footer {
                    text-align: center;
                    margin-top: 15px;
                    border-top: 2px dashed #888;
                    padding-top: 10px;
                    font-size: 0.8em;
                }
            }
        </style>
        <div class="receipt-container">
            <div class="header">
                <h3>Payment Receipt</h3>
                <p>Date: ${formatDate(new Date())}</p>
            </div>
            <div class="details">
                <p><strong>Student:</strong> ${student.name}</p>
                <p><strong>Admission No:</strong> ${student.admission_number}</p>
                <p><strong>Invoice ID:</strong> #${invoice.invoice_number}</p>
                <p><strong>Payment Method:</strong> ${paymentForm.payment_method.toUpperCase()}</p>
            </div>
            <div class="amounts">
                <div><span class="label">Total Amount Due:</span> <span>TK ${invoice.total_amount_due}</span></div>
                <div><span class="label">Total Paid (including this):</span> <span>TK ${invoice.amount_paid}</span></div>
                <div><span class="label">Remaining Balance:</span> <span>TK ${invoice.balance_due}</span></div>
            </div>
            <div class="footer">  
                Thank you for your payment.
                <p class="generation-info">
                    Generated on: ${new Date().toLocaleDateString('en-GB').replace(/\//g, '-')}. Design and Developed by Smith IT
                </p>
            </div>

            
            
        </div>
    `;

    // --- 2. Open Temporary Window and Print ---
    const printWindow = window.open('', 'Print', 'height=600,width=800');
    if (!printWindow) {
        Swal.fire('Error', 'Could not open print window. Please check your browser pop-up blocker settings.', 'error');
        return;
    }
    
    printWindow.document.write('<html><head><title>Payment Receipt</title></head><body>');
    printWindow.document.write(printContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    
    // Wait for content to render before calling print
    printWindow.onload = () => {
        printWindow.focus(); // Focus the window to ensure print dialogue appears
        printWindow.print();
        // Optional: Close the window after printing, or let the user close it
        // setTimeout(() => printWindow.close(), 1000); 
    };
};

// Function to handle payment submission for a specific invoice
const submitPayment = (invoice) => {
    // Get the amount for the specific invoice from the 'amounts' object
    const amountToPay = paymentForm.amounts[invoice.id];

    // Manually check for a valid amount before sending the request
    if (!amountToPay || amountToPay <= 0 || amountToPay > invoice.balance_due) {
        Swal.fire({
            title: 'Error!',
            text: 'Please enter a valid amount greater than 0 and not exceeding the balance due.',
            icon: 'error',
            showConfirmButton: false,
            timer: 3000,
        });
        return; // Prevent the form from submitting
    }

    // --- START: FIX FOR SUBMISSION ---
    // Create a new form object with only the data needed for this specific payment
    const paymentData = {
        invoice_id: invoice.id,
        student_id: selectedStudent.value.id,
        amount: amountToPay,
        payment_method: paymentForm.payment_method,
    };

    // Use a new useForm instance to send only the correct data
    const specificPaymentForm = useForm(paymentData);

    // Send the payment
    specificPaymentForm.post(route('accountant.payments.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            // Clear the amount for the specific invoice after successful submission
            delete paymentForm.amounts[invoice.id];

            // --- NEW: Ask to print immediately after success ---
            Swal.fire({
                title: 'Payment Collected!',
                text: 'Payment collected successfully. Would you like to print the receipt now?',
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: 'Print Receipt',
                cancelButtonText: 'Close',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Find the newly updated invoice data (Inertia should have updated props)
                    const updatedInvoice = props.students
                        .find(s => s.id === selectedStudent.value.id)?.invoices
                        .find(i => i.id === invoice.id);
                        
                    if (updatedInvoice) {
                        printReceipt(updatedInvoice);
                    } else {
                        // Fallback message if updated data is missing for some reason
                         Swal.fire('Print Error', 'Successfully collected payment, but failed to load updated receipt data for printing.', 'warning');
                    }
                }
            });
            // --- END NEW ---
        },
        onError: (errors) => {
            // Display a generic error message or specific errors
            Swal.fire({
                title: 'Error!',
                text: 'There was an issue processing the payment.',
                icon: 'error',
                showConfirmButton: false,
                timer: 3000,
            });
            console.error(errors);
        }
    });
    // --- END: FIX FOR SUBMISSION ---
};

// Function to handle search submission
const search = () => {
    router.get(
        route('accountant.payments.collect'),
        { search: searchForm.search },
        { preserveState: true, replace: true }
    );
};

// Function to select a student for the right-hand panel
const selectStudent = (student) => {
    selectedStudent.value = student;
};

</script>

<template>
    <AuthenticatedLayout>
        <Head title="Collect Payments" />

        <div class="container mx-auto px-4 py-8">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                <h2 class="text-3xl font-bold text-gray-800 mb-4 sm:mb-0">Collect Payments</h2>

                <form @submit.prevent="search" class="w-full sm:w-auto">
                    <div class="flex items-center space-x-2">
                        <input
                            type="text"
                            v-model="searchForm.search"
                            placeholder="Search by name or admission number"
                            class="w-full sm:w-80 rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        />
                        <button
                            type="submit"
                            class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition duration-150"
                        >
                            Search
                        </button>
                        <button
                            type="button"
                            @click="searchForm.reset(), search()"
                            class="bg-gray-400 text-white px-4 py-2 rounded-md hover:bg-gray-500 transition duration-150"
                        >
                            Reset
                        </button>
                    </div>
                </form>
            </div>

            <div v-if="students.length > 0" class="flex flex-col md:flex-row space-y-6 md:space-y-0 md:space-x-6">
                <div class="w-full md:w-1/3 bg-white rounded-xl shadow-lg overflow-hidden">
                    <h3 class="text-xl font-bold px-6 py-4 bg-gray-50 border-b">Select Student</h3>
                    <ul class="divide-y divide-gray-200 max-h-[60vh] overflow-y-auto">
                        <li v-for="student in students" :key="student.id">
                            <button
                                @click="selectStudent(student)"
                                class="flex items-center w-full text-left py-4 px-6 transition duration-150 ease-in-out"
                                :class="{ 'bg-blue-50 text-blue-700 font-semibold': selectedStudent && selectedStudent.id === student.id, 'hover:bg-gray-50': selectedStudent && selectedStudent.id !== student.id }"
                            >
                                <div class="w-10 h-10 bg-blue-200 rounded-full flex items-center justify-center text-blue-800 font-bold mr-4 flex-shrink-0">
                                    {{ student.name.charAt(0) }}
                                </div>
                                <div class="flex-grow">
                                    <div class="font-bold">{{ student.name }}</div>
                                    <div class="text-sm text-gray-500 mt-1 flex flex-wrap gap-x-2">
                                        <span>Admission No: {{ student.admission_number }}</span>
                                    </div>
                                </div>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="w-full md:w-2/3 bg-white rounded-xl shadow-lg p-6">
                    <div v-if="selectedStudent">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start mb-6 pb-4 border-b">
                            <div class="flex items-center">
                                <div class="w-16 h-16 bg-blue-200 rounded-full flex items-center justify-center text-blue-800 font-bold text-2xl mr-4">
                                    {{ selectedStudent.name.charAt(0) }}
                                </div>
                                <div class="flex-grow">
                                    <h3 class="text-2xl font-bold text-gray-800">{{ selectedStudent.name }}</h3>
                                    <div class="text-md text-gray-500 mt-1 flex flex-wrap gap-x-4">
                                        <span>Admission No: {{ selectedStudent.admission_number }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="selectedStudent.invoices.length > 0" class="grid gap-6">
                            <div v-for="invoice in selectedStudent.invoices" :key="invoice.id" class="bg-gray-50 rounded-lg p-5 shadow-sm border border-gray-200">
                                <div class="flex justify-between items-center mb-4">
                                    <span class="font-bold text-lg text-gray-700">Invoice #{{ invoice.invoice_number }}</span>
                                    <span
                                        class="px-3 py-1 text-xs font-semibold rounded-full uppercase tracking-wide"
                                        :class="{
                                            'bg-green-100 text-green-800': invoice.status === 'paid',
                                            'bg-red-100 text-red-800': invoice.status === 'overdue',
                                            'bg-yellow-100 text-yellow-800': invoice.status === 'partially_paid',
                                            'bg-gray-100 text-gray-800': invoice.status === 'pending',
                                        }"
                                    >
                                        {{ invoice.status.replace('_', ' ') }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 text-sm text-gray-600 mb-5">
                                    <div>
                                        <strong>Total Amount:</strong>
                                        <p class="font-bold text-gray-800">TK {{ invoice.total_amount_due }}</p>
                                    </div>
                                    <div>
                                        <strong>Amount Paid:</strong>
                                        <p class="font-bold text-gray-800">TK {{ invoice.amount_paid }}</p>
                                    </div>
                                    <div>
                                        <strong>Balance Due:</strong>
                                        <p class="font-bold text-red-600">TK {{ invoice.balance_due }}</p>
                                    </div>
                                    
                                </div>
                                <div v-if="invoice.balance_due > 0">
                                    <form @submit.prevent="submitPayment(invoice)">
                                        <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3 items-stretch sm:items-center">
                                            <input
                                                type="number"
                                                v-model="paymentForm.amounts[invoice.id]"
                                                :placeholder="'Enter amount (TK ' + invoice.balance_due + ')'"
                                                class="w-full sm:w-1/2 rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                                                required
                                            />
                                            <select
                                                v-model="paymentForm.payment_method"
                                                class="w-full sm:w-1/4 rounded-md border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                                            >
                                                <option value="cash">Cash</option>
                                            </select>
                                            <button
                                                type="submit"
                                                class="w-full sm:w-1/4 bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 transition duration-150"
                                            >
                                                Collect Payment
                                            </button>
                                        </div>
                                    </form>
                                </div>
                                <div v-else>
                                    <p class="text-center text-green-500 font-medium p-2">Amount is fully paid. 🎉</p>
                                </div>
                                
                                <!-- START: NEW PRINT BUTTON AND DOWNLOAD SECTION -->
                                <div v-if="invoice.status === 'paid' || invoice.status === 'partially_paid'" class="mt-4 flex flex-col sm:flex-row justify-end gap-3">
                                    <button
                                        type="button"
                                        @click="printReceipt(invoice)"
                                        class="inline-flex items-center justify-center px-4 py-2 bg-pink-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-pink-700 active:bg-pink-800 focus:outline-none focus:border-pink-800 focus:ring focus:ring-pink-300 disabled:opacity-25 transition w-full sm:w-auto"
                                    >
                                        <!-- Print Icon SVG -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M5 4v3h10V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm0 6h10v7a2 2 0 01-2 2H7a2 2 0 01-2-2v-7zm0 1h10v4H5v-4z" clip-rule="evenodd" />
                                            <path d="M5 13a1 1 0 11-2 0 1 1 0 012 0zm7-7a1 1 0 100-2 1 1 0 000 2z" />
                                        </svg>
                                        Print Receipt
                                    </button>

                                    <a :href="route('invoices.download.pdf', { invoice: invoice.id })"
                                        target="_blank"
                                        class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:border-blue-800 focus:ring focus:ring-blue-300 disabled:opacity-25 transition w-full sm:w-auto"
                                    >
                                        <!-- Download Icon SVG -->
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L10 11.586l1.293-1.293a1 1 0 111.414 1.414l-2 2a1 1 0 01-1.414 0l-2-2a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            <path fill-rule="evenodd" d="M10 2a1 1 0 011 1v7a1 1 0 11-2 0V3a1 1 0 011-1z" clip-rule="evenodd" />
                                        </svg>
                                        Download Receipt
                                    </a>
                                </div>
                                <!-- END: NEW PRINT BUTTON AND DOWNLOAD SECTION -->

                            </div>
                        </div>
                        <div v-else class="text-center text-gray-500 p-12">
                            No invoices for this student.
                        </div>
                    </div>
                    <div v-else class="text-center text-gray-500 p-12">
                        <p class="text-xl">Select a student from the list to view their invoices.</p>
                    </div>
                </div>
            </div>
            <div v-else class="text-center py-12 bg-white rounded-lg shadow-md">
                <p class="text-xl text-gray-500">No students found.</p>
                <p v-if="searchForm.search" class="mt-2 text-gray-400">Please try a different search query or click "Reset".</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
