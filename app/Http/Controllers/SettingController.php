<?php
// app/Http/Controllers/SettingController.php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File; // <-- Make sure this is here
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::firstOrCreate([]);
        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'flash' => session('flash'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Settings/Create');
    }

    
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'school_name' => 'required|string|max:255',
                'address' => 'nullable|string',
                'phone_number' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'principal_name' => 'required|string|max:255',
                'principal_signature' => 'nullable|string|max:255',
                'school_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
                'current_session' => 'nullable|string|max:255',
            ]);

            $logoPath = null;
            // Handle logo upload using the direct file move logic
            if ($request->hasFile('school_logo')) {
                $file = $request->file('school_logo');
                
                // Define the destination directory: assets/image in the project root (base_path)
                $destinationPath = base_path('assets/image');
                
                // Ensure the directory exists
                if (!File::isDirectory($destinationPath)) {
                    // Creates directory recursively if it doesn't exist
                    File::makeDirectory($destinationPath, 0777, true, true);
                }
                
                // Create a unique file name
                $fileName = time() . '_logo_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                
                // ACTION: Move the file using native PHP move function
                $file->move($destinationPath, $fileName);
                
                // Set the path for the database (relative to the project root)
                $logoPath = 'assets/image/' . $fileName;
            }

            // Merge the final path back into validated data
            $validated['school_logo'] = $logoPath; 

            // Use updateOrCreate to ensure only one settings record exists
            Setting::updateOrCreate([], $validated);

            DB::commit();

            return redirect()->route('settings.index')->with('flash', [
                'message' => 'Settings created successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Settings creation/update failed: ' . $e->getMessage()); 
            
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while saving the settings. Please try again.',
                'type' => 'error'
            ])->withInput();
        }
    }   

    public function edit()
    {
        $settings = Setting::firstOrCreate([]);
        return Inertia::render('Admin/Settings/Edit', [
            'settings' => $settings,
            'flash' => session('flash'),
        ]);
    }

    public function update(Request $request)
    {
        DB::beginTransaction();
        try {
            $settings = Setting::firstOrCreate([]);

            $validated = $request->validate([
                'school_name' => 'required|string|max:255',
                'address' => 'nullable|string',
                'phone_number' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'principal_name' => 'required|string|max:255',
                'principal_signature' => 'nullable|string|max:255',
                'school_logo' => 'nullable', 
                'current_session' => 'nullable|string|max:255',
            ]);

            $logoPath = null;
            // Handle logo upload or removal
            if ($request->hasFile('school_logo')) {
                // First, validate the file's type and size if a new file is present
                $request->validate(['school_logo' => 'image|mimes:jpeg,png,jpg,gif|max:5120']);

                // --- START: Direct File Move Logic ---
                $file = $request->file('school_logo');
                $destinationPath = base_path('assets/image');
                
                // 1. Delete the old logo if it exists (using base_path with the stored path)
                if ($settings->school_logo && File::exists(base_path($settings->school_logo))) {
                    // ✅ ACTION: Use File::delete to remove the file from the project root
                    File::delete(base_path($settings->school_logo));
                }

                // 2. Ensure the directory exists
                if (!File::isDirectory($destinationPath)) {
                    File::makeDirectory($destinationPath, 0777, true, true);
                }
                
                // 3. Create a unique file name
                $fileName = time() . '_logo_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                
                // 4. Move the file
                $file->move($destinationPath, $fileName);
                
                // 5. Set the database path
                $logoPath = 'assets/image/' . $fileName;

                // Merge the new path into validated data
                $validated['school_logo'] = $logoPath;
                // --- END: Direct File Move Logic ---

            } elseif ($request->school_logo === 'remove') {
                // If the client sends the special 'remove' value
                if ($settings->school_logo && File::exists(base_path($settings->school_logo))) {
                    // ✅ ACTION: Use File::delete to remove the file from the project root
                    File::delete(base_path($settings->school_logo));
                }
                $validated['school_logo'] = null;

            } else {
                // No new file or 'remove' instruction, so preserve the existing database value
                unset($validated['school_logo']);
            }

            $settings->update($validated);

            DB::commit();

            return redirect()->route('settings.index')->with('flash', [
                'message' => 'Settings updated successfully! ✅',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Settings update failed: ' . $e->getMessage());
            
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while updating the settings. Please try again. ❌',
                'type' => 'error'
            ])->withInput();
        }
    }

    public function destroy()
    {
        DB::beginTransaction();
        try {
            $settings = Setting::first();
            if ($settings) {
                if ($settings->school_logo) {
                    Storage::disk('public')->delete($settings->school_logo);
                }
                $settings->delete();
            }

            DB::commit();

            return redirect()->route('settings.index')->with('flash', [
                'message' => 'Settings deleted successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Settings deletion failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while deleting the settings. Please try again.',
                'type' => 'error'
            ]);
        }
    }


    public function serveLogo($filename)
    {
        // The base path is your project root/assets/image/
        $path = base_path('assets/image/' . $filename);

        if (!File::exists($path)) {
            // Return a default image
            // NOTE: Ensure your default.jpg exists at project_root/assets/image/default.jpg
            $defaultPath = base_path('assets/image/default.jpg'); 
            
            if (File::exists($defaultPath)) {
                $mimeType = File::mimeType($defaultPath);
                return Response::file($defaultPath, ['Content-Type' => $mimeType]);
            }
            // Fallback if the requested image and the default image are missing
            abort(404);
        }

        $mimeType = File::mimeType($path);
        return Response::file($path, ['Content-Type' => $mimeType]);
    }
}