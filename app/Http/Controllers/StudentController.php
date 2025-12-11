<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassName; 
use App\Models\ClassSession;
use App\Models\Section;
use App\Models\Group;
use App\Models\Setting;
use App\Models\User; 
use App\Models\ExamResult; 
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf; 
use Illuminate\Support\Facades\File; // <-- Make sure this is here
use Illuminate\Support\Facades\Hash;
use Exception; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class StudentController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;
    /**
     * Display a listing of the resource.
    */
    public function index()
    {
        $students = Student::with(['className', 'session', 'group', 'section', 'user'])->latest()->paginate(10);
        $classes = ClassName::all();
        $setting = Setting::first();

        // Prepare student image URLs
        $students->getCollection()->transform(function ($student) {
            if ($student->image && strpos($student->image, 'assets/image/') === 0) {
                $filename = str_replace('assets/image/', '', $student->image);
                $student->image_url = route('students.image.serve', ['filename' => $filename]);
            }
            return $student;
        });

        // Prepare school logo URL or base64
        if ($setting && $setting->school_logo && strpos($setting->school_logo, 'assets/image/') === 0) {
            $filename = str_replace('assets/image/', '', $setting->school_logo);
            $setting->school_logo_url = route('students.image.serve', ['filename' => $filename]);
        } elseif ($setting && $setting->school_logo) {
            // Fallback to base64 if needed (optional)
            $logoPath = base_path($setting->school_logo);
            if (File::exists($logoPath) && File::isReadable($logoPath)) {
                $mimeType = File::mimeType($logoPath);
                $imageData = base64_encode(File::get($logoPath));
                $setting->school_logo_url = 'data:' . $mimeType . ';base64,' . $imageData;
            }
        }

        return Inertia::render('Students/Index', [
            'students' => $students,
            'classes' => $classes,
            'setting' => $setting,
        ]);
    }
    /**
     * Show the form for creating a new student.
    */
    public function create()
    {
        $classes = ClassName::where('status', 0)->get(['id', 'class_name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $groups = Group::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);
        // Fetch users who are not yet linked to any student or teacher,
        // or just all users if you want more flexibility in linking.
        // For simplicity, fetching all users here.
        $availableUsers = User::doesntHave('student')
                                ->doesntHave('teacher')
                                ->get(['id', 'name', 'email']);

        return Inertia::render('Students/Create', [
            'classes' => $classes,
            'sessions' => $sessions,
            'groups' => $groups,
            'sections' => $sections,
            'availableUsers' => $availableUsers, // Pass available users to the view
        ]);
    }

    private function generateUniqueAdmissionNumber(): string
    {
        do {
            // Generate a random number between 100,000 (inclusive) and 999,999 (inclusive)
            $number = random_int(100000, 999999);
        } while (Student::where('admission_number', $number)->exists());

        return (string)$number;
    }
   
    /**
     * Store a newly created student in storage.
    */

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:class_names,id',
            'age' => 'required|integer|min:0',
            'date_of_birth' => 'required|date',
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other'])],
            'admission_date' => 'required|date',
            'session_id' => 'required|exists:class_sessions,id',
            'group_id' => 'required|exists:groups,id',
            'section_id' => 'required|exists:sections,id',
            'parent_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'blood_group' => ['nullable', 'string', 'max:10'],
            'contact' => 'required|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
            'status' => 'required|integer|in:0,1',
            'enrollment_status' => ['required', 'string', Rule::in(['applied', 'under_review', 'admitted', 'enrolled', 'rejected', 'waitlisted', 'withdrawn'])],
            // Remove roll_number from validation — we will generate it
            'admission_fee_amount' => 'nullable|numeric|min:0',
            'admission_fee_paid' => 'boolean',
            'payment_method' => ['nullable', 'string', Rule::in(['Cash', 'bKash', 'Bank Transfer'])],
        ]);

        return DB::transaction(function () use ($request, $validatedData) {
            // Step 1: Create User
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => Str::random(10) . '@example.com',
                'password' => Hash::make(Str::random(16)),
            ]);

            $validatedData['user_id'] = $user->id;

            // Step 2: Generate Admission Number
            $validatedData['admission_number'] = $this->generateUniqueAdmissionNumber();

            // Step 3: Auto Generate Roll Number (Smart per Class+Section+Group+Session)
            $lastRoll = Student::where('class_id', $validatedData['class_id'])
                ->where('section_id', $validatedData['section_id'])
                ->where('group_id', $validatedData['group_id'])
                ->where('session_id', $validatedData['session_id'])
                ->max('roll_number');

            $validatedData['roll_number'] = ($lastRoll ?? 0) + 1;

            // Step 4: Handle Image Upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $destinationPath = base_path('assets/image');

                if (!File::isDirectory($destinationPath)) {
                    File::makeDirectory($destinationPath, 0777, true, true);
                }

                $fileName = time() . '_student_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $file->move($destinationPath, $fileName);
                $imagePath = 'assets/image/' . $fileName;
            }
            $validatedData['image'] = $imagePath;

            // Step 5: Handle Admission Fee (convert to paisa)
            if (isset($validatedData['admission_fee_amount']) && $validatedData['admission_fee_amount'] !== null) {
                $validatedData['admission_fee_amount'] = (int)($validatedData['admission_fee_amount'] * 100);
            } else {
                $validatedData['admission_fee_amount'] = null;
            }

            $validatedData['admission_fee_paid'] = $validatedData['admission_fee_paid'] ?? false;

            // Step 6: Create Student
            Student::create($validatedData);

            return redirect()->route('students.index')
                ->with('success', "শিক্ষার্থী সফলভাবে যোগ করা হয়েছে! Roll Number: {$validatedData['roll_number']}");
        });
    }

    /**
     * Show the form for editing the specified student.
    */
    public function edit(Student $student)
    {
        $classes = ClassName::where('status', 0)->get(['id', 'class_name']);
        $sessions = ClassSession::where('status', 0)->get(['id', 'name']);
        $groups = Group::where('status', 0)->get(['id', 'name']);
        $sections = Section::where('status', 0)->get(['id', 'name']);

        // Fetch users who are not yet linked to any student or teacher,
        // OR the user currently linked to this student (to allow re-selecting the same user).
        $availableUsers = User::where(function ($query) {
                                $query->whereDoesntHave('student')
                                      ->whereDoesntHave('teacher');
                            })
                            ->orWhere('id', $student->user_id) // Include the student's current user
                            ->get(['id', 'name', 'email']);

        // Eager load relationships for the student being edited
        $student->load(['className', 'session', 'group', 'section', 'user']);

        return Inertia::render('Students/Edit', [
            'student' => $student,
            'classes' => $classes,
            'sessions' => $sessions,
            'groups' => $groups,
            'sections' => $sections,
            'availableUsers' => $availableUsers, // Pass available users to the view
            'flash' => session('flash'), // Ensure flash messages are passed
            'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : (object)[], // Ensure errors are passed
        ]);
    }

    /**
     * Update the specified student in storage.
    */
    
    public function update(Request $request, Student $student)
    {
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'class_id' => 'nullable|exists:class_names,id',
            'age' => 'nullable|integer|min:0',
            'date_of_birth' => 'nullable|date',
            'blood_group' => ['nullable', 'string', 'max:10'],
            'gender' => ['required', Rule::in(['Male', 'Female', 'Other'])],
            'admission_date' => 'nullable|date',
            'session_id' => 'nullable|exists:class_sessions,id',
            'group_id' => 'nullable|exists:groups,id',
            'section_id' => 'nullable|exists:sections,id',

            // user_id unique rule should ignore the current student's user_id
            'user_id' => [
                'required',
                'exists:users,id',
                Rule::unique('students', 'user_id')->ignore($student->id),
            ],

            'parent_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:1024',
            'status' => 'nullable|integer|in:0,1',
            'enrollment_status' => ['required', 'string', Rule::in(['applied', 'under_review', 'admitted', 'enrolled', 'rejected', 'waitlisted', 'withdrawn'])],

            // --- Validation for admission_number and roll_number ---
            'admission_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('students', 'admission_number')->ignore($student->id),
            ],
            'roll_number' => [
                'required',
                'integer',
                Rule::unique('students', 'roll_number')->ignore($student->id),
            ],

            // New validation rules for admission fee and payment details
            'admission_fee_amount' => 'nullable|numeric|min:0',
            'admission_fee_paid' => 'boolean',
            'payment_method' => ['nullable', 'string', Rule::in(['Cash', 'bKash', 'Bank Transfer'])],
        ]);

         // --- START: ADJUSTED IMAGE UPDATE LOGIC ---
    if ($request->hasFile('image')) {
        $file = $request->file('image');
        
        // 1. Delete old image if it exists and path is not null
        if ($student->image && File::exists(base_path($student->image))) {
            File::delete(base_path($student->image));
        }
        
        // 2. Define the destination directory: assets/image
        $destinationPath = base_path('assets/image');
        if (!File::isDirectory($destinationPath)) {
            File::makeDirectory($destinationPath, 0777, true, true);
        }
        
        // 3. Create a unique file name
        $fileName = time() . '_student_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        
        // 4. Move the file to the custom root path
        $file->move($destinationPath, $fileName);
        
        // 5. Set the path for the database
        $validatedData['image'] = 'assets/image/' . $fileName;

    } elseif ($request->input('clear_image') === true) {
        // Handle clear image checkbox/flag (assuming you pass 'clear_image' boolean from Inertia)
        if ($student->image && File::exists(base_path($student->image))) {
            File::delete(base_path($student->image));
        }
        $validatedData['image'] = null;
    } else {
        // If no new image is uploaded and clear_image is not set, we keep the existing image path.
        // We unset 'image' from $validatedData so the current value in the database is preserved.
        unset($validatedData['image']); 
    }
    // --- END: ADJUSTED IMAGE UPDATE LOGIC ---

    // --- Handle admission_fee_amount conversion for update ---
    if (isset($validatedData['admission_fee_amount']) && $validatedData['admission_fee_amount'] !== null) {
        $validatedData['admission_fee_amount'] = (int)($validatedData['admission_fee_amount'] * 100);
    } else {
        $validatedData['admission_fee_amount'] = null;
    }

    // Set default for admission_fee_paid if not present (e.g., checkbox not checked)
    $validatedData['admission_fee_paid'] = $validatedData['admission_fee_paid'] ?? false;

    $student->update($validatedData);

    return redirect()->route('students.index')->with('success', 'Student updated successfully!');
    }

    // Serve student images from assets/image directory
    public function serveImageStudents($filename)
    {
        $path = base_path('assets/image/' . $filename);

        if (!File::exists($path)) {
            // Return a default image
            $defaultPath = base_path('assets/image/default.jpg'); // Ensure a default image exists at this path
            if (File::exists($defaultPath)) {
                $mimeType = File::mimeType($defaultPath);
                return response()->file($defaultPath, ['Content-Type' => $mimeType]);
            }
            abort(404); // Fallback to 404 if default image doesn't exist
        }

        $mimeType = File::mimeType($path);
        return response()->file($path, ['Content-Type' => $mimeType]);
    }

   
    /**
     * Remove the specified resource from storage.
    */
    public function destroy(Student $student)
    {
        // 1. Authorization Check
        $this->authorize('delete', $student); 

        DB::beginTransaction();
        
        try {
            // 2. Database Operation
            $student->delete();
            
            // 3. Commit Transaction
            DB::commit();

            return redirect()->route(route: 'students.index')->with('flash', [
                'message' => 'Student deleted successfully!',
                'type' => 'success'
            ]);
            
        } catch (\Exception $e) {
            // 4. Rollback Transaction on Failure
            DB::rollBack();
            
            // Log the error for internal debugging
            Log::error('Student deletion failed: ' . $e->getMessage()); 
            
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while deleting the student. Please try again.',
                'type' => 'error'
            ]);
        }
    }

    //  NextRoll Method

    public function nextRoll(Request $request)
    {
        $next = Student::where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('group_id', $request->group_id)
            ->where('session_id', $request->session_id)
            ->max('roll_number');

        return response()->json(['next_roll' => ($next ?? 0) + 1]);
    }



    // Student Single id card download method
    public function downloadIdCard($student_id)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        try {
            $student = Student::findOrFail($student_id);
            $student->load('className');

            $setting = Setting::first() ?? (object) [
                // ... (rest of $setting properties)
                'school_name' => 'Your School Name',
                'principal_signature' => 'Principal Name',
            ];

            // --- STUDENT IMAGE LOGIC (Final Path Adjustment) ---
            $studentPhotoSrc = ''; // Base64 placeholder
            $studentPhotoPath = null; // Absolute path

            if ($student->image && strpos($student->image, 'assets/image/') === 0) {
                $cleanImageName = str_replace('assets/image/', '', $student->image);
                $fullPath = base_path('assets/image/' . $cleanImageName);

                if (File::exists($fullPath) && File::isReadable($fullPath)) {
                    
                    // 1. CRITICAL ADJUSTMENT: Replace Windows backslashes with forward slashes
                    // This creates a reliable URI path for Dompdf on Windows.
                    $studentPhotoPath = str_replace('\\', '/', $fullPath);
                    
                    // 2. Also generate Base64 as a fallback
                    $mimeType = File::mimeType($fullPath);
                    $imageData = base64_encode(File::get($fullPath));
                    $studentPhotoSrc = 'data:' . $mimeType . ';base64,' . $imageData;
                    
                    Log::info("Student image data prepared. Path: {$studentPhotoPath}, Base64 Length: " . strlen($imageData));
                } else {
                    Log::warning("Student image not found or readable: {$fullPath}");
                }
            }
            
            $student->image_source = [
                'path' => $studentPhotoPath, 
                'base64' => $studentPhotoSrc
            ];

            // --- SCHOOL LOGO LOGIC (Final Path Adjustment) ---
            $logoSrc = ''; 
            $logoPath = null; 
            
            if ($setting->school_logo && strpos($setting->school_logo, 'assets/image/') === 0) {
                $cleanLogoName = str_replace('assets/image/', '', $setting->school_logo);
                $fullPath = base_path('assets/image/' . $cleanLogoName);
                
                if (File::exists($fullPath) && File::isReadable($fullPath)) {
                    // CRITICAL ADJUSTMENT: Replace Windows backslashes with forward slashes
                    $logoPath = str_replace('\\', '/', $fullPath); 
                    
                    $mimeType = File::mimeType($fullPath);
                    $imageData = base64_encode(File::get($fullPath));
                    $logoSrc = 'data:' . $mimeType . ';base64,' . $imageData;

                    Log::info("School logo data prepared. Path: {$logoPath}, Base64 Length: " . strlen($imageData));
                } else {
                    Log::warning("School logo not found or readable: {$fullPath}");
                }
            }
            
            $setting->image_source = [
                'path' => $logoPath,
                'base64' => $logoSrc
            ];
            

            $data = [
                'student' => $student,
                'setting' => $setting,
            ];

            if (ob_get_contents()) {
                ob_end_clean();
            }

            $options_array = [
                'isRemoteEnabled' => true, 
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'sans-serif',
            ];

            $pdf = Pdf::loadView('pdfs.student_id_card', $data)
                ->setOptions($options_array)
                ->setPaper('A4', 'portrait');

            $filename = 'ID_Card_' . str_replace(' ', '_', $student->name ?? 'Unknown') . '_' . ($student->admission_number ?? 'N_A') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            // ... (rest of exception handling)
        }
    }


    

    // Class ID Cards Printing Method
    public function printClassIdCards($classId)
    {

        // *** ADD THESE LINES ***
        set_time_limit(300); // Set time limit to 5 minutes (300 seconds)
        ini_set('memory_limit', '512M'); // Set memory limit to 512MB
        
        try {
            $students = Student::with(['className', 'section'])
                ->where('class_id', $classId)
                ->orderBy('roll_number')
                ->get();

            if ($students->isEmpty()) {
                return redirect()->back()->with('error', 'No students found in this class.');
            }

            $setting = Setting::first() ?? (object)[
                'school_name' => 'Your School',
                'address' => 'Sylhet, Bangladesh',
                'school_logo' => null,
            ];

            // Convert images to base64 using the method below
            $setting->logo_base64 = $this->generate_base64_image(
                $setting->school_logo,
                'https://placehold.co/120x120/0066CC/ffffff?text=LOGO'
            );

            foreach ($students as $student) {
                $student->image_base64 = $this->generate_base64_image(
                    $student->image,
                    'https://placehold.co/130x130/002856/ffffff?text=' . substr($student->name ?? 'S', 0, 1)
                );
            }

            $pdf = PDF::loadView('pdfs.print_class_idcards', compact('students', 'setting'))
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont'             => 'DejaVu Sans',
                    'isHtml5ParserEnabled'    => true,
                    'isRemoteEnabled'         => true,
                    'isPhpEnabled'            => true,
                    'isFontSubsettingEnabled'=> true,
                    'dpi'                     => 150,
                ]);

            $className = $students->first()->className->class_name ?? 'Unknown';
            $fileName = "ID_Cards_{$className}_" . now()->format('Y-m-d') . ".pdf";

            return $pdf->stream($fileName);

        } catch (\Exception $e) {
            \Log::error('PDF Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'PDF generation failed: ' . $e->getMessage());
        }
    }



    // Generate Base64 Image Method
    private function generate_base64_image($imagePath, $fallbackUrl = null)
    {
        // 1. Initial checks for null, empty, or existing URL/data URI are fine
        if (!$imagePath || trim($imagePath) === '') {
            return $fallbackUrl ?? 'data:image/svg+xml;base64,...(placeholder)';
        }

        if (filter_var($imagePath, FILTER_VALIDATE_URL) || str_starts_with($imagePath, 'data:')) {
            return $imagePath;
        }
        
        // --- STEP 1: Implement the Working Teacher/Logo Logic ---
        // If the path contains the specific 'assets/image/' prefix, try to resolve it relative to base_path.
        if (str_starts_with($imagePath, 'assets/image/')) {
            // Construct the path relative to the Laravel project root (base_path)
            $fullPath = base_path($imagePath); // E.g., C:\project\assets\image\photo.jpg
            
            if (File::exists($fullPath) && File::isReadable($fullPath)) {
                Log::info("Image found (BASE_PATH FIX): " . $fullPath);
                $mime = File::mimeType($fullPath) ?: 'image/jpeg';
                $base64 = base64_encode(File::get($fullPath));
                return "data:{$mime};base64,{$base64}";
            }
        }
        
        // --- STEP 2: Fallback to Generic Storage/Public Checks (Original Logic) ---
        // This is useful for images stored in the Laravel storage system (e.g., 'students/photos/...')

        // A. Try Storage Path (most common for user uploads)
        $storagePath = storage_path('app/public/' . $imagePath); 
        if (File::exists($storagePath) && File::isReadable($storagePath)) {
            Log::info("Image found (STORAGE_PATH): " . $storagePath);
            $mime = File::mimeType($storagePath) ?: 'image/jpeg';
            $base64 = base64_encode(File::get($storagePath));
            return "data:{$mime};base64,{$base64}";
        }

        // B. Try Public Asset Path (for any other files in the public directory)
        $publicAssetPath = public_path($imagePath);
        if (File::exists($publicAssetPath) && File::isReadable($publicAssetPath)) {
            Log::info("Image found (PUBLIC_PATH): " . $publicAssetPath);
            $mime = File::mimeType($publicAssetPath) ?: 'image/jpeg';
            $base64 = base64_encode(File::get($publicAssetPath));
            return "data:{$mime};base64,{$base64}";
        }
        
        // --- STEP 3: Failure ---
        Log::warning('Image NOT found after all checks. Path: ' . $imagePath);
        return $fallbackUrl ?? 'data:image/svg+xml;base64,...(placeholder)';
    }



  

    // Transfer Certificate Download Method
    public function downloadTransferCertificate($student_id)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        try {
            $student = Student::with(['className', 'session'])->findOrFail($student_id);
            $setting = Setting::first() ?? (object)[
                'school_name' => 'Your School Name',
                'address' => 'Dhaka, Bangladesh',
                'phone_number' => '+880 1XXX-XXXXXX',
                'principal_name' => 'Principal Name',
            ];

            // === SCHOOL LOGO HANDLING (Same as ID Card - Works on Shared Hosting) ===
            $logoBase64 = null;
            $logoPath = null;

            if ($setting->school_logo) {
                // Handle both: storage/app/public/... AND assets/image/...
                if (strpos($setting->school_logo, 'assets/image/') === 0) {
                    // Case 1: Image stored in public assets folder (common in shared hosting)
                    $cleanName = str_replace('assets/image/', '', $setting->school_logo);
                    $fullPath = base_path('assets/image/' . $cleanName);
                } else {
                    // Case 2: Laravel default storage path
                    $fullPath = storage_path('app/public/' . $setting->school_logo);
                }

                if (file_exists($fullPath) && is_readable($fullPath)) {
                    // Fix Windows backslashes
                    $logoPath = str_replace('\\', '/', $fullPath);

                    // Generate Base64 (Dompdf loves this - most reliable)
                    $mime = mime_content_type($fullPath);
                    $data = file_get_contents($fullPath);
                    $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($data);

                    \Log::info("TC Logo loaded successfully: " . $fullPath);
                } else {
                    \Log::warning("School logo not found: " . $fullPath);
                }
            }

            // === SESSION NAME ===
            $sessionName = $student->session?->name ?? ($student->session?->session ?? 'N/A');

            // === PREPARE DATA ===
            $data = [
                'student'     => $student,
                'setting'     => $setting,
                'logoBase64'  => $logoBase64,
                'logoPath'    => $logoPath, // optional fallback
                'sessionName' => $sessionName,
                'conduct'     => $student->conduct ?? 'Good',
                'reason'      => $student->reason_for_leaving ?? "Guardian's Request (Transfer to another school)",
                'date'        => now()->format('F j, Y'),
            ];

            // Clean output buffer
            if (ob_get_contents()) ob_end_clean();

            // Generate PDF
            $pdf = PDF::loadView('pdfs.transfer_certificate', $data)
                    ->setPaper('a4', 'landscape')
                    ->setOptions([
                        'isRemoteEnabled' => true,
                        'isHtml5ParserEnabled' => true,
                        'defaultFont' => 'sans-serif',
                    ]);

            $filename = 'Transfer_Certificate_' . 
                        str_replace([' ', '/'], '_', $student->name) . 
                        '_' . ($student->admission_number ?? 'N/A') . '.pdf';

            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Log::error("Transfer Certificate Generation Failed: " . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to generate Transfer Certificate.');
        }
    }


    // New method to show a specific student's exam and invoice history

    public function showStudentHistory($student_id)
    {
        $student = Student::with([
            // ---- EXAM RESULTS + MARKS ----
            'examResults' => function ($q) {
                $q->with(['exam', 'session', 'className', 'marks.subject'])
                    ->whereNotNull('published_at')
                    ->orderByDesc('published_at');
            },
            // ---- INVOICES - Load payments with them ----
            'invoices' => function ($q) {
                $q->with('payments') // Assuming you have a payments relationship
                ->orderByDesc('issued_at');
            }
        ])->findOrFail($student_id);

        // ------------------------------------------------------------------------
        // ---------- MISSING SECTION 1: GROUP EXAMS (Needed for Inertia) ----------
        // ------------------------------------------------------------------------

        $groupedExamHistory = $student->examResults
            ->groupBy(fn ($r) => $r->className?->class_name ?? '—')
            ->map(fn ($classGroup) => $classGroup->groupBy(fn ($r) => $r->session?->name ?? '—'));

        // ------------------------------------------------------------------------
        // ---------- MISSING SECTION 2: FLATTEN FOR VUE (Needed for Inertia) ----------
        // ------------------------------------------------------------------------

        $examHistory = $student->examResults->map(fn ($r) => [
            'id' => $r->id,
            'exam_name' => $r->exam?->exam_name ?? 'N/A',
            'total_marks_obtained' => $r->total_marks_obtained,
            'total_possible_marks' => $r->total_possible_marks,
            'percentage' => $r->percentage,
            'final_letter_grade' => $r->final_letter_grade,
            'final_grade_point' => $r->final_grade_point,
            'published_at' => $r->published_at?->format('Y-m-d'),
            'class_name' => $r->className?->class_name ?? '—',
            'session_name' => $r->session?->name ?? '—',

            // ----- SUBJECT-WISE BREAKDOWN -----
            'subject_wise_data' => $r->marks->map(fn ($m) => [
                'subject' => $m->subject?->name ?? '—',
                'class_test' => $m->class_test_marks,
                'assignment' => $m->assignment_marks,
                'exam' => $m->exam_marks,
                'attendance' => $m->attendance_marks,
                'total' => ($m->class_test_marks ?? 0)
                        + ($m->assignment_marks ?? 0)
                        + ($m->exam_marks ?? 0)
                        + ($m->attendance_marks ?? 0),
            ])->values()->all(),
        ])->values();


        // ---------- PROCESS INVOICES TO INCLUDE PAID AMOUNT AND BALANCE ----------
        $invoiceHistory = $student->invoices->map(function ($invoice) {
            $totalPaid = $invoice->payments->sum('amount'); // Sum up payment amounts
            $balanceDue = $invoice->total_amount_due - $totalPaid;

            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'billing_period' => $invoice->billing_period,
                'total_amount_due' => $invoice->total_amount_due, // Original due amount
                'total_paid_amount' => $totalPaid, // New: Total amount paid
                'remaining_balance' => max(0, $balanceDue), // New: The actual remaining due
                'status' => $invoice->status, // Use original status or derive from balanceDue
                'issued_at' => $invoice->issued_at,
            ];
        })->all();


        return Inertia::render('Accountant/StudentHistory/StudentHistory', [
            'student' => [
                'id'   => $student->id,
                'name' => $student->name,
            ],
            // These variables are now correctly defined above:
            'examHistory' => $examHistory,
            'groupedExamHistory' => $groupedExamHistory,
            'invoiceHistory' => $invoiceHistory,
        ]);
    }


    /**
     * Display a list of all students who passed the final exam.
    */

    public function show(string $id)
    {
        // Find the student with their related class, session, and section data
        $student = Student::with(['className', 'session', 'section'])->find($id);

        // 1. Check if the student object is found
        // dd($student);

        if (!$student) {
            return Inertia::render('Result/PassedStudents', [
                'student' => null
            ]);
        }

        return Inertia::render('Result/PassedStudents', [
            'student' => $student,
        ]);
    }

    public function passedStudents()
    {
        // Fetch exam results for students who passed the 'Yearly' exam.
        $passedStudents = ExamResult::with(['student.className', 'student.section', 'student.session', 'exam'])
            // Use a simple `where` clause to filter by the specific exam name.
            ->whereHas('exam', function ($query) {
                $query->where('exam_name', 'Yearly','yearly');
            })
            // Filter by the overall status, ensuring it's 'Pass'.
            ->where('overall_status', 'Pass')
            ->get()
            ->map(function ($result) {
                // Map the results to a convenient format for the frontend.
                return [
                    'id' => $result->student_id,
                    'name' => $result->student->name,
                    'className' => $result->student->className->class_name ?? 'N/A',
                    'session' => $result->student->session->name ?? 'N/A',
                    'section' => $result->student->section->name ?? 'N/A',
                    'overall_status' => $result->overall_status,
                ];
            });

        return Inertia::render('Result/PassedStudents', [
            'passedStudents' => $passedStudents,
        ]);
    }

   
    public function promoteStudents()
    {
        try {
            DB::beginTransaction();

            // Find all exam results with a 'Pass' status, eager loading the student relationship.
            $passedResults = ExamResult::with('student')
                ->where('overall_status', 'Pass')
                ->get();

            // If no students passed, roll back and return an error message.
            if ($passedResults->isEmpty()) {
                DB::rollBack();
                return back()->with('error', 'No students to promote.');
            }

            // Loop through each passed exam result to promote the student.
            foreach ($passedResults as $result) {
                $student = $result->student;
                if ($student) {
                    // Find the next class and session for promotion based on the current IDs.
                    $currentClassId = $student->class_id; // Changed to match your migration
                    $currentSessionId = $student->session_id;

                    $nextClass = ClassName::where('id', '>', $currentClassId)->orderBy('id')->first();
                    $nextSession = ClassSession::where('id', '>', $currentSessionId)->orderBy('id')->first();

                    // If a next class is found, update the student's class ID.
                    if ($nextClass) {
                        $student->class_id = $nextClass->id; // Changed to match your migration
                    }

                    // If a next session is found, update the student's session ID.
                    if ($nextSession) {
                        $student->session_id = $nextSession->id;
                    }

                    // Update the student's overall status to 'Promoted' to prevent re-promotion.
                    $result->overall_status = 'Promoted';

                    // Save the changes to both the student and the exam result records.
                    $student->save();
                    $result->save();
                }
            }

            // If all updates are successful, commit the transaction.
            DB::commit();

            return back()->with('success', 'All passed students have been successfully promoted!');
        } catch (\Exception $e) {
            // If any error occurs, roll back the transaction and return an error message.
            DB::rollBack();
            return back()->with('error', 'Promotion failed: ' . $e->getMessage());
        }
    }


}