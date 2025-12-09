<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\ClassName;
use App\Models\Section;
use App\Models\Group;
use App\Models\User;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Setting; // Assuming you have a Setting model
use Barryvdh\DomPDF\Facade\Pdf; 
use Illuminate\Support\Facades\File;

class TeacherController extends Controller
{
    // Make sure this trait is here!
    use AuthorizesRequests, ValidatesRequests;
    /**
     * Display a listing of the resource.
    */
    public function index()
    {
        $teachers = Teacher::all()->map(function ($teacher) {
            $teacher->image_url = $teacher->image
                ? route('teachers.image.serve', ['filename' => basename($teacher->image)])
                : null;
            return $teacher;
        });

        return Inertia::render('Teachers/Index', [
            'teachers' => $teachers,
            'setting'  => Setting::first(['school_name', 'school_logo', 'address', 'phone_number', 'email']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
    */
    public function create()
    {
        return Inertia::render('Teachers/Create', [
            'classes' => ClassName::all(['id', 'class_name']),
            'sections' => Section::all(['id', 'name']),
            'groups' => Group::all(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
    */
    
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'subject_taught' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'required|in:0,1',
            'phone_number' => 'nullable|string|max:20|unique:teachers,phone_number',
            'qualification' => 'nullable|string|max:255',
            'joining_date' => 'nullable|date',
            'designation' => 'required|in:Head Teacher,Senior Teacher,Junior Teacher,Assistant Teacher',
            'is_class_teacher' => 'nullable|boolean',
        ];

        // Only validate class/section/group if is_class_teacher is checked
        if ($request->boolean('is_class_teacher')) {
            $rules['class_id'] = 'required|exists:class_names,id';
            $rules['section_id'] = 'required|exists:sections,id';
            $rules['group_id'] = 'nullable|exists:groups,id';

            // Prevent duplicate class teacher assignment
            $rules['class_id'] .= '|unique:teachers,class_id,NULL,id,section_id,' . $request->section_id .
                                ',group_id,' . ($request->group_id ?? 'NULL');
        }

        $validatedData = $request->validate($rules);

        DB::transaction(function () use ($request, $validatedData) {
            $joiningNumber = $this->generateUniqueJoiningNumber();

            // Create User
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => Str::uuid() . '@teacher.local',
                'password' => Hash::make($validatedData['password']),
            ]);


            // --- Image upload to assets/image/ in the project root ---
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                
                // 1. Define the destination directory: assets/image in the project root
                $destinationPath = base_path('assets/image');
                
                // 2. Ensure the directory exists before moving the file
                if (!File::isDirectory($destinationPath)) {
                    File::makeDirectory($destinationPath, 0777, true, true);
                }
                
                // 3. Create a unique file name to avoid collisions
                $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                
                // 4. Move the file to the custom root path
                $file->move($destinationPath, $fileName);
                
                // 5. Set the path for the database (relative to the project root)
                $imagePath = 'assets/image/' . $fileName;
            }

            // Prepare teacher data
            $teacherData = [
                'user_id' => $user->id,
                'joining_number' => $joiningNumber,
                'image' => $imagePath,
                'name' => $validatedData['name'],
                'subject_taught' => $validatedData['subject_taught'],
                'address' => $validatedData['address'],
                'status' => $validatedData['status'],
                'phone_number' => $validatedData['phone_number'] ?? null,
                'qualification' => $validatedData['qualification'] ?? null,
                'joining_date' => $validatedData['joining_date'] ?? null,
                'designation' => $validatedData['designation'],
            ];

            // Only assign class teacher fields if checked 🇵🇰
            if ($request->boolean('is_class_teacher')) {
                $teacherData['class_id'] = $validatedData['class_id'];
                $teacherData['section_id'] = $validatedData['section_id'];
                $teacherData['group_id'] = $validatedData['group_id'] ?? null;
            }

            Teacher::create($teacherData);
        });

        return redirect()->route('teachers.index')->with('success', 'Teacher created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
    */
    public function edit(Teacher $teacher)
    {
        $teacher->load(['assignedClass', 'assignedSection', 'assignedGroup']);

        return Inertia::render('Teachers/Edit', [
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'joining_number' => $teacher->joining_number,
                'subject_taught' => $teacher->subject_taught,
                'address' => $teacher->address,
                'phone_number' => $teacher->phone_number,
                'qualification' => $teacher->qualification,
                'joining_date' => $teacher->joining_date,
                'designation' => $teacher->designation,
                'status' => $teacher->status,
                'image' => $teacher->image ? asset($teacher->image) : null,
                'is_class_teacher' => $teacher->isClassTeacher(),
                'class_id' => $teacher->class_id,
                'section_id' => $teacher->section_id,
                'group_id' => $teacher->group_id,
            ],
            'classes' => ClassName::all(['id', 'class_name']),
            'sections' => Section::all(['id', 'name']),
            'groups' => Group::all(['id', 'name']),
        ]);
    }

    /**
     * Update the specified resource in storage.
    */

    public function update(Request $request, Teacher $teacher)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'subject_taught' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'clear_image' => 'nullable|boolean',
            'status' => 'required|in:0,1',
            'phone_number' => 'nullable|string|max:20|unique:teachers,phone_number,' . $teacher->id,
            'qualification' => 'nullable|string|max:255',
            'joining_date' => 'nullable|date',
            'designation' => 'required|in:Head Teacher,Senior Teacher,Junior Teacher,Assistant Teacher',
            'is_class_teacher' => 'nullable|boolean',
        ];

        if ($request->boolean('is_class_teacher')) {
            $rules['class_id'] = 'required|exists:class_names,id';
            $rules['section_id'] = 'required|exists:sections,id';
            $rules['group_id'] = 'nullable|exists:groups,id';

            // Allow same assignment if it's the same teacher
            $uniqueRule = 'unique:teachers,class_id,NULL,id,section_id,' . $request->section_id .
                          ',group_id,' . ($request->group_id ?? 'NULL');
            if ($teacher->class_id == $request->class_id && $teacher->section_id == $request->section_id) {
                // Skip unique check if same assignment
            } else {
                $rules['class_id'] .= '|' . $uniqueRule;
            }
        }

        $validatedData = $request->validate($rules);

        $imagePath = $this->serveImage($request, $teacher);

        $imagePath = $teacher->image; // Default to existing image path

        // Define the base destination path outside the public folder
        $destinationPath = base_path('assets/image');
        
        // Handle image upload or clear
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            
            // 1. Delete old image if it exists
            if ($teacher->image && File::exists(base_path($teacher->image))) {
                File::delete(base_path($teacher->image));
            }
            
            // 2. Ensure the directory exists
            if (!File::isDirectory($destinationPath)) {
                File::makeDirectory($destinationPath, 0777, true, true);
            }
            
            // 3. Move the new file and set the new path
            $fileName = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $file->move($destinationPath, $fileName);
            $imagePath = 'assets/image/' . $fileName;
        } elseif ($request->input('clear_image')) {
            // Handle clearing the image
            if ($teacher->image && File::exists(base_path($teacher->image))) {
                File::delete(base_path($teacher->image));
            }
            $imagePath = null;
        }


        $updateData = [
            'name' => $validatedData['name'],
            'subject_taught' => $validatedData['subject_taught'],
            'address' => $validatedData['address'],
            'status' => $validatedData['status'],
            'phone_number' => $validatedData['phone_number'] ?? null,
            'qualification' => $validatedData['qualification'] ?? null,
            'joining_date' => $validatedData['joining_date'] ?? null,
            'designation' => $validatedData['designation'],
            'image' => $imagePath,
        ];

        // Handle class teacher assignment
        if ($request->boolean('is_class_teacher')) {
            $updateData['class_id'] = $validatedData['class_id'];
            $updateData['section_id'] = $validatedData['section_id'];
            $updateData['group_id'] = $validatedData['group_id'] ?? null;
        } else {
            $updateData['class_id'] = null;
            $updateData['section_id'] = null;
            $updateData['group_id'] = null;
        }

        $teacher->update($updateData);

        return redirect()->route('teachers.index')->with('success', 'Teacher updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
    */

    public function destroy(Teacher $teacher)
    {

        // If unauthorized, this stops the script immediately with a 403 Forbidden error.
        $this->authorize('delete', $teacher); 

        DB::beginTransaction();
        
        try {
            // 2. Database Operation
            $teacher->delete();
            
            // 3. Commit Transaction
            DB::commit();

            // 4. Corrected Success Message
            return redirect()->route(route: 'teachers.index')->with('flash', [
                'message' => 'Teacher deleted successfully!', // Corrected the message text
                'type' => 'success'
            ]);
            
        } catch (\Exception $e) {
            // 5. Rollback Transaction on Failure
            DB::rollBack();
            
            // Log the error for internal debugging
            Log::error('Teacher deletion failed: ' . $e->getMessage()); 
            
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while deleting the teacher. Please try again.',
                'type' => 'error'
            ]);
        }
    }


    // Serve Teacher Image Method
    public function serveImage($filename)
    {
        // 1. Define the full path to the image file in the project root
        $path = base_path('assets/image/' . $filename);

        // 2. Check if the file exists at that path
        if (!File::exists($path)) {
            // You can return a default image here if the file is missing
            return response()->json(['message' => 'Image not found.'], 404);
        }

        // 3. Determine the content type (MIME type)
        $mimeType = File::mimeType($path);

        // 4. Return the file content with the correct headers
        return response()->file($path, ['Content-Type' => $mimeType]);
    }

    /**
     * Generate a unique 6-digit joining number.
     * This method is now part of the controller.
    */

    // Helper method to generate a unique 6-digit joining number
    private function generateUniqueJoiningNumber()
    {
        do {
            $joiningNumber = strval(random_int(100000, 999999));
        } while (Teacher::where('joining_number', $joiningNumber)->exists());

        return $joiningNumber;
    }  


    // Single Teacher ID Card Download Method

    public function downloadPdfTeacher(Teacher $teacher)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        try {
            $teacher->loadMissing(['user']);

            // School Settings with safe fallback
            $setting = Setting::first() ?? (object)[
                'school_name'         => 'BLUE BIRD SCHOOL',
                'address'             => 'Sylhet, Zindabazar, Bangladesh',
                'phone_number'        => '01615338863',
                'principal_signature' => 'Md. Principal Sir',
                'school_logo'         => null, // Assume this holds 'assets/image/logo.png'
            ];

            // ====================================================================
            // 1. SCHOOL LOGO - Base64 (Using Base Path Logic)
            // ====================================================================
            $logoBase64 = '';
            if ($setting->school_logo && strpos($setting->school_logo, 'assets/image/') === 0) {
                $cleanLogoName = str_replace('assets/image/', '', $setting->school_logo);
                $fullPath = base_path('assets/image/' . $cleanLogoName);
                
                if (File::exists($fullPath) && File::isReadable($fullPath)) {
                    $mimeType = File::mimeType($fullPath);
                    $imageData = base64_encode(File::get($fullPath));
                    $logoBase64 = 'data:' . $mimeType . ';base64,' . $imageData;
                    
                    Log::info("PDF Debug: School Logo SUCCESS. Path: " . str_replace('\\', '/', $fullPath));
                } else {
                    Log::error('PDF Debug: School Logo FAILED (NOT FOUND). Verified Path: ' . $fullPath);
                }
            }
            
            if (empty($logoBase64)) {
                // Fallback placeholder (used if image is missing or path is wrong)
                $logoBase64 = 'data:image/svg+xml;base64,' . base64_encode('
                    <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80">
                        <circle cx="40" cy="40" r="38" fill="#0A327A"/>
                        <text x="40" y="48" font-family="Arial" font-size="18" fill="white" text-anchor="middle">LOGO</text>
                    </svg>
                ');
            }
            $setting->logo_base64 = $logoBase64;


            // ====================================================================
            // 2. TEACHER PHOTO - Base64 (Using Base Path Logic - assuming same structure)
            // ====================================================================
            $teacherPhotoSrc = '';
            if ($teacher->image && strpos($teacher->image, 'assets/image/') === 0) {
                $cleanPhotoName = str_replace('assets/image/', '', $teacher->image);
                $fullPath = base_path('assets/image/' . $cleanPhotoName);

                if (File::exists($fullPath) && File::isReadable($fullPath)) {
                    $mimeType = File::mimeType($fullPath);
                    $imageData = base64_encode(File::get($fullPath));
                    $teacherPhotoSrc = 'data:' . $mimeType . ';base64,' . $imageData;

                    Log::info("PDF Debug: Teacher Photo SUCCESS. Path: " . str_replace('\\', '/', $fullPath));
                } else {
                    Log::error('PDF Debug: Teacher Photo FAILED (NOT FOUND). Verified Path: ' . $fullPath);
                }
            }
            
            if (empty($teacherPhotoSrc)) {
                // Placeholder with first letter of name
                $initial = substr($teacher->name ?? 'T', 0, 1);
                $teacherPhotoSrc = 'data:image/svg+xml;base64,' . base64_encode('
                    <svg xmlns="http://www.w3.org/2000/svg" width="140" height="140">
                        <circle cx="70" cy="70" r="68" fill="#0A327A"/>
                        <text x="70" y="88" font-family="Arial" font-size="60" fill="white" text-anchor="middle" font-weight="bold">' . $initial . '</text>
                    </svg>
                ');
            }

            // ====================================================================
            // 3. TEACHER BACKGROUND SHAPE (teacher_id.png) - Base64 CSS Style
            // ====================================================================
            $shapeBase64Url = '';
            $shapePath = public_path('assets/image/teacher_id.png'); // Assuming this asset remains in public/assets

            if (File::exists($shapePath)) {
                $shapeData = File::get($shapePath);
                $mime = File::mimeType($shapePath) ?: 'image/png';
                $base64 = base64_encode($shapeData);
                $shapeBase64Url = "background-image: url('data:{$mime};base64,{$base64}');
                                background-size: cover;
                                background-position: center bottom;
                                background-repeat: no-repeat;";
            } else {
                Log::warning('PDF Debug: teacher_id.png not found at: ' . $shapePath);
                $shapeBase64Url = 'background-color: #0A327A;';
            }

            // ====================================================================
            // 4. Pass Data and Generate PDF
            // ====================================================================
            $data = [
                'teacher'         => $teacher,
                'setting'         => $setting,
                'teacherPhotoSrc' => $teacherPhotoSrc,
                'shapeBase64Url'  => $shapeBase64Url,
            ];

            if (ob_get_contents()) ob_end_clean();

            $pdf = Pdf::loadView('pdfs.teacher_id_card', $data)
                    ->setPaper('a4', 'portrait')
                    ->setOptions([
                        'isRemoteEnabled'      => true,
                        'isHtml5ParserEnabled' => true,
                        'defaultFont'          => 'sans-serif',
                    ]);

            $filename = 'Teacher_ID_' 
                    . preg_replace('/[^A-Za-z0-9]/', '_', $teacher->name ?? 'Unknown') 
                    . '_' . ($teacher->joining_number ?? 'NA') 
                    . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Teacher ID Card PDF FATAL Error: ' . $e->getMessage(), [
                'teacher_id' => $teacher->id ?? null,
                'trace'      => $e->getTraceAsString()
            ]);

            return back()->with('error', 'Failed to generate Teacher ID Card due to a system error. Please contact support.');
        }
    }
    

    // Download All Teachers ID Cards as PDF
    public function printAllIdCards()
    {
        try {
            // Set unlimited execution time for this heavy task
            set_time_limit(0);

            // Fetch all active teachers
            $teachers = Teacher::with('user')->where('status', 0)->get();
            if ($teachers->isEmpty()) {
                return redirect()->route('teachers.index')->with('error', 'No active teachers found to generate PDFs.');
            }

            // Fetch School Settings with fallback
            $setting = Setting::first() ?? (object) [
                'school_name' => 'Default School',
                'school_logo' => null,
                'address' => 'N/A',
                'phone' => 'N/A', // Use 'phone' to match template
                'email' => 'N/A',
                'principal_name' => 'N/A',
            ];

            // Prepare School Logo image as Base64
            $setting->logo_base64 = 'https://placehold.co/50x50/008080/FFFFFF?text=LOGO'; // Fallback
            if ($setting->school_logo) {
                $cleanLogoName = str_replace('assets/image/', '', $setting->school_logo);
                $logoPath = public_path('uploads/settings/' . $setting->school_logo);
                $newLogoPath = base_path('assets/image/' . $cleanLogoName);
                $foundPath = null;

                if (File::exists($logoPath)) {
                    $foundPath = $logoPath;
                } elseif (File::exists($newLogoPath)) {
                    $foundPath = $newLogoPath;
                }

                if ($foundPath) {
                    $imageData = base64_encode(File::get($foundPath));
                    $mimeType = File::mimeType($foundPath) ?: 'image/jpeg';
                    $setting->logo_base64 = 'data:' . $mimeType . ';base64,' . $imageData;
                } else {
                    Log::warning("School logo '{$setting->school_logo}' not found. Checked: {$logoPath} and {$newLogoPath}");
                }
            }

            // Prepare Teacher images as Base64
            $teacherPhotos = [];
            foreach ($teachers as $teacher) {
                $teacherPhotoSrc = 'https://placehold.co/140x140/008080/FFFFFF?text=PHOTO'; // Updated to match photo size
                if ($teacher->image) {
                    $cleanImageName = str_replace('assets/image/', '', $teacher->image);
                    $photoPath = base_path('assets/image/' . $cleanImageName);
                    if (File::exists($photoPath)) {
                        $imageData = base64_encode(File::get($photoPath));
                        $mimeType = File::mimeType($photoPath) ?: 'image/jpeg';
                        $teacherPhotoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                    } else {
                        Log::warning("Teacher image not found at: $photoPath");
                    }
                }
                $teacherPhotos[$teacher->id] = $teacherPhotoSrc;
            }

            $data = [
                'teachers' => $teachers,
                'setting' => $setting,
                'teacherPhotos' => $teacherPhotos,
            ];

            // Clear output buffer to prevent corrupt PDF headers
            if (ob_get_contents()) {
                ob_end_clean();
            }

            // Load the PDF view
            $pdf = Pdf::loadView('pdfs.all_teachers_id_cards', $data);
            $pdf->setPaper('A4', 'portrait');

            // Stream the PDF
            $filename = 'All_Teachers_ID_Cards_' . now()->format('Ymd') . '.pdf';
            return $pdf->stream($filename);
        } catch (\Exception $e) {
            Log::error('Error generating PDF for all teachers: ' . $e->getMessage());
            return redirect()->route('teachers.index')->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    
}
