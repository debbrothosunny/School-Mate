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
        $subjects = Subject::latest('id')->paginate(10); // Changed to latest('id') for consistent ordering

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

    /**
     * Store a newly created subject in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:subjects,name',
            'code' => 'nullable|string|max:50|unique:subjects,code',
            'full_marks' => 'required|integer|min:1',
            'passing_marks' => 'required|integer|min:0|lte:full_marks',
            'status' => 'required|integer|in:0,1',
        ]);

        try {
            // Create a new subject record
            Subject::create($validatedData);

            // Redirect to the index page with a success flash message
            return redirect()->route('subjects.index')->with('flash', [
                'message' => 'Subject added successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            // Redirect with an error flash message if something goes wrong
            return redirect()->back()->with('flash', [
                'message' => 'Failed to add subject: ' . $e->getMessage(),
                'type' => 'error'
            ]);
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

    /**
     * Update the specified subject in storage.
     */
    public function update(Request $request, Subject $subject)
    {
        // Validate the incoming request data for update
        $validatedData = $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('subjects', 'name')->ignore($subject->id),
            ],
            'code' => [
                'nullable', 'string', 'max:50',
                Rule::unique('subjects', 'code')->ignore($subject->id),
            ],
            'full_marks' => 'required|integer|min:1',
            'passing_marks' => 'required|integer|min:0|lte:full_marks',
            'status' => 'required|integer|in:0,1',
        ]);

        try {
            // Update the subject record
            $subject->update($validatedData);

            // Redirect to the index page with a success flash message
            return redirect()->route('subjects.index')->with('flash', [
                'message' => 'Subject updated successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            // Redirect with an error flash message if something goes wrong
            return redirect()->back()->with('flash', [
                'message' => 'Failed to update subject: ' . $e->getMessage(),
                'type' => 'error'
            ]);
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
        $classes = ClassName::where('status', 0)->get(['id', 'class_name']); // Assuming 'class_name' for class names
        $subjects = Subject::where('status', 0)->get(['id', 'name', 'code']);
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

            return redirect()->route('class-subjects.index')->with('flash', [
                'message' => 'Class Subject added successfully!',
                'type' => 'success'
            ]);
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

        $classes = ClassName::where('status', 0)->get(['id', 'class_name']); // Assuming 'class_name' for class names
        $subjects = Subject::where('status', 0)->get(['id', 'name', 'code']);
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
