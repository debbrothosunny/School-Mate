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
use Illuminate\Support\Facades\LOG;
use Illuminate\Support\Facades\DB; // For raw queries if needed, though Eloquent is better
use Illuminate\Notifications\DatabaseNotification; // Assuming you're using Laravel's built-in notifications
use App\Models\ClassTime; // Import ClassTime model
use Carbon\Carbon;

class DashboardController extends Controller
{

    public function index(Request $request)
    {
        $user  = $request->user();
        $roles = $user->getRoleNames();

        // role‐based redirects
        if ($roles->contains('accounts')) {
            return redirect()->route('accounts.dashboard');
        }
        if ($roles->contains('teacher')) {
            return redirect()->route('teacher.dashboard');
        }
        if ($roles->contains('student')) {
            return redirect()->route('student.dashboard');
        }

        // prepare default dashboard data
        $dashboardData = [
            'cards' => [
                'totalTeachers'             => 0,
                'totalSections'             => 0,
                'totalStudents'             => 0,
                'totalActiveClasses'        => 0,
                'totalSessions'             => 0,
                'totalGroups'               => 0,
                'monthlyCollection'         => 0,
                'monthlyDue'                => 0,
                'yearlyCollection'          => 0,
                'yearlyDue'                 => 0,
                'totalCollection'           => 0,
                'totalDue'                  => 0,
                'totalPendingRegistrations' => 0,
            ],
            'attendanceStats' => [
                'labels' => ['Present', 'Absent', 'Late'],
                'data'   => [0, 0, 0],
            ],
            'message'  => 'Welcome to your Dashboard!',
            'userName' => $user->name,
        ];

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth   = Carbon::now()->endOfMonth();
        $startOfYear  = Carbon::now()->startOfYear();
        $endOfYear    = Carbon::now()->endOfYear();

        // only admins see full overview
        if ($roles->contains('admin')) {
            // entity counts
            $dashboardData['cards']['totalTeachers']      = Teacher::count();
            $dashboardData['cards']['totalSections']      = Section::count();
            $dashboardData['cards']['totalStudents']      = Student::count();
            $dashboardData['cards']['totalActiveClasses'] = ClassName::where('status', 0)->count();
            $dashboardData['cards']['totalSessions']      = ClassSession::count();
            $dashboardData['cards']['totalGroups']        = Group::count();

            // pending student registrations
            $dashboardData['cards']['totalPendingRegistrations'] = User::role('student')
                ->whereDoesntHave('roles', fn($q) =>
                    $q->whereIn('name', ['admin','teacher','accounts'])
                )->count();

            // attendance this month
            $presentCount = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', 'present')->count();
            $absentCount  = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', 'absent')->count();
            $lateCount    = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
                ->where('status', 'late')->count();

            $dashboardData['attendanceStats']['data'] = [
                $presentCount,
                $absentCount,
                $lateCount,
            ];

            // invoice-based collections & dues
            $dashboardData['cards']['monthlyCollection'] = Invoice::whereBetween('issued_at', [$startOfMonth, $endOfMonth])
                ->sum('amount_paid');
            $dashboardData['cards']['monthlyDue']        = Invoice::whereBetween('issued_at', [$startOfMonth, $endOfMonth])
                ->sum('balance_due');

            $dashboardData['cards']['yearlyCollection'] = Invoice::whereBetween('issued_at', [$startOfYear, $endOfYear])
                ->sum('amount_paid');
            $dashboardData['cards']['yearlyDue']        = Invoice::whereBetween('issued_at', [$startOfYear, $endOfYear])
                ->sum('balance_due');

            $dashboardData['cards']['totalCollection'] = Invoice::sum('amount_paid');
            $dashboardData['cards']['totalDue']        = Invoice::sum('balance_due');

            // customized welcome message
            $dashboardData['message'] = 'Welcome, Admin! Here’s a complete school overview.';

            return Inertia::render('Dashboard', $dashboardData);
        }

        // fallback for users without a specific dashboard
        $dashboardData['message'] =
            'Your account is awaiting role assignment. Please wait for an administrator to review your registration.';

        return Inertia::render('UnassignedUserDashboard', $dashboardData);
    }

 
    public function teacherIndex(Request $request)
    {
        $user = $request->user();

        if (!$user->hasRole('teacher') || !$user->teacher) {
            abort(403, 'Unauthorized. You are not assigned as a teacher.');
        }

        $teacher = $user->teacher;

        // First, get the distinct class_name_ids associated with the teacher
        // Assuming 'classNames' is a belongsToMany relationship on the Teacher model
        // and it uses a pivot table (e.g., 'class_subjects' as seen in your error message)
        $distinctClassIds = $teacher->classNames()
                                    ->pluck('class_names.id') // Get only the IDs
                                    ->unique(); // Ensure unique IDs in case of pivot table duplicates

        // Now, fetch the ClassName models using these distinct IDs
        // and eager load all the necessary relationships
        $myClasses = ClassName::whereIn('id', $distinctClassIds)
                            ->with([
                                'section',
                                'students',
                                'classSchedules.room',
                                'examSchedules' => function ($query) use ($teacher) {
                                    $query->where('teacher_id', $teacher->id)
                                        ->with('room', 'subject');
                                }
                            ])
                            ->get();

        $dashboardData = [
            'myClasses' => $myClasses,
            'teacherName' => $teacher->name ?? $user->name,
            'message' => 'Welcome, Teacher!',
            'teacherSpecificStat1' => 'Value 1',
            'teacherSpecificStat2' => 'Value 2',
        ];

        return Inertia::render('TeacherDashboard', $dashboardData);
    }


    
    //  Student Dashboard 
    public function studentIndex(Request $request)
    {
        // Eager load the 'student' relationship to avoid an N+1 query problem.
        // This fetches the student data in the same query as the user.
        $user = User::with('student')->find($request->user()->id);

        // If the user isn't found or doesn't have the 'student' role, redirect.
        if (!$user || !$user->hasRole('student')) {
            return redirect()->route('dashboard');
        }

        $student = $user->student;

        // Initialize dashboard data with default values
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
            // New key for the upcoming fee notice
            'upcomingFeeNotice' => null,
        ];

        // Proceed only if a student profile exists for the user
        if ($student) {
            try {
                // Fetch timetable entries using the student's class, section, and session IDs.
                // Eager load all necessary relationships to prevent multiple queries in the view.
                $studentTimetable = ClassTime::where('class_name_id', $student->class_id)
                    ->where('section_id', $student->section_id)
                    ->where('session_id', $student->session_id)
                    ->with(['className', 'subject', 'teacher', 'section', 'session', 'room'])
                    ->orderBy('day_of_week')
                    ->orderBy('start_time')
                    ->get();

                // Fetch exam schedules with the same filtering and eager loading.
                $studentExams = ExamSchedule::where('class_id', $student->class_id)
                    ->where('section_id', $student->section_id)
                    ->where('session_id', $student->session_id)
                    ->with(['subject', 'room'])
                    ->orderBy('exam_date')
                    ->orderBy('start_time')
                    ->get();

                // Fetch all relevant attendance records in a single query
                $attendanceRecords = Attendance::where('student_id', $student->id)
                    ->where('class_id', $student->class_id)
                    ->where('section_id', $student->section_id)
                    ->where('session_id', $student->session_id)
                    ->get();

                $totalClasses = $attendanceRecords->count();
                // Filter the collection to count only 'present' records.
                $attendedClasses = $attendanceRecords->where('status', 'present')->count();

                // Calculate attendance percentage, safely handling division by zero.
                $attendancePercentage = ($totalClasses > 0) ? ($attendedClasses / $totalClasses) * 100 : 0;
                
                // --- NEW: Check for an upcoming tuition fee due date ---
                $today = Carbon::now()->startOfDay();
                $threeDaysFromNow = Carbon::now()->addDays(3)->endOfDay();
                
                $upcomingInvoice = Invoice::where('student_id', $student->id)
                                          ->where('status', '!=', 'paid')
                                          ->where('balance_due', '>', 0)
                                          ->whereBetween('due_date', [$today, $threeDaysFromNow])
                                          ->orderBy('due_date', 'asc')
                                          ->first();
                
                // Populate the dashboard data array
                $dashboardData['studentTimetable'] = $studentTimetable;
                $dashboardData['studentExams'] = $studentExams;
                $dashboardData['studentAttendance'] = [
                    'totalClasses' => $totalClasses,
                    'attendedClasses' => $attendedClasses,
                    'percentage' => round($attendancePercentage, 2), // Round to 2 decimal places
                ];
                $dashboardData['upcomingFeeNotice'] = $upcomingInvoice;

            } catch (\Exception $e) {
                // Log the error and provide a user-friendly message.
                Log::error("Error fetching student dashboard data: " . $e->getMessage());
                $dashboardData['message'] = 'There was an error fetching your data. Please contact an administrator.';
            }

        } else {
            // If the student profile doesn't exist, update the message.
            $dashboardData['message'] = 'Your student profile is not fully set up. Please contact an administrator.';
        }

        // Render the Inertia component with the final data.
        return Inertia::render('StudentDashboard', $dashboardData);
    }
}
