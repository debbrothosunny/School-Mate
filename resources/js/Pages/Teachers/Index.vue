<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'; // Import usePage for flash messages
import { ref, computed } from 'vue'; // Import ref and computed
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue'; // Import TextInput for the search bar

const props = defineProps({
    teachers: Array, // This prop will contain the list of teachers from the controller
});

const form = useForm({}); // Initialize a form object for delete action

const showDeleteModal = ref(false);
const teacherToDelete = ref(null);
const flash = computed(() => usePage().props.flash || {}); // For displaying flash messages

// Reactive variable for search input
const searchQuery = ref('');

// Computed property for filtered teachers
const filteredTeachers = computed(() => {
    if (!searchQuery.value) {
        // If no search query, return the original teachers array
        return [...props.teachers]; // Return a copy to avoid direct mutation
    }
    const lowerCaseQuery = searchQuery.value.toLowerCase();
    return props.teachers.filter(teacher => {
        // Check multiple fields for a match
        return (
            (teacher.name && teacher.name.toLowerCase().includes(lowerCaseQuery)) ||
            (teacher.subject_taught && teacher.subject_taught.toLowerCase().includes(lowerCaseQuery)) ||
            (teacher.user?.name && teacher.user.name.toLowerCase().includes(lowerCaseQuery)) ||
            (teacher.user?.email && teacher.user.email.toLowerCase().includes(lowerCaseQuery)) ||
            (teacher.status === 0 ? 'active' : 'inactive').includes(lowerCaseQuery)
        );
    });
});


const confirmTeacherDeletion = (teacher) => {
    teacherToDelete.value = teacher;
    showDeleteModal.value = true;
};

const deleteTeacher = () => {
    if (teacherToDelete.value) {
        form.delete(route('teachers.destroy', teacherToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
                // Inertia will automatically re-render the list due to the redirect
            },
            onError: (errors) => {
                console.error("Error deleting teacher:", errors);
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    teacherToDelete.value = null;
};
</script>

<template>
    <Head title="Teachers" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Teachers</h2>
        </template>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">All Teachers</h3>
                    <Link :href="route('teachers.create')">
                        <PrimaryButton>Add New Teacher</PrimaryButton>
                    </Link>
                </div>

                <!-- Search Input - Added here! -->
                <div class="mb-6">
                    <TextInput
                        id="search"
                        type="text"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        v-model="searchQuery"
                        placeholder="Search teachers by name, subject, linked user, or status..."
                    />
                </div>

                <div v-if="flash.message" :class="`p-4 mb-4 text-sm rounded-lg ${flash.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`" role="alert">
                    {{ flash.message }}
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject Taught</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Linked User</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-if="filteredTeachers.length === 0">
                                <td colspan="6" class="px-4 py-4 whitespace-nowrap text-center text-gray-500">No teachers found.</td>
                            </tr>
                            <tr v-for="teacher in filteredTeachers" :key="teacher.id">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <img v-if="teacher.image" :src="`/storage/${teacher.image}`" alt="Teacher Image" class="h-10 w-10 rounded-full object-cover">
                                    <div v-else class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 text-xs">No Img</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">{{ teacher.name }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">{{ teacher.subject_taught }}</td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span v-if="teacher.user">{{ teacher.user.name }} ({{ teacher.user.email }})</span>
                                    <span v-else class="text-gray-500">Not Linked</span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <span :class="{ 'bg-green-100 text-green-800': teacher.status === 0, 'bg-red-100 text-red-800': teacher.status === 1 }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ teacher.status === 0 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('teachers.edit', teacher.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</Link>
                                    <button @click="confirmTeacherDeletion(teacher)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl w-96">
                <h3 class="text-lg font-semibold mb-4">Confirm Deletion</h3>
                <p class="mb-4">Are you sure you want to delete teacher: <span class="font-bold">{{ teacherToDelete ? teacherToDelete.name : '' }}</span>?</p>
                <div class="flex justify-end space-x-4">
                    <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                    <DangerButton @click="deleteTeacher">Delete</DangerButton>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No specific styles needed beyond TailwindCSS for this component */
</style>
