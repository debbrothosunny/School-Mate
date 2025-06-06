<?php

namespace App\Http\Controllers;

use App\Models\ClassName;
use App\Models\Teacher;
use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClassNameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $classes = ClassName::with(['teacher', 'section'])->paginate(10);

        return Inertia::render('ClassNames/Index', [
            'classes' => $classes,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get active teachers (for dropdown and subject auto-display)
        $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
        // Get active sections (for dropdown)
        $sections = Section::where('status', 0)->get(['id', 'name']);

        return Inertia::render('ClassNames/Create', [
            'teachers' => $teachers,
            'sections' => $sections,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'section_id' => 'required|exists:sections,id',
            'name' => 'required|string|max:255',
            'class_time' => 'required|string|max:255',
            'day' => 'required|string|max:255', 
            'room_number' => 'required|string|max:20',
            'status' => 'required|integer|in:0,1', // 0 or 1
        ]);

        ClassName::create([
            'teacher_id' => $request->teacher_id,
            'section_id' => $request->section_id,
            'name' => $request->name,
            'class_time' => $request->class_time,
            'day' => $request->day, 
            'room_number' => $request->room_number, 
            'status' => $request->status,
        ]);

        return redirect()->route('class-names.index')->with('flash', [
            'message' => 'Class created successfully!',
            'type' => 'success'
        ]);
    }

    /**
     * Display the specified resource.
     */
 

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClassName $className)
    {
        $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        return Inertia::render('ClassNames/Edit', [
            'className' => $className->load(['teacher', 'section']),
            'teachers' => $teachers,
            'sections' => $sections,
        ]);
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ClassName $className)
    {
        $request->validate([
            'teacher_id' => 'required|exists:teachers,id',
            'section_id' => 'required|exists:sections,id',
            'name' => 'required|string|max:255',
            'class_time' => 'required|string|max:255',
            'day' => 'required|string|max:255', 
            'room_number' => 'required|string|max:20', 
            'status' => 'required|integer|in:0,1',
        ]);

        $className->update([
            'teacher_id' => $request->teacher_id,
            'section_id' => $request->section_id,
            'name' => $request->name,
            'class_time' => $request->class_time,
            'day' => $request->day, 
            'room_number' => $request->room_number, 
            'status' => $request->status,
        ]);

        return redirect()->route('class-names.index')->with('flash', [
            'message' => 'Class updated successfully!',
            'type' => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ClassName $className)
    {
        $className->delete();
        return redirect()->route('class-names.index')->with('flash', [
            'message' => 'Class deleted successfully!',
            'type' => 'success'
        ]);
    }
}