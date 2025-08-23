<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue'; // Import ref and onMounted for reactive data and lifecycle hook

const props = defineProps({
    message: String,
    teacherName: String,
    myClasses: {
        type: Array,
        default: () => [],
    },
});

// Reactive state for real-time notifications
const notifications = ref([]);

// Helper function to format date and time
const formatDateTime = (dateTimeString) => {
    if (!dateTimeString) return '';
    try {
        const date = new Date(dateTimeString);
        // Format date as YYYY-MM-DD
        const formattedDate = date.toISOString().split('T')[0];
        // Format time as HH:MM AM/PM
        const formattedTime = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        return { date: formattedDate, time: formattedTime };
    } catch (e) {
        console.error("Error formatting date/time:", e);
        return { date: dateTimeString, time: dateTimeString }; // Fallback
    }
};

// Lifecycle hook to set up real-time listening when the component is mounted
onMounted(() => {
    // Check if Echo is available (it should be initialized in your app.js/bootstrap.js)
    if (window.Echo) {
        console.log('Laravel Echo is available. Setting up listener for notices channel.');

        // Listen for new notices on the 'notices' public channel
        window.Echo.channel('notices')
            .listen('.new.notice', (e) => { // '.new.notice' matches broadcastAs() in your Laravel event
                console.log('New notice received:', e.notice);
                // Add the new notice to the beginning of the notifications array
                notifications.value.unshift(e.notice);
                // Optionally, you could trigger a temporary visual cue like a toast notification here
            })
            .error((error) => {
                console.error('Echo channel error:', error);
            });

        // Optional: Log connection status for debugging
        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log('Echo connection status: Connected!');
        });
        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            console.log('Echo connection status: Disconnected!');
        });
        window.Echo.connector.pusher.connection.bind('error', (err) => {
            console.error('Echo connection error:', err);
        });

    } else {
        console.warn('Laravel Echo is not initialized. Real-time notifications will not work.');
    }
});
</script>

<template>
    <Head title="Teacher Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Teacher Dashboard</h2>
        </template>

        <!-- Main dashboard content area -->
        <div class="py-8 bg-gray-50 min-h-screen"> <!-- Added bg-gray-50 and min-h-screen for overall background -->
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <!-- Dashboard Header with Welcome Message -->
                    <div class="bg-gradient-to-r from-indigo-700 to-purple-600 text-white p-8 sm:p-10 rounded-t-lg">
                        <h3 class="text-4xl font-extrabold mb-2 tracking-tight">{{ message }}</h3>
                        <p class="text-xl text-indigo-100">Welcome, <span class="font-bold">{{ teacherName }}</span>! Let's make a difference today!</p>
                    </div>

                    <!-- Main content grid -->
                    <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-3 gap-8"> <!-- Adjusted grid layout -->

                        <!-- Quick Actions Card -->
                        <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow duration-300 border border-gray-100">
                            <h4 class="text-2xl font-bold text-gray-800 mb-5">Quick Actions</h4>
                            <ul class="space-y-4">
                                <li>
                                    <Link :href="route('teachermarks.index')" class="flex items-center text-indigo-600 hover:text-indigo-800 font-medium group">
                                        <svg class="h-6 w-6 mr-3 text-indigo-500 group-hover:text-indigo-700 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                        Manage Marks
                                    </Link>
                                </li>
                                <li>
                                    <Link :href="route('teacherattendance.index')" class="flex items-center text-indigo-600 hover:text-indigo-800 font-medium group">
                                        <svg class="h-6 w-6 mr-3 text-indigo-500 group-hover:text-indigo-700 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        Manage Attendance
                                    </Link>
                                </li>
                                <li>
                                    <Link :href="route('teacher.my-notices')" class="flex items-center text-indigo-600 hover:text-indigo-800 font-medium group">
                                        <svg class="h-6 w-6 mr-3 text-indigo-500 group-hover:text-indigo-700 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17l-3 3m0 0l-3-3m3 3V9m-9 1a9 9 0 1118 0V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2v-3"></path></svg>
                                        My Notices
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <!-- Announcements & Events Card (now displaying real-time notifications) -->
                        <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow duration-300 border border-gray-100">
                            <h4 class="text-2xl font-bold text-gray-800 mb-5">Announcements & Events</h4>
                            <div v-if="notifications.length > 0" class="space-y-3">
                                <div v-for="notification in notifications" :key="notification.id" class="bg-blue-50 border-l-4 border-blue-400 text-blue-800 p-3 rounded-md shadow-sm">
                                    <p class="font-semibold">{{ notification.title }}</p>
                                    <p class="text-sm">{{ notification.description }}</p>
                                    <p class="text-xs text-blue-600 mt-1">
                                        Received: {{ formatDateTime(notification.created_at).date }} {{ formatDateTime(notification.created_at).time }}
                                        <!-- Removed class_name and class_names display as per instruction -->
                                    </p>
                                </div>
                            </div>
                            <p v-else class="text-gray-600 italic text-lg">No new announcements or upcoming events at this time.</p>
                            <div class="mt-4">
                                <!-- Existing static announcements or other dynamic content can still go here -->
                            </div>
                        </div>

                        <!-- Placeholder for another general card (e.g., Performance Insights) -->
                        <div class="bg-white rounded-xl p-6 shadow-lg hover:shadow-xl transition-shadow duration-300 border border-gray-100">
                            <h4 class="text-2xl font-bold text-gray-800 mb-5">Performance Insights</h4>
                            <p class="text-gray-600 italic text-lg">Coming soon!</p>
                        </div>

                    </div>

                    <!-- Assigned Classes Section - Now a full-width section below the main grid -->
                    <div class="p-6 sm:p-8 bg-gray-50 rounded-b-lg">
                        <h4 class="text-3xl font-bold text-gray-800 mb-6">Your Assigned Classes</h4>
                        <div v-if="myClasses.length > 0" class="space-y-8">
                            <div v-for="cls in myClasses" :key="cls.id" class="bg-white p-6 rounded-xl shadow-lg border-l-4 border-indigo-500 hover:shadow-xl transition-shadow duration-300">
                                <h5 class="text-2xl font-semibold text-indigo-700 mb-4">
                                    {{ cls.name }}
                                    <span v-if="cls.section && cls.section.name" class="text-indigo-500 text-lg"> (Section: {{ cls.section.name }})</span>
                                </h5>

                                <!-- Students in this Class -->
                                <div class="mb-5">
                                    <h6 class="text-lg font-medium text-gray-700 mb-2 border-b border-gray-200 pb-1">Students:</h6>
                                    <ul v-if="cls.students && cls.students.length > 0" class="list-disc list-inside text-base text-gray-600 space-y-1">
                                        <li v-for="student in cls.students" :key="student.id">{{ student.name }}</li>
                                    </ul>
                                    <p v-else class="text-base text-gray-500 italic">No students assigned to this class yet.</p>
                                </div>

                                <!-- Class Schedule -->
                                <div class="mb-5">
                                    <h6 class="text-lg font-medium text-gray-700 mb-2 border-b border-gray-200 pb-1">Class Schedule:</h6>
                                    <ul v-if="cls.class_schedules && cls.class_schedules.length > 0" class="list-disc list-inside text-base text-gray-600 space-y-1">
                                        <li v-for="(schedule, index) in cls.class_schedules" :key="index">
                                            <span class="font-semibold">{{ schedule.day_of_week }}</span>: {{ schedule.start_time }} - {{ schedule.end_time }}
                                            <span v-if="schedule.room && schedule.room.name"> (Room: {{ schedule.room.name }})</span>
                                            <span class="text-indigo-500"> (Class: {{ cls.name }})</span> <!-- Added class name here -->
                                        </li>
                                    </ul>
                                    <p v-else class="text-base text-gray-500 italic">No class schedule available.</p>
                                </div>

                                <!-- Exam Schedule -->
                                <div>
                                    <h6 class="text-lg font-medium text-gray-700 mb-2 border-b border-gray-200 pb-1">Exam Schedule:</h6>
                                    <ul v-if="cls.exam_schedules && cls.exam_schedules.length > 0" class="list-disc list-inside text-base text-gray-600 space-y-1">
                                        <li v-for="(exam, index) in cls.exam_schedules" :key="index">
                                            <span class="font-semibold">{{ exam.name }}</span>
                                            <span v-if="exam.subject && exam.subject.name"> (Subject: {{ exam.subject.name }})</span>:
                                            {{ formatDateTime(exam.exam_date).date }} from {{ formatDateTime(exam.start_time).time }} to {{ formatDateTime(exam.end_time).time }} on <span class="font-semibold">{{ exam.day_of_week }}</span>
                                            <span v-if="exam.room && exam.room.name"> (Room: {{ exam.room.name }})</span>
                                            <span class="text-indigo-500"> (Class: {{ cls.name }})</span> <!-- Added class name here -->
                                        </li>
                                    </ul>
                                    <p v-else class="text-base text-gray-500 italic">No exam schedule available.</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-10">
                            <p class="text-xl text-gray-600 italic">You are not currently assigned to any classes. Please contact the administration if this is incorrect.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No specific styles needed beyond TailwindCSS for this component */
</style>
