<?php

namespace App\Http\Controllers;

use App\Models\ClassName; // Make sure this is imported
use App\Models\Group; // Make sure this is imported
use App\Models\Teacher; 
use App\Models\Section; 
use App\Models\ClassSubject; 
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Validation\Rules\RequiredIf;
use Inertia\Inertia; // Assuming you're using Inertia.js
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ClassNameController extends Controller
{
    // Make sure this trait is here!
    use AuthorizesRequests, ValidatesRequests;
    
    /**
     * Display a listing of the resource.
    */
    public function index()
    {
        // Get the search query from the request
        $search = request('search');

        $query = ClassName::orderBy('id');

        // Apply the search filter if a query is present
        if ($search) {
            $query->where('class_name', 'like', '%' . $search . '%')
                // Assuming 'status' is an integer, you'll need to handle it differently
                // or just search by class_name if you only want to search the string fields efficiently.
                // For simplicity and matching your Vue logic (searching both):
                ->orWhere('status', 'like', '%' . $search . '%');
        }

        $classNames = $query->paginate(10);

        // Append the search query to the pagination links (important!)
        // This makes sure that when clicking a pagination link, the search query is preserved.
        $classNames->appends(request()->only('search'));

        return Inertia::render('ClassNames/Index', [
            'classNames' => $classNames, // Pass the paginated data
            // For 'classes' prop name consistency with Vue, rename 'classNames' to 'classes' if you prefer,
            // but let's stick to 'classNames' for now and update Vue.
            'message' => session('message'),
            'type' => session('type'),
            // You might want to pass the current search term back to pre-fill the input
            'currentSearch' => $search,
        ]);
    }


    /**
     * Show the form for creating a new resource.
    */
    public function create()
    {
        // No related models (Teacher, Section, Group) are needed here anymore.
        return Inertia::render('ClassNames/Create');
    }

    /**
     * Store a newly created resource in storage.
    */
    public function store(Request $request)
    {
        $request->validate([
            // Validation for the basic class name and status
            'class_name' => 'required|string|max:255|unique:class_names,class_name',
            'status'     => 'required|in:0,1',
        ],
        [
            'class_name.unique' => 'এই ক্লাসের নাম ইতিমধ্যে বিদ্যমান।',
        ]);

        // Create the new class name entry
        ClassName::create([
            'class_name' => $request->class_name,
            'status' => $request->status,
        ]); 

        return redirect()->route('class-names.index')
            ->with('message', ['text' => 'ক্লাসের নাম সফলভাবে তৈরি হয়েছে!', 'type' => 'success']);
    }

    /**
     * Show the form for editing the specified resource.
    */
    public function edit(ClassName $className)
    {
        // Only pass the ClassName object itself
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
            // Class name must be unique, ignoring the current record
            'class_name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('class_names', 'class_name')->ignore($className->id),
            ],
            'status' => 'required|in:0,1',
        ],
        [
            'class_name.unique' => 'এই ক্লাসের নাম ইতিমধ্যে বিদ্যমান।',
        ]);

        // Update the class name entry
        $className->update($request->only(['class_name', 'status']));

        return redirect()->route('class-names.index')
            ->with('message', ['text' => 'ক্লাসের নাম সফলভাবে আপডেট হয়েছে!', 'type' => 'success']);
    }

    /**
     * Remove the specified resource from storage.
    */

    public function destroy(ClassName $className)
    {
        // 1. Check if the current user is authorized to perform the 'delete' action
        // on this specific $className instance.
        $this->authorize('delete', $className); // Laravel will throw an exception if not authorized

        try {
            $className->delete();
            return redirect()->back()->with(['message' => 'Class deleted successfully!', 'type' => 'success']);
        } catch (\Exception $e) {
            // ...
        }
    }
    

    // Group Functions

    public function groupIndex()
    {
        $groups = Group::orderBy('name')->paginate(10);

        return Inertia::render('Groups/Index', [
            'groups' => $groups,
            // Pass flash data explicitly as message and type (not nested in flash)
            'message' => session('message'),
            'type' => session('type'),
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

    return redirect()->route('groups.index')->with([
        'message' => 'Group updated successfully.',
        'type' => 'success',
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
