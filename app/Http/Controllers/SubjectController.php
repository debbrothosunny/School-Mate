<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\ClassSubject;
use App\Models\ClassName; 
use App\Models\Section; 
use App\Models\Teacher;  
use App\Models\Group;  
use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    /**
     * Display a listing of the subjects.
    */

    public function index(Request $request)
    {
        // Fetch all subjects, ordered by name, and paginate them
        $subjects = Subject::latest('id')->paginate(10); 

        // Pass subjects data to the Inertia.js view
        return Inertia::render('Subjects/Index', [
            'subjects' => $subjects,
            'flash' => session('flash'), // Pass flash messages
        ]);
    }

    /**
     * Show the form for creating a new subject.
     */
    public function create()
    {
        // Render the create subject form
        return Inertia::render('Subjects/Create');
    }

    // ------------------------------------------------------------------
    // ⬇️ STORE METHOD: ADDED VALIDATION FOR SUBJECTIVE/OBJECTIVE/PRACTICAL ⬇️
    // ------------------------------------------------------------------

    /**
     * Store a newly created subject in storage.
    */
    public function store(Request $request)
    {
        // 1. Basic Validation
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:subjects,name',
            
            // Breakdown - Full Marks
            'subjective_full_marks' => 'required|integer|min:0',
            'objective_full_marks'  => 'required|integer|min:0',
            'practical_full_marks'  => 'required|integer|min:0',

            // Breakdown - Passing Marks
            'subjective_passing_marks' => 'required|integer|min:0|lte:subjective_full_marks',
            'objective_passing_marks'  => 'required|integer|min:0|lte:objective_full_marks',
            'practical_passing_marks'  => 'required|integer|min:0|lte:practical_full_marks',
            
            // Total Marks (Now optional, as it will be calculated or validated against the sum)
            // We will set this manually to ensure it matches the sum.
            'full_marks'    => 'nullable|integer', 
            'passing_marks' => 'nullable|integer',
            
            'status' => 'required|integer|in:0,1',
        ]);
        
        // 2. Calculate and Validate Totals
        $totalFull = 
            $validatedData['subjective_full_marks'] + 
            $validatedData['objective_full_marks'] + 
            $validatedData['practical_full_marks'];
            
        $totalPassing = 
            $validatedData['subjective_passing_marks'] + 
            $validatedData['objective_passing_marks'] + 
            $validatedData['practical_passing_marks'];

        // Assign the calculated totals to the fields
        $validatedData['full_marks']    = $totalFull;
        $validatedData['passing_marks'] = $totalPassing;

        // Optionally, check if the total is zero (A subject must have some marks)
        if ($totalFull < 1) {
             return redirect()->back()->withErrors(['full_marks' => 'Total full marks must be greater than zero.'])
                ->withInput();
        }

        try {
            // Create a new subject record
            Subject::create($validatedData);

            // Redirect to the index page with a success flash message
            return redirect()->route('subjects.index')->with('flash', [
                'message' => 'বিষয়টি সফলভাবে যোগ করা হয়েছে!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            // Redirect with an error flash message if something goes wrong
            return redirect()->back()->with('flash', [
                'message' => 'Failed to add subject: ' . $e->getMessage(),
                'type' => 'error'
            ])->withInput();
        }
    }

    

    /**
     * Show the form for editing the specified subject.
     */
    public function edit(Subject $subject)
    {
        // Render the edit subject form, passing the subject data
        return Inertia::render('Subjects/Edit', [
            'subject' => $subject,
        ]);
    }

    // ------------------------------------------------------------------
    // ⬇️ UPDATE METHOD: APPLIED SAME LOGIC FOR SUBJECTIVE/OBJECTIVE/PRACTICAL ⬇️
    // ------------------------------------------------------------------
    
    /**
     * Update the specified subject in storage.
    */
    public function update(Request $request, Subject $subject)
    {
        // 1. Basic Validation
        $validatedData = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('subjects', 'name')->ignore($subject->id),
            ],
            
            // Breakdown - Full Marks
            'subjective_full_marks' => 'required|integer|min:0',
            'objective_full_marks'  => 'required|integer|min:0',
            'practical_full_marks'  => 'required|integer|min:0',

            // Breakdown - Passing Marks
            'subjective_passing_marks' => 'required|integer|min:0|lte:subjective_full_marks',
            'objective_passing_marks'  => 'required|integer|min:0|lte:objective_full_marks',
            'practical_passing_marks'  => 'required|integer|min:0|lte:practical_full_marks',
            
            // Total Marks (Now optional, as it will be calculated or validated against the sum)
            'full_marks'    => 'nullable|integer',
            'passing_marks' => 'nullable|integer',

            'status' => 'required|integer|in:0,1',
        ]);

        // 2. Calculate and Validate Totals
        $totalFull = 
            $validatedData['subjective_full_marks'] + 
            $validatedData['objective_full_marks'] + 
            $validatedData['practical_full_marks'];
            
        $totalPassing = 
            $validatedData['subjective_passing_marks'] + 
            $validatedData['objective_passing_marks'] + 
            $validatedData['practical_passing_marks'];

        // Assign the calculated totals to the fields
        $validatedData['full_marks']    = $totalFull;
        $validatedData['passing_marks'] = $totalPassing;

        // Optionally, check if the total is zero (A subject must have some marks)
        if ($totalFull < 1) {
             return redirect()->back()->withErrors(['full_marks' => 'Total full marks must be greater than zero.'])
                ->withInput();
        }

        try {
            // Update the subject record
            $subject->update($validatedData);

            // Redirect to the index page with a success flash message
            return redirect()->route('subjects.index')->with('flash', [
                'message' => 'বিষয়টি সফলভাবে আপডেট করা হয়েছে!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            // Redirect with an error flash message if something goes wrong
            return redirect()->back()->with('flash', [
                'message' => 'Failed to update subject: ' . $e->getMessage(),
                'type' => 'error'
            ])->withInput();
        }
    }

    /**
     * Remove the specified subject from storage.
    */
    public function destroy(Subject $subject)
    {
        try {
            // Delete the subject record
            $subject->delete();

            // Redirect to the index page with a success flash message
            return redirect()->route('subjects.index')->with('flash', [
                'message' => 'Subject deleted successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            // Redirect with an error flash message if something goes wrong
            return redirect()->back()->with('flash', [
                'message' => 'Failed to delete subject: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

   
    // Class Subject Functions

    public function classSubjectIndex()
    {
        // Eager load relationships, including 'teacher', 'section', and 'group'
        // Updated to use your provided syntax without a closure
        $classSubjects = ClassSubject::with(['className', 'subject', 'teacher', 'session', 'section', 'group'])
            ->latest('id')
            ->paginate(10);

               

        return Inertia::render('ClassSubjects/Index', [
            'classSubjects' => $classSubjects,
            'flash' => session('flash'),
        ]);
    }

    /**
     * Show the form for creating a new class subject.
    */

    public function classSubjectCreate()
    {
        // Fetch unique class names with the first corresponding id
        $classes = ClassName::where('status', 0)
            ->select('id', 'class_name')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('class_names')
                    ->where('status', 0)
                    ->groupBy('class_name');
            })
            ->get();

        $subjects = Subject::where('status', 0)->get(['id', 'name']);
        $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        $groups = Group::where('status', 0)->get(['id', 'name']); // Fetch groups

        return Inertia::render('ClassSubjects/Create', [
            'classes' => $classes,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'sessions' => $sessions,
            'sections' => $sections,
            'groups' => $groups, // Pass groups to the view
        ]);
    }

    /**
     * Store a newly created class subject in storage.
     */
    public function classSubjectStore(Request $request)
    {
        $validatedData = $request->validate([
            'class_name_id' => 'required|exists:class_names,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'session_id' => 'required|exists:class_sessions,id',
            'section_id' => 'required|exists:sections,id',
            'group_id' => 'nullable|exists:groups,id', // Added group_id validation
            'status' => 'required|integer|in:0,1',
        ]);

        // Check for existing class subject with the same class, subject, session, section, AND group
        $existingClassSubject = ClassSubject::where('class_name_id', $validatedData['class_name_id'])
            ->where('subject_id', $validatedData['subject_id'])
            ->where('session_id', $validatedData['session_id'])
            ->where('section_id', $validatedData['section_id'])
            ->where('group_id', $validatedData['group_id']) // Added group_id to uniqueness check
            ->first();

        if ($existingClassSubject) {
            return redirect()->back()->withErrors([
                'class_name_id' => 'This subject is already assigned to this class, session, section, and group combination.',
            ])->withInput();
        }

        try {
            ClassSubject::create($validatedData);

            // Redirect with a success message
            return redirect()->route('class-subjects.index')
                ->with('flash', ['type' => 'success', 'message' => 'Class schedule created successfully!']);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', [
                'message' => 'Failed to add class subject: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Show the form for editing the specified class subject.
    */
    public function classSubjectEdit(ClassSubject $classSubject)
    {
        // Load relationships, including 'group'
        $classSubject->load(['className', 'subject', 'teacher', 'session', 'section', 'group']);

        // Fetch unique class names with the first corresponding id
        $classes = ClassName::where('status', 0)
            ->select('id', 'class_name')
            ->whereIn('id', function ($query) {
                $query->select(DB::raw('MIN(id)'))
                    ->from('class_names')
                    ->where('status', 0)
                    ->groupBy('class_name');
            })
            ->get();

        $subjects = Subject::where('status', 0)->get(['id', 'name']);
        $teachers = Teacher::where('status', 0)->get(['id', 'name', 'subject_taught']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        $groups = Group::where('status', 0)->get(['id', 'name']); // Fetch groups

        return Inertia::render('ClassSubjects/Edit', [
            'classSubject' => $classSubject,
            'classes' => $classes,
            'subjects' => $subjects,
            'teachers' => $teachers,
            'sessions' => $sessions,
            'sections' => $sections,
            'groups' => $groups, // Pass groups to the view
        ]);
    }

    /**
     * Update the specified class subject in storage.
    */
    public function classSubjectUpdate(Request $request, ClassSubject $classSubject)
    {
        $validatedData = $request->validate([
            'class_name_id' => 'required|exists:class_names,id',
            'subject_id' => 'required|exists:subjects,id',
            'teacher_id' => 'nullable|exists:teachers,id',
            'session_id' => 'required|exists:class_sessions,id',
            'section_id' => 'required|exists:sections,id',
            'group_id' => 'nullable|exists:groups,id', // Added group_id validation
            'status' => 'required|integer|in:0,1',
        ]);

        // Check for existing class subject with the same class, subject, session, section, AND group, excluding current record
        $existingClassSubject = ClassSubject::where('class_name_id', $validatedData['class_name_id'])
                                            ->where('subject_id', $validatedData['subject_id'])
                                            ->where('session_id', $validatedData['session_id'])
                                            ->where('section_id', $validatedData['section_id'])
                                            ->where('group_id', $validatedData['group_id']) // Added group_id to uniqueness check
                                            ->where('id', '!=', $classSubject->id)
                                            ->first();

        if ($existingClassSubject) {
            return redirect()->back()->withErrors([
                'class_name_id' => 'This subject is already assigned to this class, session, section, and group combination.',
            ])->withInput();
        }

        try {
            $classSubject->update($validatedData);

            return redirect()->route('class-subjects.index')->with('flash', [
                'message' => 'Class Subject updated successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', [
                'message' => 'Failed to update class subject: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

    /**
     * Remove the specified class subject from storage.
    */
    public function classSubjectDestroy(ClassSubject $classSubject)
    {
        try {
            $classSubject->delete();

            return redirect()->route('class-subjects.index')->with('flash', [
                'message' => 'Class Subject deleted successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('flash', [
                'message' => 'Failed to delete class subject: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }

}
