<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    settings: Object,
});

// State for the delete confirmation modal
const showDeleteModal = ref(false);

// --- Delete Functions ---
const confirmDelete = () => {
    showDeleteModal.value = true;
};

const deleteSetting = () => {
    const form = useForm({});
    form.delete(route('settings.destroy', props.settings.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
        },
        onError: (errors) => {
            console.error('Delete errors:', errors);
            showDeleteModal.value = false;
        },
    });
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
};

// Check if settings exist to show the correct button
const settingsExist = computed(() => props.settings && Object.keys(props.settings).length > 0 && props.settings.id);

// Watch for flash messages and show a SweetAlert toast
const flash = computed(() => usePage().props.flash || {});
watch(() => flash.value, (newFlash) => {
    if (newFlash && newFlash.message) {
        Swal.fire({
            icon: newFlash.type === 'success' ? 'success' : 'error',
            title: newFlash.type === 'success' ? 'Success!' : 'Error!',
            text: newFlash.message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }
}, { deep: true });
</script>

<template>
    <Head title="School Settings" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                School Settings
            </h2>
        </template>

        <div class="py-12 bg-gray-100">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-2xl font-bold text-gray-900">Current School Information</h3>
                        <div class="flex space-x-2" v-if="settingsExist">
                            <Link :href="route('settings.edit')">
                                <PrimaryButton>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                    </svg>
                                    Edit Settings
                                </PrimaryButton>
                            </Link>
                            <DangerButton @click="confirmDelete">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                                Delete Settings
                            </DangerButton>
                        </div>
                        <Link v-else :href="route('settings.create')">
                            <PrimaryButton>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                Create Settings
                            </PrimaryButton>
                        </Link>
                    </div>

                    <div v-if="!settingsExist" class="text-center py-10 text-gray-500">
                        <p class="text-xl">No school settings have been created yet.</p>
                        <p class="mt-2">Click the button above to create the initial settings.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">General Information</h4>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">School Name</p>
                                    <p class="text-gray-900">{{ settings.school_name || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Address</p>
                                    <p class="text-gray-900">{{ settings.address || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Phone Number</p>
                                    <p class="text-gray-900">{{ settings.phone_number || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Email</p>
                                    <p class="text-gray-900">{{ settings.email || 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 p-6 rounded-lg shadow-sm">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Administrative & Current Session</h4>
                            <div class="space-y-4">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Principal Name</p>
                                    <p class="text-gray-900">{{ settings.principal_name || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Current Session</p>
                                    <p class="text-gray-900">{{ settings.current_session || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Principal Signature</p>
                                    <p class="text-gray-900">{{ settings.principal_signature || 'Not Set' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">School Logo</p>
                                    <p class="text-gray-900">{{ settings.school_logo || 'Not Set' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">Confirm Deletion</h3>
                <p class="mb-6 text-gray-600">
                    Are you sure you want to delete the school settings? This action cannot be undone.
                </p>
                <div class="flex justify-end space-x-3">
                    <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                    <DangerButton @click="deleteSetting">Delete</DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
