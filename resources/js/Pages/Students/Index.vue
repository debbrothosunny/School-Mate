<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    students: Object, // This prop will contain the paginated list of students
});

const flash = computed(() => usePage().props.flash || {});
const form = useForm({});
const showDeleteModal = ref(false);
const studentToDelete = ref(null);
const searchQuery = ref('');

const filteredStudents = computed(() => {
    if (!searchQuery.value) {
        return [...props.students.data];
    }
    const lowerCaseQuery = searchQuery.value.toLowerCase();
    return props.students.data.filter(student => {
        return (
            (student.name && student.name.toLowerCase().includes(lowerCaseQuery)) ||
            (student.admission_number && student.admission_number.toLowerCase().includes(lowerCaseQuery)) ||
            (student.roll_number && student.roll_number.toString().includes(lowerCaseQuery)) ||
            (student.class_name?.name && student.class_name.name.toLowerCase().includes(lowerCaseQuery)) ||
            (student.age && student.age.toString().includes(lowerCaseQuery)) ||
            (student.session?.name && student.session.name.toLowerCase().includes(lowerCaseQuery)) ||
            (student.group?.name && student.group.name.toLowerCase().includes(lowerCaseQuery)) ||
            (student.section?.name && student.section.name.toLowerCase().includes(lowerCaseQuery)) ||
            (student.parent_name && student.parent_name.toLowerCase().includes(lowerCaseQuery)) ||
            (student.contact && student.contact.toLowerCase().includes(lowerCaseQuery)) ||
            (student.status === 0 ? 'active' : 'inactive').includes(lowerCaseQuery) ||
            (student.admission_fee_amount && (student.admission_fee_amount / 100).toFixed(2).includes(lowerCaseQuery)) ||
            (student.admission_fee_paid !== undefined && (student.admission_fee_paid ? 'paid' : 'unpaid').includes(lowerCaseQuery)) ||
            (student.payment_method && student.payment_method.toLowerCase().includes(lowerCaseQuery))
        );
    });
});

const displayTotal = computed(() => {
    return searchQuery.value ? filteredStudents.value.length : props.students.total;
});

const displayFrom = computed(() => {
    if (searchQuery.value) {
        return filteredStudents.value.length > 0 ? 1 : 0;
    }
    return props.students.from;
});

const displayTo = computed(() => {
    if (searchQuery.value) {
        return filteredStudents.value.length;
    }
    return props.students.to;
});

const confirmDelete = (student) => {
    studentToDelete.value = student;
    showDeleteModal.value = true;
};

const deleteStudent = () => {
    if (studentToDelete.value) {
        form.delete(route('students.destroy', studentToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
            },
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

const formatCurrency = (amountInPaisa) => {
    if (amountInPaisa === null || amountInPaisa === undefined) {
        return 'N/A';
    }
    return `BDT ${(amountInPaisa / 100).toFixed(2)}`;
};
</script>

<template>
    <Head title="Student Management" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Student Management</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-2xl font-bold text-gray-900">All Students</h3>
                            <Link :href="route('students.create')">
                                <PrimaryButton>Add New Student</PrimaryButton>
                            </Link>
                        </div>

                        <div class="mb-6">
                            <TextInput
                                id="search"
                                type="text"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                v-model="searchQuery"
                                placeholder="Search students..."
                            />
                        </div>

                        <div v-if="flash.message" :class="`p-4 rounded-lg font-medium text-sm my-4 ${flash.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`" role="alert">
                            {{ flash.message }}
                        </div>

                        <div class="overflow-x-auto rounded-lg shadow-sm border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Image</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Admission No.</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Roll No.</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Class</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Age</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Session</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Group</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Section</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Parent</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Contact</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Admission Fee</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Fee Paid</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Payment Method</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" v-if="filteredStudents.length">
                                    <tr v-for="student in filteredStudents" :key="student.id" class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <img
                                                :src="student.image ? `/storage/${student.image}` : 'https://placehold.co/40x40/DDDDDD/000000?text=No+Image'"
                                                alt="Student Image"
                                                class="w-10 h-10 object-cover rounded-full"
                                            />
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">{{ student.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.admission_number }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.roll_number }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.class_name ? student.class_name.name : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.age }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.session ? student.session.name : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.group ? student.group.name : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.section ? student.section.name : 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.parent_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.contact }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ formatCurrency(student.admission_fee_amount) }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${student.admission_fee_paid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`">
                                                {{ student.admission_fee_paid ? 'Paid' : 'Unpaid' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.payment_method || 'N/A' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span :class="`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${student.status === 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`">
                                                {{ student.status === 0 ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <Link :href="route('students.edit', student.id)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</Link>
                                            <button @click="confirmDelete(student)" class="text-red-600 hover:text-red-900">Delete</button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody v-else>
                                    <tr>
                                        <td colspan="16" class="text-center py-4 text-gray-500">No students found.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 flex justify-between items-center">
                            <span class="text-sm text-gray-700" v-if="displayTotal > 0">
                                Showing {{ displayFrom }} to {{ displayTo }} of {{ displayTotal }} results
                            </span>
                            <span v-else class="text-sm text-gray-700">No results found</span>
                            <div class="flex" v-if="!searchQuery">
                                <Link
                                    v-for="link in students.links"
                                    :key="link.label"
                                    :href="link.url || '#'"
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-2 text-sm leading-4 font-medium rounded-md',
                                        link.active ? 'bg-indigo-600 text-white' : 'text-gray-700 bg-white hover:bg-gray-100',
                                        !link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''
                                    ]"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white p-6 rounded-lg shadow-xl w-full max-w-sm">
                <h3 class="text-xl font-semibold mb-4 text-gray-800">Confirm Deletion</h3>
                <p class="mb-6 text-gray-600">
                    Are you sure you want to delete student: <span class="font-bold text-gray-900">{{ studentToDelete ? studentToDelete.name : '' }}</span>?
                </p>
                <div class="flex justify-end space-x-3">
                    <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                    <DangerButton @click="deleteStudent" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">Delete</DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
