<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue'; // Make sure 'computed' is imported

const props = defineProps({
    examSchedule: Object,
    room: Object,
    eligibleStudentsData: Array, // Prepared student data with current assignments
    errors: Object, // Inertia validation errors
    status: String, // Success/error status messages
});

// Initialize form with existing assignments
const form = useForm({
    assignments: props.eligibleStudentsData.map(student => ({
        student_id: student.student_id,
        seat_number: student.seat_number,
    })),
});

// Auto-assign 
const autoAssignSeats = () => {
    router.post(route('exam-seat-plan.auto-assign', props.examSchedule.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['eligibleStudentsData', 'status'] }); // Reload updated data
        },
        onError: (errors) => {
            console.error('Auto-assign errors:', errors);
        },
    });
};

// Save manual assignments action
const submit = () => {
    form.post(route('exam-seat-plan.store', props.examSchedule.id), {
        onSuccess: () => {
            // The show method will automatically reload eligibleStudentsData
            router.reload({ only: ['eligibleStudentsData', 'status'] });
        },
        onError: (errors) => {
            console.error('Manual assign errors:', errors);
        }
    });
};

// Map student data by ID for easy lookup in the "Assigned Seats" display
const assignedStudentsMap = computed(() => {
    const map = {};
    props.eligibleStudentsData.forEach(student => {
        if (student.seat_number) {
            map[student.seat_number] = student;
        }
    });
    return map;
});

// Generate an array of all possible seat numbers in the room
const allRoomSeats = computed(() => {
    const seats = [];
    if (props.room && props.room.capacity) {
        for (let i = 1; i <= props.room.capacity; i++) {
            seats.push(i);
        }
    }
    return seats;
});

// Helper to get error for a specific student's seat_number input
const getStudentSeatError = (index) => {
    return props.errors[`assignments.${index}.seat_number`] || null;
};

// Helper to get general errors not tied to a specific field
const getGeneralErrors = computed(() => {
    return Object.keys(props.errors).filter(key => !key.startsWith('assignments.')).map(key => props.errors[key]);
});

// --- NEW FUNCTION FOR BST TIME FORMATTING ---
const formatTimeToBST = (timeString, dateString) => {
    if (!timeString || !dateString) return 'N/A';

    try {
        // Combine date and time to create a full datetime string for accurate parsing
        // Assumes timeString is in HH:MM:SS format and dateString is 'YYYY-MM-DD'
        const combinedDateTime = `${dateString}T${timeString}`;
        const date = new Date(combinedDateTime);

        // Check for valid date parsing
        if (isNaN(date.getTime())) {
            console.error("Invalid date or time string provided to formatTimeToBST:", combinedDateTime);
            return 'Invalid Time';
        }

        // Use toLocaleTimeString for proper timezone formatting
        // 'en-BD' for Bangladesh locale, timeZone: 'Asia/Dhaka' for BST
        return date.toLocaleTimeString('en-BD', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true, // Use AM/PM format
            timeZone: 'Asia/Dhaka' // Bangladesh Standard Time (BST)
        });
    } catch (e) {
        console.error("Error formatting time to BST:", e);
        return 'N/A';
    }
};

</script>

<template>
    <Head :title="`Seat Plan for ${examSchedule.exam.exam_name || 'N/A'}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Seat Plan for {{ examSchedule.exam.exam_name || 'N/A' }} ({{ examSchedule.subject.name || 'N/A' }})
            </h2>
            <p class="text-sm text-gray-600">
                Class: {{ examSchedule.class_name.class_name }} {{ examSchedule.section.name }} |
                Room: {{ room ? room.name : 'N/A' }} (Capacity: {{ room ? room.capacity : 'N/A' }}) |
                Date: {{ new Date(examSchedule.exam_date).toLocaleDateString() }} |
                Time:
                **{{ formatTimeToBST(examSchedule.start_time, examSchedule.exam_date) }} - {{ formatTimeToBST(examSchedule.end_time, examSchedule.exam_date) }}**
            </p>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Manage Seat Assignments</h3>

                    <div v-if="status" class="alert alert-success mb-4">{{ status }}</div>
                    <div v-if="getGeneralErrors.length > 0" class="alert alert-danger mb-4">
                        <ul>
                            <li v-for="(error, index) in getGeneralErrors" :key="index">{{ error }}</li>
                        </ul>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="mb-4 d-flex justify-content-between align-items-center">
                            <div>
                                <button type="button" @click="autoAssignSeats" class="btn btn-secondary me-2">
                                    Auto Assign Seats (by Roll No.)
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    Save Manual Assign Seats
                                </button>
                            </div>
                            <div class="text-sm text-gray-600">
                                Total Eligible Students: {{ eligibleStudentsData.length }}
                            </div>
                        </div>

                        <h4 class="font-medium text-gray-800 mb-3">Student List & Manual Assignment</h4>
                        <div class="table-responsive mb-5">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Roll No.</th>
                                        <th>Admission No.</th>
                                        <th>Student Name</th>
                                        <th>Assigned Seat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-if="eligibleStudentsData.length === 0">
                                        <td colspan="4" class="text-center text-gray-500 py-4">No eligible students found for this exam schedule.</td>
                                    </tr>
                                    <tr v-for="(student, index) in eligibleStudentsData" :key="student.student_id">
                                        <td>{{ student.roll_number }}</td>
                                        <td>{{ student.admission_number }}</td>
                                        <td>{{ student.name }}</td>
                                        <td>
                                            <input
                                                type="number"
                                                v-model="form.assignments[index].seat_number"
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

                    <h4 class="font-medium text-gray-800 mb-3">Room Overview: {{ room ? room.name : 'N/A' }}</h4>
                    <p class="text-sm text-gray-600 mb-3">
                        Capacity: {{ room ? room.capacity : 'N/A' }} seats
                    </p>

                    <div v-if="room && room.capacity > 0" class="seat-overview-grid p-3 border rounded">
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
                                <small>{{ assignedStudentsMap[seatNum].roll_number }}</small>
                                <small>{{ assignedStudentsMap[seatNum].name.split(' ')[0] }}</small>
                            </div>
                            <div v-else class="empty-placeholder">
                                <small>Empty</small>
                            </div>
                        </div>
                    </div>
                    <div v-else class="alert alert-info">
                        Room capacity not defined or is 0. Cannot display seat overview.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Basic styling for the seat grid */
.seat-overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); /* Responsive grid */
    gap: 8px; /* Space between cells */
}

.seat-cell {
    border: 1px solid #ccc;
    padding: 10px;
    min-height: 80px; /* Minimum height for cells */
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    border-radius: 4px;
    font-size: 0.8rem;
    position: relative;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: all 0.2s ease-in-out;
}

.seat-label {
    font-weight: bold;
    color: #555;
    position: absolute;
    top: 5px;
    left: 5px;
    font-size: 0.7em;
}

.assigned-student, .empty-placeholder {
    margin-top: 5px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-grow: 1;
}

.assigned-student small, .empty-placeholder small {
    display: block;
    line-height: 1.2;
    color: #333;
    font-size: 0.75em;
}

/* Colors for seat status */
.seat-empty {
    background-color: #e9ecef; /* Light gray for empty */
    border-color: #adb5bd;
}

.seat-assigned {
    background-color: #d4edda; /* Light green for assigned */
    border-color: #28a745;
    color: #155724;
    font-weight: 500;
}

/* Dark mode adjustments (assuming your global dark mode variables are set) */
html.dark .seat-cell {
    border-color: var(--color-border);
    background-color: var(--color-bg-primary);
    color: var(--color-text-secondary);
}

html.dark .seat-label {
    color: var(--color-text-tertiary);
}

html.dark .assigned-student small, html.dark .empty-placeholder small {
    color: var(--color-text-primary);
}

html.dark .seat-empty {
    background-color: var(--color-bg-secondary);
    border-color: var(--color-border);
}

html.dark .seat-assigned {
    background-color: #2a4b31; /* Darker green */
    border-color: #3a6b41;
    color: #8cdda4;
}
</style>