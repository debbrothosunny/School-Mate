<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

const page = usePage();

// Navigation dropdown states
const showingNavigationDropdown = ref(false);
const showAcademicDropdown = ref(false);
const showExamDropdown = ref(false);
const showLibraryDropdown = ref(false);
const showAcademicDropdownMobile = ref(false);
const showExamDropdownMobile = ref(false);
const showLibraryDropdownMobile = ref(false);
const showUserDropdown = ref(false);
const showSidebar = ref(false); // New: Toggle for mobile sidebar

// Notifications reactive state
const notifications = ref(page.props.auth.user.notifications || []);
const unreadNotificationsCount = ref(page.props.auth.user.unread_notifications_count || 0);
const showNotificationsDropdown = ref(false);

// Notification sound states
const notificationSound = ref(null);
const isSoundEnabled = ref(false);
const showSoundNotice = ref(false);
const notificationSoundFile = new URL('/sounds/notification.mp3', import.meta.url).href;

// Role-based computed properties
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.roles?.map(r => r.toLowerCase()).includes('admin'));
const isAccounts = computed(() => user.value?.roles?.map(r => r.toLowerCase()).includes('accounts'));
const isTeacher = computed(() => user.value?.roles?.map(r => r.toLowerCase()).includes('teacher'));
const isFrontDesk = computed(() => user.value?.roles?.map(r => r.toLowerCase()).includes('front-desk'));
const isStudent = computed(() => !isAdmin.value && !isAccounts.value && !isTeacher.value && !isFrontDesk.value);
const getDashboardRoute = computed(() => {
    if (isAdmin.value) return route('dashboard');
    if (isAccounts.value) return route('accounts.dashboard');
    if (isTeacher.value) return route('teacher.dashboard');
    if (isFrontDesk.value) return route('front-desk.dashboard');
    if (isStudent.value) return route('student.dashboard');
    return route('dashboard');
});

// Lifecycle hooks for realtime notifications
onMounted(() => {
    notificationSound.value = new Audio(notificationSoundFile);
    setTimeout(() => {
        if (!isSoundEnabled.value) showSoundNotice.value = true;
    }, 5000);
    if (window.Echo) {
        window.Echo.connector.pusher.connection.bind('connected', () => console.log('Echo connected'));
        window.Echo.connector.pusher.connection.bind('disconnected', () => console.error('Echo disconnected'));
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
                if (isSoundEnabled.value) notificationSound.value.play().catch(() => {});
            });
        if (user.value) {
            window.Echo.private(`App.Models.User.${user.value.id}`)
                .notification((payload) => {
                    notifications.value.unshift({
                        id: payload.invoice_id || payload.data?.id,
                        type: 'invoice',
                        message: payload.message,
                        data: payload,
                        created_at: payload.created_at,
                        read_at: null,
                    });
                    unreadNotificationsCount.value++;
                    if (isSoundEnabled.value) notificationSound.value.play().catch(() => {});
                });
            if (user.value.roles && user.value.roles.includes('accounts')) {
                window.Echo.private(`private.accountant.${user.value.id}`)
                    .listen('.student.payment.made', (e) => {
                        notifications.value.unshift({
                            id: `payment-${e.paymentId}`,
                            type: 'payment',
                            message: `New payment of ${e.amount} from student: ${e.studentName}`,
                            data: e,
                            created_at: new Date().toISOString(),
                            read_at: null,
                        });
                        unreadNotificationsCount.value++;
                        if (isSoundEnabled.value) notificationSound.value.play().catch(() => {});
                    });
            }
        }
    }
});

onUnmounted(() => {
    if (window.Echo) {
        window.Echo.leave('notices');
        if (user.value) {
            window.Echo.leave(`private-App.Models.User.${user.value.id}`);
            if (user.value.roles && user.value.roles.includes('accounts')) {
                window.Echo.leave(`private.accountant.${user.value.id}`);
            }
        }
    }
});

// Handlers
const handleNotificationsClick = () => {
    showNotificationsDropdown.value = !showNotificationsDropdown.value;
    if (notificationSound.value) {
        notificationSound.value.play()
            .then(() => {
                isSoundEnabled.value = true;
                showSoundNotice.value = false;
            }).catch(() => {});
    }
    if (showNotificationsDropdown.value && unreadNotificationsCount.value > 0) {
        router.post(route('notifications.markAsRead'), {}, {
            onSuccess: () => unreadNotificationsCount.value = 0,
        });
    }
};

const markAllAsRead = () => {
    router.post(route('notifications.markAllAsRead'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            unreadNotificationsCount.value = 0;
            notifications.value.forEach(n => {
                if (!n.read_at) n.read_at = new Date().toISOString();
            });
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
        confirmButtonColor: '#0083feff',
        cancelButtonColor: 'rgba(255, 0, 0, 1)',
        confirmButtonText: 'Yes, log me out!',
    }).then((result) => {
        if (result.isConfirmed) logout();
    });
};

const toggleDropdown = (dropdownRef) => {
    dropdownRef.value = !dropdownRef.value;
};

// New: Toggle sidebar for mobile
const toggleSidebar = () => {
    showSidebar.value = !showSidebar.value;
};

// Route watchers
const academicRoutes = [
    'sections.index', 'sections.create', 'sections.edit',
    'sessions.index', 'sessions.create', 'sessions.edit',
    'groups.index', 'groups.create', 'groups.edit',
    'subjects.index', 'subjects.create','subjects.edit',
    'class-subjects.index','class-subjects.create','class-subjects.edit',
    'class-names.index', 'class-names.create','class-names.edit',
    'class-time-slots.index', 'class-time-slots.create',
    'timetable.index', 'timetable.create',
    'exams.index', 'exams.edit',
    'exam-time-slots.index', 'exam-time-slots.create',
    'exam-schedules.index',
    'results.show',
    'attendance.report',
    'attendance.teachers.index',
];

const isAcademicRouteActive = computed(() =>
    academicRoutes.some(r => route().current(r))
);

watch(
    () => isAcademicRouteActive.value,
    (active) => {
        if (active) showAcademicDropdown.value = true;
    },
    { immediate: true }
);

const libraryRoutes = [
    'books.index', 'books.create', 'books.edit',
];

const withOutDropDownsRoutes = [
    'bus-schedules.index', 'bus-schedules.create', 'bus-schedules.edit','users.index',
    
];

const isLibraryRouteActive = computed(() =>
    libraryRoutes.some(r => route().current(r))
);

watch(
    () => isLibraryRouteActive.value,
    (active) => {
        if (active) showLibraryDropdown.value = true;
    },
    { immediate: true }
);
</script>

<template>
    <!-- Main layout container -->
    <div class="min-h-screen bg-white text-black">
        <!-- Navigation Bar -->


        <nav class="bg-white border-b border-gray-200 sticky top-0 z-50 shadow-md">
        <div class="max-w-9xl mx-auto px-8 sm:px-8 lg:px-8">
            <div class="flex items-center justify-between h-20">
            
            <!-- Left Side: Logo + Greeting (Only on Desktop) -->
            <div class="hidden lg:flex items-center space-x-6 flex-1">
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="flex-shrink-0">
                <img src="/images/logo.jpeg" alt="School Logo" class="h-12 w-auto">
                </a>


            
            </div>

            <!-- Mobile: Only Logo + Hamburger -->
            <div class="flex items-center lg:hidden">
                <button @click="toggleSidebar" class="p-2 text-gray-700 hover:bg-gray-100 rounded-lg">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                </svg>
                </button>
                <a href="{{ route('dashboard') }}" class="ml-3">
                <img src="/images/logo.jpeg" alt="School Logo" class="h-10 w-auto">
                </a>
            </div>

            <!-- Right Side: User Dropdown (Always Visible) -->
            <div class="flex items-center">
                <div class="relative">
                <button 
                    @click="showUserDropdown = !showUserDropdown"
                    class="flex items-center space-x-3 p-2 rounded-full hover:bg-gray-100 transition group"
                >
                    <div class="w-11 h-11 bg-gradient-to-br from-indigo-500 to-purple-600 text-white rounded-full flex items-center justify-center text-xl font-bold shadow-lg ring-4 ring-white">
                    {{ user.value?.name?.charAt(0).toUpperCase() || 'U' }}
                    </div>
                    <!-- Optional: Show name on desktop -->
                    <span class="hidden lg:block text-gray-700 font-medium">
                    {{ user.value?.name || 'User' }}
                    </span>
                </button>

                <!-- Dropdown Menu -->
                <div 
                    v-if="showUserDropdown" 
                    v-click-outside="() => showUserDropdown = false"
                    class="absolute right-0 mt-3 w-56 rounded-xl shadow-2xl bg-white ring-1 ring-black ring-opacity-5 overflow-hidden z-50 animate-in fade-in slide-in-from-top-2 duration-200"
                >
                    <div class="py-2">
                    <NavLink 
                        :href="route('profile.edit')" 
                        class="flex items-center gap-3 px-5 py-3 text-gray-700 hover:bg-indigo-50 transition"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Profile Settings
                    </NavLink>
                    
                    <hr class="border-gray-200">

                    <button 
                        @click="confirmLogout"
                        class="flex items-center gap-3 w-full text-left px-5 py-3 text-red-600 hover:bg-red-50 transition font-medium"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Log Out
                    </button>
                    </div>
                </div>
                </div>
            </div>
            </div>
        </div>
        </nav>



        <!-- Main content with sidebar -->
        <div class="flex min-h-screen">
            <!-- Sidebar -->
            
            <aside v-if="isAdmin || isAccounts || isTeacher || isFrontDesk || isStudent" 
                   class="fixed inset-y-0 left-0 w-64 sm:w-56 lg:w-64 bg-white shadow-md transform md:static md:transform-none transition-transform duration-300 z-40 overflow-y-auto"
                   :class="{ '-translate-x-full': !showSidebar, 'translate-x-0': showSidebar }">
                <div class="flex-1 space-y-2 p-4" style="font-family: 'Poppins', sans-serif;">
                    <!-- Close Button for Mobile -->
                    <button v-if="showSidebar" @click="toggleSidebar" class="md:hidden mb-4 p-2 focus:outline-none">
                        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <!-- Admin Links -->
                    <div v-if="isAdmin">
                        <NavLink :href="route('dashboard')" :active="route().current('dashboard')" class="flex items-center py- px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('dashboard'), 'text-black hover:bg-green-300': !route().current('dashboard') }">
                            <i class="fa-solid fa-gauge-high mr-2 w-6"></i> Admin Dashboard
                        </NavLink>
                        <NavLink :href="route('users.index')" :active="route().current('users.index')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('users.index'), 'text-black hover:bg-green-300': !route().current('users.index') }">
                            <i class="fa-solid fa-users mr-2 w-6"></i> Users Management
                        </NavLink>
                        <button @click="showAcademicDropdown = !showAcademicDropdown" class="w-full text-left py-2 px-4 text-sm text-gray-700 hover:bg-green-300 rounded-md transition flex items-center justify-between">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-graduation-cap w-6"></i> Academic</span>
                            <svg :class="{ 'rotate-180': showAcademicDropdown }" class="h-4 w-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="showAcademicDropdown" class="pl-4 space-y-1 bg-white">
                            <NavLink :href="route('class-names.index')" :active="route().current('class-names.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('class-names.*'), 'text-black hover:bg-green-300': !route().current('class-names.*') }">
                                <i class="fa-solid fa-chalkboard mr-2 w-6"></i> Class Names
                            </NavLink>
                            <NavLink :href="route('sections.index')" :active="route().current('sections.*')" :preserve-state="true" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('sections.*'), 'text-black hover:bg-green-300': !route().current('sections.*') }">
                                <i class="fa-solid fa-layer-group mr-2 w-6"></i> Section Management
                            </NavLink>
                            <NavLink :href="route('sessions.index')" :active="route().current('sessions.*')" :preserve-state="true" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('sessions.*'), 'text-black hover:bg-green-300': !route().current('sessions.*') }">
                                <i class="fa-solid fa-calendar-alt mr-2 w-6"></i> Session Management
                            </NavLink>
                            <NavLink :href="route('groups.index')" :active="route().current('groups.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('groups.*'), 'text-black hover:bg-green-300': !route().current('groups.*') }">
                                <i class="fa-solid fa-users-viewfinder mr-2 w-6"></i> Group
                            </NavLink>
                            <NavLink :href="route('subjects.index')" :active="route().current('subjects.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('subjects.*'), 'text-black hover:bg-green-300': !route().current('subjects.*') }">
                                <i class="fa-solid fa-book mr-2 w-6"></i> Subjects
                            </NavLink>
                            <NavLink :href="route('class-subjects.index')" :active="route().current('class-subjects.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('class-subjects.*'), 'text-black hover:bg-green-300': !route().current('class-subjects.*') }">
                                <i class="fa-solid fa-book-open mr-2 w-6"></i> Class Subjects
                            </NavLink>
                            <NavLink :href="route('class-time-slots.index')" :active="route().current('class-time-slots.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('class-time-slots.*'), 'text-black hover:bg-green-300': !route().current('class-time-slots.*') }">
                                <i class="fa-solid fa-calendar-days mr-2 w-6"></i> Class Time Slot
                            </NavLink>
                            <NavLink :href="route('timetable.index')" :active="route().current('timetable.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('timetable.*'), 'text-black hover:bg-green-300': !route().current('timetable.*') }">
                                <i class="fa-solid fa-calendar-days mr-2 w-6"></i> Class Routine
                            </NavLink>
                            <NavLink :href="route('exams.index')" :active="route().current('exams.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('exams.*'), 'text-black hover:bg-green-300': !route().current('exams.*') }">
                                <i class="fa-solid fa-clipboard-list mr-2 w-6"></i> Exam Name
                            </NavLink>
                            <NavLink :href="route('exam-time-slots.index')" :active="route().current('exam-time-slots.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('exam-time-slots.*'), 'text-black hover:bg-green-300': !route().current('exam-time-slots.*') }">
                                <i class="fa-solid fa-clipboard-list mr-2 w-6"></i> Exam Time Slot
                            </NavLink>
                            <NavLink :href="route('exam-schedules.index')" :active="route().current('exam-schedules.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('exam-schedules.*'), 'text-black hover:bg-green-300': !route().current('exam-schedules.*') }">
                                <i class="fa-solid fa-calendar-check mr-2 w-6"></i> Exam Schedules
                            </NavLink>
                            <NavLink :href="route('results.show')" :active="route().current('results.show.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('results.show.*'), 'text-black hover:bg-green-300': !route().current('results.show.*') }">
                                <i class="fa-solid fa-calendar-check mr-2 w-6"></i> Result
                            </NavLink>
                            <NavLink :href="route('attendance.report')" :active="route().current('attendance.report.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('attendance.report.*'), 'text-black hover:bg-green-300': !route().current('attendance.report.*') }">
                                <i class="fa-solid fa-calendar-check mr-2 w-6"></i> Student Attendance Report
                            </NavLink>
                            <NavLink :href="route('attendance.teachers.index')" :active="route().current('attendance.teachers.index.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('attendance.teachers.index.*'), 'text-black hover:bg-green-300': !route().current('attendance.teachers.index.*') }">
                                <i class="fa-solid fa-calendar-check mr-2 w-6"></i> Teacher Attendance
                            </NavLink>
                        </div>
                        <button @click="showLibraryDropdown = !showLibraryDropdown" class="w-full text-left py-2 px-4 text-sm text-gray-700 hover:bg-green-300 rounded-md transition flex items-center justify-between">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-book-open-reader w-6"></i> Library</span>
                            <svg :class="{ 'rotate-180': showLibraryDropdown }" class="h-4 w-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="showLibraryDropdown" class="pl-4 space-y-1 bg-white">
                            <NavLink :href="route('books.index')" :active="route().current('books.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('books.*'), 'text-black hover:bg-green-300': !route().current('books.*') }">
                                <i class="fa-solid fa-book-open-reader mr-2 w-6"></i> Books
                            </NavLink>
                        </div>
                        <NavLink :href="route('bus-schedules.index')" :active="route().current('bus-schedules.*')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('bus-schedules.*'), 'text-black hover:bg-green-300': !route().current('bus-schedules.*') }">
                            <i class="fa-solid fa-bus mr-2 w-6"></i> Bus Schedules
                        </NavLink>
                        <NavLink :href="route('notices.index')" :active="route().current('notices.index')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('notices.index'), 'text-black hover:bg-green-300': !route().current('notices.index') }">
                            <i class="fa-solid fa-chalkboard-user mr-2 w-6"></i> Notice
                        </NavLink>
                        <NavLink :href="route('teachers.index')" :active="route().current('teachers.index')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('teachers.index'), 'text-black hover:bg-green-300': !route().current('teachers.index') }">
                            <i class="fa-solid fa-chalkboard-user mr-2 w-6"></i> Teacher Management
                        </NavLink>
                        <NavLink :href="route('students.index')" :active="route().current('students.index')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('students.index'), 'text-black hover:bg-green-300': !route().current('students.index') }">
                            <i class="fa-solid fa-user-plus mr-2 w-6"></i> Student Management
                        </NavLink>
                        <NavLink :href="route('fee-types.index')" :active="route().current('fee-types.*')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('fee-types.*'), 'text-black hover:bg-green-300': !route().current('fee-types.*') }">
                            <i class="fa-solid fa-money-bill-wave mr-2 w-6"></i> Fee Types
                        </NavLink>
                        <NavLink :href="route('class-fee-structures.index')" :active="route().current('class-fee-structures.*')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('class-fee-structures.*'), 'text-black hover:bg-green-300': !route().current('class-fee-structures.*') }">
                            <i class="fa-solid fa-hand-holding-dollar mr-2 w-6"></i> Class Fee Structure
                        </NavLink>
                        <NavLink :href="route('student-fee-assignments.index')" :active="route().current('student-fee-assignments.*')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('student-fee-assignments.*'), 'text-black hover:bg-green-300': !route().current('student-fee-assignments.*') }">
                            <i class="fa-solid fa-file-invoice-dollar mr-2 w-6"></i> Student Fee Assignment
                        </NavLink>
                        <NavLink :href="route('admin.invoices.index')" :active="route().current('admin.invoices.*')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('admin.invoices.*'), 'text-black hover:bg-green-300': !route().current('admin.invoices.*') }">
                            <i class="fa-solid fa-receipt mr-2 w-6"></i> Invoice
                        </NavLink>
                        <NavLink :href="route('admin.payments.history')" :active="route().current('admin.payments.history.*')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('admin.payments.history.*'), 'text-black hover:bg-green-300': !route().current('admin.payments.history.*') }">
                            <i class="fa-solid fa-receipt mr-2 w-6"></i> Payment History
                        </NavLink>
                        <NavLink :href="route('grade-configurations.index')" :active="route().current('grade-configurations.*')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('grade-configurations.*'), 'text-black hover:bg-green-300': !route().current('grade-configurations.*') }">
                            <i class="fa-solid fa-star mr-2 w-6"></i> Grade System
                        </NavLink>
                        <NavLink :href="route('students.passed')" :active="route().current('students.passed')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('students.passed'), 'text-black hover:bg-green-300': !route().current('students.passed') }">
                            <i class="fa-solid fa-chart-bar mr-2 w-6"></i> Passed Student
                        </NavLink>
                        <NavLink :href="route('salaries.index')" :active="route().current('salaries.index')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('salaries.index'), 'text-black hover:bg-green-300': !route().current('salaries.index') }">
                            <i class="fa-solid fa-money-bill-wave mr-2 w-6"></i> Setup Salary
                        </NavLink>
                        <NavLink :href="route('salaries.payroll.index')" :active="route().current('salaries.payroll.index')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('salaries.payroll.index'), 'text-black hover:bg-green-300': !route().current('salaries.payroll.index') }">
                            <i class="fa-solid fa-money-check mr-2 w-6"></i> Make Salary
                        </NavLink>
                        <NavLink :href="route('settings.index')" :active="route().current('settings.index')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('settings.index'), 'text-black hover:bg-green-300': !route().current('settings.index') }">
                            <i class="fa-solid fa-gear mr-2 w-6"></i> Settings
                        </NavLink>
                    </div>
                    <!-- Accounts Links -->
                    <div v-if="isAccounts">
                        <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 mt-4">Accounts Panel</h3>
                        <NavLink :href="route('accounts.dashboard')" :active="route().current('accounts.dashboard')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('accounts.dashboard'), 'text-black hover:bg-green-300': !route().current('accounts.dashboard') }">
                            <i class="fa-solid fa-gauge-high mr-2 w-6"></i> Dashboard
                        </NavLink>
                        <NavLink :href="route('accountant.payments.collect')" :active="route().current('accountant.payments.collect.*')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('accountant.payments.collect.*'), 'text-black hover:bg-green-300': !route().current('accountant.payments.collect.*') }">
                            <i class="fa-solid fa-receipt mr-2 w-6"></i> Collect Payment
                        </NavLink>
                    </div>
                    <!-- Teacher Links -->
                    <div v-if="isTeacher && !isAdmin">
                        <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 mt-4">Teacher Tools</h3>
                        <NavLink :href="route('teacher.dashboard')" :active="route().current('teacher.dashboard')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('teacher.dashboard'), 'text-black hover:bg-green-300': !route().current('teacher.dashboard') }">
                            <i class="fa-solid fa-gauge-high mr-2 w-6"></i> Teacher Dashboard
                        </NavLink>
                        <NavLink :href="route('teacher.my-notices')" :active="route().current('teacher.my-notices')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('teacher.my-notices'), 'text-black hover:bg-green-300': !route().current('teacher.my-notices') }">
                            <i class="fa-solid fa-bell mr-2 w-6"></i> My Notice Board
                        </NavLink>
                        <NavLink :href="route('teachermarks.index')" :active="route().current('teachermarks.index')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('teachermarks.index'), 'text-black hover:bg-green-300': !route().current('teachermarks.index') }">
                            <i class="fa-solid fa-marker mr-2 w-6"></i> Marks
                        </NavLink>
                        <NavLink :href="route('teacherattendance.index')" :active="route().current('teacherattendance.index')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('teacherattendance.index'), 'text-black hover:bg-green-300': !route().current('teacherattendance.index') }">
                            <i class="fa-solid fa-clipboard-user mr-2 w-6"></i> Student Attendance
                        </NavLink>
                    </div>
                    <!-- Student Links -->
                    <div v-if="isStudent">
                        <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 mt-4">Student Tools</h3>
                        <NavLink :href="route('student.dashboard')" :active="route().current('student.dashboard')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('student.dashboard'), 'text-black hover:bg-green-300': !route().current('student.dashboard') }">
                            <i class="fa-solid fa-gauge-high mr-2 w-6"></i> My Dashboard
                        </NavLink>
                        <button @click="showLibraryDropdown = !showLibraryDropdown" class="w-full text-left py-2 px-4 text-sm text-gray-700 hover:bg-green-300 rounded-md transition flex items-center justify-between">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-book-reader w-6"></i> Student Library</span>
                            <svg :class="{ 'rotate-180': showLibraryDropdown }" class="h-4 w-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="showLibraryDropdown" class="pl-4 space-y-1 bg-white">
                            <NavLink :href="route('student.books.index')" :active="route().current('student.books.index')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('student.books.index'), 'text-black hover:bg-green-300': !route().current('student.books.index') }">
                                <i class="fa-solid fa-book-open-reader mr-2 w-6"></i> Available Books
                            </NavLink>
                            <NavLink :href="route('student.books.my-borrowed')" :active="route().current('student.books.my-borrowed')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('student.books.my-borrowed'), 'text-black hover:bg-green-300': !route().current('student.books.my-borrowed') }">
                                <i class="fa-solid fa-handshake mr-2 w-6"></i> My Borrowed Books
                            </NavLink>
                        </div>
                        <NavLink :href="route('student.results.index')" :active="route().current('student.results.index')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('student.results.index'), 'text-black hover:bg-green-300': !route().current('student.results.index') }">
                            <i class="fa-solid fa-gauge-high mr-2 w-6"></i> My Result
                        </NavLink>
                    </div>
                    <!-- FrontDesk Links -->
                    <div v-if="isFrontDesk">
                        <h3 class="text-xs uppercase font-semibold text-gray-500 mb-2 mt-4">Front Desk</h3>
                        <NavLink :href="route('front-desk.dashboard')" :active="route().current('front-desk.dashboard')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('front-desk.dashboard'), 'text-black hover:bg-green-300': !route().current('front-desk.dashboard') }">
                            <i class="fa-solid fa-house-user mr-2 w-6"></i> My Dashboard
                        </NavLink>
                        <NavLink :href="route('students.index')" :active="route().current('students.index')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('students.index'), 'text-black hover:bg-green-300': !route().current('students.index') }">
                            <i class="fa-solid fa-user-plus mr-2 w-6"></i> Admit Student
                        </NavLink>
                        <NavLink :href="route('teachers.index')" :active="route().current('teachers.index')" class="flex items-center py-2 px-4 rounded-md transition" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('teachers.index'), 'text-black hover:bg-green-300': !route().current('teachers.index') }">
                            <i class="fa-solid fa-chalkboard-user mr-2 w-6"></i> Teacher Manage
                        </NavLink>


                        <NavLink :href="route('attendance.teachers.index')" :active="route().current('attendance.teachers.index.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('attendance.teachers.index.*'), 'text-black hover:bg-green-300': !route().current('attendance.teachers.index.*') }">
                                <i class="fa-solid fa-calendar-check mr-2 w-6"></i> Teacher Attendance
                        </NavLink>
                        <button @click="showAcademicDropdown = !showAcademicDropdown" class="w-full text-left py-2 px-4 text-sm text-gray-700 hover:bg-green-300 rounded-md transition flex items-center justify-between">
                            <span class="flex items-center gap-2"><i class="fa-solid fa-graduation-cap w-6"></i> Academic</span>
                            <svg :class="{ 'rotate-180': showAcademicDropdown }" class="h-4 w-4 transition-transform" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <div v-show="showAcademicDropdown" class="pl-4 space-y-1 bg-white">
                            <NavLink :href="route('class-names.index')" :active="route().current('class-names.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('class-names.*'), 'text-black hover:bg-green-300': !route().current('class-names.*') }">
                                <i class="fa-solid fa-chalkboard mr-2 w-6"></i> Class Names
                            </NavLink>
                            <NavLink :href="route('sections.index')" :active="route().current('sections.*')" :preserve-state="true" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('sections.*'), 'text-black hover:bg-green-300': !route().current('sections.*') }">
                                <i class="fa-solid fa-layer-group mr-2 w-6"></i> Section Management
                            </NavLink>
                            <NavLink :href="route('sessions.index')" :active="route().current('sessions.*')" :preserve-state="true" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('sessions.*'), 'text-black hover:bg-green-300': !route().current('sessions.*') }">
                                <i class="fa-solid fa-calendar-alt mr-2 w-6"></i> Session Management
                            </NavLink>
                            <NavLink :href="route('groups.index')" :active="route().current('groups.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('groups.*'), 'text-black hover:bg-green-300': !route().current('groups.*') }">
                                <i class="fa-solid fa-users-viewfinder mr-2 w-6"></i> Group
                            </NavLink>
                            <NavLink :href="route('subjects.index')" :active="route().current('subjects.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('subjects.*'), 'text-black hover:bg-green-300': !route().current('subjects.*') }">
                                <i class="fa-solid fa-book mr-2 w-6"></i> Subjects
                            </NavLink>
                            <NavLink :href="route('class-subjects.index')" :active="route().current('class-subjects.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('class-subjects.*'), 'text-black hover:bg-green-300': !route().current('class-subjects.*') }">
                                <i class="fa-solid fa-book-open mr-2 w-6"></i> Class Subjects
                            </NavLink>
                            <NavLink :href="route('class-time-slots.index')" :active="route().current('class-time-slots.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('class-time-slots.*'), 'text-black hover:bg-green-300': !route().current('class-time-slots.*') }">
                                <i class="fa-solid fa-calendar-days mr-2 w-6"></i> Class Time Slot
                            </NavLink>
                            <NavLink :href="route('timetable.index')" :active="route().current('timetable.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('timetable.*'), 'text-black hover:bg-green-300': !route().current('timetable.*') }">
                                <i class="fa-solid fa-calendar-days mr-2 w-6"></i> Class Time Table
                            </NavLink>
                            <NavLink :href="route('exams.index')" :active="route().current('exams.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('exams.*'), 'text-black hover:bg-green-300': !route().current('exams.*') }">
                                <i class="fa-solid fa-clipboard-list mr-2 w-6"></i> Exam Name
                            </NavLink>
                            <NavLink :href="route('exam-time-slots.index')" :active="route().current('exam-time-slots.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('exam-time-slots.*'), 'text-black hover:bg-green-300': !route().current('exam-time-slots.*') }">
                                <i class="fa-solid fa-clipboard-list mr-2 w-6"></i> Exam Time Slot
                            </NavLink>
                            <NavLink :href="route('exam-schedules.index')" :active="route().current('exam-schedules.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('exam-schedules.*'), 'text-black hover:bg-green-300': !route().current('exam-schedules.*') }">
                                <i class="fa-solid fa-calendar-check mr-2 w-6"></i> Exam Schedules
                            </NavLink>
                            <NavLink :href="route('results.show')" :active="route().current('results.show.*')" class="flex items-center py-1 px-4 rounded-md text-sm" :class="{ 'bg-green-500 text-white pointer-events-none': route().current('results.show.*'), 'text-black hover:bg-green-300': !route().current('results.show.*') }">
                                <i class="fa-solid fa-calendar-check mr-2 w-6"></i> Result
                            </NavLink>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Overlay for Mobile Sidebar -->
            <div v-if="showSidebar" class="fixed inset-0 bg-black bg-opacity-50 md:hidden z-30" @click="toggleSidebar"></div>
            <!-- Main Content -->
            <main class="flex-1 p-4 sm:p-6 bg-gray-100 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>

<style scoped>
/* Beautiful gradient avatar */
.profile-avatar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

/* Smooth dropdown */
.animate-in {
  animation: fadeIn 0.2s ease-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

/* Mobile adjustments */
@media (max-width: 1023px) {
  .hidden lg\\:flex { display: none !important; }
  .hidden lg\\:block { display: none !important; }
}

</style>