<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    exam: Object,
    cls: Object,
    section: Object,
    session: Object,
    room: Object,
    group: Object,
    eligibleStudentsData: Array,
    errors: Object,
    status: String,
});

// Initialize form with necessary IDs from props
const form = useForm({
    exam_id: props.exam?.id || '',
    class_id: props.cls?.id || '',
    section_id: props.section?.id || '',
    session_id: props.session?.id || '',
    room_id: props.room?.id || '',
    group_id: props.group?.id || '',
    assignments: props.eligibleStudentsData.map(student => ({
        student_id: student.student_id,
        seat_number: student.seat_number,
    })),
});

// Reactive state for temporary UI error display
const uiError = ref(null);
const showUiError = (message) => {
    uiError.value = message;
    setTimeout(() => {
        uiError.value = null;
    }, 4000);
};

// Check if all necessary context data is present
const isContextReady = computed(() => {
    return (
        form.exam_id &&
        form.class_id &&
        form.section_id &&
        form.session_id &&
        form.room_id &&
        form.group_id
    );
});

// Auto-assign seats
const autoAssignSeats = () => {
    if (!isContextReady.value) {
        showUiError('Assignment failed: Missing required context (Exam, Class, Section, Session, Room, or Group).');
        return;
    }
    router.post(route('class-section-seat-plan.auto-assign'), form.data(), {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['eligibleStudentsData', 'status'] });
        },
        onError: (errors) => {
            console.error('Auto-assign errors:', errors);
        },
    });
};

// Save manual assignments 
const submit = () => {
    if (!isContextReady.value) {
        showUiError('Save failed: Missing required context (Exam, Class, Section, Session, Room, or Group).');
        return;
    }
    form.post(route('class-section-seat-plan.store'), {
        onSuccess: () => {
            router.reload({ only: ['eligibleStudentsData', 'status'] });
        },
        onError: (errors) => {
            console.error('Manual assign errors:', errors);
        },
    });
};

// Reactive state for PDF loading
const isGeneratingPDF = ref(false);

// Handle PDF download with validation
const downloadPDF = () => {
    if (!isContextReady.value) {
        showUiError('PDF generation failed: Missing required context (Exam, Class, Section, Session, Room, or Group).');
        return;
    }
    const hasAssignments = props.eligibleStudentsData.some(student => student.seat_number);
    if (!hasAssignments) {
        showUiError('No seats are assigned yet. Please assign seats before generating the PDF.');
        return;
    }
    const queryParams = new URLSearchParams({
        exam_id: form.exam_id,
        class_id: form.class_id,
        section_id: form.section_id,
        session_id: form.session_id,
        room_id: form.room_id,
        group_id: form.group_id,
    }).toString();
    isGeneratingPDF.value = true;
    window.open(`/class-section-seat-plan/pdf?${queryParams}`, '_blank');
    setTimeout(() => {
        isGeneratingPDF.value = false;
    }, 1000);
};

// Map student data for seat overview
const assignedStudentsMap = computed(() => {
    const map = {};
    props.eligibleStudentsData.forEach(student => {
        if (student.seat_number && String(student.seat_number).trim() !== '') {
            map[student.seat_number] = student;
        }
    });
    return map;
});

// Generate array of all possible seat numbers
const allRoomSeats = computed(() => {
    const seats = [];
    if (props.room && props.room.capacity && props.room.capacity > 0) {
        for (let i = 1; i <= props.room.capacity; i++) {
            seats.push(i);
        }
    }
    return seats;
});

// Get error for specific student seat input
const getStudentSeatError = (index) => {
    return props.errors[`assignments.${index}.seat_number`] || null;
};

// Get general errors
const getGeneralErrors = computed(() => {
    return Object.keys(props.errors).filter(key => !key.startsWith('assignments.')).map(key => props.errors[key]);
});

// Reactive property for the current date display
const generatedOnDate = computed(() => {
    // Format the date as "Month Day, Year"
    return new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
});
</script>

<template>
    <Head :title="`Seat Plan for ${exam?.exam_name || 'N/A'} - Class ${cls?.class_name || 'N/A'} (${section?.name || 'N/A'})`" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Seat Plan for {{ exam?.exam_name || 'N/A' }} - Class {{ cls?.class_name || 'N/A' }} ({{ section?.name || 'N/A' }})
            </h2>
            <p class="text-sm text-gray-600">
                Session: {{ session?.name || 'N/A' }} | Room: {{ room ? room.name : 'N/A' }} (Capacity: {{ room ? room.capacity : 'N/A' }}) | Group: {{ group?.name || 'N/A' }}
            </p>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div v-if="uiError" class="alert alert-danger mb-4 transition-opacity duration-500 ease-in-out">
                        {{ uiError }}
                    </div>
                    <div v-if="!isContextReady" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-lg" role="alert">
                        <p class="font-bold">Assignment System Disabled</p>
                        <p class="text-sm">One or more required parameters are missing. Please ensure all context (Exam, Class, Section, Session, Room, and Group) is selected/passed correctly.</p>
                        <ul class="list-disc list-inside mt-2 text-xs">
                            <li v-if="!form.exam_id">Missing Exam ID</li>
                            <li v-if="!form.class_id">Missing Class ID</li>
                            <li v-if="!form.section_id">Missing Section ID</li>
                            <li v-if="!form.session_id">Missing Session ID</li>
                            <li v-if="!form.room_id">Missing Room ID</li>
                            <li v-if="!form.group_id">Missing Group ID</li>
                        </ul>
                    </div>
                    <div v-if="status" class="alert alert-success mb-4">{{ status }}</div>
                    <div v-if="getGeneralErrors.length > 0" class="alert alert-danger mb-4">
                        <p class="font-bold mb-1">Validation Errors:</p>
                        <ul class="list-disc list-inside">
                            <li v-for="(error, index) in getGeneralErrors" :key="index">{{ error }}</li>
                        </ul>
                    </div>
                    <form @submit.prevent="submit">
                        <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center">
                            <div class="mb-4 md:mb-0">
                                <button
                                    type="button"
                                    @click="autoAssignSeats"
                                    class="btn btn-secondary me-2"
                                    :disabled="!isContextReady"
                                >
                                    Auto Assign Seats (by Roll No.)
                                </button>
                                <button
                                    type="submit"
                                    class="btn btn-primary me-2"
                                    :disabled="!isContextReady"
                                >
                                    Save Manual Assign Seats
                                </button>
                                <button
                                    type="button"
                                    @click="downloadPDF"
                                    class="btn btn-info"
                                    :disabled="isGeneratingPDF || !isContextReady"
                                    aria-label="Download seat plan as PDF"
                                    :aria-busy="isGeneratingPDF"
                                >
                                    <span v-if="isGeneratingPDF">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Generating PDF...
                                    </span>
                                    <span v-else>Print PDF</span>
                                </button>
                            </div>
                            <div class="text-base font-semibold text-gray-700 bg-gray-50 p-2 rounded-lg">
                                Total Eligible Students: <span class="text-indigo-600">{{ eligibleStudentsData.length }}</span>
                            </div>
                        </div>
                        <h4 class="font-bold text-lg text-gray-800 mb-3 border-b pb-2">Student List & Manual Assignment</h4>
                        <div class="table-responsive mb-8 shadow-inner rounded-lg">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Roll No.</th>
                                        <th>Admission ID.</th>
                                        <th>Student Name</th>
                                        <th>Group</th>
                                        <th class="w-1/6">Assigned Seat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="eligibleStudentsData.length === 0">
                                        <td colspan="5" class="text-center text-gray-500 py-6">
                                            No eligible students found for this exam, class, section, and group.
                                        </td>
                                    </tr>
                                    <tr v-for="(student, index) in eligibleStudentsData" :key="student.student_id">
                                        <td>{{ student.roll_number }}</td>
                                        <td>{{ student.admission_number }}</td>
                                        <td>{{ student.name }}</td>
                                        <td>{{ student.group_name || 'N/A' }}</td>
                                        <td>
                                            <input
                                                type="number"
                                                v-model.number="form.assignments[index].seat_number"
                                                class="form-control"
                                                :class="{ 'is-invalid': getStudentSeatError(index) }"
                                                min="1"
                                                :max="room?.capacity"
                                                placeholder="Seat No."
                                            />
                                            <div v-if="getStudentSeatError(index)" class="invalid-feedback">
                                                {{ getStudentSeatError(index) }}
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </form>
                    <h4 class="font-bold text-lg text-gray-800 mb-3 border-b pb-2">Room Overview: {{ room ? room.name : 'N/A' }}</h4>
                    <p class="text-sm text-gray-600 mb-3">
                        Capacity: <span class="font-semibold">{{ room ? room.capacity : 'N/A' }}</span> seats
                    </p>
                    <div v-if="room && room.capacity > 0" class="seat-overview-grid p-4 border border-gray-200 rounded-xl bg-gray-50">
                        <div
                            v-for="seatNum in allRoomSeats"
                            :key="seatNum"
                            class="seat-cell"
                            :class="{
                                'seat-assigned': assignedStudentsMap[seatNum],
                                'seat-empty': !assignedStudentsMap[seatNum]
                            }"
                        >
                            <span class="seat-label">Seat {{ seatNum }}</span>
                            <div v-if="assignedStudentsMap[seatNum]" class="assigned-student">
                                <small class="font-medium">Roll: {{ assignedStudentsMap[seatNum].roll_number }}</small>
                                <small class="truncate">{{ assignedStudentsMap[seatNum].name.split(' ')[0] }}...</small>
                            </div>
                            <div v-else class="empty-placeholder">
                                <small>Available</small>
                            </div>
                        </div>
                    </div>
                    <div v-else class="alert alert-info">
                        Room capacity not defined or is 0. Cannot display seat overview. **If the room data is missing, please check your controller for passing the `room` prop.**
                    </div>
                </div>
                <div class="mt-8 py-4 px-6 bg-gray-50 border-t border-gray-200 text-center text-xs text-gray-500 rounded-b-lg shadow-inner">
                    <p>
                         All Rights Reserved. Biddaloy is a product of <a href="https://smithitbd.com/" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-medium transition-colors duration-150">Smith IT</a>
                    </p>
                </div>
                </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.btn {
    @apply px-4 py-2 rounded-lg text-white font-medium transition duration-150 ease-in-out shadow-md;
}
.btn-primary {
    @apply bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2;
}
.btn-secondary {
    @apply bg-gray-500 hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2;
}
.btn-info {
    @apply bg-cyan-600 hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2;
}
.alert {
    @apply p-4 rounded-lg;
}
.alert-success {
    @apply bg-green-100 text-green-700 border border-green-200;
}
.alert-danger {
    @apply bg-red-100 text-red-700 border border-red-200;
}
.alert-info {
    @apply bg-blue-100 text-blue-700 border border-blue-200;
}
.table-responsive {
    overflow-x: auto;
}
.table {
    width: 100%;
    border-collapse: collapse;
}
.table th, .table td {
    @apply p-3 border-b border-gray-200 text-sm;
}
.table th {
    @apply text-left font-semibold text-gray-600 bg-gray-50;
}
.form-control {
    @apply border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-full max-w-[100px] text-center;
}
.is-invalid {
    @apply border-red-500 focus:border-red-500 focus:ring-red-500;
}
.invalid-feedback {
    @apply text-red-600 text-xs mt-1;
}
.seat-overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
}
.seat-cell {
    @apply p-3 rounded-xl text-center shadow-lg flex flex-col justify-between items-center h-24 border-2 transition-all duration-300;
    font-size: 0.75rem;
}
.seat-label {
    @apply font-extrabold mb-1 text-gray-700;
}
.assigned-student {
    @apply text-xs text-gray-800 leading-tight w-full;
}
.assigned-student small {
    display: block;
    line-height: 1.2;
}
.empty-placeholder {
    @apply text-gray-500 text-xs;
}
.seat-assigned {
    @apply bg-emerald-100 border-emerald-400 hover:bg-emerald-200;
}
.seat-empty {
    @apply bg-white border-gray-300 hover:bg-gray-100;
}
</style>