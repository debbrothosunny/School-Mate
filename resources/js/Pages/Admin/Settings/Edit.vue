<script setup>
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { computed, watch, ref } from 'vue';

const props = defineProps({
    settings: Object,
});

// Image handling logic matching the student image system (moved up to avoid initialization error)
const getImageUrl = (image) => {
    if (image) {
        if (image.startsWith('http')) {
            return image;
        }
        return route('settings.logo.serve', { filename: image.split('/').pop() });
    }
    return 'https://placehold.co/120x120/E0E7FF/1E40AF?text=PHOTO';
};

// Create ref for logo preview, initialized with existing logo using images.logo route
const logoPreview = ref(props.settings?.school_logo ? getImageUrl(props.settings.school_logo) : null);

// Create an Inertia form with the initial data from the props
const form = useForm({
    school_name: props.settings?.school_name || '',
    address: props.settings?.address || '',
    phone_number: props.settings?.phone_number || '',
    email: props.settings?.email || '',
    principal_name: props.settings?.principal_name || '',
    principal_signature: props.settings?.principal_signature || '',
    school_logo: null, // File input for logo
    current_image_path: props.settings?.school_logo || null, // Track current logo path
    clear_image: false, // Checkbox to clear current logo
    current_session: props.settings?.current_session || '',
});

// Handle logo file selection and preview
const handleLogoSelect = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.school_logo = file;
        form.clear_image = false; // Uncheck clear checkbox when a new file is selected
        logoPreview.value = URL.createObjectURL(file);
    } else {
        // Revert to existing logo if no new file is selected
        logoPreview.value = form.current_image_path ? getImageUrl(form.current_image_path) : null;
    }
};

// Handle clear image checkbox
const handleClearImageCheckbox = () => {
    if (form.clear_image) {
        form.school_logo = null; // Clear new file if any
        logoPreview.value = null; // Clear preview
    } else {
        // Restore current logo preview if unchecking
        logoPreview.value = form.current_image_path ? getImageUrl(form.current_image_path) : null;
    }
};

// Submit form
const submit = () => {
    form.post(route('settings.update'), {
        preserveScroll: true,
        forceFormData: true, // Required for file uploads
        onSuccess: () => {
            if (form.school_logo || form.clear_image) {
                logoPreview.value = null; // Reset preview after upload or clear
                form.school_logo = null;
                form.clear_image = false;
            }
            // Update current_image_path with new logo path if provided by backend
            if (usePage().props.settings?.school_logo) {
                form.current_image_path = usePage().props.settings.school_logo;
                logoPreview.value = getImageUrl(form.current_image_path);
            }
        },
    });
};

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

// Access form errors for validation feedback
const { errors } = form;
</script>

<template>
    <Head title="Edit School Settings" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-cyan-500 via-pink-500 to-amber-500">
                Edit School Settings
            </h2>
        </template>
        <div class="container mx-auto px-4 py-12 bg-gradient-to-br from-gray-50 to-white">
            <div class="bg-white/95 rounded-3xl shadow-2xl p-8 animate-fade-in">
                <form @submit.prevent="submit">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- School Name -->
                        <div>
                            <InputLabel for="school_name" value="School Name" class="text-sm font-bold text-cyan-900" />
                            <input
                                id="school_name"
                                type="text"
                                v-model="form.school_name"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-md focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 bg-white/90 transition duration-300"
                            />
                            <InputError class="mt-2 text-pink-600" :message="errors.school_name" />
                        </div>
                        <!-- Principal Name -->
                        <div>
                            <InputLabel for="principal_name" value="Principal Name" class="text-sm font-bold text-cyan-900" />
                            <input
                                id="principal_name"
                                type="text"
                                v-model="form.principal_name"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-md focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 bg-white/90 transition duration-300"
                            />
                            <InputError class="mt-2 text-pink-600" :message="errors.principal_name" />
                        </div>
                        <!-- Address -->
                        <div class="md:col-span-2">
                            <InputLabel for="address" value="Address" class="text-sm font-bold text-cyan-900" />
                            <textarea
                                id="address"
                                v-model="form.address"
                                rows="3"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-md focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 bg-white/90 transition duration-300"
                            ></textarea>
                            <InputError class="mt-2 text-pink-600" :message="errors.address" />
                        </div>
                        <!-- Phone Number -->
                        <div>
                            <InputLabel for="phone_number" value="Phone Number" class="text-sm font-bold text-cyan-900" />
                            <input
                                id="phone_number"
                                type="text"
                                v-model="form.phone_number"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-md focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 bg-white/90 transition duration-300"
                            />
                            <InputError class="mt-2 text-pink-600" :message="errors.phone_number" />
                        </div>
                        <!-- Email -->
                        <div>
                            <InputLabel for="email" value="Email" class="text-sm font-bold text-cyan-900" />
                            <input
                                id="email"
                                type="email"
                                v-model="form.email"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-md focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 bg-white/90 transition duration-300"
                            />
                            <InputError class="mt-2 text-pink-600" :message="errors.email" />
                        </div>
                        <!-- Current Session -->
                        <div>
                            <InputLabel for="current_session" value="Current Session" class="text-sm font-bold text-cyan-900" />
                            <input
                                id="current_session"
                                type="text"
                                v-model="form.current_session"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-md focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 bg-white/90 transition duration-300"
                            />
                            <InputError class="mt-2 text-pink-600" :message="errors.current_session" />
                        </div>
                        <!-- Principal Signature (Text Input) -->
                        <div>
                            <InputLabel for="principal_signature" value="Principal Signature" class="text-sm font-bold text-cyan-900" />
                            <input
                                id="principal_signature"
                                type="text"
                                v-model="form.principal_signature"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-md focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 bg-white/90 transition duration-300"
                            />
                            <InputError class="mt-2 text-pink-600" :message="errors.principal_signature" />
                        </div>
                        <!-- School Logo Upload -->
                        <div>
                            <InputLabel for="school_logo" value="School Logo" class="text-sm font-bold text-cyan-900" />
                            <input
                                id="school_logo"
                                type="file"
                                accept="image/*"
                                @change="handleLogoSelect"
                                class="mt-1 block w-full rounded-xl border-gray-300 shadow-md focus:border-cyan-500 focus:ring focus:ring-cyan-200 focus:ring-opacity-50 bg-white/90 transition duration-300"
                            />
                            <InputError class="mt-2 text-pink-600" :message="errors.school_logo" />
                            <div v-if="logoPreview" class="mt-2 photo-box">
                                <img
                                    :src="logoPreview"
                                    alt="Logo Preview"
                                    class="img-thumbnail"
                                    style="width: 128px; height: 128px; object-fit: cover;"
                                    @error="logoPreview = null"
                                />
                            </div>
                            <div v-else-if="!form.school_logo && !form.current_image_path" class="mt-2 text-cyan-600">No logo uploaded.</div>
                            <div v-if="props.settings?.school_logo || form.school_logo" class="mt-2">
                                <label class="flex items-center">
                                    <Checkbox v-model:checked="form.clear_image" name="clear_image" @change="handleClearImageCheckbox" />
                                    <span class="ml-2 text-sm text-cyan-700">Remove Current Logo</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <Link :href="route('settings.index')">
                            <button type="button" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition duration-300">
                                Cancel
                            </button>
                        </Link>
                        <PrimaryButton
                            :class="{ 'opacity-75': form.processing }"
                            :disabled="form.processing"
                            class="bg-gradient-to-r from-cyan-500 to-violet-500 hover:from-cyan-600 hover:to-violet-600 text-white font-bold rounded-xl shadow-lg transition duration-300 transform hover:scale-105"
                        >
                            Save Settings
                        </PrimaryButton>
                    </div>
                </form>
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
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(0, 255, 255, 0.3);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .text-4xl {
        font-size: 2rem;
    }
    .photo-box img {
        width: 100px;
        height: 100px;
    }
}
</style>