<?php

namespace App\Http\Controllers;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
class SettingController extends Controller
{
   
    /**
     * Display a listing of the resource.
     * For a single-record settings table, this redirects to the edit page.
    */

    public function index()
    {
        $settings = Setting::firstOrCreate([]);

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'flash' => session('flash'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     * Since there is only one settings record, we render the same edit form.
    */
    public function create()
    {
        return Inertia::render('Admin/Settings/Create');
    }

    /**
     * Store a new resource or update the existing one.
     * We use a single 'store' method to manage the one settings record.
    */
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
                'school_logo' => 'nullable|string|max:255',
                'current_session' => 'nullable|string|max:255',
            ]);

            // This ensures a single record is created if none exists, or updated if it does.
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

    /**
     * Show the form for editing the single settings record.
    */
    public function edit()
    {
        // Find the first record, or create a new one if it doesn't exist.
        $settings = Setting::firstOrCreate([]);

        return Inertia::render('Admin/Settings/Edit', [
            'settings' => $settings,
            'flash' => session('flash'),
        ]);
    }

    /**
     * Update the single settings record in storage.
     * Since we only have one record, we don't need an ID parameter.
    */
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
                'school_logo' => 'nullable|string|max:255',
                'current_session' => 'nullable|string|max:255',
            ]);

            $settings->update($validated);

            DB::commit();

            return redirect()->route('settings.index')->with('flash', [
                'message' => 'Settings updated successfully!',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Settings update failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while updating the settings. Please try again.',
                'type' => 'error'
            ])->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     * Deleting the single settings record is not recommended, so we'll provide a warning.
    */
    public function destroy()
    {
        return redirect()->back()->with('flash', [
            'message' => 'The main settings record cannot be deleted.',
            'type' => 'error'
        ]);
    }
}
