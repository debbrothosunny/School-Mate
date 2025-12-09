<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

// Props received from the Laravel controller
const props = defineProps({
    userName: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        required: true,
    },
    cards: {
        type: Object,
        required: true,
        default: () => ({}),
    },
    recentStudents: {
        type: Array,
        default: () => [],
    },
    recentTeachers: {
        type: Array,
        default: () => [],
    },
    recentInvoices: {
        type: Array,
        default: () => [],
    },
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
};

const formatCurrency = (amount) => {
    if (amount === undefined || amount === null) return 'N/A';
    return `৳${amount.toFixed(2)}`;
};

</script>

<template>
    <Head title="Front Desk Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-3xl text-gray-800 leading-tight">
                Front Desk Dashboard
            </h2>
            <p class="text-sm text-gray-500 mt-1">{{ props.message }}</p>
        </template>

        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Students</p>
                            <div class="text-3xl font-bold text-gray-900 mt-1">{{ props.cards.totalStudents }}</div>
                        </div>
                        <div class="p-3 bg-blue-100 rounded-full">
                            <i class="fas fa-user-graduate text-blue-500 text-2xl"></i>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Teachers</p>
                            <div class="text-3xl font-bold text-gray-900 mt-1">{{ props.cards.totalTeachers }}</div>
                        </div>
                        <div class="p-3 bg-green-100 rounded-full">
                            <i class="fas fa-chalkboard-teacher text-green-500 text-2xl"></i>
                        </div>
                    </div>

                    

                    <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Active Classes</p>
                            <div class="text-3xl font-bold text-gray-900 mt-1">{{ props.cards.totalActiveClasses }}</div>
                        </div>
                        <div class="p-3 bg-purple-100 rounded-full">
                            <i class="fas fa-book-open text-purple-500 text-2xl"></i>
                        </div>
                    </div>

                    <div class="bg-gray-200 p-6 rounded-lg shadow-md flex flex-col items-center justify-center cursor-not-allowed" title="Coming Soon: Online Admission">
                        <div class="p-4 bg-gray-300 rounded-full mb-3">
                        <i class="fas fa-user-plus text-gray-600 text-2xl"></i>

                        </div>
                        <p class="text-gray-600 font-semibold">Online Admission</p>
                        <small class="text-gray-500 mt-1">Coming Soon</small>
                    </div>

                    <div class="bg-gray-200 p-6 rounded-lg shadow-md flex flex-col items-center justify-center cursor-not-allowed" title="Coming Soon: Reporting Feature">
                        <div class="p-4 bg-gray-300 rounded-full mb-3">
                        <i class="fas fa-chart-pie text-gray-600 text-2xl"></i>
                        </div>
                        <p class="text-gray-600 font-semibold">Advanced Reporting</p>
                        <small class="text-gray-500 mt-1">Coming Soon</small>
                    </div>
                </div>

               
            </div>
        </div>
    </AuthenticatedLayout>
</template>