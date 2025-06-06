<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon; // For date manipulation
use App\Models\Teacher; 
use App\Models\Section;  
use App\Models\Student;    
use App\Models\ClassName;  
use App\Models\Attendance;  
use App\Models\User;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $roles = $user->getRoleNames();

        // Redirect to Accounts Dashboard if user has 'accounts' role
        if ($roles->contains('accounts')) {
            return redirect()->route('accounts.dashboard');
        }

        // Redirect to Teacher Dashboard if user has 'teacher' role
        if ($roles->contains('teacher')) {
            return redirect()->route('teacher.dashboard');
        }

        // Initialize dashboard data with default/zero values for Admin/Unassigned User
        $dashboardData = [
            'cards' => [
                'totalTeachers' => 0,
                'totalSections' => 0,
                'totalStudents' => 0,
                'totalActiveClasses' => 0,
                'totalPresent' => 0,
                'totalAbsent' => 0,
                // Removed 'totalPendingRegistrations'
            ],
            'attendanceStats' => [
                'labels' => ['Present', 'Absent', 'Late'],
                'data' => [0, 0, 0],
            ],
            'message' => 'Welcome to your Dashboard!',
            'userName' => $user->name, // Ensure userName is passed
        ];

        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        if ($roles->contains('admin')) {
            // Admin sees global stats
            $dashboardData['cards'] = [
                'totalTeachers' => Teacher::count(),
                'totalSections' => Section::count(),
                'totalStudents' => Student::count(),
                'totalActiveClasses' => ClassName::where('status', 0)->count(),
            ];

            // Removed pending registrations count logic (as roles are now auto-assigned)

            $presentCount = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
                                     ->where('status', 'present')
                                     ->count();
            $absentCount = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
                                     ->where('status', 'absent')
                                     ->count();
            $lateCount = Attendance::whereBetween('date', [$startOfMonth, $endOfMonth])
                                   ->where('status', 'late')
                                   ->count();

            $dashboardData['attendanceStats']['data'] = [$presentCount, $absentCount, $lateCount];
            $dashboardData['message'] = 'Welcome, Admin! Here\'s an overview of the school.';

            $dashboardData['cards']['totalPresent'] = $presentCount;
            $dashboardData['cards']['totalAbsent'] = $absentCount;

            return Inertia::render('Dashboard', $dashboardData); // Admin dashboard

        } else {
            // Default: Unassigned User Dashboard
            // This dashboard will now be for users who were assigned 'student' role
            // but don't have 'admin', 'teacher', or 'accounts' roles.
            $dashboardData['message'] = 'Welcome to your dashboard! Your role has been assigned based on your registration. If you believe there is an error, please contact an administrator.';
            return Inertia::render('UnassignedUserDashboard', $dashboardData); // Render the new component
        }
    }


    // NEW METHOD FOR TEACHER SPECIFIC DASHBOARD
    public function teacherIndex(Request $request)
    {
        $user = $request->user();

        // Ensure the user is a teacher and has an associated teacher record
        if (!$user->hasRole('teacher') || !$user->teacher) {
            abort(403, 'Unauthorized. You are not assigned as a teacher.');
        }

        $teacher = $user->teacher;

        // Fetch classes assigned to this teacher
        // Assuming a many-to-many relationship between Teacher and ClassName
        $myClasses = $teacher->classNames()->with('section')->get();

        // You can add more teacher-specific dashboard data here
        $dashboardData = [
            'myClasses' => $myClasses,
            'teacherName' => $teacher->name ?? $user->name,
            'message' => 'Welcome, Teacher! This is your personalized dashboard.',
            // Add other teacher-specific stats or data here
            'teacherSpecificStat1' => 'Value 1',
            'teacherSpecificStat2' => 'Value 2',
        ];

        return Inertia::render('TeacherDashboard', $dashboardData); // You'll need to create this Vue page
    }
}