<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SectionController extends Controller
{
    /**
     * Display a listing of the sections.
     */
    public function index()
    {
        $sections = Section::orderBy('name')->get();
        return Inertia::render('Admin/SectionManagement/Index', [
            'sections' => $sections,
        ]);
    }

    /**
     * Show the form for creating a new section.
     */
    public function create()
    {
        return Inertia::render('Admin/SectionManagement/Create');
    }

    /**
     * Store a newly created section in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:sections,name',
            'status' => 'required|boolean', // Validate status as boolean (0 or 1)
        ]);

        Section::create([
            'name' => $request->name,
            'status' => $request->status, // Save the status
        ]);

        return redirect()->route('sections.index')
                         ->with('success', 'Section created successfully.');
    }

    /**
     * Show the form for editing the specified section.
     */
    public function edit(Section $section)
    {
        return Inertia::render('Admin/SectionManagement/Edit', [
            'section' => $section,
        ]);
    }

    /**
     * Update the specified section in storage.
     */
     public function update(Request $request, Section $section)
    {
       
        $request->validate([
            'name' => 'required|string|max:255|unique:sections,name,' . $section->id,
            'status' => 'required|boolean', // 0 or 1
        ]);

        $section->update([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        // Add this line to set a flash message
        return redirect()->route('sections.index')->with('success', 'Section updated successfully.');
    }

    /**
     * Remove the specified section from storage (soft delete/deactivate).
     */
    public function destroy(Section $section)
    {
        $section->delete(); // This performs a hard delete
        return redirect()->route('sections.index')
                        ->with('success', 'Section deleted successfully.');
    }
}