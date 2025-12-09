<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\ClassName;
use App\Models\ExamSchedule;
use App\Models\Teacher;
use App\Models\Section;
use App\Models\Student;
use App\Models\Attendance;

use App\Models\Invoice;
use App\Models\ClassSession;
use App\Models\Group;
use App\Models\User;
use App\Models\PayrollRecord; // **Crucial for your payroll data**
use Illuminate\Support\Facades\LOG;
use Illuminate\Support\Facades\DB; // For raw queries if needed, though Eloquent is better
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification; // Assuming you're using Laravel's built-in notifications
use App\Models\ClassTime; // Import ClassTime model
use Carbon\Carbon;

class DashboardController extends Controller
{

    public function index(Request $request)
    {
        

        $user = $request->user();
    $roles = $user->getRoleNames();

    // --- Role-Based Redirects ---
    if ($roles->contains('accounts')) {
        return redirect()->route('accounts.dashboard');
    }
    if ($roles->contains('teacher')) {
        return redirect()->route('teacher.dashboard');
    }
    if ($roles->contains('student')) {
        return redirect()->route('student.dashboard');
    }
    if ($roles->contains('front-desk')) {
        return redirect()->route('front-desk.dashboard');
    }

    // --- Prepare Default Dashboard Data ---
    $dashboardData = [
        'cards' => [
            'totalTeachers' => 0,
            'totalSections' => 0,
            'totalStudents' => 0,
            'totalActiveClasses' => 0,
            'totalSessions' => 0,
            'totalGroups' => 0,
            'monthlyCollection' => 0,
            'monthlyDue' => 0,
            'monthlyProfit' => 0, 
            'yearlyCollection' => 0,
            'yearlyDue' => 0,
            'yearlyProfit' => 0,
            'totalCollection' => 0,
            'totalDue' => 0,
            'overallProfit' => 0,
            'totalPendingRegistrations' => 0,
            'totalAdmissionFeeCollected' => 0, // 💡 NEW: Added default value for the new card
        ],
        'attendanceStats' => [
            'labels' => ['Present', 'Absent', 'Late'],
            'data' => [0, 0, 0],
        ],
        'message' => "Good to have you back, Admin. Let's explore your school's data today.",
        'userName' => $user->name,
    ];

    // --- Define Time Boundaries ---
    $startOfMonth = Carbon::now()->startOfMonth();
    $endOfMonth = Carbon::now()->endOfMonth();
    $startOfYear = Carbon::now()->startOfYear();
    $endOfYear = Carbon::now()->endOfYear();

    // --- Admin Dashboard Logic (Full Overview) ---
    if ($roles->contains('admin')) {
        // Entity Counts 
        $dashboardData['cards']['totalTeachers'] = Teacher::count();
        $dashboardData['cards']['totalSections'] = Section::count();
        $dashboardData['cards']['totalStudents'] = Student::count();
        $dashboardData['cards']['totalActiveClasses'] = ClassName::where('status', 0)->count();
        $dashboardData['cards']['totalSessions'] = ClassSession::count();
        $dashboardData['cards']['totalGroups'] = Group::count();
        
        // Pending student registrations
        $dashboardData['cards']['totalPendingRegistrations'] = User::role('student')
            ->whereDoesntHave('roles', fn($q) =>
                $q->whereIn('name', ['admin','teacher','accounts'])
            )->count();
        
        // Attendance this month
        $presentCount = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'present')->count();
        $absentCount = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'absent')->count();
        $lateCount = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'late')->count();
        $dashboardData['attendanceStats']['data'] = [
            $presentCount,
            $absentCount,
            $lateCount,
        ];
        
        // 1. REVENUE (Collection) Calculation (from Invoices)
        $monthlyCollection = Invoice::whereBetween('issued_at', [$startOfMonth, $endOfMonth])->sum('amount_paid');
        $yearlyCollection = Invoice::whereBetween('issued_at', [$startOfYear, $endOfYear])->sum('amount_paid');
        $totalCollection = Invoice::sum('amount_paid');
        
        // 2. EXPENSE (Payroll) Calculation
        $monthlyExpense = PayrollRecord::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->sum('net_payable');
        $yearlyExpense = PayrollRecord::whereBetween('payment_date', [$startOfYear, $endOfYear])
            ->sum('net_payable');
        $overallExpense = PayrollRecord::sum('net_payable');
        
        // 3. PROFIT Calculation
        $monthlyProfit = $monthlyCollection - $monthlyExpense;
        $yearlyProfit = $yearlyCollection - $yearlyExpense;
        $overallProfit = $totalCollection - $overallExpense;
        
        // 4. 💡 NEW: Admission Fee Collection Calculation
        // Sum the admission_fee_amount only for students who have paid (admission_fee_paid = true/1)
        $totalAdmissionFeeCollectedInSubunits = Student::where('admission_fee_paid', true)
            ->sum('admission_fee_amount');
        
        $totalAdmissionFeeCollected = $totalAdmissionFeeCollectedInSubunits / 100; // Convert from subunits
        
        // --- Map Financial Results to the dashboardData array ---
        
        // Collections & Dues
        $dashboardData['cards']['monthlyCollection'] = $monthlyCollection;
        $dashboardData['cards']['monthlyDue'] = Invoice::whereBetween('issued_at', [$startOfMonth, $endOfMonth])->sum('balance_due');
        $dashboardData['cards']['yearlyCollection'] = $yearlyCollection;
        $dashboardData['cards']['yearlyDue'] = Invoice::whereBetween('issued_at', [$startOfYear, $endOfYear])->sum('balance_due');
        $dashboardData['cards']['totalCollection'] = $totalCollection;
        $dashboardData['cards']['totalDue'] = Invoice::sum('balance_due');
        
        // PROFIT
        $dashboardData['cards']['monthlyProfit'] = $monthlyProfit;
        $dashboardData['cards']['yearlyProfit'] = $yearlyProfit;
        $dashboardData['cards']['overallProfit'] = $overallProfit;

        // 💡 NEW MAPPING
        $dashboardData['cards']['totalAdmissionFeeCollected'] = $totalAdmissionFeeCollected;
        
        // customized welcome message
        $dashboardData['message'] = 'Welcome, Sir! Here’s a complete school overview.';
        return Inertia::render('Dashboard', $dashboardData);
    }

    // --- Fallback for Unassigned Users ---
    $dashboardData['message'] =
        'Your account is awaiting role assignment. Please wait for an administrator to review your registration.';
    return Inertia::render('UnassignedUserDashboard', $dashboardData);
    }

    
    //  Teacher Dashboard
    public function teacherIndex()
    {
        $userId = Auth::user()->id;

        // Get the teacher tied to this user
        $teacher = Teacher::where('user_id', $userId)->first();

        if (!$teacher) {
            return redirect()->route('home')->with('error', 'You are not assigned as a teacher.');
        }

        $teacherId = $teacher->id;

        // Get unique class IDs assigned to this teacher
        $assignedClassIds = DB::table('class_times')
            ->where('teacher_id', $teacherId)
            ->pluck('class_name_id')
            ->unique()
            ->toArray();

        if (empty($assignedClassIds)) {
            $myClasses = collect();
        } else {
            $myClasses = ClassName::whereIn('id', $assignedClassIds)
                ->with([
                    'students',
                    'classSchedules' => function ($query) use ($teacherId) {
                        $query->where('teacher_id', $teacherId)
                            ->with(['room', 'classTimeSlot']);
                    },
                    'examSchedules' => function ($query) use ($teacherId) {
                        $query->where('teacher_id', $teacherId)
                            ->with(['subject', 'room', 'examSlot']);
                    },
                    'section'
                ])->get();
        }

        return Inertia::render('TeacherDashboard', [
            'message' => 'Welcome to your dashboard!',
            'teacherName' => $teacher->name,
            'myClasses' => $myClasses,
        ]);
    }

    
    //  Student Dashboard 
    public function studentIndex(Request $request)
    {
        $user = User::with('student')->find($request->user()->id);

        if (!$user || !$user->hasRole('student')) {
            return redirect()->route('dashboard');
        }

        $student = $user->student;

        // Log the student details for debugging
        Log::info('StudentDashboard - User ID: ' . $user->id);
        if ($student) {
            Log::info('StudentDashboard - Student details:', [
                'student_id' => $student->id,
                'class_id' => $student->class_id,
                'section_id' => $student->section_id,
                'session_id' => $student->session_id,
            ]);
        }

        $dashboardData = [
            'message' => 'Welcome back, ' . $user->name . '! Here an overview of your academic life.',
            'userName' => $user->name,
            'studentTimetable' => [],
            'studentExams' => [],
            'studentAttendance' => [
                'totalClasses' => 0,
                'attendedClasses' => 0,
                'percentage' => 0,
            ],
            'upcomingFeeNotice' => null,
        ];

        if ($student) {
            try {
                // Timetable Entries: Eager load the 'classTimeSlot' relationship
                $studentTimetable = ClassTime::where('class_name_id', $student->class_id)
                    ->where('section_id', $student->section_id)
                    ->where('session_id', $student->session_id)
                    ->with(['className', 'subject', 'teacher', 'section', 'session', 'room', 'classTimeSlot'])
                    ->orderBy('day_of_week')
                    ->orderBy('class_time_slot_id')
                    ->get();
                
                // Log the count of timetable entries found
                Log::info('StudentDashboard - Timetable entries found: ' . $studentTimetable->count());

                // Exam Schedules: Eager load the 'examSlot' relationship
                $studentExams = ExamSchedule::where('class_id', $student->class_id)
                    ->where('section_id', $student->section_id)
                    ->where('session_id', $student->session_id)
                    ->with(['subject', 'room', 'examSlot'])
                    ->orderBy('exam_date')
                    ->orderBy('exam_slot_id') // Corrected column name
                    ->get();

                // Log the count of exam schedule entries found
                Log::info('StudentDashboard - Exam schedule entries found: ' . $studentExams->count());

                // Attendance Records
                $attendanceRecords = Attendance::where('student_id', $student->id)
                    ->where('class_id', $student->class_id)
                    ->where('section_id', $student->section_id)
                    ->where('session_id', $student->session_id)
                    ->get();

                $totalClasses = $attendanceRecords->count();

                $attendedClasses = $attendanceRecords->where('status', 'present')->count();

                $attendancePercentage = ($totalClasses > 0) ? ($attendedClasses / $totalClasses) * 100 : 0;

                // Upcoming Fees Notice (due in next 3 days)
                $today = Carbon::now()->startOfDay();
                $threeDaysFromNow = Carbon::now()->addDays(3)->endOfDay();

                $upcomingInvoice = Invoice::where('student_id', $student->id)
                    ->where('status', '!=', 'paid')
                    ->where('balance_due', '>', 0)
                    ->whereBetween('due_date', [$today, $threeDaysFromNow])
                    ->orderBy('due_date')
                    ->first();

                $dashboardData['studentTimetable'] = $studentTimetable;
                $dashboardData['studentExams'] = $studentExams;
                $dashboardData['studentAttendance'] = [
                    'totalClasses' => $totalClasses,
                    'attendedClasses' => $attendedClasses,
                    'percentage' => round($attendancePercentage, 2),
                ];
                $dashboardData['upcomingFeeNotice'] = $upcomingInvoice;
            } catch (\Exception $e) {
                Log::error("Error fetching student dashboard data: " . $e->getMessage());
                $dashboardData['message'] = 'Catch up on your progress here.';
            }
        } else {
            $dashboardData['message'] = 'Your student profile is not fully set up. Please contact an administrator.';
        }

        return Inertia::render('StudentDashboard', $dashboardData);
    }


    //  frontDesk Dashboard 
    public function frontDeskIndex()
    {
        $user = Auth::user();
        
        // Prepare the base dashboard data for the front desk
        $dashboardData = [
            'message' => 'Welcome, ' . $user->name . '! Here’s a quick overview of your front desk tasks.',
            'userName' => $user->name,
            'cards' => [
                'totalStudents' => 0,
                'totalTeachers' => 0,
                'totalPendingRegistrations' => 0,
                'totalActiveClasses' => 0,
            ],
            'recentStudents' => [],
            'recentTeachers' => [],
            'recentInvoices' => [],
        ];

        try {
            // Get total counts
            $dashboardData['cards']['totalStudents'] = Student::count();
            $dashboardData['cards']['totalTeachers'] = Teacher::count();
            $dashboardData['cards']['totalActiveClasses'] = ClassName::where('status', 0)->count();

            // Get pending student registrations.

            // This queries for users with the 'student' role that do not have any other administrator-level roles.
            $dashboardData['cards']['totalPendingRegistrations'] = User::role('student')
                ->whereDoesntHave('roles', fn($q) =>
                    $q->whereIn('name', ['admin', 'teacher', 'accounts'])
                )
                ->count();

            // Fetch recent student and teacher records
            $dashboardData['recentStudents'] = Student::with('user', 'className', 'section')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
            
            $dashboardData['recentTeachers'] = Teacher::with('user')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();

            // Fetch recent invoices
            $dashboardData['recentInvoices'] = Invoice::with('student')
                ->orderBy('issued_at', 'desc')
                ->take(5)
                ->get();

        } catch (\Exception $e) {
            Log::error("Error fetching front desk dashboard data: " . $e->getMessage());
            $dashboardData['message'] = 'An error occurred while loading your dashboard data.';
        }

        return Inertia::render('FrontDeskDashboard', $dashboardData);
    }
}
