<script setup>
import { router, useForm, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { ref, reactive } from 'vue';

const props = defineProps({
    teachers: Array,
    currentDate: String,
});

// 1. Create a LOCAL reactive copy of the teachers data (IMPORTANT for v-model)
const attendanceList = reactive(
    props.teachers.map(t => ({
        ...t,
        in_time: t.in_time || '',
        out_time: t.out_time || '',
        note: t.note || '', // Include note field
        errors: {},
        // State for visual success feedback on the row
        saved: false
    }))
);


// State for the date filter (used for viewing attendance on the current page)
const dateFilter = ref(props.currentDate);

// ✨ State for the date range (for PDF reporting), defaulting to the current view date
const rangeStartDate = ref(props.currentDate);
const rangeEndDate = ref(props.currentDate);

// State to hold the global date validation error
const dateError = ref(null); 

// Function to handle the change of date filter (updates the attendance list below)
const filterByDate = () => {
    router.get(route('attendance.teachers.index'), { date: dateFilter.value }, {
        preserveState: true,
        preserveScroll: true,
    });
};

// UX LOGIC: Clear time fields if status is not time-based
const handleStatusChange = (teacherData) => {
    if (['Absent', 'Leave'].includes(teacherData.status)) {
        teacherData.in_time = '';
        teacherData.out_time = '';
    }
    delete teacherData.errors.in_time;
    delete teacherData.errors.out_time;
};


// Form state for handling individual attendance submission
const form = useForm({
    teacher_id: null,
    date: props.currentDate,
    status: 'Absent',
    in_time: null,
    out_time: null,
    note: null,
});

// Function to submit attendance for a specific teacher
const submitAttendance = (teacherData) => {
    // Clear previous errors, success state, and the global date error
    teacherData.errors = {};
    teacherData.saved = false; 
    dateError.value = null; 

    // Map the data
    form.teacher_id = teacherData.id;
    form.date = props.currentDate;
    form.status = teacherData.status;
    form.in_time = teacherData.in_time || null;
    form.out_time = teacherData.out_time || null;
    form.note = teacherData.note || null;

    if (form.processing) {
        return;
    }

    form.post(route('attendance.teachers.store'), {
        preserveScroll: true,
        onSuccess: () => {
             teacherData.saved = true;
             dateError.value = null; 
             setTimeout(() => {
                 teacherData.saved = false;
             }, 2000);
        },
        onError: (errors) => {
             if (errors.date) {
                 dateError.value = Array.isArray(errors.date) ? errors.date[0] : errors.date;
                 return;
             }
             teacherData.errors = errors;
        }
    });
};

// ✨ Function to generate the DATE RANGE PDF
const printRangePdfReport = () => {
    if (!rangeStartDate.value || !rangeEndDate.value) {
        alert('Please select both a start date and an end date for the report.');
        return;
    }
    
    // Call the dedicated route 'attendance.teachers.print.range'
    window.open(route('attendance.teachers.print.range', { 
        start_date: rangeStartDate.value, 
        end_date: rangeEndDate.value 
    }), '_blank');
};

// Helper function for visual status badge (omitted for brevity)
const getStatusClasses = (status) => {
    switch (status) {
        case 'Present':
            return 'bg-green-600 text-white border-green-700';
        case 'Absent':
            return 'bg-red-500 text-white border-red-600';
        case 'Leave':
            return 'bg-yellow-500 text-white border-yellow-600';
        case 'Half Day':
            return 'bg-blue-500 text-white border-blue-600';
        default:
            return 'bg-gray-400 text-white border-gray-500';
    }
}
</script>

<template>
    <Head title="Teacher Attendance" />

    <AuthenticatedLayout>
    
    <div class="py-6 sm:py-10 bg-gray-100 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <header class="mb-8 flex flex-col sm:flex-row sm:justify-between sm:items-center p-4 sm:p-0">
                <h1 class="text-4xl font-extrabold text-gray-900 leading-tight">
                    <span class="text-indigo-600">Attendance</span> Tracker 🗓️
                </h1>
                
                <div class="mt-4 sm:mt-0 flex flex-col items-end">
                    
                    <div class="flex flex-col md:flex-row items-end md:items-center space-y-3 md:space-y-0 md:space-x-4 bg-white p-4 rounded-xl shadow-lg border-2 border-red-100">
                        <div class="flex items-center space-x-3">
                            <label class="text-lg font-semibold text-gray-700 whitespace-nowrap">Report Range:</label>
                            <input type="date" v-model="rangeStartDate" class="border-gray-300 rounded-lg p-2 text-sm w-32 focus:border-red-500 focus:ring-red-500" />
                            <span class="text-gray-500">to</span>
                            <input type="date" v-model="rangeEndDate" class="border-gray-300 rounded-lg p-2 text-sm w-32 focus:border-red-500 focus:ring-red-500" />
                        </div>
                        
                        <button 
                            @click="printRangePdfReport"
                            class="w-full md:w-auto inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-full font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-800 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150 shadow-md whitespace-nowrap"
                        >
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2v5H3v-5h2M17 9v4m-2 0h4m-2-4v4m-3 0h4m-2-4v4M7 7h10v10H7z"></path></svg>
                            **Print Range PDF**
                        </button>
                    </div>

                    <div class="mt-4 flex items-center space-x-3 bg-white p-3 rounded-xl shadow-lg border border-indigo-100">
                        <label for="date-filter" class="text-lg font-semibold text-gray-700 whitespace-nowrap">View Date:</label>
                        <input 
                            id="date-filter"
                            type="date"
                            v-model="dateFilter"
                            @change="filterByDate"
                            class="border-indigo-300 focus:border-indigo-600 focus:ring-indigo-600 rounded-lg shadow-sm p-2 text-base transition duration-150 ease-in-out"
                        />
                    </div>
                    
                    <p v-if="dateError" class="text-red-600 text-sm font-medium mt-2 p-2 bg-red-100 rounded-md shadow-sm border border-red-500">
                                {{ dateError }}
                    </p>
                </div>
            </header>
            
            <p class="text-xl font-medium text-gray-600 mb-6 border-b pb-2">
                Entries for **{{ currentDate }}** ({{ attendanceList.length }} Teachers)
            </p>

            <div class="hidden lg:grid grid-cols-12 gap-4 bg-indigo-600 text-white p-4 rounded-t-xl shadow-md font-bold text-sm uppercase tracking-wider">
                <div class="col-span-3">Teacher Name & ID</div>
                <div class="col-span-2">Status</div> 
                <div class="col-span-3">In Time / Out Time (Optional)</div> 
                <div class="col-span-2">Note (Optional)</div> <div class="col-span-2 text-center">Action</div> </div>

            <div class="space-y-4">
                <div 
                    v-for="teacher in attendanceList" 
                    :key="teacher.id" 
                    :class="{
                        'lg:grid lg:grid-cols-12 lg:gap-4 p-4 lg:p-6 bg-white rounded-xl shadow-lg border-l-4 transition-all duration-300 ease-in-out': true,
                        // Error State
                        'border-red-500 bg-red-50': Object.keys(teacher.errors).length,
                        // Success State (Temporary Flash)
                        'border-green-500 bg-green-100/50': teacher.saved && !Object.keys(teacher.errors).length,
                        // Default State
                        'border-transparent hover:shadow-xl': !Object.keys(teacher.errors).length && !teacher.saved
                    }"
                >
                    <div class="col-span-3 mb-2 lg:mb-0">
                        <div class="text-lg font-bold text-gray-800">{{ teacher.name }}</div>
                        <div class="text-sm text-gray-500">ID: {{ teacher.joining_number }}</div>
                    </div>

                    <div class="col-span-2 mb-3 lg:mb-0">
                        <label class="block lg:hidden text-xs font-semibold uppercase text-gray-500 mb-1">Status</label>
                        <div class="flex flex-wrap gap-2 items-center">
                            <label class="inline-flex items-center text-sm font-medium cursor-pointer" :class="{'text-red-600': teacher.errors.status}">
                                <input type="radio" :name="`status-${teacher.id}`" value="Present" v-model="teacher.status" @change="handleStatusChange(teacher)" class="form-radio h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500">
                                <span class="ml-2 text-gray-700">Present</span>
                            </label>
                            <label class="inline-flex items-center text-sm font-medium cursor-pointer" :class="{'text-red-600': teacher.errors.status}">
                                <input type="radio" :name="`status-${teacher.id}`" value="Absent" v-model="teacher.status" @change="handleStatusChange(teacher)" class="form-radio h-4 w-4 text-red-600 border-gray-300 focus:ring-red-500">
                                <span class="ml-2 text-gray-700">Absent</span>
                            </label>
                            <label class="inline-flex items-center text-sm font-medium cursor-pointer" :class="{'text-red-600': teacher.errors.status}">
                                <input type="radio" :name="`status-${teacher.id}`" value="Leave" v-model="teacher.status" @change="handleStatusChange(teacher)" class="form-radio h-4 w-4 text-yellow-600 border-gray-300 focus:ring-yellow-500">
                                <span class="ml-2 text-gray-700">Leave</span>
                            </label>
                            <label class="inline-flex items-center text-sm font-medium cursor-pointer" :class="{'text-red-600': teacher.errors.status}">
                                <input type="radio" :name="`status-${teacher.id}`" value="Half Day" v-model="teacher.status" @change="handleStatusChange(teacher)" class="form-radio h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                <span class="ml-2 text-gray-700">Half Day</span>
                            </label>
                        </div>
                        <p v-if="teacher.errors.status" class="text-red-600 text-xs italic mt-1">{{ teacher.errors.status }}</p>
                    </div>

                    <div class="col-span-3 mb-4 lg:mb-0"> 
                        <label class="block lg:hidden text-xs font-semibold uppercase text-gray-500 mb-1">Time Logs</label>
                        <div class="flex flex-col space-y-2">
                            <div class="flex space-x-3">
                                <input type="time" v-model="teacher.in_time" :disabled="['Absent', 'Leave'].includes(teacher.status)" class="border-gray-300 rounded-lg shadow-sm p-2 w-full text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-200 disabled:opacity-75 transition-colors" :class="{'border-red-500': teacher.errors.in_time}"/>
                                <input type="time" v-model="teacher.out_time" :disabled="['Absent', 'Leave'].includes(teacher.status)" class="border-gray-300 rounded-lg shadow-sm p-2 w-full text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-200 disabled:opacity-75 transition-colors" :class="{'border-red-500': teacher.errors.out_time}"/>
                            </div>
                            <p v-if="teacher.errors.in_time" class="text-red-600 text-xs italic">In Time: {{ teacher.errors.in_time }}</p>
                            <p v-if="teacher.errors.out_time" class="text-red-600 text-xs italic">Out Time: {{ teacher.errors.out_time }}</p>
                        </div>
                    </div>

                    <div class="col-span-2 mb-4 lg:mb-0">
                         <label class="block lg:hidden text-xs font-semibold uppercase text-gray-500 mb-1">Note / Comment</label>
                         <textarea
                            v-model="teacher.note"
                            rows="2"
                            placeholder="Reason for absence or late arrival..."
                            class="w-full border-gray-300 rounded-lg shadow-sm p-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"
                            :class="{'border-red-500': teacher.errors.note}"
                         ></textarea>
                         <p v-if="teacher.errors.note" class="text-red-600 text-xs italic mt-1">{{ teacher.errors.note }}</p>
                    </div>

                    <div class="col-span-2 flex justify-end lg:justify-center items-center">
                        <button 
                            @click="submitAttendance(teacher)"
                            :disabled="form.processing"
                            class="w-full lg:w-auto inline-flex items-center justify-center px-6 py-2 border border-transparent text-sm font-medium rounded-full shadow-md text-white transition duration-200 ease-in-out disabled:opacity-60 disabled:cursor-not-allowed"
                            :class="{
                                'bg-green-500 hover:bg-green-600': teacher.saved,
                                'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500': !teacher.saved
                            }"
                        >
                            <svg v-if="form.processing && form.teacher_id === teacher.id" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span v-else-if="teacher.saved">Saved! ✅</span>
                            <span v-else>{{ teacher.attendance_id ? 'Update' : 'Mark' }}</span>
                        </button>
                    </div>
                </div>
                
                <div v-if="attendanceList.length === 0" class="p-8 bg-white rounded-xl shadow-lg text-center">
                    <p class="text-xl text-gray-500 font-medium">
                        No teachers found for **{{ currentDate }}**. Check your filters or teacher roster.
                    </p>
                </div>
            </div>
        </div>
    </div>

    </AuthenticatedLayout>
</template>