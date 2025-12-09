<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    timeSlots: Array,
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

watchEffect(() => {
    if (flash.value?.message) {
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
</script>

<template>
    <Head title="Class Time Slots" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-lg sm:text-xl md:text-2xl font-semibold text-gray-800 dark:text-gray-100 leading-tight">Class Time Slots</h2>
        </template>
        <div class="py-6 sm:py-12 bg-gray-100 dark:bg-gray-900 min-h-screen font-inter">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="p-4 sm:p-6 lg:p-8">
                        <!-- Header + Create Button -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                            <h3 class="text-base sm:text-lg font-medium text-gray-900 dark:text-gray-100">List of Time Slots</h3>
                            <Link :href="route('class-time-slots.create')">
                                <button
                                    class="inline-flex items-center px-4 py-2 text-sm font-semibold rounded-md transition bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm hover:shadow-md"
                                    aria-label="Create new time slot"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Create New Time Slot
                                </button>
                            </Link>
                        </div>
                        <!-- Time Slots Table -->
                        <div v-if="timeSlots.length > 0" class="space-y-4 sm:space-y-0">
                            <div v-for="(slot, index) in timeSlots" :key="slot.id" class="sm:hidden bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4 shadow-sm transition hover:shadow-md">
                                <div class="grid gap-2 text-sm">
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[70%]">{{ slot.name }}</span>
                                        <span class="text-gray-500 dark:text-gray-400">{{ index + 1 }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">Start Time:</span>
                                        <span class="ml-2 text-gray-500 dark:text-gray-400">{{ slot.start_time }}</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700 dark:text-gray-300">End Time:</span>
                                        <span class="ml-2 text-gray-500 dark:text-gray-400">{{ slot.end_time }}</span>
                                    </div>
                                    <div class="flex justify-end gap-2 mt-3">
                                        <Link
                                            :href="route('class-time-slots.edit', slot.id)"
                                            class="inline-flex items-center justify-center min-w-10 min-h-10 p-2 bg-indigo-50 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-800 rounded-md transition focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                            aria-label="Edit time slot"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"></path>
                                            </svg>
                                        </Link>
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="overflow-x-auto hidden sm:block">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">#</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Start Time</th>
                                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">End Time</th>
                                            <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-for="(slot, index) in timeSlots" :key="slot.id" class="hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ index + 1 }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100 truncate max-w-xs">{{ slot.name }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ slot.start_time }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ slot.end_time }}</td>
                                            <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-right flex justify-end gap-3">
                                                <Link
                                                    :href="route('class-time-slots.edit', slot.id)"
                                                    class="inline-flex items-center justify-center min-w-10 min-h-10 p-2 bg-indigo-50 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-800 rounded-md transition focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                                    aria-label="Edit time slot"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L15.232 5.232z"></path>
                                                    </svg>
                                                </Link>
                                                <button
                                                    disabled
                                                    title="Delete time slot is currently disabled"
                                                    class="inline-flex items-center justify-center min-w-10 min-h-10 p-2 
                                                        bg-gray-100 dark:bg-gray-800 
                                                        text-gray-400 dark:text-gray-600 
                                                        rounded-md 
                                                        cursor-not-allowed 
                                                        opacity-60 
                                                        border border-gray-300 dark:border-gray-700"
                                                    aria-label="Delete time slot (disabled)"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 4v12m4-12v12"></path>
                                                    </svg>
                                                </button>
                                                <!-- Time slot deletion disabled -->
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div v-else class="bg-indigo-50 dark:bg-indigo-900 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-200 px-4 py-3 rounded-md text-center text-sm" role="alert">
                            <p class="mb-0">No time slots found. Please create one to get started.</p>
                        </div>
                    </div>
                </div>
                <!-- Footer -->
                <footer class="mt-8 sm:mt-12 pt-4 sm:pt-6 border-t border-gray-200 dark:border-gray-700 text-center bg-gray-100 dark:bg-gray-900">
                    <p class="text-xs sm:text-sm md:text-base text-gray-900 dark:text-gray-100 font-medium leading-relaxed">
                        © All Rights Reserved. Biddaloy is a product of
                        <a href="https://smithitbd.com/" target="_blank" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors hover:underline">
                            Smith IT
                        </a>
                    </p>
                </footer>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.font-inter {
    font-family: 'Inter', sans-serif;
}

/* Mobile card layout */
@media (max-width: 639px) {
    .overflow-x-auto {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .sm\:hidden {
        display: block;
    }
    .sm\:block {
        display: none;
    }
}

/* Tablet and up */
@media (min-width: 640px) {
    .sm\:hidden {
        display: none;
    }
    .sm\:block {
        display: block;
    }
}

/* Touch targets and hover effects */
button, a {
    min-height: 44px;
    min-width: 44px;
    transition: color 0.2s, background-color 0.2s, transform 0.2s;
}
button:hover:not(:disabled), a:hover:not(.opacity-50) {
    transform: scale(1.05);
}

/* Truncate long text */
.truncate {
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Responsive padding and font sizes */
@media (max-width: 639px) {
    .p-6 {
        padding: 1rem;
    }
    .text-sm {
        font-size: 0.875rem;
    }
    .px-3 {
        padding-left: 0.75rem;
        padding-right: 0.75rem;
    }
    .py-2 {
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
}

/* Footer adjustments */
@media (max-width: 639px) {
    footer p {
        white-space: normal;
        max-width: 100%;
    }
}

/* Table and card styling */
.table-hover tr:hover {
    background-color: #f9fafb;
}

/* Smooth transitions for cards */
.transition {
    transition: all 0.2s ease-in-out;
}
</style>