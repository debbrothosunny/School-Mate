<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ClassName; // Assuming your class model is ClassName
use App\Models\Teacher;   // Assuming your teacher model is Teacher
use App\Models\User;

class MyClassesController extends Controller
{
    public function index(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        // Ensure the user is a teacher and has an associated teacher record
        // This is a crucial check to prevent unauthorized access or errors if a teacher profile is missing.
        if (!$user->hasRole('teacher') || !$user->teacher) {
            // Abort with a 403 Forbidden error if the user is not a teacher or lacks a teacher profile.
            // You might customize this to redirect to a different page or show a more user-friendly message.
            abort(403, 'Unauthorized. You are not assigned as a teacher or your teacher profile is incomplete.');
        }

        // Get the associated Teacher model for the authenticated user.
        $teacher = $user->teacher;

        // Fetch classes assigned to this teacher.
        // Eager load 'section' to get section names, and since class_names has 'day' and 'room_number',
        // those columns will automatically be included in the ClassName objects.
        $myClasses = $teacher->classNames()->with('section')->get();

        // Prepare the data to be passed to the Inertia frontend.
        // 'myClasses' will contain the collection of classes assigned to the teacher,
        // including 'day' and 'room_number' and 'section' relationships.
        // 'teacherName' will display the teacher's name (preferring the name from the Teacher model,
        // falling back to the User model's name if the Teacher model's name is not set).
        return Inertia::render('MyClasses/Index', [
            'myClasses' => $myClasses,
            'teacherName' => $teacher->name ?? $user->name,
        ]);
    }
}
