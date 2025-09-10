<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\GradeConfigure;
use App\Models\ExamResult;
use App\Models\Subject; 
use App\Models\ClassSession; 
use App\Models\Section;
use App\Models\Group;
use App\Models\Mark; // Your marks table
use App\Models\Student;
use App\Models\ClassName; 
use App\Models\Exam;
use App\Models\Setting;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; 
use Illuminate\Support\Facades\Storage;
class ResultController extends Controller
{
    public function index()
    {
        $gradeConfigurations = GradeConfigure::orderBy('grade_point', 'desc')->get();

        return Inertia::render('GradeConfiguration/Index', [
            'gradeConfigurations' => $gradeConfigurations,
            'flash' => session('flash'),
        ]);
    }

    /**
     * Show the form for creating a new grade configuration.
    */
    public function create()
    {
        return Inertia::render('GradeConfiguration/Create', [
            'flash' => session('flash'),
        ]);
    }

    /**
     * Store a newly created grade configuration in storage.
    */
    public function store(Request $request)
    {
        $request->validate([
            'class_interval' => ['required', 'string', 'max:20', 'unique:grade_configures,class_interval'],
            'letter_grade' => ['required', 'string', 'max:5', 'unique:grade_configures,letter_grade'],
            'grade_point' => ['required', 'numeric', 'min:0', 'max:5', 'unique:grade_configures,grade_point'],
            'status' => ['required', 'boolean'],
        ]);

        GradeConfigure::create($request->all()); 

        return redirect()->route('grade-configurations.index')->with('flash', ['success' => 'Grade configuration created successfully!']);
    }
 
    /**
     * Show the form for editing the specified grade configuration.
    */
    public function edit(GradeConfigure $gradeConfiguration) 
    {
        return Inertia::render('GradeConfiguration/Edit', [
            'gradeConfiguration' => $gradeConfiguration,
            'flash' => session('flash'),
        ]);
    }

    /**
     * Update the specified grade configuration in storage.
    */
    public function update(Request $request, GradeConfigure $gradeConfiguration) 
    {
        $request->validate([
            'class_interval' => ['required', 'string', 'max:20', Rule::unique('grade_configures')->ignore($gradeConfiguration->id)],
            'letter_grade' => ['required', 'string', 'max:5', Rule::unique('grade_configures')->ignore($gradeConfiguration->id)],
            'grade_point' => ['required', 'numeric', 'min:0', 'max:5', Rule::unique('grade_configures')->ignore($gradeConfiguration->id)],
            'status' => ['required', 'boolean'],
        ]);

        $gradeConfiguration->update($request->all());

        return redirect()->route('grade-configurations.index')->with('flash', ['success' => 'Grade configuration updated successfully!']);
    }

    /**
     * Remove the specified grade configuration from storage.
    */
    public function destroy(GradeConfigure $gradeConfiguration) // ✨ CORRECTED: Type-hinted with GradeConfigure ✨
    {
        $gradeConfiguration->delete();

        return redirect()->route('grade-configurations.index')->with('flash', ['success' => 'Grade configuration deleted successfully!']);
    }







    /**
     * Display the result filtering form and the results table.
    */
    public function showResults(Request $request)
    {
        // Get selected filter values from the request
        $selectedSessionId = $request->input('session_id');
        $selectedClassId = $request->input('class_id');
        $selectedSectionId = $request->input('section_id');
        $selectedGroupId = $request->input('group_id');
        $selectedExamId = $request->input('exam_id');

        // Initialize data containers
        $students = collect();
        $allSubjects = collect();
        $examDetails = null;
        $studentResultsData = [];
        $initialMessage = ['text' => 'Please select all required filters to view results.', 'type' => 'info'];

        // Fetch dropdown data
        $sessions = ClassSession::all();
        $classes = ClassName::all();
        $sections = Section::all();
        $groups = Group::all();
        $exams = collect(); // Will be filtered by session if selected

        // If a session is selected, fetch exams for that session
        if ($selectedSessionId) {
            $exams = Exam::where('session_id', $selectedSessionId)->get();
        }

        // --- Logic to fetch and prepare results only if all required filters are selected ---
        if ($selectedSessionId && $selectedClassId && $selectedExamId) {
            $initialMessage = null; // Clear initial message as filters are selected

            $examDetails = Exam::find($selectedExamId);

            // Fetch students based on selected filters, eager load their associated details
            $studentsQuery = Student::with(['className', 'section', 'group', 'session'])
                ->where('session_id', $selectedSessionId)
                ->where('class_id', $selectedClassId);

            if ($selectedSectionId) {
                $studentsQuery->where('section_id', $selectedSectionId);
            }
            if ($selectedGroupId) {
                $studentsQuery->where('group_id', $selectedGroupId);
            }

            $students = $studentsQuery->get();

            // --- CORRECTED LOGIC FOR FETCHING ALL SUBJECTS FOR THE SELECTED CLASS/SESSION/SECTION ---
            $allSubjectsQuery = Subject::query();

            $allSubjects = $allSubjectsQuery->join('class_subjects', 'subjects.id', '=', 'class_subjects.subject_id')
                ->where('class_subjects.class_name_id', $selectedClassId)
                ->where('class_subjects.session_id', $selectedSessionId)
                ->when($selectedSectionId, function ($query) use ($selectedSectionId) {
                    $query->where('class_subjects.section_id', $selectedSectionId);
                })
                ->select('subjects.*')
                ->distinct()
                ->get();

            // Fetch active grading scale configurations once for efficiency
            $gradingScale = GradeConfigure::where('status', 0)->orderBy('grade_point', 'desc')->get();

            // Prepare results for each student
            foreach ($students as $student) {
                // Fetch all marks for the current student and selected exam, with eager loaded exam and subject
                $studentMarks = Mark::where('student_id', $student->id)
                    ->where('exam_id', $selectedExamId)
                    ->with(['exam', 'subject'])
                    ->get();

                $subjectDetails = [];
                $overallTotalObtained = 0;
                $overallTotalPossible = 0;
                $overallGradePointsSum = 0;
                $subjectsCountedForGPA = 0;
                $overallPassStatus = true; // Initialize to true, will be set to false if any subject fails or is missing marks

                // Iterate through ALL subjects for the class (from the correctly fetched $allSubjects)
                foreach ($allSubjects as $subject) {
                    $mark = $studentMarks->firstWhere('subject_id', $subject->id);

                    // If no mark entry found for this subject, default to null/N/A and mark as overall fail
                    $marksObtained = $mark ? $mark->total_subject_marks_obtained : null;
                    $percentage = $mark ? $mark->subject_percentage : 0;
                    $letterGrade = $mark ? $mark->subject_letter_grade : 'N/A';
                    $gradePoint = $mark ? $mark->subject_grade_point : 0.0;
                    $passStatus = $mark ? $mark->subject_pass_status : 'N/A'; // From Mark model accessor

                    $subjectDetails[] = [
                        'subject_name' => $subject->name,
                        'marks_obtained' => $marksObtained,
                        'total_marks' => $examDetails ? $examDetails->total_marks : 0,
                        'passing_marks' => $examDetails ? $examDetails->passing_marks : 0,
                        'percentage' => round($percentage, 2),
                        'letter_grade' => $letterGrade,
                        'grade_point' => round($gradePoint, 2),
                        'pass_status' => $passStatus,
                    ];

                    // Logic to determine overall pass/fail based on individual subjects
                    if ($marksObtained !== null) {
                        $overallTotalObtained += $marksObtained;
                        $overallTotalPossible += ($examDetails ? $examDetails->total_marks : 0);
                        $overallGradePointsSum += $gradePoint;
                        $subjectsCountedForGPA++;

                        // *** CRITICAL CHANGE HERE ***
                        // If individual subject's passStatus is 'Fail' OR if its letter grade is 'F'
                        if ($passStatus === 'Fail' || $letterGrade === 'F') {
                            $overallPassStatus = false; // Mark overall status as Fail
                        }
                    } else {
                        // If marks are missing for a subject, it also counts as a fail for overall status
                        $overallPassStatus = false;
                    }
                }

                $overallPercentage = ($overallTotalPossible > 0) ? ($overallTotalObtained / $overallTotalPossible) * 100 : 0;
                // REMOVE OR COMMENT OUT THIS LINE IF you want overallGPA to be based on overallPercentage:
                // $overallGPA = ($subjectsCountedForGPA > 0) ? ($overallGradePointsSum / $subjectsCountedForGPA) : 0.0;
                $finalOverallStatus = $overallPassStatus ? 'Pass' : 'Fail';

                $finalOverallLetterGrade = 'N/A';
                $overallGPA = 0.0; // Initialize overallGPA for the new logic

                if ($finalOverallStatus === 'Fail') {
                    $overallGPA = 0.0;
                    $finalOverallLetterGrade = 'F';
                } else {
                    // If the student passed all subjects, determine overall letter grade and GPA based on overall percentage.
                    foreach ($gradingScale as $gradeConfig) {
                        list($min, $max) = explode('-', $gradeConfig->class_interval);
                        if ($overallPercentage >= (float)$min && $overallPercentage <= (float)$max) {
                            $finalOverallLetterGrade = $gradeConfig->letter_grade;
                            // NEW LINE: Set overallGPA based on the grade config for the overall percentage
                            $overallGPA = $gradeConfig->grade_point;
                            break;
                        }
                    }
                }
                // --- END ADJUSTED LOGIC ---

                $studentResultsData[] = [
                    'student' => $student,
                    'subjects_data' => $subjectDetails,
                    'overall_total_obtained' => $overallTotalObtained,
                    'overall_total_possible' => $overallTotalPossible,
                    'overall_percentage' => round($overallPercentage, 2),
                    'overall_gpa' => round($overallGPA, 2),
                    'overall_status' => $finalOverallStatus,
                    'overall_letter_grade' => $finalOverallLetterGrade, // Add this to your data
                ];
            }
        }

        return Inertia::render('Result/ShowResults', [
            'sessions' => $sessions,
            'classes' => $classes,
            'sections' => $sections,
            'groups' => $groups,
            'exams' => $exams,
            'selected' => [
                'session_id' => (int)$selectedSessionId,
                'class_id' => (int)$selectedClassId,
                'section_id' => $selectedSectionId ? (int)$selectedSectionId : null,
                'group_id' => $selectedGroupId ? (int)$selectedGroupId : null,
                'exam_id' => (int)$selectedExamId,
            ],
            'examDetails' => $examDetails,
            'studentResultsData' => $studentResultsData,
            'allSubjects' => $allSubjects,
            'initialMessage' => $initialMessage,
            'flash' => session('flash'),
        ]);
    }


    /**
     * Calculate and store the overall results for an exam into final_exam_results table.
     * This method is typically called by an admin to finalize results.
    */

    public function storeExamResults(Request $request)
    {
        Log::info('storeExamResults method hit!'); // Log 1: Confirm controller entry
        Log::info('Full Request Data:', $request->all()); // Log 2: See all incoming data

        // 1. Validate the incoming request data
        try {
            $validatedData = $request->validate([
                'exam_id' => ['required', 'exists:exams,id'],
                'session_id' => ['required', 'exists:class_sessions,id'],
                'class_id' => ['required', 'exists:class_names,id'],
                'section_id' => ['nullable', 'exists:sections,id'],
                'group_id' => ['nullable', 'exists:groups,id'],
                'results' => ['required', 'array'],
                'results.*.student_id' => ['required', 'exists:students,id'],
                'results.*.total_marks_obtained' => ['required', 'numeric', 'min:0'],
                'results.*.total_possible_marks' => ['required', 'numeric', 'min:1'],
                'results.*.percentage' => ['required', 'numeric', 'min:0', 'max:100'],
                'results.*.final_grade_point' => ['required', 'numeric', 'min:0'],
                'results.*.final_letter_grade' => ['required', 'string', 'max:10'],
                'results.*.overall_status' => ['required', 'string', 'in:Pass,Fail'],
                'results.*.subject_wise_data' => ['required', 'array'],
                'results.*.exam_result_id' => ['nullable', 'exists:exam_results,id'],
            ]);
            Log::info('Validation successful. Validated data:', $validatedData); // Log 3: Data after successful validation
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed in storeExamResults: ' . $e->getMessage(), ['errors' => $e->errors(), 'request_data' => $request->all()]); // Log 4: Detailed validation errors
            // Redirect back with errors for Inertia to catch
            return redirect()->back()->withErrors($e->errors())->withInput(); // Keep input in case of validation failure
        }


        try {
            DB::beginTransaction();
            Log::info('Transaction started.'); // Log 5: Confirm transaction start

            foreach ($validatedData['results'] as $index => $result) {
                // Ensure section_id and group_id are null if empty strings from frontend
                $sectionId = $validatedData['section_id'] ?? null;
                $groupId = $validatedData['group_id'] ?? null;

                Log::info("Processing result #$index for student_id: " . ($result['student_id'] ?? 'N/A'), ['data' => $result]); // Log 6: Per-student data

                // Correcting the 'id' key for updateOrCreate
                // The 'id' in the first array of updateOrCreate is for the primary key match.
                // If exam_result_id is provided, use it for 'id'. Otherwise, it's a creation.
                $matchCriteria = [
                    'exam_id' => $validatedData['exam_id'],
                    'session_id' => $validatedData['session_id'],
                    'class_id' => $validatedData['class_id'],
                    'student_id' => $result['student_id'],
                    'section_id' => $sectionId,
                    'group_id' => $groupId,
                ];

                if (isset($result['exam_result_id']) && $result['exam_result_id']) {
                    $matchCriteria['id'] = $result['exam_result_id'];
                }

                $examResult = ExamResult::updateOrCreate(
                    $matchCriteria, // This is the 'attributes' array for finding/creating
                    [
                        'total_marks_obtained' => $result['total_marks_obtained'],
                        'total_possible_marks' => $result['total_possible_marks'],
                        'percentage' => $result['percentage'],
                        'final_grade_point' => $result['final_grade_point'],
                        'final_letter_grade' => $result['final_letter_grade'],
                        'overall_status' => $result['overall_status'],
                        'subject_wise_data' => json_encode($result['subject_wise_data']), // Store as JSON string
                        'published_at' => now(), // Mark as published when stored
                    ]
                );
                Log::info('ExamResult upserted. ID:', ['id' => $examResult->id, 'action' => $examResult->wasRecentlyCreated ? 'created' : 'updated']); // Log 7: Confirm upsert for each record
            }

            DB::commit();
            Log::info('Transaction committed successfully.'); // Log 8: Confirm transaction commit

            return redirect()->back()->with('flash', [
                'success' => 'Exam results have been successfully stored/updated.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to store exam results: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]); // Log 9: Catch-all error for database operations
            return redirect()->back()->with('flash', [
                'error' => 'An error occurred while storing results: ' . $e->getMessage()
            ])->withErrors(['results_save' => 'An unexpected error occurred. Please try again.']);
        }
    }


    /**
     * Generate and download a PDF result sheet for a specific student and exam.
    */
    public function downloadResultPdf(Student $student, Exam $exam)
    {
        $settings = Setting::first();

        // Load relations for student and exam
        $student = $student->load(['className', 'section', 'group', 'session']);
        $examDetails = $exam->load('session');

        // Fetch student marks with relationships
        $studentMarks = Mark::where('student_id', $student->id)
            ->where('exam_id', $exam->id)
            ->with(['exam', 'subject', 'classSubjectDetail'])
            ->get();

        // Fetch all subjects for the student's class/session/section/group
        $allSubjectsInClass = Subject::join('class_subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->where('class_subjects.class_name_id', $student->class_id)
            ->where('class_subjects.session_id', $student->session_id)
            ->when($student->section_id, fn($q) => $q->where('class_subjects.section_id', $student->section_id))
            ->when($student->group_id, fn($q) => $q->where('class_subjects.group_id', $student->group_id))
            ->select('subjects.*')
            ->distinct()
            ->get();

        // Initialize accumulators and arrays
        $subjectDetails = [];
        $overallTotalObtained = 0;
        $overallTotalPossible = 0;
        $overallGradePointsSum = 0;
        $subjectsCountedForGPA = 0;
        $overallPassStatus = true;

        $gradingScale = GradeConfigure::where('status', 0)->orderBy('grade_point', 'desc')->get();

        foreach ($allSubjectsInClass as $subject) {
            $mark = $studentMarks->firstWhere('subject_id', $subject->id);

            $marksObtained = $mark ? $mark->total_subject_marks_obtained : null;
            $percentage = $mark ? $mark->subject_percentage : 0;
            $letterGrade = $mark ? $mark->subject_letter_grade : 'N/A';
            $gradePoint = $mark ? $mark->subject_grade_point : 0.0;
            $passStatus = $mark ? $mark->subject_pass_status : 'N/A';

            $individualSubjectTotal = ($mark && $mark->classSubjectDetail) ? $mark->classSubjectDetail->total_subject_marks : 0;
            $individualSubjectPassingMarks = ($mark && $mark->exam) ? $mark->exam->passing_marks : 0;

            $subjectDetails[] = [
                'subject_name'      => $subject->name,
                'marks_obtained'    => $marksObtained,
                'total_marks'       => $individualSubjectTotal,
                'passing_marks'     => $individualSubjectPassingMarks,
                'percentage'        => round($percentage, 2),
                'letter_grade'      => $letterGrade,
                'grade_point'       => round($gradePoint, 2),
                'pass_status'       => $passStatus,
            ];

            if ($marksObtained !== null) {
                $overallTotalObtained += $marksObtained;
                $overallTotalPossible += $individualSubjectTotal;
                $overallGradePointsSum += $gradePoint;
                $subjectsCountedForGPA++;
                if ($passStatus === 'Fail' || $letterGrade === 'F') {
                    $overallPassStatus = false;
                }
            } else {
                $overallPassStatus = false;
            }
        }

        // Calculate overall summary
        $overallPercentage = $overallTotalPossible > 0
            ? ($overallTotalObtained / $overallTotalPossible) * 100
            : 0;

        $overallGPA = $subjectsCountedForGPA > 0 ? ($overallGradePointsSum / $subjectsCountedForGPA) : 0.0;
        $finalOverallStatus = $overallPassStatus ? 'Pass' : 'Fail';

        $finalOverallLetterGrade = 'N/A';
        if ($finalOverallStatus === 'Fail') {
            $overallGPA = 0.0;
            $finalOverallLetterGrade = 'F';
        } else {
            foreach ($gradingScale as $gc) {
                [$min, $max] = explode('-', $gc->class_interval);
                if ($overallPercentage >= (float)$min && $overallPercentage <= (float)$max) {
                    $finalOverallLetterGrade = $gc->letter_grade;
                    break;
                }
            }
        }

        // Prepare branding & images
        $schoolName = $settings->school_name === 'Blue-Bird' ? '' : $settings->school_name; // hide Blue-Bird if you want
        $principalName = $settings->principal_name ?? null;

        $schoolLogoUrl = null;
        if (!empty($settings?->school_logo)) {
            $path = ltrim($settings->school_logo, '/');
            if (Storage::disk('public')->exists($path)) {
                $schoolLogoUrl = asset('storage/' . $path);
            }
        }

        $principalSignatureUrl = null;
        if (!empty($settings?->principal_signature)) {
            $path = ltrim($settings->principal_signature, '/');
            if (Storage::disk('public')->exists($path)) {
                $principalSignatureUrl = asset('storage/' . $path);
            }
        }

        // Data payload to Blade
        $data = [
            'student'               => $student,
            'exam'                  => $examDetails,
            'subjectDetails'        => $subjectDetails,
            'overallTotalObtained'  => $overallTotalObtained,
            'overallTotalPossible'  => $overallTotalPossible,
            'overallPercentage'     => round($overallPercentage, 2),
            'overallGPA'            => round($overallGPA, 2),
            'overallStatus'         => $finalOverallStatus,
            'overallLetterGrade'    => $finalOverallLetterGrade,
            'schoolName'            => $schoolName,
            'principalName'         => $principalName,
            'schoolLogoUrl'         => $schoolLogoUrl,
            'principalSignatureUrl' => $principalSignatureUrl,
        ];

        // Generate PDF with remote assets enabled
        $pdf = PDF::setOptions(['isRemoteEnabled' => true])
            ->loadView('Result.pdf', $data);

        return $pdf->download(
            "{$student->first_name}_{$student->last_name}_Result_{$examDetails->exam_name}_" .
            ($student->admission_number ?? $student->roll_no) . ".pdf"
        );
    }






















    // Student Side To Show His Exam Result


    public function studentResultIndex(Request $request)
    {
        Log::info('--- studentResultIndex method started (Attempting to show results) ---');

        $userId = Auth::id();
        Log::info('studentResultIndex: Current authenticated User ID (Auth::id()): ' . $userId);

        if (empty($userId)) {
            Log::warning('studentResultIndex: No authenticated User ID found.');
            return Inertia::render('Student/Results/Index', ['examResults' => [], 'error' => 'Authentication error. Please log in again.']);
        }

        $student = Student::where('user_id', $userId)->first();

        if (!$student) {
            Log::warning('studentResultIndex: No Student record found for User ID: ' . $userId);
            return Inertia::render('Student/Results/Index', ['examResults' => [], 'message' => 'Your student profile could not be found.']);
        }

        $studentId = $student->id;
        Log::info('studentResultIndex: Found corresponding Student ID: ' . $studentId . ' for User ID: ' . $userId);

        try {
            $examResults = ExamResult::where('student_id', $studentId)
                // --- FIX 1: Change 'class' to 'className' ---
                ->with(['exam', 'className', 'session', 'section', 'group'])
                ->orderBy('published_at', 'desc')
                ->get();

            Log::info('studentResultIndex: Found ' . $examResults->count() . ' exam results for student ID: ' . $studentId);

            if ($examResults->isEmpty()) {
                Log::info('studentResultIndex: No exam results found for student ID: ' . $studentId . ' in the database.');
                return Inertia::render('Student/Results/Index', ['examResults' => [], 'message' => 'No exam results found for you.']);
            }

        } catch (\Exception $e) {
            Log::error('studentResultIndex: Database query or relationship loading error: ' . $e->getMessage(), [
                'student_id' => $studentId,
                'exception' => $e->getTraceAsString()
            ]);
            return Inertia::render('Student/Results/Index', ['examResults' => [], 'error' => 'Failed to retrieve results due to a server error.']);
        }

        $mappedExamResults = [];
        foreach ($examResults as $result) {
            // --- FIX 2: Change $result->class->name to $result->className->name ---
            $examName = optional($result->exam)->exam_name;
            $className = optional($result->className)->class_name; // Use className
            $sessionName = optional($result->session)->name;
            $sectionName = optional($result->section)->name;
            $groupName = optional($result->group)->name;

            if (empty($examName) || empty($className) || empty($sessionName)) {
                Log::warning('studentResultIndex: Result ID ' . $result->id . ' has missing critical relationship data (Exam, Class, or Session name).');
            }

            $mappedExamResults[] = [
                'id' => $result->id,
                'exam_name' => $examName ?? 'N/A',
                'class_name' => $className ?? 'N/A', // Use className
                'session_name' => $sessionName ?? 'N/A',
                'section_name' => $sectionName ?? 'N/A',
                'group_name' => $groupName ?? 'N/A',
                'total_marks_obtained' => $result->total_marks_obtained,
                'percentage' => $result->percentage,
                'overall_letter_grade' => $result->overall_letter_grade,
                'overall_status' => $result->overall_status,
                'published_at' => $result->published_at?->format('Y-m-d H:i') ?? 'Not Published',
                'can_download_pdf' => !empty($result->pdf_file_path) && Storage::exists($result->pdf_file_path),
                'student_id' => $result->student_id,
                'exam_id' => $result->exam_id,
            ];
        }

        Log::info('studentResultIndex: Total mapped results prepared for frontend: ' . count($mappedExamResults));
        Log::info('--- studentResultIndex method finished ---');

        return Inertia::render('Student/Results/Index', [
            'examResults' => $mappedExamResults,
        ]);
    }

    public function show(ExamResult $examResult)
    {
        $userId = Auth::id();

        if (empty($userId)) {
            abort(403, 'Authentication required to view results.');
        }

        $student = Student::where('user_id', $userId)->first();

        if (!$student || $student->id !== $examResult->student_id) {
            Log::warning('Unauthorized access attempt for ExamResult ID: ' . $examResult->id . ' by User ID ' . $userId . ' (Student ID: ' . optional($student)->id . ' vs Result Student ID: ' . $examResult->student_id . ')');
            abort(403, 'Unauthorized action. You can only view your own results.');
        }

        // --- FIX 3: Change 'class' to 'className' in load() ---
        $examResult->load(['exam', 'student', 'className', 'session', 'section', 'group']);
        $subjectWiseData = json_decode($examResult->subject_wise_data, true);

        return Inertia::render('Student/Results/Show', [
            'examResult' => [
                'id' => $examResult->id,
                'exam_name' => optional($examResult->exam)->exam_name ?? 'N/A',
                'student_name' => optional($examResult->student)->name ?? 'N/A',
                'class_name' => optional($examResult->className)->class_name ?? 'N/A', // Use className
                'session_name' => optional($examResult->session)->name ?? 'N/A',
                'section_name' => optional($examResult->section)->name ?? 'N/A',
                'group_name' => optional($examResult->group)->name ?? 'N/A',
                'total_marks_obtained' => $examResult->total_marks_obtained,
                'total_subject_maximum_sum' => $examResult->total_subject_maximum_sum,
                'overall_percentage_base' => $examResult->overall_percentage_base,
                'percentage' => $examResult->percentage,
                'final_grade_point' => $examResult->final_grade_point,
                'final_letter_grade' => $examResult->final_letter_grade,
                'overall_status' => $examResult->overall_status,
                'published_at' => $examResult->published_at?->format('Y-m-d H:i') ?? 'Not Published',
                'subject_wise_data' => $subjectWiseData,
                'pdf_file_path' => $examResult->pdf_file_path,
                'can_download_pdf' => !empty($examResult->pdf_file_path) && Storage::exists($examResult->pdf_file_path),
            ],
        ]);
    }

    /**
     * Download the PDF result for a specific exam result record.
     * Accessible via: GET /student/results/{examResult}/download-pdf
     * Uses Route Model Binding (ExamResult $examResult)
    */
    public function downloadPdf(ExamResult $examResult)
    {
        // Authorization: Ensure the logged-in student can only download their own result
        if (Auth::id() !== $examResult->student_id) {
            abort(403, 'Unauthorized action. You can only download your own results.');
        }

        $filePath = $examResult->pdf_file_path; // <--- Potential Issue #1

        // Check if the file path exists in the database and the file actually exists on disk
        if (empty($filePath) || !Storage::exists($filePath)) { // <--- Potential Issue #2 & #3
            return redirect()->back()->with('flash', ['error' => 'PDF file not found or has not been generated yet.', 'type' => 'error']);
        }

        // Generate a user-friendly filename for the download
        $studentName = Str::slug($examResult->student->first_name . ' ' . $examResult->student->last_name); // <--- Potential Issue #4
        $examName = Str::slug($examResult->exam->name); // <--- Potential Issue #4
        $downloadFileName = "{$studentName}-{$examName}-Result.pdf";

        // Serve the file for download
        return Storage::download($filePath, $downloadFileName); // <--- Final download attempt
    }



}