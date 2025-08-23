<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watchEffect } from 'vue';

const props = defineProps({
    pendingInvoices: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

watchEffect(() => {
    if (flash.value && flash.value.message) {
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

const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return 'N/A';
    try {
        const date = new Date(dateTimeString);
        return date.toLocaleString();
    } catch (e) {
        console.error("Error formatting date/time:", e);
        return dateTimeString;
    }
};

const formatCurrency = (amount) => {
    return `BDT ${Number(amount).toFixed(2)}`;
};

const actionForm = useForm({});
const showConfirmModal = ref(false);
const modalAction = ref(''); // 'approve' or 'reject'
const invoiceToActOn = ref(null);

const confirmAction = (invoice, action) => {
    invoiceToActOn.value = invoice;
    modalAction.value = action;
    showConfirmModal.value = true;
};

const executeAction = () => {
    if (modalAction.value === 'approve') {
        actionForm.post(route('admin.payments.approve', invoiceToActOn.value.id), {
            onSuccess: () => {
                closeModal();
                usePage().props.inertia.reload({ only: ['pendingInvoices'] });
            },
            onError: (errors) => {
                console.error("Approval failed:", errors);
            }
        });
    } else if (modalAction.value === 'reject') {
        actionForm.post(route('admin.payments.reject', invoiceToActOn.value.id), {
            onSuccess: () => {
                closeModal();
                usePage().props.inertia.reload({ only: ['pendingInvoices'] });
            },
            onError: (errors) => {
                console.error("Rejection failed:", errors);
            }
        });
    }
};

const closeModal = () => {
    showConfirmModal.value = false;
    invoiceToActOn.value = null;
    modalAction.value = '';
};
</script>

<template>
    <Head title="Pending Payments" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">Pending Payments</h2>
        </template>

        <div class="py-8 bg-gray-50 dark:bg-gray-800 min-h-screen">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-xl overflow-hidden p-6 sm:p-8">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Payments to Review</h3>

                    <div v-if="pendingInvoices && pendingInvoices.length > 0" class="space-y-4">
                        <div
                            v-for="invoice in pendingInvoices"
                            :key="invoice.id"
                            class="flex items-center justify-between p-4 rounded-lg border dark:border-gray-700 bg-gray-50 dark:bg-gray-800"
                        >
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-lg text-gray-900 dark:text-gray-100 truncate">
                                    Invoice #{{ invoice.invoice_number || 'N/A' }}
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium">Student:</span> {{ invoice.student?.user?.name || 'N/A' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">
                                    <span class="font-medium">Ref:</span> {{ invoice.payments?.[0]?.transaction_ref || 'N/A' }}
                                </div>
                            </div>

                            <div class="flex items-center space-x-4 ml-4 flex-shrink-0">
                                <div class="text-lg font-bold text-green-600 dark:text-green-400">
                                    {{ formatCurrency(invoice.payments?.[0]?.amount || 0) }}
                                </div>
                                <div class="flex space-x-2">
                                    <button
                                        @click="confirmAction(invoice, 'approve')"
                                        title="Accept"
                                        class="p-2 rounded-full text-white bg-green-500 hover:bg-green-600 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <button
                                        @click="confirmAction(invoice, 'reject')"
                                        title="Reject"
                                        class="p-2 rounded-full text-white bg-red-500 hover:bg-red-600 transition-colors"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="text-center py-10 text-gray-600 dark:text-gray-400 italic text-xl">
                        No invoices currently awaiting payment approval.
                    </div>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <div v-if="showConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl w-96">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-gray-100">
                    Confirm {{ modalAction === 'approve' ? 'Acceptance' : 'Rejection' }}
                </h3>
                <p class="mb-4 text-gray-800 dark:text-gray-200">
                    Are you sure you want to {{ modalAction }} this payment for 
                    <span class="font-bold">{{ invoiceToActOn?.student?.user?.name || 'N/A' }}</span>?
                </p>
                <div class="flex justify-end space-x-4">
                    <button @click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                    <button
                        @click="executeAction"
                        :class="[
                            'px-4 py-2 text-white rounded-md',
                            modalAction === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'
                        ]"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
