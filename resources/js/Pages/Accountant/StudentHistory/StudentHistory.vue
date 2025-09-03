<script setup>
import { defineProps, computed, watchEffect } from 'vue';

const props = defineProps({
    student: Object,
    examHistory: Array,
    invoiceHistory: Array,
});

// A computed property to group the exams by class name and session name
const groupedExamHistory = computed(() => {
    // Return an empty object if the examHistory prop is not yet available
    if (!props.examHistory || !Array.isArray(props.examHistory)) {
        return {};
    }

    return props.examHistory.reduce((groups, exam) => {
        // Use optional chaining for safe access in case of missing properties
        const className = exam?.class_name;
        const sessionName = exam?.session_name;

        // Ensure both properties exist before grouping
        if (className && sessionName) {
            // Initialize the class group if it doesn't exist
            if (!groups[className]) {
                groups[className] = {};
            }

            // Initialize the session group within the class if it doesn't exist
            if (!groups[className][sessionName]) {
                groups[className][sessionName] = [];
            }

            // Add the exam to the correct group
            groups[className][sessionName].push(exam);
        }

        return groups;
    }, {});
});

// Debugging: Log the incoming data to the console to confirm its structure
watchEffect(() => {
    console.log("Incoming Exam History:", props.examHistory);
    console.log("Grouped Exam History (Computed):", groupedExamHistory.value);
});
</script>

<template>
    <div class="py-8 px-4 sm:px-6 lg:px-8 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">Student History: {{ student.name }}</h1>

            <!-- Exam History Section -->
            <div class="mb-8 bg-white rounded-lg shadow-md overflow-hidden">
                <h2 class="text-xl font-semibold text-gray-800 p-6 border-b border-gray-200">Exam History</h2>
                <div class="p-6">
                    <div v-if="!examHistory || Object.keys(groupedExamHistory).length === 0" class="text-gray-500 text-center">
                        No exam history available.
                    </div>
                    <div v-else v-for="(sessions, className) in groupedExamHistory" :key="className" class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-700 mb-4 border-b pb-2">{{ className }}</h3>
                        <div v-for="(exams, sessionName) in sessions" :key="sessionName" class="mb-4 pl-4">
                            <h4 class="font-medium text-gray-600 mb-2">{{ sessionName }}</h4>
                            <div class="overflow-x-auto rounded-lg border border-gray-200">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam Name</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marks Obtained</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Final Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="exam in exams" :key="exam.id" class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ exam.exam_name || 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ exam.total_marks_obtained || 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ exam.final_letter_grade || 'N/A' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoice History Section -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <h2 class="text-xl font-semibold text-gray-800 p-6 border-b border-gray-200">Invoice History</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Billing Period</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount Due</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Issued</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="!invoiceHistory || invoiceHistory.length === 0">
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No invoice history available.</td>
                            </tr>
                            <tr v-else v-for="invoice in invoiceHistory" :key="invoice.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ invoice.invoice_number }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ invoice.billing_period }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ invoice.total_amount_due }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ invoice.status }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ new Date(invoice.issued_at).toLocaleDateString() }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
