<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\BusSchedule; // Assuming you will create this model
use App\Models\ClassName;    // To fetch class names for dropdowns
use App\Models\Student;    // To fetch class names for dropdowns
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BusScheduleController extends Controller
{
    /**  
     * Display a listing of the bus schedules.
    */
    public function index(Request $request)
    {
        $query = BusSchedule::with(['className' => function ($q) {
            $q->where('status', 0)->select('id', 'class_name');
        }]); // Eager load the related ClassName with status = 0

        // Filtering logic
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('bus_number', 'like', $searchTerm)
                    ->orWhere('route_name', 'like', $searchTerm)
                    ->orWhere('driver_name', 'like', $searchTerm)
                    ->orWhereHas('className', function ($classQuery) use ($searchTerm) {
                        $classQuery->where('status', 0)->where('class_name', 'like', $searchTerm);
                    });
            });
        }

        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Added filtering by class_id if present in request
        if ($request->filled('class_id') && $request->class_id !== '') {
            $query->where('class_id', $request->class_id);
        }

        $busSchedules = $query->orderBy('departure_time')->paginate(10);

        // Fetch unique class names with the first corresponding id, sorted by class_name
        $classNames = ClassName::where('status', 0)
            ->select('id', 'class_name')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('class_names')
                    ->where('status', 0)
                    ->groupBy('class_name');
            })
            ->orderBy('class_name')
            ->get();

        return Inertia::render('BusSchedules/Index', [
            'busSchedules' => $busSchedules,
            'filters' => $request->only(['search', 'status', 'class_id']), // Pass class_id filter
            'classNames' => $classNames, // Pass classNames to the Vue component
            'flash' => session('flash'),
        ]);
    }

    /**
     * Show the form for creating a new bus schedule.
    */
    public function create()
    {
        // Fetch unique class names with the first corresponding id, sorted by class_name
        $classNames = ClassName::where('status', 0)
            ->select('id', 'class_name')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('class_names')
                    ->where('status', 0)
                    ->groupBy('class_name');
            })
            ->orderBy('class_name')
            ->get();

        return Inertia::render('BusSchedules/Create', [
            'classNames' => $classNames,
            'flash' => session('flash'),
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[],
        ]);
    }

    /**
     * Store a newly created bus schedule in storage.
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'bus_number' => 'required|string|max:255|unique:bus_schedules,bus_number',
            'route_name' => 'required|string|max:255',
            'departure_time' => 'required|date_format:H:i', // HH:MM format
            'arrival_time' => 'required|date_format:H:i|after:departure_time',
            'driver_name' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'status' => 'required|integer|in:0,1', // 0: Active, 1: Inactive, 
            'class_id' => 'nullable|exists:class_names,id', // Validate foreign key
        ]);

        DB::beginTransaction();
        try {
            BusSchedule::create($validated);
            DB::commit();

            return redirect()->route('bus-schedules.index')->with('flash', [
                'message' => 'Bus schedule for "' . $validated['bus_number'] . '" created successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bus schedule creation failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while creating the bus schedule. Please try again.',
                'type' => 'error'
            ])->withInput(); // Keep old input in case of error
        }
    }

    /**
     * Show the form for editing the specified bus schedule.
    */
    public function edit(BusSchedule $busSchedule)
    {
        // Fetch unique class names with the first corresponding id, sorted by class_name
        $classNames = ClassName::where('status', 0)
            ->select('id', 'class_name')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('class_names')
                    ->where('status', 0)
                    ->groupBy('class_name');
            })
            ->orderBy('class_name')
            ->get();

        return Inertia::render('BusSchedules/Edit', [
            'busSchedule' => $busSchedule,
            'classNames' => $classNames,
            'flash' => session('flash'),
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[],
        ]);
    }

    /**
     * Update the specified bus schedule in storage.
    */
    public function update(Request $request, BusSchedule $busSchedule)
    {
        $validated = $request->validate([
            'bus_number' => ['required', 'string', 'max:255', Rule::unique('bus_schedules', 'bus_number')->ignore($busSchedule->id)],
            'route_name' => 'required|string|max:255',
            'departure_time' => 'required|date_format:H:i',
            'arrival_time' => 'required|date_format:H:i|after:departure_time',
            'driver_name' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:0',
            'status' => 'required|integer|in:0,1',
            'class_id' => 'nullable|exists:class_names,id',
        ]);

        DB::beginTransaction();
        try {
            $busSchedule->update($validated);
            DB::commit();

            return redirect()->route('bus-schedules.index')->with('flash', [
                'message' => 'Bus schedule for "' . $busSchedule->bus_number . '" updated successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bus schedule update failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while updating the bus schedule. Please try again.',
                'type' => 'error'
            ])->withInput();
        }
    }

    /**
     * Remove the specified bus schedule from storage.
    */
    public function destroy(BusSchedule $busSchedule)
    {
        DB::beginTransaction();
        try {
            $busSchedule->delete();
            DB::commit();

            return redirect()->back()->with('flash', [
                'message' => 'Bus schedule for "' . $busSchedule->bus_number . '" deleted successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bus schedule deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while deleting the bus schedule. Please try again.',
                'type' => 'error'
            ]);
        }
    }



    // Student Side to show Bus Schedule

     /**
     * Display the bus schedule for the authenticated student.
     */
    public function myBusSchedule()
    {
        // Get the authenticated user
        $user = auth()->user();


    // dd($user);
        // Find the student record associated with the user
        $student = Student::where('user_id', $user->id)->first();

    // dd($student);
        $busSchedule = null;
        if ($student && $student->class_id) {
            // Fetch the bus schedule linked to the student's class
            // Assuming one-to-one or one-to-many where a class has one primary bus schedule
            $busSchedule = BusSchedule::where('class_id', $student->class_id)
                                      ->orderBy('departure_time')
                                      ->first(); // Get the first matching schedule

            //  dd($busSchedule); // Temporarily dump the bus schedule found
        }

        return Inertia::render('StudentBusSchedule/Index', [
            'busSchedule' => $busSchedule, // Pass the single schedule or null
            'studentName' => $student ? $student->user->name : 'Student', // Pass student name for display
            'className' => $student && $student->className ? $student->className->class_name : 'N/A', // Pass class name
        ]);
    }
}
