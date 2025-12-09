<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
// Assuming you have these components:
import TextInput from '@/Components/TextInput.vue';
import { debounce } from 'lodash'; // Using lodash debounce for better search UX

const props = defineProps({
    busSchedules: Object, // Paginated bus schedules
    classNames: Object, // All class names for filter dropdown
    filters: Object, // Current filter values
    flash: Object, // Flash messages
});

// Reactive filter state
const search = ref(props.filters.search || '');
const status = ref(props.filters.status !== undefined ? Number(props.filters.status) : '');
const class_id = ref(props.filters.class_id || '');

// Debounced function for search input
const debouncedSearch = debounce((newSearch) => {
    router.get(route('bus-schedules.index'), {
        search: newSearch,
        status: status.value, // Use current value of status ref
        class_id: class_id.value,
    }, {
        preserveState: true,
        replace: true,
    });
}, 300); // Wait 300ms after user stops typing

// Watch for changes in filters and trigger Inertia visit
watch(search, (newSearch) => {
    debouncedSearch(newSearch);
});

watch([status, class_id], ([newStatus, newClassNameId]) => {
    router.get(route('bus-schedules.index'), {
        search: search.value,
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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bus Schedules</h2>
        </template>

        <div class="py-12 bg-gray-100 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-2xl p-6">

                    <FlashMessage v-if="flash && flash.message" :flash="flash" class="mb-6" />

                    <div class="flex flex-col md:flex-row justify-between items-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-4 md:mb-0">Bus Schedule Management</h3>
                        
                        <Link :href="route('bus-schedules.create')">
                            <PrimaryButton 
                                class="inline-flex items-center px-6 py-3 border border-transparent rounded-full 
                                font-semibold text-sm uppercase tracking-wider shadow-lg 
                                transform hover:scale-105 transition-all duration-300 
                                bg-indigo-600 hover:bg-indigo-700 text-white"
                            >
                                <svg class="w-4 h-4 mr-2 -ml-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
                                Add New Schedule
                            </PrimaryButton>
                        </Link>
                    </div>

                    <div class="mb-8 bg-gray-50 p-6 rounded-2xl shadow-inner border border-gray-200">
                      <form @submit.prevent class="flex flex-col md:flex-row md:items-end md:space-x-6 space-y-4 md:space-y-0">
                        <div class="flex-1">
                          <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                          <TextInput
                            id="search"
                            type="text"
                            class="mt-1 block w-full rounded-full shadow-sm border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            v-model="search"
                            placeholder="Bus, route, driver..."
                          />
                        </div>
                        <div class="flex-1">
                          <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                          <select
                            id="status"
                            class="mt-1 block w-full rounded-full shadow-sm border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            v-model="status"
                          >
                            <option value="">All Statuses</option>
                            <option :value="0">Active</option>
                            <option :value="1">Inactive</option>
                            <option :value="2">Delayed</option>
                            <option :value="3">Cancelled</option>
                          </select>
                        </div>
                        <div class="flex-1">
                          <label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                          <select
                            id="class_id"
                            class="mt-1 block w-full rounded-full shadow-sm border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            v-model="class_id"
                          >
                            <option value="">All Classes</option>
                            <option v-for="className in classNames" :key="className.id" :value="className.id">
                              {{ className.class_name }}
                            </option>
                          </select>
                        </div>
                        <div class="flex-shrink-0 flex items-end">
                          <SecondaryButton
                            @click="clearFilters"
                            class="w-full justify-center rounded-full shadow-lg h-10 md:h-[44px] px-6"
                          >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            Clear Filters
                          </SecondaryButton>
                        </div>
                      </form>
                    </div>

                    <div v-if="busSchedules.data.length === 0" class="text-center text-gray-500 py-10">
                        No bus schedules found matching the criteria.
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div v-for="schedule in busSchedules.data" :key="schedule.id" class="bg-white rounded-xl shadow-lg p-6 flex flex-col hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex flex-col">
                                    <h4 class="text-2xl font-bold text-indigo-600">{{ schedule.bus_number }}</h4>
                                    <p class="text-sm text-gray-500 font-medium">{{ schedule.route_name }}</p>
                                </div>
                                <span :class="['px-3 py-1 text-xs font-semibold rounded-full', getStatusBadgeClass(schedule.status)]">
                                    {{ getStatusString(schedule.status) }}
                                </span>
                            </div>

                            <div class="text-sm text-gray-700 space-y-3 flex-grow">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>Departure: <span class="font-semibold">{{ schedule.departure_time }}</span></span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span>Arrival: <span class="font-semibold">{{ schedule.arrival_time }}</span></span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    <span>Driver: <span class="font-semibold">{{ schedule.driver_name || 'N/A' }}</span></span>
                                </div>

                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H2v-2a3 3 0 015.356-1.857M17 20v-2c0-.523-.13-1.03-.356-1.571m0 0A5.002 5.002 0 0110 14.5a5.002 5.002 0 01-6.644 1.643M2 20h20"></path></svg>
                                    <span>Capacity: <span class="font-semibold">{{ schedule.capacity || 'N/A' }}</span></span>
                                </div>

                                <div class="flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h4a2 2 0 012 2v2M7 7h10"></path></svg>
                                    <span>Class: <span class="font-semibold">{{ schedule.className?.class_name || 'N/A' }}</span></span>
                                </div>
                            </div>

                            <div class="mt-6 pt-4 border-t border-gray-200 w-full flex justify-end space-x-2">
                                <Link :href="route('bus-schedules.edit', schedule.id)" 
                                      class="p-2 text-indigo-600 rounded-full hover:bg-indigo-100 transition-colors duration-200" 
                                      aria-label="Edit">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </Link>
                                <DangerButton @click="openDeleteModal(schedule)" 
                                              class="p-2 rounded-full bg-red-500 text-white hover:bg-red-600 transition-colors duration-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </DangerButton>
                            </div>
                        </div>
                    </div>

                    <Pagination :links="busSchedules.links" class="mt-8" />
                </div>

                <div class="mt-8 pt-6 border-t border-gray-300 text-center bg-gray-100">
                    <p class="text-base md:text-lg text-black font-semibold leading-relaxed mx-auto" style="display:inline-block; white-space:nowrap;">
                        © All Rights Reserved. Biddaloy is a product of
                        <a href="https://smithitbd.com/" target="_blank" class="font-semibold text-red-600 hover:text-red-700 transition-colors hover:underline">
                            Smith&nbsp;IT
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity modal-fade-in" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full animate-modal-pop-in">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.354 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">Delete Bus Schedule</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Are you sure you want to delete this bus schedule? This action cannot be undone.</p>
                                    <p v-if="scheduleToDelete" class="mt-1 text-sm font-semibold text-gray-700">Schedule: #{{ scheduleToDelete.bus_number }} - {{ scheduleToDelete.route_name }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <DangerButton @click="deleteSchedule" :disabled="deleteForm.processing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Delete
                        </DangerButton>
                        <SecondaryButton @click="closeDeleteModal" :disabled="deleteForm.processing" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                            Cancel
                        </SecondaryButton>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Scoped styles for the modal animation (as provided in the original code) */
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