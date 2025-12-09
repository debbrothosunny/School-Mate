<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, watch, watchEffect } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    classes: Array,
    subjects: Array,
    teachers: Array,
    sessions: Array,
    sections: Array,
    groups: Array,
});

const flash = computed(() => usePage().props.flash || {});

// 1. Define the classes that exclude group selection (based on class name)
const EXCLUDED_CLASS_NAMES = ['Six', 'Seven', 'Eight'];

// 2. Find the specific ID for the 'None' group
const noneGroupId = computed(() => {
    const noneGroup = props.groups.find(group => group.name === 'None');
    // Use the actual ID or fall back to an empty string if 'None' group isn't found
    return noneGroup ? noneGroup.id : '';
});

// 3. Computed property to find the name of the currently selected class
const selectedClassName = computed(() => {
    const classId = form.class_name_id;
    const selectedClass = props.classes.find(cls => cls.id === classId);
    return selectedClass ? selectedClass.class_name : null;
});

// 4. Computed property to check if the Group dropdown should be disabled
// (It should be disabled/fixed if an excluded class is chosen)
const isGroupDisabled = computed(() => {
    return EXCLUDED_CLASS_NAMES.includes(selectedClassName.value);
});


// Initialize form with useForm
const form = useForm({
    class_name_id: '',
    subject_id: '',
    teacher_id: '',
    session_id: '',
    section_id: '',
    group_id: '', // Will hold the selected Group ID, or 'None' ID
    status: 0,
});

// 5. WATCHER: Update group_id when class_name_id changes
watch(() => form.class_name_id, () => {
    if (isGroupDisabled.value) {
        // If an excluded class is selected, set group_id to the 'None' group ID
        form.group_id = noneGroupId.value;
    } else if (form.group_id === noneGroupId.value) {
        // If the current group is 'None' and a non-excluded class is selected, reset it
        form.group_id = '';
    }
}, { immediate: true });


const submit = () => {
    const dataToSend = { ...form.data() };
    
    // Convert empty teacher_id to null for backend compatibility
    dataToSend.teacher_id = dataToSend.teacher_id === '' ? null : dataToSend.teacher_id;
    
    // NOTE: group_id should now hold either a Group ID or the 'None' Group ID, 
    // so we don't convert it to null unless noneGroupId failed to load.
    if (!noneGroupId.value && dataToSend.group_id === '') {
        // Fallback for missing None ID: if it's still empty, use null
         dataToSend.group_id = null;
    }

    // Front-end validation for required fields (including dynamic check for group_id)
    if (!dataToSend.class_name_id || !dataToSend.subject_id || !dataToSend.session_id || !dataToSend.section_id) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error!',
            text: 'Please fill in all required fields.',
            toast: true,
            position: 'top-end',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false,
        });
        return;
    }

    // Front-end validation for Group ID if it is NOT an excluded class
    // This checks if a non-group class has been selected but somehow group_id is still empty/invalid
    if (!isGroupDisabled.value && !dataToSend.group_id) {
        Swal.fire({
            icon: 'error',
            title: 'Validation Error!',
            text: 'Group is required for the selected class.',
            toast: true,
            position: 'top-end',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false,
        });
        return;
    }

    form.post(route('class-subjects.store'), {
        data: dataToSend,
        onSuccess: () => {
            form.reset();
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'ক্লাস সাবজেক্ট সফলভাবে নির্ধারণ করা হয়েছে।',
                toast: true,
                position: 'top-end',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
            });
        },
        onError: errors => {
            console.error('Class Subject creation failed:', errors);
        },
    });
};

// Show flash messages from server redirects
watchEffect(() => {
    if (flash.value && flash.value.message) {
        Swal.fire({
            icon: flash.value.type === 'success' ? 'success' : 'error',
            title: flash.value.type === 'success' ? 'Success!' : 'Error!',
            text: flash.value.message,
            toast: true,
            position: 'top-end',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false,
        });
    }
});
</script>

<template>
    <Head title="Assign New Class Subject" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Assign New Class Subject</h2>
        </template>
        
        <div class="py-12 bg-gray-100 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 sm:p-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <div>
                                <InputLabel for="class_name_id" class="block mb-1">
                                    Select Class <span class="font-bold text-red-500 dark:text-red-400">(Required)</span>
                                </InputLabel>
                                <select 
                                    id="class_name_id" 
                                    v-model="form.class_name_id" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                    required
                                >
                                    <option value="" disabled>-- Select Class --</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                                </select>
                                <InputError :message="form.errors.class_name_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="group_id" class="block mb-1">
                                    Select Group 
                                    <span class="font-bold" :class="isGroupDisabled ? 'text-green-500' : 'text-red-500'">
                                        ({{ isGroupDisabled ? 'Fixed to None' : 'Required' }})
                                    </span>
                                </InputLabel>
                                <select 
                                    id="group_id" 
                                    v-model="form.group_id" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                    :required="!isGroupDisabled"  
                                    :disabled="isGroupDisabled"   
                                >
                                    <option value="" disabled>-- Select Group --</option>
                                    
                                    <template v-for="group in groups" :key="group.id">
                                        <option 
                                            :value="group.id" 
                                            :disabled="isGroupDisabled && group.id !== noneGroupId"
                                        >
                                            {{ group.name }}
                                        </option>
                                    </template>
                                </select>
                                <InputError :message="form.errors.group_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="subject_id" class="block mb-1">
                                    Select Subject <span class="font-bold text-red-500 dark:text-red-400">(Required)</span>
                                </InputLabel>
                                <select 
                                    id="subject_id" 
                                    v-model="form.subject_id" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                    required
                                >
                                    <option value="" disabled>-- Select Subject --</option>
                                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">
                                        {{ subject.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.subject_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="teacher_id" class="block mb-1">
                                    Select Teacher (Required)
                                </InputLabel>
                                <select 
                                    id="teacher_id" 
                                    v-model="form.teacher_id" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                >
                                    <option value="">-- Select Teacher --</option>
                                    <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                        {{ teacher.name }} (Subject: {{ teacher.subject_taught || 'N/A' }})
                                    </option>
                                </select>
                                <InputError :message="form.errors.teacher_id" class="mt-2" />
                            </div>
                            
                            <div>
                                <InputLabel for="session_id" class="block mb-1">
                                    Select Session <span class="font-bold text-red-500 dark:text-red-400">(Required)</span>
                                </InputLabel>
                                <select 
                                    id="session_id" 
                                    v-model="form.session_id" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                    required
                                >
                                    <option value="" disabled>-- Select Session --</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                                <InputError :message="form.errors.session_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="section_id" class="block mb-1">
                                    Select Section <span class="font-bold text-red-500 dark:text-red-400">(Required)</span>
                                </InputLabel>
                                <select 
                                    id="section_id" 
                                    v-model="form.section_id" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                    required
                                >
                                    <option value="" disabled>-- Select Section --</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                                <InputError :message="form.errors.section_id" class="mt-2" />
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="status" class="block mb-1">
                                    Status
                                </InputLabel>
                                <select 
                                    id="status" 
                                    v-model.number="form.status" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                    required
                                >
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError :message="form.errors.status" class="mt-2" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end pt-6 border-t dark:border-gray-700 mt-6">
                            <Link :href="route('class-subjects.index')" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mr-4 transition duration-150 ease-in-out">
                                Cancel
                            </Link>
                            <PrimaryButton :disabled="form.processing" :class="{ 'opacity-50': form.processing }">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Assign Subject
                            </PrimaryButton>
                        </div>
                    </form>   
                </div>
            </div>   
        </div>
    </AuthenticatedLayout>
</template>