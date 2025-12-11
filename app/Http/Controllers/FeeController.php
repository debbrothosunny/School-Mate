<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassFeeStructure;
use App\Models\ClassName; 
use App\Models\ClassSession;
use App\Models\Group;
use App\Models\Section;
use App\Models\Setting;
use App\Models\User;
use App\FeeFrequency; 
use App\Notifications\InvoiceCreated;
use App\Notifications\StudentPaymentReceived;
use App\Models\StudentFeeAssignment;
use Illuminate\Validation\ValidationException;
use App\Models\FeeType;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf; // Import the DomPDF Facade
use Illuminate\Validation\Rule; // For unique validation
class FeeController extends Controller
{

    // Class Fee Structure Functions
    public function index()
    {
        // Fetch all class fee structures with their relationships for display
        $classFeeStructures = ClassFeeStructure::with('ClassName', 'session', 'group', 'section', 'feeType')
                                                ->orderBy('class_id')
                                                ->orderBy('session_id')
                                                ->orderBy('fee_type_id')
                                                ->paginate(10); // Or adjust pagination limit

        return Inertia::render('Accountant/ClassFeeStructures/Index', [
            'classFeeStructures' => $classFeeStructures,
            'flash' => session('flash'), // Pass flash messages
        ]);
    }

    /**
     * Show the form for creating a new class fee structure.
    */
    public function create()
    {
        // Fetch unique class names with the first corresponding id, sorted by class_name
        $classes = ClassName::where('status', 0)
            ->select('id', 'class_name')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('class_names')
                    ->where('status', 0)
                    ->groupBy('class_name');
            })
            ->orderBy('class_name')
            ->get();

        $sessions = ClassSession::where('status', 0)->orderBy('name')->get(['id', 'name']);
        $groups = Group::where('status', 0)->orderBy('name')->get(['id', 'name']);
        $sections = Section::where('status', 0)->orderBy('name')->get(['id', 'name']);
        $feeTypes = FeeType::where('status', 0)->orderBy('name')->get(); // Only active fee types

        return Inertia::render('Accountant/ClassFeeStructures/Create', [
            'classes' => $classes,
            'sessions' => $sessions,
            'groups' => $groups,
            'sections' => $sections,
            'feeTypes' => $feeTypes,
        ]);
    }

    /**
     * Store a newly created class fee structure in storage.
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:class_names,id',
            'session_id' => 'required|exists:class_sessions,id',
            'group_id' => 'nullable|exists:groups,id',
            'section_id' => 'nullable|exists:sections,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'amount' => 'required|numeric|min:0', // <--- CHANGED: 'integer' to 'numeric'
            'status' => 'required|in:0,1', // treat as string values if needed
        ]);  

        // Ensure uniqueness for the combination
        $uniqueRule = Rule::unique('class_fee_structures')->where(function ($query) use ($validated) {
            $query->where('class_id', $validated['class_id'])
                  ->where('session_id', $validated['session_id'])
                  ->where('fee_type_id', $validated['fee_type_id'])
                  ->where('group_id', $validated['group_id'])
                  ->where('section_id', $validated['section_id']);
        });
        // Applying unique validation to fee_type_id field for clarity,
        // though the rule itself checks the combination.
        $request->validate(['fee_type_id' => $uniqueRule]);


        ClassFeeStructure::create($validated);

        return redirect()->route('class-fee-structures.index')->with('flash', [
            'type' => 'success',
            'message' => 'Class Fee Structure created successfully!',
        ]);
    }

    /**
     * Show the form for editing the specified class fee structure.
    */

    public function edit(ClassFeeStructure $classFeeStructure)
    {
        // Fetch unique class names with the first corresponding id, sorted by class_name
        $classes = ClassName::where('status', 0)
            ->select('id', 'class_name')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('class_names')
                    ->where('status', 0)
                    ->groupBy('class_name');
            })
            ->orderBy('class_name')
            ->get();

        $sessions = ClassSession::where('status', 0)->orderBy('name')->get(['id', 'name']);
        $groups = Group::where('status', 0)->orderBy('name')->get(['id', 'name']);
        $sections = Section::where('status', 0)->orderBy('name')->get(['id', 'name']);
        $feeTypes = FeeType::where('status', 0)->orderBy('name')->get(); // Only active fee types

        return Inertia::render('Accountant/ClassFeeStructures/Edit', [
            'classFeeStructure' => $classFeeStructure,
            'classes' => $classes,
            'sessions' => $sessions,
            'groups' => $groups,
            'sections' => $sections,
            'feeTypes' => $feeTypes,
        ]);
    }

    /**
     * Update the specified class fee structure in storage.
    */
    public function update(Request $request, ClassFeeStructure $classFeeStructure)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:class_names,id',
            'session_id' => 'required|exists:class_sessions,id',
            'group_id' => 'nullable|exists:groups,id',
            'section_id' => 'nullable|exists:sections,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'amount' => 'required|numeric|min:0', // <--- CHANGED: 'integer' to 'numeric'
            'status' => 'required|boolean',
        ]);

        // Ensure uniqueness for the combination, ignoring the current record
        $uniqueRule = Rule::unique('class_fee_structures')->where(function ($query) use ($validated) {
            $query->where('class_id', $validated['class_id'])
                  ->where('session_id', $validated['session_id'])
                  ->where('fee_type_id', $validated['fee_type_id'])
                  ->where('group_id', $validated['group_id'])
                  ->where('section_id', $validated['section_id']);
        })->ignore($classFeeStructure->id);
        $request->validate(['fee_type_id' => $uniqueRule]);

        $classFeeStructure->update($validated);

        return redirect()->route('class-fee-structures.index')->with('flash', [
            'type' => 'success',
            'message' => 'Class Fee Structure updated successfully!',
        ]);
    }
 
    /**
     * Remove the specified class fee structure from storage.
    */
    public function destroy(ClassFeeStructure $classFeeStructure)
    {
        try {
            $classFeeStructure->delete();
            return redirect()->route('class-fee-structures.index')->with('flash', [
                'type' => 'success',
                'message' => 'Class Fee Structure deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return redirect()->route('class-fee-structures.index')->with('flash', [
                'type' => 'error',
                'message' => 'Error deleting Class Fee Structure: ' . $e->getMessage(),
            ]);
        }
    }






    // Student Fee Assignment Functions

    public function StudentFeeAssignmentIndex()
    {
        // Fetch all student fee assignments with their relationships for display
        $studentFeeAssignments = StudentFeeAssignment::with('student', 'feeType', 'class', 'section', 'session')
        ->orderBy('student_id')
        ->orderBy('fee_type_id')
        ->paginate(10);

        return Inertia::render('Accountant/StudentFeeAssignments/Index', [
            'studentFeeAssignments' => $studentFeeAssignments,
            'flash' => session('flash'), // Pass flash messages
        ]);
    }

    /**
     * Show the form for creating a new student fee assignment.
     * This method will now render the bulk assignment form.
    */
    public function StudentFeeAssignmentCreate()
    {
        // Fetch unique class names with the first corresponding id, sorted by class_name
        $classes = ClassName::where('status', 0)
            ->select('id', 'class_name')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('class_names')
                    ->where('status', 0)
                    ->groupBy('class_name');
            })
            ->orderBy('class_name')
            ->get();

        $sessions = ClassSession::where('status', 0)->orderBy('name')->get(['id', 'name']);
        $groups = Group::where('status', 0)->orderBy('name')->get(['id', 'name']);
        $sections = Section::where('status', 0)->orderBy('name')->get(['id', 'name']);
        $feeTypes = FeeType::where('status', 0)->orderBy('name')->get(); // Only active fee types

        // Render the new bulk assignment component
        return Inertia::render('Accountant/StudentFeeAssignments/Create', [
            'classes' => $classes,
            'sessions' => $sessions,
            'groups' => $groups,
            'sections' => $sections,
            'feeTypes' => $feeTypes,
        ]);
    }

    /**
     * Store a newly created student fee assignment in storage (single assignment).
     * This method is for your original single assignment form if it still exists.
     * If you are fully switching to bulk, you might remove the old single assignment form/route.
    */
    public function StudentFeeAssignmentBulkStore(Request $request)
    {
        // 1. Validate the bulk request
        $validated = $request->validate([
            'student_ids' => 'required|array', // Expect an array of student IDs
            'student_ids.*' => 'required|exists:students,id', // Each ID must exist
            'fee_type_id' => 'required|exists:fee_types,id',
            'session_id' => 'required|exists:class_sessions,id',
            'class_id' => 'required|exists:class_names,id',
            'section_id' => 'required|exists:sections,id',
            'status' => 'required|boolean',
        ]);

        $studentIdsToAssign = $validated['student_ids'];
        $assignmentData = [
            'fee_type_id' => $validated['fee_type_id'],
            'session_id' => $validated['session_id'],
            'class_id' => $validated['class_id'],
            'section_id' => $validated['section_id'],
            'status' => $validated['status'],
        ];

        // 2. Check for existing assignments (the core of your request)
        $existingAssignments = StudentFeeAssignment::where('fee_type_id', $assignmentData['fee_type_id'])
            ->where('session_id', $assignmentData['session_id'])
            ->where('class_id', $assignmentData['class_id'])
            ->where('section_id', $assignmentData['section_id'])
            ->whereIn('student_id', $studentIdsToAssign)
            ->pluck('student_id')
            ->toArray();

        // Determine which student IDs are *not* already assigned
        $newStudentIds = array_diff($studentIdsToAssign, $existingAssignments);

        $assignmentsToCreate = [];
        foreach ($newStudentIds as $studentId) {
            $assignmentsToCreate[] = array_merge($assignmentData, ['student_id' => $studentId]);
        }

        $totalStudentsAssigned = count($newStudentIds);
        $totalStudentsSkipped = count($existingAssignments);
        $message = '';
        
        // 3. Insert the new assignments
        if ($totalStudentsAssigned > 0) {
            // Use insert for efficiency with multiple records
            StudentFeeAssignment::insert($assignmentsToCreate);
            $message = "Successfully assigned fees to {$totalStudentsAssigned} students.";
        }

        if ($totalStudentsSkipped > 0) {
            $skippedMessage = "Skipped {$totalStudentsSkipped} students as the fee was already assigned.";
            // Append a warning if some were skipped
            $message .= ($message ? ' ' : '') . $skippedMessage;
            $flashType = ($totalStudentsAssigned > 0) ? 'warning' : 'error';
        } else {
            $flashType = 'success';
        }

        if ($totalStudentsAssigned == 0 && $totalStudentsSkipped == 0) {
            // Should not happen if validation passes, but good to handle.
            return redirect()->back()->with('flash', [
                'type' => 'error',
                'message' => 'No students selected for assignment.',
            ]);
        }
        
        return redirect()->route('student-fee-assignments.index')->with('flash', [
            'type' => $flashType,
            'message' => $message,
        ]);
    }

    /**
     * Show the form for editing the specified student fee assignment.
    */
    public function StudentFeeAssignmentEdit(StudentFeeAssignment $studentFeeAssignment)
    {
        $students = Student::orderBy('name')->get();
        $feeTypes = FeeType::where('status', 0)->orderBy('name')->get();

        return Inertia::render('Accountant/StudentFeeAssignments/Edit', [
            'studentFeeAssignment' => $studentFeeAssignment,
            'students' => $students,
            'feeTypes' => $feeTypes,
        ]);
    }

    /**
     * Update the specified student fee assignment in storage.
    */
    public function StudentFeeAssignmentUpdate(Request $request, StudentFeeAssignment $studentFeeAssignment)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'status' => 'required|boolean',
        ]);

        $studentFeeAssignment->update($validated);

        return redirect()->route('student-fee-assignments.index')->with('flash', [
            'type' => 'success',
            'message' => 'Student Fee Assignment updated successfully!',
        ]);
    }

    /**
     * Remove the specified student fee assignment from storage.
    */
    public function StudentFeeAssignmentDestroy(StudentFeeAssignment $studentFeeAssignment)
    {
        try {
            $studentFeeAssignment->delete();
            return redirect()->route('student-fee-assignments.index')->with('flash', [
                'type' => 'success',
                'message' => 'Student Fee Assignment deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return redirect()->route('student-fee-assignments.index')->with('flash', [
                'type' => 'error',
                'message' => 'Error deleting Student Fee Assignment: ' . $e->getMessage(),
            ]);
        }
    }


    /**
     * API function to fetch students by their academic context.
     * This will be called from your Vue component when the class, session, etc., are selected.
    */
    public function getStudentsByClass(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:class_names,id',
            'session_id' => 'required|exists:class_sessions,id', // Changed to class_sessions
            'group_id' => 'nullable|exists:groups,id',
            'section_id' => 'required|exists:sections,id',
        ]);

        $query = Student::where('class_id', $validated['class_id'])
                        ->where('session_id', $validated['session_id'])
                        ->where('section_id', $validated['section_id'])
                        ->orderBy('name');

        if ($validated['group_id']) {
            $query->where('group_id', $validated['group_id']);
        } else {
            // If group_id is null in the request, ensure it matches students with null group_id
            $query->whereNull('group_id');
        }

        $students = $query->get(['id', 'name', 'admission_number']);

        return response()->json($students);
    }

    /**
     * API function to handle the bulk creation of student fee assignments.
     * This will be called when you submit the new bulk assignment form.
    */
    public function bulkStoreAssignments(Request $request)
    {
        // 1. Validate the bulk request: Must include all required foreign keys
        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'session_id' => 'required|exists:class_sessions,id',
            'class_id' => 'required|exists:class_names,id',
            'section_id' => 'required|exists:sections,id',
            'status' => 'required|boolean',
        ]);

        $studentIdsToAssign = $validated['student_ids'];
        
        // Base data for the assignment (excluding student_id, timestamps)
        $assignmentData = [
            'fee_type_id' => $validated['fee_type_id'],
            'session_id' => $validated['session_id'],
            'class_id' => $validated['class_id'],
            'section_id' => $validated['section_id'],
            'status' => $validated['status'],
        ];

        // 2. Check for existing assignments (Duplicate Check)
        $existingAssignments = StudentFeeAssignment::where('fee_type_id', $assignmentData['fee_type_id'])
            ->where('session_id', $assignmentData['session_id'])
            ->where('class_id', $assignmentData['class_id'])
            ->where('section_id', $assignmentData['section_id'])
            ->whereIn('student_id', $studentIdsToAssign)
            ->pluck('student_id')
            ->toArray();

        // Determine which student IDs are *not* already assigned
        $newStudentIds = array_diff($studentIdsToAssign, $existingAssignments);

        $assignmentsToCreate = [];
        $now = now(); // *** CRITICAL: Get the timestamp once ***

        foreach ($newStudentIds as $studentId) {
            $assignmentsToCreate[] = array_merge($assignmentData, [
                'student_id' => $studentId,
                'created_at' => $now, // *** CRITICAL: Manually add timestamps for insert() ***
                'updated_at' => $now, // *** CRITICAL: Manually add timestamps for insert() ***
            ]);
        }

        $totalStudentsAssigned = count($newStudentIds);
        $totalStudentsSkipped = count($existingAssignments);
        $message = '';
        $flashType = 'success';

        // 3. Insert the new assignments efficiently (Bulk Insert)
        if ($totalStudentsAssigned > 0) {
            StudentFeeAssignment::insert($assignmentsToCreate);
            $message = "Successfully assigned fees to {$totalStudentsAssigned} students.";
        }

        // 4. Handle feedback for skipped students
        if ($totalStudentsSkipped > 0) {
            $skippedMessage = "Skipped {$totalStudentsSkipped} students as the fee was already assigned.";
            
            if ($totalStudentsAssigned > 0) {
                $message .= " " . $skippedMessage;
                $flashType = 'warning';
            } else {
                $message = $skippedMessage . " No new assignments were created.";
                $flashType = 'error';
            }
        }
        
        if (empty($message)) {
             $message = 'No students were selected for assignment.';
             $flashType = 'error';
        }

        return redirect()->route('student-fee-assignments.index')->with('flash', [
            'type' => $flashType,
            'message' => $message,
        ]);
    }

    public function getActiveStudents(Request $request)
    {
        $students = Student::where('status', 0)
            ->when($request->class_id, fn($q) => $q->where('class_id', $request->class_id))
            ->when($request->session_id, fn($q) => $q->where('session_id', $request->session_id))
            ->when($request->section_id, fn($q) => $q->where('section_id', $request->section_id))
            ->when($request->group_id, fn($q) => $q->where('group_id', $request->group_id))
            ->select('id', 'name', 'roll_number', 'admission_number')
            ->orderBy('roll_number')
            ->get();

        return response()->json([
            'students' => $students  // ← This is required!
        ]);
    }



    // FeeType Functions 

    public function FeeTypeIndex()
    {
        $feeTypes = FeeType::orderBy('name')->paginate(10); // Or adjust pagination limit

        return Inertia::render('Accountant/FeeTypes/Index', [
            'feeTypes' => $feeTypes,
            'flash' => session('flash'), // Pass flash messages
        ]);
    }

    /**
     * Show the form for creating a new fee type.
    */
    public function FeeTypeCreate()
    {
        // Pass the available frequencies to the frontend for the dropdown
         $frequencies = array_map(fn($enum) => $enum->value, FeeFrequency::cases());
        // If not using Enum, you might define them manually:
        // $frequencies = ['monthly', 'biannual', 'annual'];

        return Inertia::render('Accountant/FeeTypes/Create', [
            'frequencies' => $frequencies,
        ]);
    }

    /**
     * Store a newly created fee type in storage.
    */
    public function FeeTypeStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:fee_types,name',
            'description' => 'nullable|string',
            'frequency' => ['required', Rule::in(array_column(FeeFrequency::cases(), 'value'))], // If using Enum
            // If not using Enum: 'frequency' => 'required|in:monthly,biannual,
            'status' => 'required|boolean', // 0 for active, 1 for inactive
        ]);

        FeeType::create($validated);

        return redirect()->route('fee-types.index')->with('flash', [
            'type' => 'success',
            'message' => 'Fee Type created successfully!',
        ]);
    }

    /**
     * Show the form for editing the specified fee type.
    */

    public function FeeTypeEdit(FeeType $feeType)
    {
        $frequencies = array_map(fn($enum) => $enum->value, FeeFrequency::cases());
        // If not using Enum: $frequencies = ['monthly', 'biannual', 'annual'];

        return Inertia::render('Accountant/FeeTypes/Edit', [
            'feeType' => $feeType,
            'frequencies' => $frequencies,
        ]);
    }

    /**
     * Update the specified fee type in storage.
    */
    public function FeeTypeUpdate(Request $request, FeeType $feeType)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('fee_types', 'name')->ignore($feeType->id),
            ],
            'description' => 'nullable|string',
            'frequency' => ['required', Rule::in(array_column(FeeFrequency::cases(), 'value'))], // If using Enum
            // If not using Enum: 'frequency' => 'required|in:monthly,biannual,annual,one_time',
            'status' => 'required|boolean',
        ]);

        $feeType->update($validated);

        return redirect()->route('fee-types.index')->with('flash', [
            'type' => 'success',
            'message' => 'Fee Type updated successfully!',
        ]);
    }

    /**
     * Remove the specified fee type from storage.
    */
    public function FeeTypeDestroy(FeeType $feeType)
    {
        try {
            // Optional: Add a check here if this fee type is currently in use
            // in ClassFeeStructures or StudentFeeAssignments before deleting.
            // If it is, you might want to prevent deletion or soft delete it.
            // E.g., if ($feeType->classFeeStructures()->exists() || $feeType->studentFeeAssignments()->exists()) { ... }

            $feeType->delete();
            return redirect()->route('fee-types.index')->with('flash', [
                'type' => 'success',
                'message' => 'Fee Type deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return redirect()->route('fee-types.index')->with('flash', [
                'type' => 'error',
                'message' => 'Error deleting Fee Type: ' . $e->getMessage(),
            ]);
        }
    }


    // ==============================================================
    // ==========================INVOICE========================================= 
    // ====================================================================

    public function invoiceIndex()
    {
        // Get the class_name filter from the request, if any
        $classNameFilter = request()->query('class_name');

        // Build the invoice query with eager loading
        $query = Invoice::with([
            'student:id,name,class_id,section_id,session_id,group_id', // Fixed: session_id
            'student.className:id,class_name',
            'student.section:id,name',
            'student.session:id,name',
            'student.group:id,name',
            'invoiceItems.feeType:id,name',
            'payments:id,invoice_id,amount',
        ])->orderBy('issued_at', 'desc');

        // Apply class_name filter if provided
        if ($classNameFilter) {
            $query->whereHas('student.className', function ($q) use ($classNameFilter) {
                $q->whereRaw('LOWER(class_name) = ?', [strtolower($classNameFilter)]);
            });
            \Log::info('Filtered invoices count for class_name ' . $classNameFilter . ': ' . $query->count());
        }

        // Paginate the results
        $paginator = $query->paginate(10)->withQueryString();

        // Transform the invoices
        $invoices = $paginator->through(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'issued_at' => $invoice->issued_at,
                'due_date' => $invoice->due_date,
                'status' => $invoice->status,
                'total_amount_due' => $invoice->total_amount_due,
                'amount_paid' => $invoice->amount_paid,
                'balance_due' => $invoice->balance_due,
                'student' => [
                    'id' => optional($invoice->student)->id,
                    'name' => optional($invoice->student)->name,
                    'class_name' => optional(optional($invoice->student)->className)->class_name,
                    'section_name' => optional(optional($invoice->student)->section)->name,
                    'session_name' => optional(optional($invoice->student)->session)->name,
                    'group_name' => optional(optional($invoice->student)->group)->name,
                ],
            ];
        });

        // Fetch all class names for the filter dropdown
        $classNames = ClassName::orderBy('class_name')->pluck('class_name')->unique()->values();
        \Log::info('Class names for dropdown: ' . json_encode($classNames));

        return Inertia::render('Accountant/Invoice/InvoiceIndex', [
            'invoices' => $invoices,
            'classNames' => $classNames,
            'flash' => session('flash'),
            'filters' => [
                'class_name' => $classNameFilter,
            ],
        ]);
    }


    /**
     * Renders the page for creating a new invoice.
     * This method now passes the necessary data for the academic context dropdowns.
    */

    public function invoiceCreate()
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
            ->orderBy('class_name')
            ->get();

        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $groups = Group::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);

        return Inertia::render('Accountant/Invoice/CreateInvoice', [
            'classes' => $classes,
            'sessions' => $sessions,
            'groups' => $groups,
            'sections' => $sections,
        ]);
    }


    /**
     * Handles the form submission to create the invoice.
    */
    public function invoiceStore(Request $request)
    {
        // 1. Validate the incoming request data FIRST, so the $validated
        // variable is available for the entire method.
        $validated = $request->validate([
            'selected_students' => 'required|array|min:1',
            'selected_students.*' => 'exists:students,id',
            'due_date' => 'required|date',
            'billing_period' => 'nullable|string|max:255',
            'selected_fee_types' => 'required|array',
            'selected_fee_types.*' => 'exists:fee_types,id',
        ]);

        $invoicesCreatedCount = 0;
        $invoices = []; // To store created invoice objects if needed later

        // 2. Loop through each student ID to create a separate invoice for each.
        foreach ($validated['selected_students'] as $studentId) {
            $student = Student::with(['className', 'section', 'session', 'group', 'user'])->find($studentId);

            $totalAmount = 0;
            $invoiceItemsData = [];
            
            $billingPeriod = $validated['billing_period'] ?? now()->format('F Y');

            // Find fee structures and calculate total for the current student.
            foreach ($validated['selected_fee_types'] as $feeTypeId) {
                $feeStructure = ClassFeeStructure::with('feeType')
                    ->where('class_id', $student->class_id)
                    ->where('session_id', $student->session_id)
                    ->where('section_id', $student->section_id)
                    ->when($student->group_id, function ($query, $groupId) {
                        return $query->where('group_id', $groupId);
                    })
                    ->where('fee_type_id', $feeTypeId)
                    ->first();

                if ($feeStructure) {
                    $description = $feeStructure->feeType->name . ' - ' . $billingPeriod;
                    $amount = $feeStructure->amount;

                    $invoiceItemsData[] = [
                        'fee_type_id' => $feeTypeId,
                        'description' => $description,
                        'amount' => $amount,
                        // ✨ CRITICAL FIX: Set balance_due to the full amount initially.
                        'balance_due' => $amount,
                    ];
                    $totalAmount += $amount;
                } else {
                    Log::warning("No ClassFeeStructure found for fee_type_id: {$feeTypeId} for student {$student->id}'s academic context.");
                }
            }

            if (empty($invoiceItemsData)) {
                continue;
            }

            $invoice = null;
            // Use a database transaction to ensure the invoice and its items are created together.
            DB::transaction(function () use ($student, $totalAmount, $invoiceItemsData, $validated, &$invoice) {
                $invoice = Invoice::create([
                    'student_id' => $student->id,
                    'invoice_number' => 'INV-' . now()->timestamp . '-' . $student->id,
                    'due_date' => $validated['due_date'],
                    'total_amount_due' => $totalAmount,
                    'balance_due' => $totalAmount,
                    'status' => 'pending',
                    'issued_at' => now(),
                    'billing_period' => $validated['billing_period'] ?? now()->format('F Y'),
                ]);

                // This should now successfully create line items with the correct balance_due
                $invoice->invoiceItems()->createMany($invoiceItemsData);
            });

            // Notify the student's user if they exist.
            if ($student->user && $invoice) {
                $student->user->notify(new InvoiceCreated($invoice));
            }

            $invoicesCreatedCount++;
        }

        if ($invoicesCreatedCount > 0) {
            return redirect()->route('admin.invoices.index')->with('flash', ['type' => 'success', 'message' => "Successfully created {$invoicesCreatedCount} invoices."]);
        } else {
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'No invoices were created. Please ensure students and fee structures are correctly set up.']);
        }
    }


    public function invoiceEdit(Invoice $invoice)
    {
        // Eager load necessary data for the edit form, matching the relationships
        // used in your invoiceIndex method for visibility.
        $invoice->load([
            // Student details and their related academic names
            'student:id,name,class_id,section_id,session_id,group_id', 
            'student.className:id,class_name',
            'student.section:id,name',
            'student.session:id,name',
            'student.group:id,name',

            // Invoice Items and the Fee Type name for display
            'invoiceItems.feeType:id,name', 
        ]);

        // Fetch all lists needed for dropdowns (Fee Types and academic context)
        $feeTypes = FeeType::where('status', 0)->get(['id', 'name']);
        
        $classes = ClassName::where('status', 0)->get(['id', 'class_name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $groups = Group::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        
        return Inertia::render('Accountant/Invoice/InvoiceEdit', [
            'invoice' => $invoice,
            'feeTypes' => $feeTypes,
            'classes' => $classes,
            'sessions' => $sessions,
            'groups' => $groups,
            'sections' => $sections,
        ]);
    }


    public function invoiceUpdate(Request $request, Invoice $invoice) // <-- CHANGED: Now using base Request
    {
        // 1. MANUAL VALIDATION: Replace $request->validated() with $request->validate()
        $validated = $request->validate([
            'due_date' => ['required', 'date'],
            // Although removed from Vue, kept for completeness based on your provided block:
            // The request will fail if the front-end doesn't send this data and it's required.
            'issued_at' => ['nullable', 'date'], 

            // Validation for invoice items array
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'exists:invoice_items,id'],
            'items.*.fee_type_id' => ['required', 'integer', 'exists:fee_types,id'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
        ]);
        
        // Use a database transaction to ensure atomicity
        DB::beginTransaction();

        try {
            // 2. Update the main invoice header fields
            $invoice->update([
                'due_date' => $validated['due_date'],
                // These fields are included based on the validation rules we defined above
                'issued_at' => $validated['issued_at'] ?? null, 
            ]);

            // 3. Sync Invoice Items (Fixed Items from Vue component are sent here)
            $invoiceItemsData = [];
            $newTotalAmountDue = 0;

            // $validated['items'] is now available
            foreach ($validated['items'] as $item) {
                $amount = (float) $item['amount'];
                $newTotalAmountDue += $amount;

                // Ensure only existing IDs are passed for syncing existing items
                if (isset($item['id'])) {
                    $invoiceItemsData[] = [
                        'id' => $item['id'],
                        'fee_type_id' => $item['fee_type_id'],
                        'amount' => $amount,
                    ];
                }
            }
            
            // Update the existing items
            foreach ($invoiceItemsData as $itemData) {
                if (isset($itemData['id'])) {
                    $invoice->invoiceItems()->where('id', $itemData['id'])->update($itemData);
                }
            }
            
            // 4. Update final calculated totals on the main invoice
            $invoice->update([
                'total_amount_due' => $newTotalAmountDue,
                // balance_due must be recalculated: new total - amount already paid
                'balance_due' => $newTotalAmountDue - $invoice->amount_paid,
            ]);
            
            DB::commit();

            // Success Response
            return redirect()->route('admin.invoices.index', $invoice)
                ->with('flash', [
                    'message' => "Invoice #{$invoice->invoice_number} successfully updated.",
                    'type' => 'success'
                ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Invoice update failed for #{$invoice->invoice_number}: " . $e->getMessage());

            // Error Response
            return redirect()->back()
                ->with('flash', [
                    'message' => 'Failed to update invoice. Please check logs for details.',
                    'type' => 'error'
                ]);
        }
    }




    


    


    /**
     * NEW API ENDPOINT.
     * Fetches students and fee structures based on academic context.
     * This will be called via AJAX from the front-end.
    */
    public function getAcademicData(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:class_names,id',
            'session_id' => 'required|exists:class_sessions,id',
            'section_id' => 'required|exists:sections,id',
            'group_id' => 'required|exists:groups,id',
        ]);


        // --- DEBUG POINT A: Check validated request data ---
        // dd($request->all());

        // This line is critical: Ensure 'class_id' matches your Student model's column
        $students = Student::where([
            'class_id' => $request->class_id, // <--- Make sure this is 'class_id'
            'session_id' => $request->session_id,
            'section_id' => $request->section_id,
            'group_id' => $request->group_id,
        ])->get(['id', 'name', 'admission_number']);

        // dd($students->toArray());

        $feeStructures = ClassFeeStructure::with('feeType')
            ->where('class_id', $request->class_id)
            ->where('session_id', $request->session_id)
            ->where('section_id', $request->section_id)
            ->where('group_id', $request->group_id)
            ->where('status', 0)
            ->get(['fee_type_id', 'amount']);

        return response()->json([
            'students' => $students,
            'fee_structures' => $feeStructures,
        ]);
    }




    // Invoice download as PDF function
    public function downloadPdf($id)
    {
        // Eager load necessary relations for invoice
        $invoice = Invoice::with(['student', 'invoiceItems', 'payments'])->findOrFail($id);

        // Generate PDF from Blade view 'invoices.pdf' passing invoice data
        $pdf = Pdf::loadView('Invoices.pdf', compact('invoice'));

        // Send the generated PDF as a downloadable file
        return $pdf->download("invoice_{$invoice->invoice_number}.pdf");
    }



    // Accountant Payment Collection Functions
    // In your controller's collectPayment method

    public function collectPayment(Request $request)
    {
        $search = $request->input('search');

        $students = Student::query()
            ->with([
                'invoices' => function($query) {
                    // Eager load the payments for each invoice
                    $query->with('payments');
                }, 
                'className',  // Ensure this matches your relationship method name
                'section', 
                'group', 
                'session'
            ])
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('admission_number', 'like', "%{$search}%");
            })
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'admission_number' => $student->admission_number,
                    'class_name' => optional($student->studentClass)->name, 
                    'section' => optional($student->section)->name,
                    'session' => optional($student->session)->name,
                    'group' => optional($student->group)->name,
                    'invoices' => $student->invoices->map(function ($invoice) {
                        return [
                            'id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'total_amount_due' => $invoice->total_amount_due,
                            'amount_paid' => $invoice->amount_paid,
                            'balance_due' => $invoice->balance_due,
                            'status' => $invoice->status,
                            // Include the payments data here
                            'payments' => $invoice->payments->map(function ($payment) {
                                return [
                                    'id' => $payment->id,
                                    'amount' => $payment->amount,
                                    'method' => $payment->method,
                                    'payment_date' => $payment->payment_date,
                                ];
                            })
                        ];
                    })
                ];
            });

        return Inertia::render('Accountant/Payments/CollectPayment', [
            'students' => $students,
            'filters' => [
                'search' => $search,
            ],
            'flash' => session('flash'),
        ]);
    }




    public function storePayment(Request $request)
    {
        // Find the invoice first to get its current balance.
        $invoice = Invoice::findOrFail($request->invoice_id);
        $currentBalanceDue = $invoice->balance_due;

        // Validate the incoming request data, including the custom rule.
        try {
            $validated = $request->validate([
                'invoice_id' => 'required|exists:invoices,id',
                'amount' => [
                    'required',
                    'numeric',
                    'min:1',
                    function ($attribute, $value, $fail) use ($currentBalanceDue) {
                        if ($value > $currentBalanceDue) {
                            $fail("The payment amount cannot exceed the invoice balance of {$currentBalanceDue} TK.");
                        }
                    },
                ],
                'payment_method' => 'required|string',
                'student_id' => 'required|exists:students,id',
            ]);
        } catch (ValidationException $e) {
            // Return back with validation errors
            return redirect()->back()->withErrors($e->errors());
        }

        // Check for existing full payment to prevent double payment issues
        if ($invoice->balance_due <= 0) {
            // Use a different flash message key for errors
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'This invoice has already been fully paid.']);
        }

        $newStatus = '';
        DB::transaction(function () use ($validated, $invoice, &$newStatus) {
            Payment::create([
                'student_id' => $validated['student_id'],
                'invoice_id' => $invoice->id,
                'amount' => $validated['amount'],
                'method' => $validated['payment_method'],
                'payment_date' => now(),
                'received_by' => auth()->id(),
            ]);

            $newAmountPaid = $invoice->amount_paid + $validated['amount'];
            $newBalanceDue = $invoice->total_amount_due - $newAmountPaid;
            $newBalanceDue = max(0, $newBalanceDue);

            $newStatus = ($newBalanceDue <= 0) ? 'paid' : 'partially_paid';

            $invoice->update([
                'amount_paid' => $newAmountPaid,
                'balance_due' => $newBalanceDue,
                'status' => $newStatus,
                'paid_at' => ($newStatus === 'paid') ? now() : null,
            ]);
        });

        // Determine the final message based on the new status
        $message = ($newStatus === 'paid') 
            ? 'Full payment collected. Invoice is now fully paid.' 
            : 'Partial payment collected successfully. Remaining balance: ' . $invoice->balance_due . ' TK.';

        // Return back with a success flash message
      return redirect()->route('accountant.payments.collect')->with('flash', ['type' => 'success', 'message' => $message]);


    }




    // Student Invoice PDF Download Function

    public function downloadInvoicePdf(Invoice $invoice)
    {
        // Eager load related data to avoid multiple queries
        $invoice->load('student', 'payments');

        // Make sure the invoice is paid or partially paid before generating
        if ($invoice->status === 'paid' || $invoice->status === 'partially_paid') {

            // Fetch the school settings data
            $settings = Setting::first();

            // Pass both the invoice and settings data to the Blade view
            $pdf = Pdf::loadView('pdfs.invoice', compact('invoice', 'settings'));

            return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
        }

        // Redirect back with an error if the invoice isn't paid
        return redirect()->back()->with('error', 'The invoice must be paid or partially paid to download.');
    }


    // For now, a show method to verify creation

    public function invoiceShow(Invoice $invoice)
    {
        // Eager load the relationships needed for the detailed view
        $invoice->load([
            // Load the 'class' relationship on the 'student' model
            'student.className', 
            
            // Load the 'feeType' relationship for each 'invoiceItem'
            'invoiceItems.feeType', 
            
            // Load payment history
            'payments', 
        ]);

        return Inertia::render('Accountant/Invoice/InvoiceShow', [
            'invoice' => $invoice,
        ]);
    }



    // public function markAllAsRead()
    // {
    //     Auth::user()->unreadNotifications->markAsRead();

    //     return redirect()->back()->with('flash', [
    //         'type' => 'success',
    //         'message' => 'All notifications marked as read!',
    //     ]);
    // }



    








    // ======================  Student Functions (Invoice Viewing & Payment Submission) ======================================


    // public function studentInvoices(Request $request)
    // {
    //     $user = $request->user();
    //     $student = $user->student;

    //     if (!$student) {
    //         return redirect()->route('dashboard')->with('flash', [
    //             'type' => 'error',
    //             'message' => 'Student profile not found.'
    //         ]);
    //     }

    //     // Fetch invoices for this specific student, and eager-load the related
    //     // invoice items and their corresponding fee types.
    //     $studentInvoices = Invoice::where('student_id', $student->id)
    //                              ->with('invoiceItems.feeType') // Eager load the relationships
    //                              ->orderBy('due_date', 'desc')
    //                              ->get();

    //     // Pass the student's invoices and name to the Inertia view.
    //     return Inertia::render('Student/StudentInvoice', [
    //         'studentInvoices' => $studentInvoices,
    //         'userName' => $user->name,
    //     ]);
    // }











    /**
     * Process a payment for a student's invoice.
    */
    public function processPayment(Request $request)
    {
        $user = $request->user();
        $student = $user->student;

        if (!$student) {
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'Student profile not found.']);
        }

        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,bank_transfer,mobile_banking,cheque,online_gateway',
            'transaction_ref' => 'nullable|string|max:255',
        ]);

        $invoice = Invoice::where('id', $validated['invoice_id'])
            ->where('student_id', $student->id)
            ->first();

        if (!$invoice) {
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'Invoice not found or does not belong to you.']);
        }

        // Prevent payment if invoice is already fully paid or cancelled
        if ($invoice->status === 'paid' || $invoice->status === 'cancelled') {
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'This invoice is already paid or cancelled.']);
        }

        // Prevent payment if the invoice is already pending approval
        if ($invoice->status === 'pending_payment_approval') {
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'This invoice is already awaiting payment approval.']);
        }

        // Prevent overpayment based on current balance
        if ($validated['amount'] > $invoice->balance_due) {
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'Payment amount exceeds the remaining balance.']);
        }

        try {
            DB::transaction(function () use ($validated, $invoice, $student, $user) {
                // 1. Create the Payment record with its status set to 0 (pending_approval)
                $payment = Payment::create([
                    'student_id' => $student->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $validated['amount'],
                    'method' => $validated['method'],
                    'transaction_ref' => $validated['transaction_ref'],
                    'payment_date' => now(),
                    'received_by' => $user->id,
                    'status' => 0, // This is the status for the *payment record itself*
                ]);

                // 2. Update the Invoice status to 'pending_payment_approval'
                // This is the status for the *invoice* that the student sees.
                $invoice->status = 'pending_payment_approval';
                $invoice->save();

                // 3. Find the accountant(s) to notify.
                // Assuming you have a 'role' relationship on the User model
                $accountants = User::role('accounts')->get();

                // 4. Send the notification to the accountant(s).
                // Use the student's name for a more personalized message.
                $studentName = $student->user->name;
                foreach ($accountants as $accountant) {
                    $accountant->notify(new StudentPaymentReceived($studentName, $validated['amount']));
                }
            });

            return redirect()->back()->with('flash', ['type' => 'success', 'message' => 'Payment submitted for approval!']);

        } catch (\Exception $e) {
            Log::error("Payment submission failed for invoice {$invoice->id}: " . $e->getMessage());
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'An error occurred during payment submission. Please try again.']);
        }
    }




    // Student PDF Generation Function
    public function generatePdf($id)
    {
        $invoice = Invoice::with(['invoiceItems.feeType', 'student'])
                        ->findOrFail($id);

        // Does this invoice belong to *my* student?
        if (Auth::id() !== $invoice->student->user_id) {
            abort(403, 'Unauthorized action.');
        }

        $pdf = Pdf::loadView('StudentPdf.invoice_pdf', ['invoice' => $invoice]);
        return $pdf->stream("Invoice-{$invoice->invoice_number}.pdf");
    }




    // ===================================================
    // Admin Functions (Payment Approval)============================

    public function pendingPayments()
    {
        // Fetch invoices that have a status of 'pending_payment_approval'
        // Eager load the student (and their user details) and any associated payments
        $pendingInvoices = Invoice::where('status', 'pending_payment_approval')
                                  ->with(['student.user', 'payments' => function($query) {
                                      // Only load payments that are currently pending approval (status 0)
                                      // This ensures we get the specific payment details for display on the admin side.
                                      $query->where('status', 0);
                                  }])
                                  ->orderBy('due_date', 'desc')
                                  ->get();

        return Inertia::render('Accountant/PendingPayments', [
            'pendingInvoices' => $pendingInvoices, // Renamed prop to better reflect content
        ]);
    }

    public function approvePayment(Request $request, $invoiceId)
    {
        $invoice = Invoice::find($invoiceId);

        if (!$invoice || $invoice->status !== 'pending_payment_approval') {
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'Invoice not found or not in pending approval status.']);
        }

        $pendingPayment = $invoice->payments()->where('status', 0)->first();

        if (!$pendingPayment) {
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'No pending payment found for this invoice.']);
        }

        try {
            DB::transaction(function () use ($invoice, $pendingPayment) {
                $pendingPayment->status = 1; // Approved
                $pendingPayment->save();

                $invoice->amount_paid += $pendingPayment->amount;
                $invoice->balance_due -= $pendingPayment->amount;

                if ($invoice->balance_due <= 0) {
                    $invoice->status = 'paid';
                    $invoice->paid_at = now();
                } elseif ($invoice->amount_paid > 0 && $invoice->balance_due > 0) {
                    $invoice->status = 'partially_paid';
                }
                $invoice->save();
            });

            return redirect()->back()->with('flash', ['type' => 'success', 'message' => 'Payment approved successfully! Invoice status updated.']);

        } catch (\Exception $e) {
            Log::error("Payment approval failed for invoice {$invoiceId}: " . $e->getMessage());
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'An error occurred during payment approval. Please try again.']);
        }
    }

    public function rejectPayment(Request $request, $invoiceId)
    {
        $invoice = Invoice::find($invoiceId);

        if (!$invoice || $invoice->status !== 'pending_payment_approval') {
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'Invoice not found or not in pending approval status.']);
        }

        $pendingPayment = $invoice->payments()->where('status', 0)->first();

        if (!$pendingPayment) {
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'No pending payment found for this invoice to reject.']);
        }

        try {
            DB::transaction(function () use ($invoice, $pendingPayment) {
                $pendingPayment->status = 2; // Rejected
                $pendingPayment->save();

                // Revert invoice status based on its current financial state
                if ($invoice->amount_paid == 0) {
                    $invoice->status = 'pending';
                } else {
                    $invoice->status = 'partially_paid';
                }
                $invoice->save();
            });

            return redirect()->back()->with('flash', ['type' => 'success', 'message' => 'Payment rejected successfully. Invoice status reverted.']);

        } catch (\Exception $e) {
            Log::error("Payment rejection failed for invoice {$invoiceId}: " . $e->getMessage());
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'An error occurred during payment rejection. Please try again.']);
        }
    }




     /**
     * Display the payment history with filters.
     */
    public function paymentHistoryindex(Request $request)
    {
        // Fetch unique class names with the first corresponding id, sorted by class_name
        $classes = ClassName::where('status', 0)
            ->select('id', 'class_name AS name')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('class_names')
                    ->where('status', 0)
                    ->groupBy('class_name');
            })
            ->orderBy('class_name')
            ->get();

        $classId = $request->get('class_id');
        $statusFilter = $request->get('status');
        $invoices = Invoice::query()
            // ... existing filters ...
            ->when($classId, function ($query, $classId) {
                $query->whereHas('student', function ($q) use ($classId) {
                    $q->where('class_id', $classId);
                });
            })
            ->when($statusFilter, function ($query, $statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->with([
                // Keep existing Student relationship
                'student' => function ($query) {
                    $query->select('id', 'name', 'admission_number', 'class_id')
                        ->with(['className' => function ($q) {
                            $q->where('status', 0)
                            ->select('id', 'class_name AS name');
                        }]);
                },
                // ✨ NEW: Eager load invoice items and their fee type/category
                'invoiceItems' => function ($query) {
                    // Select only the item details needed to show the due category
                    $query->select('invoice_id', 'fee_type_id', 'description', 'balance_due')
                        // Only load items that actually have a balance due (> 0)
                        ->where('balance_due', '>', 0)
                        ->with(['feeType' => function ($q) {
                            // Select the ID and Name from the fee_types table
                            $q->select('id', 'name');
                        }]);
                }
            ])
            // Select core invoice fields
            ->select('id', 'invoice_number', 'total_amount_due', 'amount_paid', 'status', 'due_date', 'issued_at', 'student_id')
            // Your existing calculation for the main invoice balance_due
            ->addSelect(DB::raw('CAST(COALESCE(total_amount_due - amount_paid, total_amount_due) AS SIGNED) as balance_due'))
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Payment/PaymentHistory', [
            'invoices' => $invoices,
            'classes' => $classes,
            'selectedClassId' => (int) $classId,
            'selectedStatus' => $statusFilter,
        ]);
    }


}


