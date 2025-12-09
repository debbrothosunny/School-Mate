<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { ref, computed, watchEffect } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    teachers: Array,
    setting: Object,
});

const form = useForm({});
const showDeleteModal = ref(false);
const teacherToDelete = ref(null);
const showViewModal = ref(false);
const selectedTeacher = ref(null);

const flash = computed(() => {
    const p = usePage().props;
    return {
        message: p.flash?.success || p.flash?.error || '',
        type: p.flash?.success ? 'success' : 'error',
    };
});

watchEffect(() => {
    if (flash.value.message) {
        Swal.fire({
            icon: flash.value.type,
            title: flash.value.type === 'success' ? 'Success!' : 'Error!',
            text: flash.value.message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
        });
    }
});

const searchQuery = ref('');
const filteredTeachers = computed(() => {
    if (!searchQuery.value) return [...props.teachers];
    const query = searchQuery.value.toLowerCase();
    return props.teachers.filter(teacher => 
        [teacher.name, teacher.joining_number, teacher.address, teacher.phone_number, teacher.qualification, teacher.subject_taught, teacher.designation, teacher.class_teacher_of].some(field => 
            field?.toString().toLowerCase().includes(query)
        )
    );
});

const confirmTeacherDeletion = (teacher) => {
    teacherToDelete.value = teacher;
    showDeleteModal.value = true;
};

const deleteTeacher = () => {
    if (teacherToDelete.value) {
        form.delete(route('teachers.destroy', teacherToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => closeDeleteModal(),
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    teacherToDelete.value = null;
};

const openViewModal = (teacher) => {
    selectedTeacher.value = teacher;
    showViewModal.value = true;
};

const closeViewModal = () => {
    showViewModal.value = false;
    selectedTeacher.value = null;
};

const formatJoiningDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' }).replace(/\//g, '/');
};
</script>

<template>
    <Head title="Teachers" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-2xl font-bold text-gray-900">Teachers</h2>
        </template>

        <div class="py-12 bg-gradient-to-b from-gray-50 to-white min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                    <div class="bg-white border-b border-gray-200 px-8 py-6">
                        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-6">
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">All Teachers</h3>
                                <p class="text-gray-500 mt-1">Manage and view all teaching staff</p>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                                <a :href="route('teachers.print_all')" target="_blank"
                                   class="inline-flex items-center justify-center px-6 py-3 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl transition shadow-lg">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4H7a2 2 0 01-2-2v-4a2 2 0 012-2h10a2 2 0 012 2v4a2 2 0 01-2 2h-2m-4 0v-6m0 0l-3 3m3-3l3 3"></path>
                                    </svg>
                                    Print All ID Cards
                                </a>
                                <Link :href="route('teachers.create')"
                                      class="inline-flex items-center justify-center px-6 py-3 bg-gray-900 hover:bg-gray-800 text-white font-medium rounded-xl transition shadow-lg">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add New Teacher
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="px-8 pt-6 pb-4">
                        <TextInput
                            v-model="searchQuery"
                            placeholder="Search teachers..."
                            class="w-full px-5 py-4 rounded-xl border-gray-200 focus:ring-4 focus:ring-blue-100 focus:border-blue-400 transition"
                        />
                    </div>

                    <!-- Desktop Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                    <th class="px-6 py-4 text-left">Image</th>
                                    <th class="px-6 py-4 text-left">Name</th>
                                    <th class="px-6 py-4 text-left">Joining No.</th>
                                    <th class="px-6 py-4 text-left">Address</th>
                                    <th class="px-6 py-4 text-left">Phone</th>
                                    <th class="px-6 py-4 text-left">Qualification</th>
                                    <th class="px-6 py-4 text-left">Joining Date</th>
                                    <th class="px-6 py-4 text-left">Subject</th>
                                    <th class="px-6 py-4 text-left">Designation</th>
                                    <th class="px-6 py-4 text-left">Class Teacher Of</th>
                                    <th class="px-6 py-4 text-left">Status</th>
                                    <th class="px-6 py-4 text-left">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="teacher in filteredTeachers" :key="teacher.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-5">
                                        <img v-if="teacher.image"
                                             :src="teacher.image.startsWith('http') ? teacher.image : route('teachers.image.serve', { filename: teacher.image.split('/').pop() })"
                                             class="w-12 h-12 rounded-full object-cover ring-2 ring-gray-200"
                                             alt="Teacher" />
                                        <div v-else class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold text-lg">
                                            {{ teacher.name.charAt(0) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-5 font-semibold text-gray-900">{{ teacher.name }}</td>
                                    <td class="px-6 py-5 text-gray-700">{{ teacher.joining_number }}</td>
                                    <td class="px-6 py-5 text-gray-600 max-w-xs truncate">{{ teacher.address }}</td>
                                    <td class="px-6 py-5 text-gray-700">{{ teacher.phone_number || 'N/A' }}</td>
                                    <td class="px-6 py-5 text-gray-700">{{ teacher.qualification || 'N/A' }}</td>
                                    <td class="px-6 py-5 text-gray-700">{{ formatJoiningDate(teacher.joining_date) }}</td>
                                    <td class="px-6 py-5 text-gray-700">{{ teacher.subject_taught }}</td>
                                    <td class="px-6 py-5 text-gray-700 font-medium">{{ teacher.designation }}</td>
                                    <td class="px-6 py-5">
                                        <span v-if="teacher.is_class_teacher"
                                              class="inline-flex items-center px-4 py-2 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">
                                            {{ teacher.class_teacher_of }}
                                        </span>
                                        <span v-else class="text-gray-400 text-sm italic">None</span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <span :class="teacher.status === 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                              class="px-4 py-2 rounded-full text-xs font-bold">
                                            {{ teacher.status === 0 ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <div class="flex items-center gap-3">
                                            <button @click="openViewModal(teacher)" class="text-blue-600 hover:text-blue-800 transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                            <Link :href="route('teachers.edit', teacher.id)" class="text-indigo-600 hover:text-indigo-800 transition">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </Link>
                                            
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- No Results -->
                        <div v-if="!filteredTeachers.length" class="text-center py-16">
                            <div class="text-6xl text-gray-200 mb-4">No teachers found</div>
                            <p class="text-gray-500">Try adjusting your search or add a new teacher.</p>
                        </div>
                    </div>
                </div>

                <!-- Mobile Cards -->
                <div class="mt-8 space-y-4 sm:hidden">
                    <div v-for="teacher in filteredTeachers" :key="teacher.id"
                         class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-4">
                                <img v-if="teacher.image"
                                     :src="teacher.image.startsWith('http') ? teacher.image : route('teachers.image.serve', { filename: teacher.image.split('/').pop() })"
                                     class="w-16 h-16 rounded-full object-cover ring-4 ring-gray-100" />
                                <div v-else class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-xl font-bold">
                                    {{ teacher.name.charAt(0) }}
                                </div>
                                <div>
                                    <h4 class="font-bold text-lg text-gray-900">{{ teacher.name }}</h4>
                                    <p class="text-sm text-indigo-600 font-medium">{{ teacher.designation }}</p>
                                </div>
                            </div>
                            <span :class="teacher.status === 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                  class="px-4 py-2 rounded-full text-xs font-bold">
                                {{ teacher.status === 0 ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div><span class="font-medium text-gray-600">Joining No:</span> {{ teacher.joining_number }}</div>
                            <div><span class="font-medium text-gray-600">Phone:</span> {{ teacher.phone_number || 'N/A' }}</div>
                            <div><span class="font-medium text-gray-600">Subject:</span> {{ teacher.subject_taught }}</div>
                            <div><span class="font-medium text-gray-600">Class:</span> 
                                <span v-if="teacher.is_class_teacher" class="text-emerald-600 font-bold">{{ teacher.class_teacher_of }}</span>
                                <span v-else class="text-gray-400 italic">None</span>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-5">
                            <button @click="openViewModal(teacher)" class="text-blue-600 hover:text-blue-800">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </button>
                            <Link :href="route('teachers.edit', teacher.id)" class="text-indigo-600 hover:text-indigo-800">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </Link>
                            <button @click="confirmTeacherDeletion(teacher)" class="text-red-600 hover:text-red-800">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2.175 2.175 0 0116.138 21H7.862a2.175 2.175 0 01-1.995-1.858L5 7m5-4h4m-4 4v12m4-12v12"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Keep Your Original ID Card Modal Exactly As Is -->
        <!-- View ID Card Modal -->
        <div v-if="showViewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden font-sans" id="printable-id-card">
                <!-- Your original ID card design - 100% unchanged -->
                <div class="relative bg-[#003087] pt-20 pb-20 text-white text-center overflow-hidden">
                    <div class="mb-3">
                        <img
                            :src="props.setting?.school_logo
                                ? route('settings.logo.serve', { filename: props.setting.school_logo.split('/').pop() })
                                : 'https://placehold.co/90x90/003087/white?text=LOGO'"
                            alt="School Logo"
                            class="w-24 h-24 mx-auto rounded-full border-4 border-white shadow-2xl object-cover ring-4 ring-white/30"
                        />
                    </div>
                    <button @click="closeViewModal" class="absolute top-5 right-5 text-white/80 hover:text-white z-50 transition">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <h1 class="text-3xl font-black tracking-wider">BLUE BIRD SCHOOL</h1>
                    <p class="text-sm uppercase tracking-widest mt-1 opacity-95">Staff ID Card</p>
                    <div class="absolute bottom-0 left-0 w-full">
                        <svg viewBox="0 0 1440 320" class="w-full h-32" preserveAspectRatio="none">
                            <path fill="#005eb8" d="M0,160 C320,50 500,300 720,240 C940,180 1120,50 1440,160 L1440,320 L0,320 Z"></path>
                        </svg>
                    </div>
                </div>
                <div class="relative px-6 -mt-30 mb-4">
                    <div class="flex justify-center">
                        <div class="relative">
                            <img
                                :src="selectedTeacher?.image_url || 'https://placehold.co/175x205/003087/white?text=NO+PHOTO'"
                                class="w-[155px] h-[160px] object-cover border-[6px] border-white shadow-2xl"
                                style="clip-path: polygon(50% 0%, 100% 18%, 100% 82%, 50% 100%, 0% 82%, 0% 18%);"
                                alt="Staff Photo"
                            />
                        </div>
                    </div>
                </div>
                <div class="text-center px-6 -mt-2">
                    <h2 class="text-3xl font-black text-[#003087] uppercase tracking-wider">
                        {{ selectedTeacher?.name || 'Unknown' }}
                    </h2>
                    <p class="text-lg font-bold text-[#005eb8] uppercase tracking-widest mt-2">
                        {{ selectedTeacher?.designation || 'Not Available' }}
                    </p>
                </div>
                <div class="mt-8 px-8">
                    <div class="space-y-5 text-center">
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Employee ID</p>
                            <p class="text-xl font-black text-gray-900">
                                {{ selectedTeacher?.joining_number || 'Unknown' }}
                            </p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-medium">Address</p>
                            <p class="text-xl font-black text-gray-900">
                                {{ selectedTeacher?.address || 'Not Provided' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center gap-4 px-8 py-6 bg-gray-50">
                    <a :href="route('teachers.download_pdf', selectedTeacher.id)"
                       target="_blank"
                       class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition transform hover:scale-105">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download PDF
                    </a>
                </div>
                <footer class="py-4 text-center text-xs text-gray-600 bg-gray-100">
                    © All Rights Reserved. Biddaloy is a product of
                    <a href="https://smithitbd.com/" target="_blank" class="text-blue-600 hover:text-blue-700 font-medium">Smith IT</a>
                </footer>
            </div>
        </div>

        <!-- Delete Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-gray-900">Confirm Deletion</h3>
                    <button @click="closeDeleteModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="mb-6 text-gray-600">
                    Are you sure you want to permanently delete <span class="font-semibold">{{ teacherToDelete?.name }}</span>?
                </p>
                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="closeDeleteModal">Cancel</SecondaryButton>
                    <DangerButton @click="deleteTeacher">Delete</DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Clean & Premium Look */
table {
    border-collapse: separate;
    border-spacing: 0;
}
tr:hover td {
    background-color: #f8fafc !important;
}
</style>