<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';

const props = defineProps({
    invoices: Object,
    classes: Array,
    selectedClassId: {
        type: Number,
        default: null,
    },
    selectedStatus: {
        type: String,
        default: '',
    },
});

const filterClassId = ref(props.selectedClassId ? String(props.selectedClassId) : '');
const filterStatus = ref(props.selectedStatus || '');
const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Due/Pending', value: 'pending' },
    { label: 'Partially Paid', value: 'partially_paid' },
    { label: 'Paid', value: 'paid' },
];

const applyFilters = () => {
    const params = {
        class_id: filterClassId.value || undefined,
        status: filterStatus.value || undefined,
    };
    router.get(route('admin.payments.history'), params, {
        preserveState: true,
        replace: true,
    });
};

watch(filterClassId, () => {
    applyFilters();
});

const setStatusFilter = (status) => {
    filterStatus.value = status;
    applyFilters();
};

const getStatusClasses = (status) => {
    switch (status) {
        case 'paid':
            return 'bg-gradient-to-r from-teal-400 to-emerald-500 text-white shadow-md'; // Bright green gradient
        case 'partially_paid':
            return 'bg-gradient-to-r from-amber-300 to-orange-400 text-amber-900 shadow-md'; // Warm orange gradient
        case 'pending':
        default:
            return 'bg-gradient-to-r from-red-400 to-rose-500 text-white shadow-md'; // Vibrant red gradient
    }
};

const formatStatus = (status) => {
    switch (status) {
        case 'partially_paid':
            return 'Partial';
        case 'pending':
            return 'Due';
        case 'paid':
            return 'Paid';
        default:
            return status || 'Unknown';
    }
};

const goToPage = (url) => {
    if (url) {
        const urlObj = new URL(url);
        if (filterClassId.value) {
            urlObj.searchParams.set('class_id', filterClassId.value);
        } else {
            urlObj.searchParams.delete('class_id');
        }
        if (filterStatus.value) {
            urlObj.searchParams.set('status', filterStatus.value);
        } else {
            urlObj.searchParams.delete('status');
        }
        router.get(urlObj.toString());
    }
};

/**
 * Calculates the total amount due for each fee category based on invoice_items.
 * The backend should have already filtered these items to only include balance_due > 0.
 */
const getDueFeeCategories = (invoice) => {
    if (invoice.status === 'paid' || !invoice.invoice_items) {
        return [];
    }
    const dueCategories = invoice.invoice_items
        .filter(item => item.balance_due > 0)
        .map(item => ({
            name: item.fee_type?.name || item.description || 'Unknown Fee',
            due: item.balance_due.toString(),
        }));
    return dueCategories;
};

onMounted(() => {
    // console.log('Invoices data:', props.invoices);
});
</script>

<template>
    <Head title="Payment History" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-extrabold text-2xl sm:text-3xl text-gray-900 leading-tight tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-purple-600 to-indigo-700">
                🌈 Payment History & Invoices
            </h2>
        </template>
        <div class="py-8 lg:py-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white shadow-xl rounded-2xl p-6 lg:p-10 text-gray-900 relative border border-gray-100">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-end mb-8 gap-5">
                        <div class="flex items-center space-x-4 w-full sm:w-auto">
                            <label for="class-select" class="text-gray-700 font-bold text-base whitespace-nowrap">Filter by Class:</label>
                            <select
                                id="class-select"
                                v-model="filterClassId"
                                class="w-full sm:w-64 px-5 py-2.5 text-base bg-white border border-indigo-300 rounded-xl shadow-md focus:ring-purple-500 focus:border-purple-500 transition-all duration-300 custom-select-colorful"
                            >
                                <option value="">All Classes</option>
                                <option v-for="sClass in classes" :key="sClass.id" :value="sClass.id">
                                    {{ sClass.name || 'Unknown' }}
                                </option>
                            </select>
                        </div>
                        <div class="flex flex-wrap gap-3 p-2 bg-gradient-to-br from-indigo-50 to-purple-100 border border-purple-200 rounded-2xl shadow-inner w-full sm:w-auto">
                            <button
                                v-for="option in statusOptions"
                                :key="option.value"
                                @click="setStatusFilter(option.value)"
                                :class="[
                                    'px-4 py-2 text-sm font-bold rounded-xl transition-all duration-300 transform hover:scale-105',
                                    filterStatus === option.value
                                        ? 'bg-gradient-to-r from-purple-500 to-indigo-600 text-white shadow-lg ring-2 ring-purple-300' // Active state: dynamic purple gradient
                                        : 'bg-white text-gray-700 hover:bg-purple-50 hover:text-purple-700 shadow-sm', // Inactive state
                                ]"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                    </div>

                    <div class="hidden sm:block overflow-x-auto relative rounded-2xl shadow-lg border border-indigo-100">
                        <table class="w-full text-sm text-left text-gray-700 divide-y divide-gray-200">
                            <thead class="text-xs font-extrabold text-white uppercase bg-gradient-to-r from-indigo-500 to-purple-600">
                                <tr>
                                    <th scope="col" class="px-5 py-4 min-w-[130px] sticky left-0 bg-gradient-to-r from-indigo-500 to-purple-600 z-10 shadow-md border-r border-indigo-400 rounded-tl-2xl">Invoice #</th>
                                    <th scope="col" class="px-5 py-4 min-w-[170px]">Student Name</th>
                                    <th scope="col" class="px-5 py-4 min-w-[110px]">Student ID.</th>
                                    <th scope="col" class="px-5 py-4 min-w-[100px]">Class</th>
                                    <th scope="col" class="px-5 py-4 text-right min-w-[130px]">Balance Due</th>
                                    <th scope="col" class="px-5 py-4 text-right min-w-[130px]">Amount Paid</th>
                                    <th scope="col" class="px-5 py-4 text-center min-w-[100px]">Status</th>
                                    <th scope="col" class="px-5 py-4 min-w-[120px]">Due Date</th>
                                    <th scope="col" class="px-5 py-4 min-w-[220px] rounded-tr-2xl">Due Fee Categories</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-if="!(invoices?.data && invoices.data.length)">
                                    <td colspan="9" class="px-5 py-12 text-center text-gray-500 text-lg font-medium">
                                        No invoices found matching the current filters.
                                    </td>
                                </tr>
                                <tr v-for="(invoice, index) in invoices?.data || []" :key="invoice.id" class="transition-all duration-200 group odd:bg-purple-50/30 even:bg-white hover:bg-purple-100/70">
                                    <th scope="row" class="px-5 py-3.5 font-bold text-indigo-700 whitespace-nowrap sticky left-0 z-10 border-r border-gray-100 group-[.odd]:bg-purple-50/30 group-[.even]:bg-white group-hover:bg-purple-100/70 shadow-sm">
                                        {{ invoice.invoice_number || 'N/A' }}
                                    </th>
                                    <td class="px-5 py-3.5 text-gray-800 font-medium truncate max-w-[200px]" :title="invoice.student?.name">
                                        {{ invoice.student?.name || 'N/A' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-gray-600 truncate max-w-[120px]" :title="invoice.student?.admission_number">
                                        {{ invoice.student?.admission_number || 'N/A' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-gray-600 truncate max-w-[120px]" :title="invoice.student?.class_name?.name">
                                        {{ invoice.student?.class_name?.name || 'N/A' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right font-extrabold text-rose-600">
                                        TK{{ (invoice.balance_due || 0).toString() }}
                                    </td>
                                    <td class="px-5 py-3.5 text-right text-emerald-600 font-bold">
                                        TK{{ (invoice.amount_paid || 0).toString() }}
                                    </td>
                                    <td class="px-5 py-3.5 text-center">
                                        <span :class="['px-3 py-1.5 inline-flex text-xs font-bold rounded-full tracking-wider', getStatusClasses(invoice.status)]">
                                            {{ formatStatus(invoice.status) }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-3.5 text-gray-600">
                                        {{ invoice.due_date ? new Date(invoice.due_date).toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' }) : '—' }}
                                    </td>
                                    <td class="px-5 py-3.5 text-sm">
                                        <div v-if="getDueFeeCategories(invoice).length" class="space-y-1">
                                            <div v-for="item in getDueFeeCategories(invoice)" :key="item.name" class="flex justify-between items-center text-xs">
                                                <span class="font-medium text-gray-700">{{ item.name }}:</span>
                                                <span class="font-bold text-rose-600">TK{{ item.due }}</span>
                                            </div>
                                        </div>
                                        <span v-else class="text-gray-500">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="block sm:hidden mt-6 space-y-4">
                        <div v-if="!(invoices?.data && invoices.data.length)" class="text-center text-gray-500 text-base font-medium p-5 bg-purple-50 rounded-xl border border-purple-200">
                            No invoices found matching the current filters.
                        </div>
                        <div v-for="(invoice, index) in invoices?.data || []" :key="invoice.id" class="bg-white rounded-xl shadow-lg border border-purple-200 p-5 transition-all duration-200 hover:shadow-xl">
                            <div class="flex justify-between items-center pb-3 border-b-2 border-purple-100 mb-3">
                                <h3 class="font-bold text-indigo-700 text-lg">#{{ invoice.invoice_number || 'N/A' }}</h3>
                                <span :class="['px-3 py-1.5 text-sm font-bold rounded-full tracking-wider', getStatusClasses(invoice.status)]">
                                    {{ formatStatus(invoice.status) }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-y-2 text-sm text-gray-700">
                                <div class="col-span-2"><strong class="text-gray-900">Student:</strong> <span class="font-medium">{{ invoice.student?.name || 'N/A' }}</span></div>
                                <div><strong class="text-gray-900">ID:</strong> <span class="font-medium">{{ invoice.student?.admission_number || 'N/A' }}</span></div>
                                <div><strong class="text-gray-900">Class:</strong> <span class="font-medium">{{ invoice.student?.class_name?.name || 'N/A' }}</span></div>
                                <div><strong class="text-gray-900">Due Date:</strong> <span class="font-medium">{{ invoice.due_date ? new Date(invoice.due_date).toLocaleDateString('en-US', { day: 'numeric', month: 'short' }) : '—' }}</span></div>
                                <div class="col-span-2"><strong class="text-rose-600">Balance Due:</strong> <span class="font-extrabold text-rose-600">TK{{ (invoice.balance_due || 0).toString() }}</span></div>
                                <div class="col-span-2"><strong class="text-emerald-600">Amount Paid:</strong> <span class="font-bold text-emerald-600">TK{{ (invoice.amount_paid || 0).toString() }}</span></div>
                                
                                <div class="col-span-2 mt-3 pt-3 border-t border-dashed border-purple-200" v-if="getDueFeeCategories(invoice).length">
                                    <strong class="text-purple-700 text-sm tracking-wider uppercase block mb-2">Due Fee Breakdown:</strong>
                                    <ul class="space-y-1">
                                        <li v-for="item in getDueFeeCategories(invoice)" :key="item.name" class="flex justify-between text-sm">
                                            <span class="text-gray-800">{{ item.name }}</span>
                                            <span class="font-bold text-rose-600">TK{{ item.due }}</span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-span-2 mt-4 text-right">
                                    <button class="text-purple-600 hover:text-purple-800 font-bold text-base transition-colors duration-200 underline flex items-center justify-end gap-1">
                                        View Invoice 
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col sm:flex-row justify-between items-center gap-4 border-t border-gray-200 pt-6">
                        <div class="text-sm font-medium text-gray-600">
                            Showing <span class="font-bold text-indigo-700">{{ invoices.from || 0 }}</span> to <span class="font-bold text-indigo-700">{{ invoices.to || 0 }}</span> of <span class="font-bold text-indigo-700">{{ invoices.total || 0 }}</span> results
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <template v-for="(link, index) in invoices?.links || []" :key="index">
                                <button
                                    v-if="link.url"
                                    @click="goToPage(link.url)"
                                    :disabled="link.active"
                                    :class="[
                                        'px-4 py-2 text-sm font-bold rounded-lg transition-all duration-300 transform hover:scale-105',
                                        link.active
                                            ? 'bg-gradient-to-r from-purple-500 to-indigo-600 text-white shadow-lg ring-2 ring-purple-300'
                                            : 'bg-gray-100 text-gray-700 hover:bg-purple-100 hover:text-purple-700 border border-gray-200',
                                        link.label.includes('Previous') || link.label.includes('Next') ? 'w-auto' : 'w-10' 
                                    ]"
                                    v-html="link.label.replace('Previous', '← Prev').replace('Next', 'Next →')"
                                ></button>
                                <span
                                    v-else-if="!link.url && (link.label.includes('Previous') || link.label.includes('Next'))"
                                    class="px-4 py-2 text-sm text-gray-400"
                                    v-html="link.label.replace('Previous', '← Prev').replace('Next', 'Next →')"
                                ></span>
                                <span
                                    v-else
                                    class="px-4 py-2 text-sm text-gray-400"
                                    v-html="link.label"
                                ></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-6 py-5 text-center bg-gradient-to-r from-indigo-50 to-purple-50 border-t border-gray-200">
            <p class="text-sm text-gray-700 font-medium">
                © All Rights Reserved. Biddaloy is a product of
                <a href="https://smithitbd.com/" target="_blank" class="text-purple-600 hover:text-indigo-800 hover:underline transition-colors duration-200 font-bold">Smith IT</a>
            </p>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Custom Select for colorful design */
.custom-select-colorful {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%238B5CF6'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); /* Purple arrow */
    background-repeat: no-repeat;
    background-position: right 1rem center;
    background-size: 1.25em;
    padding-right: 3rem; /* Make space for the icon */
    appearance: none;
}

/* Base table styling remains similar, but sticky background colors need to match the new gradients */
th:first-child,
td:first-child {
    position: sticky;
    left: 0;
    z-index: 10;
    box-shadow: 2px 0 6px rgba(0, 0, 0, 0.08); /* Slightly more prominent shadow */
}

/* Specific background for sticky header cell to match gradient */
thead th:first-child {
    background: linear-gradient(to right, #6D28D9, #7C3AED) !important; /* Purple to Indigo gradient */
}

/* Dynamic background for sticky data cells based on odd/even rows and hover state */
tbody tr.group:nth-child(odd) th:first-child {
    background-color: rgba(237, 229, 254, 0.3) !important; /* purple-50/30 */
}

tbody tr.group:nth-child(even) th:first-child {
    background-color: white !important;
}

tbody tr.group:hover th:first-child {
    background-color: rgba(237, 229, 254, 0.7) !important; /* purple-100/70 on hover */
}
</style>