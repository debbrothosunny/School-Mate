<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role; // Don't forget to import Role

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
}