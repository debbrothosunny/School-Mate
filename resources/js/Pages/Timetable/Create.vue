<script setup>
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { computed, watch } from 'vue';
import Swal from 'sweetalert2';

// Define props
const props = defineProps({
    classNames: Array,
    sections: Array,
    sessions: Array,
    subjects: Array,
    teachers: Array,
    rooms: Array,
    timeSlots: Array,
    daysOfWeek: Array,
    classSubjects: Array,
    existingTimetables: {
        type: Array,
        default: () => [],
    },
    // The groupRequiredClassIds prop can now be safely removed or ignored,
    // as we are using class names for logic.
    groups: Array,
    // groupRequiredClassIds: Array, 
});

// Helper to find the "None" group ID. (Assuming ID 4 based on previous context)
const noneGroup = computed(() => props.groups.find(g => g.name.toLowerCase() === 'none'));

const form = useForm({
    class_name_id: '',
    section_id: '',
    session_id: '',
    subject_id: '',
    teacher_id: '',
    room_id: '',
    day_of_week: '',
    class_time_slot_id: '',
    group_id: '', 
    status: true,
});

const submit = () => {
    form.post(route('timetable.store'), {
        onSuccess: () => {
            // Optional: reset specific fields
        },
    });
};

// Flash messages (Inertia uses page.props.value.flash)
const page = usePage();
watch(() => page.props.value?.flash, (newFlash) => {
    if (newFlash?.message) {
        Swal.fire({
            icon: newFlash.type === 'success' ? 'success' : 'error',
            title: newFlash.type === 'success' ? 'Success!' : 'Error!',
            text: newFlash.message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }
}, { deep: true });

// Filtered Teachers (Omitted for brevity, remains the same)
const filteredTeachers = computed(() => {
    if (!form.class_name_id || !form.section_id) return [];
    const assigned = props.classSubjects
        .filter(cs => cs.class_name_id === form.class_name_id && cs.section_id === form.section_id)
        .map(cs => cs.teacher_id);
    const unique = [...new Set(assigned)];
    return props.teachers.filter(t => unique.includes(t.id));
});

// Filtered Subjects (Omitted for brevity, remains the same)
const filteredSubjects = computed(() => {
    if (!form.class_name_id || !form.section_id) return [];
    let filtered = props.classSubjects.filter(cs =>
        cs.class_name_id === form.class_name_id && cs.section_id === form.section_id
    );
    if (form.teacher_id) {
        filtered = filtered.filter(cs => cs.teacher_id === form.teacher_id);
    }
    const subjectIds = [...new Set(filtered.map(cs => cs.subject_id))];
    return props.subjects.filter(s => subjectIds.includes(s.id));
});

// Teacher's assigned subjects (for display) (Omitted for brevity, remains the same)
const getTeacherSubjects = computed(() => (teacher) => {
    if (!form.class_name_id || !form.section_id) return 'Select Class/Section first';
    const subjects = props.classSubjects
        .filter(cs => cs.teacher_id === teacher.id && cs.class_name_id === form.class_name_id && cs.section_id === form.section_id)
        .map(cs => props.subjects.find(s => s.id === cs.subject_id))
        .filter(Boolean)
        .map(s => s.name);
    return [...new Set(subjects)].join(', ') || 'No subjects assigned';
});


// === NAME-BASED CONDITIONAL GROUP LOGIC (NEW) ===

// 1. Get the name of the currently selected class
const currentClassName = computed(() => {
    const classId = parseInt(form.class_name_id);
    const classObj = props.classNames.find(c => c.id === classId);
    return classObj ? classObj.class_name : null;
});

// 2. Check if the class is one of the "grouped" classes ('Nine' or 'Ten')
const isGroupRequired = computed(() => {
    const name = currentClassName.value;
    if (!name) return false;
    const lowerName = name.toLowerCase();
    // Class 'Nine' and 'Ten' require manual group selection
    return lowerName === 'nine' || lowerName === 'ten';
});

// 3. Check if the class is one of the "non-grouped" classes ('Six', 'Seven', 'Eight')
const isNonGroupedClass = computed(() => {
    const name = currentClassName.value;
    if (!name) return false;
    const lowerName = name.toLowerCase();
    // Classes 'Six', 'Seven', 'Eight' should auto-select 'None' group
    return lowerName === 'six' || lowerName === 'seven' || lowerName === 'eight';
});


// === WATCHERS: Auto-select 'None' group for classes Six, Seven, Eight ===
watch(() => form.class_name_id, (newClassId) => {
    form.teacher_id = '';
    form.subject_id = '';
    form.group_id = ''; // Always reset group when class changes

    // Check if the selected class is one of the non-grouped classes
    if (isNonGroupedClass.value) {
        // Automatically set group_id to the 'None' group's ID (e.g., ID 4)
        form.group_id = noneGroup.value ? noneGroup.value.id : '';
    } 
    // If it's a grouped class ('Nine' or 'Ten'), form.group_id remains cleared (handled by the template)
});


// === CONFLICT DETECTION (Updated) ===
const isTeacherBooked = computed(() => {
    if (!form.teacher_id || !form.day_of_week || !form.class_time_slot_id) return false;
    return props.existingTimetables.some(t =>
        t.teacher_id == form.teacher_id &&
        t.day_of_week === form.day_of_week &&
        t.class_time_slot_id == form.class_time_slot_id
    );
});

const isClassSectionBooked = computed(() => {
    if (!form.class_name_id || !form.section_id || !form.day_of_week || !form.class_time_slot_id) return false;
    
    const currentGroupId = form.group_id ? parseInt(form.group_id) : null;
    const noneGroupId = noneGroup.value ? noneGroup.value.id : null;
    const requiresGroupSelection = isGroupRequired.value;

    // Rule 1: If group is required (Nine/Ten) but not selected yet, no conflict check possible.
    if (requiresGroupSelection && !form.group_id) return false; 
    
    // Rule 2: If the class is one of the non-grouped classes (Six, Seven, Eight), 
    // the form's group_id is auto-set to 'None' ID (e.g., 4).
    // When saving a non-grouped class, the DB sets group_id to NULL.
    // Therefore, conflict check must look for group_id === null in existing timetables.
    const isNonGroupedCheck = isNonGroupedClass.value || (requiresGroupSelection && currentGroupId === noneGroupId);

    return props.existingTimetables.some(t =>
        t.class_name_id == form.class_name_id &&
        t.section_id == form.section_id &&
        t.day_of_week === form.day_of_week &&
        t.class_time_slot_id == form.class_time_slot_id &&
        (
            isNonGroupedCheck
                ? t.group_id === null // Check against null for Six/Seven/Eight or for Nine/Ten selecting 'None'
                : t.group_id == currentGroupId // Check against selected group ID for Nine/Ten selecting Science/Arts/Commerce
        )
    );
});


const isRoomBooked = computed(() => {
    if (!form.room_id || !form.day_of_week || !form.class_time_slot_id) return false;
    return props.existingTimetables.some(t =>
        t.room_id == form.room_id &&
        t.day_of_week === form.day_of_week &&
        t.class_time_slot_id == form.class_time_slot_id
    );
});

// For time slot UI (only room conflict) (Omitted for brevity, remains the same)
const isTimeSlotBooked = computed(() => (slotId) => {
    if (!form.day_of_week || !form.room_id) return false;
    return props.existingTimetables.some(t =>
        t.day_of_week === form.day_of_week &&
        t.class_time_slot_id == slotId &&
        t.room_id == form.room_id
    );
});

// Any conflict? (Omitted for brevity, remains the same)
const hasConflict = computed(() =>
    isTeacherBooked.value || isClassSectionBooked.value || isRoomBooked.value
);

// Reset dependent fields
watch([() => form.class_name_id, () => form.section_id], () => {
    form.teacher_id = '';
    form.subject_id = '';
    // group_id is now handled by the specific watcher above, but resetting it here as a fallback is fine.
    // form.group_id = ''; 
});

watch(() => form.teacher_id, (newId) => {
    form.subject_id = '';
    if (newId && form.class_name_id && form.section_id) {
        const ids = props.classSubjects
            .filter(cs => cs.class_name_id === form.class_name_id && cs.section_id === form.section_id && cs.teacher_id === newId)
            .map(cs => cs.subject_id);
        const unique = [...new Set(ids)];
        if (unique.length === 1) form.subject_id = unique[0];
    }
});

// Styling (Omitted for brevity, remains the same)
const selectClass = "mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 dark:text-gray-100 shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring focus:ring-indigo-500 focus:ring-opacity-50 transition duration-150 ease-in-out";
const requiredLabel = (label) => `${label} `;
</script>

<template>
    <Head title="Create Class Schedule" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Create Class Schedule</h2>
        </template>

        <div class="py-12 bg-gray-50 dark:bg-gray-900">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-2xl sm:rounded-xl">
                    <div class="p-6 sm:p-10 border-b border-gray-200 dark:border-gray-700">

                        <form @submit.prevent="submit">
                            <h3 class="text-xl font-bold text-indigo-700 dark:text-indigo-400 mb-6 border-b pb-2">1. Class and Instructor Assignment</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ isGroupRequired ? '4' : '3' }} gap-6"> 

                                <div>
                                    <label for="class_name_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        {{ requiredLabel('Class') }} <span class="text-red-500 font-normal">(Required)</span>
                                    </label>
                                    <select v-model="form.class_name_id" id="class_name_id" :class="selectClass">
                                        <option value="" disabled>Select a class</option>
                                        <option v-for="c in classNames" :key="c.id" :value="c.id">{{ c.class_name }}</option>
                                    </select>
                                    <InputError :message="form.errors.class_name_id" />
                                </div>

                                <div>
                                    <label for="section_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        {{ requiredLabel('Section') }} <span class="text-red-500 font-normal">(Required)</span>
                                    </label>
                                    <select v-model="form.section_id" id="section_id" :class="selectClass">
                                        <option value="" disabled>Select a section</option>
                                        <option v-for="s in sections" :key="s.id" :value="s.id">{{ s.name }}</option>
                                    </select>
                                    <InputError :message="form.errors.section_id" />
                                </div>

                                <div>
                                    <label for="session_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        {{ requiredLabel('Academic Session') }} <span class="text-red-500 font-normal">(Required)</span>
                                    </label>
                                    <select v-model="form.session_id" id="session_id" :class="selectClass">
                                        <option value="" disabled>Select a session</option>
                                        <option v-for="s in sessions" :key="s.id" :value="s.id">{{ s.name }}</option>
                                    </select>
                                    <InputError :message="form.errors.session_id" />
                                </div>

                                <div v-if="isGroupRequired"> 
                                    <label for="group_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        {{ requiredLabel('Group') }} <span class="text-red-500 font-normal">(Required)</span>
                                    </label>
                                    <select v-model="form.group_id" id="group_id" :class="selectClass">
                                        <option value="" disabled>Select a group</option>
                                        <option v-for="g in groups" :key="g.id" :value="g.id">{{ g.name }}</option>
                                    </select>
                                    <InputError :message="form.errors.group_id" />
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">

                                <div :class="{'lg:col-span-2': !isGroupRequired}">
                                    <label for="teacher_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        {{ requiredLabel('Teacher') }} <span class="text-red-500 font-normal">(Required)</span>
                                    </label>
                                    <select v-model="form.teacher_id" id="teacher_id" :class="selectClass" :disabled="!form.class_name_id || !form.section_id">
                                        <option value="" disabled>
                                            <span v-if="!form.class_name_id || !form.section_id">Select Class/Section first</span>
                                            <span v-else-if="filteredTeachers.length === 0">No teachers assigned</span>
                                            <span v-else>Select a teacher</span>
                                        </option>
                                        <option v-for="t in filteredTeachers" :key="t.id" :value="t.id">
                                            {{ t.name }} ({{ getTeacherSubjects(t) }})
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.teacher_id" />
                                    <p v-if="!form.class_name_id || !form.section_id" class="mt-2 text-xs text-indigo-500 dark:text-indigo-400">
                                        Teacher list is filtered by Class & Section.
                                    </p>
                                </div>

                                <div>
                                    <label for="subject_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        {{ requiredLabel('Subject') }} <span class="text-red-500 font-normal">(Required)</span>
                                    </label>
                                    <select v-model="form.subject_id" id="subject_id" :class="selectClass" :disabled="filteredSubjects.length === 0">
                                        <option value="" disabled>
                                            <span v-if="!form.teacher_id">Select Teacher first</span>
                                            <span v-else-if="filteredSubjects.length === 0">No subjects available</span>
                                            <span v-else>Select a subject</span>
                                        </option>
                                        <option v-for="s in filteredSubjects" :key="s.id" :value="s.id">
                                            {{ s.name }} 
                                        </option>
                                    </select>
                                    <InputError :message="form.errors.subject_id" />
                                </div>
                            </div>
                            
                            <hr class="my-10 border-gray-200 dark:border-gray-700">

                            <h3 class="text-xl font-bold text-indigo-700 dark:text-indigo-400 mb-6 border-b pb-2">2. Schedule and Location</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <div>
                                    <label for="room_id" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        {{ requiredLabel('Room') }} <span class="text-red-500 font-normal">(Required)</span>
                                    </label>
                                    <select v-model="form.room_id" id="room_id" :class="selectClass">
                                        <option value="" disabled>Select a room</option>
                                        <option v-for="r in rooms" :key="r.id" :value="r.id">{{ r.name }} ({{ r.capacity }})</option>
                                    </select>
                                    <InputError :message="form.errors.room_id" />
                                </div>

                                <div>
                                    <label for="day_of_week" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                        {{ requiredLabel('Day') }} <span class="text-red-500 font-normal">(Required)</span>
                                    </label>
                                    <select v-model="form.day_of_week" id="day_of_week" :class="selectClass">
                                        <option value="" disabled>Select a day</option>
                                        <option v-for="d in daysOfWeek" :key="d" :value="d">{{ d }}</option>
                                    </select>
                                    <InputError :message="form.errors.day_of_week" />
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                                        Time Slot <span class="text-red-500 font-normal">(Required)</span>
                                        <span v-if="!form.day_of_week || !form.room_id" class="text-xs text-yellow-600 dark:text-yellow-400 italic ml-2">
                                            (Select Day & Room to check availability)
                                        </span>
                                    </label>

                                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 p-3 rounded-lg border-2"
                                            :class="form.errors.class_time_slot_id ? 'border-red-500 bg-red-50' : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50'">
                                        <div v-for="slot in timeSlots" :key="slot.id"
                                                @click="!isTimeSlotBooked(slot.id) ? form.class_time_slot_id = slot.id : null"
                                                class="p-3 border rounded-xl shadow-md transition-all duration-300 text-center relative cursor-pointer"
                                                :class="{
                                                    'bg-indigo-600 text-white border-indigo-700 ring-4 ring-indigo-300/50 scale-105': form.class_time_slot_id == slot.id && !isTimeSlotBooked(slot.id),
                                                    'bg-red-700 text-white border-red-800 cursor-not-allowed opacity-70': isTimeSlotBooked(slot.id),
                                                    'bg-white dark:bg-gray-800 hover:bg-indigo-50 dark:hover:bg-gray-700 border-gray-300 dark:border-gray-600': form.class_time_slot_id != slot.id && !isTimeSlotBooked(slot.id)
                                                }">
                                            <div class="font-bold text-base">{{ slot.name }}</div>
                                            <div class="text-xs" :class="form.class_time_slot_id == slot.id || isTimeSlotBooked(slot.id) ? 'text-gray-200' : ''">
                                                <span v-if="isTimeSlotBooked(slot.id)" class="font-extrabold">BOOKED</span>
                                                <span v-else>{{ slot.start_time }} - {{ slot.end_time }}</span>
                                            </div>
                                            <div v-if="form.class_time_slot_id == slot.id && !isTimeSlotBooked(slot.id)" class="absolute top-1 right-1 text-green-300">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </div>
                                            <div v-if="isTimeSlotBooked(slot.id)" class="absolute top-1 right-1 text-red-300">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                            </div>
                                        </div>
                                    </div>
                                    <InputError :message="form.errors.class_time_slot_id" />
                                </div>
                            </div>

                            <div class="space-y-3 mt-6"> 
                                <div v-if="isTeacherBooked" class="p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>
                                    <p><strong>Teacher Conflict:</strong> এই শিক্ষককে ইতিমধ্যেই নিযুক্ত করা হয়েছে <strong>{{ form.day_of_week }}</strong> এই সময়ে.</p>
                                </div>
  
                                <div v-if="isClassSectionBooked" class="p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>
                                    <p><strong>Class/Section Conflict:</strong> This class, section, {{ isGroupRequired ? 'and group' : '' }} already have a schedule at this time.</p>
                                </div>

                                <div v-if="isRoomBooked" class="p-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 rounded-lg flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"/></svg>
                                    <p><strong>Room Conflict:</strong> এই ক্লাস রুমটি নির্বাচিত দিন এবং সময়ের জন্য ইতিমধ্যেই বুক করা আছে।</p>
                                </div>
                            </div>

                            <div class="flex justify-end mt-10 pt-6 border-t border-gray-200 dark:border-gray-700">
                                <PrimaryButton type="submit" :disabled="form.processing || hasConflict || (isGroupRequired && !form.group_id)" :class="{ 'opacity-50': form.processing || hasConflict || (isGroupRequired && !form.group_id) }">
                                    <span v-if="form.processing">Processing...</span>
                                    <span v-else-if="hasConflict">Resolve Conflict</span>
                                    <span v-else-if="isGroupRequired && !form.group_id">Select Group</span>
                                    <span v-else>Save Schedule</span>
                                </PrimaryButton>

                                <Link :href="route('timetable.index')" 
                                    class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                                    Back to Timetable
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>