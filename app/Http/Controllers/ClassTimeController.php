<?php

namespace App\Http\Controllers;

use App\Models\ClassTime;
use App\Models\ClassName;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Section;
use App\Models\ClassSession;
use App\Models\Room;
use App\Models\ClassSubject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

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

        // Apply filters based on request input
        $query = ClassTime::query()
            ->with(['className', 'subject', 'teacher', 'section', 'session', 'room']); // Eager load 'room' relationship

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
        if ($request->filled('room_id')) { // Add filter for room_id
            $query->where('room_id', $request->room_id);
        }

        // Order results for better display
        $timetableEntries = $query->orderBy('day_of_week')
                                    ->orderBy('start_time')
                                    ->get();

        // dd($timetableEntries->toArray());

        return Inertia::render('Timetable/Index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'sections' => $sections,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'rooms' => $rooms, // Pass rooms to the Inertia view for filter dropdown
            'timetableEntries' => $timetableEntries,
            'selectedFilters' => $request->only(['class_name_id', 'session_id', 'section_id', 'day_of_week', 'teacher_id', 'subject_id', 'room_id']), // Include room_id in selected filters
            'flash' => session('flash'),
        ]);
    }

    public function create()
    {
        $classes = ClassName::where('status', 0)->get(['id', 'class_name as name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        $rooms = Room::where('status', 0)->get(['id', 'name']); // Fetch active rooms for create form

        return Inertia::render('Timetable/Create', [
            'classes' => $classes,
            'sessions' => $sessions,
            'sections' => $sections,
            'rooms' => $rooms, // Pass rooms to the Create view
        ]);
    }

    /**
     * Store a newly created timetable entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_name_id' => 'required|exists:class_names,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
            'day_of_week' => ['required', Rule::in(['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'])],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room_id' => 'nullable|exists:rooms,id',
            'status' => 'required|integer|in:0,1',
        ]);

        // Backend conflict detection (in addition to database unique constraints)
        $conflicts = ClassTime::where('session_id', $validated['session_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->where(function ($query) use ($validated) {
                // Check for overlapping times
                $query->where(function ($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                      ->where('end_time', '>', $validated['start_time']);
                });
            })
            ->where(function ($query) use ($validated) {
                // Check for conflicts by class-section, teacher, or room
                $query->where(function ($q) use ($validated) {
                    $q->where('class_name_id', $validated['class_name_id'])
                      ->where('section_id', $validated['section_id']);
                })
                ->orWhere('teacher_id', $validated['teacher_id']);

                // Room conflict: Only check if room_id is provided
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


        ClassTime::create($validated);

        return redirect()->route('timetable.index')->with('flash', [
            'message' => 'Timetable entry created successfully!',
            'type' => 'success'
        ]);
    }


    public function checkConflicts(Request $request)
    {
        // Validate the incoming data for the conflict check
        $request->validate([
            'class_name_id' => 'required|exists:class_names,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
            'day_of_week' => ['required', Rule::in(['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'])],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room_id' => 'nullable|exists:rooms,id',
            // 'timetable_id' => 'nullable|exists:class_times,id', // Optional: for edit page to exclude itself
        ]);

        $input = $request->only([
            'class_name_id', 'subject_id', 'teacher_id', 'section_id', 'session_id',
            'day_of_week', 'start_time', 'end_time', 'room_id'
        ]);

        // Start building the conflict query
        $query = ClassTime::where('session_id', $input['session_id'])
            ->where('day_of_week', $input['day_of_week'])
            ->where(function ($q) use ($input) {
                // Check for overlapping times
                $q->where('start_time', '<', $input['end_time'])
                  ->where('end_time', '>', $input['start_time']);
            });

        // Add conditions for class-section, teacher, or room conflicts
        $query->where(function ($q) use ($input) {
            // Class-Section conflict
            $q->where(function ($subQ) use ($input) {
                $subQ->where('class_name_id', $input['class_name_id'])
                     ->where('section_id', $input['section_id']);
            })
            // Teacher conflict
            ->orWhere('teacher_id', $input['teacher_id']);

            // Room conflict (only if room_id is provided)
            if (!empty($input['room_id'])) {
                $q->orWhere('room_id', $input['room_id']);
            }
        });

        // If checking for conflicts during an UPDATE (edit page), exclude the current entry
        // For CREATE page, this is not needed. This is a common pattern, so I'll include it for future use.
        // if ($request->filled('timetable_id')) {
        //     $query->where('id', '!=', $request->timetable_id);
        // }

        $conflictingEntry = $query->with(['className', 'subject', 'teacher', 'section', 'room'])->first(); // Get the first conflicting entry with its relations

        if ($conflictingEntry) {
            // Format conflict details for the frontend
            $conflictDetails = [
                'isConflict' => true,
                'message' => 'This time slot conflicts with an existing entry:',
                'type' => 'conflict',
                'details' => [
                    'class_name' => $conflictingEntry->className->class_name ?? 'N/A',
                    'section_name' => $conflictingEntry->section->name ?? 'N/A',
                    'subject_name' => $conflictingEntry->subject->name ?? 'N/A',
                    'teacher_name' => $conflictingEntry->teacher->name ?? 'N/A',
                    'room_name' => $conflictingEntry->room->name ?? 'N/A',
                    'day_of_week' => $conflictingEntry->day_of_week,
                    'start_time' => $conflictingEntry->start_time->format('H:i'), // Ensure Carbon casts are used
                    'end_time' => $conflictingEntry->end_time->format('H:i'),
                ]
            ];
            return response()->json($conflictDetails, 409); // Use 409 Conflict status
        }

        return response()->json([
            'isConflict' => false,
            'message' => 'Time slot is available.',
            'type' => 'available'
        ]);
    }



    public function getOccupiedSlots(Request $request)
    {
        // Validate the incoming parameters
        $request->validate([
            'day_of_week' => ['required', Rule::in(['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'])],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'room_id' => 'nullable|exists:rooms,id', // room_id is optional but must exist if provided
            'session_id' => 'required|exists:class_sessions,id', // Session ID is crucial for context
            // 'exclude_timetable_id' => 'nullable|exists:class_times,id', // Optional: for edit page to exclude current entry
        ]);

        $dayOfWeek = $request->input('day_of_week');
        $startTime = $request->input('start_time');
        $endTime = $request->input('end_time');
        $roomId = $request->input('room_id');
        $sessionId = $request->input('session_id');
        // $excludeId = $request->input('exclude_timetable_id'); // For edit page

        $occupiedSlots = ClassTime::where('session_id', $sessionId)
            ->where('day_of_week', $dayOfWeek)
            // Check for overlapping times
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            // Filter by room_id specifically
            ->when($roomId, function ($query, $roomId) {
                // If a room_id is provided, find entries in that room
                return $query->where('room_id', $roomId);
            }, function ($query) {
                // If no room_id is provided, find entries where room_id is NULL
                // This covers "no specific room" bookings
                return $query->whereNull('room_id');
            })
            // ->when($excludeId, function ($query, $excludeId) { // For edit page
            //     return $query->where('id', '!=', $excludeId);
            // })
            ->with(['className', 'subject', 'teacher', 'section', 'room']) // Eager load relationships
            ->get();

        // Transform results to a more frontend-friendly format if needed
        $formattedSlots = $occupiedSlots->map(function ($slot) {
            return [
                'id' => $slot->id,
                'class_name' => $slot->className->class_name ?? 'N/A',
                'section_name' => $slot->section->name ?? 'N/A',
                'subject_name' => $slot->subject->name ?? 'N/A',
                'teacher_name' => $slot->teacher->name ?? 'N/A',
                'room_name' => $slot->room->name ?? 'N/A',
                'day_of_week' => $slot->day_of_week,
                'start_time' => $slot->start_time->format('H:i'),
                'end_time' => $slot->end_time->format('H:i'),
            ];
        });

        return response()->json($formattedSlots);
    }

    /**
     * Show the form for editing the specified timetable entry.
     */
    public function edit(ClassTime $timetableEntry)
    {
        // Eager load relationships for the current entry, including 'room'
        $timetableEntry->load(['className', 'subject', 'teacher', 'section', 'session', 'room']);

        // Provide all options for dropdowns, similar to index
        $classes = ClassName::where('status', 0)->get(['id', 'class_name as name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        $subjects = Subject::where('status', 0)->get(['id', 'name', 'code']);
        $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
        $rooms = Room::where('status', 0)->get(['id', 'name']); // Fetch active rooms for edit form

        return Inertia::render('Timetable/Edit', [
            'timetableEntry' => $timetableEntry,
            'classes' => $classes,
            'sessions' => $sessions,
            'sections' => $sections,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'rooms' => $rooms, // Pass rooms to the Edit view
            'flash' => session('flash'),
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[], // Ensure errors are passed
        ]);
    }

    /**
     * Update the specified timetable entry in storage.
     */
    public function update(Request $request, ClassTime $timetableEntry)
    {
        $validated = $request->validate([
            'class_name_id' => 'required|exists:class_names,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
            'day_of_week' => ['required', Rule::in(['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY', 'SUNDAY'])],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room_id' => 'nullable|exists:rooms,id',
            'status' => 'required|integer|in:0,1',
        ]);

        // Conflict detection for update (exclude current entry from conflict check)
        $conflicts = ClassTime::where('id', '!=', $timetableEntry->id) // Exclude current entry
            ->where('session_id', $validated['session_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->where(function ($query) use ($validated) {
                // Check for overlapping times
                $query->where(function ($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                      ->where('end_time', '>', $validated['start_time']);
                });
            })
            ->where(function ($query) use ($validated) {
                // Check for conflicts by class-section, teacher, or room
                $query->where(function ($q) use ($validated) {
                    $q->where('class_name_id', $validated['class_name_id'])
                      ->where('section_id', $validated['section_id']);
                })
                ->orWhere('teacher_id', $validated['teacher_id']);

                // Room conflict: Only check if room_id is provided
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
        })->filter()->unique('id')->values(); // Get unique subjects and reset keys

        $teachers = $classSubjects->map(function ($cs) {
            return $cs->teacher ? ['id' => $cs->teacher->id, 'name' => $cs->teacher->name, 'subject_taught' => $cs->teacher->subject_taught] : null;
        })->filter()->unique('id')->values(); // Get unique teachers and reset keys

        return response()->json([
            'subjects' => $subjects,
            'teachers' => $teachers,
        ]);
    }
}
