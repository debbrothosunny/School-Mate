<?php

namespace App\Http\Controllers;

use App\Models\ClassTime;
use App\Models\ClassName;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Section;
use App\Models\ClassSession;
use App\Models\ClassTimeSlot;
use App\Models\Room;
use App\Models\ClassSubject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ClassTimeController extends Controller
{
    /**
     * Display a listing of the timetable entries with filtering options.
     */
    public function index(Request $request)
    {
        // Fetch all necessary data for dropdowns
        $classes = ClassName::where('status', 0)->get(['id', 'class_name as name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        $subjects = Subject::where('status', 0)->get(['id', 'name', 'code']);
        $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
        $rooms = Room::where('status', 0)->get(['id', 'name']); // Fetch active rooms for dropdown
        $timeSlots = ClassTimeSlot::get(['id', 'start_time', 'end_time']);

        // Apply filters based on request input
        $query = ClassTime::query()
            ->with(['className', 'subject', 'teacher', 'section', 'session', 'room', 'classTimeSlot']);

        if ($request->filled('class_name_id')) {
            $query->where('class_name_id', $request->class_name_id);
        }
        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
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
        // --- UPDATED: Filter by class_time_slot_id instead of start/end time
        if ($request->filled('class_time_slot_id')) {
            $query->where('class_time_slot_id', $request->class_time_slot_id);
        }

        // Order results for better display
        $timetableEntries = $query->orderBy('day_of_week')
            ->orderBy('class_time_slot_id') // --- UPDATED: Order by the new ID
            ->get();

        return Inertia::render('Timetable/Index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'sections' => $sections,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'rooms' => $rooms,
            'timeSlots' => $timeSlots, // --- NEW: Pass time slots for filter dropdown
            'timetableEntries' => $timetableEntries,
            'selectedFilters' => $request->only(['class_name_id', 'session_id', 'section_id', 'day_of_week', 'teacher_id', 'subject_id', 'room_id', 'class_time_slot_id']),
            'flash' => session('flash'),
        ]);
    }

    public function create()
    {
        // Fetch all necessary data from the database
        $classNames = ClassName::all();
        $sections = Section::all();
        $sessions = ClassSession::all();
        $subjects = Subject::all();
        $teachers = Teacher::all();
        $rooms = Room::all();
        $timeSlots = ClassTimeSlot::all();
        
        // Static array for days of the week, as it's not dynamic data
        $daysOfWeek = [
            'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
        ];

        // Fetch the class-subject-teacher assignments
        $classSubjects = ClassSubject::all();

        // Fetch all existing timetable entries to check for booked slots
        // This data is what the Vue component uses for the real-time availability check
        $existingTimetables = ClassTime::all();

        // Render the Vue component and pass all the data as props
        return Inertia::render('Timetable/Create', [
            'classNames' => $classNames,
            'sections' => $sections,
            'sessions' => $sessions,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'rooms' => $rooms,
            'timeSlots' => $timeSlots,
            'daysOfWeek' => $daysOfWeek,
            'classSubjects' => $classSubjects,
            'existingTimetables' => $existingTimetables,
        ]);
    }

    /**
     * Store a newly created timetable entry.
     */
    public function store(Request $request)
    {
        // 1. Validate the incoming request data first
        $validatedData = $request->validate([
            'class_name_id' => 'required|exists:class_names,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
            'day_of_week' => 'required|string',
            'room_id' => 'required|exists:rooms,id',
            'class_time_slot_id' => 'required|exists:class_time_slots,id',
        ]);

        // 2. Perform the conflict checks
        // Check if the selected room is already booked for this day and time slot
        $roomConflict = ClassTime::where('day_of_week', $validatedData['day_of_week'])
                                 ->where('class_time_slot_id', $validatedData['class_time_slot_id'])
                                 ->where('room_id', $validatedData['room_id'])
                                 ->exists();

        // Check if the selected teacher is already booked for this day and time slot
        $teacherConflict = ClassTime::where('day_of_week', $validatedData['day_of_week'])
                                    ->where('class_time_slot_id', $validatedData['class_time_slot_id'])
                                    ->where('teacher_id', $validatedData['teacher_id'])
                                    ->exists();

        // 3. Handle the results
        if ($roomConflict) {
            return back()->with('message', [
                'type' => 'error',
                'text' => 'The selected room is already booked for this time slot.',
            ]);
        }

        if ($teacherConflict) {
            return back()->with('message', [
                'type' => 'error',
                'text' => 'The selected teacher is already assigned to a class at this time.',
            ]);
        }

        // 4. If no conflict, create the new class schedule entry
        ClassTime::create($validatedData);

        // 5. Redirect with a success message
        return redirect()->route('timetable.index')
        ->with('flash', ['type'=>'success','message'=>'Class schedule created successfully!']);
    }

    /**
     * Checks for scheduling conflicts based on the request data.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
    */
    // public function checkConflicts(Request $request)
    // {
    //     try {
    //         // Log the incoming request data to help with debugging
    //         Log::info('Incoming request data for conflict check:', ['data' => $request->all()]);

    //         // 1. Validate the incoming data for the conflict check
    //         $request->validate([
    //             'class_name_id' => 'required|exists:class_names,id',
    //             'subject_id' => 'required|exists:subjects,id',
    //             'teacher_id' => 'required|exists:teachers,id',
    //             'section_id' => 'required|exists:sections,id',
    //             'session_id' => 'required|exists:class_sessions,id',
    //             'day_of_week' => ['required', Rule::in(['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'])],
    //             'class_time_slot_id' => 'required|exists:class_time_slots,id',
    //             'room_id' => 'nullable|exists:rooms,id',
    //         ]);

    //         // 2. Build a comprehensive query to find any conflicting entry based on the class time slot ID
    //         $conflictingEntry = ClassTime::where('session_id', $request->input('session_id'))
    //             ->where('day_of_week', $request->input('day_of_week'))
    //             ->where('class_time_slot_id', $request->input('class_time_slot_id'))
    //             ->where(function ($query) use ($request) {
    //                 // Group the OR conditions to check for any one of the three conflicts
    //                 $query->where(function ($q) use ($request) {
    //                     // Conflict for class-section
    //                     $q->where('class_name_id', $request->input('class_name_id'))
    //                         ->where('section_id', $request->input('section_id'));
    //                 })->orWhere(function ($q) use ($request) {
    //                     // Conflict for teacher
    //                     $q->where('teacher_id', $request->input('teacher_id'));
    //                 })->orWhere(function ($q) use ($request) {
    //                     // Conflict for room if provided
    //                     if (!empty($request->input('room_id'))) {
    //                         $q->where('room_id', $request->input('room_id'));
    //                     } else {
    //                         // This clause ensures the orWhere group doesn't accidentally
    //                         // return true if room_id is empty, but a conflict is not found.
    //                         $q->whereRaw('1 = 0');
    //                     }
    //                 });
    //             })
    //             ->with(['className', 'subject', 'teacher', 'section', 'room', 'classTimeSlot'])
    //             ->first();

    //         // 3. If a conflict exists, return 409 with details
    //         if ($conflictingEntry) {
    //             return response()->json([
    //                 'isConflict' => true,
    //                 'type' => 'conflict',
    //                 'message' => 'This time slot conflicts with an existing entry.',
    //                 'details' => [
    //                     'class_name' => optional($conflictingEntry->className)->name ?? 'N/A',
    //                     'section_name' => optional($conflictingEntry->section)->name ?? 'N/A',
    //                     'subject_name' => optional($conflictingEntry->subject)->name ?? 'N/A',
    //                     'teacher_name' => optional($conflictingEntry->teacher)->name ?? 'N/A',
    //                     'room_name' => optional($conflictingEntry->room)->name ?? 'N/A',
    //                     'day_of_week' => $conflictingEntry->day_of_week,
    //                     'start_time' => optional($conflictingEntry->classTimeSlot)->start_time ?? 'N/A',
    //                     'end_time' => optional($conflictingEntry->classTimeSlot)->end_time ?? 'N/A',
    //                 ],
    //             ], 409);
    //         }

    //         // 4. No conflict found
    //         return response()->json([
    //             'isConflict' => false,
    //             'type' => 'available',
    //             'message' => 'Time slot is available.',
    //         ], 200);

    //     } catch (\Exception $e) {
    //         // Log the real error with more details
    //         Log::error('Conflict check failed: ' . $e->getMessage(), [
    //             'trace' => $e->getTraceAsString(),
    //             'input' => $request->all(),
    //             'method' => $request->method(),
    //             'url' => $request->fullUrl(),
    //         ]);

    //         // Return the specific error message for debugging
    //         return response()->json([
    //             'isConflict' => false,
    //             'type' => 'error',
    //             'message' => 'An error occurred while checking for conflicts.',
    //             'exception_message' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    /**
     * Gets a list of all occupied time slots for a given day and time range.
     * This is used for the real-time availability display, now using a time slot ID.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
    */
    // public function getOccupiedSlots(Request $request)
    // {
    //     $request->validate([
    //         'day_of_week' => ['required', Rule::in(['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'])],
    //         'session_id' => 'required|exists:class_sessions,id',
    //         'room_id' => 'nullable|exists:rooms,id',
    //     ]);

    //     $dayOfWeek = $request->input('day_of_week');
    //     $roomId = $request->input('room_id', null);
    //     $sessionId = $request->input('session_id');

    //     $occupiedSlots = ClassTime::query()
    //         ->where('session_id', $sessionId)
    //         ->where('day_of_week', $dayOfWeek)
    //         ->when($roomId, function ($query, $roomId) {
    //             return $query->where('room_id', $roomId);
    //         })
    //         ->with(['className', 'subject', 'teacher', 'section', 'room', 'classTimeSlot']) // Ensure classTimeSlot is loaded
    //         ->get();

    //     $formattedSlots = $occupiedSlots->map(function ($slot) {
    //         return [
    //             'id' => $slot->id,
    //             'class_name' => optional($slot->className)->name ?? 'N/A',
    //             'section_name' => optional($slot->section)->name ?? 'N/A',
    //             'subject_name' => optional($slot->subject)->name ?? 'N/A',
    //             'teacher_name' => optional($slot->teacher)->name ?? 'N/A',
    //             'room_name' => optional($slot->room)->name ?? 'N/A',
    //             'day_of_week' => $slot->day_of_week,
    //             'class_time_slot_id' => $slot->class_time_slot_id, // NEW: Include the slot ID
    //             'start_time' => optional($slot->classTimeSlot)->start_time ?? 'N/A', // Get time from the relationship
    //             'end_time' => optional($slot->classTimeSlot)->end_time ?? 'N/A', // Get time from the relationship
    //         ];
    //     });
    //     return response()->json($formattedSlots);
    // }


    /**
     * Show the form for editing the specified timetable entry.
    */
    public function edit(ClassTime $timetableEntry)
    {
        $timetableEntry->load(['className', 'subject', 'teacher', 'section', 'session', 'room', 'classTimeSlot']); // --- NEW: Eager load classTimeSlot

        $classes = ClassName::where('status', 0)->get(['id', 'class_name as name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        $subjects = Subject::where('status', 0)->get(['id', 'name', 'code']);
        $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
        $rooms = Room::where('status', 0)->get(['id', 'name']);
        $timeSlots = ClassTimeSlot::where('status', 0)->get(['id', 'start_time', 'end_time']); // --- NEW: Fetch time slots

        return Inertia::render('Timetable/Edit', [
            'timetableEntry' => $timetableEntry,
            'classes' => $classes,
            'sessions' => $sessions,
            'sections' => $sections,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'rooms' => $rooms,
            'timeSlots' => $timeSlots, // --- NEW: Pass time slots to the Edit view
            'flash' => session('flash'),
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[],
        ]);
    }

    /**
     * Update the specified timetable entry in storage.
     */
    public function update(Request $request, ClassTime $timetableEntry)
    {
        // --- UPDATED VALIDATION ---
        $validated = $request->validate([
            'class_name_id' => 'required|exists:class_names,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
            'day_of_week' => ['required', Rule::in(['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'])],
            'class_time_slot_id' => 'required|exists:class_time_slots,id', // --- NEW: Validate the new ID
            'room_id' => 'nullable|exists:rooms,id',
            'status' => 'required|integer|in:0,1',
        ]);

        // Conflict detection for update (exclude current entry from conflict check)
        // --- UPDATED: Logic simplified to check based on class_time_slot_id
        $conflicts = ClassTime::where('id', '!=', $timetableEntry->id)
            ->where('session_id', $validated['session_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->where('class_time_slot_id', $validated['class_time_slot_id'])
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('class_name_id', $validated['class_name_id'])
                        ->where('section_id', $validated['section_id']);
                })
                    ->orWhere('teacher_id', $validated['teacher_id']);

                if (!empty($validated['room_id'])) {
                    $query->orWhere('room_id', $validated['room_id']);
                }
            })
            ->exists();

        if ($conflicts) {
            return redirect()->back()->withErrors([
                'general' => 'A conflict was detected: The selected class-section, teacher, or room is already booked for this time slot.'
            ])->withInput();
        }

        $timetableEntry->update($validated);

        return redirect()->route('timetable.index')->with('flash', [
            'message' => 'Timetable entry updated successfully!',
            'type' => 'success'
        ]);
    }

    /**
     * Remove the specified timetable entry from storage.
     */
    public function destroy(ClassTime $timetableEntry)
    {
        try {
            $timetableEntry->delete();
            return redirect()->back()->with('flash', ['message' => 'Timetable entry deleted successfully!', 'type' => 'success']);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', ['message' => 'Error deleting timetable entry: ' . $e->getMessage(), 'type' => 'error']);
        }
    }

    /**
     * API endpoint to dynamically get filtered subjects and teachers based on class, section, and session.
     */
    public function getFilteredData(Request $request)
    {
        $request->validate([
            'class_name_id' => 'required|exists:class_names,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
        ]);

        $classSubjects = ClassSubject::where('class_name_id', $request->class_name_id)
            ->where('section_id', $request->section_id)
            ->where('session_id', $request->session_id)
            ->with(['subject', 'teacher'])
            ->get();

        $subjects = $classSubjects->map(function ($cs) {
            return $cs->subject ? ['id' => $cs->subject->id, 'name' => $cs->subject->name, 'code' => $cs->subject->code] : null;
        })->filter()->unique('id')->values();

        $teachers = $classSubjects->map(function ($cs) {
            return $cs->teacher ? ['id' => $cs->teacher->id, 'name' => $cs->teacher->name, 'subject_taught' => $cs->teacher->subject_taught] : null;
        })->filter()->unique('id')->values();

        return response()->json([
            'subjects' => $subjects,
            'teachers' => $teachers,
        ]);
    }

    // For managing Class Time Slots (like periods, breaks, etc.)
    public function classTimeSlotIndex()
    {
        $timeSlots = ClassTimeSlot::all();

        return Inertia::render('ClassTimeSlots/Index', [
            'timeSlots' => $timeSlots,
        ]);
    }

    // Show the form for creating a new resource.
    public function classTimeSlotCreate()
    {
        return Inertia::render('ClassTimeSlots/Create');
    }

    // Store a newly created resource in storage.
    public function classTimeSlotStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:class_time_slots,name',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        ClassTimeSlot::create($validated);

        return redirect()->route('class-time-slots.index')->with('success', 'Time slot created successfully.');
    }


    // Show the form for editing the specified resource.
    public function classTimeSlotEdit(ClassTimeSlot $classTimeSlot)
    {
        return Inertia::render('ClassTimeSlots/Edit', [
            'timeSlot' => $classTimeSlot,
        ]);
    }

    public function classTimeSlotUpdate(Request $request, ClassTimeSlot $classTimeSlot)
    {
        // Intercept and format the time strings
        $startTime = Carbon::parse($request->input('start_time'))->format('H:i');
        $endTime = Carbon::parse($request->input('end_time'))->format('H:i');

        // Create a new request object or modify the current one
        $request->merge([
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);

        // Now, the validation will receive the corrected H:i format
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:class_time_slots,name,' . $classTimeSlot->id,
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        $classTimeSlot->update($validated);

        return redirect()->route('class-time-slots.index')->with('success', 'Time slot updated successfully.');
    }

    public function classTimeSlotDestroy(ClassTimeSlot $classTimeSlot)
    {
        $classTimeSlot->delete();

        return redirect()->route('class-time-slots.index')->with('success', 'Time slot deleted successfully.');
    }
}
