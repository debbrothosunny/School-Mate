<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';



const props = defineProps({
    busSchedules: Object, // Paginated bus schedules
    classNames: Object, // All class names for filter dropdown
    filters: Object, // Current filter values
    flash: Object, // Flash messages
});

// Reactive filter state
const search = ref(props.filters.search || '');
const status = ref(props.filters.status !== undefined ? Number(props.filters.status) : ''); // Default to all statuses
const class_id = ref(props.filters.class_id || '');

// Watch for changes in filters and trigger Inertia visit
watch([search, status, class_id], ([newSearch, newStatus, newClassNameId]) => {
    router.get(route('bus-schedules.index'), {
        search: newSearch,
        status: newStatus,
        class_id: newClassNameId,
    }, {
        preserveState: true,
        replace: true,
    });
});

// Function to clear filters
const clearFilters = () => {
    search.value = '';
    status.value = '';
    class_id.value = '';
};

// Function to get status string based on a numeric code
const getStatusString = (status) => {
    switch (status) {
        case 0: return 'Active';
        case 1: return 'Inactive';
        case 2: return 'Delayed';
        case 3: return 'Cancelled';
        default: return 'Unknown';
    }
};

// Function to get a Tailwind class for the status badge
const getStatusBadgeClass = (status) => {
    switch (status) {
        case 0: return 'bg-green-600 text-white';
        case 1: return 'bg-gray-400 text-white';
        case 2: return 'bg-yellow-500 text-white';
        case 3: return 'bg-red-600 text-white';
        default: return 'bg-gray-400 text-white';
    }
};

// Form for deletion
const deleteForm = useForm({});
const showDeleteModal = ref(false);
const scheduleToDelete = ref(null);

// Function to open the delete confirmation modal
const openDeleteModal = (schedule) => {
    scheduleToDelete.value = schedule;
    showDeleteModal.value = true;
};

// Function to close the delete confirmation modal
const closeDeleteModal = () => {
    showDeleteModal.value = false;
    scheduleToDelete.value = null;
};

// Function to handle bus schedule deletion
const deleteSchedule = () => {
    if (scheduleToDelete.value) {
        deleteForm.delete(route('bus-schedules.destroy', scheduleToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
            },
            onError: (errors) => {
                console.error('Deletion failed:', errors);
            }
        });
    }
};
</script>

<template>
    <Head title="Bus Schedules"/>

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-gray-100">Bus Schedules</h2>
        </template>

        <div class="py-12 bg-gray-100 dark:bg-gray-900 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-2xl p-6">

                    <!-- Flash Message Display -->
                    <FlashMessage v-if="flash && flash.message" :flash="flash" class="mb-6" />

                    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4 md:mb-0">Bus Schedule Management</h3>
                        <Link :href="route('bus-schedules.create')">
                            <PrimaryButton class="inline-flex items-center px-6 py-3 border border-transparent rounded-full font-semibold text-sm uppercase tracking-wider shadow-lg transform hover:scale-105 transition-all duration-300">
                                <svg class="w-4 h-4 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                                Add New Schedule
                            </PrimaryButton>
                        </Link>
                    </div>

                    <!-- Filter Section - Redesigned -->
                    <div class="mb-8 bg-gray-50 dark:bg-gray-700 p-6 rounded-2xl shadow-inner border border-gray-200 dark:border-gray-600">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                                <TextInput
                                    id="search"
                                    type="text"
                                    class="mt-1 block w-full rounded-full shadow-sm dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500"
                                    v-model="search"
                                    placeholder="Bus, route, driver..."
                                />
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                <select
                                    id="status"
                                    class="mt-1 block w-full rounded-full shadow-sm dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500"
                                    v-model="status"
                                >
                                    <option value="" class="dark:bg-gray-600 dark:text-gray-100">All Statuses</option>
                                    <option :value="0" class="dark:bg-gray-600 dark:text-gray-100">Active</option>
                                    <option :value="1" class="dark:bg-gray-600 dark:text-gray-100">Inactive</option>
                                    <option :value="2" class="dark:bg-gray-600 dark:text-gray-100">Delayed</option>
                                    <option :value="3" class="dark:bg-gray-600 dark:text-gray-100">Cancelled</option>
                                </select>
                            </div>
                            <div>
                                <label for="class_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Class</label>
                                <select
                                    id="class_id"
                                    class="mt-1 block w-full rounded-full shadow-sm dark:bg-gray-600 dark:text-gray-100 dark:border-gray-500"
                                    v-model="class_id"
                                >
                                    <option value="" class="dark:bg-gray-600 dark:text-gray-100">All Classes</option>
                                    <option v-for="className in classNames" :key="className.id" :value="className.id" class="dark:bg-gray-600 dark:text-gray-100">
                                        {{ className.class_name }}
                                    </option>
                                </select>
                            </div>
                            <div class="md:col-span-1">
                                <SecondaryButton @click="clearFilters" class="w-full justify-center rounded-full shadow-lg dark:text-gray-100">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    Clear Filters
                                </SecondaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- Bus Schedules Card List -->
                    <div v-if="busSchedules.data.length === 0" class="text-center text-gray-500 dark:text-gray-400 py-10">
                        No bus schedules found matching the criteria.
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="schedule in busSchedules.data" :key="schedule.id" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 flex flex-col hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                            <!-- Header Section -->
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex flex-col">
                                    <h4 class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ schedule.bus_number }}</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">{{ schedule.route_name }}</p>
                                </div>
                                <span :class="['px-3 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(schedule.status)]">
                                    {{ getStatusString(schedule.status) }}
                                </span>
                            </div>

                            <!-- Schedule Details -->
                            <div class="text-sm text-gray-700 dark:text-gray-300 space-y-3 flex-grow">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>Departure: <span class="font-semibold">{{ schedule.departure_time }}</span></span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>Arrival: <span class="font-semibold">{{ schedule.arrival_time }}</span></span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    <span>Driver: <span class="font-semibold">{{ schedule.driver_name || 'N/A' }}</span></span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H2v-2a3 3 0 015.356-1.857M17 20v-2c0-.523-.13-1.03-.356-1.571m0 0A5.002 5.002 0 0110 14.5a5.002 5.002 0 01-6.644 1.643M2 20h20"></path></svg>
                                    <span>Capacity: <span class="font-semibold">{{ schedule.capacity || 'N/A' }}</span></span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h4a2 2 0 012 2v2M7 7h10"></path></svg>
                                    <span>Class: <span class="font-semibold">{{ schedule.class_name?.class_name || 'N/A' }}</span></span>
                                </div>
                            </div>

                            <!-- Actions Section -->
                            <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700 w-full flex justify-end space-x-2">
                                <Link :href="route('bus-schedules.edit', schedule.id)" class="p-2 text-indigo-600 dark:text-indigo-400 rounded-full hover:bg-indigo-50 dark:hover:bg-gray-700 transition-colors duration-200" aria-label="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </Link>
                                <DangerButton @click="openDeleteModal(schedule)" class="p-2 rounded-full hover:bg-red-50 dark:hover:bg-gray-700 transition-colors duration-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </DangerButton>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination Links -->
                    <Pagination :links="busSchedules.links" class="mt-8" />
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 dark:bg-gray-900 dark:bg-opacity-75 overflow-y-auto h-full w-full flex items-center justify-center z-50 transition-all duration-300 modal-fade-in">
            <div class="relative p-8 border w-full max-w-lg shadow-2xl rounded-2xl bg-white dark:bg-gray-800 animate-modal-pop-in">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4 text-center">Confirm Deletion</h3>
                <p class="mb-6 text-gray-700 dark:text-gray-300 text-center">
                    Are you sure you want to permanently delete the schedule for bus "<span class="font-semibold text-indigo-600 dark:text-indigo-400">{{ scheduleToDelete ? scheduleToDelete.bus_number : '' }}</span>"? This action cannot be undone.
                </p>
                <div class="flex justify-center space-x-4">
                    <SecondaryButton @click="closeDeleteModal" class="rounded-full">Cancel</SecondaryButton>
                    <DangerButton @click="deleteSchedule" class="rounded-full">Delete</DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Custom animations for the modal */
@keyframes modal-pop-in {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(10px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.animate-modal-pop-in {
    animation: modal-pop-in 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
}

.modal-fade-in {
    transition: opacity 0.3s ease-out;
}
</style>
