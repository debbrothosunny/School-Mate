<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({
    payrollRecords: Object,
    staffWithStructures: Array,
});

const searchQuery = ref('');

// Helper: Month name
const getMonthName = (monthNumber) => {
    if (!monthNumber) return 'N/A';
    return new Date(2020, monthNumber - 1).toLocaleString('default', { month: 'long' });
};

// Forms
const paymentForm = useForm({
    payment_date: new Date().toISOString().slice(0, 10),
});

const payrollForm = useForm({
    pay_month: new Date().getMonth() + 1,
    pay_year: new Date().getFullYear(),
    salariable_id: null,
    salariable_type: null,
    deduction_percentage: 0,
    absence_deduction_amount: 0,
    absent_days: 0,
});

// Unique ID for modals
const generateUniqueId = () => `swal-${Math.random().toString(36).substr(2, 9)}`;

// ──────────────────────────────────────────────────────────────
// Print Payroll Records
// ──────────────────────────────────────────────────────────────
const printPayroll = () => {
    const uniqueId = generateUniqueId();
    Swal.fire({
        title: 'Print Payroll Records',
        html: `
            <div class="p-6 bg-gradient-to-br from-indigo-50 to-white rounded-xl shadow-sm border text-center">
                <div class="mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 bg-indigo-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                    </div>
                    <p class="text-lg font-semibold text-gray-800">Select payroll period to print</p>
                    <p class="text-sm text-gray-600 mt-1">Leave blank to print filtered records</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Month</label>
                        <select id="${uniqueId}-month" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">All Months</option>
                            ${[...Array(12).keys()].map(i => {
                                const m = i + 1;
                                const sel = m === payrollForm.pay_month ? 'selected' : '';
                                return `<option value="${m}" ${sel}>${getMonthName(m)}</option>`;
                            }).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Year</label>
                        <input id="${uniqueId}-year" type="number" min="2020"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                               value="${payrollForm.pay_year}">
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Print',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#4F46E5',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition shadow-md',
            cancelButton: 'px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition mr-3'
        },
        preConfirm: async () => {
            const month = document.getElementById(`${uniqueId}-month`).value;
            const year = document.getElementById(`${uniqueId}-year`).value;

            let records = filteredRecords.value;
            let settings = { school_logo: null, school_name: 'N/A', address: 'N/A' };

            if (month || year) {
                // Fetch records from the API if a specific month/year is selected
                try {
                    const response = await axios.get(route('payroll.records-for-print'), {
                        params: { month, year }
                    });
                    records = response.data.records;
                    settings = response.data.settings;
                } catch (error) {
                    Swal.fire('Error!', 'Failed to fetch payroll records.', 'error');
                    return false;
                }
            } else {
                // Use filteredRecords and fetch settings separately
                try {
                    const response = await axios.get(route('payroll.records-for-print'));
                    settings = response.data.settings;
                } catch (error) {
                    Swal.fire('Error!', 'Failed to fetch school settings.', 'error');
                    return false;
                }
            }

            if (!records.length) {
                Swal.fire({
                    icon: 'warning',
                    title: 'No Records',
                    text: 'No payroll records available to print.',
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    showConfirmButton: false,
                });
                return false;
            }

            // Determine the pay period for the header
            const payPeriod = month && year ? `${getMonthName(month)} ${year}` : 
                            records[0] ? `${getMonthName(records[0].pay_month)} ${records[0].pay_year}` : 'All Periods';

            // Generate printable HTML
            let printContent = `
                <html>
                <head>
                    <title>Salary Report - ${payPeriod}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .print-container { max-width: 1000px; margin: 0 auto; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .header img { max-width: 150px; max-height: 100px; margin-bottom: 10px; }
                        h1 { color: #1F2937; font-size: 24px; margin: 0; }
                        .school-info { color: #4B5563; font-size: 14px; margin: 5px 0; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #E5E7EB; padding: 12px; text-align: left; }
                        th { background-color: #059669; color: white; font-weight: bold; text-transform: uppercase; }
                        td { color: #1F2937; }
                        .text-emerald { color: #059669; font-weight: bold; }
                        .text-red { color: #DC2626; font-weight: medium; }
                        .signature { min-width: 150px; }
                        @media print {
                            body { margin: 0; }
                            .no-print { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="print-container">
                        <div class="header">
                            ${settings.school_logo ? `<img src="${settings.school_logo}" alt="School Logo">` : ''}
                            <h1>${settings.school_name}</h1>
                            <p class="school-info">${settings.address}</p>
                            <p class="school-info">Salary Report - Period: ${payPeriod}</p>
                            <p class="school-info">Generated on ${new Date().toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' })}</p>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Staff</th>
                                    <th>Gross</th>
                                    <th>Ded %</th>
                                    <th>Abs Cut</th>
                                    <th>Net</th>
                                    <th>Period</th>
                                    <th>Signature</th>
                                </tr>
                            </thead>
                            <tbody>
            `;

            records.forEach(r => {
                printContent += `
                    <tr>
                        <td>${r.salariable?.name || 'N/A'}</td>
                        <td>৳${r.gross_earning?.toLocaleString() || '0'}</td>
                        <td>${r.deduction_percentage_used || 0}%</td>
                        <td class="text-red">৳${r.absence_deduction_amount?.toLocaleString() || '0'}</td>
                        <td class="text-emerald">৳${r.net_payable?.toLocaleString() || '0'}</td>
                        <td>${getMonthName(r.pay_month)} ${r.pay_year}</td>
                        <td class="signature"></td>
                    </tr>
                `;
            });

            printContent += `
                            </tbody>
                        </table>
                    </div>
                </body>
                </html>
            `;

            // Open a new window and trigger print
            const printWindow = window.open('', '_blank');
            printWindow.document.write(printContent);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
            printWindow.onafterprint = () => printWindow.close();
        },
    });
};

// ──────────────────────────────────────────────────────────────
// 1. Make Payment
// ──────────────────────────────────────────────────────────────
const makePayment = (record) => {
    const netPayable = record.net_payable?.toLocaleString('en-US') || 'N/A';
    const staffName = record.salariable?.name || 'Staff';
    const payPeriod = `${getMonthName(record.pay_month)} ${record.pay_year}`;
    const uniqueId = generateUniqueId();
    Swal.fire({
        title: `Confirm Payment for ${staffName}`,
        html: `
            <div class="p-6 bg-gradient-to-br from-indigo-50 to-white rounded-xl shadow-sm border">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <p class="text-sm text-gray-600">Pay Period</p>
                        <p class="font-semibold text-gray-900">${payPeriod}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-600">Net Payable</p>
                        <p class="text-2xl font-bold text-emerald-600">৳${netPayable}</p>
                    </div>
                </div>
                <div class="mt-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label>
                    <input id="${uniqueId}-date" type="date"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                           value="${paymentForm.payment_date}">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Record Payment',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#4F46E5',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'px-5 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition ml-3',
            cancelButton: 'px-5 py-2.5 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition'
        },
        preConfirm: () => {
            const date = document.getElementById(`${uniqueId}-date`).value;
            if (!date) return Swal.showValidationMessage('Please select payment date');
            paymentForm.payment_date = date;
            return paymentForm.put(route('payroll.pay', record.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Paid!',
                        text: `${staffName}'s salary marked as paid.`,
                        toast: true,
                        position: 'top-end',
                        timer: 3000,
                        showConfirmButton: false
                    });
                },
                onError: () => Swal.fire('Error!', 'Failed to record payment.', 'error')
            });
        },
    });
};

// ──────────────────────────────────────────────────────────────
// 2. Make Salary – Manual Absence Deduction
// ──────────────────────────────────────────────────────────────
const makeSalaryForStaff = (staff) => {
    const staffName = staff.name || 'Staff';
    const grossPay = staff.gross_pay_structure || 0;
    const uniqueId = generateUniqueId();
    const updateNet = () => {
        const pct = parseInt(document.getElementById(`${uniqueId}-deduction`)?.value || 0);
        const manualCut = parseFloat(document.getElementById(`${uniqueId}-manual-cut`)?.value || 0);
        const absentDays = parseInt(document.getElementById(`${uniqueId}-absent-days`)?.value || 0);
        const genCut = Math.round((grossPay * pct) / 100);
        const net = grossPay - genCut - manualCut;
        document.getElementById(`${uniqueId}-gen-cut`).textContent = `৳${genCut.toLocaleString()}`;
        document.getElementById(`${uniqueId}-manual-cut-display`).textContent = `৳${manualCut.toLocaleString()}`;
        document.getElementById(`${uniqueId}-net`).textContent = `৳${net.toLocaleString()}`;
    };
    Swal.fire({
        title: `Generate Salary – ${staffName}`,
        width: 720,
        html: `
            <div class="p-6 bg-white rounded-xl shadow-sm border">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Month</label>
                        <select id="${uniqueId}-month" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition">
                            ${[...Array(12).keys()].map(i => {
                                const m = i + 1;
                                const sel = m === payrollForm.pay_month ? 'selected' : '';
                                return `<option value="${m}" ${sel}>${getMonthName(m)}</option>`;
                            }).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Year</label>
                        <input id="${uniqueId}-year" type="number" min="2020"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition"
                               value="${payrollForm.pay_year}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">General Deduction %</label>
                        <select id="${uniqueId}-deduction" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition">
                            <option value="0" ${payrollForm.deduction_percentage === 0 ? 'selected' : ''}>0%</option>
                            <option value="10" ${payrollForm.deduction_percentage === 10 ? 'selected' : ''}>10%</option>
                            <option value="20" ${payrollForm.deduction_percentage === 20 ? 'selected' : ''}>20%</option>
                            <option value="25" ${payrollForm.deduction_percentage === 25 ? 'selected' : ''}>25%</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-red-600 mb-1.5">Absence Deduction (৳)</label>
                        <input id="${uniqueId}-manual-cut" type="number" min="0" step="1" value="0"
                               class="w-full px-4 py-2.5 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 transition placeholder:text-red-300"
                               placeholder="e.g. 2500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Absent Days</label>
                        <input id="${uniqueId}-absent-days" type="number" min="0" max="31" step="1" value="0"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 transition"
                               placeholder="e.g. 2">
                    </div>
                </div>
                <div class="bg-gradient-to-r from-indigo-50 to-emerald-50 p-5 rounded-xl border">
                    <div class="grid grid-cols-2 gap-4 text-sm font-medium">
                        <div><span class="text-gray-600">Gross Pay:</span> <span class="font-bold text-gray-900">৳${grossPay.toLocaleString()}</span></div>
                        <div><span class="text-orange-600">General Deduction:</span> <span id="${uniqueId}-gen-cut" class="font-semibold">৳0</span></div>
                        <div><span class="text-red-600">Absence Deduction:</span> <span id="${uniqueId}-manual-cut-display" class="font-semibold">৳0</span></div>
                        <div class="col-span-2">
                            <span class="text-emerald-600 font-bold text-lg">Net Payable:</span>
                            <span id="${uniqueId}-net" class="font-bold text-emerald-600 text-xl">৳${grossPay.toLocaleString()}</span>
                        </div>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Generate Salary',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#4F46E5',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition shadow-md',
            cancelButton: 'px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition mr-3'
        },
        didOpen: () => {
            ['deduction', 'manual-cut', 'absent-days'].forEach(id => {
                const el = document.getElementById(`${uniqueId}-${id}`);
                el?.addEventListener('input', updateNet);
            });
            updateNet();
        },
        preConfirm: () => {
            const manualCut = parseFloat(document.getElementById(`${uniqueId}-manual-cut`).value) || 0;
            if (manualCut > grossPay) {
                Swal.showValidationMessage('Absence deduction cannot exceed gross pay');
                return false;
            }
            payrollForm.pay_month = parseInt(document.getElementById(`${uniqueId}-month`).value);
            payrollForm.pay_year = parseInt(document.getElementById(`${uniqueId}-year`).value);
            payrollForm.salariable_id = staff.id;
            payrollForm.salariable_type = staff.salariable_type;
            payrollForm.deduction_percentage = parseInt(document.getElementById(`${uniqueId}-deduction`).value);
            payrollForm.absence_deduction_amount = manualCut;
            payrollForm.absent_days = parseInt(document.getElementById(`${uniqueId}-absent-days`).value) || 0;
            return payrollForm.post(route('payroll.make', staff.id), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Payroll Generated!',
                        text: `${staffName}'s salary with ৳${manualCut.toLocaleString()} absence deduction.`,
                        toast: true,
                        position: 'top-end',
                        timer: 3000,
                        showConfirmButton: false
                    });
                },
                onError: (err) => {
                    const msg = Object.values(err).flat().join('<br>');
                    Swal.fire('Error!', msg, 'error');
                }
            });
        }
    });
};

// ──────────────────────────────────────────────────────────────
// 3. Process Monthly Payroll
// ──────────────────────────────────────────────────────────────
const processMonthlyPayroll = () => {
    const uniqueId = generateUniqueId();
    Swal.fire({
        title: 'Process Monthly Payroll',
        html: `
            <div class="p-6 bg-gradient-to-br from-indigo-50 to-white rounded-xl shadow-sm border text-center">
                <div class="mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 bg-indigo-100 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-lg font-semibold text-gray-800">Generate payroll for <span class="text-indigo-600">all staff</span></p>
                    <p class="text-sm text-gray-600 mt-1">This will create salary records for the selected month</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Month</label>
                        <select id="${uniqueId}-month" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            ${[...Array(12).keys()].map(i => {
                                const m = i + 1;
                                const sel = m === payrollForm.pay_month ? 'selected' : '';
                                return `<option value="${m}" ${sel}>${getMonthName(m)}</option>`;
                            }).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Year</label>
                        <input id="${uniqueId}-year" type="number" min="2020"
                               class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                               value="${payrollForm.pay_year}">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Deduction %</label>
                        <select id="${uniqueId}-deduction" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="0" ${payrollForm.deduction_percentage === 0 ? 'selected' : ''}>0%</option>
                            <option value="10" ${payrollForm.deduction_percentage === 10 ? 'selected' : ''}>10%</option>
                            <option value="20" ${payrollForm.deduction_percentage === 20 ? 'selected' : ''}>20%</option>
                            <option value="25" ${payrollForm.deduction_percentage === 25 ? 'selected' : ''}>25%</option>
                        </select>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Process All',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#4F46E5',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 transition shadow-md',
            cancelButton: 'px-6 py-3 bg-gray-100 text-gray-700 font-semibold rounded-lg hover:bg-gray-200 transition mr-3'
        },
        preConfirm: () => {
            const month = parseInt(document.getElementById(`${uniqueId}-month`).value);
            const year = parseInt(document.getElementById(`${uniqueId}-year`).value);
            const deduction = parseInt(document.getElementById(`${uniqueId}-deduction`).value);
            if (!month || !year) {
                Swal.showValidationMessage('Please select both month and year');
                return false;
            }
            payrollForm.month = month;
            payrollForm.year = year;
            payrollForm.deduction_percentage = deduction;
            return payrollForm.post(route('payroll.process.monthly'), {
                preserveScroll: true,
                onSuccess: () => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Payroll Processed!',
                        text: `All staff payroll for ${getMonthName(month)} ${year}.`,
                        toast: true,
                        position: 'top-end',
                        timer: 3000,
                        showConfirmButton: false
                    });
                },
                onError: (err) => {
                    const msg = Object.values(err).flat().join('<br>');
                    Swal.fire('Error!', msg, 'error');
                }
            });
        },
    });
};

// ──────────────────────────────────────────────────────────────
// 4. Search & Filter
// ──────────────────────────────────────────────────────────────
const filteredRecords = computed(() => {
    if (!searchQuery.value) return props.payrollRecords.data;
    const q = searchQuery.value.toLowerCase();
    return props.payrollRecords.data.filter(r => {
        const s = r.salariable;
        return (
            s?.name?.toLowerCase().includes(q) ||
            r.gross_earning?.toString().includes(q) ||
            r.net_payable?.toString().includes(q) ||
            r.deduction_percentage_used?.toString().includes(q) ||
            r.absence_deduction_amount?.toString().includes(q) ||
            getMonthName(r.pay_month).toLowerCase().includes(q) ||
            r.pay_year?.toString().includes(q)
        );
    });
});

const filteredStaff = computed(() => {
    if (!props.staffWithStructures) return [];
    const q = searchQuery.value.toLowerCase();
    return props.staffWithStructures
        .map(s => {
            const cs = s.current_salary || {};
            const gross = (cs.basic_salary || 0) +
                          (cs.house_rent_allowance || 0) +
                          (cs.medical_allowance || 0) +
                          (cs.academic_allowance || 0) +
                          (cs.transport_allowance || 0) +
                          (cs.festival_bonus || 0);
            return { ...s, gross_pay_structure: gross };
        })
        .filter(s => !q || (
            s.name?.toLowerCase().includes(q) ||
            s.role?.toLowerCase().includes(q) ||
            s.gross_pay_structure.toString().includes(q)
        ));
});

// Safe pagination visit
const visit = (url) => {
    if (url) router.get(url, {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Staff Salary & Payroll" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
                Staff Salary & Payroll
            </h2>
        </template>
        <div class="py-10 bg-gradient-to-b from-gray-50 to-white min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
                <!-- Search Bar -->
                <div class="relative max-w-2xl mx-auto">
                    <TextInput
                        v-model="searchQuery"
                        placeholder="Search staff, salary, deduction, month..."
                        class="w-full pl-12 pr-12 py-3 text-base rounded-xl border-gray-200 shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                    />
                    <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <button v-if="searchQuery" @click="searchQuery = ''" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <!-- Process Monthly Payroll and Print Buttons -->
                <div class="flex justify-end gap-4">
                    <button
                        @click="processMonthlyPayroll"
                        class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition shadow-sm"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Process Monthly Payroll
                    </button>
                    <button
                        @click="printPayroll"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition shadow-sm"
                    >
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Print Payroll
                    </button>
                </div>
                <!-- Current Staff -->
                <section class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                    <div class="p-6 bg-gradient-to-r from-indigo-600 to-indigo-700">
                        <h3 class="text-xl font-bold text-white">Current Staff</h3>
                        <p class="text-indigo-100 text-sm mt-1">View and manage salary structures</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-wider">Basic</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-wider">HRA</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-wider">Medical</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-wider">Academic</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-wider">Transport</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-wider">Bonus</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-wider">Gross</th>
                                    <th class="px-6 py-4 text-right text-xs font-bold text-indigo-700 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="s in filteredStaff" :key="s.id + s.salariable_type" class="hover:bg-indigo-50 transition">
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ s.name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ s.role }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">৳{{ s.current_salary?.basic_salary?.toLocaleString() || '0' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">৳{{ s.current_salary?.house_rent_allowance?.toLocaleString() || '0' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">৳{{ s.current_salary?.medical_allowance?.toLocaleString() || '0' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">৳{{ s.current_salary?.academic_allowance?.toLocaleString() || '0' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">৳{{ s.current_salary?.transport_allowance?.toLocaleString() || '0' }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">৳{{ s.current_salary?.festival_bonus?.toLocaleString() || '0' }}</td>
                                    <td class="px-6 py-4 text-sm font-bold text-indigo-600">৳{{ s.gross_pay_structure?.toLocaleString() || '0' }}</td>
                                    <td class="px-6 py-4 text-right">
                                        <button v-if="s.current_salary" @click="makeSalaryForStaff(s)"
                                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition shadow-sm">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                            Make Salary
                                        </button>
                                        <span v-else class="text-red-500 text-xs font-medium">No Setup</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-if="!filteredStaff.length" class="text-center py-12 text-gray-500">
                            <p class="text-lg">No staff found matching your search.</p>
                        </div>
                    </div>
                </section>
                <!-- Payroll History -->
                <section class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                    <div class="p-6 bg-gradient-to-r from-emerald-600 to-emerald-700">
                        <h3 class="text-xl font-bold text-white">Payroll History</h3>
                        <p class="text-emerald-100 text-sm mt-1">View all generated salaries and payment status</p>
                    </div>
                    <!-- Desktop Table -->
                    <div class="hidden lg:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Staff</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Gross</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Ded %</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-red-600 uppercase tracking-wider">Abs Cut</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-emerald-600 uppercase tracking-wider">Net</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Period</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Signature</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="r in filteredRecords" :key="r.id" class="hover:bg-emerald-50 transition">
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">{{ r.salariable?.name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">৳{{ r.gross_earning?.toLocaleString() }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ r.deduction_percentage_used }}%</td>
                                    <td class="px-6 py-4 text-sm text-red-600 font-medium">৳{{ r.absence_deduction_amount?.toLocaleString() || 0 }}</td>
                                    <td class="px-6 py-4 text-sm font-bold text-emerald-600">৳{{ r.net_payable?.toLocaleString() }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ getMonthName(r.pay_month) }} {{ r.pay_year }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ getMonthName(r.pay_month) }} {{ r.pay_year }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Mobile Cards -->
                    <div class="lg:hidden p-4 space-y-4">
                        <div v-for="r in filteredRecords" :key="r.id" class="bg-gradient-to-br from-white to-emerald-50 p-5 rounded-xl border border-emerald-100 shadow-sm hover:shadow-md transition">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <p class="font-bold text-gray-900">{{ r.salariable?.name }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ getMonthName(r.pay_month) }} {{ r.pay_year }}</p>
                                </div>
                                <span :class="r.payment_date ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                      class="px-3 py-1 text-xs font-bold rounded-full">
                                    {{ r.payment_date ? 'Paid' : 'Pending' }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div><span class="text-gray-600">Gross:</span> <span class="font-semibold">৳{{ r.gross_earning?.toLocaleString() }}</span></div>
                                <div><span class="text-red-600">Abs Cut:</span> <span class="font-medium">৳{{ r.absence_deduction_amount?.toLocaleString() || 0 }}</span></div>
                                <div class="col-span-2">
                                    <span class="text-emerald-600 font-bold">Net Payable:</span>
                                    <span class="font-bold text-emerald-600 text-lg">৳{{ r.net_payable?.toLocaleString() }}</span>
                                </div>
                            </div>
                            <div v-if="!r.payment_date" class="mt-4">
                                <button @click="makePayment(r)"
                                        class="w-full px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition shadow-sm">
                                    Record Payment
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Pagination -->
                    <nav v-if="payrollRecords.links?.length > 2" class="flex justify-center p-6 border-t bg-gray-50">
                        <div class="inline-flex rounded-lg shadow-sm">
                            <template v-for="link in payrollRecords.links" :key="link.label">
                                <button
                                    v-if="link.url"
                                    @click="visit(link.url)"
                                    v-html="link.label"
                                    :class="{
                                        'px-4 py-2.5 text-sm font-medium rounded-lg transition': true,
                                        'bg-indigo-600 text-white': link.active,
                                        'bg-white text-gray-700 hover:bg-gray-100': !link.active
                                    }"
                                />
                                <span v-else v-html="link.label" class="px-4 py-2.5 text-sm font-medium text-gray-400 bg-gray-100 rounded-lg cursor-not-allowed" />
                            </template>
                        </div>
                    </nav>
                </section>
                <!-- Footer -->
                <footer class="text-center py-8 text-xs text-gray-500 border-t mt-16">
                    © <span class="font-semibold text-indigo-600">Biddaloy</span> — A product of
                    <a href="https://smithitbd.com" target="_blank" class="text-indigo-600 hover:underline font-medium">Smith IT</a>
                </footer>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Smooth scroll & focus */
* { scroll-behavior: smooth; }
input:focus, select:focus { outline: none; }
/* Custom scrollbar */
::-webkit-scrollbar { height: 8px; }
::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 4px; }
::-webkit-scrollbar-thumb { background: #c7d2fe; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #a5b4fc; }
</style>