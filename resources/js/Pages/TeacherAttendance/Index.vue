<script setup>
import { ref, watch, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps({
    classes: Array,
    sessions: Array,
    sections: Array,
    groups: Array,
    students: Array,
    teacher: Object,
    selectedSessionId: [Number, String],
    selectedSectionId: [Number, String],
    selectedGroupId: [Number, String],
    selectedAttendanceDate: String,
})

const search = ref('')

// Filter Form
const filterForm = useForm({
    session_id: props.selectedSessionId || '',
    section_id: props.selectedSectionId || '',
    group_id: props.selectedGroupId || '',
    attendance_date: props.selectedAttendanceDate || new Date().toISOString().slice(0, 10),
})

// Attendance Form
const attendanceForm = useForm({
    session_id: filterForm.session_id,
    section_id: filterForm.section_id,
    group_id: filterForm.group_id,
    attendance_date: filterForm.attendance_date,
    attendance_data: [], // Will be filled from props.students
})

// Keep attendance form in sync with filters
watch([() => filterForm.session_id, () => filterForm.section_id, () => filterForm.group_id, () => filterForm.attendance_date], () => {
    attendanceForm.session_id = filterForm.session_id
    attendanceForm.section_id = filterForm.section_id
    attendanceForm.group_id = filterForm.group_id
    attendanceForm.attendance_date = filterForm.attendance_date
})

// Rebuild attendance_data whenever students change (after filter)
watch(() => props.students, (newStudents) => {
    attendanceForm.attendance_data = newStudents.map(student => ({
        student_id: student.id,
        status: student.attendance_status || 'present', // respects already saved attendance
    }))
}, { immediate: true })

// Safe getters/setters using student_id (works perfectly with search/filter)
const getStatus = (studentId) => {
    const item = attendanceForm.attendance_data.find(i => i.student_id === studentId)
    return item ? item.status : 'present'
}

const setStatus = (studentId, status) => {
    const item = attendanceForm.attendance_data.find(i => i.student_id === studentId)
    if (item) item.status = status
}

// Search students (by name or roll)
const filteredStudents = computed(() => {
    if (!search.value.trim()) return props.students

    const term = search.value.toLowerCase()
    return props.students.filter(student =>
        student.name.toLowerCase().includes(term) ||
        student.roll_number.toString().includes(term)
    )
})

// Can submit only if all filters filled and students exist
const canSubmit = computed(() => {
    return filterForm.session_id &&
           filterForm.section_id &&
           filterForm.group_id &&
           filterForm.attendance_date &&
           props.students.length > 0 &&
           attendanceForm.attendance_data.length > 0
})

// Apply Filters → Reload page with query params (Called automatically by the watch below)
const applyFilters = () => {
    attendanceForm.clearErrors('attendance_date')

    router.get(route('teacherattendance.index'), {
        session_id: filterForm.session_id || undefined,
        section_id: filterForm.section_id || undefined,
        group_id: filterForm.group_id || undefined,
        attendance_date: filterForm.attendance_date || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true, // Use replace to avoid filling browser history
    })
}

// 🎯 NEW: INSTANT FILTERING WATCHER
watch([
    () => filterForm.session_id,
    () => filterForm.section_id,
    () => filterForm.group_id,
    () => filterForm.attendance_date
], () => {
    // Only trigger the server request if ALL four required filters are selected
    if (filterForm.session_id && filterForm.section_id && filterForm.group_id && filterForm.attendance_date) {
        applyFilters();
    }
}, { immediate: false, deep: true })


// Submit Attendance
const submitAttendance = () => {
    attendanceForm.post(route('teacherattendance.store'), {
        onSuccess: () => {
            // Flash message handled by Laravel/Inertia
        },
        onError: () => {
            // Errors will show automatically
        }
    })
}
</script>

<template>
    <Head title="Take Student Attendance" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Take Student Attendance
                </h2>
                <div class="mt-2 sm:mt-0 text-sm text-gray-600">
                    <strong>{{ teacher.name }}</strong> — Class Teacher of
                    <span class="font-bold text-indigo-600">
                        {{ teacher.assigned_class }}
                        {{ teacher.assigned_section ? ' - ' + teacher.assigned_section : '' }}
                        {{ teacher.assigned_group ? ` (${teacher.assigned_group})` : '' }}
                    </span>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

                <div v-if="$page.props.flash?.success"
                    class="fixed top-4 left-1/2 -translate-x-1/2 z-50 px-8 py-5 rounded-2xl shadow-2xl bg-gradient-to-r from-green-500 to-emerald-600 text-white font-bold text-lg animate-pulse">
                    {{ $page.props.flash.success }}
                </div>

                <div v-if="$page.props.flash?.error"
                    class="fixed top-4 left-1/2 -translate-x-1/2 z-50 px-8 py-5 rounded-2xl shadow-2xl bg-gradient-to-r from-red-500 to-rose-600 text-white font-bold text-lg animate-bounce">
                    {{ $page.props.flash.error }}
                </div>

                <div class="bg-gradient-to-r from-indigo-600 to-purple-700 text-white p-8 rounded-3xl shadow-2xl text-center">
                    <h3 class="text-2xl font-bold">You are Class Teacher of</h3>
                    <p class="text-5xl font-extrabold mt-3">
                        {{ props.classes[0]?.class_name }}
                        <span v-if="teacher.assigned_section" class="text-3xl"> — {{ teacher.assigned_section }}</span>
                        <span v-if="teacher.assigned_group" class="text-2xl"> ({{ teacher.assigned_group }})</span>
                    </p>
                </div>

                <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-800 mb-8">Select Filters</h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <InputLabel value="Session" />
                            <select v-model="filterForm.session_id"
                                class="mt-1 block w-full rounded-xl border-gray-300 focus:ring-4 focus:ring-indigo-300 focus:border-indigo-500">
                                <option value="">Select Session</option>
                                <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <InputError :message="filterForm.errors.session_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel value="Section" />
                            <select v-model="filterForm.section_id"
                                class="mt-1 block w-full rounded-xl border-gray-300 focus:ring-4 focus:ring-indigo-300">
                                <option value="">Select Section</option>
                                <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                            </select>
                            <InputError :message="filterForm.errors.section_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel value="Group" />
                            <select v-model="filterForm.group_id"
                                class="mt-1 block w-full rounded-xl border-gray-300 focus:ring-4 focus:ring-indigo-300">
                                <option value="">Select Group</option>
                                <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                            </select>
                            <InputError :message="filterForm.errors.group_id" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel value="Date" />
                            <TextInput type="date" v-model="filterForm.attendance_date"
                                class="mt-1 block w-full rounded-xl" />
                            <InputError :message="attendanceForm.errors.attendance_date" class="mt-2" />
                        </div>
                    </div>

                    </div>
                
                <div v-if="props.students.length > 0"
                    class="bg-white rounded-3xl shadow-2xl border border-gray-200 overflow-hidden">

                    <div class="p-6 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <h3 class="text-2xl font-bold text-gray-800">
                            Student Attendance ({{ props.students.length }})
                        </h3>
                        <div class="flex flex-col sm:flex-row gap-3">
                            <TextInput v-model="search" placeholder="Search by name or roll..."
                                class="w-full sm:w-64" />
                            
                            </div>
                    </div>

                    <form @submit.prevent="submitAttendance">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-100 text-xs uppercase font-bold text-gray-600">
                                    <tr>
                                        <th class="px-6 py-4 text-left">Roll</th>
                                        <th class="px-6 py-4 text-left">Name</th>
                                        <th class="px-6 py-4 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="student in filteredStudents" :key="student.id"
                                        class="hover:bg-gray-50 transition-all duration-200">
                                        <td class="px-6 py-5 font-medium text-gray-900">
                                            {{ student.roll_number }}
                                        </td>
                                        <td class="px-6 py-5 text-gray-800 font-medium">
                                            {{ student.name }}
                                        </td>
                                        <td class="px-6 py-5">
                                            <div class="flex justify-center gap-8">
                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio"
                                                            :name="'status_' + student.id"
                                                            :checked="getStatus(student.id) === 'present'"
                                                            @change="setStatus(student.id, 'present')"
                                                            class="w-5 h-5 text-green-600 focus:ring-green-500">
                                                    <span class="text-green-700 font-semibold">Present</span>
                                                </label>

                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio"
                                                            :name="'status_' + student.id"
                                                            :checked="getStatus(student.id) === 'absent'"
                                                            @change="setStatus(student.id, 'absent')"
                                                            class="w-5 h-5 text-red-600 focus:ring-red-500">
                                                    <span class="text-red-700 font-semibold">Absent</span>
                                                </label>

                                                <label class="flex items-center gap-2 cursor-pointer">
                                                    <input type="radio"
                                                            :name="'status_' + student.id"
                                                            :checked="getStatus(student.id) === 'late'"
                                                            @change="setStatus(student.id, 'late')"
                                                            class="w-5 h-5 text-yellow-600 focus:ring-yellow-500">
                                                    <span class="text-yellow-700 font-semibold">Late</span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="p-6 bg-gray-50 border-t border-gray-200">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div v-if="attendanceForm.hasErrors" class="text-red-600">
                                    <p class="font-semibold">Please fix the following errors:</p>
                                    <ul class="list-disc list-inside">
                                        <li v-for="(error, field) in attendanceForm.errors" :key="field">
                                            {{ error }}
                                        </li>
                                    </ul>
                                </div>

                                <PrimaryButton type="submit"
                                    :disabled="!canSubmit || attendanceForm.processing"
                                    class="px-12 py-4 text-xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700">
                                    {{ attendanceForm.processing ? 'Saving Attendance...' : 'Save Attendance' }}
                                </PrimaryButton>
                            </div>
                        </div>
                    </form>
                </div>

                <div v-else-if="filterForm.session_id && filterForm.section_id && filterForm.group_id && filterForm.attendance_date"
                    class="bg-yellow-50 border-2 border-yellow-400 rounded-3xl p-12 text-center">
                    <p class="text-2xl font-bold text-yellow-800">
                        No students found for the selected criteria.
                    </p>
                </div>

                <div v-else class="bg-blue-50 border-2 border-blue-300 rounded-3xl p-12 text-center">
                    <p class="text-2xl font-semibold text-blue-800">
                        Please select **Session**, **Section**, **Group**, and **Date** to load students automatically.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
input[type="radio"] {
    transform: scale(1.4);
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1);
}

.animate-bounce {
    animation: bounce 1s infinite;
}
</style>