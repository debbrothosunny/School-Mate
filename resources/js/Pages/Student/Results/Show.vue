<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'; // Adjust path if different
import { Head, Link } from '@inertiajs/vue3';

// Define the props that this component will receive from the Laravel controller
const props = defineProps({
    examResult: Object, // This will be a single, detailed exam result object
});
</script>

<template>
    <Head :title="`${examResult.exam_name} Result`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ examResult.exam_name }} Result for {{ examResult.student_name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="mb-6 border-b pb-4">
                            <h3 class="text-xl font-bold mb-4">Overall Result Summary</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                                <div><strong>Exam:</strong> {{ examResult.exam_name }}</div>
                                <div><strong>Student:</strong> {{ examResult.student_name }}</div>
                                <div><strong>Class:</strong> {{ examResult.class_name }}</div>
                                <div><strong>Session:</strong> {{ examResult.session_name }}</div>
                                <div><strong>Section:</strong> {{ examResult.section_name }}</div>
                                <div><strong>Group:</strong> {{ examResult.group_name }}</div>
                                <div><strong>Published At:</strong> {{ examResult.published_at }}</div>
                            </div>

                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-lg font-medium">
                                <div class="p-3 bg-gray-50 rounded-lg text-center">
                                    <strong>Total Marks Obtained:</strong> <br>{{ examResult.total_marks_obtained }} / {{ examResult.overall_percentage_base }}
                                </div>
                                <div class="p-3 bg-gray-50 rounded-lg text-center">
                                    <strong>Overall Percentage:</strong> <br>{{ examResult.percentage }}%
                                </div>
                                <div class="p-3 bg-gray-50 rounded-lg text-center">
                                    <strong>Final Grade Point:</strong> <br>{{ examResult.final_grade_point }}
                                </div>

                                <div class="p-3 bg-gray-50 rounded-lg text-center">
                                    <strong>Final Letter Grade:</strong> <br>{{ examResult.final_letter_grade }}
                                </div>
                                
                                <div class="p-3 bg-gray-50 rounded-lg text-center md:col-span-2 lg:col-span-1">
                                    <strong>Status:</strong>
                                    <span :class="{'px-2 py-1 inline-flex text-sm leading-5 font-semibold rounded-full': true, 'bg-green-100 text-green-800': examResult.overall_status === 'Pass', 'bg-red-100 text-red-800': examResult.overall_status === 'Fail'}">
                                        {{ examResult.overall_status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-6 border-b pb-4">
                            <h3 class="text-xl font-bold mb-4">Subject-wise Details</h3>
                            <div v-if="examResult.subject_wise_data && examResult.subject_wise_data.length > 0" class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Marks Obtained</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Marks</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Grade Point</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Letter Grade</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="subject in examResult.subject_wise_data" :key="subject.subject_name">
                                            <td class="px-6 py-4 whitespace-nowrap">{{ subject.subject_name }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ subject.marks_obtained ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ subject.total_marks }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ subject.percentage }}%</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ subject.grade_point }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">{{ subject.letter_grade }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span :class="{'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true, 'bg-green-100 text-green-800': subject.pass_status === 'Pass', 'bg-red-100 text-red-800': subject.pass_status === 'Fail', 'bg-gray-100 text-gray-800': subject.pass_status === 'N/A'}">
                                                    {{ subject.pass_status }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div v-else class="text-gray-600">
                                No subject-wise data available for this result.
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end">
                            <Link :href="route('student.results.index')" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-3">
                                Back to Results List
                            </Link>
                            <a v-if="examResult.can_download_pdf" :href="route('student.results.download_pdf', examResult.exam_id)" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Download Result PDF
                            </a>
                            <span v-else class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-gray-400 bg-gray-200 cursor-not-allowed">
                                PDF Not Available
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>