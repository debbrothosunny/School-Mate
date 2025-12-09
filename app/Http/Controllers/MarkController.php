<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Mark;
use App\Models\Student;
use App\Models\ClassName; // Assuming Class is named ClassName
use App\Models\ClassSession; // Assuming Session is named ClassSession
use App\Models\Section;
use App\Models\Group;
use App\Models\Exam;
use App\Models\Teacher;
use App\Models\ClassSubject;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


class MarkController extends Controller
{
    /**
     * Display a listing of the marks.
    */
    public function index(Request $request)
    {
        // Get filter parameters from the request
        $classId = $request->input('class_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $groupId = $request->input('group_id');
        $examId = $request->input('exam_id');
        $subjectId = $request->input('subject_id');

        $students = collect();
        $initialMessage = null;

        // Fetch all classes, sessions, sections, groups, exams, and subjects for dropdowns
        $classes = ClassName::all(['id', 'class_name']); // Assuming 'class_name' is the column name
        $sessions = ClassSession::all(['id', 'name']);
        $sections = Section::all(['id', 'name']);
        $groups = Group::all(['id', 'name']);
        $exams = Exam::all(['id', 'exam_name']); // Assuming 'exam_name' is the column name
        $subjects = Subject::all(['id', 'name']);

        // Fetch students and calculate attendance marks if all filters are present
         if ($classId && $sessionId && $sectionId && $groupId && $examId && $subjectId) {
        $students = Student::where('class_id', $classId)
                           ->where('session_id', $sessionId)
                           ->where('section_id', $sectionId)
                           ->where('group_id', $groupId)
                           ->get();

        if ($students->isEmpty()) {
            $initialMessage = ['text' => 'No students found for the selected criteria.', 'type' => 'info'];
        } else {
            // ✨ MODIFICATION FOR totalClassDays START ✨
            // Fetch the ClassName to get the total_classes for this specific class
            $className = ClassName::find($classId);

            // Use the total_classes from the ClassName model.
            // Provide a fallback (e.g., 26) if the column is null or ClassName not found.
            $totalClassDays = $className ? ($className->total_classes ?? 26) : 26;
            // This will retrieve the value from the 'total_classes' column in your 'class_names' table.
            // ✨ MODIFICATION FOR totalClassDays END ✨

            $maxAttendanceMarksForExam = 10; // This remains 10 as per your requirement

            foreach ($students as $student) {
                // Count 'present' attendance records for the current student, specific to the selected subject
                $presentDays = Attendance::where('student_id', $student->id)
                                         ->where('class_id', $classId)
                                         ->where('session_id', $sessionId)
                                         ->where('section_id', $sectionId)
                                         ->where('group_id', $groupId)
                                         ->where('subject_id', $subjectId)
                                         ->where('status', 'present')
                                         ->count();

                $attendanceMarks = 0;
                if ($totalClassDays > 0) {
                    $attendancePercentage = ($presentDays / $totalClassDays);
                    $attendanceMarks = round($attendancePercentage * $maxAttendanceMarksForExam, 2);
                }

                $existingMark = Mark::where('student_id', $student->id)
                                     ->where('class_id', $classId)
                                     ->where('session_id', $sessionId)
                                     ->where('section_id', $sectionId)
                                     ->where('group_id', $groupId)
                                     ->where('exam_id', $examId)
                                     ->where('subject_id', $subjectId)
                                     ->first();

                $student->class_test_marks = $existingMark ? $existingMark->class_test_marks : null;
                $student->assignment_marks = $existingMark ? $existingMark->assignment_marks : null;
                $student->attendance_marks = $attendanceMarks;
                $student->exam_marks = $existingMark ? $existingMark->exam_marks : null;
                $student->marks_id = $existingMark ? $existingMark->id : null;
            }
        }
    } else {
         $initialMessage = ['text' => 'Please select all required filters (Class, Session, Section, Group, Exam, and Subject) to view marks.', 'type' => 'info'];
    }

    // ... (unchanged code for returning Inertia render) ...
    return Inertia::render('Marks/Index', [
        'classes' => $classes,
        'sessions' => $sessions,
        'sections' => $sections,
        'groups' => $groups,
        'exams' => $exams,
        'subjects' => $subjects,
        'selectedClassId' => (int)$classId,
        'selectedSessionId' => (int)$sessionId,
        'selectedSectionId' => (int)$sectionId,
        'selectedGroupId' => (int)$groupId,
        'selectedExamId' => (int)$examId,
        'selectedSubjectId' => (int)$subjectId,
        'students' => $students,
        'initialMessage' => $initialMessage,
    ]);
    }

    // Store or update marks for students.
    public function store(Request $request)
    {
        // 1. Validate incoming data
        $request->validate([
            'class_id' => ['required', 'exists:class_names,id'],
            'session_id' => ['required', 'exists:class_sessions,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'exam_id' => ['required', 'exists:exams,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'marks_data' => ['required', 'array'],
            'marks_data.*.student_id' => ['required', 'exists:students,id'],

            // Updated Mark Component Fields
            'marks_data.*.subjective_marks' => ['nullable', 'numeric', 'min:0'],
            'marks_data.*.objective_marks' => ['nullable', 'numeric', 'min:0'],
            'marks_data.*.practical_marks' => ['nullable', 'numeric', 'min:0'],
            // Note: 'attendance_marks' is calculated, not directly validated here.
        ]);

        $classId = $request->input('class_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $groupId = $request->input('group_id');
        $examId = $request->input('exam_id');
        $subjectId = $request->input('subject_id'); // Subject for which marks are being saved

        // Retrieve total class days from the ClassName model
        $className = ClassName::find($classId);
        $totalClassDays = $className ? ($className->total_classes ?? 26) : 26;
        $maxAttendanceMarksForExam = 10;

        // 2. Loop through each student's marks data to store/update
        foreach ($request->input('marks_data') as $markData) {
            $studentId = $markData['student_id'];

            // Calculate student's present days for the SPECIFIC SUBJECT.
            $presentDays = Attendance::where('student_id', $studentId)
                                            ->where('class_id', $classId)
                                            ->where('session_id', $sessionId)
                                            ->where('section_id', $sectionId)
                                            ->where('group_id', $groupId)
                                            ->where('subject_id', $subjectId)
                                            ->where('status', 'present')
                                            ->count();

            // Calculate attendance marks based on percentage
            $attendanceMarks = 0;
            if ($totalClassDays > 0) {
                $attendancePercentage = ($presentDays / $totalClassDays);
                $attendanceMarks = round($attendancePercentage * $maxAttendanceMarksForExam, 2);
            }

            // 3. Update or create the Mark record
            $markRecord = Mark::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'class_id' => $classId,
                    'session_id' => $sessionId,
                    'section_id' => $sectionId,
                    'group_id' => $groupId,
                    'exam_id' => $examId,
                    'subject_id' => $subjectId,
                ],
                [
                    // Use the new component fields
                    'subjective_marks' => $markData['subjective_marks'] ?? null,
                    'objective_marks' => $markData['objective_marks'] ?? null,
                    'practical_marks' => $markData['practical_marks'] ?? null,
                    'attendance_marks' => $attendanceMarks, // Save calculated attendance marks
                    'is_absent' => $markData['is_absent'] ?? false, // Assuming is_absent flag is also passed
                ]
            );

            // 4. Recalculate and cache the total marks using the model's accessor.
            // This ensures the cached column 'total_marks_obtained' is always up-to-date.
            $markRecord->total_marks_obtained = $markRecord->total_subject_marks_obtained; // Accessor handles the calculation
            $markRecord->save();
        }

        // 5. Redirect with a success message
        return redirect()->back()->with('flash', ['success' => 'Marks saved successfully!']);
    }



    // Teacher Side To create his class Mark Add

    public function teacherMarksIndex(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized. Please log in.');
        }

        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) {
            abort(403, 'You are not registered as a teacher or your teacher profile is incomplete.');
        }
        $teacherId = $teacher->id;

        $teacherAssignments = ClassSubject::where('teacher_id', $teacherId)->get();

        $assignedClassIds = $teacherAssignments->pluck('class_name_id')->unique()->toArray();
        $assignedSessionIds = $teacherAssignments->pluck('session_id')->unique()->toArray();
        $assignedSectionIds = $teacherAssignments->pluck('section_id')->unique()->toArray();
        $assignedSubjectIds = $teacherAssignments->pluck('subject_id')->unique()->toArray();

        $classes = ClassName::whereIn('id', $assignedClassIds)->get(['id', 'class_name']);
        $sessions = ClassSession::whereIn('id', $assignedSessionIds)->get(['id', 'name']);
        $sections = Section::whereIn('id', $assignedSectionIds)->get(['id', 'name']);
        $groups = Group::all(['id', 'name']);
        $exams = Exam::all(['id', 'exam_name']);
        $subjects = Subject::whereIn('id', $assignedSubjectIds)->get(['id', 'name', 'full_marks', 'subjective_full_marks', 'objective_full_marks', 'practical_full_marks']);

        $classId = $request->input('class_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $groupId = $request->input('group_id');
        $examId = $request->input('exam_id');
        $subjectId = $request->input('subject_id');

        $students = collect();
        $initialMessage = null;

        // Default max marks for display (will be updated if a subject is selected)
        $selectedExamTotalMarks = 0; 
        $maxAttendanceMarksForExam = 10;
        $selectedSubject = null;

        // Validate selected filters against teacher's assignments
        if ($classId && !in_array($classId, $assignedClassIds)) {
            abort(403, 'Unauthorized class selection.');
        }
        if ($sessionId && !in_array($sessionId, $assignedSessionIds)) {
            abort(403, 'Unauthorized session selection.');
        }
        if ($sectionId && !in_array($sectionId, $assignedSectionIds)) {
            abort(403, 'Unauthorized section selection.');
        }
        if ($subjectId && !in_array($subjectId, $assignedSubjectIds)) {
            abort(403, 'Unauthorized subject selection.');
        }
        
        if ($subjectId) {
            $selectedSubject = Subject::find($subjectId);
        }

        if ($classId && $sessionId && $sectionId && $groupId && $examId && $subjectId) {
            $specificAssignmentExists = ClassSubject::where('teacher_id', $teacherId)
                ->where('class_name_id', $classId)
                ->where('session_id', $sessionId)
                ->where('section_id', $sectionId)
                ->where('subject_id', $subjectId)
                ->exists();

            if (!$specificAssignmentExists) {
                $initialMessage = ['text' => 'You are not assigned to this specific Class, Session, Section, and Subject combination.', 'type' => 'error'];
            } else {
                // Calculate dynamic total marks based on subject config for display
                if ($selectedSubject) {
                    $subjectiveMax = (float)($selectedSubject->subjective_full_marks ?? 0);
                    $objectiveMax = (float)($selectedSubject->objective_full_marks ?? 0);
                    $practicalMax = (float)($selectedSubject->practical_full_marks ?? 0);
                    
                    $selectedExamTotalMarks = $subjectiveMax + $objectiveMax + $practicalMax + $maxAttendanceMarksForExam;

                    // Fallback to the overall full_marks if component marks are not set
                    if ($selectedExamTotalMarks <= $maxAttendanceMarksForExam) {
                        $selectedExamTotalMarks = (float)($selectedSubject->full_marks ?? 100);
                    }
                }

                $students = Student::where('class_id', $classId)
                    ->where('session_id', $sessionId)
                    ->where('section_id', $sectionId)
                    ->where('group_id', $groupId)
                    ->get();

                if ($students->isEmpty()) {
                    $initialMessage = ['text' => 'No students found for the selected criteria.', 'type' => 'info'];
                } else {
                    $className = ClassName::find($classId);
                    $totalClassDays = $className ? ($className->total_classes ?? 26) : 26;

                    foreach ($students as $student) {
                        // Count attendance WITHOUT subject filter to calculate class-wise attendance
                        $presentDays = Attendance::where('student_id', $student->id)
                            ->where('class_id', $classId)
                            ->where('session_id', $sessionId)
                            ->where('section_id', $sectionId)
                            ->where('group_id', $groupId)
                            // The subject_id filter is intentionally excluded here to count class-wise attendance
                            ->where('status', 'present')
                            ->count();

                        $attendanceMarks = 0;
                        if ($totalClassDays > 0) {
                            $attendancePercentage = ($presentDays / $totalClassDays);
                            $attendanceMarks = round($attendancePercentage * $maxAttendanceMarksForExam, 2);
                        }
                        $attendanceMarks = min($attendanceMarks, $maxAttendanceMarksForExam);


                        $existingMark = Mark::where('student_id', $student->id)
                            ->where('class_id', $classId)
                            ->where('session_id', $sessionId)
                            ->where('section_id', $sectionId)
                            ->where('group_id', $groupId)
                            ->where('exam_id', $examId)
                            ->where('subject_id', $subjectId)
                            ->first();

                        // Map the new mark component fields
                        $student->subjective_marks = $existingMark ? $existingMark->subjective_marks : null;
                        $student->objective_marks = $existingMark ? $existingMark->objective_marks : null;
                        $student->practical_marks = $existingMark ? $existingMark->practical_marks : null;

                        $student->attendance_marks = $attendanceMarks;
                        $student->marks_id = $existingMark ? $existingMark->id : null;
                    }
                }
            }
        } else {
            $initialMessage = ['text' => 'Please select all required filters (Class, Session, Section, Group, Exam, and Subject) to view marks.', 'type' => 'info'];
        }

        return Inertia::render('TeacherMarks/Index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'sections' => $sections,
            'groups' => $groups,
            'exams' => $exams,
            'subjects' => $subjects,
            'selectedClassId' => (int)$classId,
            'selectedSessionId' => (int)$sessionId,
            'selectedSectionId' => (int)$sectionId,
            'selectedGroupId' => (int)$groupId,
            'selectedExamId' => (int)$examId,
            'selectedSubjectId' => (int)$subjectId,
            'students' => $students,
            'initialMessage' => $initialMessage,
            'selectedExamTotalMarks' => (int)$selectedExamTotalMarks, // Updated to dynamic value
        ]);
    }


    // Teacher Side To Store his class Mark Add
    public function teacherMarksStore(Request $request)
    {
        // 1. Authentication and Teacher Profile Check
        $user = Auth::user();
        if (!$user) {
            // Log this or handle it better, but for now, simple abort
            abort(403, 'Unauthorized. Please log in.');
        }

        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) {
            abort(403, 'You are not registered as a teacher or your teacher profile is incomplete.');
        }
        $teacherId = $teacher->id;

        // Fetch exam and subject models early to use for validation
        $examId = $request->input('exam_id');
        $subjectId = $request->input('subject_id');
        $selectedExam = Exam::find($examId);
        $selectedSubject = Subject::find($subjectId);

        if (!$selectedSubject) {
            return redirect()->back()->withErrors(['subject_id' => 'The selected subject could not be found or is invalid.'])->withInput();
        }

        // Using 100 as a safe upper bound for individual components if not configured
        $examSpecificMaxMarks = 100;

        // 2. Initial Validation (using Validator::make to allow for custom 'after' hook)
        $validator = Validator::make($request->all(), [
            'class_id' => ['required', 'exists:class_names,id'],
            'session_id' => ['required', 'exists:class_sessions,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'exam_id' => ['required', 'exists:exams,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'marks_data' => ['required', 'array'],
            'marks_data.*.student_id' => ['required', 'exists:students,id'],

            // Validate component fields against the subject's maximum configured marks
            // Note: The 'max' here prevents marks > component max.
            'marks_data.*.subjective_marks' => ['nullable', 'numeric', 'min:0', 'max:' . ((float)($selectedSubject->subjective_full_marks ?? $examSpecificMaxMarks))],
            'marks_data.*.objective_marks' => ['nullable', 'numeric', 'min:0', 'max:' . ((float)($selectedSubject->objective_full_marks ?? $examSpecificMaxMarks))],
            'marks_data.*.practical_marks' => ['nullable', 'numeric', 'min:0', 'max:' . ((float)($selectedSubject->practical_full_marks ?? $examSpecificMaxMarks))],
            'marks_data.*.is_absent' => ['nullable', 'boolean'],
        ]);

        // 3. Custom Validation (Total Marks Constraint: subjective + objective + practical <= Subject's Total Full Marks)
        $validator->after(function ($validator) use ($request, $selectedSubject) {

            // --- DYNAMIC MAX TOTAL CALCULATION ---
            $subjectiveMax = (float)($selectedSubject->subjective_full_marks ?? 0);
            $objectiveMax = (float)($selectedSubject->objective_full_marks ?? 0);
            $practicalMax = (float)($selectedSubject->practical_full_marks ?? 0);

            // Calculate the total based on component full marks
            $maxTotal = $subjectiveMax + $objectiveMax + $practicalMax;

            // Fallback: If component marks are zeroed out, use the overall full_marks for the limit.
            if ($maxTotal <= 0 && (float)($selectedSubject->full_marks ?? 0) > 0) {
                $maxTotal = (float)($selectedSubject->full_marks);
            }
            // --- END DYNAMIC MAX TOTAL CALCULATION ---

            foreach ($request->input('marks_data') as $index => $markData) {

                // Skip total validation if student is marked absent
                if ($markData['is_absent'] ?? false) {
                    continue;
                }

                $subjective = $markData['subjective_marks'] ?? 0;
                $objective = $markData['objective_marks'] ?? 0;
                $practical = $markData['practical_marks'] ?? 0;

                // Sum all components
                $totalObtainedMarks = (float)$subjective + (float)$objective + (float)$practical;

                if ($totalObtainedMarks > $maxTotal) {
                    // This error is crucial for the front-end to show the total marks issue
                    $validator->errors()->add("marks_data.{$index}.total_marks", "Total marks for this student (Subjective + Objective + Practical) cannot exceed {$maxTotal} (Max Subject Total). Current total: " . $totalObtainedMarks);
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // 4. Authorization Check (Teacher must be assigned to this Class/Subject)
        $classId = $request->input('class_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $groupId = $request->input('group_id');
        $subjectId = $request->input('subject_id');

        $isAuthorized = ClassSubject::where('teacher_id', $teacherId)
            ->where('class_name_id', $classId)
            ->where('session_id', $sessionId)
            ->where('section_id', $sectionId)
            ->where('group_id', $groupId)
            ->where('subject_id', $subjectId)
            ->exists();

        if (!$isAuthorized) {
            abort(403, 'You are not authorized to add/update marks for this class/subject combination.');
        }

        // 5. Data Saving Loop
        foreach ($request->input('marks_data') as $markData) {
            $studentId = $markData['student_id'];

            // Update or create the Mark record
            $markRecord = Mark::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'class_id' => $classId,
                    'session_id' => $sessionId,
                    'section_id' => $sectionId,
                    'group_id' => $groupId,
                    'exam_id' => $examId,
                    'subject_id' => $subjectId,
                ],
                [
                    // Use the new component fields
                    'subjective_marks' => $markData['subjective_marks'] ?? null,
                    'objective_marks' => $markData['objective_marks'] ?? null,
                    'practical_marks' => $markData['practical_marks'] ?? null,
                    'is_absent' => $markData['is_absent'] ?? false,
                ]
            );

            // Assuming your Mark model has a total_subject_marks_obtained accessor/mutator
            // that calculates the total from the components (subjective + objective + practical).
            $markRecord->total_marks_obtained = $markRecord->total_subject_marks_obtained;
            $markRecord->save();
        }

        // 6. Redirect with a success message
        // FIX: Changed from with('flash', ['success' => ...]) to the standard with('success', ...)
        return redirect()->back()->with('success', 'Marks saved successfully!');
    }


   
}

