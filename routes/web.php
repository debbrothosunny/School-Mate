<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ClassNameController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\MyClassesController;
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\AttendanceController;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {
   

    // User Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ADMIN ONLY ROUTES (Consolidated Group)
    Route::middleware('role:admin')->group(function () {
        // User Management Routes
         // Dashboard Route
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole'])->name('users.assign-role');

        // SECTION MANAGEMENT ROUTES (Individual Definitions - corrected destroy)
        Route::get('/sections', [SectionController::class, 'index'])->name('sections.index');
        Route::get('/sections/create', [SectionController::class, 'create'])->name('sections.create'); // Add create route
        Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
        Route::get('/sections/{section}/edit', [SectionController::class, 'edit'])->name('sections.edit');
        Route::post('/sections/{section}', [SectionController::class, 'update'])->name('sections.update');
        Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy'); // CORRECT: DELETE method

        // TEACHER MANAGEMENT ROUTES (Individual Definitions)
        Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::get('/teachers/create', [TeacherController::class, 'create'])->name('teachers.create');
        Route::post('/teachers', [TeacherController::class, 'store'])->name('teachers.store');
        Route::get('/teachers/{teacher}/edit', [TeacherController::class, 'edit'])->name('teachers.edit');
        Route::post('/teachers/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');

       // CLASS MANAGEMENT ROUTES (Individual Definitions) - Adjusted for ClassNameController and  schema
        Route::get('/class-names', [ClassNameController::class, 'index'])->name('class-names.index');
        Route::get('/class-names/create', [ClassNameController::class, 'create'])->name('class-names.create');
        Route::post('/class-names', [ClassNameController::class, 'store'])->name('class-names.store');
        Route::get('/class-names/{className}/edit', [ClassNameController::class, 'edit'])->name('class-names.edit');
        Route::post('/class-names/{className}', [ClassNameController::class, 'update'])->name('class-names.update');
        Route::delete('/class-names/{className}', [ClassNameController::class, 'destroy'])->name('class-names.destroy');


        // STUDENT MANAGEMENT ROUTES (Individual Definitions) <--- NEW SECTION
        Route::get('/students', [StudentController::class, 'index'])->name('students.index');
        Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        Route::post('/students/{student}', [StudentController::class, 'update'])->name('students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');

    });


    // TEACHER ONLY ROUTES
    Route::middleware('role:teacher')->group(function () {
        Route::get('/my-classes', [MyClassesController::class, 'index'])->name('my-classes.index');
        Route::get('/teacher/dashboard', [DashboardController::class, 'teacherIndex'])->name('teacher.dashboard');
    });

    // ACCOUNTS ONLY ROUTES (New dedicated route)
    Route::middleware('role:accounts')->group(function () {
        Route::get('/accounts/dashboard', [AccountsController::class, 'index'])->name('accounts.dashboard');
    });

   

});

require __DIR__.'/auth.php';
