<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { computed, watchEffect, ref, watch } from 'vue';

const props = defineProps({
    classNames: Object, // List of class names for dropdown
});

const flash = computed(() => usePage().props.flash || {});

const form = useForm({
    bus_number: '',
    route_name: '',
    departure_time: '',
    arrival_time: '',
    driver_name: '',
    capacity: null,
    status: 0, // Default to Active
    class_id: null, // Changed to a single value for single selection
});

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
        }
    }
});

const submit = () => {
    form.post(route('bus-schedules.store'), {
        onSuccess: () => {
            form.reset();
        },
        onError: (errors) => {
            console.error("Bus schedule creation failed:", errors);
        },
    });
};
</script>

<template>
    <Head title="Create Bus Schedule" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create Bus Schedule</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div>
                            <InputLabel for="bus_number" value="Bus Number" />
                            <TextInput id="bus_number" type="text" class="mt-1 block w-full" v-model="form.bus_number" required autofocus />
                            <InputError class="mt-2" :message="form.errors.bus_number" />
                        </div>

                        <div>
                            <InputLabel for="route_name" value="Route Name" />
                            <TextInput id="route_name" type="text" class="mt-1 block w-full" v-model="form.route_name" required />
                            <InputError class="mt-2" :message="form.errors.route_name" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="departure_time" value="Departure Time" />
                                <TextInput id="departure_time" type="time" class="mt-1 block w-full" v-model="form.departure_time" required />
                                
                                <InputError class="mt-2" :message="form.errors.departure_time" />
                            </div>
                            <div>
                                <InputLabel for="arrival_time" value="Arrival Time" />
                                <TextInput id="arrival_time" type="time" class="mt-1 block w-full" v-model="form.arrival_time" required />
                                
                                <InputError class="mt-2" :message="form.errors.arrival_time" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="driver_name" value="Driver Name" />
                            <TextInput id="driver_name" type="text" class="mt-1 block w-full" v-model="form.driver_name" />
                            <InputError class="mt-2" :message="form.errors.driver_name" />
                        </div>

                        <div>
                            <InputLabel for="capacity" value="Capacity" />
                            <TextInput id="capacity" type="number" class="mt-1 block w-full" v-model="form.capacity" min="0" />
                            <InputError class="mt-2" :message="form.errors.capacity" />
                        </div>

                        <div>
                            <InputLabel value="Associated Class" />
                            <div class="flex flex-wrap gap-2 mt-1">
                                <label v-for="className in props.classNames" :key="className.id"
                                       :class="{'bg-blue-500 text-white': form.class_id === className.id, 'bg-gray-200 text-gray-700': form.class_id !== className.id}"
                                       class="px-4 py-2 rounded-full cursor-pointer transition-colors duration-200 ease-in-out">
                                    <input type="radio" :value="className.id" v-model="form.class_id" class="hidden" />
                                    {{ className.class_name }}
                                </label>
                            </div>
                            <InputError class="mt-2" :message="form.errors.class_id" />
                        </div>

                        <div>
                            <InputLabel for="status" value="Status" />
                            <select id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" v-model.number="form.status" required>
                                <option :value="0">Active</option>
                                <option :value="1">Inactive</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <Link :href="route('bus-schedules.index')" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Create Schedule
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
