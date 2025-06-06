<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

// Define the props that will be passed from the controller
const props = defineProps({
    myClasses: Array, // Array of class objects
    teacherName: String, // Name of the teacher
});

// You can add computed properties or methods here for displaying timetable
// For example, grouping classes by day or time
</script>

<template>
    <Head title="My Classes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                My Classes ({{ props.teacherName }})
            </h2>
        </template>

        <!-- Removed py-12 as AuthenticatedLayout already provides padding -->
        <div class="sm:px-0 lg:px-0"> <!-- Removed max-w-7xl mx-auto to allow content to naturally fill the available width -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Assigned Classes:</h3>

                    <div v-if="myClasses.length > 0">
                        <ul class="divide-y divide-gray-200">
                            <li v-for="classItem in myClasses" :key="classItem.id" class="py-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <p class="text-sm font-medium text-indigo-600">
                                            {{ classItem.name }} (Section: {{ classItem.section ? classItem.section.name : 'N/A' }})
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            Time: {{ classItem.class_time ?? 'Not set' }} | Day: {{ classItem.day ?? 'Not set' }} | Room: {{ classItem.room_number ?? 'Not set' }}
                                        </p>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div v-else>
                        <p class="text-gray-600">No classes assigned to you yet.</p>
                    </div>

                    <h3 class="text-lg font-medium text-gray-900 mt-8 mb-4">Your Timetable:</h3>
                    <div class="border p-4 rounded-md bg-gray-50 text-gray-600">
                        <p class="mb-4">This is where you would display the detailed timetable for the teacher's assigned classes.</p>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">Day</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">Class</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">Room</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-if="myClasses.length === 0">
                                    <td colspan="4" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">No timetable entries.</td>
                                </tr>
                                <tr v-for="classItem in myClasses" :key="classItem.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ classItem.day ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ classItem.class_time ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ classItem.name }} ({{ classItem.section ? classItem.section.name : 'N/A' }})</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ classItem.room_number ?? 'N/A' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
