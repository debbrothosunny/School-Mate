<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;


class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
    */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
    */  
     public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'contact_info' => 'required|string|max:20',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'contact_info' => $request->contact_info,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
     }





    public function checkContactInfo(Request $request)
    {
        $email = $request->input('email');
        $contactInfo = $request->input('contact_info');

        $errors = [];

        if ($email && User::where('email', $email)->exists()) {
            $errors['email'] = ['This email is already registered.'];
        }

        if ($contactInfo && User::where('contact_info', $contactInfo)->exists()) {
            $errors['contact_info'] = ['This contact number is already registered.'];
        }

        if (count($errors) > 0) {
            return response()->json(['errors' => $errors], 422); // Use 422 Unprocessable Entity
        }

        return response()->json(['message' => 'Available'], 200);
    }
}
