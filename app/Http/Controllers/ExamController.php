<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request ;
use App\Models\Exam;
use App\Models\ClassName;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Room;
use App\Models\ExamSchedule;
use App\Models\ClassSession; 
use App\Models\ExamSeatPlan; 
use App\Models\ExamTimeSlot; 
use App\Models\Group; 
use App\Models\Student; 
use App\Models\Setting; 
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Validation\Exception;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log; // For logging
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth; // For teacher/student authentication
use Illuminate\Support\Facades\DB; // For database transactions
use Carbon\Carbon; 


class ExamController extends Controller
{
    public function index(Request $request)
    {
        // Fetch all active sessions for filtering dropdown (can keep if dropdown is still desired for display only, but not for filtering query)
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);

        // Build the query to fetch exams with their associated session
        // No filters are applied here, so it fetches all exams
        $query = Exam::with('session');

        // Order results, e.g., by session name and then exam name
        $exams = $query->orderBy('session_id')
                       ->orderBy('exam_name')
                       ->paginate(10); // Paginate the results

        return Inertia::render('Exams/Index', [
            'exams' => $exams,
            'sessions' => $sessions, // Still pass sessions if your Index.vue uses it for display, even without filtering
            // Removed 'selectedFilters' as it's no longer needed
            'flash' => session('flash'), // Pass flash messages
        ]);
    }


    public function create()
    {
        // Fetch active sessions for the dropdown
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);

        return Inertia::render('Exams/Create', [
            'sessions' => $sessions,
        ]);
    }

    public function store(Request $request)
    {
        // Removed validation for 'total_marks' and 'passing_marks'
        $validatedData = $request->validate([
            'exam_name' => [
                'required',
                'string',
                'max:255',
                // Unique per session_id (composite unique key)
                Rule::unique('exams')->where(function ($query) use ($request) {
                    return $query->where('session_id', $request->session_id);
                }),
            ],
            'session_id' => 'required|exists:class_sessions,id',
            'status' => 'required|integer|in:0,1',
        ]);

        try {
            Exam::create($validatedData);

            return redirect()->route('exams.index')->with('flash', [
                'message' => 'Exam created successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', [
                'message' => 'Failed to create exam: ' . $e->getMessage(),
                'type' => 'error'
            ])->withInput(); // Keep old input in case of error
        }
    }

    /**
     * Show the form for editing the specified exam.
     */
    public function edit(Exam $exam)
    {
        // Eager load the session relationship
        $exam->load('session');
        // Fetch active sessions for the dropdown
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);

        return Inertia::render('Exams/Edit', [
            'exam' => $exam,
            'sessions' => $sessions,
        ]);
    }

    /**
     * Update the specified exam in storage.
     */
    public function update(Request $request, Exam $exam)
    {
        // Removed validation for 'total_marks' and 'passing_marks'
        $validatedData = $request->validate([
            'exam_name' => [
                'required',
                'string',
                'max:255',
                // Unique per session_id, ignoring the current exam's ID
                Rule::unique('exams')->where(function ($query) use ($request) {
                    return $query->where('session_id', $request->session_id);
                })->ignore($exam->id),
            ],
            'session_id' => 'required|exists:class_sessions,id',
            'status' => 'required|integer|in:0,1',
        ]);

        try {
            $exam->update($validatedData);

            return redirect()->route('exams.index')->with('flash', [
                'message' => 'Exam updated successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', [
                'message' => 'Failed to update exam: ' . $e->getMessage(),
                'type' => 'error'
            ])->withInput(); // Keep old input in case of error
        }
    }

    /**
     * Remove the specified exam from storage.
    */
    public function destroy(Exam $exam)
    {
        try {
            $exam->delete();
            return redirect()->route('exams.index')->with('flash', [
                'message' => 'Exam deleted successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->route('exams.index')->with('flash', [
                'message' => 'Error deleting exam: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }



    // Exam Schedule Functions 

    public function ExamSchdeuleIndex(Request $request)
    {
        // 1. Fetch all necessary data for filter dropdowns (INCLUDING GROUPS)
        $exams = Exam::where('status', 0)->get(['id', 'exam_name']);
        
        // Fetch unique class names with the first corresponding id
        $classes = ClassName::where('status', 0)
            ->select('id', 'class_name')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('class_names')
                    ->where('status', 0)
                    ->groupBy('class_name');
            })
            ->get();

        $sections = Section::where('status', 0)->get(['id', 'name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        
        // Fetch Groups for filtering
        $groups = Group::where('status', 0)->get(['id', 'name']);
        
        $teachers = Teacher::where('status', 0)->get(['id', 'name']);
        $subjects = Subject::where('status', 0)->get(['id', 'name']);
        $rooms = Room::where('status', 0)->get(['id', 'name']);
        $timeSlots = ExamTimeSlot::all(['id', 'name', 'start_time', 'end_time']);

        // 2. Build the query to fetch exam schedules with all related models (INCLUDING GROUP)
        $query = ExamSchedule::with(['exam', 'className', 'section', 'session', 'group', 'teacher', 'subject', 'room', 'examSlot']);

        // 3. Apply filters based on request input (INCLUDING GROUP FILTER)
        if ($request->filled('exam_id')) {
            $query->where('exam_id', $request->exam_id);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }
        // Group Filter Logic
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
        }
        
        // Existing Filters
        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }
        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }
        if ($request->filled('exam_date')) {
            $query->whereDate('exam_date', $request->exam_date);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('exam_slot_id')) {
            $query->where('exam_slot_id', $request->exam_slot_id);
        }

        // 4. Order results and paginate
        $examSchedules = $query->orderBy('exam_date')->paginate(10); 
                                    
        // 5. Return data to the Inertia view (INCLUDING GROUPS & GROUP FILTER)
        return Inertia::render('ExamSchedules/Index', [
            'examSchedules' => $examSchedules,
            'exams' => $exams,
            'classes' => $classes,
            'sections' => $sections,
            'sessions' => $sessions,
            'groups' => $groups,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'rooms' => $rooms,
            'timeSlots' => $timeSlots,
            'selectedFilters' => $request->only([
                'exam_id', 'class_id', 'section_id', 'session_id', 
                'group_id',
                'teacher_id', 'subject_id', 'room_id', 'exam_date', 'status', 'exam_slot_id'
            ]),
            'flash' => session('flash'),
        ]);
    }


    /**
     * Show the form for creating a new exam schedule.
    */

    public function ExamSchdeuleCreate()
    {
        $exams = Exam::where('status', 0)->get(['id', 'exam_name']);
        
        // Fetch unique class names with the first corresponding id
        $classes = ClassName::where('status', 0)
            ->select('id', 'class_name')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('class_names')
                    ->where('status', 0)
                    ->groupBy('class_name');
            })
            ->get();

        $sections = Section::where('status', 0)->get(['id', 'name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        
        // Fetch Groups
        $groups = Group::where('status', 0)->get(['id', 'name']); 
        
        $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
        $subjects = Subject::where('status', 0)->get(['id', 'name']);
        $rooms = Room::where('status', 0)->get(['id', 'name']);
        $timeSlots = ExamTimeSlot::all(['id', 'name', 'start_time', 'end_time']);

        return Inertia::render('ExamSchedules/Create', [
            'exams' => $exams,
            'classes' => $classes,
            'sections' => $sections,
            'sessions' => $sessions,
            'groups' => $groups,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'rooms' => $rooms,
            'exam_slots' => $timeSlots,
        ]);
    }


    public function ExamSchdeuleStore(Request $request)
    {
        $validatedData = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required|exists:class_names,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
            'group_id' => 'nullable|exists:groups,id', // ⬅️ NEW: Group validation
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
            'room_id' => 'required|exists:rooms,id',
            'exam_slot_id' => 'required|exists:exam_time_slots,id',
            'exam_date' => 'required|date|after_or_equal:today',
            'day_of_week' => 'required|string|in:MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY,SUNDAY',
            'status' => 'required|integer|in:0,1', // ⬅️ ADJUSTED: Status validation (0 or 1)
        ]);

        // Check for Room, Teacher, or Exam conflict based on the time slot ID
        $conflict = ExamSchedule::whereDate('exam_date', $validatedData['exam_date'])
            ->where('exam_slot_id', $validatedData['exam_slot_id'])
            ->where(function ($query) use ($validatedData) {
                
                // 1. Room conflict check
                $query->where('room_id', $validatedData['room_id']); 
                
                // 2. Teacher conflict check
                $query->orWhere('teacher_id', $validatedData['teacher_id']); 
                
                // 3. Class/Subject conflict check (A class/section/group can't have two exams at once)
                $query->orWhere(function($q) use ($validatedData) {
                    $q->where('class_id', $validatedData['class_id'])
                        ->where('section_id', $validatedData['section_id'])
                        ->where('session_id', $validatedData['session_id'])
                        ->where('group_id', $validatedData['group_id'] ?? null); // ⬅️ NEW: Include group check
                });
            })
            ->exists();

        if ($conflict) {
            return redirect()->back()->with('flash', [
                'message' => 'A conflict was detected. Either the room, teacher, or the class/group/section is already booked for this time on this date.',
                'type' => 'error'
            ])->withInput();
        }

        try {
            ExamSchedule::create($validatedData);

            return redirect()->route('exam-schedules.index')->with('flash', [
                'message' => 'Exam schedule created successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', [
                'message' => 'Failed to create exam schedule: ' . $e->getMessage(),
                'type' => 'error'
            ])->withInput();
        }
    }


    // public function ExamSchdeuleEdit(ExamSchedule $examSchedule)
    // {
    //     // ⬅️ ADJUSTED: Eager load all relationships, including 'group'
    //     $examSchedule->load(['exam', 'className', 'section', 'session', 'group', 'teacher', 'subject', 'room', 'examSlot']);

    //     $exams = Exam::where('status', 0)->get(['id', 'exam_name']);
    //     $classes = ClassName::where('status', 0)->get(['id', 'class_name']);
    //     $sections = Section::where('status', 0)->get(['id', 'name']);
    //     $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        
    //     // ⬅️ NEW: Fetch Groups
    //     $groups = Group::where('status', 0)->get(['id', 'name']); 
        
    //     $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
    //     $subjects = Subject::where('status', 0)->get(['id', 'name']);
    //     $rooms = Room::where('status', 0)->get(['id', 'name']);
    //     $timeSlots = ExamTimeSlot::all(['id', 'name', 'start_time', 'end_time']);

    //     return Inertia::render('ExamSchedules/Edit', [
    //         'examSchedule' => $examSchedule,
    //         'exams' => $exams,
    //         'classes' => $classes,
    //         'sections' => $sections,
    //         'sessions' => $sessions,
    //         'groups' => $groups, // ⬅️ NEW: Pass Groups to view
    //         'teachers' => $teachers,
    //         'subjects' => $subjects,
    //         'rooms' => $rooms,
    //         'timeSlots' => $timeSlots,
    //     ]);
    // }

    // public function ExamSchdeuleUpdate(Request $request, ExamSchedule $examSchedule)
    // {
    //     $validatedData = $request->validate([
    //         'exam_id' => 'required|exists:exams,id',
    //         'class_id' => 'required|exists:class_names,id',
    //         'section_id' => 'required|exists:sections,id',
    //         'session_id' => 'required|exists:class_sessions,id',
    //         'group_id' => 'nullable|exists:groups,id', // ⬅️ NEW: Group validation
    //         'teacher_id' => 'required|exists:teachers,id',
    //         'subject_id' => 'required|exists:subjects,id',
    //         'room_id' => 'required|exists:rooms,id',
    //         'exam_slot_id' => 'required|exists:exam_time_slots,id',
    //         'exam_date' => 'required|date|after_or_equal:today',
    //         'day_of_week' => 'required|string|in:MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY,SUNDAY',
    //         'status' => 'required|integer|in:0,1', // ⬅️ ADJUSTED: Status validation (0 or 1)
    //     ]);

    //     // Check for Room, Teacher, or Exam conflict based on the time slot ID
    //     $conflict = ExamSchedule::where('id', '!=', $examSchedule->id) // Exclude current record
    //         ->whereDate('exam_date', $validatedData['exam_date'])
    //         ->where('exam_slot_id', $validatedData['exam_slot_id'])
    //         ->where(function ($query) use ($validatedData) {
                
    //             // 1. Room conflict check
    //             $query->where('room_id', $validatedData['room_id']);
                
    //             // 2. Teacher conflict check
    //             $query->orWhere('teacher_id', $validatedData['teacher_id']);
                
    //             // 3. Class/Subject conflict check
    //             $query->orWhere(function($q) use ($validatedData) {
    //                 $q->where('class_id', $validatedData['class_id'])
    //                     ->where('section_id', $validatedData['section_id'])
    //                     ->where('session_id', $validatedData['session_id'])
    //                     ->where('group_id', $validatedData['group_id'] ?? null); // ⬅️ NEW: Include group check
    //             });
    //         })
    //         ->exists();

    //     if ($conflict) {
    //         return redirect()->back()->with('flash', [
    //             'message' => 'A conflict was detected. Either the room, teacher, or the class/group/section is already booked for this time on this date.',
    //             'type' => 'error'
    //         ])->withInput();
    //     }

    //     try {
    //         $examSchedule->update($validatedData);

    //         return redirect()->route('exam-schedules.index')->with('flash', [
    //             'message' => 'Exam schedule updated successfully!',
    //             'type' => 'success'
    //         ]);
    //     } catch (\Exception $e) {
    //         return redirect()->back()->with('flash', [
    //             'message' => 'Failed to update exam schedule: ' . $e->getMessage(),
    //             'type' => 'error'
    //         ])->withInput();
    //     }
    // }


    public function ExamSchdeuleDestroy(ExamSchedule $examSchedule)
    {
        try {
            $examSchedule->delete();
            return redirect()->route('exam-schedules.index')->with('flash', [
                'message' => 'Exam schedule deleted successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->route('exam-schedules.index')->with('flash', [
                'message' => 'Error deleting exam schedule: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }


    public function getAvailableResources(Request $request)
    {
        // 1. Validate the incoming request data
        $request->validate([
            'exam_date' => 'required|date',
            'exam_slot_id' => 'required|exists:exam_time_slots,id',
        ]);

        // 2. Find all exam schedules that match the provided date and time slot
        $bookedSchedules = ExamSchedule::where('exam_date', $request->input('exam_date'))
            ->where('exam_slot_id', $request->input('exam_slot_id'))
            ->get();

        // 3. Get the IDs of the occupied rooms and teachers
        $occupiedRoomIds = $bookedSchedules->pluck('room_id')->unique()->values()->all();
        $occupiedTeacherIds = $bookedSchedules->pluck('teacher_id')->unique()->values()->all();

        // 4. Return the IDs as JSON
        return response()->json([
            'occupiedRoomIds' => $occupiedRoomIds,
            'occupiedTeacherIds' => $occupiedTeacherIds,
        ]);
    }



    // Exam Seat Plan Functions
    public function show(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required|exists:class_names,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
            'room_id' => 'nullable|exists:rooms,id',
            'group_id' => 'required|exists:groups,id',
        ]);

        // Fetch data based on filters
        $examId = $request->input('exam_id');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $sessionId = $request->input('session_id');
        $roomId = $request->input('room_id');
        $groupId = $request->input('group_id');

        // Fetch related models
        $exam = Exam::findOrFail($examId);
        $class = ClassName::findOrFail($classId);
        $section = Section::findOrFail($sectionId);
        $session = ClassSession::findOrFail($sessionId);
        $room = $roomId ? Room::findOrFail($roomId) : null;
        $group = Group::findOrFail($groupId);

        // Fetch eligible students
        $eligibleStudents = Student::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('session_id', $sessionId)
            ->where('group_id', $groupId)
            ->orderBy('roll_number')
            ->with('group')
            ->get();

        // Fetch seat plans based on filters
        $seatPlans = ExamSeatPlan::where('exam_id', $examId)
            ->where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('session_id', $sessionId)
            ->where('group_id', $groupId)
            ->when($roomId, function ($query, $roomId) {
                return $query->where('room_id', $roomId);
            })
            ->get()
            ->keyBy('student_id');

        // Prepare form assignments
        $formAssignments = $eligibleStudents->map(function ($student) use ($seatPlans) {
            $assignedSeat = $seatPlans->get($student->id);
            return [
                'student_id' => $student->id,
                'name' => $student->name,
                'roll_number' => $student->roll_number,
                'admission_number' => $student->admission_number,
                'group_name' => $student->group ? $student->group->name : 'N/A',
                'seat_number' => $assignedSeat ? $assignedSeat->seat_number : null,
            ];
        })->toArray();

        // Return data to the Inertia view
        return Inertia::render('ExamSchedules/ClassSectionSeatPlan', [
            'exam' => $exam, // Changed from examSchedule to exam
            'cls' => $class,
            'section' => $section,
            'session' => $session,
            'room' => $room,
            'group' => $group,
            'eligibleStudentsData' => $formAssignments,
            'errors' => session('errors') ? session('errors')->getBag('default')->toArray() : [],
            'status' => session('status'),
        ]);
    }

    public function autoAssign(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required|exists:class_names,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
            'room_id' => 'required|exists:rooms,id',
            'group_id' => 'required|exists:groups,id',
        ]);

        $examScheduleId = $request->input('exam_id');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $sessionId = $request->input('session_id');
        $roomId = $request->input('room_id');
        $groupId = $request->input('group_id');

        $room = Room::findOrFail($roomId);
        $eligibleStudents = Student::where('class_id', $classId)
            ->where('section_id', $sectionId)
            ->where('session_id', $sessionId)
            ->where('group_id', $groupId)
            ->orderBy('roll_number')
            ->get();

        if ($eligibleStudents->count() > $room->capacity) {
            return back()->withErrors(['message' => "Number of students ({$eligibleStudents->count()}) exceeds room capacity ({$room->capacity})."]);
        }

        DB::transaction(function () use ($examScheduleId, $classId, $sectionId, $sessionId, $roomId, $groupId, $eligibleStudents, $room) {
            ExamSeatPlan::where('exam_id', $examScheduleId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('session_id', $sessionId)
                ->where('group_id', $groupId)
                ->delete();

            $seatNumber = 1;
            foreach ($eligibleStudents as $student) {
                if ($seatNumber <= $room->capacity) {
                    ExamSeatPlan::create([
                        'exam_id' => $examScheduleId,
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'session_id' => $sessionId,
                        'room_id' => $roomId,
                        'group_id' => $groupId,
                        'student_id' => $student->id,
                        'seat_number' => $seatNumber,
                    ]);
                    $seatNumber++;
                }
            }
        });

        return redirect()->back()->with('status', 'Seats auto-assigned successfully!');
    }


    /**
     * Store/Update manual seat assignments.
    */
    public function seatStore(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required|exists:class_names,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
            'room_id' => 'required|exists:rooms,id',
            'group_id' => 'required|exists:groups,id',
            'assignments.*.student_id' => 'required|exists:students,id',
            'assignments.*.seat_number' => 'nullable|integer|min:1',
        ]);

        $examScheduleId = $request->input('exam_id');
        $classId = $request->input('class_id');
        $sectionId = $request->input('section_id');
        $sessionId = $request->input('session_id');
        $roomId = $request->input('room_id');
        $groupId = $request->input('group_id');
        $room = Room::findOrFail($roomId);

        DB::transaction(function () use ($examScheduleId, $classId, $sectionId, $sessionId, $roomId, $groupId, $request, $room) {
            $submittedSeatNumbers = collect($request->assignments)
                ->pluck('seat_number')
                ->filter()
                ->unique()
                ->values();

            foreach ($submittedSeatNumbers as $seatNum) {
                if ($seatNum > $room->capacity) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'assignments' => ["Seat number {$seatNum} exceeds room capacity ({$room->capacity})."],
                    ]);
                }
            }

            foreach ($request->assignments as $assignmentData) {
                $studentId = $assignmentData['student_id'];
                $newSeatNumber = $assignmentData['seat_number'];

                if ($newSeatNumber !== null) {
                    $conflictingStudent = collect($request->assignments)
                        ->where('seat_number', $newSeatNumber)
                        ->where('student_id', '!=', $studentId)
                        ->first();
                    if ($conflictingStudent) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'assignments.' . collect($request->assignments)->search(fn($item) => $item['student_id'] == $studentId) . '.seat_number' => "Seat {$newSeatNumber} is assigned to multiple students.",
                        ]);
                    }

                    ExamSeatPlan::updateOrCreate(
                        [
                            'exam_id' => $examScheduleId,
                            'class_id' => $classId,
                            'section_id' => $sectionId,
                            'session_id' => $sessionId,
                            'group_id' => $groupId,
                            'student_id' => $studentId,
                        ],
                        [
                            'room_id' => $roomId,
                            'seat_number' => $newSeatNumber,
                        ]
                    );
                } else {
                    ExamSeatPlan::where('exam_id', $examScheduleId)
                        ->where('class_id', $classId)
                        ->where('section_id', $sectionId)
                        ->where('session_id', $sessionId)
                        ->where('group_id', $groupId)
                        ->where('student_id', $studentId)
                        ->delete();
                }
            }
        });

        return redirect()->back()->with('status', 'Seat plan updated successfully!');
    }


   // Student Side To Show Exam Schedule 


    public function studentMyExamSchedule(Request $request)
    {
        try {
            $user = Auth::user();
            $student = $user->student ?? $user; // Adjust this based on your User-Student relationship

            if (!$student || !($student instanceof Student)) {
                return redirect()->route('dashboard')->with('flash', [
                    'type' => 'error',
                    'message' => 'Your account is not linked to a student profile.',
                ]);
            }

            // --- DEBUG POINT 1: Check Student's filter criteria ---
            // Visit your student's exam schedule URL and this will show you the student's IDs
            // Make sure these IDs match the class_id, section_id, and session_id in your exam_schedules table
            // dd([
            //     'student_id' => $student->id,
            //     'student_name' => $student->name,
            //     'student_class_id' => $student->class_id,
            //     'student_section_id' => $student->section_id,
            //     'student_session_id' => $student->session_id,
            //     'current_date' => now()->toDateString(), // Check the current date being used for filtering
            // ]);


            $examSchedules = ExamSchedule::with([
                                            'exam',      // Loads the Exam model
                                            'className', // <--- THIS IS NOW CORRECT, matching your relationship name
                                            'section',   // Loads the Section model
                                            'subject',   // Loads the Subject model
                                            'teacher',   // Loads the Teacher model
                                            'room'       // Loads the Room model
                                        ])
                                        ->where('class_id', $student->class_id)
                                        ->where('section_id', $student->section_id)
                                        ->where('session_id', $student->session_id)
                                        ->where('exam_date', '>=', now()->toDateString())
                                        ->orderBy('exam_date')
                                        ->orderBy('start_time')
                                        ->get()
                                        ->map(function ($schedule) {
                                            $seatNumber = 'N/A (No specific seat plan)';

                                            return [
                                                'id' => $schedule->id,
                                                'exam_name' => $schedule->exam ? $schedule->exam->exam_name : 'N/A',
                                                'class_name' => $schedule->className ? $schedule->className->class_name : 'N/A', // Access via `className` relationship
                                                'section_name' => $schedule->section ? $schedule->section->name : 'N/A',
                                                'subject_name' => $schedule->subject ? $schedule->subject->name : 'N/A',
                                                'teacher_name' => $schedule->teacher ? $schedule->teacher->name : 'N/A',
                                                'room_name' => $schedule->room ? $schedule->room->name : 'N/A',
                                                'exam_date' => $schedule->exam_date,
                                                'start_time' => $schedule->start_time,
                                                'end_time' => $schedule->end_time,
                                                'seat_number' => $seatNumber,
                                                
                                            ];
                                        });

            // --- DEBUG POINT 2: Check the fetched exam schedules ---
            // If Debug Point 1 showed correct student data, uncomment this.
            // This will show you exactly what exam schedules were retrieved by the query.
            // dd($examSchedules);


            return Inertia::render('StudentExamSchedule/MyExamSchedule', [
                'examSchedules' => $examSchedules,
                'studentName' => $student->name,
                'flash' => session('flash'),
            ]);

        } catch (Throwable $e) {
            Log::error("Error loading student's exam schedule for user " . (Auth::id() ?? 'N/A') . ": " . $e->getMessage(), ['exception' => $e]);
            return redirect()->back()->with('flash', [
                'type' => 'error',
                'message' => 'An internal server error occurred while fetching your schedule. Please try again later or contact support.',
            ]);
        }
    }



    // Exam Time Slot Management Functions
    public function examTimeSlotIndex()
    {
        $timeSlots = ExamTimeSlot::all();

        return Inertia::render('ExamTimeSlots/Index', [
            'timeSlots' => $timeSlots,
            // Pass flash messages explicitly
            'flash' => [
                'message' => session('message'),
                'type' => session('type'),
            ],
        ]);
    }


    /**
     * Show the form for creating a new exam time slot.
     */
    public function examTimeSlotCreate()
    {
        return Inertia::render('ExamTimeSlots/Create');
    }

    /**
     * Store a newly created exam time slot in storage.
     *
     * After validation and creation, this method redirects the user back to
     * the index page, which forces Inertia to re-fetch the data.
     */
    public function examTimeSlotStore(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:exam_time_slots,name',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        ExamTimeSlot::create($validatedData);

        return Redirect::route('exam-time-slots.index');
    }

    /**
     * Display the form for editing the specified exam time slot.
     *
     * @param  \App\Models\ExamTimeSlot  $examTimeSlot
    */
    public function examTimeSlotEdit(ExamTimeSlot $examTimeSlot)
    {
        return Inertia::render('ExamTimeSlots/Edit', [
            'timeSlot' => $examTimeSlot,
        ]);
    }

    /**
     * Update the specified exam time slot in storage.
     *
     * After validation and updating the record, this method redirects the
     * user to the index page to display the updated list.
    */
    public function examTimeSlotUpdate(Request $request, ExamTimeSlot $examTimeSlot)
    {
        $validatedData = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('exam_time_slots')->ignore($examTimeSlot->id),
            ],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $examTimeSlot->update($validatedData);

        // Redirect to the index page with a flash message
        return redirect()->route('exam-time-slots.index')->with([
            'message' => 'Exam time slot updated successfully.',
            'type' => 'success',
        ]);
    }

    /**
     * Remove the specified exam time slot from storage.
     *
     * After deleting the record, this method redirects the user back to the
     * index page, which will re-render without the deleted item.
    */
    public function examTimeSlotDestroy(ExamTimeSlot $examTimeSlot)
    {
        $examTimeSlot->delete();

        return Redirect::route('exam-time-slots.index')->with([
            'message' => 'Exam time slot deleted successfully.',
            'type' => 'success',
        ]);
    }



    // Generate PDF for Exam Seat Plan

    public function generateSeatPlanPDF(Request $request)
    {
        \Log::info('Starting generateSeatPlanPDF', $request->all());

        try {
            $request->validate([
                'exam_id' => 'required|exists:exams,id', // Validates against exams table
                'class_id' => 'required|exists:class_names,id',
                'section_id' => 'required|exists:sections,id',
                'session_id' => 'required|exists:class_sessions,id',
                'room_id' => 'required|exists:rooms,id',
                'group_id' => 'required|exists:groups,id',
            ]);

            $examId = $request->input('exam_id'); // Refers to exams.id
            $classId = $request->input('class_id');
            $sectionId = $request->input('section_id');
            $sessionId = $request->input('session_id');
            $roomId = $request->input('room_id');
            $groupId = $request->input('group_id');

            \Log::info('Fetching exam schedule');
            // Query ExamSchedule by exam_id, not id
            $examSchedule = ExamSchedule::with(['exam', 'subject'])
                ->where('exam_id', $examId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('session_id', $sessionId)
                ->where('group_id', $groupId)
                ->firstOrFail();

            \Log::info('Fetching class, section, session, room, group');
            $class = ClassName::findOrFail($classId);
            $section = Section::findOrFail($sectionId);
            $session = ClassSession::findOrFail($sessionId);
            $room = Room::findOrFail($roomId);
            $group = Group::findOrFail($groupId);

            \Log::info('Fetching eligible students');
            $eligibleStudents = Student::where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('session_id', $sessionId)
                ->where('group_id', $groupId)
                ->orderBy('roll_number')
                ->with('group')
                ->get();

            \Log::info('Eligible students count: ' . $eligibleStudents->count());

            $seatPlans = ExamSeatPlan::where('exam_id', $examId)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('session_id', $sessionId)
                ->where('group_id', $groupId)
                ->get()
                ->keyBy('student_id');

            \Log::info('Seat plans count: ' . $seatPlans->count());

            $seatAssignments = $eligibleStudents->map(function ($student) use ($seatPlans) {
                $assignedSeat = $seatPlans->get($student->id);
                return [
                    'student_id' => $student->id,
                    'name' => $student->name,
                    'roll_number' => $student->roll_number,
                    'admission_number' => $student->admission_number,
                    'group_name' => $student->group ? $student->group->name : 'N/A',
                    'seat_number' => $assignedSeat ? $assignedSeat->seat_number : 'Not Assigned',
                ];
            })->sortBy('seat_number')->toArray();

            \Log::info('Seat assignments count: ' . count($seatAssignments));

            if (empty($seatAssignments) || !collect($seatAssignments)->some(fn($assignment) => $assignment['seat_number'] !== 'Not Assigned')) {
                \Log::warning('No seats assigned');
                return response()->json(['error' => 'No seats assigned for this exam, class, section, and group.'], 422);
            }

            $setting = Setting::first();
            \Log::info('Setting fetched', ['setting' => $setting ? $setting->toArray() : null]);

            $data = [
                'setting' => $setting,
                'examSchedule' => $examSchedule,
                'class' => $class,
                'section' => $section,
                'session' => $session,
                'room' => $room,
                'group' => $group,
                'seatAssignments' => $seatAssignments,
            ];

            \Log::info('Loading PDF view');
            $pdf = PDF::loadView('pdfs.class_section_seat_plan', $data);
            $pdf->setPaper('A4', 'portrait');

            \Log::info('Generating PDF download');
            return $pdf->download("seat_plan_exam_{$examId}_class_{$classId}_section_{$sectionId}_group_{$groupId}.pdf");
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Model not found: ' . $e->getMessage());
            return response()->json(['error' => 'Record not found: ' . $e->getModel() . ' with ID ' . $e->getIds()[0]], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed: ' . $e->getMessage());
            return response()->json(['error' => 'The selected exam id is invalid.'], 422);
        } catch (\Exception $e) {
            \Log::error('PDF generation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }

}





