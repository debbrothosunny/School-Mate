<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';

// Import your custom components

import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';


const showingNavigationDropdown = ref(false);
const page = usePage();

// --- AUTHENTICATION/LOGOUT LOGIC ---
const logout = () => {
    router.post(route('logout'));
};

const confirmLogout = () => {
    // Swal is globally available because you included it via CDN in app.blade.php
    Swal.fire({
        title: 'Are you sure?',
        text: "You will be logged out of your session.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, log me out!'
    }).then((result) => {
        if (result.isConfirmed) {
            logout();
        }
    });
};

// --- COMPUTED PROPERTIES FOR ROLE CHECKS ---
const isAdmin = computed(() => {
    if (!page.props.auth.user || !page.props.auth.user.roles) {
        return false;
    }
    return page.props.auth.user.roles.map(role => role.toLowerCase()).includes('admin');
});

const isAccounts = computed(() => {
    if (!page.props.auth.user || !page.props.auth.user.roles) {
        return false;
    }
    return page.props.auth.user.roles.map(role => role.toLowerCase()).includes('accounts');
});

const isTeacher = computed(() => {
    if (!page.props.auth.user || !page.props.auth.user.roles) {
        return false;
    }
    return page.props.auth.user.roles.map(role => role.toLowerCase()).includes('teacher');
});

// New: Computed property for Student/Default role
const isStudent = computed(() => {
    return !isAdmin.value && !isAccounts.value && !isTeacher.value;
});


// Add these to make the user and their roles accessible directly in the template
const user = computed(() => page.props.auth.user);

// --- Adjusted: Computed property for dynamic Dashboard route ---
// This now always points to the general dashboard, as teacher will have a specific link
const getDashboardRoute = computed(() => {
    // This route is primarily for the ApplicationLogo link in the header.
    // The actual dashboard a user sees is determined by DashboardController.
    // For Accounts, it will redirect to accounts.dashboard.
    return route('dashboard');
});

</script>

<template>
    <!-- Main layout container with Inter font -->
    <div class="min-h-screen bg-gray-100 flex flex-col font-[Inter] antialiased">
        <!-- Navigation Bar -->
        <nav class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm">
            <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16 items-center">
                    <!-- Logo and App Name -->
                    <div class="flex items-center">
                        <div class="shrink-0 flex items-center">
                            <!-- You can add a logo component here if you have one -->
                            <!-- <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800" /> -->
                        </div>
                        <div class="ml-4 text-xl font-extrabold text-gray-800 hidden sm:block tracking-tight">
                            School-Mate
                        </div>
                    </div>

                    <!-- User Dropdown (right side) -->
                    <div class="flex items-center ms-6">
                        <div class="relative">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <span class="inline-flex rounded-md">
                                        <button
                                            type="button"
                                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:text-gray-900 focus:outline-none transition ease-in-out duration-150"
                                        >
                                            {{ $page.props.auth.user.name }}
                                            <svg
                                                class="ms-2 -me-0.5 h-4 w-4"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 20 20"
                                                fill="currentColor"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </span>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                    <DropdownLink @click.prevent="confirmLogout">
                                        Log Out
                                    </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>

                    <!-- Mobile hamburger button -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                            class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path
                                    :class="{
                                        hidden: showingNavigationDropdown,
                                        'inline-flex': !showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    :class="{
                                        hidden: !showingNavigationDropdown,
                                        'inline-flex': showingNavigationDropdown,
                                    }"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <!-- This section was modified to include responsive styling elements -->
            <div :class="{ 'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown }" class="sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <!-- Responsive NavLinks for Admin Panel -->
                    <div v-if="isAdmin" class="mt-4 border-t border-gray-200 pt-4">
                        <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 px-3">Admin Panel</h3>
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('users.index')" :active="route().current('users.index')">
                            User Management
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('sections.index')" :active="route().current('sections.*')">
                            Section Management
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('teachers.index')" :active="route().current('teachers.index')">
                            Teachers
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('class-names.index')" :active="route().current('class-names.index')">
                            Class Management
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('students.index')" :active="route().current('students.index')">
                            Student Management
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('attendance.index')" :active="route().current('attendance.index')">
                            Student Attendance
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive NavLinks for Accounts Panel -->
                    <div v-if="isAccounts" class="mt-4 border-t border-gray-200 pt-4">
                        <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2">Accounts Panel</h3>
                        <ResponsiveNavLink :href="route('accounts.dashboard')" :active="route().current('accounts.dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive NavLinks for Teacher Tools -->
                    <div v-if="isTeacher && !isAdmin" class="mt-4 border-t border-gray-200 pt-4">
                        <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2">Teacher Tools</h3>
                        <ResponsiveNavLink :href="route('teacher.dashboard')" :active="route().current('teacher.dashboard')">
                            Teacher Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('my-classes.index')" :active="route().current('my-classes.index')">
                            My Class Timetable
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive NavLinks for Student/Default User Tools -->
                    <div v-if="isStudent" class="mt-4 border-t border-gray-200 pt-4">
                        <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2">Student Tools</h3>
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            My Dashboard
                        </ResponsiveNavLink>
                        <!-- Add more student-specific links here if needed -->
                    </div>
                </div>

                <div class="pt-4 pb-1 border-t border-gray-200">
                    <div class="px-4">
                        <div class="font-medium text-base text-gray-800">
                            {{ $page.props.auth.user.name }}
                        </div>
                        <div class="font-medium text-sm text-gray-500">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')"> Profile </ResponsiveNavLink>
                        <ResponsiveNavLink @click.prevent="confirmLogout"> Log Out </ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <div class="flex flex-1">
            <!-- Main Sidebar (for larger screens) -->
            <aside :class="{ 'hidden': !showingNavigationDropdown && !isAdmin && !isTeacher && !isAccounts && !isStudent, 'block': showingNavigationDropdown || isAdmin || isTeacher || isAccounts || isStudent }"
                class="fixed inset-y-0 left-0 bg-white text-gray-800 w-64 p-4 z-30 overflow-y-auto
                        md:block md:static md:shadow-md md:z-auto transition-all duration-300 ease-in-out">
                <div class="space-y-4">
                    <!-- Admin Panel Links -->
                    <div v-if="isAdmin">
                    <h3 class="text-xs uppercase font-semibold text-gray-500 mb-5">Admin Panel</h3>
                    <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Admin Dashboard Panel</NavLink>
                    <NavLink :href="route('users.index')" :active="route().current('users.index')">Users Management</NavLink>
                    <NavLink :href="route('sections.index')" :active="route().current('sections.*')">Section Management</NavLink>
                    <NavLink :href="route('teachers.index')" :active="route().current('teachers.index')">Teacher Management</NavLink>
                    <NavLink :href="route('class-names.index')" :active="route().current('class-names.index')">Class Management</NavLink>
                    <NavLink :href="route('students.index')" :active="route().current('students.index')">Student Management</NavLink>
                    <NavLink :href="route('attendance.index')" :active="route().current('attendance.index')">Student Attendance</NavLink>
                </div>

                    <!-- Accounts Panel Links -->
                    <div v-if="isAccounts" class="mt-6">
                        <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 px-3">Accounts Panel</h3>
                        <NavLink :href="route('accounts.dashboard')" :active="route().current('accounts.dashboard')">
                            Dashboard
                        </NavLink>
                    </div>

                    <!-- Teacher Tools Links -->
                    <div v-if="isTeacher && !isAdmin" class="mt-6">
                        <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 px-3">Teacher Tools</h3>
                        <NavLink :href="route('teacher.dashboard')" :active="route().current('teacher.dashboard')">
                            Teacher Dashboard
                        </NavLink>
                        <NavLink :href="route('my-classes.index')" :active="route().current('my-classes.index')">
                            My Class Timetable
                        </NavLink>
                    </div>

                    <!-- Student/Default User Links -->
                    <div v-if="isStudent" class="mt-6">
                        <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 px-3">Student Tools</h3>
                        <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            My Dashboard
                        </NavLink>
                        <!-- Add more student-specific links here if needed -->
                    </div>
                </div>
            </aside>

            <!-- Page Content -->
            <div class="flex-1 md:ml-64 lg:ml-64 py-6 px-4 sm:px-6 lg:px-8">
                <!-- Page Heading -->
                <header class="bg-white shadow-sm rounded-lg mb-6 p-4" v-if="$slots.header">
                    <div class="max-w-7xl mx-auto py-2">
                        <slot name="header" />
                    </div>
                </header>
                

                <!-- Page Content -->
                <main>
                    <div class="max-w-full mx-auto">
                        <slot />
                    </div>
                </main>
            </div>
        </div>
    </div>
</template>
