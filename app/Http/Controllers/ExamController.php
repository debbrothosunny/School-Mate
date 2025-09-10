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
use App\Models\Student; 
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Illuminate\Validation\ValidationException;
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
            'total_marks' => 'required|integer',
            'passing_marks' => 'required|integer',
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
            'total_marks' => 'required|integer',
            'passing_marks' => 'required|integer',
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
        // Fetch all necessary data for filter dropdowns
        $exams = Exam::where('status', 0)->get(['id', 'exam_name']);
        $classes = ClassName::where('status', 0)->get(['id', 'class_name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $teachers = Teacher::where('status', 0)->get(['id', 'name']);
        $subjects = Subject::where('status', 0)->get(['id', 'name']);
        $rooms = Room::where('status', 0)->get(['id', 'name']);
        $timeSlots = ExamTimeSlot::all(['id', 'name', 'start_time', 'end_time']);

        // Build the query to fetch exam schedules with all related models
        $query = ExamSchedule::with(['exam', 'className', 'section', 'session', 'teacher', 'subject', 'room', 'examSlot']);

        // Apply filters based on request input
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

        // Order results and paginate
        $examSchedules = $query->orderBy('exam_date')
                               ->paginate(10); 
                               
        return Inertia::render('ExamSchedules/Index', [
            'examSchedules' => $examSchedules,
            'exams' => $exams,
            'classes' => $classes,
            'sections' => $sections,
            'sessions' => $sessions,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'rooms' => $rooms,
            'timeSlots' => $timeSlots,
            'selectedFilters' => $request->only(['exam_id', 'class_id', 'section_id', 'session_id', 'teacher_id', 'subject_id', 'room_id', 'exam_date', 'status', 'exam_slot_id']),
            'flash' => session('flash'),
        ]);
    }


    /**
     * Show the form for creating a new exam schedule.
    */
    public function ExamSchdeuleCreate()
    {
        $exams = Exam::where('status', 0)->get(['id', 'exam_name']);
        $classes = ClassName::where('status', 0)->get(['id', 'class_name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
        $subjects = Subject::where('status', 0)->get(['id', 'name', 'code']);
        $rooms = Room::where('status', 0)->get(['id', 'name']);
        $timeSlots = ExamTimeSlot::all(['id', 'name', 'start_time', 'end_time']);

        return Inertia::render('ExamSchedules/Create', [
            'exams' => $exams,
            'classes' => $classes,
            'sections' => $sections,
            'sessions' => $sessions,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'rooms' => $rooms,
            'exam_slots' => $timeSlots, // CHANGED THIS LINE
        ]);
    }


    public function ExamSchdeuleStore(Request $request)
    {
        $validatedData = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required|exists:class_names,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
            'room_id' => 'required|exists:rooms,id',
            'exam_slot_id' => 'required|exists:exam_time_slots,id', // Use the new ID
            'exam_date' => 'required|date|after_or_equal:today',
            'day_of_week' => 'required|string|in:MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY,SUNDAY',
            'status' => 'required|integer|in:0,1,2',
        ]);

        // Check for Room, Teacher, or Exam conflict based on the time slot ID
        $conflict = ExamSchedule::where(function ($query) use ($validatedData) {
            $query->where('room_id', $validatedData['room_id'])
                  ->orWhere('teacher_id', $validatedData['teacher_id'])
                  ->orWhere(function($q) use ($validatedData) {
                      $q->where('class_id', $validatedData['class_id'])
                        ->where('section_id', $validatedData['section_id'])
                        ->where('session_id', $validatedData['session_id'])
                        ->where('subject_id', $validatedData['subject_id']);
                  });
        })
        ->whereDate('exam_date', $validatedData['exam_date'])
        ->where('exam_slot_id', $validatedData['exam_slot_id'])
        ->exists();

        if ($conflict) {
            return redirect()->back()->with('flash', [
                'message' => 'A conflict was detected. Either the room, teacher, or the exam slot is already booked for this time on this date.',
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


    public function ExamSchdeuleEdit(ExamSchedule $examSchedule)
    {
        // Eager load all relationships
        $examSchedule->load(['exam', 'className', 'section', 'session', 'teacher', 'subject', 'room', 'examTimeSlot']);

        $exams = Exam::where('status', 0)->get(['id', 'exam_name']);
        $classes = ClassName::where('status', 0)->get(['id', 'class_name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
        $subjects = Subject::where('status', 0)->get(['id', 'name', 'code']);
        $rooms = Room::where('status', 0)->get(['id', 'name']);
        $timeSlots = ExamTimeSlot::all(['id', 'name', 'start_time', 'end_time']);

        return Inertia::render('ExamSchedules/Edit', [
            'examSchedule' => $examSchedule,
            'exams' => $exams,
            'classes' => $classes,
            'sections' => $sections,
            'sessions' => $sessions,
            'teachers' => $teachers,
            'subjects' => $subjects,
            'rooms' => $rooms,
            'timeSlots' => $timeSlots,
        ]);
    }

    public function ExamSchdeuleUpdate(Request $request, ExamSchedule $examSchedule)
    {
        $validatedData = $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'class_id' => 'required|exists:class_names,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
            'teacher_id' => 'required|exists:teachers,id',
            'subject_id' => 'required|exists:subjects,id',
            'room_id' => 'required|exists:rooms,id',
            'exam_slot_id' => 'required|exists:exam_time_slots,id', // Use the new ID
            'exam_date' => 'required|date|after_or_equal:today',
            'day_of_week' => 'required|string|in:MONDAY,TUESDAY,WEDNESDAY,THURSDAY,FRIDAY,SATURDAY,SUNDAY',
            'status' => 'required|integer|in:0,1,2',
        ]);

        // Check for Room, Teacher, or Exam conflict based on the time slot ID
        $conflict = ExamSchedule::where('id', '!=', $examSchedule->id) // Exclude current record
            ->where(function ($query) use ($validatedData) {
                $query->where('room_id', $validatedData['room_id'])
                      ->orWhere('teacher_id', $validatedData['teacher_id'])
                      ->orWhere(function($q) use ($validatedData) {
                          $q->where('class_id', $validatedData['class_id'])
                            ->where('section_id', $validatedData['section_id'])
                            ->where('session_id', $validatedData['session_id'])
                            ->where('subject_id', $validatedData['subject_id']);
                      });
            })
            ->whereDate('exam_date', $validatedData['exam_date'])
            ->where('exam_slot_id', $validatedData['exam_slot_id'])
            ->exists();

        if ($conflict) {
            return redirect()->back()->with('flash', [
                'message' => 'A conflict was detected. Either the room, teacher, or the exam slot is already booked for this time on this date.',
                'type' => 'error'
            ])->withInput();
        }

        try {
            $examSchedule->update($validatedData);

            return redirect()->route('exam-schedules.index')->with('flash', [
                'message' => 'Exam schedule updated successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', [
                'message' => 'Failed to update exam schedule: ' . $e->getMessage(),
                'type' => 'error'
            ])->withInput();
        }
    }


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


















    
    public function show(ExamSchedule $examSchedule)
    {
        // Load relationships needed for the view
        $examSchedule->load([
            'room',
            'exam',          // Ensure this loads the related Exam model
            'subject',       // Ensure this loads the related Subject model
            'className',     // Ensure this loads the related Class model (if that's the relationship name)
            'section',       // Ensure this loads the related Section model
            'examSeatPlans.student'
        ]);

        // Get students eligible for this specific exam
        // (Adjust this query based on how your students are linked to classes/sections/sessions)
        $eligibleStudents = Student::where('class_id', $examSchedule->class_id)
                                   ->where('section_id', $examSchedule->section_id)
                                   ->where('session_id', $examSchedule->session_id)
                                   ->orderBy('roll_number') // Order for natural display/assignment
                                   ->get();

        // Prepare existing assignments for the frontend
        $currentAssignments = $examSchedule->examSeatPlans->keyBy('student_id');

        // Map eligible students with their current seat assignments for the form
        $formAssignments = $eligibleStudents->map(function ($student) use ($currentAssignments) {
            $assignedSeat = $currentAssignments->get($student->id);
            return [
                'student_id' => $student->id,
                'name' => $student->name,
                'roll_number' => $student->roll_number,
                'admission_number' => $student->admission_number,
                'seat_number' => $assignedSeat ? $assignedSeat->seat_number : null,
            ];
        })->toArray();

        return Inertia::render('ExamSchedules/SeatPlan', [ // You'll create this Vue component
            'examSchedule' => $examSchedule->toArray(),
            'room' => $examSchedule->room->toArray(),
            'eligibleStudentsData' => $formAssignments, // Data prepared for the form
            'errors' => session('errors') ? session('errors')->getBag('default')->toArray() : [], // Pass validation errors
            'status' => session('status'), // Pass success/error messages
        ]);
    }

    /**
     * Handle auto-assignment of seats.
    */
    public function autoAssign(ExamSchedule $examSchedule)
    {
        $examSchedule->load('room');
        if (!$examSchedule->room) {
            return back()->withErrors(['message' => 'Room not assigned to this exam schedule.'])->withInput();
        }

        $room = $examSchedule->room;
        $eligibleStudents = Student::where('class_id', $examSchedule->class_id)
                                   ->where('section_id', $examSchedule->section_id)
                                   ->where('session_id', $examSchedule->session_id)
                                   ->orderBy('roll_number') // Assign by roll number order
                                   ->get();

        if ($eligibleStudents->count() > $room->capacity) {
            return back()->withErrors(['message' => 'Number of eligible students (' . $eligibleStudents->count() . ') exceeds room capacity (' . $room->capacity . '). Please split the class or use a larger room.'])->withInput();
        }

        DB::transaction(function () use ($examSchedule, $eligibleStudents, $room) {
            // Delete all existing assignments for this exam schedule
            $examSchedule->examSeatPlans()->delete();

            $seatNumber = 1;
            foreach ($eligibleStudents as $student) {
                if ($seatNumber <= $room->capacity) {
                    ExamSeatPlan::create([
                        'exam_schedule_id' => $examSchedule->id,
                        'student_id' => $student->id,
                        'seat_number' => $seatNumber,
                    ]);
                    $seatNumber++;
                } else {
                    // This case should ideally be caught by the capacity check above,
                    // but good to have a safeguard.
                    break;
                }
            }
        });

        return redirect()->back()->with('status', 'Seats auto-assigned successfully!');
    }


    /**
     * Store/Update manual seat assignments.
    */
    public function seatStore(Request $request, ExamSchedule $examSchedule)
    {
        $validated = $request->validate([
            'assignments.*.student_id' => 'required|exists:students,id',
            'assignments.*.seat_number' => 'nullable|integer|min:1', // Allow null for unassigned
        ]);

        $roomCapacity = $examSchedule->room->capacity;

        DB::transaction(function () use ($examSchedule, $validated, $roomCapacity) {
            // Prepare a list of seat numbers that are actually being submitted
            $submittedSeatNumbers = collect($validated['assignments'])
                                    ->pluck('seat_number')
                                    ->filter() // Remove nulls (unassigned)
                                    ->unique()
                                    ->values();

            // Validate against room capacity and duplicates within submission
            foreach ($submittedSeatNumbers as $seatNum) {
                if ($seatNum > $roomCapacity) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'assignments' => ["Seat number {$seatNum} exceeds the room capacity of {$roomCapacity}."],
                    ]);
                }
            }

            // Get existing assignments for this schedule
            $existingAssignments = $examSchedule->examSeatPlans->keyBy('student_id');

            foreach ($validated['assignments'] as $assignmentData) {
                $studentId = $assignmentData['student_id'];
                $newSeatNumber = $assignmentData['seat_number'];

                if ($newSeatNumber !== null) {
                    // Check for duplicate seat number *across students being assigned* in the current request
                    $conflictingStudent = collect($validated['assignments'])
                                          ->where('seat_number', $newSeatNumber)
                                          ->where('student_id', '!=', $studentId)
                                          ->first();
                    if ($conflictingStudent) {
                         throw \Illuminate\Validation\ValidationException::withMessages([
                            'assignments.' . collect($validated['assignments'])->search(fn($item) => $item['student_id'] == $studentId) . '.seat_number' => "Seat {$newSeatNumber} is assigned to multiple students.",
                        ]);
                    }

                    // Update or create the assignment
                    ExamSeatPlan::updateOrCreate(
                        [
                            'exam_schedule_id' => $examSchedule->id,
                            'student_id' => $studentId,
                        ],
                        [
                            'seat_number' => $newSeatNumber,
                        ]
                    );
                } else {
                    // If seat_number is null, it means the student is unassigned for this exam
                    // Delete any existing assignment for this student for this exam schedule
                    $examSchedule->examSeatPlans()->where('student_id', $studentId)->delete();
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

}





