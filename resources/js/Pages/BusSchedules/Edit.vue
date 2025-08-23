<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { computed, watchEffect } from 'vue';



const props = defineProps({
    busSchedule: Object, // The bus schedule object to edit
    classNames: Object, // List of class names for dropdown
});

const flash = computed(() => usePage().props.flash || {});

const form = useForm({
    _method: 'post', 
    bus_number: props.busSchedule.bus_number,
    route_name: props.busSchedule.route_name,
    // FIX: Ensure departure_time is in HH:MM format
    departure_time: props.busSchedule.departure_time ? props.busSchedule.departure_time.substring(0, 5) : '',
    // FIX: Ensure arrival_time is in HH:MM format
    arrival_time: props.busSchedule.arrival_time ? props.busSchedule.arrival_time.substring(0, 5) : '',
    driver_name: props.busSchedule.driver_name,
    capacity: props.busSchedule.capacity,
    status: props.busSchedule.status,
    notes: props.busSchedule.notes,
    class_id: props.busSchedule.class_id,
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
            alert(flash.value.message);
        }
    }
});

const submit = () => {
    form.post(route('bus-schedules.update', props.busSchedule.id), {
        onSuccess: () => {
            // No need to reset form on edit success
        },
        onError: (errors) => {
            console.error("Bus schedule update failed:", errors);
        },
    });
};
</script>

<template>
    <Head :title="`Edit Bus Schedule: ${busSchedule.bus_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Bus Schedule: {{ busSchedule.bus_number }}</h2>
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
                                <p class="mt-1 text-sm text-gray-500">Format: HH:MM (e.g., 09:00 for 9 AM, 14:30 for 2:30 PM)</p>
                                <InputError class="mt-2" :message="form.errors.departure_time" />
                            </div>
                            <div>
                                <InputLabel for="arrival_time" value="Arrival Time" />
                                <TextInput id="arrival_time" type="time" class="mt-1 block w-full" v-model="form.arrival_time" required />
                                <p class="mt-1 text-sm text-gray-500">Format: HH:MM (e.g., 09:00 for 9 AM, 14:30 for 2:30 PM)</p>
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
                            <InputLabel for="class_id" value="Associated Class (Optional)" />
                            <select id="class_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" v-model="form.class_id">
                                <option :value="null">-- Select a Class --</option>
                                <option v-for="className in props.classNames" :key="className.id" :value="className.id">
                                    {{ className.class_name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.class_id" />
                        </div>

                        <div>
                            <InputLabel for="status" value="Status" />
                            <select id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" v-model.number="form.status" required>
                                <option :value="0">Active</option>
                                <option :value="1">Inactive</option>
                                <option :value="2">Delayed</option>
                                <option :value="3">Cancelled</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>


                        <div class="flex items-center justify-end mt-4">
                            <Link :href="route('bus-schedules.index')" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                Update Schedule
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
