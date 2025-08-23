<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { ref, computed, watchEffect } from 'vue'

const props = defineProps({
    classes: Array,
    sessions: Array,
    sections: Array,
    subjects: Array,
    teachers: Array,
    rooms: Array,
    timetableEntries: Array,
    selectedFilters: Object,
    flash: Object,
});

const flash = computed(() => usePage().props.flash || {});

// --- Filter Form State ---
const filterForm = useForm({
    class_name_id: props.selectedFilters.class_name_id || '',
    session_id: props.selectedFilters.session_id || '',
    section_id: props.selectedFilters.section_id || '',
    day_of_week: props.selectedFilters.day_of_week || '',
    teacher_id: props.selectedFilters.teacher_id || '',
    subject_id: props.selectedFilters.subject_id || '',
});

const daysOfWeek = [
    { value: 'MONDAY', label: 'Monday' },
    { value: 'TUESDAY', label: 'Tuesday' },
    { value: 'WEDNESDAY', label: 'Wednesday' },
    { value: 'THURSDAY', label: 'Thursday' },
    { value: 'FRIDAY', label: 'Friday' },
    { value: 'SATURDAY', label: 'Saturday' },
    { value: 'SUNDAY', label: 'Sunday' },
];

// Function to apply filters
const applyFilters = () => {
    router.get(route('timetable.index'), filterForm.data(), {
        preserveState: true,
        preserveScroll: true,
    });
};

// Function to clear filters
const clearFilters = () => {
    filterForm.reset();
    applyFilters();
};

// --- Group Timetable Entries by Day ---
const groupedTimetable = computed(() => {
    const grouped = {};
    daysOfWeek.forEach(day => {
        grouped[day.value] = props.timetableEntries
            .filter(entry => entry.day_of_week === day.value)
            .sort((a, b) => {
                const timeA = a.start_time;
                const timeB = b.start_time;
                if (timeA < timeB) return -1;
                if (timeA > timeB) return 1;
                return 0;
            });
    });
    return grouped;
});

// --- Delete Action ---
const deleteForm = useForm({});
const confirmDelete = (entry) => {
    Swal.fire({
        title: 'Are you sure?',
        text: `You want to delete the entry for ${entry.subject?.name} on ${entry.day_of_week} at ${entry.start_time}? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444', // Tailwind red-500
        cancelButtonColor: '#3b82f6',  // Tailwind blue-500
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            deleteForm.delete(route('timetable.destroy', entry.id), {
                preserveScroll: true,
                onSuccess: () => {
                    applyFilters();
                },
                onError: (errors) => {
                    console.error("Error deleting timetable entry:", errors);
                    Swal.fire({
                        icon: 'error',
                        title: 'Deletion Failed!',
                        text: errors.message || 'There was an error deleting the timetable entry.',
                    });
                },
            });
        }
    });
};

// --- Flash Messages ---
watchEffect(() => {
    if (flash.value && flash.value.message) {
        Swal.fire({
            icon: flash.value.type === 'success' ? 'success' : 'error',
            title: flash.value.type === 'success' ? 'Success!' : 'Error!',
            text: flash.value.message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }
});

// --- Inline SVG Icons ---
const icons = {
  add: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-1"><path d="M5 12h14"/><path d="M12 5v14"/></svg>`,
  edit: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>`,
  del: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4"><path d="M20 5H9l-7 7 7 7h11a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2Z"/><line x1="10" x2="16" y1="9" y2="15"/><line x1="16" x2="10" y1="9" y2="15"/></svg>`,
  filter: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-1"><path d="M3 6h18"/><path d="M7 12h10"/><path d="M10 18h4"/></svg>`,
  clear: `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-1"><path d="M21 4H8l-7 8 7 8h13a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2z"/><line x1="18" x2="12" y1="9" y2="15"/><line x1="12" x2="18" y1="9" y2="15"/></svg>`
};

// Function to get icon string
const getIcon = (iconName) => {
  return icons[iconName];
};
</script>

<template>
    <Head title="Class Timetable" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Class Timetable Management</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-xl rounded-2xl p-6 sm:p-8">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
                        <h3 class="text-xl font-semibold text-gray-900 mb-2 sm:mb-0">Timetable Overview</h3>
                        <Link :href="route('timetable.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-xl shadow-lg transition duration-300 flex items-center">
                            <div v-html="getIcon('add')"></div>
                            Add New Entry
                        </Link>
                    </div>

                    <!-- Filter Section -->
                    <div class="p-6 bg-gray-50 rounded-2xl shadow-inner mb-8">
                        <h4 class="text-lg font-medium text-gray-700 mb-4">Filter Timetable</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label for="filter_class" class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                                <select id="filter_class" v-model="filterForm.class_name_id" @change="applyFilters" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">All Classes</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="filter_session" class="block text-sm font-medium text-gray-700 mb-1">Session</label>
                                <select id="filter_session" v-model="filterForm.session_id" @change="applyFilters" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">All Sessions</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="filter_section" class="block text-sm font-medium text-gray-700 mb-1">Section</label>
                                <select id="filter_section" v-model="filterForm.section_id" @change="applyFilters" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">All Sections</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="filter_day" class="block text-sm font-medium text-gray-700 mb-1">Day</label>
                                <select id="filter_day" v-model="filterForm.day_of_week" @change="applyFilters" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">All Days</option>
                                    <option v-for="day in daysOfWeek" :key="day.value" :value="day.value">{{ day.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="filter_teacher" class="block text-sm font-medium text-gray-700 mb-1">Teacher</label>
                                <select id="filter_teacher" v-model="filterForm.teacher_id" @change="applyFilters" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">All Teachers</option>
                                    <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label for="filter_subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                                <select id="filter_subject" v-model="filterForm.subject_id" @change="applyFilters" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">All Subjects</option>
                                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4 mt-6">
                          <button type="button" @click="applyFilters" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl shadow-md transition duration-300 flex items-center">
                              <div v-html="getIcon('filter')"></div>
                              Apply Filters
                          </button>
                          <button type="button" @click="clearFilters" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-xl shadow-md transition duration-300 flex items-center">
                              <div v-html="getIcon('clear')"></div>
                              Clear Filters
                          </button>
                      </div>
                    </div>

                    <!-- Timetable Content -->
                    <div v-if="Object.values(groupedTimetable).every(arr => arr.length === 0)" class="text-center py-10 text-gray-500">
                        <p class="text-xl font-medium mb-2">No timetable entries found.</p>
                        <p>Try adjusting your filters or add a new timetable entry to get started.</p>
                    </div>

                    <div v-else>
                      <template v-for="day in daysOfWeek" :key="day.value">
                          <div v-if="groupedTimetable[day.value]?.length > 0" class="mb-8">
                              <div class="bg-gray-800 text-white text-center py-2 rounded-t-xl mb-4 font-bold tracking-wide">
                                  {{ day.label }}
                              </div>
                              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                  <div
                                      v-for="entry in groupedTimetable[day.value]"
                                      :key="entry.id"
                                      class="bg-white p-6 rounded-xl shadow-md border border-gray-200 hover:shadow-lg transition-shadow duration-300 flex flex-col justify-between"
                                  >
                                      <div>
                                          <div class="flex justify-between items-start mb-2">
                                              <span class="text-sm font-medium text-gray-500">{{ entry.start_time?.substring(0, 5) }} - {{ entry.end_time?.substring(0, 5) }}</span>
                                              <span
                                                  :class="{
                                                      'bg-green-100 text-green-800': entry.status === 0,
                                                      'bg-red-100 text-red-800': entry.status === 1,
                                                  }"
                                                  class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                                              >
                                                  {{ entry.status === 0 ? 'Active' : 'Inactive' }}
                                              </span>
                                          </div>
                                          <h4 class="text-lg font-bold text-gray-900">{{ entry.subject?.name || 'N/A' }}</h4>
                                          <p class="text-sm text-gray-700 mb-2">
                                              <span class="font-medium">Class:</span> {{ entry.className?.class_name || 'N/A' }} ({{ entry.section?.name || 'N/A' }})
                                          </p>
                                          <ul class="text-sm text-gray-600 space-y-1 mt-4 border-t pt-4">
                                              <li><span class="font-medium">Teacher:</span> {{ entry.teacher?.name || 'N/A' }}</li>
                                              <li><span class="font-medium">Room:</span> {{ entry.room?.name || 'N/A' }}</li>
                                          </ul>
                                      </div>
                                      <div class="flex justify-end space-x-2 mt-4 pt-4 border-t">
                                          <Link :href="route('timetable.edit', entry.id)" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-3 rounded-lg flex items-center text-xs">
                                              <div v-html="getIcon('edit')"></div>
                                              <span class="ml-1">Edit</span>
                                          </Link>
                                          <DangerButton @click="confirmDelete(entry)" class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-3 rounded-lg flex items-center text-xs">
                                              <div v-html="getIcon('del')"></div>
                                              <span class="ml-1">Delete</span>
                                          </DangerButton>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </template>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
