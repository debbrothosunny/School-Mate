<?php

namespace App\Http\Controllers;

use App\Models\ClassTime;
use App\Models\ClassName;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Section;
use App\Models\ClassSession;
use App\Models\Setting;
use App\Models\ClassTimeSlot; // This line is the key
use App\Models\Room;
use App\Models\Group;
use App\Models\ClassSubject;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Log;

class ClassTimeController extends Controller
{
    public function index(Request $request)
    {
        // Fetch unique class names with the first corresponding id, renaming class_name to name
        $classes = ClassName::where('status', 0)
            ->select('id', 'class_name as name')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('class_names')
                    ->where('status', 0)
                    ->groupBy('class_name');
            })
            ->get();

        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        $subjects = Subject::where('status', 0)->get(['id', 'name']);
        $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
        $rooms = Room::where('status', 0)->get(['id', 'name']);
        $timeSlots = ClassTimeSlot::get(['id', 'name', 'start_time', 'end_time']);
        // Fetch all groups (assuming status 0 means active)
        $groups = Group::where('status', 0)->get(['id', 'name']);

        // Apply filters based on request input
        $query = ClassTime::query()
            ->with(['className', 'subject', 'teacher', 'section', 'session', 'room', 'classTimeSlot', 'group']);

        if ($request->filled('class_name_id')) {
            $query->where('class_name_id', $request->class_name_id);
        }
        if ($request->filled('session_id')) {
            $query->where('session_id', $request->session_id);
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        if ($request->filled('group_id')) {
            $query->where('group_id', $request->group_id);
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
        if ($request->filled('class_time_slot_id')) {
            $query->where('class_time_slot_id', $request->class_time_slot_id);
        }

        // Order results for better display
        $timetableEntries = $query->orderBy('day_of_week')
            ->orderBy('class_time_slot_id')
            ->get()
            ->map(function ($entry) {
                return [
                    'id' => $entry->id,
                    'class_name_id' => $entry->class_name_id,
                    'subject_id' => $entry->subject_id,
                    'teacher_id' => $entry->teacher_id,
                    'section_id' => $entry->section_id,
                    'session_id' => $entry->session_id,
                    'day_of_week' => $entry->day_of_week,
                    'class_time_slot_id' => $entry->class_time_slot_id,
                    'room_id' => $entry->room_id,
                    'group_id' => $entry->group_id,
                    'status' => $entry->status,
                    'created_at' => $entry->created_at,
                    'updated_at' => $entry->updated_at,
                    'className' => $entry->className,
                    'subject' => $entry->subject,
                    'teacher' => $entry->teacher,
                    'section' => $entry->section,
                    'session' => $entry->session,
                    'room' => $entry->room,
                    'classTimeSlot' => $entry->classTimeSlot,
                    'group' => $entry->group,
                ];
            });

        return Inertia::render('Timetable/Index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'sections' => $sections,
            'groups' => $groups,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'rooms' => $rooms,
            'timeSlots' => $timeSlots,
            'timetableEntries' => $timetableEntries,
            'selectedFilters' => $request->only(['class_name_id', 'session_id', 'section_id', 'group_id', 'day_of_week', 'teacher_id', 'subject_id', 'room_id', 'class_time_slot_id']),
            'flash' => session('flash'),
        ]);
    } 

    public function create()
    {
        // Fetch unique class names with the first corresponding id
        $classNames = ClassName::where('status', 0)
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
        $subjects = Subject::where('status', 0)->get(['id', 'name']);
        $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
        $rooms = Room::where('status', 0)->get(['id', 'name']);
        $timeSlots = ClassTimeSlot::get(['id', 'start_time', 'end_time']);
        $groups = Group::where('status', 0)->get(['id', 'name']); // Fetch groups

        // Static array for days of the week
        $daysOfWeek = [
            'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
        ];

        // Fetch the class-subject-teacher assignments
        $classSubjects = ClassSubject::where('status', 0)->get();

        // Fetch all existing timetable entries to check for booked slots
        $existingTimetables = ClassTime::all();

        // Define Class IDs that require a group for the frontend logic
        $groupRequiredClassIds = [9, 10]; // Replace with actual IDs from class_names table

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
            'groups' => $groups,
            'groupRequiredClassIds' => $groupRequiredClassIds,
        ]);
    }

    /**
     * Store a newly created timetable entry.
    */
    public function store(Request $request)
    {
        // Define Class IDs that require a group selection in the UI (Nine and Ten)
        // We keep this list only to drive the validation rule, matching the Vue's isGroupRequired logic.
        $groupRequiredClassIds = [9, 10]; 
        
        // Fetch the 'None' group for comparison, if needed, but the logic relies on the ID sent from Vue.
        $noneGroup = Group::where('name', 'None')->first();

        // 1. Determine the validation rule for group_id
        $groupValidationRule = in_array($request->input('class_name_id'), $groupRequiredClassIds)
            ? 'required|exists:groups,id' // Required for classes 9 & 10 (can be ID 4 'None')
            : 'required|exists:groups,id'; // REQUIRED for 6, 7, 8 (as Vue auto-sets it to ID 4, and DB is NOT NULL)

        // 2. Validate the incoming request data
        $validatedData = $request->validate([
            'class_name_id' => 'required|exists:class_names,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
            'day_of_week' => 'required|string',
            'room_id' => 'required|exists:rooms,id',
            'class_time_slot_id' => 'required|exists:class_time_slots,id',
            'group_id' => $groupValidationRule, // Adjusted: Now required for all (ID 4 is always sent for 6/7/8)
        ]);

        // 🚨 CRITICAL ADJUSTMENT: REMOVE ALL NULL CONVERSION LOGIC
        // We trust that Vue sends the correct group_id (ID 4 for 6/7/8 and the chosen ID for 9/10).
        // The DB will now save group_id = 4 for all non-specialized entries.

        Log::info("Starting conflict checks for class schedule creation.", $validatedData);

        // 3. Perform the conflict checks
        
        // Check 1: Class/Section/Group Conflict
        $classSectionConflictQuery = ClassTime::where('day_of_week', $validatedData['day_of_week'])
            ->where('class_time_slot_id', $validatedData['class_time_slot_id'])
            ->where('class_name_id', $validatedData['class_name_id'])
            ->where('section_id', $validatedData['section_id'])
            // Now, we always check for an exact group_id match, as group_id is never NULL.
            ->where('group_id', $validatedData['group_id']); 
            
        $classSectionConflict = $classSectionConflictQuery->exists();

        // Check 2: Teacher Conflict
        $teacherConflict = ClassTime::where('day_of_week', $validatedData['day_of_week'])
            ->where('class_time_slot_id', $validatedData['class_time_slot_id'])
            ->where('teacher_id', $validatedData['teacher_id'])
            ->exists();

        // Check 3: Room Conflict
        $roomConflict = ClassTime::where('day_of_week', $validatedData['day_of_week'])
            ->where('class_time_slot_id', $validatedData['class_time_slot_id'])
            ->where('room_id', $validatedData['room_id'])
            ->exists();

        // 4. Handle Conflicts
        if ($classSectionConflict || $teacherConflict || $roomConflict) {
            $message = "Conflict detected! ";
            if ($classSectionConflict) $message .= "Class/Section/Group is already booked. ";
            if ($teacherConflict) $message .= "Teacher is already busy. ";
            if ($roomConflict) $message .= "Room is already booked. ";

            return redirect()->back()
                ->withInput()
                ->with('flash', ['type' => 'error', 'message' => $message]);
        }
        
        // 5. If no conflict, create the new class schedule entry
        ClassTime::create($validatedData);

        // 6. Redirect with a success message
        return redirect()->route('timetable.index')
            ->with('flash', ['type'=>'success','message'=>'Class schedule created successfully!']);
    }

    /**
     * Show the form for editing the specified timetable entry.
    */
    public function edit(ClassTime $timetableEntry)
    {
        // Eager load relationships, including 'group'
        $timetableEntry->load(['className', 'subject', 'teacher', 'section', 'session', 'room', 'classTimeSlot', 'group']);

        // Fetch unique class names with the first corresponding id, renaming class_name to name
        $classes = ClassName::where('status', 0)
            ->select('id', 'class_name as name')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('class_names')
                    ->where('status', 0)
                    ->groupBy('class_name');
            })
            ->get();

        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        $subjects = Subject::where('status', 0)->get(['id', 'name']);
        $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
        $rooms = Room::where('status', 0)->get(['id', 'name']);
        $timeSlots = ClassTimeSlot::where('status', 0)->get(['id', 'start_time', 'end_time']);
        $groups = Group::where('status', 0)->get(['id', 'name']); // Fetch groups

        return Inertia::render('Timetable/Edit', [
            'timetableEntry' => $timetableEntry,
            'classes' => $classes,
            'sessions' => $sessions,
            'sections' => $sections,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'rooms' => $rooms,
            'timeSlots' => $timeSlots,
            'groups' => $groups,
            'flash' => session('flash'),
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[],
        ]);
    }

    /**
     * Update the specified timetable entry in storage.
    */
    // ... imports ...

    public function update(Request $request, ClassTime $timetableEntry)
    {
        // Determine which classes require a group ID (e.g., 9 and 10)
        $groupRequiredClassIds = ClassName::whereIn('class_name', ['Class 9', 'Class 10'])
                                        ->pluck('id')
                                        ->toArray();

        $validated = $request->validate([
            'class_name_id' => 'required|exists:class_names,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'required|exists:teachers,id',
            'section_id' => 'required|exists:sections,id',
            'session_id' => 'required|exists:class_sessions,id',
            'day_of_week' => ['required', Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])],
            'class_time_slot_id' => 'required|exists:class_time_slots,id',
            'room_id' => 'nullable|exists:rooms,id',
            'status' => 'required|integer|in:0,1',
            // --- NEW: Validation for group_id ---
            'group_id' => [
                'required', // Always required, even for 'None' ID
                'exists:groups,id',
                // Optional/Alternative: Make required *only* for Class 9/10:
                // new RequiredIf(in_array($request->class_name_id, $groupRequiredClassIds)),
            ],
        ]);

        // Conflict detection for update (exclude current entry from conflict check)
        $conflicts = ClassTime::where('id', '!=', $timetableEntry->id)
            ->where('session_id', $validated['session_id'])
            ->where('day_of_week', $validated['day_of_week'])
            ->where('class_time_slot_id', $validated['class_time_slot_id'])
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    // --- UPDATED: Class/Section/Group Conflict Check ---
                    $q->where('class_name_id', $validated['class_name_id'])
                    ->where('section_id', $validated['section_id'])
                    ->where('group_id', $validated['group_id']);
                })
                ->orWhere('teacher_id', $validated['teacher_id']);

                if (!empty($validated['room_id'])) {
                    $query->orWhere('room_id', $validated['room_id']);
                }
            })
            ->exists();

        if ($conflicts) {
            return redirect()->back()->withErrors([
                'general' => 'A conflict was detected: The selected class-section-group, teacher, or room is already booked for this time slot.'
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
            return redirect()->back()->with('flash', ['message' => 'Timetable deleted successfully!', 'type' => 'success']);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', ['message' => 'Error deleting timetable : ' . $e->getMessage(), 'type' => 'error']);
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
            return $cs->subject ? ['id' => $cs->subject->id, 'name' => $cs->subject->name] : null;
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
            // Pass any flash message explicitly if exists
            'message' => session('message'),
            'type' => session('type'),
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




    // PDF Generation for Timetable
public function generatePdf(Request $request)
{
    // 1. Fetch layout data (timeSlots, daysOfWeek) internally
    $timeSlots = ClassTimeSlot::orderBy('start_time')->get();
    $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    
    // --- ENHANCEMENT START: Fetch variables needed for filter context ---
    $classes = ClassName::all(); 
    $teachers = Teacher::all();
    $sections = Section::all(); 
    // --- ENHANCEMENT END ---
      
    // 2. Filter Timetable Entries based on the request parameters
    $timetableQuery = ClassTime::with(['className', 'section', 'subject', 'teacher', 'room', 'classTimeSlot']);

    // Apply only the filters received from the Vue component
    if ($request->filled('class_name_id')) {
        $timetableQuery->where('class_name_id', $request->input('class_name_id'));
    }
    
    if ($request->filled('section_id')) { 
        $timetableQuery->where('section_id', $request->input('section_id'));
    }

    if ($request->filled('teacher_id')) {
        $timetableQuery->where('teacher_id', $request->input('teacher_id'));
    }
    
    if ($request->filled('day_of_week')) {
        $timetableQuery->where('day_of_week', $request->input('day_of_week'));
    }
    // ... apply other filters as needed

    $timetableEntries = $timetableQuery
                            ->orderByRaw("FIELD(day_of_week, '".implode("','", $daysOfWeek)."')")
                            ->orderBy('class_time_slot_id')
                            ->get();
                            
    $groupedTimetable = $timetableEntries->groupBy('day_of_week');
    $settings = Setting::first(); 

    // --- LOGIC ADDED: School Logo Handling (Copied from downloadAllResultsPdf) ---
    $schoolLogoUrl = null;
    if ($settings && !empty($settings->school_logo)) {
        // Assuming base_path() is correct for your asset structure
        $imagePath = base_path($settings->school_logo); 
        
        if (file_exists($imagePath) && is_readable($imagePath)) {
            try {
                $mimeType = mime_content_type($imagePath);
                if (in_array($mimeType, ['image/png', 'image/jpeg', 'image/jpg'])) {
                    $imageContents = file_get_contents($imagePath);
                    $schoolLogoUrl = 'data:' . $mimeType . ';base64,' . base64_encode($imageContents);
                } else {
                    \Log::warning('PDF LOGO CHECK: Unsupported image format for timetable', ['path' => $imagePath, 'mimeType' => $mimeType]);
                }
            } catch (\Exception $e) {
                \Log::error('PDF LOGO CHECK: Base64 encoding failed for timetable: ' . $e->getMessage(), ['path' => $imagePath]);
            }
        } else {
            \Log::warning('PDF LOGO CHECK: Logo file missing or unreadable for timetable', ['path' => $imagePath]);
        }
    }
    // --- END LOGIC ADDED ---

    // 3. Generate the PDF
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.timetable_report', [
        'groupedTimetable' => $groupedTimetable,
        'timeSlots' => $timeSlots,
        'daysOfWeek' => $daysOfWeek, 
        'settings' => $settings,
        'appliedFilters' => $request->all(),
        'classes' => $classes,
        'teachers' => $teachers,
        'sections' => $sections, 
        // --- PASS NEW VARIABLE TO VIEW ---
        'schoolLogoUrl' => $schoolLogoUrl, 
    ]);

    // 4. Stream the PDF
    $fileName = 'Timetable-Report-' . now()->format('Ymd') . '.pdf'; 
    
    // You can customize the file name based on class/section if they are set
    if ($request->filled('class_name_id')) {
        $className = $classes->firstWhere('id', $request->input('class_name_id'))?->name;
        $sectionName = $request->filled('section_id') ? $sections->firstWhere('id', $request->input('section_id'))?->name : '';
        $fileName = 'Routine-' . ($className ?? 'Class') . ($sectionName ? '-' . $sectionName : '') . '-' . now()->format('Ymd') . '.pdf';
    }

    return $pdf->stream($fileName); 
}
}
