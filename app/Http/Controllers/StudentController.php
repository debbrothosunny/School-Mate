<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassName; 
use App\Models\ClassSession;
use App\Models\Section;
use App\Models\Group;
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
        // Eager load relations and paginate the students
        // Inertia automatically serializes the model attributes and loaded relationships
        $students = Student::with(['className', 'session', 'group', 'section'])->latest()->paginate(10);

        return Inertia::render('Students/Index', [
            'students' => $students,
        ]);
    }


    /**
     * Show the form for creating a new student.
     */

    public function create()
    {
        $classes = ClassName::where('status', 0)->get(['id', 'name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $groups = Group::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']); // <--- Fetch sections

        return Inertia::render('Students/Create', [
            'classes' => $classes,
            'sessions' => $sessions,
            'groups' => $groups,
            'sections' => $sections, // <--- Pass sections to the view
        ]);
    }

    /**
     * Store a newly created student in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'nullable|exists:class_names,id',
            'age' => 'nullable|integer|min:0',
            'session_id' => 'nullable|exists:class_sessions,id',
            'group_id' => 'nullable|exists:groups,id',
            'section_id' => 'nullable|exists:sections,id',
            'parent_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Image validation: nullable, image file, specific mimes, max 2MB
            'status' => 'required|integer|in:0,1',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('students', 'public'); // Store in 'storage/app/public/students'
            $validatedData['image'] = $imagePath; // Add the path to validated data
        } else {
            $validatedData['image'] = null; // Ensure image is null if no file is uploaded
        }

        Student::create($validatedData); // Use $validatedData for creation

        return redirect()->route('students.index')->with('flash', [
            'message' => 'Student added successfully!',
            'type' => 'success'
        ]);
    }



    /**
     * Show the form for editing the specified resource.
     */
     public function edit(Student $student)
    {
        $classes = ClassName::where('status', 0)->get(['id', 'name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']); // Changed to ClassSession
        $groups = Group::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);

        return Inertia::render('Students/Edit', [
            'student' => $student->load(['className', 'session', 'group', 'section']),
            'classes' => $classes,
            'sessions' => $sessions,
            'groups' => $groups,
            'sections' => $sections,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Student $student)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'nullable|exists:class_names,id',
            'age' => 'nullable|integer|min:0',
            'session_id' => 'nullable|exists:class_sessions,id',
            'group_id' => 'nullable|exists:groups,id',
            'section_id' => 'nullable|exists:sections,id',
            'parent_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Image validation for update
            'status' => 'required|integer|in:0,1',
        ]);

        // Handle image update
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($student->image) {
                Storage::disk('public')->delete($student->image);
            }
            $imagePath = $request->file('image')->store('students', 'public');
            $validatedData['image'] = $imagePath;
        } elseif (isset($validatedData['image']) && $validatedData['image'] === null) {
            // If image field is explicitly set to null (e.g., user removed existing image)
            if ($student->image) {
                Storage::disk('public')->delete($student->image);
            }
            $validatedData['image'] = null;
        } else {
            // If no new image is uploaded and it's not explicitly set to null, keep existing image
            $validatedData['image'] = $student->image;
        }

        $student->update($validatedData); // Use $validatedData for update

        return redirect()->route('students.index')->with('flash', [
            'message' => 'Student updated successfully!',
            'type' => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Student $student)
    {
        $student->delete();

        return redirect()->route('students.index')->with('flash', [
            'message' => 'Student deleted successfully!',
            'type' => 'success'
        ]);
    }
}