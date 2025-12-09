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
    form.delete(route('settings.destroy', props.settings?.id), {
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
watch(
    () => flash.value,
    (newFlash) => {
        if (newFlash && newFlash.message) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: newFlash.type === 'success' ? 'success' : 'error',
                    title: newFlash.type === 'success' ? 'Success!' : 'Error!',
                    text: newFlash.message,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    background: newFlash.type === 'success' ? '#E6FFF5' : '#FFE6E6',
                    iconColor: newFlash.type === 'success' ? '#00CC88' : '#FF5555',
                });
            }
        }
    },
    { deep: true }
);

// Image handling logic matching the student image system
const getImageUrl = (image) => {
    if (image) {
        if (image.startsWith('http')) {
            return image;
        }
        // Use the settings.logo.serve route for local images
        return route('settings.logo.serve', { filename: image.split('/').pop() });
    }
    return 'https://placehold.co/120x120/E0E7FF/1E40AF?text=PHOTO';
};
</script>

<template>
    <Head title="School Settings" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-cyan-500 via-pink-500 to-amber-500">
                School Settings
            </h2>
        </template>
        <div class="container mx-auto px-4 py-12 bg-gradient-to-br from-gray-50 to-white">
            <div class="bg-white/95 rounded-3xl shadow-2xl p-8 animate-fade-in">
                <div class="bg-gradient-to-r from-cyan-100 via-pink-100 to-amber-100 rounded-xl p-6 mb-6">
                    <h3 class="text-2xl font-bold text-cyan-900">Current School Information</h3>
                </div>
                <div class="flex justify-between items-center mb-8">
                    <div></div>
                    <div class="flex space-x-4">
                        <Link v-if="settingsExist" :href="route('settings.edit')">
                            <PrimaryButton class="bg-gradient-to-r from-cyan-500 to-violet-500 hover:from-cyan-600 hover:to-violet-600 text-white font-bold rounded-xl shadow-lg transition duration-300 transform hover:scale-105">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 mr-2"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"
                                    />
                                </svg>
                                Edit Settings
                            </PrimaryButton>
                        </Link>
                        
                        <Link v-else :href="route('settings.create')">
                            <PrimaryButton class="bg-gradient-to-r from-cyan-500 to-violet-500 hover:from-cyan-600 hover:to-violet-600 text-white font-bold rounded-xl shadow-lg transition duration-300 transform hover:scale-105">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 mr-2"
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Create Settings
                            </PrimaryButton>
                        </Link>
                    </div>
                </div>
                <div v-if="!settingsExist" class="text-center py-10 text-cyan-600">
                    <p class="text-2xl font-bold">No school settings have been created yet. 😔</p>
                    <p class="mt-2 text-lg">Click the button above to create the initial settings.</p>
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-gradient-to-br from-cyan-50 to-violet-50 p-6 rounded-xl shadow-md animate-fade-in">
                        <h4 class="text-lg font-bold text-cyan-900 mb-4">General Information</h4>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-semibold text-cyan-700">School Name</p>
                                <p class="text-cyan-900 text-lg">{{ settings.school_name || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-cyan-700">Address</p>
                                <p class="text-cyan-900 text-lg">{{ settings.address || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-cyan-700">Phone Number</p>
                                <p class="text-cyan-900 text-lg">{{ settings.phone_number || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-cyan-700">Email</p>
                                <p class="text-cyan-900 text-lg">{{ settings.email || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-br from-pink-50 to-amber-50 p-6 rounded-xl shadow-md animate-fade-in">
                        <h4 class="text-lg font-bold text-pink-900 mb-4">Administrative & Current Session</h4>
                        <div class="space-y-4">
                            <div>
                                <p class="text-sm font-semibold text-pink-700">Principal Name</p>
                                <p class="text-pink-900 text-lg">{{ settings.principal_name || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-pink-700">Current Session</p>
                                <p class="text-pink-900 text-lg">{{ settings.current_session || 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-pink-700">Principal Signature (Text)</p>
                                <p class="text-pink-900 font-signature text-2xl">{{ settings.principal_signature || 'Not Set' }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-pink-700">School Logo</p>
                                <div class="photo-box">
                                    <img
                                        :src="getImageUrl(settings.school_logo)"
                                        alt="School Logo"
                                        class="max-w-xs h-auto rounded-lg border-4 border-gradient-to-r from-cyan-500 to-violet-500 p-1 bg-white/90 object-contain transition duration-300 transform hover:scale-105"
                                        style="max-height: 150px;"
                                        @error="this.src='https://placehold.co/120x120/E0E7FF/1E40AF?text=PHOTO'"
                                    />
                                </div>
                            </div>
                            <!-- Optional: Principal Photo (if applicable) -->
                            <div v-if="settings.principal_photo">
                                <p class="text-sm font-semibold text-pink-700">Principal Photo</p>
                                <div class="photo-box">
                                    <img
                                        :src="getImageUrl(settings.principal_photo)"
                                        alt="Principal Photo"
                                        class="max-w-xs h-auto rounded-lg border-4 border-gradient-to-r from-pink-500 to-amber-500 p-1 bg-white/90 object-contain transition duration-300 transform hover:scale-105"
                                        style="max-height: 150px;"
                                        @error="this.src='https://placehold.co/120x120/E0E7FF/1E40AF?text=PHOTO'"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 bg-gray-900/60 flex items-center justify-center z-50 p-4"
        >
            <div class="bg-white/95 rounded-xl shadow-xl p-6 w-full max-w-md animate-bounce-in">
                <h3 class="text-xl font-bold text-cyan-900 mb-4">Confirm Deletion</h3>
                <p class="mb-6 text-cyan-700">
                    Are you sure you want to delete the school settings? This action cannot be undone and will remove the global configuration.
                </p>
                <div class="flex justify-end space-x-3">
                    <button
                        @click="closeDeleteModal"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition duration-300"
                    >
                        Cancel
                    </button>
                    <DangerButton
                        class="bg-gradient-to-r from-pink-500 to-amber-500 hover:from-pink-600 hover:to-amber-600 text-white font-bold rounded-md transition duration-300"
                        @click="deleteSetting"
                    >
                        Delete Permanently
                    </DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Custom animations */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes bounceIn {
    0% { opacity: 0; transform: scale(0.8); }
    50% { opacity: 0.5; transform: scale(1.1); }
    100% { opacity: 1; transform: scale(1); }
}
.animate-fade-in {
    animation: fadeIn 0.4s ease-out;
}
.animate-bounce-in {
    animation: bounceIn 0.4s ease-out;
}

/* Image styling */
.photo-box img {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.photo-box img:hover {
    box-shadow: 0 4px 15px rgba(0, 255, 255, 0.3);
}

/* Signature font */
.font-signature {
    font-family: 'Sacramento', cursive;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .text-4xl {
        font-size: 2rem;
    }
    .text-2xl {
        font-size: 1.5rem;
    }
    .photo-box img {
        max-height: 100px;
    }
}
</style>