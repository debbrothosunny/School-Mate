<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { computed, watchEffect } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    group: Object,
    groupTypes: Array,
    errors: Object,
});

// Use usePage to access all page props (including flash)
const page = usePage();

// Build flash reactive computed with explicit keys for clarity
const flash = computed(() => ({
    message: page.props.message || '',
    type: page.props.type || '',
}));

// Initialize form with correct method override: 'put' for updates, not 'post'
const form = useForm({
    _method: 'post',
    name: props.group.name || '',
    status: Number(props.group.status || 0), // Ensure status numeric and default to 0
});

// Watch flash messages and show SweetAlert notifications
watchEffect(() => {
    if (flash.value.message) {
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

// Submit handler sends form data correctly using the update route
const submit = () => {
    form.post(route('groups.update', props.group.id), {
        forceFormData: true, // ensures compatibility if you add file inputs later
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'গ্রুপ সফলভাবে আপডেট করা হয়েছে!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
            // Optional: reset form if needed
            // form.reset();
        },
        onError: (errors) => {
            Swal.fire({
                icon: 'error',
                title: 'Failed!',
                text: 'Could not update group. Please check the fields.',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
            });
            console.error("Group update failed:", errors);
        },
    });
};

</script>


<template>
    <Head :title="`Edit Group: ${group.name}`" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Group: {{ group.name }}</h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="name" value="Group Name" />
                            <select
                                id="name"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                v-model="form.name"
                                required
                            >
                                <option value="" disabled>-- Select Group Type --</option>
                                <option v-for="type in groupTypes" :key="type" :value="type">{{ type }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="status" value="Status" />
                            <select
                                id="status"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
                                v-model.number="form.status"
                                required
                            >
                                <option :value="0">Active</option>
                                <option :value="1">Inactive</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>
                        <div class="flex items-center justify-end mt-4">
                            <Link :href="route('groups.index')" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                <span v-if="form.processing">Updating...</span>
                                <span v-else>Update Group</span>
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No additional styles beyond Tailwind CSS used */
</style>
