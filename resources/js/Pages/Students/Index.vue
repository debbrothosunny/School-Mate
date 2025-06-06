<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue'; // Import ref and computed
import TextInput from '@/Components/TextInput.vue'; // Import TextInput for the search bar

const props = defineProps({
    students: Object, // This prop will contain the paginated list of students
});

// Log the entire student object to see the image path for each student
// Open your browser's developer console (F12) to see this output
// Check if 'image' property exists and its value is as expected (e.g., 'students/imagename.jpg')
console.log('Students data including images:', props.students.data);

const flash = computed(() => usePage().props.flash || {});

const form = useForm({});
const showDeleteModal = ref(false);
const studentToDelete = ref(null);

// Reactive variable for search input
const searchQuery = ref('');

// Computed property for filtered students
const filteredStudents = computed(() => {
    if (!searchQuery.value) {
        // If no search query, return the original data.
        return [...props.students.data]; // Return a copy to avoid direct mutation
    }
    const lowerCaseQuery = searchQuery.value.toLowerCase();
    return props.students.data.filter(student => {
        // Check multiple fields for a match
        return (
            (student.name && student.name.toLowerCase().includes(lowerCaseQuery)) ||
            (student.class_name?.name && student.class_name.name.toLowerCase().includes(lowerCaseQuery)) ||
            (student.age && student.age.toString().includes(lowerCaseQuery)) ||
            (student.session?.name && student.session.name.toLowerCase().includes(lowerCaseQuery)) ||
            (student.group?.name && student.group.name.toLowerCase().includes(lowerCaseQuery)) ||
            (student.section?.name && student.section.name.toLowerCase().includes(lowerCaseQuery)) ||
            (student.parent_name && student.parent_name.toLowerCase().includes(lowerCaseQuery)) ||
            (student.contact && student.contact.toLowerCase().includes(lowerCaseQuery)) ||
            (student.status === 0 ? 'active' : 'inactive').includes(lowerCaseQuery)
        );
    });
});

// Computed properties for pagination display that adapt to search
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
</script>

<template>
    <Head title="Student Management" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Student Management</h2>
        </template>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">All Students</h3>
                    <Link
                        :href="route('students.create')"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 text-white text-xs font-semibold rounded-md hover:bg-gray-700"
                    >
                        Add New Student
                    </Link>
                </div>

                <!-- Search Input - Added here! -->
                <div class="mb-6">
                    <TextInput
                        id="search"
                        type="text"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        v-model="searchQuery"
                        placeholder="Search students by name, class, session, group, section, parent, contact, or status..."
                    />
                </div>

                <div
                    v-if="flash.message"
                    :class="[
                        'p-4 mb-4 text-sm rounded-lg',
                        flash.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                    ]"
                    role="alert"
                >
                    {{ flash.message }}
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Image</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Age</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Session</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Group</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Section</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parent</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" v-if="filteredStudents.length">
                            <tr v-for="student in filteredStudents" :key="student.id">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <img 
                                        :src="student.image ? `/storage/${student.image}` : 'https://placehold.co/40x40/DDDDDD/000000?text=No+Image'" 
                                        alt="Student Image" 
                                        class="w-10 h-10 object-cover rounded-full"
                                    />
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ student.name }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.class_name ? student.class_name.name : 'N/A' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.age }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.session ? student.session.name : 'N/A' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.group ? student.group.name : 'N/A' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.section ? student.section.name : 'N/A' }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.parent_name }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ student.contact }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800': student.status === 0,
                                            'bg-red-100 text-red-800': student.status === 1,
                                        }"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ student.status === 0 ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('students.edit', student.id)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</Link>
                                    <button @click="confirmDelete(student)" class="text-red-600 hover:text-red-900">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                        <tbody v-else>
                            <tr>
                                <td colspan="11" class="text-center py-4 text-gray-500">No students found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-between items-center">
                    <span class="text-sm text-gray-700" v-if="displayTotal > 0">
                        Showing {{ displayFrom }} to {{ displayTo }} of {{ displayTotal }} results
                    </span>
                    <span v-else class="text-sm text-gray-700">No results found</span>

                    <div class="flex" v-if="!searchQuery"> <!-- Only show pagination links if no search is active -->
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

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-lg shadow-xl w-96">
                <h3 class="text-lg font-semibold mb-4">Confirm Deletion</h3>
                <p class="mb-4">
                    Are you sure you want to delete student:
                    <span class="font-bold">{{ studentToDelete ? studentToDelete.name : '' }}</span>?
                </p>
                <div class="flex justify-end space-x-4">
                    <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                    <button
                        @click="deleteStudent"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* No specific styles needed beyond TailwindCSS for this component */
</style>
