<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, Link, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import Swal from 'sweetalert2'; // Assuming Swal is imported

const flash = computed(() => usePage().props.flash || {});

const form = useForm({
    name: '',
    // REMOVED: full_marks and passing_marks (These are now calculated in the backend)
    // 🚀 NEW MARK BREAKDOWN FIELDS
    subjective_full_marks: 0,
    objective_full_marks: 0,
    practical_full_marks: 0,
    subjective_passing_marks: 0,
    objective_passing_marks: 0,
    practical_passing_marks: 0,
    status: 0,
});

// 💡 COMPUTED PROPERTY: Calculate total full marks for display
const totalFullMarks = computed(() => {
    return (
        parseInt(form.subjective_full_marks || 0) +
        parseInt(form.objective_full_marks || 0) +
        parseInt(form.practical_full_marks || 0)
    );
});

// 💡 COMPUTED PROPERTY: Calculate total passing marks for display
const totalPassingMarks = computed(() => {
    return (
        parseInt(form.subjective_passing_marks || 0) +
        parseInt(form.objective_passing_marks || 0) +
        parseInt(form.practical_passing_marks || 0)
    );
});

const submit = () => {
    form.post(route('subjects.store'), {
        onSuccess: () => form.reset(),
        onError: (errors) => console.error("Subject creation failed:", errors),
    });
};

watchEffect(() => {   
    if (flash.value?.message) {
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
    <Head title="Add New Subject" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Add New Subject</h2>
        </template>

        <div class="py-12 bg-gray-100 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6 sm:p-8">
                    <form @submit.prevent="submit" class="space-y-8">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <InputLabel for="name" class="block mb-1">
                                    Subject Name <span class="font-bold text-red-500">*</span>
                                </InputLabel>
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="off"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div> 
                                <InputLabel for="status" class="block mb-1">
                                    Status 
                                </InputLabel>
                                <select
                                    id="status"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                    v-model.number="form.status"
                                    required
                                >
                                    <option :value="0">Active</option>
                                    <option :value="1">Inactive</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.status" />
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-6 space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Marks Breakdown (Full Marks)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                
                                <div>
                                    <InputLabel for="subjective_full_marks" class="block mb-1">
                                        Subjective <span class="font-bold text-red-500">*</span>
                                    </InputLabel>
                                    <TextInput
                                        id="subjective_full_marks"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        v-model.number="form.subjective_full_marks"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.subjective_full_marks" />
                                </div>
                                
                                <div>
                                    <InputLabel for="objective_full_marks" class="block mb-1">
                                        Objective <span class="font-bold text-red-500">*</span>
                                    </InputLabel>
                                    <TextInput
                                        id="objective_full_marks"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        v-model.number="form.objective_full_marks"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.objective_full_marks" />
                                </div>

                                <div>
                                    <InputLabel for="practical_full_marks" class="block mb-1">
                                        Practical <span class="font-bold text-red-500">*</span>
                                    </InputLabel>
                                    <TextInput
                                        id="practical_full_marks"
                                        type="number"
                                        min="0"
                                        class="mt-1 block w-full"
                                        v-model.number="form.practical_full_marks"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.practical_full_marks" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-6 space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Marks Breakdown (Passing Marks)</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                
                                <div>
                                    <InputLabel for="subjective_passing_marks" class="block mb-1">
                                        Subjective (Max: {{ form.subjective_full_marks }}) <span class="font-bold text-red-500">*</span>
                                    </InputLabel>
                                    <TextInput
                                        id="subjective_passing_marks"
                                        type="number"
                                        min="0"
                                        :max="form.subjective_full_marks"
                                        class="mt-1 block w-full"
                                        v-model.number="form.subjective_passing_marks"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.subjective_passing_marks" />
                                </div>

                                <div>
                                    <InputLabel for="objective_passing_marks" class="block mb-1">
                                        Objective (Max: {{ form.objective_full_marks }}) <span class="font-bold text-red-500">*</span>
                                    </InputLabel>
                                    <TextInput
                                        id="objective_passing_marks"
                                        type="number"
                                        min="0"
                                        :max="form.objective_full_marks"
                                        class="mt-1 block w-full"
                                        v-model.number="form.objective_passing_marks"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.objective_passing_marks" />
                                </div>
                                
                                <div>
                                    <InputLabel for="practical_passing_marks" class="block mb-1">
                                        Practical (Max: {{ form.practical_full_marks }}) <span class="font-bold text-red-500">*</span>
                                    </InputLabel>
                                    <TextInput
                                        id="practical_passing_marks"
                                        type="number"
                                        min="0"
                                        :max="form.practical_full_marks"
                                        class="mt-1 block w-full"
                                        v-model.number="form.practical_passing_marks"
                                        required
                                    />
                                    <InputError class="mt-2" :message="form.errors.practical_passing_marks" />
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-indigo-50 dark:bg-indigo-900/20 p-4 rounded-lg text-sm font-medium grid grid-cols-2 gap-4">
                            <p class="text-gray-700 dark:text-gray-300">
                                **Total Full Marks (Calculated):** <span class="text-indigo-600 dark:text-indigo-400 font-bold ml-1">{{ totalFullMarks }}</span>
                            </p>
                            <p class="text-gray-700 dark:text-gray-300">
                                **Total Passing Marks (Calculated):** <span class="text-indigo-600 dark:text-indigo-400 font-bold ml-1">{{ totalPassingMarks }}</span>
                            </p>
                            <InputError class="mt-2 col-span-2" :message="form.errors.full_marks" />
                            <InputError class="mt-2 col-span-2" :message="form.errors.passing_marks" />
                        </div>


                        <div class="flex items-center justify-end pt-6 border-t dark:border-gray-700 mt-6">
                            <Link :href="route('subjects.index')" class="text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 mr-4 transition duration-150 ease-in-out">
                                Cancel
                            </Link>
                            <PrimaryButton :class="{ 'opacity-50': form.processing }" :disabled="form.processing || totalFullMarks < 1">
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Add Subject
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>