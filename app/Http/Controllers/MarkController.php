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

    /**
     * Store or update marks for students.
     */
    public function store(Request $request)
    {
        // 1. Validate incoming data, including the new 'exam_marks' field
        $request->validate([
            'class_id' => ['required', 'exists:class_names,id'],
            'session_id' => ['required', 'exists:class_sessions,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'exam_id' => ['required', 'exists:exams,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'marks_data' => ['required', 'array'],
            'marks_data.*.student_id' => ['required', 'exists:students,id'],
            'marks_data.*.class_test_marks' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'marks_data.*.assignment_marks' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'marks_data.*.exam_marks' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        $classId = $request->input('class_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $groupId = $request->input('group_id');
        $examId = $request->input('exam_id');
        $subjectId = $request->input('subject_id'); // Subject for which marks are being saved

        // ✨ CORRECTED LOGIC FOR totalClassDays START ✨
        // Get the ClassName instance using only class_id.
        // The 'total_classes' column is directly on the 'class_names' table.
        $className = ClassName::find($classId);

        // Use the total_classes from the ClassName model.
        // Provide a fallback (e.g., 26) if the column is null or ClassName not found.
        $totalClassDays = $className ? ($className->total_classes ?? 26) : 26;
        // This will retrieve the value from the 'total_classes' column in your 'class_names' table.
        // The 'session_id' is NOT used when querying the ClassName model here,
        // as it's not a column on the 'class_names' table.
        // ✨ CORRECTED LOGIC FOR totalClassDays END ✨

        // Define the maximum attendance marks for this exam/subject.
        $maxAttendanceMarksForExam = 10; // This remains 10 as per your requirement

        // 2. Loop through each student's marks data to store/update
        foreach ($request->input('marks_data') as $markData) {
            $studentId = $markData['student_id'];

            // Calculate student's present days for the SPECIFIC SUBJECT.
            // This is still subject-specific because attendance is usually recorded per subject.
            $presentDays = Attendance::where('student_id', $studentId)
                                     ->where('class_id', $classId)
                                     ->where('session_id', $sessionId)
                                     ->where('section_id', $sectionId)
                                     ->where('group_id', $groupId)
                                     ->where('subject_id', $subjectId) // Crucial: Filters by selected subject (e.g., Bangla)
                                     ->where('status', 'present') // Counts only 'present' records
                                     ->count();

            // Calculate attendance marks based on percentage for the SPECIFIC SUBJECT
            $attendanceMarks = 0;
            if ($totalClassDays > 0) {
                $attendancePercentage = ($presentDays / $totalClassDays);
                $attendanceMarks = round($attendancePercentage * $maxAttendanceMarksForExam, 2);
            }

            // 3. Update or create the Mark record
            Mark::updateOrCreate(
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
                    'class_test_marks' => $markData['class_test_marks'],
                    'assignment_marks' => $markData['assignment_marks'],
                    'exam_marks' => $markData['exam_marks'],
                    'attendance_marks' => $attendanceMarks, // Save calculated attendance marks
                ]
            );
        }

        // 4. Redirect with a success message
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
        $exams = Exam::all(['id', 'exam_name', 'total_marks']);
        $subjects = Subject::whereIn('id', $assignedSubjectIds)->get(['id', 'name']);

        $classId = $request->input('class_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $groupId = $request->input('group_id');
        $examId = $request->input('exam_id');
        $subjectId = $request->input('subject_id');

        $students = collect();
        $initialMessage = null;
        $selectedExamTotalMarks = 0;

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
                $selectedExam = Exam::find($examId);
                if ($selectedExam) {
                    $selectedExamTotalMarks = $selectedExam->total_marks; // This is the main exam's portion, e.g., 30 or 40
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

                    $maxAttendanceMarksForExam = 10;

                    foreach ($students as $student) {
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
            'selectedExamTotalMarks' => (int)$selectedExamTotalMarks, // Ensure it's an integer
        ]);
    }

    /**
     * Store or update marks for students.
     */
    public function teacherMarksStore(Request $request)
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

        // Get the total_marks for the selected exam to use in validation
        $examId = $request->input('exam_id');
        $selectedExam = Exam::find($examId);
        $examSpecificMaxMarks = $selectedExam ? $selectedExam->total_marks : 0; // e.g., 30 or 40

        // Define the fixed max marks for Class Test, Assignment, and Attendance
        $maxClassTestMarks = 5;
        $maxAssignmentMarks = 5;
        $maxAttendanceMarks = 10; // Attendance is calculated, but for total validation, we assume its max contribution

        $validator = Validator::make($request->all(), [
            'class_id' => ['required', 'exists:class_names,id'],
            'session_id' => ['required', 'exists:class_sessions,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'exam_id' => ['required', 'exists:exams,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'marks_data' => ['required', 'array'],
            'marks_data.*.student_id' => ['required', 'exists:students,id'],
            'marks_data.*.class_test_marks' => ['nullable', 'numeric', 'min:0', 'max:' . $maxClassTestMarks], // Max 5
            'marks_data.*.assignment_marks' => ['nullable', 'numeric', 'min:0', 'max:' . $maxAssignmentMarks], // Max 5
            'marks_data.*.exam_marks' => ['nullable', 'integer', 'min:0', 'max:' . $examSpecificMaxMarks],     // Max 30 or 40 dynamically
        ]);

        // ✨ Add a custom validation rule for the total marks per student ✨
        $validator->after(function ($validator) use ($request, $maxClassTestMarks, $maxAssignmentMarks, $maxAttendanceMarks, $examSpecificMaxMarks) {
            foreach ($request->input('marks_data') as $index => $markData) {
                $classTest = $markData['class_test_marks'] ?? 0;
                $assignment = $markData['assignment_marks'] ?? 0;
                $examMarks = $markData['exam_marks'] ?? 0;

                // Recalculate attendance marks for the specific student from the database
                // This is needed because attendance_marks is not sent from the frontend.
                $studentId = $markData['student_id'];
                $classId = $request->input('class_id');
                $sessionId = $request->input('session_id');
                $sectionId = $request->input('section_id');
                $groupId = $request->input('group_id');
                $subjectId = $request->input('subject_id');

                $className = ClassName::find($classId);
                $totalClassDays = $className ? ($className->total_classes ?? 26) : 26;
                $presentDays = Attendance::where('student_id', $studentId)
                                         ->where('class_id', $classId)
                                         ->where('session_id', $sessionId)
                                         ->where('section_id', $sectionId)
                                         ->where('group_id', $groupId)
                                         ->where('subject_id', $subjectId)
                                         ->where('status', 'present')
                                         ->count();

                $calculatedAttendanceMarks = 0;
                if ($totalClassDays > 0) {
                    $attendancePercentage = ($presentDays / $totalClassDays);
                    $calculatedAttendanceMarks = round($attendancePercentage * $maxAttendanceMarks, 2); // Max 10
                }
                
                // Ensure attendance marks do not exceed the max configured
                $calculatedAttendanceMarks = min($calculatedAttendanceMarks, $maxAttendanceMarks);


                $totalObtainedMarks = (float)$classTest + (float)$assignment + (float)$examMarks + (float)$calculatedAttendanceMarks;

                // Total allowed for the combined marks is 50
                if ($totalObtainedMarks > 50) {
                    $validator->errors()->add("marks_data.{$index}.total_marks", "Total marks for this student (Class Test + Assignment + Exam + Attendance) cannot exceed 50. Current total: " . $totalObtainedMarks);
                }
            }
        });

        if ($validator->fails()) {
            return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
        }

        // Check teacher authorization after validation (if valid, proceed)
        $classId = $request->input('class_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $groupId = $request->input('group_id');
        $examId = $request->input('exam_id');
        $subjectId = $request->input('subject_id');

        $isAuthorized = ClassSubject::where('teacher_id', $teacherId)
                                     ->where('class_name_id', $classId)
                                     ->where('session_id', $sessionId)
                                     ->where('section_id', $sectionId)
                                     ->where('subject_id', $subjectId)
                                     ->exists();

        if (!$isAuthorized) {
            abort(403, 'You are not authorized to add/update marks for this class/subject combination.');
        }

        // Re-get totalClassDays and maxAttendanceMarksForExam, though they are also in the custom validator
        // This keeps the loop clean for actual saving
        $className = ClassName::find($classId);
        $totalClassDays = $className ? ($className->total_classes ?? 26) : 26;
        $maxAttendanceMarksForExam = 10;

        foreach ($request->input('marks_data') as $markData) {
            $studentId = $markData['student_id'];

            // Recalculate attendance here for saving, ensuring consistency
            $presentDays = Attendance::where('student_id', $studentId)
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
            // Ensure attendance marks do not exceed the max configured
            $attendanceMarks = min($attendanceMarks, $maxAttendanceMarksForExam);


            Mark::updateOrCreate(
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
                    'class_test_marks' => $markData['class_test_marks'],
                    'assignment_marks' => $markData['assignment_marks'],
                    'exam_marks' => $markData['exam_marks'],
                    'attendance_marks' => $attendanceMarks,
                ]
            );
        }

        return redirect()->back()->with('flash', ['success' => 'Marks saved successfully!']);
    }
}

