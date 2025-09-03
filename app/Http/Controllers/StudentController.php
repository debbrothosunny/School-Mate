<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassName; 
use App\Models\ClassSession;
use App\Models\Section;
use App\Models\Group;
use App\Models\User; 
use App\Models\ExamResult; 
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Eager load relations including 'user' and paginate the students
        $students = Student::with(['className', 'session', 'group', 'section', 'user'])->latest()->paginate(10);

        return Inertia::render('Students/Index', [
            'students' => $students,
        ]);
    }

    /**
     * Show the form for creating a new student.
    */
    public function create()
    {
        $classes = ClassName::where('status', 0)->get(['id', 'class_name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $groups = Group::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        // Fetch users who are not yet linked to any student or teacher,
        // or just all users if you want more flexibility in linking.
        // For simplicity, fetching all users here.
        $availableUsers = User::doesntHave('student')
                                ->doesntHave('teacher')
                                ->get(['id', 'name', 'email']);

        return Inertia::render('Students/Create', [
            'classes' => $classes,
            'sessions' => $sessions,
            'groups' => $groups,
            'sections' => $sections,
            'availableUsers' => $availableUsers, // Pass available users to the view
        ]);
    }

    private function generateUniqueAdmissionNumber(): string
    {
        do {
            // Generate a random number between 100,000 (inclusive) and 999,999 (inclusive)
            $number = random_int(100000, 999999);
        } while (Student::where('admission_number', $number)->exists());

        return (string)$number;
    }
   
    /**
     * Store a newly created student in storage.
    */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:class_names,id',
            'age' => 'required|integer|min:0',
            'date_of_birth' => 'required|date',
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other'])],
            'admission_date' => 'required|date',
            'session_id' => 'required|exists:class_sessions,id',
            'group_id' => 'required|exists:groups,id',
            'section_id' => 'required|exists:sections,id',
            'user_id' => 'required|exists:users,id|unique:students,user_id',
            'parent_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'contact' => 'required|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|integer|in:0,1',
            'enrollment_status' => ['required', 'string', Rule::in(['applied', 'under_review', 'admitted', 'enrolled', 'rejected', 'waitlisted', 'withdrawn'])],
            'roll_number' => 'required|integer|unique:students,roll_number',
            'admission_fee_amount' => 'nullable|numeric|min:0',
            'admission_fee_paid' => 'boolean',
            'payment_method' => ['nullable', 'string', Rule::in(['Cash', 'bKash', 'Bank Transfer'])],
        ]);

        // Generate the unique admission number
        $validatedData['admission_number'] = $this->generateUniqueAdmissionNumber();

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('students', 'public');
            $validatedData['image'] = $imagePath;
        } else {
            $validatedData['image'] = null;
        }

        // Handle admission_fee_amount conversion
        if (isset($validatedData['admission_fee_amount'])) {
            $validatedData['admission_fee_amount'] = (int)($validatedData['admission_fee_amount'] * 100);
        } else {
            $validatedData['admission_fee_amount'] = null;
        }

        // Set default for admission_fee_paid
        if (!isset($validatedData['admission_fee_paid'])) {
            $validatedData['admission_fee_paid'] = false;
        }

        // Create the student using the validated data
        $student = Student::create($validatedData);

        return redirect()->route('students.index')->with('success', 'Student added successfully!');
    }

    /**
     * Show the form for editing the specified student.
    */
    public function edit(Student $student)
    {
        $classes = ClassName::where('status', 0)->get(['id', 'class_name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $groups = Group::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);

        // Fetch users who are not yet linked to any student or teacher,
        // OR the user currently linked to this student (to allow re-selecting the same user).
        $availableUsers = User::where(function ($query) {
                                $query->whereDoesntHave('student')
                                      ->whereDoesntHave('teacher');
                            })
                            ->orWhere('id', $student->user_id) // Include the student's current user
                            ->get(['id', 'name', 'email']);

        // Eager load relationships for the student being edited
        $student->load(['className', 'session', 'group', 'section', 'user']);

        return Inertia::render('Students/Edit', [
            'student' => $student,
            'classes' => $classes,
            'sessions' => $sessions,
            'groups' => $groups,
            'sections' => $sections,
            'availableUsers' => $availableUsers, // Pass available users to the view
            'flash' => session('flash'), // Ensure flash messages are passed
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[], // Ensure errors are passed
        ]);
    }

    /**
     * Update the specified student in storage.
    */
    public function update(Request $request, Student $student)
    {
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'class_id' => 'nullable|exists:class_names,id',
            'age' => 'nullable|integer|min:0',
            'date_of_birth' => 'nullable|date',
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other'])],
            'admission_date' => 'nullable|date',
            'session_id' => 'nullable|exists:class_sessions,id',
            'group_id' => 'nullable|exists:groups,id',
            'section_id' => 'nullable|exists:sections,id',

            // user_id unique rule should ignore the current student's user_id
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('students', 'user_id')->ignore($student->id),
            ],

            'parent_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'nullable|integer|in:0,1',
            'enrollment_status' => ['required', 'string', Rule::in(['applied', 'under_review', 'admitted', 'enrolled', 'rejected', 'waitlisted', 'withdrawn'])],

            // --- Validation for admission_number and roll_number ---
            'admission_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('students', 'admission_number')->ignore($student->id),
            ],
            'roll_number' => [
                'required',
                'integer',
                Rule::unique('students', 'roll_number')->ignore($student->id),
            ],

            // New validation rules for admission fee and payment details
            'admission_fee_amount' => 'nullable|numeric|min:0',
            'admission_fee_paid' => 'boolean',
            'payment_method' => ['nullable', 'string', Rule::in(['Cash', 'bKash', 'Bank Transfer'])],
        ]);

        // Handle image update
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($student->image) {
                Storage::disk('public')->delete($student->image);
            }
            $imagePath = $request->file('image')->store('students', 'public');
            $validatedData['image'] = $imagePath;
        } elseif ($request->input('image') === null) {
            // If the image field is explicitly set to null in the request payload
            // This happens if you have a "clear image" button or similar functionality.
            if ($student->image) {
                Storage::disk('public')->delete($student->image);
            }
            $validatedData['image'] = null;
        } else {
            // If no new image is uploaded and it's not explicitly set to null, keep the existing image
            // We don't need to do anything here since $validatedData does not contain 'image' in this case,
            // so the model will keep the old value if not updated.
            // If you want to explicitly ensure the old image path is preserved if no new upload,
            // you can do: $validatedData['image'] = $student->image;
            unset($validatedData['image']); // Remove image from validatedData if no new file and not nulling
                                            // This prevents overwriting with null if nothing changed.
        }

        // --- Handle admission_fee_amount conversion for update ---
        // If admission_fee_amount is provided, convert it to the smallest unit (paisa).
        if (isset($validatedData['admission_fee_amount']) && $validatedData['admission_fee_amount'] !== null) {
            $validatedData['admission_fee_amount'] = (int)($validatedData['admission_fee_amount'] * 100);
        } else {
            // Ensure it's null if no amount is provided, matching nullable() in migration
            $validatedData['admission_fee_amount'] = null;
        }

        // Set default for admission_fee_paid if not present (e.g., checkbox not checked)
        if (!isset($validatedData['admission_fee_paid'])) {
            $validatedData['admission_fee_paid'] = false;
        }

        $student->update($validatedData);

        // Redirect back to the index page without a flash message
        return redirect()->route('students.index')->with('success', 'Student updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
    */
    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()->route(route: 'students.index')->with('flash', [
            'message' => 'Student deleted successfully!',
            'type' => 'success'
        ]);
    }
    


    


   


    // New method to fetch students and fee structures based on academic criteria
    // public function AccountantStudentHistory()
    // {
    //     return Inertia::render('Accountant/StudentHistory/StudentHistory', [
    //         'classes' => ClassName::all(),
    //         'sessions' => ClassSession::all(),
    //         'sections' => Section::all(),
    //         'groups' => Group::all(),
    //         'groupedData' => [], // Start with an empty array for search results
    //         'errorMessage' => null,
    //         'feeStructures' => [],
    //     ]);
    // }

    
    // public function getAcademicData(Request $request)
    // {
    //     $validated = $request->validate([
    //         'class_id' => 'required|exists:class_names,id',
    //         'session_id' => 'required|exists:class_sessions,id',
    //         'section_id' => 'required|exists:sections,id',
    //         'group_id' => 'nullable|exists:groups,id',
    //     ]);
        
    //     $studentAcademicRecords = StudentAcademicRecord::where([
    //         'class_id' => $validated['class_id'],
    //         'session_id' => $validated['session_id'],
    //         'section_id' => $validated['section_id'],
    //     ])
    //     ->when($validated['group_id'], function ($query, $groupId) {
    //         return $query->where('group_id', $groupId);
    //     }, function ($query) {
    //         return $query->whereNull('group_id');
    //     })
    //     ->with('student:id,name,admission_number')
    //     ->get();

    //     $students = $studentAcademicRecords->pluck('student');

    //     $feeStructuresQuery = ClassFeeStructure::with('feeType')
    //         ->where('class_id', $validated['class_id'])
    //         ->where('session_id', $validated['session_id']);

    //     if ($validated['section_id']) {
    //         $feeStructuresQuery->where('section_id', $validated['section_id']);
    //     }

    //     if ($validated['group_id']) {
    //         $feeStructuresQuery->where('group_id', $validated['group_id']);
    //     } else {
    //         $feeStructuresQuery->whereNull('group_id');
    //     }
        
    //     $feeStructures = $feeStructuresQuery->get();
        
    //     return response()->json([
    //         'students' => $students,
    //         'fee_structures' => $feeStructures,
    //     ]);
    // }

    
    //  public function showStudentHistory(Request $request)
    // {
    //     // Get the validated data from the request, or default to null for the first load
    //     $validated = $request->validate([
    //         'class_id' => 'nullable|exists:class_names,id',
    //         'session_id' => 'nullable|exists:class_sessions,id',
    //         'section_id' => 'nullable|exists:sections,id',
    //         'group_id' => 'nullable|exists:groups,id',
    //     ]);
        
    //     $groupedData = collect([]);
    //     $feeStructures = collect([]);
    //     $errorMessage = null;

    //     // Only fetch student data if all required fields are present
    //     if (isset($validated['class_id']) && isset($validated['session_id']) && isset($validated['section_id'])) {
    //         try {
    //             // Step 1: Find student academic records that match the selected criteria
    //             $academicRecords = StudentAcademicRecord::where([
    //                 'class_id' => $validated['class_id'],
    //                 'session_id' => $validated['session_id'],
    //                 'section_id' => $validated['section_id'],
    //             ])
    //             ->when(isset($validated['group_id']), function ($query) use ($validated) {
    //                 return $query->where('group_id', $validated['group_id']);
    //             }, function ($query) {
    //                 return $query->whereNull('group_id');
    //             })
    //             ->with(['student:id,name,admission_number', 'className', 'session', 'section', 'group'])
    //             ->get();
        
    //             // Step 2: Get a list of all student IDs from the found records
    //             $studentIds = $academicRecords->pluck('student_id');
        
    //             // Step 3: Find all invoices for those students
    //             $invoices = Invoice::with('invoiceItems.feeType')
    //                              ->whereIn('student_id', $studentIds)
    //                              ->orderBy('due_date', 'asc')
    //                              ->get();
                                 
    //             // Step 4: Find all exam results for those students and their sessions
    //             $examResults = ExamResult::with(['exam', 'class', 'section', 'group'])
    //                                      ->whereIn('student_id', $studentIds)
    //                                      ->where('session_id', $validated['session_id'])
    //                                      ->get();
        
    //             // Step 5: Organize and combine the data for the frontend
    //             foreach ($academicRecords as $record) {
    //                 $studentId = $record->student_id;
                    
    //                 // Filter invoices and exam results to only those for the current student
    //                 $studentInvoices = $invoices->where('student_id', $studentId)->values();
    //                 $studentExamResults = $examResults->where('student_id', $studentId)->values();
        
    //                 $groupedData->push([
    //                     'student_id' => $studentId,
    //                     'student_name' => $record->student->name,
    //                     'academic_details' => [
    //                         'class_name' => $record->className->class_name,
    //                         'session_name' => $record->session->name,
    //                         'section_name' => $record->section->name,
    //                         'group_name' => $record->group ? $record->group->name : 'N/A',
    //                         'roll_number' => $record->roll_number,
    //                         'admission_number' => $record->student->admission_number, // Add admission number here
    //                     ],
    //                     'invoice_history' => $studentInvoices,
    //                     'exam_results' => $studentExamResults, // Add exam results here
    //                 ]);
    //             }
                
    //             // Step 6: Fetch fee structures based on the validated request data
    //             $feeStructuresQuery = ClassFeeStructure::with('feeType')
    //                 ->where('class_id', $validated['class_id'])
    //                 ->where('session_id', $validated['session_id']);

    //             if (isset($validated['section_id'])) {
    //                 $feeStructuresQuery->where('section_id', $validated['section_id']);
    //             }
                
    //             if (isset($validated['group_id'])) {
    //                 $feeStructuresQuery->where('group_id', $validated['group_id']);
    //             } else {
    //                 $feeStructuresQuery->whereNull('group_id');
    //             }
                
    //             $feeStructures = $feeStructuresQuery->get();
                
    //             // Set an error message if no students were found
    //             if ($groupedData->isEmpty()) {
    //                 $errorMessage = "No students found for this academic context.";
    //             }

    //         } catch (\Exception $e) {
    //             // If any part of the process fails, set a user-friendly error message
    //             $errorMessage = 'Failed to load student history. Please try again.';
    //             // Log the full error for your own debugging
    //             Log::error('Student history loading failed: ' . $e->getMessage());
    //         }
    //     }

    //     // Pass all necessary data directly to the Inertia page
    //     return Inertia::render('Accountant/StudentHistory/StudentHistory', [
    //         'classes' => ClassName::all(),
    //         'sessions' => ClassSession::all(),
    //         'sections' => Section::all(),
    //         'groups' => Group::all(),
    //         'groupedData' => $groupedData,
    //         'feeStructures' => $feeStructures,
    //         'errorMessage' => $errorMessage,
    //     ]);
    // }

    
    // public function storeAcademicRecord(Request $request)
    // {
    //     // Step 1: Validate the incoming request data
    //     // The 'roll_number' is not required as it's not being submitted from the form.
    //     $validated = $request->validate([
    //         'student_ids' => 'required|array',
    //         'student_ids.*' => 'exists:students,id', // Validate each ID in the array
    //         'class_id' => 'required|exists:class_names,id',
    //         'session_id' => 'required|exists:class_sessions,id',
    //         'section_id' => 'required|exists:sections,id',
    //         'group_id' => 'nullable|exists:groups,id',
    //     ]);

    //     $successCount = 0;
    //     $failedIds = [];

    //     // Step 2: Loop through each selected student and create a record
    //     foreach ($validated['student_ids'] as $studentId) {
    //         // Using updateOrCreate ensures we don't create a new record if one already exists
    //         // for this student in the same session, preventing database constraint errors.
    //         $record = StudentAcademicRecord::updateOrCreate(
    //             [
    //                 'student_id' => $studentId,
    //                 'session_id' => $validated['session_id'],
    //             ],
    //             [
    //                 'class_id' => $validated['class_id'],
    //                 'section_id' => $validated['section_id'],
    //                 'group_id' => $validated['group_id'],
    //                 // Roll number is left blank here, as your form doesn't provide it
    //                 'roll_number' => null, 
    //             ]
    //         );

    //         if ($record) {
    //             $successCount++;
    //         } else {
    //             $failedIds[] = $studentId;
    //         }
    //     }

    //     // Step 3: Redirect back with a status message based on the outcome
    //     $message = "Successfully stored {$successCount} academic record(s).";
    //     if (!empty($failedIds)) {
    //         $message .= " Failed to store records for the following student IDs: " . implode(', ', $failedIds);
    //     }

    //     return redirect()->back()->with('success', $message);
    // }





    // New method to show a specific student's exam and invoice history

    public function showStudentHistory($student_id)
    {
        // Find the student and eager-load their relationships.
        $student = Student::with([
            'examResults' => function ($query) {
                // Eager-load the exam, session, and className relationships.
                // We need to load the 'exam' relationship to get the exam name.
                $query->with(['exam', 'session', 'className'])
                    ->orderBy('session_id')
                    ->orderBy('class_id');
            },
            'invoices' => function ($query) {
                // Order invoices by issue date in descending order.
                $query->orderBy('issued_at', 'desc');
            }
        ])->find($student_id);

        // Handle the case where the student is not found.
        if (!$student) {
            abort(404, 'Student not found.');
        }

        // Prepare the data to be sent to the Vue component.
        $examHistory = collect($student->examResults)->map(function ($examResult) {
            // Return a new array with flattened data, ensuring relationships exist.
            return [
                'id' => $examResult->id,
                // Safely access the exam name from the new 'exam' relationship
                'exam_name' => optional($examResult->exam)->exam_name ?? 'N/A',
                'total_marks_obtained' => $examResult->total_marks_obtained,
                'final_letter_grade' => $examResult->final_letter_grade,
                'session_name' => optional($examResult->session)->name,
                'class_name' => optional($examResult->className)->class_name,
            ];
        });

        // Use Inertia to render the Vue component with the prepared data.
        return Inertia::render('Accountant/StudentHistory/StudentHistory', [
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
            ],
            'examHistory' => $examHistory,
            'invoiceHistory' => $student->invoices,
        ]);
    }





     /**
     * Display a list of all students who passed the final exam.
     */

    public function show(string $id)
    {
        // Find the student with their related class, session, and section data
        $student = Student::with(['className', 'session', 'section'])->find($id);

        // 1. Check if the student object is found
        // dd($student);

        if (!$student) {
            return Inertia::render('Result/PassedStudents', [
                'student' => null
            ]);
        }

        return Inertia::render('Result/PassedStudents', [
            'student' => $student,
        ]);
    }

    public function passedStudents()
    {
        // Fetch exam results with a 'Pass' status, eager loading the related student, class name, and session.
        $passedStudents = ExamResult::with(['student.className', 'student.section', 'student.session'])
            ->where('overall_status', 'Pass')
            ->get()
            ->map(function ($result) {
                // Map the results to a more convenient format for the frontend.
                return [
                    'id' => $result->student_id,
                    'name' => $result->student->name,
                    'className' => $result->student->className,
                    'session' => $result->student->session,
                    'section' => $result->student->section,
                    'overall_status' => $result->overall_status,
                ];
            });

        return Inertia::render('Result/PassedStudents', [
            'passedStudents' => $passedStudents,
        ]);
    }

    /**
     * Promote students who passed the final exam.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function promoteStudents()
    {
        try {
            DB::beginTransaction();

            // Find all exam results with a 'Pass' status, eager loading the student relationship.
            $passedResults = ExamResult::with('student')
                ->where('overall_status', 'Pass')
                ->get();

            // If no students passed, roll back and return an error message.
            if ($passedResults->isEmpty()) {
                DB::rollBack();
                return back()->with('error', 'No students to promote.');
            }

            // Loop through each passed exam result to promote the student.
            foreach ($passedResults as $result) {
                $student = $result->student;
                if ($student) {
                    // Find the next class and session for promotion based on the current IDs.
                    $currentClassId = $student->class_id; // Changed to match your migration
                    $currentSessionId = $student->session_id;

                    $nextClass = ClassName::where('id', '>', $currentClassId)->orderBy('id')->first();
                    $nextSession = ClassSession::where('id', '>', $currentSessionId)->orderBy('id')->first();

                    // If a next class is found, update the student's class ID.
                    if ($nextClass) {
                        $student->class_id = $nextClass->id; // Changed to match your migration
                    }

                    // If a next session is found, update the student's session ID.
                    if ($nextSession) {
                        $student->session_id = $nextSession->id;
                    }

                    // Update the student's overall status to 'Promoted' to prevent re-promotion.
                    $result->overall_status = 'Promoted';

                    // Save the changes to both the student and the exam result records.
                    $student->save();
                    $result->save();
                }
            }

            // If all updates are successful, commit the transaction.
            DB::commit();

            return back()->with('success', 'All passed students have been successfully promoted!');
        } catch (\Exception $e) {
            // If any error occurs, roll back the transaction and return an error message.
            DB::rollBack();
            return back()->with('error', 'Promotion failed: ' . $e->getMessage());
        }
    }





   
    


}