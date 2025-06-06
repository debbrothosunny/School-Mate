<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    message: String,
    totalStudents: Number, // Example prop from DashboardController
    pendingFeesCount: Number, // Example prop from DashboardController
    // You can define other props here for accounts-specific data
    // e.g., latestTransactions: Array, totalRevenue: String, etc.
});
</script>

<template>
    <Head title="Accounts Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Accounts Dashboard</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-2xl font-bold mb-4">Hello, Accounts Staff!</h3>
                        <p class="text-lg">{{ message }}</p>

                        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <!-- Quick Stats Card 1 -->
                            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-lg shadow-sm">
                                <h4 class="text-md font-semibold text-blue-700">Total Registered Students</h4>
                                <p class="text-3xl font-bold text-blue-900">{{ totalStudents ?? 'N/A' }}</p>
                            </div>

                            <!-- Quick Stats Card 2 -->
                            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg shadow-sm">
                                <h4 class="text-md font-semibold text-yellow-700">Pending Fee Payments</h4>
                                <p class="text-3xl font-bold text-yellow-900">{{ pendingFeesCount ?? 'N/A' }}</p>
                            </div>

                            <!-- Quick Stats Card 3 -->
                            <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-lg shadow-sm">
                                <h4 class="text-md font-semibold text-green-700">Total Revenue (This Month)</h4>
                                <p class="text-3xl font-bold text-green-900">$XX,XXX.XX</p>
                            </div>
                        </div>

                        <div class="mt-10">
                            <h4 class="text-xl font-semibold mb-3 text-gray-800">Financial Overview Sections:</h4>
                            <ul class="list-disc list-inside space-y-3 text-gray-700">
                                <li>
                                    **Recent Transactions:** Display a table of the last 5-10 financial transactions (payments received, expenses).
                                </li>
                                <li>
                                    **Fee Collection Status:** Summaries of fees due, collected, and overdue, perhaps by class or student group.
                                </li>
                                <li>
                                    **Expense Tracking:** A quick view of recent expenses or a summary of budget vs. actual spending.
                                </li>
                                <li>
                                    **Reporting Tools:** Links to generate financial reports (e.g., income statements, balance sheets).
                                </li>
                                <li>
                                    **Student Accounts Management:** Direct links or tools to view and manage individual student fee statuses.
                                </li>
                            </ul>
                        </div>

                        <div class="mt-8 p-4 bg-gray-50 border-l-4 border-gray-400 text-gray-700 rounded-md">
                            <p class="font-semibold">Note:Its future plan</p>
                            <p>This dashboard is a placeholder. Specific data tables, charts, and functionalities will be added here based on detailed requirements for the Accounts role.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
