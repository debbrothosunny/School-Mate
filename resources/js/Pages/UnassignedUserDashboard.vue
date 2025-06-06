<script setup>

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

import { Head } from '@inertiajs/vue3';



defineProps({

    message: String,

    userName: String, // Ensure userName is passed from DashboardController

});

</script>


<template>

    <Head title="My Dashboard" />

    <AuthenticatedLayout>

        <template #header>

            <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Dashboard</h2>

        </template>



        <div class="py-12">

            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

                    <div class="p-6 text-gray-900 text-center">

                        <h3 class="text-2xl font-bold mb-4">Hello, {{ userName ?? 'User' }}!</h3>

                        <p class="text-lg mb-4">{{ message }}</p>


                        <div class="p-4 bg-blue-50 border-l-4 border-blue-400 text-blue-700 rounded-md">

                            <p class="font-semibold text-xl">Your account is currently awaiting role assignment.</p>

                            <p class="mt-2">An administrator will review your registration and assign the appropriate role (e.g., Teacher, Accounts, or    Student) shortly. You will gain full access to the system features once your role is assigned.</p>

                            <p class="mt-2">Thank you for your patience!</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

    </AuthenticatedLayout>

</template>