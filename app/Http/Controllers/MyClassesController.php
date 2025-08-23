<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ClassTime; // Assuming your class model is ClassName


class MyClassesController extends Controller
{
    public function index(Request $request)
    {
        // Get the authenticated user
        $user = $request->user();

        // Ensure the user is a teacher and has an associated teacher record
        if (!$user->hasRole('teacher') || !$user->teacher) {
            abort(403, 'Unauthorized. You are not assigned as a teacher or your teacher profile is incomplete.');
        }

        // Get the associated Teacher model for the authenticated user.
        $teacher = $user->teacher;

        // Fetch ClassTime entries specifically for this teacher.
        // Eager load all necessary relationships for display in the timetable.
        $teacherTimetable = ClassTime::query()
            ->where('teacher_id', $teacher->id) // Filter by the current teacher's ID
            ->with(['className', 'subject', 'section', 'session', 'room']) // Load related data
            ->orderBy('day_of_week') // Order for consistent timetable display
            ->orderBy('start_time')
            ->get();

        // Uncomment the line below to debug the data being sent to the Vue component
        // dd($teacherTimetable->toArray());

        return Inertia::render('MyClasses/Index', [
            // Only pass the teacher's name and their specific timetable data
            'teacherName' => $teacher->name ?? $user->name,
            'teacherTimetable' => $teacherTimetable,
        ]);
    }
}
