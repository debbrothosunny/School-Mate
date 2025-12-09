<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    students: Object,
    classes: Array,
    setting: Object,
});

const flash = computed(() => usePage().props.flash || {});
const form = useForm({});
const showDeleteModal = ref(false);
const studentToDelete = ref(null);
const searchQuery = ref('');
const showIdCardModal = ref(false);
const showTransferCertModal = ref(false);
const selectedStudent = ref(null);
const showClassIdCardModal = ref(false);
const selectedClass = ref('');

// --- UX Improvements: Flash Message Timeout ---
const localFlash = ref({});
const flashTimeout = ref(null);
watch(flash, (newFlash) => {
    if (newFlash.message) {
        localFlash.value = { ...newFlash };
        if (flashTimeout.value) clearTimeout(flashTimeout.value);
        flashTimeout.value = setTimeout(() => {
            localFlash.value = {};
        }, 5000);
    }
}, { immediate: true, deep: true });

const clearFlash = () => {
    if (flashTimeout.value) clearTimeout(flashTimeout.value);
    localFlash.value = {};
};

const filteredStudents = computed(() => {
    if (!props.students.data) return [];
    if (searchQuery.value) {
        const lowerCaseQuery = searchQuery.value.toLowerCase();
        return props.students.data.filter(student => {
            const statusText = student.status === 0 ? 'active' : 'inactive';
            const feePaidText = student.admission_fee_paid ? 'paid' : 'unpaid';
            return (
                (student.name && student.name.toLowerCase().includes(lowerCaseQuery)) ||
                (student.admission_number && student.admission_number.toLowerCase().includes(lowerCaseQuery)) ||
                (student.roll_number !== null && student.roll_number.toString().includes(lowerCaseQuery)) ||
                (student.className?.class_name && student.className.class_name.toLowerCase().includes(lowerCaseQuery)) ||
                (student.age !== null && student.age.toString().includes(lowerCaseQuery)) ||
                (student.session?.name && student.session.name.toLowerCase().includes(lowerCaseQuery)) ||
                (student.group?.name && student.group.name.toLowerCase().includes(lowerCaseQuery)) ||
                (student.section?.name && student.section.name.toLowerCase().includes(lowerCaseQuery)) ||
                (student.parent_name && student.parent_name.toLowerCase().includes(lowerCaseQuery)) ||
                (student.contact && student.contact.toLowerCase().includes(lowerCaseQuery)) ||
                statusText.includes(lowerCaseQuery) ||
                (student.admission_fee_amount !== null && (student.admission_fee_amount / 100).toFixed(2).includes(lowerCaseQuery)) ||
                feePaidText.includes(lowerCaseQuery) ||
                (student.payment_method && student.payment_method.toLowerCase().includes(lowerCaseQuery)) ||
                (student.blood_group && student.blood_group.toLowerCase().includes(lowerCaseQuery))
            );
        });
    }
    return [...props.students.data];
});

const displayTotal = computed(() => {
    return searchQuery.value ? filteredStudents.value.length : (props.students.total || 0);
});

const confirmDelete = (student) => {
    studentToDelete.value = student;
    showDeleteModal.value = true;
};

const deleteStudent = () => {
    if (studentToDelete.value) {
        form.delete(route('students.destroy', studentToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => closeDeleteModal(),
            onError: (errors) => {
                console.error("Error deleting student:", errors);
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    studentToDelete.value = null;
};

const openIdCardModal = (student) => {
    console.log('Selected Student:', student);
    selectedStudent.value = { ...student };
    showIdCardModal.value = true;
};

const closeIdCardModal = () => {
    showIdCardModal.value = false;
    selectedStudent.value = null;
};

const openTransferCertModal = (student) => {
    selectedStudent.value = student;
    showTransferCertModal.value = true;
};

const closeTransferCertModal = () => {
    showTransferCertModal.value = false;
    selectedStudent.value = null;
};

const downloadFile = (url) => {
    const link = document.createElement('a');
    link.href = url;
    link.download = '';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const formatCurrency = (amountInPaisa) => {
    if (amountInPaisa === null || amountInPaisa === undefined) return 'N/A';
    return `BDT ${(amountInPaisa / 100).toFixed(2)}`;
};

const getStatusBadgeClass = (status) => {
    return status === 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
};

const FALLBACK_IMAGE_URL_TABLE = 'https://placehold.co/40x40/f3f4f6/9ca3af?text=Pic';
const handleImageError = (event) => {
    if (event.target.src !== FALLBACK_IMAGE_URL_TABLE) {
        event.target.src = FALLBACK_IMAGE_URL_TABLE;
    }
};

/* Updated Function – Now redirects to Laravel route */
const printClassIdCards = () => {
    if (!selectedClass.value?.id) {
        return alert('দয়া করে ক্লাস সিলেক্ট করুন!');
    }

    const classId = selectedClass.value.id;

    // এই URL টা সরাসরি ওপেন করুন — এটাই সবচেয়ে নিশ্চিত পদ্ধতি
    const downloadUrl = `/admin/students/print-class-idcards/${classId}`;

    // নতুন ট্যাবে ওপেন করুন — PDF অটো ডাউনলোড হবে
    const newWindow = window.open(downloadUrl, '_blank');

    // যদি পপআপ ব্লক হয় তাহলে ম্যানুয়ালি বলে দিন
    if (!newWindow || newWindow.closed || typeof newWindow.closed == 'undefined') {
        alert('পপআপ ব্লক হয়েছে! দয়া করে ব্রাউজারে পপআপ অনুমোদন করুন। তারপর আবার চেষ্টা করুন।');
    } else {
        // সফল হলে মেসেজ দেখান
        setTimeout(() => {
            alert('PDF ডাউনলোড শুরু হয়েছে! চেক করুন ডাউনলোড ফোল্ডার।');
        }, 1000);
    }

    closeClassIdCardModal();
};

/* Helper to close the class ID card modal */
const closeClassIdCardModal = () => {
    showClassIdCardModal.value = false;
    selectedClass.value = null;
};const openClassIdCardModal = () => {
    selectedClass.value = '';
    showClassIdCardModal.value = true;
};

</script>

<template>
    <Head title="Student Index" />
    <AuthenticatedLayout>

        <template #header>
            <h2 class="font-semibold text-xl md:text-2xl text-gray-800 leading-tight">Student Management</h2>
        </template>
        
        <div class="py-4 sm:py-6 lg:py-8 font-Montserrat">
            <div class="mx-auto max-w-9xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="p-4 sm:p-6">
                        <!-- Header Section -->
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                            <h3 class="text-lg md:text-3xl font-bold text-black-900">All Students ({{ displayTotal }})</h3>
                            <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                                <Link :href="route('students.create')">
                                    <PrimaryButton class="w-full sm:w-auto px-4 py-2 text-sm font-medium bg-blue-600 hover:bg-blue-700 rounded-md shadow-sm hover:shadow-md transition-all duration-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                        Add Student
                                    </PrimaryButton>
                                </Link>
                                <PrimaryButton @click="openClassIdCardModal" class="w-full sm:w-auto px-4 py-2 text-sm font-medium bg-emerald-600 hover:bg-emerald-700 rounded-md shadow-sm hover:shadow-md transition-all duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z" />
                                    </svg>
                                    Print Class ID Cards
                                </PrimaryButton>
                            </div>
                        </div>
                        <!-- Search Bar -->
                        <div class="mb-6">
                            <div class="relative">
                                <TextInput
                                    id="search"
                                    type="text"
                                    class="w-full px-4 py-2.5 text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    v-model="searchQuery"
                                    placeholder="Search by name, admission number, roll, class, or contact..."
                                />
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <button v-if="searchQuery" @click="searchQuery = ''" class="text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full p-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                    <svg v-else class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                            </div>
                        </div>
                        <!-- Flash Message -->
                        <div v-if="localFlash.message" class="mb-6 p-4 rounded-md text-sm font-medium flex justify-between items-center" :class="localFlash.type === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'">
                            <span>{{ localFlash.message }}</span>
                            <button @click="clearFlash" class="text-gray-500 hover:text-gray-700 focus:outline-none">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <!-- Student Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-700">
                                <thead class="bg-gray-100 text-gray-600 uppercase text-sm font-bold">
                                    <tr>
                                        <th class="px-4 py-3 w-16 text-center">Photo</th>
                                        <th class="px-4 py-3">Name</th>
                                        <th class="px-4 py-3 hidden sm:table-cell">Student ID</th>
                                        <th class="px-4 py-3 hidden md:table-cell">Roll</th>
                                        <th class="px-4 py-3 hidden lg:table-cell">Class/Sec.</th>
                                        <th class="px-4 py-3 hidden xl:table-cell">Guardian</th>
                                        <th class="px-4 py-3 hidden xl:table-cell">Contact</th>
                                        <th class="px-4 py-3 hidden 2xl:table-cell text-right">Fee</th>
                                        <th class="px-4 py-3 hidden 2xl:table-cell text-center">Fee Status</th>
                                        <th class="px-4 py-3 hidden lg:table-cell text-center">Status</th>
                                        <th class="px-4 py-3 text-center">ID Card</th>
                                        <th class="px-4 py-3 hidden md:table-cell text-center">Transfer Cert.</th>
                                        <th class="px-4 py-3 text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody v-if="filteredStudents.length">
                                    <tr v-for="student in filteredStudents" :key="student.id" class="border-b hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 text-center">
                                            <img
                                                v-if="student.image"
                                                :src="student.image_url || (student.image.startsWith('http') ? student.image : route('students.image.serve', { filename: student.image.split('/').pop() }))"
                                                alt="Student Photo"
                                                class="w-8 h-8 rounded-full object-cover mx-auto"
                                                @error="handleImageError"
                                            />
                                            <div v-else class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs text-gray-500 mx-auto">No Img</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium">{{ student.name }}</div>
                                            <div class="text-xs text-gray-500 sm:hidden">
                                                <div>Adm: {{ student.admission_number }}</div>
                                                <div>Roll: {{ student.roll_number }}</div>
                                                <div>{{ student.className?.class_name || 'N/A' }} {{ student.section?.name ? `/ ${student.section.name}` : '' }}</div>
                                                <div>Guardian: {{ student.parent_name }}</div>
                                                <div>Contact: {{ student.contact }}</div>
                                                <div>Fee: {{ formatCurrency(student.admission_fee_amount) }}</div>
                                                <div>Fee Status: {{ student.admission_fee_paid ? 'Paid' : 'Unpaid' }}</div>
                                                <div>Status: {{ student.status === 0 ? 'Active' : 'Inactive' }}</div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 hidden sm:table-cell">{{ student.admission_number }}</td>
                                        <td class="px-4 py-3 hidden md:table-cell">{{ student.roll_number }}</td>
                                        <td class="px-4 py-3 hidden lg:table-cell">{{ student.class_name?.class_name || 'N/A' }} {{ student.section?.name ? `/ ${student.section.name}` : '' }}</td>
                                        <td class="px-4 py-3 hidden xl:table-cell">{{ student.parent_name }}</td>
                                        <td class="px-4 py-3 hidden xl:table-cell">{{ student.contact }}</td>
                                        <td class="px-4 py-3 hidden 2xl:table-cell text-right">{{ formatCurrency(student.admission_fee_amount) }}</td>
                                        <td class="px-4 py-3 hidden 2xl:table-cell text-center">
                                            <span :class="`px-2 py-1 text-xs font-medium rounded-full ${student.admission_fee_paid ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`">
                                                {{ student.admission_fee_paid ? 'Paid' : 'Unpaid' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 hidden lg:table-cell text-center">
                                            <span :class="`px-2 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(student.status)}`">
                                                {{ student.status === 0 ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button @click="openIdCardModal(student)" class="text-blue-600 hover:text-blue-800 hover:scale-110 transition-all duration-200" title="View ID Card">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                                                    <path fill-rule="evenodd" d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 text-center hidden md:table-cell">
                                            <button @click="openTransferCertModal(student)" class="text-blue-600 hover:text-blue-800 hover:scale-110 transition-all duration-200" title="View Transfer Certificate">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 013.75 3.75v16.5a3.75 3.75 0 01-3.75 3.75H5.625a2.25 2.25 0 01-2.25-2.25V3.75a2.25 2.25 0 012.25-2.25zm7.5 0H19.5a2.25 2.25 0 012.25 2.25v16.5a2.25 2.25 0 01-2.25 2.25H13.125a3.75 3.75 0 01-3.75-3.75V5.25A3.75 3.75 0 0113.125 1.5zm-10.5 8.25h16.5a.75.75 0 01.75.75v8.25a.75.75 0 01-.75.75H2.625a.75.75 0 01-.75-.75V10.5a.75.75 0 01.75-.75zm0 3.75a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0v-2.25a.75.75 0 01.75-.75zm13.5 0a.75.75 0 01.75.75v2.25a.75.75 0 01-1.5 0v-2.25a.75.75 0 01.75-.75z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex justify-center gap-2">
                                                <Link :href="route('students.history', student.id)" class="text-blue-600 hover:text-blue-800 text-xs font-medium hover:underline">History</Link>
                                                <Link :href="route('students.edit', student.id)" class="text-blue-600 hover:text-blue-800 text-xs font-medium hover:underline">Edit</Link>
                                                
                                                <button disabled class="text-gray-400 dark:text-gray-600 text-xs font-medium cursor-not-allowed opacity-60" title="Student deletion is disabled">
                                                Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                </tbody>
                                <tbody v-else>
                                    <tr>
                                        <td colspan="13" class="py-8 text-center text-gray-500">
                                            <div class="text-lg font-medium">No students found</div>
                                            <div class="text-sm mt-1">Try adjusting your search or add a new student.</div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Pagination -->
                        <div class="mt-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                            <span class="text-sm text-gray-600">Showing {{ filteredStudents.length }} of {{ displayTotal }} students</span>
                            <div class="flex gap-2" v-if="!searchQuery && students.links">
                                <Link
                                    v-for="link in students.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-2 text-sm font-medium rounded-md transition-colors',
                                        link.active ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-lg">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Confirm Deletion</h3>
                    <button @click="closeDeleteModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <p class="text-sm text-gray-600 mb-6">
                    Are you sure you want to delete <span class="font-medium text-blue-600">{{ studentToDelete?.name }}</span>? This action cannot be undone.
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">Cancel</button>
                    <DangerButton @click="deleteStudent" :disabled="form.processing" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">Delete</DangerButton>
                </div>
            </div>
        </div>





       <!-- ID Card Modal -->

      <div v-if="showIdCardModal" class="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-sm w-full overflow-hidden">

          <!-- Close Button -->
          <div class="flex justify-end p-5">
            <button @click="closeIdCardModal" class="text-gray-400 hover:text-gray-600 transition">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>

        <!-- ID Card – Premium Orange + Golden Emblem -->
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
            <div class="relative w-full max-w-sm rounded-xl bg-white shadow-2xl overflow-hidden transform transition-all scale-100 opacity-100">

                <button class="absolute top-4 right-4 z-30 text-gray-400 hover:text-gray-600 focus:outline-none">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <div class="relative bg-gradient-to-br from-blue-900 to-blue-900 pb-20 pt-12 overflow-hidden" 
                    style="clip-path: polygon(0 0, 100% 0, 100% 80%, 50% 100%, 0 80%);">
                    
                    <div class="relative z-10 flex flex-col items-center">
                        
                        <div class="relative mb-3">
                            
                        <img
                            :src="props.setting?.school_logo_url ||
                                (props.setting?.school_logo ? route('students.image.serve', { filename: props.setting.school_logo.split('/').pop() }) : 'https://placehold.co/100x100/white/orange?text=LOGO')"
                            class="relative z-10 w-20 h-20 rounded-full border-8 border-white shadow-2xl object-cover ring-4 ring-yellow-400/30"
                            alt="School Logo"
                            onerror="this.src='https://placehold.co/100x100/white/orange?text=LOGO'"
                        />

                        </div>
                        <h1 class="mt-0 text-3xl font-black text-white tracking-wider drop-shadow-2xl">
                        {{ props.setting?.school_name || 'ABC SCHOOL NAME' }}
                        </h1>
                        
                    </div>
                </div>

                

                <div class="relative -mt-30 flex justify-center z-20"> 
                    <div class="relative w-36 h-36 rounded-full overflow-hidden border-8 border-white shadow-lg ring-4 ring-yellow-400/50">
                        <img
                        :src="selectedStudent?.image_url ||
                        (selectedStudent?.image ? route('students.image.serve', { filename: selectedStudent.image.split('/').pop() }) : 'https://placehold.co/300x300/FEF3C7/F97316?text=' + (selectedStudent?.name?.charAt(0) || 'S'))"
                            class="w-full h-full object-cover"
                            alt="Student"
                            onerror="this.src='https://placehold.co/300x300/FEF3C7/F97316?text=' + (selectedStudent?.name?.charAt(0) || 'S')"
                        />
                    </div>
                </div>



                <div class="px-8 pt-6 pb-4 bg-white">
                    <div class="space-y-4 text-base">
                        <div class="flex">
                            <span class="w-[140px] text-gray-600 font-medium">Stduent ID</span>
                            <span class="font-bold text-gray-900">: {{ selectedStudent?.admission_number || '1234' }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-[140px] text-gray-600 font-medium">Roll Number</span>
                            <span class="font-bold text-gray-900">: {{ selectedStudent?.roll_number || '123456' }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-[140px] text-gray-600 font-medium">Student Name</span>
                            <span class="font-bold text-gray-900">:  {{ selectedStudent?.name || 'Name Here' }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-[140px] text-gray-600 font-medium">Class</span>
                            <span class="font-bold text-gray-900">: {{ selectedStudent?.class_name?.class_name ? 
                        selectedStudent.class_name.class_name + 
                        (selectedStudent.section?.name ? ` (${selectedStudent.section.name})` : '') 
                        : 'Class Here' }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-[140px] text-gray-600 font-medium">Father/Guardian</span>
                            <span class="font-bold text-gray-900">: {{ selectedStudent?.parent_name || 'Name Here' }}</span>
                        </div>
                        <div class="flex">
                            <span class="w-[140px] text-gray-600 font-medium">Emergency Call</span>
                            <span class="font-bold text-gray-900">: {{ setting?.phone_number || 'Name Here' }}</span>
                        </div>
                        
                    </div>
                </div>

                <div class="w-full bg-blue-600 py-3 px-4 text-center text-sm font-medium text-white shadow-inner">
                {{ setting?.address || 'Name Here' }}
                </div>

                <div class="flex justify-around items-center p-6 bg-gray-50 border-t border-gray-100">
                    <button @click="closeIdCardModal"
                    class="px-10 py-4 bg-gray-100 text-gray-700 rounded-2xl font-bold hover:bg-gray-200 transition shadow-md">
                    Close
                    </button>
                    <PrimaryButton
                    @click="downloadFile(route('admin.students.download.idcard', selectedStudent?.id))"
                    class="px-12 py-4 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-bold rounded-2xl shadow-xl transition transform hover:scale-105 flex items-center gap-3 text-lg">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m-4-4h8m-8 8h8a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Download PDF
                    </PrimaryButton>
                </div>
            </div>
        </div>



        <!-- Footer -->
        <footer class="py-4 text-center text-xs text-gray-600 bg-gray-100">
            © All Rights Reserved. Biddaloy is a product of
            <a href="https://smithitbd.com/" target="_blank" class="text-blue-600 hover:text-blue-700 font-medium">Smith IT</a>
        </footer>

    
          <!-- Action Buttons -->
          <div class="flex justify-center gap-6 px-6 pb-10">
            <button @click="closeIdCardModal"
                    class="px-10 py-4 bg-gray-100 text-gray-700 rounded-2xl font-bold hover:bg-gray-200 transition shadow-md">
              Close
            </button>
            <PrimaryButton
              @click="downloadFile(route('admin.students.download.idcard', selectedStudent?.id))"
              class="px-12 py-4 bg-gradient-to-r from-orange-500 to-amber-600 hover:from-orange-600 hover:to-amber-700 text-white font-bold rounded-2xl shadow-xl transition transform hover:scale-105 flex items-center gap-3 text-lg">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m-4-4h8m-8 8h8a2 2 0 002-2V6a2 2 0 00-2-2H8a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              Download PDF
            </PrimaryButton>
          </div>
        </div>
      </div>





        <!-- Transfer Certificate Modal -->
        <div v-if="showTransferCertModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-xs sm:max-w-sm md:max-w-md">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Transfer Certificate - {{ selectedStudent?.name }}</h3>
                    <button @click="closeTransferCertModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="text-sm text-gray-600 space-y-2 mb-6">
                    <p><strong>Admission Number:</strong> {{ selectedStudent?.admission_number }}</p>
                    <p><strong>Class:</strong> {{ selectedStudent?.class_name?.class_name || 'N/A' }} {{ selectedStudent?.section?.name ? `/ ${selectedStudent.section.name}` : '' }}</p>
                    <p><strong>Date:</strong> {{ new Date().toLocaleDateString() }}</p>
                </div>
                <div class="flex justify-end gap-3">
                    <button @click="closeTransferCertModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">Close</button>
                    <PrimaryButton @click="downloadFile(route('students.certificate.download', selectedStudent?.id))" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">Download</PrimaryButton>
                </div>
            </div>
        </div>

        <!-- Class ID Card Modal -->
        <div v-if="showClassIdCardModal" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-xs sm:max-w-sm md:max-w-md">

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Select Class for ID Cards</h3>
                    <button @click="closeClassIdCardModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>


                <div class="mb-6">

                    <select v-model="selectedClass" class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="" disabled selected hidden>
                        -- Select a Class --
                    </option>
                      
                        <option v-for="cls in classes" :key="cls.id" :value="cls">{{ cls.class_name }}</option>
                    </select>
                </div>


                <div class="flex justify-end gap-3">
                    <button @click="closeClassIdCardModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">Cancel</button>
                    <PrimaryButton @click="printClassIdCards" :disabled="!selectedClass" class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z" />
                        </svg>
                        Print
                    </PrimaryButton>
                </div>
            </div>
        </div>



        <!-- Footer -->
        <div class="mt-8 py-4 text-center bg-gray-50">
            <p class="text-sm text-gray-600">
                © All Rights Reserved. Biddaloy by
                <a href="https://smithitbd.com/" target="_blank" class="text-blue-600 hover:text-blue-700 hover:underline">Smith IT</a>
            </p>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
:root {
    --primary-color: #2563EB;
    --secondary-color: #6EE7B7;
    --text-color: #1F2937;
    --light-bg: #F9FAFB;
}
.card-wrapper {
    display: flex;
    justify-content: center;
    padding: 1rem;
}
.id-card {
    width: 100%;
    max-width: 280px;
    aspect-ratio: 90 / 130;
    border: 1px solid #E5E7EB;
    border-radius: 0.75rem;
    background: #fff;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.top-bar {
    height: 6%;
    background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
}
.bottom-bar {
    height: 3%;
    background: var(--primary-color);
}
.bottom-bar-front {
    background: var(--secondary-color);
}
.id-front {
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    align-items: center;
    height: 100%;
}
.header {
    text-align: center;
    padding: 4%;
    width: 100%;
}
.header img {
    width: 14%;
    aspect-ratio: 1;
    object-fit: contain;
    border-radius: 0.25rem;
    margin-bottom: 1%;
}
.header h2 {
    font-size: clamp(0.8rem, 3.5vw, 0.9rem);
    color: var(--primary-color);
    font-weight: 600;
    margin: 0;
}
.contact {
    font-size: clamp(0.5rem, 2vw, 0.6rem);
    color: #6B7280;
    margin: 1% 0;
}
.identity-label {
    font-size: clamp(0.5rem, 2vw, 0.6rem);
    text-transform: uppercase;
    color: var(--primary-color);
    font-weight: 600;
    border: 0.075rem solid var(--secondary-color);
    border-radius: 0.25rem;
    padding: 0.5% 2%;
    background: #fff;
}
.photo-box {
    width: 28%;
    aspect-ratio: 1;
    border: 0.125rem solid var(--primary-color);
    border-radius: 50%;
    overflow: hidden;
    margin: 3% auto;
    background: #F3F4F6;
}
.photo-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.student-title h3 {
    font-size: clamp(0.9rem, 4.5vw, 1rem);
    color: var(--text-color);
    font-weight: 700;
    margin: 0;
}
.student-title p {
    font-size: clamp(0.6rem, 2.5vw, 0.7rem);
    color: #4B5563;
    font-weight: 500;
    margin-top: 1%;
}
.details {
    margin: 2% 3% 0;
    width: calc(100% - 8%);
}
.details p {
    font-size: clamp(0.6rem, 2.5vw, 0.7rem);
    margin: 1.1% 0;
    display: flex;
    justify-content: space-around;
    border-bottom: 0.05rem dotted #D1D5DB;
    padding-bottom: 0.5%;
}
.details p strong {
    color: #6B7280;
    font-weight: 500;
    min-width: 40%;
}
.details p span {
    color: var(--text-color);
    font-weight: 600;
}
@media (max-width: 640px) {
    .card-wrapper {
        padding: 0.5rem;
    }
    .id-card {
        max-width: 90vw;
    }
    .header img {
        width: 16%;
    }
    .header h2 {
        font-size: clamp(0.7rem, 3vw, 0.8rem);
    }
    .contact, .identity-label {
        font-size: clamp(0.45rem, 1.8vw, 0.55rem);
    }
    .photo-box {
        width: 30%;
    }
    .student-title h3 {
        font-size: clamp(0.8rem, 4vw, 0.9rem);
    }
    .student-title p {
        font-size: clamp(0.55rem, 2vw, 0.65rem);
    }
    .details p {
        font-size: clamp(0.55rem, 2vw, 0.65rem);
    }
}
@media (max-width: 768px) {
    table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
    }
    th, td {
        min-width: 100px;
    }
    .text-xl {
        font-size: 1.25rem;
    }
}
@media (max-width: 640px) {
    .fixed.inset-0 {
        padding: 1rem;
    }
    .max-w-xs, .max-w-sm, .max-w-md {
        width: 100%;
        max-width: 90vw;
    }  
    .text-lg {
        font-size: 1.125rem;
    }
}

</style>