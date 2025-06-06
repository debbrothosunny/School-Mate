<?php

namespace App\Http\Controllers;

use App\Models\ClassName;
use App\Models\ClassSession;
use App\Models\Group;
use App\Models\Section;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    /**
     * Display the attendance management page.
     */
    public function index(Request $request)
    {
        // Fetch all classes, sessions, sections, and groups for dropdowns
        $classes = ClassName::all(['id', 'name']);
        $sessions = ClassSession::all(['id', 'name']);
        $sections = Section::all(['id', 'name']);
        $groups = Group::all(['id', 'name']);

        // Get selected filters from request, or set sensible defaults for initial load
        // Note: Defaults are now strictly for initial population; validation happens before fetch
        $selectedClassId = $request->input('class_id') ? (int) $request->input('class_id') : ($classes->first()->id ?? null);
        $selectedSessionId = $request->input('session_id') ? (int) $request->input('session_id') : ($sessions->first()->id ?? null);
        $selectedSectionId = $request->input('section_id') ? (int) $request->input('section_id') : ($sections->first()->id ?? null);
        $selectedGroupId = $request->input('group_id') ? (int) $request->input('group_id') : null; // Group is now required for fetching
        $selectedDate = $request->input('date', Carbon::now()->toDateString());

        $students = collect();
        $message = null;

        // NEW: All filter criteria (including group_id) are now REQUIRED to fetch students
        if ($selectedClassId && $selectedSessionId && $selectedSectionId && $selectedGroupId && $selectedDate) {
            try {
                $studentsQuery = Student::where('class_id', $selectedClassId)
                                        ->where('session_id', $selectedSessionId)
                                        ->where('section_id', $selectedSectionId)
                                        ->where('group_id', $selectedGroupId) // Group is now a mandatory filter
                                        ->select('id', 'name');

                $students = $studentsQuery->get();

                if ($students->isEmpty()) {
                    $message = ['text' => 'No students found for the selected Class, Session, Section, Group, and Date combination.', 'type' => 'info'];
                } else {
                    // Fetch existing attendance records for these filtered students on the given date
                    $existingAttendances = Attendance::whereIn('student_id', $students->pluck('id'))
                                                     ->where('class_id', $selectedClassId)
                                                     ->where('session_id', $selectedSessionId)
                                                     ->where('section_id', $selectedSectionId)
                                                     ->where('group_id', $selectedGroupId) // Group is now a mandatory filter for existing attendance
                                                     ->where('date', $selectedDate)
                                                     ->get()
                                                     ->keyBy('student_id');

                    $students = $students->map(function ($student) use ($existingAttendances) {
                        $status = 'present';
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
             $message = ['text' => 'Please select Class, Session, Section, Group, and Date to view students.', 'type' => 'info'];
        }

        return Inertia::render('Attendance/Index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'sections' => $sections,
            'groups' => $groups,
            'selectedClassId' => $selectedClassId,
            'selectedSessionId' => $selectedSessionId,
            'selectedSectionId' => $selectedSectionId,
            'selectedGroupId' => $selectedGroupId,
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
            'group_id' => 'required|exists:groups,id', // NEW: group_id is now required in validation
            'date' => 'required|date_format:Y-m-d',
            'attendance_data' => 'required|array',
            'attendance_data.*.student_id' => 'required|exists:students,id',
            'attendance_data.*.status' => ['required', Rule::in(['present', 'absent', 'late'])],
        ]);

        $classId = $request->input('class_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $groupId = $request->input('group_id');
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
                        'group_id' => $groupId, // Ensure group_id is always passed
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
}

