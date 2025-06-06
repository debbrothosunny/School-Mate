<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage; // Import Storage facade

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all teachers with their associated user data (if user_id is present)
        $teachers = Teacher::with('user:id,name,email')->get();

        return Inertia::render('Teachers/Index', [
            'teachers' => $teachers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Pass a list of users who are NOT ALREADY assigned as teachers.
        // This prevents linking a user to multiple teacher profiles.
        $assignedUserIds = Teacher::whereNotNull('user_id')->pluck('user_id');
        $availableUsers = User::whereNotIn('id', $assignedUserIds)->select('id', 'name')->get();

        return Inertia::render('Teachers/Create', [
            'availableUsers' => $availableUsers, // Renamed for clarity
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // 1. Capture the validated data into a variable
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            // user_id is nullable, but if provided, it must exist and be unique among teachers
            'user_id' => 'nullable|exists:users,id|unique:teachers,user_id',
            'subject_taught' => 'required|string|max:255',
            'address' => 'nullable|string|max:255', // Assuming 'address' is also part of the form
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validation for the image file
            'status' => 'required|integer|in:0,1',
        ]);

        // 2. Handle image upload and update the $validatedData array
        if ($request->hasFile('image')) {
            // Store the image in 'teachers' subdirectory within the 'public' disk
            $imagePath = $request->file('image')->store('teachers', 'public');
            // Store the path in the 'image' key of the validated data array
            $validatedData['image'] = $imagePath;
        } else {
            // If no file was uploaded, ensure the 'image' field is explicitly null
            $validatedData['image'] = null;
        }

        // 3. Create the teacher record using the $validatedData array
        // This ensures all validated fields, including the image path, are passed to the model.
        Teacher::create($validatedData);

        return redirect()->route('teachers.index')
                         ->with('success', 'Teacher added successfully.');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Teacher $teacher)
    {
        // When editing, the currently linked user (if any) should be available in the dropdown
        // along with other unassigned users.
        $assignedUserIds = Teacher::whereNotNull('user_id')
                                  ->where('id', '!=', $teacher->id) // Exclude current teacher's user_id
                                  ->pluck('user_id');
        $availableUsers = User::whereNotIn('id', $assignedUserIds)->select('id', 'name')->get();

        return Inertia::render('Teachers/Edit', [
            'teacher' => $teacher->load('user'), // Eager load user data
            'availableUsers' => $availableUsers, // Renamed for clarity
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Teacher $teacher)
    {
        // 1. Validate the request and store the result in $validatedData
        // It's essential to assign the result of validate() to a variable.
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            // user_id is nullable, but if provided, it must exist and be unique among other teachers
            'user_id' => 'nullable|exists:users,id|unique:teachers,user_id,' . $teacher->id,
            'subject_taught' => 'required|string|max:255',
            'address' => 'nullable|string|max:255', // Assuming 'address' is part of the form
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Validation for the new image file

            'status' => 'required|integer|in:0,1', // Assuming 0/1 from select input
        ]);

        // 2. Initialize the image path for update.
        // By default, we keep the existing image path.
        $imagePathToSave = $teacher->image;

        // 3. Handle explicit image removal (if the 'Remove Current Image' checkbox was checked)
        if (isset($validatedData['clear_image']) && $validatedData['clear_image']) {
            // Delete the old image file from storage if it exists
            if ($teacher->image) {
                Storage::disk('public')->delete($teacher->image);
            }
            // Set the image path to null, indicating no image
            $imagePathToSave = null;
        }

        // 4. Handle new image upload (this takes precedence over clearing)
        if ($request->hasFile('image')) {
            // Delete the old image if a new one is being uploaded
            if ($teacher->image) {
                Storage::disk('public')->delete($teacher->image);
            }
            // Store the new image and get its path
            $imagePathToSave = $request->file('image')->store('teachers', 'public');
        }

        // 5. Update the teacher record
        // Use the $validatedData for all common fields, and $imagePathToSave for the image.
        $teacher->update([
            'name' => $validatedData['name'],
            'user_id' => $validatedData['user_id'],
            'subject_taught' => $validatedData['subject_taught'],
            'image' => $imagePathToSave, // Use the determined image path
            'status' => $validatedData['status'],
        ]);

        return redirect()->route('teachers.index')
                         ->with('success', 'Teacher updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Teacher $teacher)
    {
        // Delete associated image file
        if ($teacher->image_path && Storage::disk('public')->exists($teacher->image_path)) {
            Storage::disk('public')->delete($teacher->image_path);
        }
        $teacher->delete();

        return redirect()->route('teachers.index')
                         ->with('success', 'Teacher deleted successfully.');
    }
}