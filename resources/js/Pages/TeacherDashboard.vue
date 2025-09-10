<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
const { message, teacherName, myClasses } = defineProps({
  message: String,
  teacherName: String,
  myClasses: { type: Array, default: () => [] },
});
const notifications = ref([]);
const formatDateTime = (dateTimeString) => {
  if (!dateTimeString) return '';
  try {
    const date = new Date(dateTimeString);
    const datePart = date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    const timePart = date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
    return { date: datePart, time: timePart };
  } catch {
    return { date: dateTimeString, time: dateTimeString };
  }
};
const formatTime = (timeString) => {
  if (!timeString) return '';
  try {
    const date = new Date(`2000-01-01T${timeString}`);
    return date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
  } catch {
    return timeString;
  }
};
onMounted(() => {
  if (window.Echo) {
    window.Echo.channel('notices')
      .listen('.new.notice', (e) => {
        notifications.value.unshift(e.notice);
      });
  }
});
</script>

<template>
  <Head title="Teacher Dashboard" />
  <AuthenticatedLayout class="font-sans" >
    <template #header>
      <div class="flex items-center justify-between flex-wrap gap-4">
        <!-- Adjusted heading font -->
        <h2 class="text-3xl font-extrabold text-gray-800 tracking-tight font-inter">Teacher Dashboard</h2>
        <div class="flex items-center space-x-3">
          <Link :href="route('profile.edit')"
                class="px-5 py-2.5 bg-gray-200 text-gray-700 rounded-full font-semibold hover:bg-gray-300 transition-all shadow-sm font-inter">
            Edit Profile
          </Link>
        </div>
      </div>
    </template>
    <div class="bg-gray-100 min-h-screen py-10 font-inter">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
        <!-- Welcome Section -->
        <section class="bg-slate-900 text-white p-10 rounded-3xl shadow-2xl relative overflow-hidden">
          <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/clean-gray-paper.png')] opacity-5"></div>
          <div class="relative z-10">
            <h1 class="text-4xl sm:text-5xl font-black tracking-tight mb-2 font-inter">{{ message }}</h1>
            <p class="text-xl opacity-90 mt-2 font-inter">Welcome, <span class="font-bold text-teal-300">{{ teacherName }}</span>! Let’s get you started.</p>
          </div>
        </section>
        <!-- Main Content Grid -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-8 font-inter">
          <!-- Quick Actions -->
          <div class="lg:col-span-2 grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Manage Marks Card -->
            <Link :href="route('teachermarks.index')" class="bg-white rounded-3xl p-8 shadow-md border border-gray-200 flex flex-col items-center justify-center text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300 font-inter">
              <div class="p-4 rounded-full bg-teal-100 text-teal-600 mb-4">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
              </div>
              <h3 class="text-xl font-bold text-gray-800 mb-2 font-inter">Manage Marks</h3>
              <p class="text-gray-500 text-sm font-inter">Enter student marks.</p>
            </Link>
            <!-- Manage Attendance Card -->
            <Link :href="route('teacherattendance.index')" class="bg-white rounded-3xl p-8 shadow-md border border-gray-200 flex flex-col items-center justify-center text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300 font-inter">
              <div class="p-4 rounded-full bg-teal-100 text-teal-600 mb-4">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M15 5l1.5 1.5M19 5l-1.5 1.5M17 11v6m-5-6v6m-4-6v6"/></svg>
              </div>
              <h3 class="text-xl font-bold text-gray-800 mb-2 font-inter">Manage Attendance</h3>
              <p class="text-gray-500 text-sm font-inter">Record & view student attendance.</p>
            </Link>
            <!-- My Notices Card -->
            <Link :href="route('teacher.my-notices')" class="bg-white rounded-3xl p-8 shadow-md border border-gray-200 flex flex-col items-center justify-center text-center hover:shadow-xl hover:-translate-y-1 transition-all duration-300 font-inter">
              <div class="p-4 rounded-full bg-teal-100 text-teal-600 mb-4">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17l-3 3m0 0l-3-3m3 3V9m-9 5h18"/></svg>
              </div>
              <h3 class="text-xl font-bold text-gray-800 mb-2 font-inter">My Notices</h3>
              <p class="text-gray-500 text-sm font-inter">view announcements.</p>
            </Link>
          </div>
          <!-- Performance Insights Placeholder -->
          <div class="bg-white rounded-3xl p-8 shadow-md border border-gray-200 flex items-center justify-center text-center lg:col-span-1 opacity-70 cursor-not-allowed font-inter">
            <div>
              <h3 class="text-2xl font-bold text-gray-800 mb-4 font-inter">Resource Library</h3>
              <p class="text-gray-600 italic font-inter">Coming Soon!</p>
            </div>
          </div>
          <div class="bg-white rounded-3xl p-8 shadow-md border border-gray-200 flex items-center justify-center text-center lg:col-span-1 opacity-70 cursor-not-allowed font-inter">
            <div>
              <h3 class="text-2xl font-bold text-gray-800 mb-4 font-inter">Performance Insights</h3>
              <p class="text-gray-600 italic font-inter">Coming Soon!</p>
            </div>
          </div>
          <div class="bg-white rounded-3xl p-8 shadow-md border border-gray-200 flex items-center justify-center text-center lg:col-span-1 opacity-70 cursor-not-allowed font-inter">
            <div>
              <h3 class="text-2xl font-bold text-gray-800 mb-4 font-inter">Assignment and Quiz Builder</h3>
              <p class="text-gray-600 italic font-inter">Coming Soon!</p>
            </div>
          </div>
        </section>
        <!-- Assigned Classes Section -->
        <section class="mt-12 bg-white p-8 rounded-3xl shadow-xl border border-gray-200 font-inter">
          <h3 class="text-2xl font-extrabold text-gray-800 mb-8 border-b border-gray-200 pb-4 font-inter">Your Assigned Classes</h3>
          <div v-if="myClasses.length" class="space-y-8">
            <article v-for="cls in myClasses" :key="cls.id" class="bg-gray-50 p-6 rounded-2xl shadow-inner border border-gray-200 hover:shadow-lg transition-shadow duration-300 font-inter">
              <header class="mb-4">
                <h4 class="text-2xl font-bold text-slate-800 font-inter">
                  {{ cls.class_name }}
                  <small v-if="cls.section?.name" class="text-slate-500 text-lg font-medium ml-2 font-inter">(Section: {{ cls.section.name }})</small>
                </h4>
              </header>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Students -->
                <section class="p-4 bg-gray-100 rounded-lg border border-gray-200">
                  <h5 class="flex items-center text-lg font-semibold text-gray-700 mb-2 border-b border-gray-300 pb-1 font-inter">
                    <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4.125 4.125 0 100 8.25 4.125 4.125 0 000-8.25zM20 20v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/></svg>
                    Students
                  </h5>
                  <ul v-if="cls.students.length" class="list-disc list-inside max-h-40 overflow-y-auto text-gray-600 text-sm space-y-1 font-inter">
                    <li v-for="student in cls.students" :key="student.id" class="truncate">{{ student.name }}</li>
                  </ul>
                  <p v-else class="italic text-gray-400 text-sm font-inter">No students assigned.</p>
                </section>
                <!-- Class Schedule -->
                <section class="p-4 bg-gray-100 rounded-lg border border-gray-200 font-inter">
                  <h5 class="flex items-center text-lg font-semibold text-gray-700 mb-2 border-b border-gray-300 pb-1 font-inter">
                    <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Class Schedule
                  </h5>
                  <ul v-if="cls.class_schedules?.length" class="list-disc list-inside max-h-40 overflow-y-auto text-gray-600 text-sm space-y-1 font-inter">
                    <li v-for="(schedule, idx) in cls.class_schedules" :key="idx">
                      <span class="font-semibold">{{ schedule.day_of_week }}</span>:
                      {{ formatTime(schedule.class_time_slot.start_time) }} – {{ formatTime(schedule.class_time_slot.end_time) }}
                      <span v-if="schedule.room?.name" class="italic">(Room: {{ schedule.room.name }})</span>
                    </li>
                  </ul>
                  <p v-else class="italic text-gray-400 text-sm font-inter">No schedule available.</p>
                </section>
                <!-- Exam Schedule -->
                <section class="p-4 bg-gray-100 rounded-lg border border-gray-200 font-inter">
                  <h5 class="flex items-center text-lg font-semibold text-gray-700 mb-2 border-b border-gray-300 pb-1 font-inter">
                    <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.5v11M12 6.5c2.21 0 4 1.79 4 4v4m-4 4.5v-11M12 17.5c-2.21 0-4-1.79-4-4v-4"/></svg>
                    Exam Schedule
                  </h5>
                  <ul v-if="cls.exam_schedules?.length" class="list-disc list-inside max-h-40 overflow-y-auto text-gray-600 text-sm space-y-1 font-inter">
                    <li v-for="(exam, idx) in cls.exam_schedules" :key="idx">
                      <span class="font-semibold">{{ exam.name }}</span>
                      <span v-if="exam.subject?.name" class="italic">({{ exam.subject.name }})</span>:
                      {{ formatDateTime(exam.exam_date).date }} {{ formatTime(exam.exam_slot.start_time) }} – {{ formatTime(exam.exam_slot.end_time) }}
                      <span v-if="exam.room?.name" class="italic">(Room: {{ exam.room.name }})</span>
                    </li>
                  </ul>
                  <p v-else class="italic text-gray-400 text-sm font-inter">No exams scheduled.</p>
                </section>
              </div>
            </article>
          </div>
          <p v-else class="text-center italic text-gray-600 py-20 font-semibold font-inter">
            You are not assigned to any classes currently. Please contact admin.
          </p>
        </section>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

.font-inter {
  font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont,
    "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
}
</style>
