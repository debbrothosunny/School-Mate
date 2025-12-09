<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users with their roles.
    */
    public function index()
    {
        $users = User::with('roles')->orderBy('id', 'desc')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'contact_info' => $user->contact_info, // Ensure this exists if you added the migration
                'roles' => $user->roles->pluck('name')->toArray(), // Get role names
            ];
        });

        $availableRoles = Role::all()->pluck('name'); // Get all role names for dropdown

        return Inertia::render('Admin/UserManagement/Index', [
            'users' => $users,
            'availableRoles' => $availableRoles,
        ]);
    }

    /**
     * Assign/update a role for a specific user.
    */
    
    public function assignRole(Request $request, User $user)
    {
        $request->validate([
            'role' => ['nullable', 'string', 'exists:roles,name'], // Validate selected role
        ]);

        // Remove all current roles and assign the new one
        // Use syncRoles if user should only have one role at a time
        $user->syncRoles($request->role);

        return redirect()->back()->with('success', 'User role updated successfully.');
    }

   public function teacherLogin(Request $request)
   {
    // 1. Validate both joining_number and password
    $request->validate([
        'joining_number' => ['required', 'string'],
        'password' => ['required', 'string'], // Added password validation
    ]);

    // 2. Attempt to find the teacher by joining number
    $teacher = Teacher::where('joining_number', $request->joining_number)->first();

    // Check if the teacher exists
    if (!$teacher) {
        return back()->withErrors([
            'joining_number' => 'Invalid credentials.',
        ])->onlyInput('joining_number');
    }

    // 3. Find the associated user account
    $user = User::find($teacher->user_id);

    // Check if a user account was actually found for the teacher
    if (!$user) {
        return back()->withErrors([
            'joining_number' => 'Invalid credentials.',
        ])->onlyInput('joining_number');
    }

    // 4. Securely verify the provided password against the stored hash
    if (Hash::check($request->password, $user->password)) {
        // Password is correct, log the user in
        Auth::login($user);
        $request->session()->regenerate();
        
        // Use a generic error message for security (prevents user enumeration)
        return redirect()->intended(route('teacher.dashboard'));
    }

    // 5. If the password verification failed
    return back()->withErrors([
        'joining_number' => 'Invalid credentials.', // Generic failure message
    ])->onlyInput('joining_number');
    }


    public function studentLogin(Request $request)
    {
        // WARNING: This authentication method is highly insecure as it does not require a password.
        // It should only be used in a development environment or for demonstration purposes.
        $request->validate([
            'admission_number' => ['required', 'string'],
        ]);

        $student = Student::where('admission_number', $request->admission_number)->first();

        if ($student) {
            $user = User::find($student->user_id);

            if ($user) {
                Auth::login($user);
                $request->session()->regenerate();
                return redirect()->intended(route('student.dashboard'));
            }
        }

        return back()->withErrors([
            'admission_number' => 'Invalid admission number.',
        ])->onlyInput('admission_number');
    }
}