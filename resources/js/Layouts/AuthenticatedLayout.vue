<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Echo from 'laravel-echo';

// Your existing components
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

// Theme composable
import { useTheme } from '@/Composables/useTheme';
const { isDark, toggleTheme } = useTheme();

const page = usePage();

// Nav dropdown toggles
const showingNavigationDropdown = ref(false);
const showAcademicDropdown = ref(false);
const showExamDropdown = ref(false);
const showLibraryDropdown = ref(false);
const showAcademicDropdownMobile = ref(false);
const showExamDropdownMobile = ref(false);
const showLibraryDropdownMobile = ref(false);

// ----------------------------------------------------------------------------
// Real-time Notification State
// ----------------------------------------------------------------------------
const notifications = ref(page.props.auth.user.notifications || []);
const unreadNotificationsCount = ref(page.props.auth.user.unread_notifications_count || 0);
const showNotificationsDropdown = ref(false);

// Audio element reference and state for autoplay policy
const notificationSound = ref(null);
const isSoundEnabled = ref(false);
const showSoundNotice = ref(false);

// Import the notification sound file dynamically using Vite's asset handling
const notificationSoundFile = new URL('/sounds/notification.mp3', import.meta.url).href;

// ----------------------------------------------------------------------------
// Echo Listeners
// ----------------------------------------------------------------------------
onMounted(() => {
    const user = page.props.auth.user;

    // Show notice to enable sound if it's not already enabled after a short delay
    setTimeout(() => {
        if (!isSoundEnabled.value) {
            showSoundNotice.value = true;
        }
    }, 5000);

    if (window.Echo) {
        window.Echo.connector.pusher.connection.bind('connected', () => {
            console.log('Echo connection status: Connected!');
        });
        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            console.error('Echo connection status: Disconnected!');
        });

        // Public notices channel
        window.Echo.channel('notices')
            .listen('.new.notice', ({ notice }) => {
                notifications.value.unshift({
                    id: `notice-${notice.id}`,
                    type: 'notice',
                    message: notice.title,
                    data: notice,
                    created_at: notice.created_at,
                    read_at: null,
                });
                unreadNotificationsCount.value++;
                try {
                    // Only play sound if it has been enabled by user interaction
                    if (isSoundEnabled.value) {
                        notificationSound.value.play();
                    }
                } catch (e) {
                    console.error('Could not play notification sound:', e);
                }
            });

        // Private Laravel notifications channel
        if (user) {
            window.Echo.private(`App.Models.User.${user.id}`)
                .notification((payload) => {
                    console.log('Received real-time notification:', payload);

                    // Add the new notification to the top of the array
                    notifications.value.unshift({
                        id: payload.invoice_id || payload.data?.id,
                        type: 'invoice',
                        message: payload.message,
                        data: payload,
                        created_at: payload.created_at,
                        read_at: null, // Mark as unread
                    });
                    unreadNotificationsCount.value++;
                    try {
                        // Only play sound if it has been enabled by user interaction
                        if (isSoundEnabled.value) {
                            notificationSound.value.play();
                        }
                    } catch (e) {
                        console.error('Could not play notification sound:', e);
                    }
                });

            // NEW LISTENER: Listen for student payment notifications if the user is an accountant.
            if (user.roles && user.roles.includes('accounts')) {
                // Corrected channel name from `private.Accountant` to `private.accountant`
                window.Echo.private(`private.accountant.${user.id}`)
                    .listen('.student.payment.made', (e) => {
                        console.log('Received student payment notification:', e);
                        notifications.value.unshift({
                            id: `payment-${e.paymentId}`,
                            type: 'payment',
                            message: `New payment of ${e.amount} from student: ${e.studentName}`,
                            data: e,
                            created_at: new Date().toISOString(),
                            read_at: null,
                        });
                        unreadNotificationsCount.value++;
                        try {
                            if (isSoundEnabled.value) {
                                notificationSound.value.play();
                            }
                        } catch (err) {
                            console.error('Could not play notification sound for payment:', err);
                        }
                    });
            }
        }
    } else {
        console.error('Echo is not available or not configured correctly.');
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave('notices');
        if (page.props.auth.user) {
            window.Echo.leave(`private-App.Models.User.${page.props.auth.user.id}`);
            if (page.props.auth.user.roles && page.props.auth.user.roles.includes('accounts')) {
                // Corrected channel name from `private-private.accountant` to `private.accountant`
                window.Echo.leave(`private.accountant.${page.props.auth.user.id}`);
            }
        }
    }
});

// ----------------------------------------------------------------------------
// Handlers
// ----------------------------------------------------------------------------
const handleNotificationsClick = () => {
    showNotificationsDropdown.value = !showNotificationsDropdown.value;

    // Attempt to play the sound. This will be the user interaction that
    // enables autoplay.
    if (notificationSound.value) {
        notificationSound.value.play()
            .then(() => {
                isSoundEnabled.value = true;
                showSoundNotice.value = false;
                console.log('Audio playback permission granted and sound enabled.');
            })
            .catch(e => {
                console.warn('Audio playback was blocked by browser policy:', e);
            });
    }

    if (showNotificationsDropdown.value && unreadNotificationsCount.value > 0) {
        router.post(route('notifications.markAsRead'), {}, {
            onSuccess: () => {
                unreadNotificationsCount.value = 0;
            },
            onError: (errors) => {
                console.error('Failed to mark notifications as read:', errors);
            },
        });
    }
};

const markAllAsRead = () => {
    router.post(route('notifications.markAllAsRead'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            unreadNotificationsCount.value = 0;
            notifications.value.forEach(notification => notification.read_at = notification.read_at || new Date().toISOString());
        },
        onError: (errors) => {
            console.error('Failed to mark all notifications as read:', errors);
            },
        });
};

const logout = () => {
    router.post(route('logout'));
};

const confirmLogout = () => {
    Swal.fire({
        title: 'Are you sure?',
        text: "You will be logged out of your session.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, log me out!',
    }).then((result) => {
        if (result.isConfirmed) logout();
    });
};

// ----------------------------------------------------------------------------
// Role-based Computeds (unchanged)
// ----------------------------------------------------------------------------
const isAdmin = computed(() => page.props.auth.user?.roles?.map(r => r.toLowerCase()).includes('admin'));
const isAccounts = computed(() => page.props.auth.user?.roles?.map(r => r.toLowerCase()).includes('accounts'));
const isTeacher = computed(() => page.props.auth.user?.roles?.map(r => r.toLowerCase()).includes('teacher'));
const isStudent = computed(() => !isAdmin.value && !isAccounts.value && !isTeacher.value);
const user = computed(() => page.props.auth.user);
const getDashboardRoute = computed(() => {
    if (isAdmin.value) return route('dashboard');
    if (isAccounts.value) return route('accounts.dashboard');
    if (isTeacher.value) return route('teacher.dashboard');
    if (isStudent.value) return route('student.dashboard');
    return route('dashboard');
});
</script>

<template>
    <!-- Main layout container with Inter font -->
    <!-- The `dark:bg-gray-900` on this div will apply to the entire page background -->
    <div class="min-h-screen bg-white dark:bg-black text-black dark:text-white">
        
    <!-- Navigation Bar -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm dark:bg-gray-800 dark:border-gray-700">
        <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <!-- Logo and App Name -->
                <div class="flex items-center">
                    <div class="shrink-0 flex items-center">
                        <Link :href="getDashboardRoute">
                            <span class="text-xl font-extrabold text-gray-800 tracking-tight dark:text-gray-100">School-Mate</span>
                        </Link>
                    </div>
                    <div class="ml-4 text-xl font-extrabold text-gray-800 hidden sm:block tracking-tight dark:text-gray-100">
                        School Management System
                    </div>
                </div>










                <!-- User Dropdown (right side) -->
                <div class="flex items-center ms-6">
                    <!-- Dark Mode Toggle Button -->
                    <button @click="toggleTheme" class="flex-shrink-0 p-2 rounded-full text-gray-700 hover:text-gray-900 hover:bg-gray-100 focus:outline-none transition duration-150 ease-in-out dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-700">
                        <i :class="isDark ? 'fa-solid fa-sun' : 'fa-solid fa-moon'" class="h-5 w-5"></i>
                    </button>

                    <!-- Notification Icon and Dropdown Trigger -->
                    <div class="relative">
                        <button @click="handleNotificationsClick" class="flex-shrink-0 p-2 rounded-full text-gray-700 hover:text-gray-900 hover:bg-gray-100 focus:outline-none transition duration-150 ease-in-out dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-700">
                            <i class="fa-solid fa-bell h-5 w-5"></i>
                            <span v-if="unreadNotificationsCount > 0" class="absolute top-1 right-1 h-4 w-4 flex items-center justify-center bg-red-500 text-white text-xs font-bold rounded-full transform translate-x-1/2 -translate-y-1/2">
                                {{ unreadNotificationsCount }}
                            </span>
                        </button>
                        
                        <!-- Notification Dropdown -->
                        <div
                            v-if="showNotificationsDropdown"
                            @click.away="showNotificationsDropdown = false"
                            class="absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50 dark:bg-gray-700 dark:text-gray-100"
                        >
                            <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="notification-menu">
                                <!-- Dropdown Header -->
                                <div class="flex items-center justify-between px-4 py-2 border-b border-gray-200 dark:border-gray-600">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Notifications</h3>
                                    <button @click.stop="markAllAsRead" v-if="unreadNotificationsCount > 0" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        Mark all as read
                                    </button>
                                </div>

                                <!-- Notification List -->
                                <div class="max-h-80 overflow-y-auto">
                                    <!-- If there are no notifications -->
                                    <div v-if="notifications.length === 0" class="py-4 px-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                                        You're all caught up!
                                    </div>
                                    <!-- Loop through the notifications array -->
                                    <div v-for="notification in notifications" :key="notification.id" class="flex items-center px-4 py-3 border-b border-gray-200 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-600 cursor-pointer transition-colors duration-150 ease-in-out">
                                        <!-- Notification Icon -->
                                        <div class="flex-shrink-0 me-3">
                                            <i
                                                v-if="notification.type === 'notice'"
                                                class="fa-solid fa-bullhorn text-blue-500 w-5 h-5 flex items-center justify-center rounded-full"
                                            ></i>
                                            <i
                                                v-else-if="notification.type === 'invoice'"
                                                class="fa-solid fa-file-invoice-dollar text-green-500 w-5 h-5 flex items-center justify-center rounded-full"
                                            ></i>
                                            <i
                                                v-else
                                                class="fa-solid fa-bell text-gray-400 w-5 h-5 flex items-center justify-center rounded-full"
                                            ></i>
                                        </div>
                                        <!-- Notification Content -->
                                        <div class="flex-grow">
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ notification.message }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ notification.created_at }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- View All link -->
                                <div class="block px-4 py-2 text-center text-sm text-indigo-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-150 ease-in-out">
                                    <Link :href="route('notifications.index')" class="dark:text-indigo-400">
                                        View All
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div class="relative">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <span class="inline-flex rounded-md">
                                    <button
                                        type="button"
                                class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:text-gray-900 focus:outline-none transition ease-in-out duration-150 dark:bg-gray-800 dark:text-gray-300 dark:hover:text-gray-100"
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
                                <DropdownLink :href="route('profile.edit')" class="dark:text-gray-100 dark:hover:bg-gray-700"> Profile </DropdownLink>
                                <DropdownLink @click.prevent="confirmLogout" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                    Log Out
                                </DropdownLink>

                                
                            </template>
                        </Dropdown>
                    </div>
                </div>














                <!-- Mobile hamburger button -->
                <div class="-me-2 flex items-center md:hidden">
                    <button
                        @click="showingNavigationDropdown = !showingNavigationDropdown"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out dark:text-gray-400 dark:hover:text-gray-300 dark:hover:bg-gray-700"
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
            
        <!-- Responsive Navigation Menu (Mobile) -->
        <div :class="{ 'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown }" class="md:hidden bg-white dark:bg-gray-800">
            <div class="pt-2 pb-3 space-y-1">
                <!-- ADMIN PANEL (MOBILE) -->
                <div v-if="isAdmin" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 px-3 dark:text-gray-400">Admin Panel</h3>
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-gauge-high me-2 w-5"></i>Dashboard
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('users.index')" :active="route().current('users.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-users me-2 w-5"></i>User Management
                    </ResponsiveNavLink>

                    <div class="relative">
                        <button
                            @click="showAcademicDropdownMobile = !showAcademicDropdownMobile"
                            class="flex items-center justify-between w-full text-start py-2 px-3 text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out rounded-md font-medium dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <i class="fa-solid fa-graduation-cap me-2 w-5"></i>Academic Management
                            <svg
                                class="ms-2 h-4 w-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': showAcademicDropdownMobile }"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="showAcademicDropdownMobile" class="ps-4 mt-2 space-y-1 bg-white dark:bg-gray-800">
                            <ResponsiveNavLink :href="route('sections.index')" :active="route().current('sections.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-layer-group me-2 w-5"></i>Section Management
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('sessions.index')" :active="route().current('sessions.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-calendar-alt me-2 w-5"></i>Session Management
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('subjects.index')" :active="route().current('subjects.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-book me-2 w-5"></i>Subjects
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('class-subjects.index')" :active="route().current('class-subjects.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-book-open me-2 w-5"></i>Class Subjects
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('class-names.index')" :active="route().current('class-names.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-chalkboard me-2 w-5"></i>Class Names
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('timetable.index')" :active="route().current('timetable.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-calendar-days me-2 w-5"></i>Class Time Table
                            </ResponsiveNavLink>
                        </div>
                    </div>

                    <div class="relative">
                        <button
                            @click="showExamDropdownMobile = !showExamDropdownMobile"
                            class="flex items-center justify-between w-full text-start py-2 px-3 text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out rounded-md font-medium dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <i class="fa-solid fa-file-alt me-2 w-5"></i>Exam Management
                            <svg
                                class="ms-2 h-4 w-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': showExamDropdownMobile }"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="showExamDropdownMobile" class="ps-4 mt-2 space-y-1 bg-white dark:bg-gray-800">
                            <ResponsiveNavLink :href="route('exams.index')" :active="route().current('exams.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-clipboard-list me-2 w-5"></i>Exam Name
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('exam-schedules.index')" :active="route().current('exam-schedules.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-calendar-check me-2 w-5"></i>Exam Schedules
                            </ResponsiveNavLink>
                        </div>
                    </div>

                    <div class="relative">
                        <button
                            @click="showLibraryDropdownMobile = !showLibraryDropdownMobile"
                            class="flex items-center justify-between w-full text-start py-2 px-3 text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out rounded-md font-medium dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <i class="fa-solid fa-book-reader me-2 w-5"></i>Library Management
                            <svg
                                class="ms-2 h-4 w-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': showLibraryDropdownMobile }"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="showLibraryDropdownMobile" class="ps-4 mt-2 space-y-1 bg-white dark:bg-gray-800">
                            <ResponsiveNavLink :href="route('books.index')" :active="route().current('books.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-book-open-reader me-2 w-5"></i>Books
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('borrow-records.index')" :active="route().current('borrow-books.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-handshake me-2 w-5"></i>Borrow Records
                            </ResponsiveNavLink>
                        </div>
                    </div>

                    <ResponsiveNavLink :href="route('bus-schedules.index')" :active="route().current('bus-schedules.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-bus me-2 w-5"></i>Bus Schedules
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('teachers.index')" :active="route().current('teachers.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-chalkboard-user me-2 w-5"></i>Teacher Management
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('students.index')" :active="route().current('students.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-user-graduate me-2 w-5"></i>Student Management
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('attendance.index')" :active="route().current('attendance.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-clipboard-user me-2 w-5"></i>Student Attendance
                    </ResponsiveNavLink>
                </div>

                <div v-if="isAccounts" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 px-3 dark:text-gray-400">Accounts Panel</h3>
                    <ResponsiveNavLink :href="route('accounts.dashboard')" :active="route().current('accounts.dashboard')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-gauge-high me-2 w-5"></i>Dashboard
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('students.index')" :active="route().current('students.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-user-graduate me-2 w-5"></i>Student Management
                    </ResponsiveNavLink>
                </div>

                <div v-if="isTeacher && !isAdmin" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 px-3 dark:text-gray-400">Teacher Tools</h3>
                    <ResponsiveNavLink :href="route('teacher.dashboard')" :active="route().current('teacher.dashboard')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-gauge-high me-2 w-5"></i>Teacher Dashboard
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('my-classes.index')" :active="route().current('my-classes.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-calendar-days me-2 w-5"></i>My Class Timetable
                    </ResponsiveNavLink>
                </div>

                <div v-if="isStudent" class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                    <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 px-3 dark:text-gray-400">Student Tools</h3>
                    <ResponsiveNavLink :href="route('student.dashboard')" :active="route().current('student.dashboard')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-gauge-high me-2 w-5"></i>My Dashboard
                    </ResponsiveNavLink>
                    <div class="relative">
                        <button
                            @click="showLibraryDropdownMobile = !showLibraryDropdownMobile"
                            class="flex items-center justify-between w-full text-start py-2 px-3 text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out rounded-md font-medium dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <i class="fa-solid fa-book-reader me-2 w-5"></i>Student Library
                            <svg
                                class="ms-2 h-4 w-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': showLibraryDropdownMobile }"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="showLibraryDropdownMobile" class="ps-4 mt-2 space-y-1 bg-white dark:bg-gray-800">
                            <ResponsiveNavLink :href="route('student.books.index')" :active="route().current('student.books.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-book-open-reader me-2 w-5"></i>Available Books
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('student.books.my-borrowed')" :active="route().current('student.books.my-borrowed')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-handshake me-2 w-5"></i>My Borrowed Books
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-700">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800 dark:text-gray-100">
                        {{ $page.props.auth.user.name }}
                    </div>
                    <div class="font-medium text-sm text-gray-500 dark:text-gray-400">
                        {{ $page.props.auth.user.email }}
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    <ResponsiveNavLink :href="route('profile.edit')" class="dark:text-gray-100 dark:hover:bg-gray-700"> Profile </ResponsiveNavLink>
                    <ResponsiveNavLink @click.prevent="confirmLogout" class="dark:text-gray-100 dark:hover:bg-gray-700"> Log Out </ResponsiveNavLink>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex flex-1">
        <!-- Desktop Sidebar -->
        <aside :class="{ 'hidden': !isAdmin && !isTeacher && !isAccounts && !isStudent, 'block': isAdmin || isTeacher || isAccounts || isStudent }"
                class="fixed inset-y-0 left-0 bg-white text-gray-800 w-64 p-4 z-30 overflow-y-auto
                        md:block md:static md:shadow-md md:z-auto transition-all duration-300 ease-in-out dark:bg-gray-800 dark:text-gray-100">
            <div class="space-y-4">
                <!-- ADMIN PANEL (DESKTOP) -->
                <div v-if="isAdmin">
                    <h3 class="text-xs uppercase font-semibold text-gray-500 mb-5 dark:text-gray-400">Admin Panel</h3>
                    <NavLink :href="route('dashboard')" :active="route().current('dashboard')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-gauge-high me-2 w-5"></i>Admin Dashboard Panel
                    </NavLink>
                    <NavLink :href="route('users.index')" :active="route().current('users.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-users me-2 w-5"></i>Users Management
                    </NavLink>
                    <div class="relative">
                        <button
                            @click="showAcademicDropdown = !showAcademicDropdown"
                            class="flex items-center justify-between w-full text-start py-2 px-3 text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out rounded-md font-medium dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <i class="fa-solid fa-graduation-cap me-2 w-5"></i>Academic
                            <svg
                                class="ms-2 h-4 w-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': showAcademicDropdown }"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <!-- Added dark:bg-gray-800 to the dropdown content div -->
                        <div v-show="showAcademicDropdown" class="ps-4 mt-2 space-y-1 bg-white dark:bg-gray-800">
                            <NavLink :href="route('sections.index')" :active="route().current('sections.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-layer-group me-2 w-5"></i>Section Management
                            </NavLink>
                            <NavLink :href="route('sessions.index')" :active="route().current('sessions.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-calendar-alt me-2 w-5"></i>Session Management
                            </NavLink>
                            <NavLink :href="route('groups.index')" :active="route().current('groups.index.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-users-viewfinder me-2 w-5"></i>Group
                            </NavLink>
                            <NavLink :href="route('subjects.index')" :active="route().current('subjects.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-book me-2 w-5"></i>Subjects
                            </NavLink>
                            <NavLink :href="route('class-subjects.index')" :active="route().current('class-subjects.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-book-open me-2 w-5"></i>Class Subjects
                            </NavLink>
                            <NavLink :href="route('class-names.index')" :active="route().current('class-names.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-chalkboard me-2 w-5"></i>Class Names
                            </NavLink>
                            <NavLink :href="route('class-time-slots.index')" :active="route().current('class-time-slots.index.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-calendar-days me-2 w-5"></i>Class Time Slot
                            </NavLink>
                            <NavLink :href="route('timetable.index')" :active="route().current('timetable.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-calendar-days me-2 w-5"></i>Class Time Table
                            </NavLink>
                            <NavLink :href="route('exams.index')" :active="route().current('exams.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-clipboard-list me-2 w-5"></i>Exam Name
                            </NavLink>
                            <NavLink :href="route('exam-schedules.index')" :active="route().current('exam-schedules.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-calendar-check me-2 w-5"></i>Exam Schedules
                            </NavLink>
                            <NavLink :href="route('marks.index')" :active="route().current('marks.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-marker me-2 w-5"></i>Marks
                            </NavLink>
                        </div>
                    </div>

                    <div class="relative">
                        <button
                            @click="showLibraryDropdown = !showLibraryDropdown"
                            class="flex items-center justify-between w-full text-start py-2 px-3 text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out rounded-md font-medium dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <i class="fa-solid fa-book-reader me-2 w-5"></i>Library Management
                            <svg
                                class="ms-2 h-4 w-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': showLibraryDropdown }"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <!-- Added dark:bg-gray-800 to the dropdown content div -->
                        <div v-show="showLibraryDropdown" class="ps-4 mt-2 space-y-1 bg-white dark:bg-gray-800">
                            <NavLink :href="route('books.index')" :active="route().current('books.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-book-open-reader me-2 w-5"></i>Books
                            </NavLink>
                            <NavLink :href="route('borrow-records.index')" :active="route().current('borrow-books.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-handshake me-2 w-5"></i>Borrow Records
                            </NavLink>
                        </div>
                    </div>

                    <NavLink :href="route('bus-schedules.index')" :active="route().current('bus-schedules.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-bus me-2 w-5"></i>Bus Schedules
                    </NavLink>
                    
                    <NavLink :href="route('notices.index')" :active="route().current('notices.index.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-bell me-2 w-5"></i>Notice Board
                    </NavLink>
                    
                    <NavLink :href="route('teachers.index')" :active="route().current('teachers.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-chalkboard-user me-2 w-5"></i>Teacher Management
                    </NavLink>
                    <NavLink :href="route('students.index')" :active="route().current('students.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-user-graduate me-2 w-5"></i>Student Management
                    </NavLink>
                    <NavLink :href="route('attendance.index')" :active="route().current('attendance.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-clipboard-user me-2 w-5"></i>Student Attendance
                    </NavLink>
                    <NavLink :href="route('grade-configurations.index')" :active="route().current('grade-configurations.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-star me-2 w-5"></i>Grade System
                    </NavLink>
                    <NavLink :href="route('results.show')" :active="route().current('results.show')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-chart-bar me-2 w-5"></i>Result
                    </NavLink>

                    <NavLink :href="route('students.passed')" :active="route().current('students.passed')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-chart-bar me-2 w-5"></i>Passed Student 
                    </NavLink>
                    
                    <NavLink :href="route('settings.index')" :active="route().current('settings.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-chart-bar me-2 w-5"></i>Setting
                    </NavLink>
                </div>

                <!-- ACCOUNTS PANEL (DESKTOP) -->
                <div v-if="isAccounts" class="mt-6">
                    <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 px-3 dark:text-gray-400">Accounts Panel</h3>
                    <NavLink :href="route('accounts.dashboard')" :active="route().current('accounts.dashboard')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-gauge-high me-2 w-5"></i>Dashboard
                    </NavLink>

                    <NavLink :href="route('fee-types.index')" :active="route().current('fee-types.index.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-money-bill-wave me-2 w-5"></i>Fee Types
                    </NavLink>

                    <NavLink :href="route('class-fee-structures.index')" :active="route().current('class-fee-structures.index.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-hand-holding-dollar me-2 w-5"></i>Class Fee Structure
                    </NavLink>

                    <NavLink :href="route('student-fee-assignments.index')" :active="route().current('student-fee-assignments.index.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-file-invoice-dollar me-2 w-5"></i>Student Fee assignment
                    </NavLink>

                    <NavLink :href="route('admin.invoices.index')" :active="route().current('admin.invoices.index.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-receipt me-2 w-5"></i>Invoice
                    </NavLink>

                    <NavLink :href="route('admin.payments.pending')" :active="route().current('admin.payments.pending.*')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-receipt me-2 w-5"></i>Pending Payments
                    </NavLink>
                    
                    <NavLink :href="route('students.index')" :active="route().current('students.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-user-graduate me-2 w-5"></i>Student Management
                    </NavLink>

                </div>

                <!-- TEACHER TOOLS (DESKTOP) -->
                <div v-if="isTeacher && !isAdmin" class="mt-6">
                    <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 px-3 dark:text-gray-400">Teacher Tools</h3>
                    <NavLink :href="route('teacher.dashboard')" :active="route().current('teacher.dashboard')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-gauge-high me-2 w-5"></i>Teacher Dashboard
                    </NavLink>
                    
                    <NavLink :href="route('my-classes.index')" :active="route().current('my-classes.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-calendar-days me-2 w-5"></i>My Class Timetable
                    </NavLink>

                    <NavLink :href="route('teacher.my-notices')" :active="route().current('teacher.my-notices')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-bell me-2 w-5"></i>My Notice Board
                    </NavLink>

                    <NavLink :href="route('teachermarks.index')" :active="route().current('teachermarks.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-marker me-2 w-5"></i>Marks
                    </NavLink>
                    
                    <NavLink :href="route('teacherattendance.index')" :active="route().current('teacherattendance.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-clipboard-user me-2 w-5"></i>Teacher Attendance
                    </NavLink>
                </div>

                <!-- STUDENT TOOLS (DESKTOP) -->
                <div v-if="isStudent" class="mt-6">
                    <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 px-3 dark:text-gray-400">Student Tools</h3>
                    <NavLink :href="route('student.dashboard')" :active="route().current('student.dashboard')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                        <i class="fa-solid fa-gauge-high me-2 w-5"></i>My Dashboard
                    </NavLink>

                    <div class="relative">
                        <button
                            @click="showLibraryDropdown = !showLibraryDropdown"
                            class="flex items-center justify-between w-full text-start py-2 px-3 text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out rounded-md font-medium dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            <i class="fa-solid fa-book-reader me-2 w-5"></i>Student Library
                            <svg
                                class="ms-2 h-4 w-4 transform transition-transform duration-200"
                                :class="{ 'rotate-180': showLibraryDropdown }"
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 20 20"
                                fill="currentColor"
                            >
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <!-- Added dark:bg-gray-800 to the dropdown content div -->
                        <div v-show="showLibraryDropdown" class="ps-4 mt-2 space-y-1 bg-white dark:bg-gray-800">
                            <NavLink :href="route('student.books.index')" :active="route().current('student.books.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-book-open-reader me-2 w-5"></i>Available Books
                            </NavLink>
                            <NavLink :href="route('student.books.my-borrowed')" :active="route().current('student.books.my-borrowed')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                                <i class="fa-solid fa-handshake me-2 w-5"></i>My Borrowed Books
                            </NavLink>
                        </div>

                        <NavLink :href="route('student.invoices.index')" :active="route().current('student.invoices.index')" class="dark:text-gray-100 dark:hover:bg-gray-700">
                            <i class="fa-solid fa-gauge-high me-2 w-5"></i>My Invoice
                        </NavLink>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <!-- This main tag will also get the dark background -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 bg-gray-100 dark:bg-gray-900">
            <slot />
        </main>
    </div>

    </div>
</template>

<style scoped>
/*
    Since we can't use the NavLink component,
    this style block defines the classes for
    consistent padding, color, and transitions.
*/
.nav-link-base {
    display: flex;
    align-items: center;
    padding-top: 0.5rem;
    padding-bottom: 0.5rem;
    padding-left: 0.75rem; /* px-3 */
    padding-right: 0.75rem; /* px-3 */
    color: #4a5568; /* gray-700 */
    transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
    transition-duration: 150ms;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    border-radius: 0.375rem; /* rounded-md */
    font-weight: 500;
}
.nav-link-base:hover {
    background-color: #f7fafc; /* gray-100 */
}
.dark .nav-link-base {
    color: #e2e8f0; /* gray-100 */
}
.dark .nav-link-base:hover {
    background-color: #4a5568; /* gray-700 */
}
</style>
