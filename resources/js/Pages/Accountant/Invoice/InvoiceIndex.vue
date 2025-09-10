<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    invoices: {
        type: Object,
        required: true,
    },
});

const flash = computed(() => usePage().props.flash || {});

// Format currency as BDT
const formatCurrency = (amount) => `BDT ${Number(amount).toFixed(2)}`;

// A utility function to format the full date string for display.
const formatDate = (dateString) => {
    if (!dateString) {
        return 'N/A';
    }
    const date = new Date(dateString);
    return date.toISOString().slice(0, 10);
};

const form = useForm({});
const showDeleteModal = ref(false);
const invoiceToDelete = ref(null);

const confirmDelete = (invoice) => {
    invoiceToDelete.value = invoice;
    showDeleteModal.value = true;
};

const deleteInvoice = () => {
    if (invoiceToDelete.value) {
        form.delete(route('admin.invoices.destroy', invoiceToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
            },
            onError: (errors) => {
                console.error("Error deleting invoice:", errors);
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    invoiceToDelete.value = null;
};
</script>

<template>
    <Head title="Manage Invoices" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                Manage Invoices
            </h2>
        </template>

        <div class="py-4">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="p-4 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">Invoice List</h3>
                        <Link
                            :href="route('admin.invoices.create')"
                            class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-xs font-semibold rounded-md hover:bg-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 transition-colors"
                        >
                            Create New Invoice
                        </Link>
                    </div>

                    <div
                        v-if="flash.message"
                        :class="[
                            'p-4 mb-4 text-sm rounded-lg',
                            flash.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                        ]"
                        role="alert"
                    >
                        {{ flash.message }}
                    </div>

                    <!-- Clean, responsive list-item design -->
                    <div class="space-y-4">
                        <div
                            v-for="invoice in invoices.data"
                            :key="invoice.id"
                            class="bg-white dark:bg-gray-900 rounded-lg shadow-sm p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between transition-shadow"
                        >
                            <!-- Invoice & Student Info -->
                            <div class="flex-grow mb-2 sm:mb-0">
                                <div class="font-bold text-lg text-gray-900 dark:text-gray-100">
                                    Invoice #{{ invoice.invoice_number }}
                                </div>
                                <div class="text-gray-700 dark:text-gray-300">
                                    <span class="font-medium">{{ invoice.student?.name || 'N/A' }}</span>
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    <span v-if="invoice.student?.class_name">Class: {{ invoice.student.class_name }}</span> |
                                    <span v-if="invoice.student?.session_name">Session: {{ invoice.student.session_name }}</span>
                                </div>
                            </div>
                            
                            <!-- Financial Details -->
                            <div class="flex-grow grid grid-cols-2 gap-x-6 gap-y-2 text-sm sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 sm:flex-grow-0 sm:w-auto">
                                <div class="flex flex-col">
                                    <p class="text-gray-500 dark:text-gray-400">Total Due</p>
                                    <p class="font-semibold">{{ formatCurrency(invoice.total_amount_due) }}</p>
                                </div>
                                <div class="flex flex-col">
                                    <p class="text-gray-500 dark:text-gray-400">Paid</p>
                                    <p class="font-semibold">{{ formatCurrency(invoice.amount_paid) }}</p>
                                </div>
                                <div class="flex flex-col">
                                    <p class="text-gray-500 dark:text-gray-400">Balance Due</p>
                                    <p class="font-bold text-red-600 dark:text-red-400">{{ formatCurrency(invoice.balance_due) }}</p>
                                </div>
                                <div class="flex flex-col">
                                    <p class="text-gray-500 dark:text-gray-400">Due Date</p>
                                    <p class="font-semibold">{{ formatDate(invoice.due_date) }}</p>
                                </div>
                                <div class="flex flex-col">
                                    <p class="text-gray-500 dark:text-gray-400">Status</p>
                                    <span
                                        :class="[
                                            'px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full capitalize w-fit mt-1',
                                            {
                                                'bg-yellow-100 text-yellow-800': invoice.status === 'pending',
                                                'bg-blue-100 text-blue-800': invoice.status === 'partially_paid',
                                                'bg-green-100 text-green-800': invoice.status === 'paid',
                                                'bg-red-100 text-red-800': invoice.status === 'overdue',
                                                'bg-gray-100 text-gray-800': invoice.status === 'cancelled',
                                            }
                                        ]"
                                    >
                                        {{ invoice.status.replace('_', ' ') }}
                                    </span>
                                </div>
                            </div>

                            <!-- New Action Buttons -->
                            <div class="flex-none mt-4 sm:mt-0 sm:ml-4 flex items-center space-x-2">
                                <Link
                                    :href="route('account.invoices.download-pdf', invoice.id)"
                                    class="inline-flex items-center px-4 py-2 bg-blue-500 text-white text-xs font-semibold rounded-md hover:bg-blue-600 transition-colors "target="_blank"
                                >
                                    Download PDF
                                </Link>
                                <button
                                    @click="confirmDelete(invoice)"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-xs font-semibold rounded-md hover:bg-red-700 transition-colors"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                        <p v-if="!invoices.data.length" class="text-center py-4 text-gray-500 dark:text-gray-400">No invoices found.</p>
                    </div>

                    <!-- Pagination -->
                    <div class="mt-8 flex justify-between items-center">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            Showing {{ invoices.from }} to {{ invoices.to }} of {{ invoices.total }} results
                        </span>
                        <div class="flex space-x-1">
                            <Link
                                v-for="link in invoices.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-2 text-sm leading-4 font-medium rounded-md',
                                    link.active ? 'bg-indigo-600 text-white' : 'text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700',
                                    !link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''
                                ]"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl w-96">
                <h3 class="text-lg font-semibold mb-4">Confirm Deletion</h3>
                <p class="mb-4 text-gray-800 dark:text-gray-200">
                    Are you sure you want to delete this invoice for
                    <span class="font-bold">{{ invoiceToDelete ? invoiceToDelete.student.name : '' }}</span>?
                </p>
                <div class="flex justify-end space-x-4">
                    <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                    <button
                        @click="deleteInvoice"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
