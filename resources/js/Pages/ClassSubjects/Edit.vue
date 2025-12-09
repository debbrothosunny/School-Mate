<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch, watchEffect } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    classSubject: Object,
    classes: Array,
    subjects: Array,
    teachers: Array,
    sessions: Array,
    sections: Array,
    groups: Array,
});

const flash = computed(() => usePage().props.flash || {});

// Reactive refs for IDs of special classes and group 'None'
const class9Id = ref(null);
const class10Id = ref(null);
const noneGroupId = ref(null);

watchEffect(() => {
    if (props.classes.length) {
        // Find IDs for "Class 9" and "Class 10" (assuming names include "Class ")
        class9Id.value = props.classes.find(c => c.class_name && c.class_name.includes('Nine'))?.id || null;
        class10Id.value = props.classes.find(c => c.class_name && c.class_name.includes('Ten'))?.id || null;
    }
    if (props.groups.length) {
        // Find ID for the "None" group
        noneGroupId.value = props.groups.find(g => g.name === 'None')?.id || null;
    }
});

// Determine if the currently selected class is 9 or 10
const isClass9Or10 = computed(() => {
    return form.class_name_id === class9Id.value || form.class_name_id === class10Id.value;
});

const form = useForm({
    _method: 'post', // Changed to PUT/PATCH for update
    class_name_id: props.classSubject.class_name_id,
    subject_id: props.classSubject.subject_id,
    teacher_id: props.classSubject.teacher_id || '',
    session_id: props.classSubject.session_id,
    section_id: props.classSubject.section_id,
    // Initialize group_id. Use the existing ID, or '' if null/undefined.
    group_id: props.classSubject.group_id || '', 
    status: Number(props.classSubject.status),
});


// WATCHER: Logic to manage group_id based on class selection
watch(
    () => form.class_name_id,
    (newClassId) => {
        // If no class is selected, reset group
        if (!newClassId) {
            form.group_id = '';
            return;
        }

        // Case 1: Class 9 or 10 selected (Group required)
        if (isClass9Or10.value) {
            // If the current group is 'None' or empty, reset it to force selection of Science/Arts/Commerce
            if (form.group_id === noneGroupId.value || form.group_id === '') {
                 form.group_id = '';
            }
        } 
        // Case 2: Other Class selected (Group should be fixed to 'None')
        else {
            // Automatically set group to 'None' ID if available
            form.group_id = noneGroupId.value || '';
        }
    },
    { immediate: true }, // Run immediately on mount to fix initial state if needed
);

const submit = () => {
    // We send PUT request to the update route
    form.post(route('class-subjects.update', props.classSubject.id), {
        onBefore: () => {
            // Manually set _method to PUT/PATCH for Inertia post method to work with Laravel update routes
            form.defaults({ _method: 'put' })._method = 'put';
            
            // Clean up data before sending
            const dataToSend = { ...form.data() };
            dataToSend.teacher_id = dataToSend.teacher_id === '' ? null : dataToSend.teacher_id;
            dataToSend.group_id = dataToSend.group_id === '' ? null : dataToSend.group_id;
            
            // If group_id is still null and isClass9Or10 is true, we need to stop submission (manual validation fallback)
            if (isClass9Or10.value && dataToSend.group_id === null) {
                 Swal.fire({ icon: 'error', title: 'Validation Error!', text: 'Group selection (Science/Arts/Commerce) is required for this class.', toast: true, position: 'top-end', timer: 3000, timerProgressBar: true, showConfirmButton: false, });
                 return false; // Prevent form submission
            }
        },
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'ক্লাস সাবজেক্ট নির্ধারণ সফলভাবে আপডেট করা হয়েছে।',
                toast: true,
                position: 'top-end',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
            });
        },
        onError: (errors) => {
            console.error('Class Subject update failed:', errors);
        },
    });
};

watchEffect(() => {
    if (flash.value.message) {
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
    <Head :title="`Edit Class Subject: ${props.classSubject.className?.class_name} - ${props.classSubject.subject?.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Edit Class Subject: {{ props.classSubject.className?.class_name }} - {{ props.classSubject.subject?.name }}
                <span v-if="props.classSubject.group?.name && props.classSubject.group.name !== 'None'"> ({{ props.classSubject.group.name }})</span>
            </h2>
        </template>

        <div class="py-12 bg-gray-100 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 sm:p-8">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div>
                                <InputLabel for="class_name_id" value="Select Class (Required)" />
                                <select id="class_name_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm" v-model="form.class_name_id" required>
                                    <option value="" disabled>-- Select Class --</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.class_name_id" />
                            </div>

                            <div>
                                <InputLabel for="subject_id" value="Select Subject (Required)" />
                                <select id="subject_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm" v-model="form.subject_id" required>
                                    <option value="" disabled>-- Select Subject --</option>
                                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }} ({{ subject.code || 'N/A' }})</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.subject_id" />
                            </div>

                            <div>
                                <InputLabel for="group_id" :value="isClass9Or10 ? 'Select Group (Required)' : 'Group (Fixed to None)'" />
                                <select
                                    id="group_id"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm"
                                    v-model="form.group_id"
                                    :required="isClass9Or10"
                                    :disabled="!isClass9Or10"
                                >
                                    <option value="" disabled v-if="isClass9Or10">-- Select Group --</option>
                                    
                                    <template v-for="group in groups" :key="group.id">
                                        <option 
                                            :value="group.id" 
                                            v-if="isClass9Or10 || group.id === noneGroupId"
                                        >
                                            {{ group.name }}
                                        </option>
                                    </template>
                                </select>
                                <InputError class="mt-2" :message="form.errors.group_id" />
                            </div>
                            
                            <div>
                                <InputLabel for="teacher_id" value="Select Teacher (Optional)" />
                                <select id="teacher_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm" v-model="form.teacher_id">
                                    <option value="">-- Select Teacher --</option>
                                    <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                        {{ teacher.name }} (Teaches: {{ teacher.subject_taught || 'N/A' }})
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.teacher_id" />
                            </div>

                            <div>
                                <InputLabel for="session_id" value="Select Session (Required)" />
                                <select id="session_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm" v-model="form.session_id" required>
                                    <option value="" disabled>-- Select Session --</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.session_id" />
                            </div>

                            <div>
                                <InputLabel for="section_id" value="Select Section (Required)" />
                                <select id="section_id" class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm" v-model="form.section_id" required>
                                    <option value="" disabled>-- Select Section --</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.section_id" />
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="status" value="Status" />
                                <select id="status" class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-300 rounded-md shadow-sm" v-model.number="form.status" required>
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
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
                                Update Assignment
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>