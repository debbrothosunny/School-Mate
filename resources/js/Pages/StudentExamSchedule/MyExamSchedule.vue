<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';

const props = defineProps({
    examSchedules: Array,
    studentName: String,
    flash: Object,
});

const flash = computed(() => usePage().props.flash || {});

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

const formatTime = (timeString) => {
    if (!timeString) return '';
    try {
        // This is robust for HH:MM:SS or HH:MM
        const [hours, minutes, seconds] = timeString.split(':');
        const dummyDate = new Date();
        dummyDate.setHours(parseInt(hours, 10), parseInt(minutes, 10), parseInt(seconds || 0, 10));
        return dummyDate.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
    } catch (e) {
        console.error("Error formatting time:", timeString, e);
        return timeString;
    }
};

// New function to format the day of the week
const formatDayOfWeek = (dateString) => {
    if (!dateString) return '';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { weekday: 'long' }); // e.g., "Thursday"
    } catch (e) {
        console.error("Error formatting day of week:", dateString, e);
        return '';
    }
};
</script>

<template>
    <Head :title="`${studentName}'s Exam Schedule`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ studentName }}'s Exam Schedule
            </h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <h3 class="card-title h5 mb-4">Your Upcoming Exam Schedule</h3>

                    <div v-if="examSchedules.length > 0">
                        <div v-for="schedule in examSchedules" :key="schedule.id" class="mb-4 p-3 border rounded bg-light">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <h5 class="mb-1 text-primary">
                                        <strong>{{ schedule.exam_name }} - {{ schedule.subject_name }}</strong>
                                    </h5>
                                    <p class="mb-1 text-muted">Class: {{ schedule.class_name }} | Section: {{ schedule.section_name }}</p>
                                    <p class="mb-1">
                                        <i class="fas fa-calendar-alt me-2"></i>
                                        <strong>Date:</strong> {{ schedule.exam_date }} ({{ formatDayOfWeek(schedule.exam_date) }})
                                    </p>
                                    <p class="mb-0">
                                        <i class="fas fa-clock me-2"></i>
                                        <strong>Time:</strong> {{ formatTime(schedule.start_time) }} - {{ formatTime(schedule.end_time) }}
                                    </p>
                                </div>
                                <div class="col-md-5 text-end">
                                    <p class="mb-1">
                                        <i class="fas fa-chalkboard-teacher me-2"></i>
                                        <strong>Invigilator:</strong> {{ schedule.teacher_name }}
                                    </p>
                                    <p class="mb-1">
                                        <i class="fas fa-door-open me-2"></i>
                                        <strong>Room:</strong> <span class="badge bg-info text-dark fs-6">{{ schedule.room_name }}</span>
                                    </p>
                                    <p class="mb-0 mt-2" v-if="schedule.seat_number && schedule.seat_number !== 'N/A (No specific seat plan)'">
                                        <i class="fas fa-chair me-2"></i>
                                        <strong>Your Seat:</strong>
                                        <span class="badge bg-primary text-light fs-5">{{ schedule.seat_number }}</span>
                                    </p>
                                    <p class="mb-0 mt-2 text-muted fst-italic" v-else>
                                        <i class="fas fa-info-circle me-2"></i>
                                        Seat not specifically assigned.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="alert alert-info text-center py-4" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        No upcoming exam schedules found for you.
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.badge {
    padding: 0.5em 0.8em;
    font-weight: 600;
}
.text-muted.fst-italic {
    font-size: 0.9em;
}
</style>