<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClassFeeStructure;
use App\Models\ClassName; 
use App\Models\ClassSession;
use App\Models\Group;
use App\Models\Section;
use App\Models\User;
use App\FeeFrequency; 
use App\Notifications\InvoiceCreated;
use App\Notifications\StudentPaymentReceived;
use App\Models\StudentFeeAssignment;
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
        // Provide necessary data for dropdowns
        $classes = ClassName::orderBy('class_name')->get(); // Adjust 'class_name' if your column is different
        $sessions = ClassSession::orderBy('name')->get();
        $groups = Group::orderBy('name')->get();
        $sections = Section::orderBy('name')->get();
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
        $classes = ClassName::orderBy('class_name')->get();
        $sessions = ClassSession::orderBy('name')->get();
        $groups = Group::orderBy('name')->get();
        $sections = Section::orderBy('name')->get();
        $feeTypes = FeeType::where('status', 0)->orderBy('name')->get();

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






    // 

    public function StudentFeeAssignmentIndex()
    {
        // Fetch all student fee assignments with their relationships for display
        $studentFeeAssignments = StudentFeeAssignment::with('student', 'feeType')
                                                    ->orderBy('student_id')
                                                    ->orderBy('fee_type_id')
                                                    ->paginate(10); // Or adjust pagination limit

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
        // Provide all necessary data for the filter dropdowns in the bulk assignment form
        $classes = ClassName::orderBy('class_name')->get(); // Adjust 'name' if your column is 'class_name'
        $sessions = ClassSession::orderBy('name')->get();
        $groups = Group::orderBy('name')->get();
        $sections = Section::orderBy('name')->get();
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
    public function StudentFeeAssignmentStore(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'applies_from' => 'required|date',
            'applies_to' => 'nullable|date|after_or_equal:applies_from',
            'status' => 'required|boolean', // 0 for active, 1 for inactive
        ]);

        StudentFeeAssignment::create($validated);

        return redirect()->route('student-fee-assignments.index')->with('flash', [
            'type' => 'success',
            'message' => 'Student Fee Assignment created successfully!',
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
            'applies_from' => 'required|date',
            'applies_to' => 'nullable|date|after_or_equal:applies_from',
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
        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'fee_type_id' => 'required|exists:fee_types,id',
            'applies_from' => 'required|date',
            'applies_to' => 'nullable|date|after_or_equal:applies_from',
            'status' => 'required|boolean',
        ]);

        // Loop through each student to create an assignment
        foreach ($validated['student_ids'] as $studentId) {
            StudentFeeAssignment::create([
                'student_id' => $studentId,
                'fee_type_id' => $validated['fee_type_id'],
                'applies_from' => $validated['applies_from'],
                'applies_to' => $validated['applies_to'],
                'status' => $validated['status'],
            ]);
        }

        return redirect()->route('student-fee-assignments.index')->with('flash', [
            'type' => 'success',
            'message' => 'student fee assignments created successfully!',
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
        $paginator = Invoice::with([
            'student:id,name,class_id,section_id,session_id,group_id',
            'student.className:id,class_name',
            'student.section:id,name',
            'student.session:id,name',
            'student.group:id,name',
            'invoiceItems.feeType:id,name',
            'payments:id,invoice_id,amount',
        ])->orderBy('issued_at', 'desc')->paginate(10);

        // Transform each item (Laravel 10+ supports through() on paginator)
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
                    'class_name' => optional(optional($invoice->student)->className)->name,
                    'section_name' => optional(optional($invoice->student)->section)->name,
                    'session_name' => optional(optional($invoice->student)->session)->name,
                    'group_name' => optional(optional($invoice->student)->group)->name,
                ],
            ];
        });

        return Inertia::render('Accountant/Invoice/InvoiceIndex', [
            'invoices' => $invoices,
            'flash' => session('flash'),
        ]);
    }


    /**
     * Renders the page for creating a new invoice.
     * This method now passes the necessary data for the academic context dropdowns.
    */
    public function invoiceCreate()
    {
        $classes = ClassName::all(['id', 'class_name']);
        $sessions = ClassSession::all(['id', 'name']);
        $groups = Group::all(['id', 'name']);
        $sections = Section::all(['id', 'name']);

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
        // 1. Validate the incoming request data.
        // This ensures the required fields are present and correctly formatted.
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'due_date' => 'required|date',
            'selected_fee_types' => 'required|array',
            'selected_fee_types.*' => 'exists:fee_types,id',
        ]);

        // 2. Load the student with their academic relationships and the user relationship.
        // The 'user' relationship is needed to send the notification later.
        $student = Student::with(['className', 'section', 'session', 'group', 'user'])->find($validated['student_id']);

        $totalAmount = 0;
        $invoiceItemsData = [];

        // 3. Define the billing period.
        $billingPeriod = now()->format('F Y'); // e.g., "August 2025"

        // 4. Loop through each selected fee type to find its fee structure and calculate the total.
        foreach ($validated['selected_fee_types'] as $feeTypeId) {
            // Find the fee structure based on the student's current academic context.
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
                // If a fee structure is found, prepare the data for an invoice item.
                $description = $feeStructure->feeType->name . ' - ' . $billingPeriod;
                $amount = $feeStructure->amount;

                $invoiceItemsData[] = [
                    'fee_type_id' => $feeTypeId,
                    'description' => $description,
                    'amount' => $amount,
                ];
                $totalAmount += $amount;
            } else {
                Log::warning("No ClassFeeStructure found for fee_type_id: {$feeTypeId} for student {$student->id}'s academic context.");
            }
        }

        // 5. If no valid fee items are found, redirect back with an error.
        if (empty($invoiceItemsData)) {
            return redirect()->back()->with('flash', ['type' => 'error', 'message' => 'No valid fee items found for the selected student and fees.']);
        }

        $invoice = null;
        // 6. Use a database transaction to ensure both the invoice and its items are created together.
        // If either creation fails, everything is rolled back.
        DB::transaction(function () use ($validated, $totalAmount, $invoiceItemsData, $billingPeriod, &$invoice) {
            $invoice = Invoice::create([
                'student_id' => $validated['student_id'],
                'invoice_number' => 'INV-' . time(),
                'due_date' => $validated['due_date'],
                'total_amount_due' => $totalAmount,
                'balance_due' => $totalAmount, // Initially, the balance is the total amount due.
                'status' => 'pending',
                'issued_at' => now(),
                'billing_period' => $billingPeriod,
            ]);

            // Create all the related invoice items for the newly created invoice.
            $invoice->invoiceItems()->createMany($invoiceItemsData);
        });

        // 7. Notify the student's user if they exist.
        // This logic is now placed here, after the transaction is committed.
        if ($student->user) {
            $student->user->notify(new InvoiceCreated($invoice));
        }

        // 8. Redirect to the newly created invoice's page with a success message.
        return redirect()->route('admin.invoices.show', $invoice)->with('flash', ['type' => 'success', 'message' => 'Invoice created successfully.']);
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





    
    // You can add more functions here to show all invoices, edit them, etc.
    // For now, a show method to verify creation
    public function invoiceShow(Invoice $invoice)
    {
        $invoice->load(['student', 'invoiceItems.feeType', 'payments.receivedBy']);

        return Inertia::render('Admin/ShowInvoice', [
            'invoice' => $invoice,
        ]);
    }



    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('flash', [
            'type' => 'success',
            'message' => 'All notifications marked as read!',
        ]);
    }



    








    // ======================  Student Functions (Invoice Viewing & Payment Submission) ======================================


    public function studentInvoices(Request $request)
    {
        $user = $request->user();
        $student = $user->student;

        if (!$student) {
            return redirect()->route('dashboard')->with('flash', [
                'type' => 'error',
                'message' => 'Student profile not found.'
            ]);
        }

        // Fetch invoices for this specific student, and eager-load the related
        // invoice items and their corresponding fee types.
        $studentInvoices = Invoice::where('student_id', $student->id)
                                 ->with('invoiceItems.feeType') // Eager load the relationships
                                 ->orderBy('due_date', 'desc')
                                 ->get();

        // Pass the student's invoices and name to the Inertia view.
        return Inertia::render('Student/StudentInvoice', [
            'studentInvoices' => $studentInvoices,
            'userName' => $user->name,
        ]);
    }











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


}


