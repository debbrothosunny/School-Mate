<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; // Adjust path if different
import { Head, Link } from '@inertiajs/vue3';

// Define the props that this component will receive from the Laravel controller
const props = defineProps({
    examResults: Array, // This will be an array of summarized exam result objects
});
</script>

<template>
    <Head title="My Exam Results" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Exam Results</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Your Past Exam Results</h3>

                        <div v-if="examResults.length === 0" class="text-gray-600">
                            No exam results found yet for your account.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exam</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class (Section/Group)</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Marks</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                                        
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Published At</th>
                                        <th scope="col" class="relative px-6 py-3">
                                            <span class="sr-only">Actions</span>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="result in examResults" :key="result.id">
                                        <td class="px-6 py-4 whitespace-nowrap">{{ result.exam_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ result.class_name }} <span v-if="result.section_name && result.section_name !== 'N/A'">({{ result.section_name }})</span> <span v-if="result.group_name && result.group_name !== 'N/A'">({{ result.group_name }})</span></td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ result.session_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ result.total_marks_obtained }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ result.percentage }}%</td>
                                       
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="{'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true, 'bg-green-100 text-green-800': result.overall_status === 'Pass', 'bg-red-100 text-red-800': result.overall_status === 'Fail'}">
                                                {{ result.overall_status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ result.published_at }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('student.results.show', result.id)" class="text-indigo-600 hover:text-indigo-900 mr-2">View Details</Link>
                                            <a v-if="result.can_download_pdf" :href="route('student.results.download_pdf', result.id)" class="text-blue-600 hover:text-blue-900">Download PDF</a>
                                            <span v-else class="text-gray-400 text-sm italic">PDF Not Ready</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>