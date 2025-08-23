<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';



const props = defineProps({
    notices: Object, // Paginated notices data for the authenticated user
    flash: Object, // Flash messages
});

const flash = computed(() => usePage().props.flash || {});

// Watch for flash messages and display SweetAlert
watchEffect(() => {
    if (flash.value && flash.value.message) {
        if (typeof Swal !== 'undefined') {
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
        } else {
            console.warn('Swal (SweetAlert2) is not defined. Flash messages will not be displayed via Swal.');
            alert(flash.value.message);
        }
    }
});

// Helper to format date for display
const formatNoticeDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

// Helper to display target users from array
const displayTargetUsers = (usersArray) => {
    if (!usersArray || usersArray.length === 0) return 'N/A';
    return usersArray.map(user => user.charAt(0).toUpperCase() + user.slice(1)).join(', ');
};
</script>

<template>
    <Head title="My Notices" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Notices</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                    <!-- Flash Message Display -->
                    <FlashMessage v-if="flash && flash.message" :flash="flash" class="mb-4" />

                    <h3 class="text-lg font-medium text-gray-900 mb-6">Notices for You</h3>

                    <div v-if="notices.data.length === 0" class="text-center py-8 text-gray-600">
                        <p class="mb-2">No notices found for you at the moment.</p>
                        <p>Please check back later!</p>
                    </div>

                    <div v-else class="space-y-6">
                        <div v-for="notice in notices.data" :key="notice.id" class="border border-gray-200 rounded-lg p-4 shadow-sm bg-gray-50">
                            <h4 class="text-xl font-semibold text-gray-800 mb-2">{{ notice.title }}</h4>
                            <p class="text-sm text-gray-600 mb-3">
                                Published on: <span class="font-medium">{{ formatNoticeDate(notice.notice_date) }}</span>
                                <span v-if="notice.creator"> by {{ notice.creator.name }}</span>
                            </p>
                            <div class="prose max-w-none text-gray-700 mb-4">
                                <p>{{ notice.content }}</p>
                            </div>
                            <div class="text-xs text-gray-500">
                                Targeted to: {{ displayTargetUsers(notice.target_user) }}
                            </div>
                        </div>
                    </div>

                    <!-- Pagination Links -->
                    <Pagination v-if="notices.links && notices.links.length > 3" :links="notices.links" class="mt-6" />

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
