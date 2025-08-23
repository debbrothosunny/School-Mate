<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, watchEffect, ref, watch } from 'vue'; // Added 'ref' and 'watch'

const props = defineProps({
    classes: Array,
    subjects: Array,
    teachers: Array,
    sessions: Array,
    sections: Array,
    groups: Array, // NEW: Added groups prop
});

const flash = computed(() => usePage().props.flash || {});

// Reactive references for specific class/group IDs
const class9Id = ref(null);
const class10Id = ref(null);
const noneGroupId = ref(null);

// Find these IDs once props are available
watchEffect(() => {
    if (props.classes.length > 0) {
        class9Id.value = props.classes.find(cls => cls.name === 'Class 9')?.id; // Assuming 'name' for class_name
        class10Id.value = props.classes.find(cls => cls.name === 'Class 10')?.id;
    }
    if (props.groups.length > 0) {
        noneGroupId.value = props.groups.find(group => group.name === 'None')?.id;
    }
});


const form = useForm({
    class_name_id: '',
    subject_id: '',
    teacher_id: '', // Nullable in DB, so default to empty string for "Select Teacher"
    session_id: '',
    section_id: '',
    group_id: '', // NEW: Added group_id
    status: 0, // Default to Active
});

// Computed property to check if the selected class is 9 or 10
const isClass9Or10 = computed(() => {
    const selectedClassId = form.class_name_id;
    return selectedClassId === class9Id.value || selectedClassId === class10Id.value;
});

// Watch for changes in class_name_id to adjust group_id
watch(() => form.class_name_id, (newClassId) => {
    if (newClassId) { // Only run if a class is selected
        if (!isClass9Or10.value) {
            // If not Class 9 or 10, set group_id to 'None'
            form.group_id = noneGroupId.value || ''; // Ensure it's set to the actual ID or empty
        } else {
            // If it becomes Class 9 or 10, clear group_id to force selection
            // unless it's already a valid group (Science/Arts/Commerce)
            const currentGroup = props.groups.find(g => g.id === form.group_id);
            if (!currentGroup || currentGroup.name === 'None') {
                form.group_id = ''; // Clear if it was 'None' or invalid
            }
        }
    } else {
        // If no class is selected, reset group_id
        form.group_id = '';
    }
}, { immediate: true }); // Run immediately on component load to set initial group_id if needed


const submit = () => {
    // Convert empty string for teacher_id to null if not selected, for backend compatibility
    const dataToSend = { ...form.data() };
    dataToSend.teacher_id = dataToSend.teacher_id === '' ? null : dataToSend.teacher_id;
    // Ensure group_id is null if it's an empty string (e.g., when 'None' is implied but not explicitly selected)
    dataToSend.group_id = dataToSend.group_id === '' ? null : dataToSend.group_id;


    form.post(route('class-subjects.store'), {
        data: dataToSend, // Pass the modified data
        onSuccess: () => {
            form.reset(); // Clear form fields after successful submission
            // After reset, the watch effect with immediate: true should re-evaluate group_id
        },
        onError: (errors) => {
            console.error("Class Subject creation failed:", errors);
        },
    });
};

watchEffect(() => {
    if (flash.value && flash.value.message) {
        Swal.fire({
            icon: flash.value.type === 'success' ? 'success' : 'error',
            title: flash.value.type === 'success' ? 'Success!' : 'Error!',
            text: flash.value.message,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
        });
    }
});
</script>

<template>
    <Head title="Assign New Class Subject" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Assign New Class Subject</h2>
        </template>

        <div class="container-fluid py-4">
            <div class="card shadow-sm rounded-lg">
                <div class="card-body p-4">
                    <form @submit.prevent="submit">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <InputLabel for="class_name_id" value="Select Class" class="form-label" />
                                <select id="class_name_id" class="form-select" v-model="form.class_name_id" required>
                                    <option value="" disabled>-- Select Class --</option>
                                    <option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option> <!-- Assuming 'name' for class_name -->
                                </select>
                                <InputError class="mt-2" :message="form.errors.class_name_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="subject_id" value="Select Subject" class="form-label" />
                                <select id="subject_id" class="form-select" v-model="form.subject_id" required>
                                    <option value="" disabled>-- Select Subject --</option>
                                    <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }} ({{ subject.code || 'N/A' }})</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.subject_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="teacher_id" value="Select Teacher (Optional)" class="form-label" />
                                <select id="teacher_id" class="form-select" v-model="form.teacher_id">
                                    <option value="">-- Select Teacher --</option>
                                    <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">
                                        {{ teacher.name }} (Subject: {{ teacher.subject_taught || 'N/A' }})
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.teacher_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="session_id" value="Select Session" class="form-label" />
                                <select id="session_id" class="form-select" v-model="form.session_id" required>
                                    <option value="" disabled>-- Select Session --</option>
                                    <option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.session_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="section_id" value="Select Section" class="form-label" />
                                <select id="section_id" class="form-select" v-model="form.section_id" required>
                                    <option value="" disabled>-- Select Section --</option>
                                    <option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.section_id" />
                            </div>

                            <!-- NEW: Group Selection Field -->
                            <div class="col-md-6">
                                <InputLabel for="group_id" value="Select Group" class="form-label" />
                                <select
                                    id="group_id"
                                    class="form-select"
                                    v-model="form.group_id"
                                    :required="isClass9Or10"
                                    :disabled="!isClass9Or10 && noneGroupId !== null"
                                >
                                    <option value="" disabled v-if="isClass9Or10">-- Select Group --</option>
                                    <option :value="noneGroupId" v-else-if="noneGroupId !== null">-- None --</option>
                                    <option value="" disabled v-else>-- No Groups Available --</option>

                                    <!-- Filter groups based on isClass9Or10 -->
                                    <template v-if="isClass9Or10">
                                        <option v-for="group in groups.filter(g => g.name !== 'None')" :key="group.id" :value="group.id">
                                            {{ group.name }}
                                        </option>
                                    </template>
                                </select>
                                <InputError class="mt-2" :message="form.errors.group_id" />
                            </div>

                            <div class="col-md-6">
                                <InputLabel for="status" value="Status" class="form-label" />
                                <select id="status" class="form-select" v-model.number="form.status" required>
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <Link :href="route('class-subjects.index')" class="btn btn-secondary me-3">Cancel</Link>
                            <PrimaryButton :class="{ 'opacity-75': form.processing }" :disabled="form.processing" class="btn btn-primary">
                                Assign Subject
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
