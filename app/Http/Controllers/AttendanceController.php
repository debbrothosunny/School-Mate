<?php

namespace App\Http\Controllers;

use App\Models\ClassName;
use App\Models\ClassSession;
use App\Models\Group;
use App\Models\Section;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\TeacherAttendance;
use App\Models\Teacher;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; 

class AttendanceController extends Controller
{
    

    /**
     * Display the teacher students attendance management page.
    */

    public function teacherAttendanceIndex(Request $request)
    {
        // $teacher->isClassTeacher() মেথডটি যদি Teacher মডেলে না থাকে, তাহলে এটি সমস্যা করতে পারে।
        // আমরা এখন এটি সরাসরি teacher->class_id চেক করে দেখতে পারি।
        $teacher = Teacher::with(['assignedClass', 'assignedSection', 'assignedGroup'])
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // 1. Check if the teacher is assigned to a class (Class Teacher check)
        if (empty($teacher->class_id)) {
            // Vue কম্পোনেন্টের সঙ্গে সামঞ্জস্য রেখে 'error' ফ্ল্যাশ মেসেজ ব্যবহার করা হয়েছে।
            return Inertia::render('TeacherAttendance/Index', [
                'classes'  => collect(),
                'sessions' => collect(),
                'sections' => collect(),
                'groups'   => collect(),
                'students' => collect(),
                'teacher'  => null,
                'flash'    => ['error' => 'আপনি এখনও কোনো ক্লাসের ক্লাস টিচার হিসেবে নিযুক্ত হননি।'],
            ]);
        }

        $sessions = ClassSession::select('id', 'name')->get();
        $sections = Section::select('id', 'name')->get();
        $groups   = Group::select('id', 'name')->get();

        $sessionId = $request->integer('session_id');
        $sectionId = $request->integer('section_id');
        $groupId   = $request->integer('group_id');
        $date      = $request->input('attendance_date');

        $students = collect();

        if ($sessionId && $sectionId && $groupId && $date) {
            // শুধুমাত্র শিক্ষকের Assigned Class-এর ছাত্র লোড করা হবে
            $students = Student::where([
                'class_id'   => $teacher->class_id,
                'session_id' => $sessionId,
                'section_id' => $sectionId,
                'group_id'   => $groupId,
            ])
            ->select('id', 'name', 'roll_number')
            ->orderBy('roll_number')
            ->get();

            if ($students->isNotEmpty()) {
                $existing = Attendance::where([
                    'class_id'   => $teacher->class_id,
                    'session_id' => $sessionId,
                    'section_id' => $sectionId,
                    'group_id'   => $groupId,
                    'date'       => $date,
                ])
                ->whereIn('student_id', $students->pluck('id'))
                ->pluck('status', 'student_id');

                foreach ($students as $student) {
                    $student->attendance_status = $existing[$student->id] ?? 'present';
                }
            }
        }

        $assignedClass = [
            'id'         => $teacher->class_id,
            'class_name' => $teacher->assignedClass?->class_name ?? 'N/A',
        ];

        return Inertia::render('TeacherAttendance/Index', [
            // শুধুমাত্র Assigned ক্লাসটি পাঠানো হচ্ছে
            'classes'              => collect([$assignedClass]), 
            'sessions'             => $sessions,
            'sections'             => $sections,
            'groups'               => $groups,
            'students'             => $students,
            'teacher'              => [
                'name'             => $teacher->name,
                'assigned_class'   => $assignedClass['class_name'],
                'assigned_section' => $teacher->assignedSection?->name,
                'assigned_group'   => $teacher->assignedGroup?->name,
            ],
            'selectedSessionId'      => $sessionId,
            'selectedSectionId'      => $sectionId,
            'selectedGroupId'        => $groupId,
            'selectedAttendanceDate' => $date,
        ]);
    }

    /**
     * Store the attendance record.
    */
    public function teacherAttendanceStore(Request $request)
    {
        $classIdColumn = 'class_id'; 
        
        $teacher = Teacher::where('user_id', Auth::id())->first();

        if (!$teacher) {
            return back()->with('error', 'আপনার শিক্ষক অ্যাকাউন্ট খুঁজে পাওয়া যায়নি।');
        }

        // 1. Validation 
        $validated = $request->validate([
            'session_id'    => 'required|exists:class_sessions,id',
            'section_id'    => 'required|exists:sections,id',
            'group_id'      => 'required|exists:groups,id',
            'attendance_date'=> 'required|date|before_or_equal:today|after_or_equal:today', 
            'attendance_data'=> 'required|array|min:1',
            'attendance_data.*.student_id' => 'required|exists:students,id',
            'attendance_data.*.status'     => 'required|in:present,absent,late',
        ], 
        // 2. Custom Messages Array
        [
            'attendance_date.before_or_equal' => 'ভবিষ্যতের তারিখ সংরক্ষিত হবে না।',
            'attendance_date.after_or_equal' => ' অতীতের তারিখ সংরক্ষিত হবে না।',
        ]);
        
        // 2. Class Teacher Assignment Check
        if (empty($teacher->$classIdColumn) || empty($teacher->section_id)) {
            Log::warning("ATTENDANCE FAIL: Teacher #{$teacher->id} attempted to submit attendance but is not fully assigned (Class or Section missing).");
            return back()->with('error', 'আপনি কোনো ক্লাসের ক্লাস টিচার নন, তাই অ্যাটেনডেন্স নিতে পারবেন না।');
        }
        
        // 3. Authorization Check
        if ($teacher->section_id && (int)$request->section_id !== $teacher->section_id) {
            Log::warning("ATTENDANCE FAIL: Teacher #{$teacher->id} attempted to submit attendance for unauthorized Section ID: {$request->section_id}.");
            return back()->with('error', 'আপনি এই সেকশনের ক্লাস টিচার নন।');
        }
        if ($teacher->group_id && (int)$request->group_id !== $teacher->group_id) {
            Log::warning("ATTENDANCE FAIL: Teacher #{$teacher->id} attempted to submit attendance for unauthorized Group ID: {$request->group_id}.");
            return back()->with('error', 'আপনি এই গ্রুপের ক্লাস টিচার নন।');
        }

        // 4. অ্যাটেনডেন্স চেক লজিক (আপডেটের মেসেজের জন্য)
        $attendanceExists = Attendance::where([
            $classIdColumn  => $teacher->$classIdColumn,
            'session_id'    => $validated['session_id'],
            'section_id'    => $validated['section_id'],
            'group_id'      => $validated['group_id'],
            'date'          => $validated['attendance_date'],
        ])->exists();

        $successMessage = $attendanceExists 
            ? 'অ্যাটেনডেন্সের তথ্য সফলভাবে আপডেট করা হয়েছে!' 
            : 'অ্যাটেনডেন্স সফলভাবে সেভ করা হয়েছে!';

        // --- Transaction Starts Here ---
        DB::beginTransaction();
        try {
            Log::info("ATTENDANCE START: Teacher #{$teacher->id} saving {$request->attendance_date} attendance for Class {$teacher->$classIdColumn}, Section {$request->section_id}, Group {$request->group_id}. Total records: " . count($request->attendance_data));

            // অ্যাটেনডেন্স সেভ
            foreach ($request->attendance_data as $item) {
                
                // 5. এখানে `updateOrCreate` ব্যবহার করা হয়েছে
                Attendance::updateOrCreate(
                    // ইউনিক রেকর্ড খোঁজার জন্য
                    [
                        'student_id'    => $item['student_id'],
                        $classIdColumn  => $teacher->$classIdColumn, 
                        'session_id'    => $request->session_id,
                        'section_id'    => $request->section_id,
                        'group_id'      => $request->group_id,
                        'date'          => $request->attendance_date,
                    ],
                    // যে কলামগুলো আপডেট বা ইনসার্ট করা হবে
                    [
                        'status'    => $item['status'],
                        // 'taken_by' কলামটি এখান থেকে বাদ দেওয়া হয়েছে
                    ]
                );
            }

            DB::commit();

            Log::info("ATTENDANCE SUCCESS: Teacher #{$teacher->id} attendance successfully saved/updated.");
            return back()->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ATTENDANCE FATAL ERROR: Teacher Attendance Store Failed by Teacher #{$teacher->id}: " . $e->getMessage() . " Stack: " . $e->getTraceAsString());
            return back()->with('error', 'অ্যাটেনডেন্স সেভ করার সময় একটি অপ্রত্যাশিত ত্রুটি হয়েছে। বিস্তারিত তথ্যের জন্য সিস্টেম লগ চেক করুন।');
        }
    }


    // Attendance Report Generation
    public function report(Request $request)
    {
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

        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        $groups = Group::where('status', 0)->get(['id', 'name']);

        $classId = $request->input('class_id') ? (int) $request->input('class_id') : ($classes->first()->id ?? null);
        $sessionId = $request->input('session_id') ? (int) $request->input('session_id') : ($sessions->first()->id ?? null);
        $sectionId = $request->input('section_id') ? (int) $request->input('section_id') : ($sections->first()->id ?? null);
        $groupId = $request->input('group_id') ? (int) $request->input('group_id') : null;
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString()); // 2025-11-01
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());     // 2025-11-30

        \Log::info('Raw Date Range: startDate = ' . $startDate . ', endDate = ' . $endDate); // Debug log

        $students = collect();
        $attendanceRecords = collect();
        $attendanceQuery = collect(); // Initialize to avoid undefined variable
        $message = null;

        if ($classId && $sessionId && $sectionId && $groupId && $startDate && $endDate) {
            try {
                $students = Student::where('class_id', $classId)
                    ->where('session_id', $sessionId)
                    ->where('section_id', $sectionId)
                    ->where('group_id', $groupId)
                    ->select('id', 'name', 'roll_number', 'admission_number')
                    ->orderBy('roll_number')
                    ->get();

                \Log::info('Selected Students: ', $students->toArray()); // Log selected students

                if ($students->isEmpty()) {
                    $message = ['text' => 'No students found for the selected filters.', 'type' => 'info'];
                } else {
                    $attendanceQuery = Attendance::whereIn('student_id', $students->pluck('id'))
                        ->where('class_id', $classId)
                        ->where('session_id', $sessionId)
                        ->where('section_id', $sectionId)
                        ->where('group_id', $groupId)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->get();

                    \Log::info('Attendance Query Results: ', $attendanceQuery->toArray()); // Log query results

                    $attendanceRecords = $attendanceQuery->groupBy('student_id')->map(function ($records, $studentId) use ($startDate, $endDate) {
                        $dailyStatus = [];
                        $currentDate = Carbon::parse($startDate);
                        $end = Carbon::parse($endDate);
                        while ($currentDate->lte($end)) {
                            $dateKey = $currentDate->toDateString();
                            $record = $records->firstWhere('date', $dateKey);
                            $dailyStatus[$dateKey] = $record ? $record->status : null; // Use null for missing days
                            $currentDate->addDay();
                        }
                        return $dailyStatus;
                    });

                    $students = $students->map(function ($student) use ($attendanceQuery, $attendanceRecords, $startDate, $endDate) {
                        $studentRecords = $attendanceRecords->get($student->id, []);
                        $recordedDays = $attendanceQuery->where('student_id', $student->id)->count();
                        $presentDays = $attendanceQuery->where('student_id', $student->id)->where('status', 'present')->count();
                        $absentDays = $attendanceQuery->where('student_id', $student->id)->where('status', 'absent')->count();
                        $lateDays = $attendanceQuery->where('student_id', $student->id)->where('status', 'late')->count();

                        $attendancePercentage = $recordedDays > 0 ? round(($presentDays / $recordedDays) * 100, 2) : 0;

                        return [
                            'id' => $student->id,
                            'name' => $student->name,
                            'roll_number' => $student->roll_number ?? $student->admission_number ?? 'N/A',
                            'admission_number' => $student->admission_number ?? 'N/A',
                            'recorded_days' => $recordedDays,
                            'present_days' => $presentDays,
                            'absent_days' => $absentDays,
                            'late_days' => $lateDays,
                            'attendance_percentage' => $attendancePercentage,
                            'daily_status' => $studentRecords,
                        ];
                    });
                }
            } catch (\Exception $e) {
                $message = ['text' => 'Error generating report: ' . $e->getMessage(), 'type' => 'error'];
            }
        } else {
            $message = ['text' => 'Please select all required filters (Class, Session, Section, Group, and Date Range) to generate the report.', 'type' => 'info'];
        }

        return Inertia::render('AttendanceReport/Index', [
            'classes' => $classes,
            'sessions' => $sessions,
            'sections' => $sections,
            'groups' => $groups,
            'students' => $students,
            'selectedClassId' => $classId,
            'selectedSessionId' => $sessionId,
            'selectedSectionId' => $sectionId,
            'selectedGroupId' => $groupId,
            'selectedStartDate' => $startDate,
            'selectedEndDate' => $endDate,
            'initialMessage' => $message,
            'flash' => session('flash'),
        ]);
    }


    //   Export Attendance Report as PDF For Students
    public function exportPDF(Request $request)
    {
        // Increase resources for potentially large reports
        ini_set('memory_limit', '512M');
        set_time_limit(300);
        
        $classId = $request->input('class_id');
        $sessionId = $request->input('session_id');
        $sectionId = $request->input('section_id');
        $groupId = $request->input('group_id');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Validation might be useful here, especially for dates.
        // $request->validate([...]); 

        $class = ClassName::findOrFail($classId);
        $session = ClassSession::findOrFail($sessionId);
        $section = Section::findOrFail($sectionId);
        $group = Group::findOrFail($groupId);

        // NOTE: The previous report generation logic using ->where('student_id', $student->id) 
        // inside the map loop can lead to incorrect counts because it filters the original 
        // $attendanceQuery Collection in place. You should use a fresh collection or the 
        // keyBy/groupBy method for correct counting, but I will maintain your current 
        // structure and pass the full data set to the map function.
        
        $students = Student::where('class_id', $classId)
            ->where('session_id', $sessionId)
            ->where('section_id', $sectionId)
            ->where('group_id', $groupId)
            ->select('id', 'name', 'roll_number', 'admission_number')
            ->orderBy('roll_number')
            ->get();

        $attendanceQuery = Attendance::whereIn('student_id', $students->pluck('id'))
            ->where('class_id', $classId)
            ->where('session_id', $sessionId)
            ->where('section_id', $sectionId)
            ->where('group_id', $groupId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        \Log::info('Attendance Query Results: ', $attendanceQuery->toArray());

        $reportData = $students->map(function ($student) use ($attendanceQuery, $startDate, $endDate) {
            // Use filter() to get records for the current student without modifying the original collection
            $studentRecords = $attendanceQuery->filter(fn ($record) => $record->student_id === $student->id); 
            
            $recordedDays = $studentRecords->count();
            $presentDays = $studentRecords->where('status', 'present')->count();
            $absentDays = $studentRecords->where('status', 'absent')->count();
            $lateDays = $studentRecords->where('status', 'late')->count();
            $attendancePercentage = $recordedDays > 0 ? round(($presentDays / $recordedDays) * 100, 2) : 0;

            // Generate daily status
            $dailyStatus = [];
            $currentDate = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            while ($currentDate->lte($end)) {
                $dateKey = $currentDate->toDateString();
                $record = $studentRecords->firstWhere('date', $dateKey);
                $dailyStatus[$dateKey] = $record ? $record->status : null;
                $currentDate->addDay();
            }

            // Inline percentage class logic
            $percentageClass = '';
            if ($attendancePercentage >= 90) {
                $percentageClass = 'bg-green-100';
            } elseif ($attendancePercentage >= 70) {
                $percentageClass = 'bg-yellow-100';
            } else {
                $percentageClass = 'bg-red-100';
            }

            return [
                'name' => $student->name,
                'roll_number' => $student->roll_number ?? $student->admission_number ?? 'N/A',
                'admission_number' => $student->admission_number ?? 'N/A',
                'recorded_days' => $recordedDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'late_days' => $lateDays,
                'attendance_percentage' => $attendancePercentage,
                'daily_status' => $dailyStatus,
                // Passing only the required Tailwind background class
                'percentage_class' => $percentageClass, 
            ];
        });

        // Fetch settings from the database
        $setting = Setting::first(); // Assuming a single row for global settings
        // Fallback setting (kept for robustness)
        if (!$setting) {
             // ... Fallback creation logic ...
        }

        // --- LOGIC ADDED: School Logo Handling (Base64 Encoding) ---
        $schoolLogoUrl = null;
        if ($setting && !empty($setting->school_logo)) {
            // Assuming base_path() is correct for your asset structure, 
            // adjust if you are using storage_path() or public_path()
            $imagePath = base_path($setting->school_logo); 
            
            if (file_exists($imagePath) && is_readable($imagePath)) {
                try {
                    $mimeType = mime_content_type($imagePath);
                    if (in_array($mimeType, ['image/png', 'image/jpeg', 'image/jpg'])) {
                        $imageContents = file_get_contents($imagePath);
                        $schoolLogoUrl = 'data:' . $mimeType . ';base64,' . base64_encode($imageContents);
                    }
                } catch (\Exception $e) {
                    \Log::error('PDF LOGO CHECK: Base64 encoding failed for student report: ' . $e->getMessage(), ['path' => $imagePath]);
                }
            }
        }
        // --- END LOGIC ADDED ---

        $pdf = Pdf::loadView('pdfs.attendance-report', [
            'reportData' => $reportData,
            'class' => $class,
            'session' => $session,
            'section' => $section,
            'group' => $group,
            'startDate' => Carbon::parse($startDate)->format('F d, Y'), // Formatting dates for display
            'endDate' => Carbon::parse($endDate)->format('F d, Y'),     // Formatting dates for display
            'setting' => $setting,
            'schoolLogoUrl' => $schoolLogoUrl, // <-- ❗ Pass the logo URL to the view
        ]);

        return $pdf->download('attendance-report-' . $startDate . '-to-' . $endDate . '.pdf');
    }


    // Teacher Attendance Index
    public function index(Request $request)
    {
        // Get the requested date or default to today
        $date = $request->input('date', today()->toDateString());

        // Fetch all teachers with their attendance status for the specific date
        $teachers = Teacher::with([
            'attendances' => function ($query) use ($date) {
                $query->where('date', $date);
            }
        ])
        ->get()
        ->map(function ($teacher) use ($date) {
            // Check if an attendance record exists for the teacher on the given date
            $attendance = $teacher->attendances->first();

            return [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'joining_number' => $teacher->joining_number,
                'date' => $date,
                'attendance_id' => optional($attendance)->id, // Attendance record ID if it exists
                'status' => optional($attendance)->status ?? 'Absent', // Default to present if no record
                'in_time' => optional($attendance)->in_time,
                'out_time' => optional($attendance)->out_time,
                'note' => optional($attendance)->note,
            ];
        });

        return Inertia::render('PresenceTracker/TeacherAttendanceIndex', [
            'teachers' => $teachers,
            'currentDate' => $date,
        ]);
    }

    
    /**
     * Store a newly created resource or update an existing one in storage.
    */
    public function store(Request $request)
    {
        // Define the current date string for validation comparison
        $today = now()->format('Y-m-d'); 

        // 1. Validate the incoming data
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            
            // 🚨 CRITICAL CHANGE: Only allow TODAY's date.
            'date' => [
                'required', 
                'date', 
                'before_or_equal:' . $today, // Prevents future dates
                'after_or_equal:' . $today,  // Prevents past dates
            ],
            
            'status' => 'required|in:Present,Absent,Leave,Half Day',
            
            'in_time' => 'nullable|date_format:H:i', 
            'out_time' => 'nullable|date_format:H:i|after:in_time', 
            
            'note' => 'nullable|string|max:500',
        ], [
            // Custom message for both future and past rejection (since both rules rely on :today)
            'date.before_or_equal' => 'পূর্ববর্তী তারিখ সংরক্ষিত হবে না।',
            'date.after_or_equal' => ' অতীতের তারিখ সংরক্ষিত হবে না।',
        ]);

        // 2. Add seconds (if needed) and Find or Create the attendance record
        $inTime = $validated['in_time'] ? $validated['in_time'] . ':00' : null;
        $outTime = $validated['out_time'] ? $validated['out_time'] . ':00' : null;

        TeacherAttendance::updateOrCreate(
            [
                'teacher_id' => $validated['teacher_id'],
                'date' => $validated['date'],
            ],
            [
                'status' => $validated['status'],
                'in_time' => $inTime,
                'out_time' => $outTime,
                'note' => $validated['note'],
            ]
        );

        // 3. Redirect back with a success flash message
        return back()->with('success', 'Teacher attendance saved successfully.');
    }

    

    //   Update an existing attendance record
    public function update(Request $request, TeacherAttendance $teacherAttendance)
    {
        // Define the current date string for validation comparison
        $today = now()->format('Y-m-d'); 

        // 1. Validation
        $validated = $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            
            // 🚨 CRITICAL CHANGE: Only allow TODAY's date.
            'date' => [
                'required', 
                'date', 
                'before_or_equal:' . $today, // Prevents future dates
                'after_or_equal:' . $today,  // Prevents past dates
            ],
            
            'status' => 'required|in:Present,Absent,Leave,Half Day',
            
            'in_time' => 'nullable|date_format:H:i',
            'out_time' => 'nullable|date_format:H:i|after:in_time',
            
            'note' => 'nullable|string|max:500',
        ], [
            // Custom message for both future and past rejection
            'date.before_or_equal' => 'ভবিষ্যতের তারিখে উপস্থিতি আপডেট করা যাবে না। তারিখটি অবশ্যই আজকের (' . $today . ') হতে হবে।',
            'date.after_or_equal' => 'অতীতে উপস্থিতি রেকর্ড বা আপডেট করা যাবে না। তারিখটি অবশ্যই আজকের (' . $today . ') হতে হবে।',
        ]);
        
        // 2. Perform Update with appended seconds (if necessary)
        $validated['in_time'] = $validated['in_time'] ? $validated['in_time'] . ':00' : null;
        $validated['out_time'] = $validated['out_time'] ? $validated['out_time'] . ':00' : null;

        $teacherAttendance->update($validated);

        // 3. Redirect
        return redirect()->route('attendance.teachers.index')->with('success', 'Attendance record updated successfully.');
    }


    /**
     * Generate a PDF report for teacher attendance.
    */

    public function printRangePdf(Request $request)
    {
        // Ensure sufficient resources for PDF generation
        ini_set('memory_limit', '512M');
        set_time_limit(300);

        // ... (Validation remains the same)
        $request->validate([
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // 1. Fetch the Settings Record and initialize logo variable
        $setting = Setting::first(); 
        $schoolLogoUrl = null;

        // --- LOGIC ADDED: School Logo Handling (Base64 Encoding) ---
        if ($setting && !empty($setting->school_logo)) {
            // Assuming the logo path is relative from the base path or public path
            $imagePath = public_path($setting->school_logo); // Adjusted to public_path for common use

            if (file_exists($imagePath) && is_readable($imagePath)) {
                try {
                    $mimeType = mime_content_type($imagePath);
                    if (in_array($mimeType, ['image/png', 'image/jpeg', 'image/jpg'])) {
                        $imageContents = file_get_contents($imagePath);
                        $schoolLogoUrl = 'data:' . $mimeType . ';base64,' . base64_encode($imageContents);
                    }
                } catch (\Exception $e) {
                    \Log::error('PDF LOGO CHECK: Base64 encoding failed: ' . $e->getMessage());
                }
            }
        }
        // --- END LOGIC ADDED ---

        // 2. Fetch all relevant attendance records (existing logic)
        $attendanceRecords = TeacherAttendance::with('teacher')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('teacher_id')
            ->get();
        
        // 3. Group the records by date (existing logic)
        $groupedAttendance = $attendanceRecords->groupBy(function ($item) {
            return Carbon::parse($item->date)->format('Y-m-d');
        });
        
        // 4. Fetch all teachers (existing logic)
        $allTeachers = Teacher::select('id', 'name', 'joining_number')->get()->keyBy('id');

        // 5. Load the dedicated PDF Blade view
        $pdf = Pdf::loadView('pdfs.teacher_attendance_report', [
            'groupedAttendance' => $groupedAttendance,
            'allTeachers' => $allTeachers,
            'startDate' => Carbon::parse($startDate)->format('F d, Y'),
            'endDate' => Carbon::parse($endDate)->format('F d, Y'),
            'reportGenerated' => now()->format('Y-m-d H:i:s'),
            'setting' => $setting,
            'schoolLogoUrl' => $schoolLogoUrl, // ❗ NEW: Pass the Base64 logo URL
        ]);
        
        $fileName = 'Teacher-Attendance-Report-' . $startDate . '-to-' . $endDate . '.pdf';

        return $pdf->download($fileName);
    }



    


}

