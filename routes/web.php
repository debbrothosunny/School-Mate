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
use App\Http\Controllers\AccountsController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ClassTimeController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\MarkController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BusScheduleController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\FeeController;
use App\Http\Controllers\SalaryController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
    
});

Route::post('/check-contact', [RegisteredUserController::class, 'checkContactInfo'])->name('check.contact');


// New route for teacher login by joining number
Route::post('/teacher-login', [UserController::class, 'teacherLogin'])->name('teacher.login');

// New route for teacher login by joining number
Route::post('/student-login', [UserController::class, 'studentLogin'])->name('student.login');

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
        Route::get('/sections/create', [SectionController::class, 'create'])->name('sections.create'); 
        Route::post('/sections', [SectionController::class, 'store'])->name('sections.store');
        Route::get('/sections/{section}/edit', [SectionController::class, 'edit'])->name('sections.edit');
        Route::post('/sections/{section}', [SectionController::class, 'update'])->name('sections.update');
        Route::delete('/sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy'); 
        
        // Session MANAGEMENT ROUTES (Individual Definitions - corrected destroy)
        Route::get('/sessions', [SectionController::class, 'sessionIndex'])->name('sessions.index');
        Route::get('/sessions/create', [SectionController::class, 'sessionCreate'])->name('sessions.create'); 
        Route::post('/sessions', [SectionController::class, 'sessionStore'])->name('sessions.store');
        Route::get('/sessions/{session}/edit', [SectionController::class, 'sessionEdit'])->name('sessions.edit');
        Route::post('/sessions/{session}', [SectionController::class, 'sessionUpdate'])->name('sessions.update');
        Route::delete('/sessions/{session}', [SectionController::class, 'sessionDestroy'])->name('sessions.destroy'); 

        // SUBJECT MANAGEMENT ROUTES
        Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
        Route::get('/subjects/create', [SubjectController::class, 'create'])->name('subjects.create');
        Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
        Route::get('/subjects/{subject}/edit', [SubjectController::class, 'edit'])->name('subjects.edit');
        Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
        Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');


        // Class Subject Management Routes
        Route::get('/class-subjects', [SubjectController::class, 'classSubjectIndex'])->name('class-subjects.index');
        Route::get('/class-subjects/create', [SubjectController::class, 'classSubjectCreate'])->name('class-subjects.create');
        Route::post('/class-subjects', [SubjectController::class, 'classSubjectStore'])->name('class-subjects.store');
        Route::get('/class-subjects/{class_subject}/edit', [SubjectController::class, 'classSubjectEdit'])->name('class-subjects.edit');
        Route::post('/class-subjects/{class_subject}', [SubjectController::class, 'classSubjectUpdate'])->name('class-subjects.update');
        Route::delete('/class-subjects/{class_subject}', [SubjectController::class, 'classSubjectDestroy'])->name('class-subjects.destroy');

        Route::get('/class-subjects/get-filtered-data', [SubjectController::class, 'getFilteredData'])->name('class-subjects.getFilteredData');






        // Route for displaying Class Time Slot
        Route::get('/class-time-slots', [ClassTimeController::class, 'classTimeSlotIndex'])->name('class-time-slots.index');

        // Route for displaying the form to create a Class Time Slot
        Route::get('/class-time-slots/create', [ClassTimeController::class, 'classTimeSlotCreate'])->name('class-time-slots.create');

        // Route for storing a new Class Time Slot
        Route::post('/class-time-slots', [ClassTimeController::class, 'classTimeSlotStore'])->name('class-time-slots.store');

        // Route for showing the Class Time SlotEdit Form
        Route::get('/class-time-slots/{classTimeSlot}/edit', [ClassTimeController::class, 'classTimeSlotEdit'])->name('class-time-slots.edit');

        // Route for updating an Class Time Slot
        Route::put('/class-time-slots/{classTimeSlot}', [ClassTimeController::class, 'classTimeSlotUpdate'])->name('class-time-slots.update');

        // Route for deleting Class Time Slot
        Route::delete('/class-time-slots/{classTimeSlot}', [ClassTimeController::class, 'classTimeSlotDestroy'])->name('class-time-slots.destroy');




        // Route for displaying the main timetable index with filtering capabilities
        Route::get('/timetables', [ClassTimeController::class, 'index'])->name('timetable.index');

        // Route for displaying the form to create a new timetable entry
        Route::get('/timetables/create', [ClassTimeController::class, 'create'])->name('timetable.create');

        // Route for storing a new timetable entry
        Route::post('/timetables', [ClassTimeController::class, 'store'])->name('timetable.store');

        // Route for showing the edit form for a specific timetable entry
        Route::get('/timetables/{timetableEntry}/edit', [ClassTimeController::class, 'edit'])->name('timetable.edit');

        // Route for updating an existing timetable entry
        Route::post('/timetables/{timetableEntry}', [ClassTimeController::class, 'update'])->name('timetable.update');

        // Route for deleting a timetable entry
        Route::delete('/timetables/{timetableEntry}', [ClassTimeController::class, 'destroy'])->name('timetable.destroy');


         Route::post('/timetables/check-conflicts', [ClassTimeController::class, 'checkConflicts'])->name('timetable.checkConflicts');

         
         // NEW API endpoint to get occupied slots for real-time display
        Route::get('/timetables/occupied-slots', [ClassTimeController::class, 'getOccupiedSlots'])->name('timetable.getOccupiedSlots');
  
        // This is used by the frontend modal to populate dropdowns.
        Route::get('/timetables/filtered-data', [ClassTimeController::class, 'getFilteredData'])
        ->name('timetable.getFilteredData');

        // PDF Generation Route for Timetable
        Route::get('/timetable/pdf', [ClassTimeController::class, 'generatePDF'])->name('timetable.pdf');


        

       // CLASS MANAGEMENT ROUTES (Individual Definitions) - Adjusted for ClassNameController and  schema
        Route::get('/class-names', [ClassNameController::class, 'index'])->name('class-names.index');
        Route::get('/class-names/create', [ClassNameController::class, 'create'])->name('class-names.create');
        Route::post('/class-names', [ClassNameController::class, 'store'])->name('class-names.store');
        Route::get('/class-names/{className}/edit', [ClassNameController::class, 'edit'])->name('class-names.edit');
        Route::post('/class-names/{className}', [ClassNameController::class, 'update'])->name('class-names.update');
        Route::delete('/class-names/{className}', [ClassNameController::class, 'destroy'])->name('class-names.destroy');


        // TEACHER ATTENDANCE ROUTES
        // 1. Index (List all attendance, potentially for a date range/day)
        Route::get('/attendance/teachers', [AttendanceController::class, 'index'])->name('attendance.teachers.index');
        
        
        // 3. Store (Handle creation/daily marking) - This can be the most used POST
        Route::post('/attendance/teachers', [AttendanceController::class, 'store'])->name('attendance.teachers.store');
        
        

        // 5. Update (Handle update submission)
        Route::put('/attendance/teachers/{teacherAttendance}', [AttendanceController::class, 'update'])->name('attendance.teachers.update');


        Route::get('attendance/teachers/print/range', [AttendanceController::class, 'printRangePdf'])->name('attendance.teachers.print.range');



        
        // Student Management
        // NEW: Placing specific student routes at the top to avoid conflicts.
        Route::post('/students/promote', [StudentController::class, 'promoteStudents'])->name('students.promote');
        Route::get('/students/passed', [StudentController::class, 'passedStudents'])->name('students.passed');

        Route::get('/attendance-report', [AttendanceController::class, 'report'])->name('attendance.report');
        Route::get('/attendance-report/pdf', [AttendanceController::class, 'exportPDF'])->name('attendance.report.pdf');







        // Exam Management Routes
        Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
        Route::get('/exams/create', [ExamController::class, 'create'])->name('exams.create');
        Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
        Route::get('/exams/{exam}/edit', [ExamController::class, 'edit'])->name('exams.edit');
        Route::post('/exams/{exam}', [ExamController::class, 'update'])->name('exams.update');
        Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])->name('exams.destroy');




        // Display the index page with a list of all time slots.
        Route::get('/exam-time-slots', [ExamController::class, 'examTimeSlotIndex'])
            ->name('exam-time-slots.index');

        // Display the form to create a new time slot.
        Route::get('/exam-time-slots/create', [ExamController::class, 'examTimeSlotCreate'])
            ->name('exam-time-slots.create');

        // Store a new time slot in the database.
        Route::post('/exam-time-slots', [ExamController::class, 'examTimeSlotStore'])
            ->name('exam-time-slots.store');

        // Display the form to edit an existing time slot.
        // The route receives the ExamTimeSlot model instance through route model binding.
        Route::get('/exam-time-slots/{examTimeSlot}/edit', [ExamController::class, 'examTimeSlotEdit'])
            ->name('exam-time-slots.edit');

        // Update an existing time slot in the database.
        // The route receives the ExamTimeSlot model instance through route model binding.
        Route::put('/exam-time-slots/{examTimeSlot}', [ExamController::class, 'examTimeSlotUpdate'])
            ->name('exam-time-slots.update');

        // Delete a time slot from the database.
        // The route receives the ExamTimeSlot model instance through route model binding.
        Route::delete('/exam-time-slots/{examTimeSlot}', [ExamController::class, 'examTimeSlotDestroy'])
            ->name('exam-time-slots.destroy');



        Route::get('/exam-schedules', [ExamController::class, 'ExamSchdeuleIndex'])->name('exam-schedules.index');
        Route::get('/exam-schedules/create', [ExamController::class, 'ExamSchdeuleCreate'])->name('exam-schedules.create');
        Route::post('/exam-schedules', [ExamController::class, 'ExamSchdeuleStore'])->name('exam-schedules.store');
        Route::get('/exam-schedules/{examSchedule}/edit', [ExamController::class, 'ExamSchdeuleEdit'])->name('exam-schedules.edit');
        // Using put for update as per RESTful conventions, but your controller uses POST - ensure consistency
        Route::put('/exam-schedules/{examSchedule}', [ExamController::class, 'ExamSchdeuleUpdate'])->name('exam-schedules.update');
        Route::delete('/exam-schedules/{examSchedule}', [ExamController::class, 'ExamSchdeuleDestroy'])->name('exam-schedules.destroy');

        // New route for fetching available resources (rooms, teachers)
        Route::get('/exam-schedules/available-resources', [ExamController::class, 'getAvailableResources'])->name('exam-schedules.available-resources');





        // Route to display and manage seat plans for a specific exam schedule


        Route::get('/class-section-seat-plan', [ExamController::class, 'show'])->name('class-section-seat-plan.show');
        Route::post('/class-section-seat-plan/auto-assign', [ExamController::class, 'autoAssign'])->name('class-section-seat-plan.auto-assign');
        Route::post('/class-section-seat-plan/store', [ExamController::class, 'seatStore'])->name('class-section-seat-plan.store');
        Route::get('/class-section-seat-plan/pdf', [ExamController::class, 'generateSeatPlanPDF'])->name('class-section-seat-plan.pdf');


        // Marks Router

        // Route::get('/marks', [MarkController::class, 'index'])->name('marks.index');
        // Route::post('/marks', [MarkController::class, 'store'])->name('marks.store');



        // Books Route
        Route::get('/books', [BookController::class, 'index'])->name('books.index');
        Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
        Route::post('/books', [BookController::class, 'store'])->name('books.store');
        Route::get('/books/edit/{book}', [BookController::class, 'edit'])->name('books.edit');
        // Using POST for update as per your input, consider PUT/PATCH for RESTful consistency
        Route::post('/books/update/{book}', [BookController::class, 'update'])->name('books.update');
        // Note: The destroy method in BookController currently handles BorrowRecord deletion.
        // If this route is for deleting Books, ensure your BookController has a destroy(Book $book) method
        // that handles Book model deletion.
        Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');


        // Student Borrow Book Functionality Route
        Route::post('/books/{book}/borrow', [BookController::class, 'borrowBookStore'])
        ->name('books.borrow.store');

  
        // Admin Borrow Records Management (URIs adjusted)
        Route::get('/borrow-records', [BookController::class, 'adminBorrowRecordsIndex'])->name('borrow-records.index');

        Route::put('/borrows/{borrow}/return', [BookController::class, 'returnBook'])->name('borrows.return');




        // Route::post('/borrow-records/{borrowBook}/approve', [BookController::class, 'approveReturn'])->name('borrow-records.approve-return');
        // Route::post('/borrow-records/{borrowBook}/mark-lost', [BookController::class, 'markAsLost'])->name('borrow-records.mark-lost');




        // Bus Schedules Route
        Route::get('/bus-schedules', [BusScheduleController::class, 'index'])->name('bus-schedules.index');
        Route::get('/bus-schedules/create', [BusScheduleController::class, 'create'])->name('bus-schedules.create');
        Route::post('/bus-schedules', [BusScheduleController::class, 'store'])->name('bus-schedules.store');
        Route::get('/bus-schedules/edit/{busSchedule}', [BusScheduleController::class, 'edit'])->name('bus-schedules.edit');
        // Using POST for update as per your input, consider PUT/PATCH for RESTful consistency
        Route::post('/bus-schedules/update/{busSchedule}', [BusScheduleController::class, 'update'])->name('bus-schedules.update');
        Route::delete('/bus-schedules/{busSchedule}', [BusScheduleController::class, 'destroy'])->name('bus-schedules.destroy');
        
        
        
        
        
        // Group  Route
        Route::get('/groups', [ClassNameController::class, 'groupIndex'])->name('groups.index');
        Route::get('/groups/create', [ClassNameController::class, 'groupCreate'])->name('groups.create');
        Route::post('/groups', [ClassNameController::class, 'groupStore'])->name('groups.store');
        Route::get('/groups/edit/{group}', [ClassNameController::class, 'groupEdit'])->name('groups.edit');
        // Using POST for update as per your input, consider PUT/PATCH for RESTful consistency
        Route::post('/groups/update/{group}', [ClassNameController::class, 'groupUpdate'])->name('groups.update');
        Route::delete('/groups/{group}', [ClassNameController::class, 'groupDestroy'])->name('groups.destroy');



        // ✨ NEW Grade Configuration Routes ✨
        Route::get('/grade-configurations', [ResultController::class, 'index'])->name('grade-configurations.index');
        Route::get('/grade-configurations/create', [ResultController::class, 'create'])->name('grade-configurations.create');
        Route::post('/grade-configurations', [ResultController::class, 'store'])->name('grade-configurations.store');
        Route::get('/grade-configurations/{gradeConfiguration}/edit', [ResultController::class, 'edit'])->name('grade-configurations.edit');
        Route::post('/grade-configurations/{gradeConfiguration}', [ResultController::class, 'update'])->name('grade-configurations.update');
        Route::delete('/grade-configurations/{gradeConfiguration}', [ResultController::class, 'destroy'])->name('grade-configurations.destroy');


        //Exam Result Route

        Route::get('/results/show', [ResultController::class, 'showResults'])->name('results.show');

        Route::post('/results/finalize/{exam}', [ResultController::class, 'storeExamResults'])->name('results.store');

    

        Route::get('/results/download-pdf-all', [ResultController::class, 'downloadPdfAll'])->name('results.download-pdf-all');



        // Define this route, accessible via GET for downloading/streaming
        Route::get('/results/download-all-pdf', [ResultController::class, 'downloadAllResultsPdf'])->name('results.download-all-pdf');



        // Route for the Payment History page
        Route::get('/payments/history', [FeeController::class, 'paymentHistoryindex'])->name('admin.payments.history');

        
        






        // SETTINGS MANAGEMENT ROUTES (Individual Definitions, in the same style)
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::get('/settings/create', [SettingController::class, 'create'])->name('settings.create');
        Route::post('/settings', [SettingController::class, 'store'])->name('settings.store');
        Route::get('/settings/edit', [SettingController::class, 'edit'])->name('settings.edit');
        Route::post('/settings/update', [SettingController::class, 'update'])->name('settings.update');
        Route::delete('/settings', [SettingController::class, 'destroy'])->name('settings.destroy');



        //  // ✨ NEW Class Fee Structure Routes ✨
        // // Display a listing of the class fee structures
        // Route::get('/class-fee-structures', [FeeController::class, 'index'])->name('class-fee-structures.index');

        // // Show the form for creating a new class fee structure
        // Route::get('/class-fee-structures/create', [FeeController::class, 'create'])->name('class-fee-structures.create');

        // // Store a newly created class fee structure in storage
        // Route::post('/class-fee-structures', [FeeController::class, 'store'])->name('class-fee-structures.store');

        // // Display the specified class fee structure (optional, but standard for resource routes)
        // Route::get('/class-fee-structures/{classFeeStructure}', [FeeController::class, 'show'])->name('class-fee-structures.show');

        // // Show the form for editing the specified class fee structure
        // Route::get('/class-fee-structures/{classFeeStructure}/edit', [FeeController::class, 'edit'])->name('class-fee-structures.edit');

        // // Update the specified class fee structure in storage
        // // Note: Standard RESTful practice for update is PUT or PATCH.
        // // If your frontend form method is POST, you'll need to use @method('PUT') in Vue.
        // Route::put('/class-fee-structures/{classFeeStructure}', [FeeController::class, 'update'])->name('class-fee-structures.update');

        // // Remove the specified class fee structure from storage
        // Route::delete('/class-fee-structures/{classFeeStructure}', [FeeController::class, 'destroy'])->name('class-fee-structures.destroy');



        // // Display a listing of the student fee assignments
        // Route::get('/student-fee-assignments', [FeeController::class, 'StudentFeeAssignmentIndex'])->name('student-fee-assignments.index');

        // // Show the form for creating a new student fee assignment
        // Route::get('/student-fee-assignments/create', [FeeController::class, 'StudentFeeAssignmentCreate'])->name('student-fee-assignments.create');

        // // Store a newly created student fee assignment in storage
        // Route::post('/student-fee-assignments', [FeeController::class, 'StudentFeeAssignmentStore'])->name('student-fee-assignments.store');


        // // Show the form for editing the specified student fee assignment
        // Route::get('/student-fee-assignments/{studentFeeAssignment}/edit', [FeeController::class, 'StudentFeeAssignmentEdit'])->name('student-fee-assignments.edit');

        // // Update the specified student fee assignment in storage
        // // Standard RESTful practice for update is PUT or PATCH.
        // Route::put('/student-fee-assignments/{studentFeeAssignment}', [FeeController::class, 'StudentFeeAssignmentUpdate'])->name('student-fee-assignments.update');

        // // Remove the specified student fee assignment from storage
        // Route::delete('/student-fee-assignments/{studentFeeAssignment}', [FeeController::class, 'StudentFeeAssignmentDestroy'])->name('student-fee-assignments.destroy');


        // Route::get('/get-students-by-class', [FeeController::class, 'getStudentsByClass'])->name('get-students-by-class');
        // Route::post('/bulk-store-assignments', [FeeController::class, 'bulkStoreAssignments'])->name('bulk-store-assignments');


        //  Fee Type Controller

        // Route::get('/fee-types', [FeeController::class, 'FeeTypeIndex'])->name('fee-types.index');

        // // Show the form for creating a new fee type
        // Route::get('/fee-types/create', [FeeController::class, 'FeeTypeCreate'])->name('fee-types.create');

        // // Store a newly created fee type in storage
        // Route::post('/fee-types', [FeeController::class, 'FeeTypeStore'])->name('fee-types.store');


        // // Show the form for editing the specified fee type
        // Route::get('/fee-types/{feeType}/edit', [FeeController::class, 'FeeTypeEdit'])->name('fee-types.edit');

        // // Update the specified fee type in storage
        // // Note: Standard RESTful practice for update is PUT or PATCH.
        // Route::put('/fee-types/{feeType}', [FeeController::class, 'FeeTypeUpdate'])->name('fee-types.update');

        // // Remove the specified fee type from storage
        // Route::delete('/fee-types/{feeType}', [FeeController::class, 'FeeTypeDestroy'])->name('fee-types.destroy');


        // Route::get('/invoices', [FeeController::class, 'invoiceIndex'])->name('admin.invoices.index'); // NEW ROUTE
        // Route::get('/invoices/create', [FeeController::class, 'invoiceCreate'])->name('admin.invoices.create');
        // Route::post('/invoices', [FeeController::class, 'invoiceStore'])->name('admin.invoices.store');
        // Route::get('/invoices/{invoice}', [FeeController::class, 'invoiceShow'])->name('admin.invoices.show');

        Route::get('/admin/invoices/get-academic-data', [FeeController::class, 'getAcademicData'])
        ->name('admin.invoices.get-academic-data');


        // For Notification Purpose
         Route::get('/notifications/count', [FeeController::class, 'count'])->name('notifications.count');

        // Route to display all notifications
        Route::get('/notifications', [FeeController::class, 'index'])->name('notifications.index');

        // New route for notifications
        Route::post('/notifications/mark-all-as-read', [FeeController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');


        // Fee Types Routes
        Route::get('/admin/fee-types', [FeeController::class, 'FeeTypeIndex'])->name('fee-types.index');
        Route::get('/admin/fee-types/create', [FeeController::class, 'FeeTypeCreate'])->name('fee-types.create');
        Route::post('/admin/fee-types', [FeeController::class, 'FeeTypeStore'])->name('fee-types.store');
        Route::get('/admin/fee-types/{feeType}/edit', [FeeController::class, 'FeeTypeEdit'])->name('fee-types.edit');
        Route::post('/admin/fee-types/{feeType}', [FeeController::class, 'FeeTypeUpdate'])->name('fee-types.update');
        Route::delete('/admin/fee-types/{feeType}', [FeeController::class, 'FeeTypeDestroy'])->name('fee-types.destroy');

        // Class Fee Structure Routes
        Route::get('/admin/class-fee-structures', [FeeController::class, 'index'])->name('class-fee-structures.index');
        Route::get('/admin/class-fee-structures/create', [FeeController::class, 'create'])->name('class-fee-structures.create');
        Route::post('/admin/class-fee-structures', [FeeController::class, 'store'])->name('class-fee-structures.store');
        Route::get('/admin/class-fee-structures/{classFeeStructure}', [FeeController::class, 'show'])->name('class-fee-structures.show');
        Route::get('/admin/class-fee-structures/{classFeeStructure}/edit', [FeeController::class, 'edit'])->name('class-fee-structures.edit');
        Route::post('/admin/class-fee-structures/{classFeeStructure}', [FeeController::class, 'update'])->name('class-fee-structures.update');
        Route::delete('/admin/class-fee-structures/{classFeeStructure}', [FeeController::class, 'destroy'])->name('class-fee-structures.destroy');

        // Student Fee Assignment Routes
        Route::get('/admin/student-fee-assignments', [FeeController::class, 'StudentFeeAssignmentIndex'])->name('student-fee-assignments.index');
        Route::get('/admin/student-fee-assignments/create', [FeeController::class, 'StudentFeeAssignmentCreate'])->name('student-fee-assignments.create');
        Route::post('/admin/student-fee-assignments', [FeeController::class, 'StudentFeeAssignmentStore'])->name('student-fee-assignments.store');
        Route::get('/admin/student-fee-assignments/{studentFeeAssignment}/edit', [FeeController::class, 'StudentFeeAssignmentEdit'])->name('student-fee-assignments.edit');
        Route::post('/admin/student-fee-assignments/{studentFeeAssignment}', [FeeController::class, 'StudentFeeAssignmentUpdate'])->name('student-fee-assignments.update');
        Route::delete('/admin/student-fee-assignments/{studentFeeAssignment}', [FeeController::class, 'StudentFeeAssignmentDestroy'])->name('student-fee-assignments.destroy');
        Route::get('/admin/get-students-by-class', [FeeController::class, 'getStudentsByClass'])->name('get-students-by-class');
        Route::post('/admin/bulk-store-assignments', [FeeController::class, 'bulkStoreAssignments'])->name('bulk-store-assignments');

        Route::get('/admin/students/active', [FeeController::class, 'getActiveStudents'])
         ->name('admin.students.active');

        // Invoice Routes
        Route::get('/admin/invoices', [FeeController::class, 'invoiceIndex'])->name('admin.invoices.index');
        Route::get('/admin/invoices/create', [FeeController::class, 'invoiceCreate'])->name('admin.invoices.create');
        Route::post('/admin/invoices', [FeeController::class, 'invoiceStore'])->name('admin.invoices.store');


        Route::get('invoices/{invoice}', [FeeController::class, 'invoiceShow'])
        ->name('admin.invoices.show');


        // Route for showing the edit form
        Route::get('invoices/{invoice}/edit', [FeeController::class, 'invoiceEdit'])
            ->name('admin.invoices.edit');

        // Route for submitting the update form (MUST use PUT method)
        Route::put('invoices/{invoice}', [FeeController::class, 'invoiceUpdate'])
            ->name('admin.invoices.update');

        

        Route::get('/admin/students/{studentId}/id-card/download', [StudentController::class, 'downloadIdCard'])
        ->name('admin.students.download.idcard');

        Route::get('/students/next-roll', [StudentController::class, 'nextRoll'])->name('students.next-roll');


        Route::get('admin/students/print-class-idcards/{classId}', [StudentController::class, 'printClassIdCards'])->name('students.print.classidcards');


        //    
        Route::get('/admin/students/{student}/certificate', [StudentController::class, 'downloadTransferCertificate'])
        ->name('students.certificate.download');


        // Techer Single ID Card Download Route

        Route::get('/admin/teachers/{teacher}/id-card/download-pdf', [TeacherController::class, 'downloadPdfTeacher'])
        ->name('teachers.download_pdf');

        // All Teacher ID Card Printing Route

        Route::get('admin/teachers/print-all', [TeacherController::class, 'printAllIdCards'])
        ->name('teachers.print_all');

        // Serve Teacher Image
        Route::get('admin/teacher-image/{filename}', [TeacherController::class, 'serveImage'])->where('filename', '.*')->name('teachers.image.serve');

        // Serve Student Image
        Route::get('admin/student-image/{filename}', [StudentController::class, 'serveImageStudents'])->where('filename', '.*')->name('students.image.serve');
        
        // Route for serving the School Logo (using the exact same logic)
        Route::get('/settings/logo/{filename}', [App\Http\Controllers\SettingController::class, 'serveLogo'])
         ->name('settings.logo.serve');

        



        // 1. INDEX: Displays the list of all staff and their current salary status.
        // URL: /salaries
        Route::get('/salaries', [SalaryController::class, 'index'])
            ->name('salaries.index');

        // 2. CREATE: Shows the form for creating/assigning a new salary structure.
        // URL: /salaries/create
        Route::get('/salaries/create', [SalaryController::class, 'create'])
            ->name('salaries.create');

        // 3. STORE: Handles the form submission to save the new salary structure.
        // URL: /salaries
        Route::post('/salaries', [SalaryController::class, 'store'])
        ->name('salaries.store');


        Route::put('/salaries/{salaryStructure}', [SalaryController::class, 'update'])->name('salaries.update');

        // Salary Createb Route 
        Route::get('/payroll', [SalaryController::class, 'staffSalaryIndex'])->name('salaries.payroll.index');


        Route::post('/payroll/{staffId}/make', [SalaryController::class, 'makeSalary'])
        ->name('payroll.make');

        Route::post('/payroll/process', [SalaryController::class, 'processMonthlyPayroll'])->name('payroll.process.monthly');
            });

        Route::get('/payroll/records-for-print', [SalaryController::class, 'getPayrollRecordsForPrint'])->name('payroll.records-for-print');


        // TEACHER ONLY ROUTES
        Route::middleware('role:teacher')->group(function () {
            
        Route::get('/teacher/dashboard', [DashboardController::class, 'teacherIndex'])->name('teacher.dashboard');

        Route::get('teacher/marks', [MarkController::class, 'teacherMarksIndex'])->name('teachermarks.index');
        Route::post('teacher/marks', [MarkController::class, 'teacherMarksStore'])->name('teachermarks.store');

        Route::get('/teacher/my-notices', [NoticeController::class, 'myNotices'])->name('teacher.my-notices');

        // ✨ NEW Teacher Attendance Routes - Adjusted to fit here ✨
        Route::get('/teacher/attendance', [AttendanceController::class, 'teacherAttendanceIndex'])->name('teacherattendance.index');
        Route::post('/teacher/attendance', [AttendanceController::class, 'teacherAttendanceStore'])->name('teacherattendance.store');



        Route::get('/accounts/{student_id}/history', [StudentController::class, 'showStudentHistory'])->name('students.history');

        });


    
        // ACCOUNTS ONLY ROUTES (New dedicated route)
        Route::middleware('role:accounts')->group(function () {
        Route::get('/accounts/dashboard', [AccountsController::class, 'index'])->name('accounts.dashboard');

    



    Route::get('/accounts/invoices/{invoice}', [FeeController::class, 'invoiceShow'])->name('admin.invoices.show');
  



    // Payment Collection Routes
    Route::get('/accountant/payments/collect', [FeeController::class, 'collectPayment'])->name('accountant.payments.collect');

    // Existing route to handle a payment
    Route::post('/accountant/payments/store', [FeeController::class, 'storePayment'])->name('accountant.payments.store');


    Route::get('/accounts/invoices/{invoice}/download-pdf', [FeeController::class, 'downloadInvoicePdf'])->name('invoices.download.pdf');




    // Existing route for fetching a specific student's invoice
    Route::get('/get-student-invoice/{student}', [FeeController::class, 'getStudentInvoice']);








    // Student Routes
    // This route is for the initial page load to render the Vue component
    // Route::get('/accounts/students/history', [StudentController::class, 'AccountantStudentHistory'])->name('students.history');
    // // This route is for the AJAX request to fetch history data based on form submission
    // Route::get('/accounts/students/history/fetch', [StudentController::class, 'getStudentHistory'])->name('students.history.fetch');
    // Route::post('/accounts/academic-record', [StudentController::class, 'storeAcademicRecord'])->name('accounts.storeAcademicRecord');




    






    




    // Payment Routes
    // Route::get('/accounts/payments/pending', [FeeController::class, 'pendingPayments'])->name('admin.payments.pending');
    // Route::post('/accounts/payments/{paymentId}/approve', [FeeController::class, 'approvePayment'])->name('admin.payments.approve');
    // Route::post('/accounts/payments/{paymentId}/reject', [FeeController::class, 'rejectPayment'])->name('admin.payments.reject');

    // Report Routes
    Route::get('/income-statement', [AccountsController::class, 'incomeStatement'])->name('reports.income_statement');
    });





    // STUDENT ONLY ROUTES
    Route::middleware('role:student')->group(function () {
    Route::get('/student/dashboard', [DashboardController::class, 'studentIndex'])->name('student.dashboard');

    Route::get('/student/books', [BookController::class, 'studentIndex'])->name('student.books.index');
    Route::post('/student/books/borrow', [BookController::class, 'borrow'])->name('student.books.borrow');
    Route::get('/student/my-borrowed-books', [BookController::class, 'myBorrowedBooks'])->name('student.books.my-borrowed');
    Route::post('/student/books/return/{borrowBook}', [BookController::class, 'returnBook'])->name('student.books.return');
    Route::post('/student/books/request-return/{borrowBook}', [BookController::class, 'requestReturn'])->name('student.books.request-return');

    // Student Bus Schedule Route
    Route::get('/student/bus-schedule', [BusScheduleController::class, 'myBusSchedule'])->name('student.bus-schedule.index');

    Route::get('/student/my-notices', [NoticeController::class, 'myNotices'])->name('student.my-notices');

    Route::get('/student/my-exam-schedule', [ExamController::class, 'studentMyExamSchedule'])->name('student.my.exam.schedule');

    // Route to list all exam results for the student
    Route::get('/student/results', [ResultController::class, 'studentResultIndex'])->name('student.results.index');

    // Route to show detailed view of a specific exam result
    Route::get('/student/results/{examResult}', [ResultController::class, 'show'])->name('student.results.show');

    // Route to download the PDF for a specific exam result
    Route::get('/student/results/{examResult}/download-pdf', [ResultController::class, 'downloadPdf'])->name('student.results.download_pdf');

    Route::get('/student/fees', [FeeController::class, 'showStudentFees'])->name('student.fees');

    Route::get('/student/invoices', [FeeController::class, 'studentInvoices'])->name('student.invoices.index');
    Route::post('/student/invoices/process-payment', [FeeController::class, 'processPayment'])->name('student.invoices.process-payment');

    Route::get('/invoices/{invoice}/download-pdf', [FeeController::class, 'generatePdf'])->name('student.invoices.download-pdf');
    });


    // Front-Desk ONLY ROUTES
    Route::middleware('role:front-desk')->group(function () {


        Route::get('/front-desk/dashboard', [DashboardController::class, 'frontDeskIndex'])->name('front-desk.dashboard');
    
        
    
       // Student Management

        Route::get('/students', [StudentController::class, 'index'])->name('students.index');

        Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');

        Route::post('/students', [StudentController::class, 'store'])->name('students.store');
       
        Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
        
        Route::post('/students/{student}', [StudentController::class, 'update'])->name('students.update');

        Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

        // The generic show route must be at the bottom of the student routes.
        Route::get('/students/{id}', [StudentController::class, 'show'])->name('students.show');


        // TEACHER MANAGEMENT ROUTES (Individual Definitions)
        Route::get('/teachers', [TeacherController::class, 'index'])->name('teachers.index');
        Route::get('/teachers/create', [TeacherController::class, 'create'])->name('teachers.create');
        Route::post('/teachers', [TeacherController::class, 'store'])->name('teachers.store');
        Route::get('/teachers/{teacher}/edit', [TeacherController::class, 'edit'])->name('teachers.edit');
        Route::post('/teachers/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');


        // Notice Route
        Route::get('/notices', [NoticeController::class, 'index'])->name('notices.index');
        Route::get('/notices/create', [NoticeController::class, 'create'])->name('notices.create');
        Route::post('/notices', [NoticeController::class, 'store'])->name('notices.store');
        Route::get('/notices/edit/{notice}', [NoticeController::class, 'edit'])->name('notices.edit');
        // Using POST for update as per your input, consider PUT/PATCH for RESTful consistency
        Route::post('/notices/update/{notice}', [NoticeController::class, 'update'])->name('notices.update');
        Route::delete('/notices/{notice}', [NoticeController::class, 'destroy'])->name('notices.destroy');



        // TEACHER ATTENDANCE ROUTES
        // 1. Index (List all attendance, potentially for a date range/day)
        Route::get('/attendance/teachers', [AttendanceController::class, 'index'])->name('attendance.teachers.index');
        
        
        // 3. Store (Handle creation/daily marking) - This can be the most used POST
        Route::post('/attendance/teachers', [AttendanceController::class, 'store'])->name('attendance.teachers.store');
        
        

        // 5. Update (Handle update submission)
        Route::put('/attendance/teachers/{teacherAttendance}', [AttendanceController::class, 'update'])->name('attendance.teachers.update');

    });

});

require __DIR__.'/auth.php';
