<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3'; // Ensure Link is imported if used in template

const props = defineProps({
    message: String,
    teacherName: String,
    myClasses: {
        type: Array,
        default: () => [],
    },
    // Add any other props passed from MyClassesController
});
</script>

<template>
    <Head title="Teacher Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Teacher Dashboard</h2>
        </template>

        <!-- The main white card container for the dashboard content -->
        <!-- Removed py-12 as AuthenticatedLayout already provides padding -->
        <div class="sm:px-0 lg:px-0"> <!-- Removed max-w-7xl mx-auto to allow content to naturally fill the available width -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Adjusted padding from p-6 to p-4 for a more compact layout -->
                <div class="p-4 text-gray-900">
                    <h3 class="text-xl font-bold mb-4">{{ message }}</h3>

                    <div v-if="myClasses.length > 0" class="mb-6">
                        <h4 class="text-lg font-semibold mb-2">Your Assigned Classes:</h4>
                        <ul class="list-disc pl-5 space-y-1">
                            <li v-for="cls in myClasses" :key="cls.id" class="text-gray-700">
                                <span class="font-medium">{{ cls.name }}</span>
                                <span v-if="cls.section_name"> (Section: {{ cls.section_name }})</span>
                            </li>
                        </ul>
                    </div>
                    <div v-else class="mb-6">
                        <p class="text-gray-600">You are not currently assigned to any classes. Please contact the administration if this is incorrect.</p>
                    </div>

                    
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No specific styles needed beyond TailwindCSS for this component */
</style>
