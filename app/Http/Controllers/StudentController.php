<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassName; 
use App\Models\ClassSession;
use App\Models\Section;
use App\Models\Group;
use App\Models\User; 
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Inertia\Inertia;
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
            'admission_number' => 'required|string|max:255|unique:students,admission_number',
            'roll_number' => 'required|integer|unique:students,roll_number',

            // New validation rules for admission fee and payment details
            'admission_fee_amount' => 'nullable|numeric|min:0', // Use numeric for decimal input
            'admission_fee_paid' => 'boolean', // Expects 0 or 1 from checkbox/toggle
            'payment_method' => ['nullable', 'string', Rule::in(['Cash', 'bKash', 'Bank Transfer'])], // Add your expected methods
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('students', 'public'); // Store in 'storage/app/public/students'
            $validatedData['image'] = $imagePath; // Add the path to validated data
        } else {
            $validatedData['image'] = null; // Ensure image is null if no file is uploaded
        }

        // --- Handle admission_fee_amount conversion ---
        // If admission_fee_amount is provided, convert it to the smallest unit (paisa).
        // Assuming input is in BDT (e.g., 5000), convert to paisa (500000).
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

        // Create the student using the validated data
        $student = Student::create($validatedData);

        // Redirect back to the index page without a flash message
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
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:class_names,id',
            'age' => 'required|integer|min:0',
            'date_of_birth' => 'required|date',
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other'])],
            'admission_date' => 'required|date',
            'session_id' => 'required|exists:class_sessions,id',
            'group_id' => 'required|exists:groups,id',
            'section_id' => 'required|exists:sections,id',

            // user_id unique rule should ignore the current student's user_id
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('students', 'user_id')->ignore($student->id),
            ],

            'parent_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'contact' => 'required|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|integer|in:0,1',
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
}