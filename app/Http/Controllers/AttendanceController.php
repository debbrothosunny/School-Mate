<?php

namespace App\Http\Controllers;

use App\Models\ClassName;
use App\Models\ClassSession;
use App\Models\Group;
use App\Models\Section;
use App\Models\Student;
use App\Models\ClassSubject;
use App\Models\Attendance;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use App\Models\Subject;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    /**
     * Display the attendance management page.
    */  
     public function index(Request $request)
    {
        // Fetch all classes, sessions, sections, groups, and subjects for dropdowns
        $classes = ClassName::all(['id', 'class_name']);
        $sessions = ClassSession::all(['id', 'name']);
        $sections = Section::all(['id', 'name']);
        $groups = Group::all(['id', 'name']);
        $subjects = Subject::all(['id', 'name']); // ✨ Fetch all subjects

        // Get selected filters from request, or set sensible defaults for initial load
        $selectedClassId = $request->input('class_id') ? (int) $request->input('class_id') : ($classes->first()->id ?? null);
        $selectedSessionId = $request->input('session_id') ? (int) $request->input('session_id') : ($sessions->first()->id ?? null);
        $selectedSectionId = $request->input('section_id') ? (int) $request->input('section_id') : ($sections->first()->id ?? null);
        $selectedGroupId = $request->input('group_id') ? (int) $request->input('group_id') : null;
        $selectedSubjectId = $request->input('subject_id') ? (int) $request->input('subject_id') : ($subjects->first()->id ?? null); // ✨ Get selected subject ID
        $selectedDate = $request->input('date', Carbon::now()->toDateString());

        $students = collect();
        $message = null;

        // All filter criteria (including group_id and subject_id) are now REQUIRED to fetch students
        if ($selectedClassId && $selectedSessionId && $selectedSectionId && $selectedGroupId && $selectedSubjectId && $selectedDate) {
            try {
                $studentsQuery = Student::where('class_id', $selectedClassId)
                                         ->where('session_id', $selectedSessionId)
                                         ->where('section_id', $selectedSectionId)
                                         ->where('group_id', $selectedGroupId)
                                         // If students are directly linked to subjects in a meaningful way for attendance,
                                         // you might add ->where('subject_id', $selectedSubjectId) here.
                                         // However, typically attendance is class/section/group wide, not per-subject.
                                         // If attendance is per-subject, your Student model or a pivot table needs this relation.
                                         // For now, we'll assume students are fetched by class/session/section/group,
                                         // and subject_id is for filtering the attendance record itself.
                                         ->select('id', 'name');

                $students = $studentsQuery->get();

                if ($students->isEmpty()) {
                    $message = ['text' => 'No students found for the selected Class, Session, Section, Group, Subject, and Date combination.', 'type' => 'info'];
                } else {
                    // Fetch existing attendance records for these filtered students on the given date and subject
                    $existingAttendances = Attendance::whereIn('student_id', $students->pluck('id'))
                                                    ->where('class_id', $selectedClassId)
                                                    ->where('session_id', $selectedSessionId)
                                                    ->where('section_id', $selectedSectionId)
                                                    ->where('group_id', $selectedGroupId)
                                                    ->where('subject_id', $selectedSubjectId) // ✨ Filter by subject_id
                                                    ->where('date', $selectedDate)
                                                    ->get()
                                                    ->keyBy('student_id');

                    $students = $students->map(function ($student) use ($existingAttendances) {
                        $status = 'present'; // Default status for new entries
                        if ($existingAttendances->has($student->id)) {
                            $status = $existingAttendances[$student->id]->status;
                        }
                        return [
                            'id' => $student->id,
                            'name' => $student->name,
                            'status' => $status,
                        ];
                    });
                }
            } catch (\Exception $e) {
                $message = ['text' => 'Error fetching students: ' . $e->getMessage(), 'type' => 'error'];
            }
        } else {
            $message = ['text' => 'Please select Class, Session, Section, Group, Subject, and Date to view students.', 'type' => 'info'];
        }

        return Inertia::render('Attendance/Index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'sections' => $sections,
            'groups' => $groups,
            'subjects' => $subjects, // ✨ Pass subjects to the frontend
            'selectedClassId' => $selectedClassId,
            'selectedSessionId' => $selectedSessionId,
            'selectedSectionId' => $selectedSectionId,
            'selectedGroupId' => $selectedGroupId,
            'selectedSubjectId' => $selectedSubjectId, // ✨ Pass selected subject ID
            'selectedDate' => $selectedDate,
            'students' => $students,
            'initialMessage' => $message,
            'flash' => session('flash'),
        ]);
    }

    /**
     * Store or update attendance records.
     */
    public function store(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:class_names,id',
            'session_id' => 'required|exists:class_sessions,id',
            'section_id' => 'required|exists:sections,id',
            'group_id' => 'required|exists:groups,id',
            'subject_id' => 'required|exists:subjects,id', // ✨ Add subject_id to validation
            'date' => 'required|date_format:Y-m-d',
            'attendance_data' => 'required|array',
            'attendance_data.*.student_id' => 'required|exists:students,id',
            'attendance_data.*.status' => ['required', Rule::in(['present', 'absent', 'late'])],
        ]);

        $classId = $request->input('class_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $groupId = $request->input('group_id');
        $subjectId = $request->input('subject_id'); // ✨ Get subject_id from request
        $date = Carbon::parse($request->input('date'))->toDateString();
        $attendanceData = $request->input('attendance_data');

        try {
            foreach ($attendanceData as $record) {
                Attendance::updateOrCreate(
                    [
                        'student_id' => $record['student_id'],
                        'class_id' => $classId,
                        'session_id' => $sessionId,
                        'section_id' => $sectionId,
                        'group_id' => $groupId,
                        'subject_id' => $subjectId, // ✨ Ensure subject_id is part of the unique key
                        'date' => $date,
                    ],
                    [
                        'status' => $record['status'],
                    ]
                );
            }
            return redirect()->back()->with('flash', ['success' => 'Attendance saved successfully!']);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', ['error' => 'Failed to save attendance: ' . $e->getMessage()]);
        }
    }




    /**
     * Display the teacher attendance management page.
    */

    public function teacherAttendanceIndex(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Unauthorized. Please log in.');
        }

        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) {
            abort(403, 'You are not registered as a teacher or your teacher profile is incomplete.');
        }

        // Fetch classes where the authenticated teacher is explicitly the class teacher
        $classes = ClassName::where('teacher_id', $teacher->id)->get(['id', 'class_name']);
        
        $initialMessage = null;

        // If no classes assigned, set a flash error message and render page
        if ($classes->isEmpty()) {
            session()->flash('message', [
                'type' => 'error',
                'text' => 'You are not the class teacher for any class. Attendance management is not available for you.',
            ]);
            return Inertia::render('TeacherAttendance/Index', [
                'classes' => $classes,
                'sessions' => collect(),
                'sections' => collect(),
                'groups' => collect(),
                'students' => collect(),
                'selectedClassId' => null,
                'selectedSessionId' => null,
                'selectedSectionId' => null,
                'selectedGroupId' => null,
                'selectedAttendanceDate' => null,
            ]);
        }
        
        // Fetch all sessions, sections, and groups for dropdowns
        $sessions = ClassSession::all(['id', 'name']);
        $sections = Section::all(['id', 'name']);
        $groups = Group::all(['id', 'name']);
        
        $classId = $request->input('class_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $groupId = $request->input('group_id');
        $attendanceDate = $request->input('attendance_date');

        $students = collect();

        // Authorization check: Ensure the selected class belongs to the authenticated teacher.
        if ($classId && !$classes->pluck('id')->contains($classId)) {
            session()->flash('message', [
                'type' => 'error',
                'text' => 'You are not the class teacher for the selected class.',
            ]);
        }

        // Fetch students and their attendance if all required filters are present
        if ($classId && $sessionId && $sectionId && $groupId && $attendanceDate) {
            $students = Student::where('class_id', $classId)
                ->where('session_id', $sessionId)
                ->where('section_id', $sectionId)
                ->where('group_id', $groupId)
                ->get();

            if ($students->isEmpty()) {
                session()->flash('message', [
                    'type' => 'info',
                    'text' => 'No students found for the selected criteria.',
                ]);
            } else {
                // Check if attendance already collected and set flash info if so
                $existingAttendances = Attendance::where('date', $attendanceDate)
                    ->where('class_id', $classId)
                    ->where('session_id', $sessionId)
                    ->where('section_id', $sectionId)
                    ->where('group_id', $groupId)
                    ->whereIn('student_id', $students->pluck('id'))
                    ->get()
                    ->keyBy('student_id');

                if ($existingAttendances->isNotEmpty()) {
                    session()->flash('message', [
                        'type' => 'info',
                        'text' => "Attendance saved successfully!",
                    ]);
                }

                // Attach attendance status to each student
                foreach ($students as $student) {
                    $student->attendance_status = $existingAttendances->has($student->id) ? $existingAttendances[$student->id]->status : 'absent';
                }
            }
        } elseif (!$classId || !$sessionId || !$sectionId || !$groupId || !$attendanceDate) {
            session()->flash('message', [
                'type' => 'info',
                'text' => 'Please select all required filters (Class, Session, Section, Group, and Attendance Date) to manage attendance.',
            ]);
        }

        return Inertia::render('TeacherAttendance/Index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'sections' => $sections,
            'groups' => $groups,
            'students' => $students,
            'selectedClassId' => (int)$classId,
            'selectedSessionId' => (int)$sessionId,
            'selectedSectionId' => (int)$sectionId,
            'selectedGroupId' => (int)$groupId,
            'selectedAttendanceDate' => $attendanceDate,
        ]);
    }

    /**
     * Store or update attendance records for a class.
     */
    public function teacherAttendanceStore(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            abort(403, 'Unauthorized. Please log in.');
        }

        $teacher = Teacher::where('user_id', $user->id)->first();
        if (!$teacher) {
            abort(403, 'You are not registered as a teacher or your teacher profile is incomplete.');
        }

        $request->validate([
            'class_id' => ['required', 'exists:class_names,id'],
            'session_id' => ['required', 'exists:class_sessions,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'group_id' => ['required', 'exists:groups,id'],
            'attendance_date' => ['required', 'date', 'before_or_equal:today'],
            'attendance_data' => ['required', 'array'],
            'attendance_data.*.student_id' => ['required', 'exists:students,id'],
            'attendance_data.*.status' => ['required', 'string', Rule::in(['present', 'absent', 'late'])],
        ]);

        $classId = $request->input('class_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $groupId = $request->input('group_id');
        $attendanceDate = $request->input('attendance_date');

        // Authorization: Check teacher is authorized for the class
        $isAuthorized = ClassName::where('id', $classId)
            ->where('teacher_id', $teacher->id)
            ->exists();

        if (!$isAuthorized) {
            abort(403, 'You are not authorized to manage attendance for this class.');
        }

        foreach ($request->input('attendance_data') as $data) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $data['student_id'],
                    'class_id' => $classId,
                    'session_id' => $sessionId,
                    'section_id' => $sectionId,
                    'group_id' => $groupId,
                    'date' => $attendanceDate,
                ],
                ['status' => $data['status']]
            );
        }

        return redirect()->back()->with('message', [
            'type' => 'success',
            'text' => 'Attendance saved successfully!',
        ]);
    
    }



}

