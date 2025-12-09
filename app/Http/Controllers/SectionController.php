<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
class SectionController extends Controller
{
     // Make sure this trait is here!
    use AuthorizesRequests, ValidatesRequests;
    /**
     * Display a listing of the sections.
    */
    public function index()
    {
        $sections = Section::latest('id')->get();

        return Inertia::render('Admin/SectionManagement/Index', [
            'sections' => $sections,
            'flash' => session('flash'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/SectionManagement/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:sections,name',
            'status' => 'required|integer|in:0,1',
        ]);

        DB::beginTransaction();
        try {
            Section::create($validated);
            DB::commit();

        return redirect()->route('sections.index')->with('flash', [
                'message' => 'Session "' . $validated['name'] . '" created successfully!',
                'type' => 'success'
            ]);



            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Section creation failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while creating the section. Please try again.',
                'type' => 'error'
            ])->withInput();
        }
    }

    public function edit(Section $section)
    {
        return Inertia::render('Admin/SectionManagement/Edit', [
            'section' => $section,
            'flash' => session('flash'),
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[],
        ]);
    }

    public function update(Request $request, Section $section)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('sections', 'name')->ignore($section->id)],
            'status' => 'required|integer|in:0,1',
        ]);

        DB::beginTransaction();
        try {
            $section->update($validated);
            DB::commit();

            return redirect()->route('sections.index')->with('flash', [
                'message' => 'Section "' . $section->name . '" updated successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Section update failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while updating the section. Please try again.',
                'type' => 'error'
            ])->withInput();
        }
    }

    public function destroy(Section $section)
    {
        // 1. CRITICAL SECURITY CHECK (Authorization)
        // This immediately stops the request if the user is not allowed to delete this section.
        // It prevents unauthorized access, regardless of front-end tampering.
        $this->authorize('delete', $section); // Will throw 403 Forbidden if not authorized

        DB::beginTransaction();
        
        try {
            // 2. Database Operation
            $section->delete();
            
            // 3. Commit Transaction
            DB::commit();

            return redirect()->back()->with('flash', [
                'message' => 'Section "' . $section->name . '" deleted successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            // 4. Rollback Transaction on Failure
            DB::rollBack();
            
            // Log the actual error for debugging
            Log::error('Section deletion failed: ' . $e->getMessage()); 
            
            // Note: You should generally avoid catching a 403 exception here.
            // If the exception came from $this->authorize(), it will typically
            // propagate before DB::beginTransaction() is called.
            
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while deleting the section. Please try again.',
                'type' => 'error'
            ]);
        }
    }




    // Session Create Functions


    public function sessionIndex(Request $request)
    {
        $query = ClassSession::query();

        // Optional: Add search/filter logic if needed for sessions
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', (bool) $request->status);
        }

        $sessions = $query->latest('id')->paginate(10);

        return Inertia::render('Sessions/Index', [
            'sessions' => $sessions,
            'filters' => $request->only(['search', 'status']),
            'flash' => session('flash'),
        ]);
    }

    
    public function sessionCreate()
    {
        return Inertia::render('Sessions/Create');
    }

   
    public function sessionStore(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:class_sessions,name', // Assuming 'name' column for sessions
            'status' => 'required|integer|in:0,1',
        ]);

        DB::beginTransaction();
        try {
            ClassSession::create($validated);
            DB::commit();

            return redirect()->route('sessions.index')->with('flash', [
                'message' => 'Session "' . $validated['name'] . '" created successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Session creation failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while creating the session. Please try again.',
                'type' => 'error'
            ])->withInput();
        }
    }

    
    public function sessionEdit(ClassSession $session) // Laravel will attempt to bind {section} to ClassSession
    {
        return Inertia::render('Sessions/Edit', [
            'session' => $session,
            'flash' => session('flash'),
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[],
        ]);
    }

  
    public function sessionUpdate(Request $request, ClassSession $session) // Laravel will attempt to bind {section} to ClassSession
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('class_sessions', 'name')->ignore($session->id)],
            'status' => 'required|integer|in:0,1',
        ]);

        DB::beginTransaction();
        try {
            $session->update($validated);
            DB::commit();

            return redirect()->route('sessions.index')->with('flash', [
                'message' => 'Session "' . $session->name . '" updated successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Session update failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while updating the session. Please try again.',
                'type' => 'error'
            ])->withInput();
        }
    }

 
    
    public function sessionDestroy(ClassSession $session) // Laravel will attempt to bind {section} to ClassSession
    {
        DB::beginTransaction();
        try {
            $session->delete();
            DB::commit();

            return redirect()->back()->with('flash', [
                'message' => 'Session "' . $session->name . '" deleted successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Session deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while deleting the session. Please try again.',
                'type' => 'error'
            ]);
        }
    }
}