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
    /**
     * Display a listing of the notices for admin.
     */
    public function index(Request $request)
    {
        $query = Notice::with('creator'); // Eager load the creator (user) relationship

        // Basic search filter for title or content
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('title', 'like', $searchTerm)
                  ->orWhere('content', 'like', $searchTerm);
            });
        }

        // Filter by status (0: Active, 1: Inactive)
        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by target user role (e.g., 'student', 'teacher', 'parent', 'all')
        if ($request->filled('target_user') && $request->target_user !== '') {
            $query->whereJsonContains('target_user', $request->target_user);
        }

        $notices = $query->orderBy('notice_date', 'desc') // Order by latest notice date
                         ->orderBy('created_at', 'desc') // Then by creation date
                         ->paginate(10); // Paginate results

        // Get all possible roles for the target_user filter dropdown
        $availableRoles = ['all', 'student', 'teacher'];

        return Inertia::render('Notices/Index', [
            'notices' => $notices,
            'filters' => $request->only(['search', 'status', 'target_user']),
            'availableRoles' => $availableRoles, // Pass roles for the filter dropdown
            'flash' => session('flash'),
        ]);
    }

    /**
     * Show the form for creating a new notice.
     */
    public function create()
    {
        // Get all possible roles for the target_user selection
        $availableRoles = ['all', 'student', 'teacher'];

        return Inertia::render('Notices/Create', [
            'availableRoles' => $availableRoles,
            'flash' => session('flash'),
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[],
        ]);
    }

    /**
     * Store a newly created notice in storage.
    */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'notice_date' => 'nullable|date',
            'status' => 'required|integer|in:0,1', // 0: Active, 1: Inactive
            'target_user' => 'required|array', // Must be an array of selected roles
            'target_user.*' => 'string|in:all,student,teacher', // Each item in array must be a valid role
        ]);

        try {
            $notice = Notice::create([ // Store the created notice in a variable
                'title' => $validated['title'],
                'content' => $validated['content'],
                'notice_date' => $validated['notice_date'],
                'status' => $validated['status'],
                'target_user' => $validated['target_user'], // Stored as JSON array
                'created_by' => Auth::id(), // Set the creator to the authenticated user's ID
            ]);

            // Dispatch the real-time event if the notice is active and targets teachers or all
            // You might want to refine this logic based on your exact notification needs
            if ($notice->status === 0 && (in_array('teacher', $notice->target_user) || in_array('all', $notice->target_user))) {
                event(new NewNoticeCreated($notice));
            }

            return redirect()->route('notices.index')->with('flash', [
                'message' => 'Notice "' . $validated['title'] . '" created successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            Log::error('Notice creation failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while creating the notice. Please try again.',
                'type' => 'error'
            ])->withInput();
        }
    }

    /**
     * Show the form for editing the specified notice.
     */
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

    /**
     * Update the specified notice in storage.
     */
    public function update(Request $request, Notice $notice)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'notice_date' => 'nullable|date',
            'status' => 'required|integer|in:0,1',
            'target_user' => 'required|array',
            'target_user.*' => 'string|in:all,student,teacher',
        ]);

        try {
            $notice->update([
                'title' => $validated['title'],
                'content' => $validated['content'],
                'notice_date' => $validated['notice_date'],
                'status' => $validated['status'],
                'target_user' => $validated['target_user'],
            ]);

            // You might want to dispatch an event for updates too,
            // or a different event specifically for updates.
            // For simplicity, we'll focus on creation for now as per your request.

            return redirect()->route('notices.index')->with('flash', [
                'message' => 'Notice "' . $validated['title'] . '" updated successfully!',
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

    /**
     * Remove the specified notice from storage.
     */
    public function destroy(Notice $notice)
    {
        try {
            $notice->delete();

            return redirect()->back()->with('flash', [
                'message' => 'Notice "' . $notice->title . '" deleted successfully!',
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
                         ->orderBy('notice_date', 'desc') // Order by latest notice date
                         ->orderBy('created_at', 'desc') // Then by creation date
                         ->paginate(10);

        return Inertia::render('StudentNotices/MyNotices', [ // This will be the Vue component for displaying notices to users
            'notices' => $notices,
            'flash' => session('flash'),
        ]);
    }
}