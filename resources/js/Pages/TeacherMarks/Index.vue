<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { watch, computed } from 'vue';

// FIX: The defineProps syntax was already corrected in the initial prompt
const props = defineProps({
	classes: Array,
	sessions: Array,
	sections: Array,
	groups: Array,
	exams: Array,
	subjects: Array,
	students: Array,
	selectedClassId: Number,
	selectedSessionId: Number,
	selectedSectionId: Number,
	selectedGroupId: Number,
	selectedExamId: Number,
	selectedSubjectId: Number,
	initialMessage: Object,
	selectedExamTotalMarks: { type: Number, default: 0 },
});

const filterForm = useForm({
	class_id: props.selectedClassId || '',
	session_id: props.selectedSessionId || '',
	section_id: props.selectedSectionId || '',
	group_id: props.selectedGroupId || '',
	exam_id: props.selectedExamId || '',
	subject_id: props.selectedSubjectId || '',
});

const marksForm = useForm({
	class_id: filterForm.class_id,
	session_id: filterForm.session_id,
	section_id: filterForm.section_id,
	group_id: filterForm.group_id,
	exam_id: filterForm.exam_id,
	subject_id: filterForm.subject_id,
	marks_data: props.students.map(student => ({
		student_id: student.id,
		// Existing mark components
		subjective_marks: student.subjective_marks ?? null,
		objective_marks: student.objective_marks ?? null,
		practical_marks: student.practical_marks ?? null,
        // Assuming 'is_absent' might be needed later, but sticking to provided structure.
        // is_absent: student.is_absent ?? false, 
	})),
});

// Watchers to keep marksForm filtered values in sync
watch(() => filterForm.class_id, val => { marksForm.class_id = val; });
watch(() => filterForm.session_id, val => { marksForm.session_id = val; });
watch(() => filterForm.section_id, val => { marksForm.section_id = val; });
watch(() => filterForm.group_id, val => { marksForm.group_id = val; });
watch(() => filterForm.exam_id, val => { marksForm.exam_id = val; });
watch(() => filterForm.subject_id, val => { marksForm.subject_id = val; });

// Watcher for students prop changes (when filters are applied)
watch(() => props.students, newStudents => {
	marksForm.marks_data = newStudents.map(student => ({
		student_id: student.id,
		// Existing mark components are mapped here
		subjective_marks: student.subjective_marks ?? null,
		objective_marks: student.objective_marks ?? null,
		practical_marks: student.practical_marks ?? null,
	}));
}, { deep: true });

const applyFilters = () => {
	router.get(route('teachermarks.index'), filterForm.data(), {
		preserveState: true,
		preserveScroll: true,
	});
};

const submitMarks = () => {
	// The onSuccess callback was removed as per the original component's comment,
    // relying on the redirect()->back() and flash message. This is correct.
	marksForm.post(route('teachermarks.store'));
};

const canSubmit = computed(() =>
	filterForm.class_id &&
	filterForm.session_id &&
	filterForm.section_id &&
	filterForm.group_id &&
	filterForm.exam_id &&
	filterForm.subject_id &&
	props.students.length > 0 &&
	!marksForm.processing &&
	Object.keys(marksForm.errors).length === 0
);

const getMessageClass = (type) => {
	if (type === 'success') return 'bg-green-50 border border-green-400 text-green-700';
	if (type === 'error') return 'bg-red-50 border border-red-400 text-red-700';
	if (type === 'info') return 'bg-blue-50 border border-blue-400 text-blue-700';
	return '';
};
</script>

<template>
	<Head title="Manage Student Marks" />
	<AuthenticatedLayout>
		<template #header>
			<h2 class="font-semibold text-2xl text-gray-800 leading-tight tracking-wide">Manage Student Marks</h2>
		</template>

		<div v-if="$page.props.flash?.success" 
			class="p-4 mb-6 rounded border border-green-400 bg-green-50 text-green-700 font-medium"
			role="alert" 
			aria-live="polite" aria-atomic="true">
			{{ $page.props.flash.success }}
		</div>

		<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

			<div v-if="props.initialMessage"
				:class="['p-4 rounded border font-semibold', getMessageClass(props.initialMessage.type)]"
				role="alert"
				aria-live="polite" aria-atomic="true">
				{{ props.initialMessage.text }}
			</div>

			<div v-if="marksForm.hasErrors"
				class="p-4 rounded bg-red-50 border border-red-400 text-red-700 text-sm"
				role="alert">
				<ul class="list-disc list-inside space-y-1">
					<li v-for="(error, idx) in Object.values(marksForm.errors)" :key="idx">{{ error }}</li>
				</ul>
			</div>

			<section aria-label="Filter students by class, session, section, group, exam, and subject">
				<h3 class="text-xl font-semibold text-gray-900 mb-4 border-b border-gray-300 pb-2">Filters</h3>
				<form @submit.prevent="applyFilters" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">

					<div>
						<label for="class_id" class="block text-sm font-medium text-gray-700 mb-1">Class</label>
						<select id="class_id" v-model="filterForm.class_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-300 focus:ring-opacity-50">
							<option value="">--Select Class--</option>
							<option v-for="cls in classes" :key="cls.id" :value="cls.id">{{ cls.class_name }}</option>
						</select>
					</div>

					<div>
						<label for="session_id" class="block text-sm font-medium text-gray-700 mb-1">Session</label>
						<select id="session_id" v-model="filterForm.session_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-300 focus:ring-opacity-50">
							<option value="">--Select Session--</option>
							<option v-for="session in sessions" :key="session.id" :value="session.id">{{ session.name }}</option>
						</select>
					</div>

					<div>
						<label for="section_id" class="block text-sm font-medium text-gray-700 mb-1">Section</label>
						<select id="section_id" v-model="filterForm.section_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-300 focus:ring-opacity-50">
							<option value="">--Select Section--</option>
							<option v-for="section in sections" :key="section.id" :value="section.id">{{ section.name }}</option>
						</select>
					</div>

					<div>
						<label for="group_id" class="block text-sm font-medium text-gray-700 mb-1">Group</label>
						<select id="group_id" v-model="filterForm.group_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-300 focus:ring-opacity-50">
							<option value="">--Select Group--</option>
							<option v-for="group in groups" :key="group.id" :value="group.id">{{ group.name }}</option>
						</select>
					</div>

					<div>
						<label for="exam_id" class="block text-sm font-medium text-gray-700 mb-1">Exam</label>
						<select id="exam_id" v-model="filterForm.exam_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-300 focus:ring-opacity-50">
							<option value="">--Select Exam--</option>
							<option v-for="exam in exams" :key="exam.id" :value="exam.id">{{ exam.exam_name }}</option>
						</select>
					</div>

					<div>
						<label for="subject_id" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
						<select id="subject_id" v-model="filterForm.subject_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-300 focus:ring-opacity-50">
							<option value="">--Select Subject--</option>
							<option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ subject.name }}</option>
						</select>
					</div>

					<div class="sm:col-span-2 md:col-span-3 text-right">
						<button type="submit"
							:disabled="filterForm.processing"
							class="inline-flex items-center px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50">
							{{ filterForm.processing ? 'Filtering...' : 'Apply Filters' }}
						</button>
					</div>

				</form>
			</section>

			<section v-if="filterForm.exam_id && props.selectedExamTotalMarks > 0" class="mt-8 p-4 bg-indigo-50 border border-indigo-300 text-indigo-900 rounded-md">
				<h3 class="text-lg font-semibold mb-2">Marks Breakdown</h3>
				<ul class="list-disc list-inside text-sm space-y-1">
					<li>Subjective Marks: Max (Configured for subject)</li>
					<li>Objective Marks: Max (Configured for subject)</li>
					<li>Practical Marks: Max (If applicable, configured for subject)</li>
				</ul>
				<p class="mt-3 font-bold text-indigo-700 text-sm">Please ensure the total marks entered adhere to the course structure for this subject and exam (Subjective + Objective + Practical $\le$ Subject Total).</p>
			</section>

			<section v-if="props.students && props.students.length > 0" class="mt-8">
				<h3 class="text-xl font-semibold mb-4">Enter Marks for Students</h3>

				<form @submit.prevent="submitMarks" novalidate>
					<div class="overflow-x-auto rounded-lg shadow border border-gray-300">
						<table class="min-w-full divide-y divide-gray-200">
							<thead class="bg-gray-100">
								<tr>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Student Name</th>
									<th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Roll</th>
									<th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Subjective Marks</th>
									<th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Objective Marks</th>
									<th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider">Practical Marks</th>
								</tr>
							</thead>
							<tbody class="bg-white divide-y divide-gray-200">
								<tr v-for="(student, index) in props.students" :key="student.id" class="hover:bg-indigo-50">
									<td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">
										{{ student.name }}
										<input type="hidden" :name="`marks_data[${index}][student_id]`" :value="student.id" />
									</td>
									<td class="px-6 py-4 whitespace-nowrap">{{ student.roll_number }}</td>

									<td class="px-6 py-4 whitespace-nowrap text-center">
										<input
											type="number"
											step="0.01" min="0" :max="props.selectedExamTotalMarks"
											v-model="marksForm.marks_data[index].subjective_marks"
											:name="`marks_data[${index}][subjective_marks]`"
											class="w-24 rounded-md border border-gray-300 text-center text-sm shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-300 focus:ring-opacity-50"
										/>
										<p v-if="marksForm.errors[`marks_data.${index}.subjective_marks`]" class="text-xs text-red-600 mt-1">
											{{ marksForm.errors[`marks_data.${index}.subjective_marks`] }}
										</p>
									</td>

									<td class="px-6 py-4 whitespace-nowrap text-center">
										<input
											type="number"
											step="0.01" min="0" :max="100"
											v-model="marksForm.marks_data[index].objective_marks"
											:name="`marks_data[${index}][objective_marks]`"
											class="w-24 rounded-md border border-gray-300 text-center text-sm shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-300 focus:ring-opacity-50"
										/>
										<p v-if="marksForm.errors[`marks_data.${index}.objective_marks`]" class="text-xs text-red-600 mt-1">
											{{ marksForm.errors[`marks_data.${index}.objective_marks`] }}
										</p>
									</td>

									<td class="px-6 py-4 whitespace-nowrap text-center">
										<input
											type="number"
											step="0.01" min="0" :max="100"
											v-model="marksForm.marks_data[index].practical_marks"
											:name="`marks_data[${index}][practical_marks]`"
											class="w-24 rounded-md border border-gray-300 text-center text-sm shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-300 focus:ring-opacity-50"
										/>
										<p v-if="marksForm.errors[`marks_data.${index}.practical_marks`]" class="text-xs text-red-600 mt-1">
											{{ marksForm.errors[`marks_data.${index}.practical_marks`] }}
										</p>
                                        <p v-if="marksForm.errors[`marks_data.${index}.total_marks`]" class="text-xs text-red-600 mt-1 font-semibold">
											{{ marksForm.errors[`marks_data.${index}.total_marks`] }}
										</p>
									</td>

								</tr>
							</tbody>
						</table>
					</div>

					<div class="mt-6 flex justify-end">
						<button
							type="submit"
							:disabled="marksForm.processing || !canSubmit"
							class="inline-flex items-center px-6 py-2 bg-indigo-600 text-white font-semibold rounded-md shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
						>
							{{ marksForm.processing ? 'Saving...' : 'Save Marks' }}
						</button>
					</div>
				</form>
			</section>

			<section v-else-if="filterForm.class_id && filterForm.session_id && filterForm.section_id && filterForm.group_id && filterForm.exam_id && filterForm.subject_id && !props.initialMessage"
				class="p-4 rounded bg-gray-100 text-gray-700 text-center font-medium">
				No students found for the selected criteria.
			</section>
		</div>
	</AuthenticatedLayout>
</template>