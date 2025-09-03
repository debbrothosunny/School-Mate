<script setup>
// ... existing script setup
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    timeSlots: Array,
    flash: Object,
});

const flash = computed(() => usePage().props.flash);

const deleteTimeSlot = (id) => {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('class-time-slots.destroy', id), {
                onSuccess: () => {
                    Swal.fire(
                        'Deleted!',
                        'The time slot has been deleted.',
                        'success'
                    );
                },
                onError: (errors) => {
                    Swal.fire(
                        'Error!',
                        'Failed to delete the time slot.',
                        'error'
                    );
                    console.error('Delete Error:', errors);
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Class Time Slots" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Class Time Slots</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="card-title h5 mb-0">List of Time Slots</h3>
                        <Link :href="route('class-time-slots.create')" class="btn btn-primary btn-sm rounded">
                            Create New Time Slot
                        </Link>
                    </div>

                    <div v-if="timeSlots.length > 0" class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(slot, index) in timeSlots" :key="slot.id">
                                    <td>{{ index + 1 }}</td>
                                    <td>{{ slot.name }}</td>
                                    <td>{{ slot.start_time }}</td>
                                    <td>{{ slot.end_time }}</td>
                                    <td>
                                        <Link :href="route('class-time-slots.edit', slot.id)" class="btn btn-info btn-sm me-2">
                                            Edit
                                        </Link>
                                        <button @click="deleteTimeSlot(slot.id)" class="btn btn-danger btn-sm">
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="alert alert-info text-center" role="alert">
                        <p class="mb-3">No time slots found. Please create one to get started.</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>