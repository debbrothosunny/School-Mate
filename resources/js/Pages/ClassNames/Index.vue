<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    classes: Object, // This will be the paginated data from Laravel
});

const getStatusText = (status) => (status === 0 ? 'Active' : 'Inactive');

const form = useForm({});

const showDeleteModal = ref(false);
const classToDelete = ref(null);
const flash = computed(() => usePage().props.flash || {});

// Reactive variable for search input
const searchQuery = ref('');

// Computed property for filtered classes
const filteredClasses = computed(() => {
    if (!searchQuery.value) {
        return [...props.classes.data];
    }
    const lowerCaseQuery = searchQuery.value.toLowerCase();
    return props.classes.data.filter(classItem => {
        // Include 'total_classes' and 'teacher name' in the search
        return (
            (classItem.class_name && classItem.class_name.toLowerCase().includes(lowerCaseQuery)) ||
            getStatusText(classItem.status).toLowerCase().includes(lowerCaseQuery) ||
            (classItem.total_classes !== null && String(classItem.total_classes).includes(lowerCaseQuery)) ||
            (classItem.teacher && classItem.teacher.name && classItem.teacher.name.toLowerCase().includes(lowerCaseQuery))
        );
    });
});

// Computed properties for pagination display that adapt to search
const displayTotal = computed(() => {
    return searchQuery.value ? filteredClasses.value.length : props.classes.total;
});

const displayFrom = computed(() => {
    if (searchQuery.value) {
        return filteredClasses.value.length > 0 ? 1 : 0;
    }
    return props.classes.from;
});

const displayTo = computed(() => {
    if (searchQuery.value) {
        return filteredClasses.value.length;
    }
    return props.classes.to;
});

const confirmClassDeletion = (classItem) => {
    classToDelete.value = classItem;
    showDeleteModal.value = true;
};

const deleteClass = () => {
    if (classToDelete.value) {
        form.delete(route('class-names.destroy', classToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
                // Inertia will automatically re-render the page due to the redirect from the controller
            },
            onError: (errors) => {
                console.error("Error deleting class:", errors);
                closeDeleteModal();
            },
        });
    }
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    classToDelete.value = null;
};
</script>

<template>
    <AuthenticatedLayout>
        <Head title="Class Management" />

        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Class Management</h2>
        </template>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-4 text-gray-900">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium text-gray-900">All Classes</h3>
                    <Link
                        :href="route('class-names.create')"
                        class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-gray-700"
                    >
                        Add New Class
                    </Link>
                </div>

                <!-- Search Input -->
                <div class="mb-6">
                    <TextInput
                        id="search"
                        type="text"
                        class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        v-model="searchQuery"
                        placeholder="Search classes by name, status, or total classes..."
                    />
                </div>

                <div v-if="flash.message" :class="`p-4 mb-4 text-sm rounded-lg ${flash.type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`" role="alert">
                    {{ flash.message }}
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Classes</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Class Teacher</th> <!-- New Header -->
                                <th scope="col" class="relative px-4 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>

                        <tbody v-if="filteredClasses.length" class="bg-white divide-y divide-gray-200">
                            <tr v-for="classItem in filteredClasses" :key="classItem.id">
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ classItem.class_name }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ classItem.total_classes }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <span
                                        :class="{
                                            'bg-green-100 text-green-800': classItem.status === 0,
                                            'bg-red-100 text-red-800': classItem.status === 1,
                                        }"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    >
                                        {{ getStatusText(classItem.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ classItem.teacher ? classItem.teacher.name : 'N/A' }}</td> <!-- New Data Cell -->
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <Link :href="route('class-names.edit', classItem.id)" class="text-indigo-600 hover:text-indigo-900 mr-4">Edit</Link>
                                    <button
                                        @click="confirmClassDeletion(classItem)"
                                        class="text-red-600 hover:text-red-900 cursor-pointer"
                                    >
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        </tbody>

                        <tbody v-else>
                            <tr>
                                <td colspan="5" class="px-4 py-4 text-center text-sm text-gray-500">No classes found.</td> <!-- Updated colspan to 5 -->
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
                            v-for="link in classes.links"
                            :key="link.label"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-2 text-sm leading-4 font-medium rounded-md',
                                link.active ? 'bg-indigo-600 text-white' : 'text-gray-700 bg-white hover:bg-gray-100',
                                !link.url ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''
                            ]"
                            v-html="link.label"
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
                    Are you sure you want to delete class: <span class="font-bold">{{ classToDelete ? classToDelete.class_name : '' }}</span>?
                </p>
                <div class="flex justify-end space-x-4">
                    <button @click="closeDeleteModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Cancel</button>
                    <button
                        @click="deleteClass"
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
