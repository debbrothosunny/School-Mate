<?php

namespace App\Http\Controllers;

use App\Models\ClassName; // Make sure this is imported
use App\Models\Group; // Make sure this is imported
use Illuminate\Http\Request;
use Inertia\Inertia; // Assuming you're using Inertia.js
use Illuminate\Validation\Rule;

class ClassNameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all classes without any relationships
        $classes = ClassName::paginate(10);

        return Inertia::render('ClassNames/Index', [
            'classes' => $classes,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // No need to fetch teachers or sections as they are not directly linked to ClassName
        return Inertia::render('ClassNames/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'class_name' => 'required|string|max:255|unique:class_names,class_name', // Added unique rule
            'status' => 'required|integer|in:0,1',
            'total_classes' => 'required|integer|min:0', // Added validation for total_classes
        ]);

        ClassName::create([
            'class_name' => $request->class_name,
            'status' => $request->status,
            'total_classes' => $request->total_classes, // Store total_classes
        ]);

        return redirect()->route('class-names.index')->with('flash', [
            'message' => 'Class created successfully!',
            'type' => 'success'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ClassName $className)
    {
        // This method was empty in your provided code, and remains so.
        // If you need to display a single class, you can implement it here.
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ClassName $className)
    {
        // No need to fetch teachers or sections as they are not directly linked to ClassName
        return Inertia::render('ClassNames/Edit', [
            'className' => $className,
        ]);
        
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ClassName $className)
    {
        $request->validate([
            // Added unique rule ignoring the current class_name's ID
            'class_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('class_names', 'class_name')->ignore($className->id),
            ],
            'status' => 'required|integer|in:0,1',
            'total_classes' => 'required|integer|min:0', // Added validation for total_classes
        ]);

        $className->update([
            'class_name' => $request->class_name,
            'status' => $request->status,
            'total_classes' => $request->total_classes, // Update total_classes
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
        try {
            $className->delete();
            return redirect()->back()->with('flash', ['message' => 'Class deleted successfully!', 'type' => 'success']);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', ['message' => 'Error deleting class: ' . $e->getMessage(), 'type' => 'error']);
        }
    }



    // Group Functions

    public function groupIndex()
    {
        $groups = Group::orderBy('name')->paginate(10);
        return Inertia::render('Groups/Index', [
            'groups' => $groups,
            'flash' => session('flash'),
        ]);
    }



     public function groupCreate()
    {
        // Pass the allowed group types to the frontend for dropdowns/checkboxes
        return Inertia::render('Groups/Create', [
            'groupTypes' => Group::GROUP_TYPES,
        ]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function groupStore(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:groups,name', Rule::in(Group::GROUP_TYPES)], // Enforce allowed names
            'status' => 'required|boolean',
        ]);

        Group::create($validated);

        return redirect()->route('groups.index')->with('flash', [
            'message' => 'Group created successfully.',
            'type' => 'success'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function groupEdit(Group $group)
    {
        return Inertia::render('Groups/Edit', [
            'group' => $group,
            'groupTypes' => Group::GROUP_TYPES, // Pass allowed types for editing
        ]);
    }

    /**
     * Update the specified resource in storage.
    */
    public function groupUpdate(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('groups', 'name')->ignore($group->id), Rule::in(Group::GROUP_TYPES)], // Enforce allowed names
            'status' => 'required|boolean',
        ]);

        $group->update($validated);

        return redirect()->route('groups.index')->with('flash', [
            'message' => 'Group updated successfully.',
            'type' => 'success'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function groupDestroy(Group $group)
    {
        // Add logic here to prevent deletion if group is associated with students
        $group->delete();
        return redirect()->route('groups.index')->with('flash', [
            'message' => 'Group deleted successfully.',
            'type' => 'success'
        ]);
    }
}
