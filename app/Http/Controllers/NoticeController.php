<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Events\NewNoticeCreated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class NoticeController extends Controller
{

    // NOtice listing with filters and pagination
    public function index(Request $request)
    {
        // Eager load the creator relationship for each notice.
        $query = Notice::with('creator');

        // Basic search filter for notice title or content.
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('notice_title', 'like', $searchTerm)
                  ->orWhere('content', 'like', $searchTerm);
            });
        }

        // Filter by status (0: Draft, 1: Published).
        // A status of '1' means the notice is active/published.
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', (int) $request->status);
        }

        // Filter by target user role.
        if ($request->filled('target_user') && $request->target_user !== '') {
            $query->whereJsonContains('target_user', $request->target_user);
        }

        $notices = $query->orderBy('start_date', 'desc') // Order by latest start date
                         ->orderBy('created_at', 'desc') // Then by creation date
                         ->paginate(10); // Paginate results

        // Get all possible roles for the target_user filter dropdown.
        $availableRoles = ['all', 'student', 'teacher'];

        return Inertia::render('Notices/Index', [
            'notices' => $notices,
            'filters' => $request->only(['search', 'status', 'target_user']),
            'availableRoles' => $availableRoles,
            'flash' => session('flash'),
        ]);
    }

    
    public function create()
    {
        $availableRoles = ['all', 'student', 'teacher'];

        return Inertia::render('Notices/Create', [
            'availableRoles' => $availableRoles,
            'flash' => session('flash'),
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[],
        ]);
    }

    
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validated = $request->validate([
            'notice_title' => 'required|string|max:255',
            'content' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|integer|in:0,1',
            'target_user' => 'required|array',
            'target_user.*' => 'string|in:all,student,teacher',
        ]);

        try {
            // Create the new notice in the database
            $notice = Notice::create([
                'notice_title' => $validated['notice_title'],
                'content' => $validated['content'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => $validated['status'],
                'target_user' => $validated['target_user'],
                'created_by' => Auth::id(),
            ]);

            // Dispatch the real-time event only if the notice is active (status === 0)
            if ($notice->status === 0) {
                event(new NewNoticeCreated($notice));
            }

            // Redirect with a success flash message
            return redirect()->route('notices.index')->with('flash', [
                'message' => 'Notice "' . $validated['notice_title'] . '" created successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            // Log any errors that occur during creation
            Log::error('Notice creation failed: ' . $e->getMessage());

            // Redirect back with an error flash message and old input
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while creating the notice. Please try again.',
                'type' => 'error'
            ])->withInput();
        }
    }

   
    public function edit(Notice $notice)
    {
        $availableRoles = ['all', 'student', 'teacher'];

        return Inertia::render('Notices/Edit', [
            'notice' => $notice,
            'availableRoles' => $availableRoles,
            'flash' => session('flash'),
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[],
        ]);
    }

   
    public function update(Request $request, Notice $notice)
    {
        $validated = $request->validate([
            'notice_title' => 'required|string|max:255',
            'content' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|integer|in:0,1',
            'target_user' => 'required|array',
            'target_user.*' => 'string|in:all,student,teacher',
       
        ]);

        try {
            $notice->update([
                'notice_title' => $validated['notice_title'],

                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'status' => $validated['status'],
                'target_user' => $validated['target_user'],
          
            ]);

            return redirect()->route('notices.index')->with('flash', [
                'message' => 'Notice "' . $validated['notice_title'] . '" updated successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error('Notice update failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while updating the notice. Please try again.',
                'type' => 'error'
            ])->withInput();
        }
    }

    
    public function destroy(Notice $notice)
    {
        try {
            $notice->delete();

            return redirect()->back()->with('flash', [
                'message' => 'Notice "' . $notice->notice_title . '" deleted successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error('Notice deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while deleting the notice. Please try again.',
                'type' => 'error'
            ]);
        }
    }

    /**
     * Display notices for a specific authenticated user role.
     * This method will be used by students, teachers, and parents.
    */
    public function myNotices(Request $request)
    {
        $user = Auth::user();
        $userRoles = $user->getRoleNames()->toArray(); // Get roles assigned to the user

        $relevantRoles = ['all']; // Always include 'all' for general notices
        if (in_array('student', $userRoles)) {
            $relevantRoles[] = 'student';
        }
        if (in_array('teacher', $userRoles)) {
            $relevantRoles[] = 'teacher';
        }

        // Fetch notices that are active and target any of the user's roles or 'all'
        $notices = Notice::active() // Use the active scope
                         ->where(function ($query) use ($relevantRoles) {
                             foreach ($relevantRoles as $role) {
                                 $query->orWhereJsonContains('target_user', $role);
                             }
                         })
                         ->orderBy('start_date', 'desc') // Order by latest notice date
                         ->orderBy('created_at', 'desc') // Then by creation date
                         ->paginate(10);

        return Inertia::render('StudentNotices/MyNotices', [ // This will be the Vue component for displaying notices to users
            'notices' => $notices,
            'flash' => session('flash'),
        ]);
    }


}