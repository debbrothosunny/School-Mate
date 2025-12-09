<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\BorrowBook;
use App\Models\Student;
use App\Models\Invoice;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\LOG;
use Carbon\Carbon; // Import Carbon for date handling
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage; // For file storage operations

class BookController extends Controller
{
    /**
     * Display a listing of the books.
     */
    public function index(Request $request)
    {
        $query = Book::query();

        // Search filter (title, author, isbn)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%")
                  ->orWhere('isbn', 'like', "%{$search}%");
            });
        }

        // Status filter accepting '0' or '1' as string
        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === '0' || $status === '1') {
                $query->where('status', (int) $status);
            }
        }

        // Pagination with query string preserved
        $books = $query->paginate(10)->withQueryString();

        return inertia('Books/Index', [
            'books' => $books,
            'filters' => $request->only(['search', 'status']),
            'message' => session('message'),
            'type' => session('type'),
        ]);
    }  

    // Show create form
    public function create()
    {
        return inertia('Books/Create');
    }

    // Store new book
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'publication_date' => 'nullable|date',
            'isbn' => 'nullable|string|max:20|unique:books,isbn',
            'quantity' => 'required|integer|min:0',
            'genre' => 'nullable|string|max:100',
            'status' => 'required|integer|in:0,1',
        ]);

        

        $book = Book::create([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'publisher' => $validated['publisher'],
            'publication_date' => $validated['publication_date'],
            'isbn' => $validated['isbn'],
            'quantity' => $validated['quantity'],
            'available_quantity' => $validated['quantity'], // Initially all available
            'genre' => $validated['genre'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('books.index')->with([
            'message' => 'Book "' . $book->title . '" added successfully!',
            'type' => 'success',
        ]);
    }   

    // Show edit form
    public function edit(Book $book)
    {
        return inertia('Books/Edit', [
            'book' => $book,
        ]);
    }

    // Update existing book
    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'publisher' => 'nullable|string|max:255',
            'publication_date' => 'nullable|date',
            'isbn' => ['nullable', 'string', 'max:20', Rule::unique('books', 'isbn')->ignore($book->id)],
            'quantity' => 'required|integer|min:0',
            'genre' => 'nullable|string|max:100',
            'status' => 'required|integer|in:0,1',
        ]);

        // Adjust available_quantity when quantity changes
        $oldQuantity = $book->quantity;
        $newQuantity = $validated['quantity'];
        $quantityDifference = $newQuantity - $oldQuantity;

        $newAvailable = max(0, $book->available_quantity + $quantityDifference);

        

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'publisher' => $validated['publisher'],
            'publication_date' => $validated['publication_date'],
            'isbn' => $validated['isbn'],
            'quantity' => $newQuantity,
            'available_quantity' => $newAvailable,
            'genre' => $validated['genre'],

            'status' => $validated['status'],
        ]);

        return redirect()->route('books.index')->with([
            'message' => 'Book "' . $book->title . '" updated successfully!',
            'type' => 'success',
        ]);
    }

    /**
     * Remove the specified book from storage.
    */
    public function destroy(Book $book)
    {
        // Before deleting the book, ensure no active borrow records exist for it
        if ($book->borrowRecords()->whereIn('status', [0, 4])->exists()) { // Check for 'borrowed' or 'return requested'
            return redirect()->back()->with('flash', [
                'message' => 'Cannot delete book "' . $book->title . '" because it has active borrow records.',
                'type' => 'error'
            ]);
        }


        // Delete associated cover image if it exists
        if ($book->cover_image_path && Storage::disk('public')->exists($book->cover_image_path)) {
            Storage::disk('public')->delete($book->cover_image_path);
        }

        $book->delete();

        return redirect()->back()->with('flash', [
            'message' => 'Book "' . $book->title . '" deleted successfully!',
            'type' => 'success'
        ]);
    }


    // Return Book Functionality
    public function returnBook(Request $request, BorrowBook $borrow) 
    {
        // The status check should ideally be for status 0 (Borrowed)
        if ($borrow->status !== 0) {
            // More descriptive error based on current status
            return redirect()->back()->with('error', 'This book is not currently marked as borrowed (Status: ' . $borrow->status . ').');
        }

        try {
            DB::transaction(function () use ($borrow) {
                // 1. Update the borrow record
                $borrow->update([
                    // FIX 1: Removed unnecessary space in column name
                    'return_date' => now(), 
                    // FIX 2: Set the status to 1 (Returned)
                    'status'      => 1,        
                ]);

                // 2. Increase the book's available quantity
                $borrow->book->increment('available_quantity', $borrow->quantity);
            });
            
            return redirect()->back()->with('success', 'Book return successfully recorded and inventory updated.');

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Book return failed: ' . $e->getMessage()); 
            
            return redirect()->back()->with('error', 'Error processing return. Please try again.');
        }
    }


    public function adminBorrowRecordsIndex(Request $request)
    {
        // 1. Get filter for status (0: Active/Borrowed, 1: Returned, 2: All)
        $statusFilter = $request->get('status', 0);

        $borrows = BorrowBook::query()
            ->with(['book']) // Eager load the related book data
            ->when($statusFilter == 0, function ($query) {
                // Filter for Active (not returned)
                $query->whereNull('return_date');
            })
            ->when($statusFilter == 1, function ($query) {
                // Filter for Returned
                $query->whereNotNull('return_date');
            })
            // For status 2 (All), no additional status filter is applied
            ->orderByRaw('CASE WHEN return_date IS NULL THEN 0 ELSE 1 END') // Active first
            ->latest() // Then sort by latest issue date
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('BorrowRecords/Index', [
            'borrows' => $borrows,
            'filters' => [
                'status' => (int) $statusFilter,
            ],
            // Pass the current date to highlight overdue books in the front-end
            'currentDate' => now()->toDateString(), 
        ]);
    }


    // Student Borrow Book Functionality
    public function borrowBookStore(Request $request, Book $book)
    {
        // 1. INLINE VALIDATION
        $validated = $request->validate([
            'student_name' => ['required', 'string', 'max:255'],
            'admission_number' => ['required', 'string', 'max:50'],
            'class_name' => ['required', 'string', 'max:100'],
            'quantity' => [
                'required', 
                'integer', 
                'min:1',
                // Rule to ensure quantity is not greater than available stock
                'max:' . $book->available_quantity, 
            ],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
        ], [
            // Custom message for the quantity rule
            'quantity.max' => 'The requested quantity exceeds the available stock of ' . $book->available_quantity . '.',
        ]);

        // Fail early if the book is inactive or out of stock
        // **FIX APPLIED HERE: Using (int) for PHP type casting.**
        if ((int) $book->status !== 0 || $book->available_quantity < $validated['quantity']) {
            return redirect()->back()
                ->with('flash', [
                    'message' => 'Cannot issue book. It is either inactive or requested quantity exceeds available stock or quantity is too high.',
                    'status' => 'error',
                ]);
        }
        
        // 2. DATABASE TRANSACTION (Crucial for atomic operation)
        try {
            DB::transaction(function () use ($validated, $book) {
                
                // Create the Borrow record
                BorrowBook::create([
                    'book_id' => $book->id,
                    'student_name' => $validated['student_name'],
                    'admission_number' => $validated['admission_number'],
                    'class_name' => $validated['class_name'],
                    'quantity' => $validated['quantity'],
                    'borrow_date' => Carbon::today()->toDateString(), 
                    'due_date' => $validated['due_date'],
                    'return_date' => null, // Not returned yet
                    'status' => 0, // 0 = Borrowed (Active)
                ]);

                // Update the Book's available quantity
                $book->decrement('available_quantity', $validated['quantity']);
            });

            // 3. SUCCESS REDIRECT with flash message
            return redirect()->route('books.index')
                ->with('flash', [
                    'message' => 'Book "' . $book->title . '" successfully issued! 📚',
                    'status' => 'success',
                ]);

        } catch (\Exception $e) {
            // 4. ERROR HANDLING
            return redirect()->back()
                ->withInput()
                ->with('flash', [
                    'message' => 'A critical error occurred while issuing the book. Please try again.',
                    'status' => 'error',
                ]);
        }
    }
    



    /**
     * Display a listing of all borrow records for admin.
    */
    // public function adminBorrowRecordsIndex(Request $request)
    // {
    //     $query = BorrowBook::with(['book', 'student.user']); // Eager load book and student with user

    //     // Filtering
    //     if ($request->filled('search')) {
    //         $searchTerm = '%' . $request->search . '%';
    //         $query->where(function ($q) use ($searchTerm) {
    //             // Search by book title or student name
    //             $q->whereHas('book', function ($bookQuery) use ($searchTerm) {
    //                 $bookQuery->where('title', 'like', $searchTerm);
    //             })->orWhereHas('student.user', function ($userQuery) use ($searchTerm) {
    //                 $userQuery->where('name', 'like', $searchTerm);
    //             });
    //         });
    //     }

    //     if ($request->filled('status')) {
    //         $query->where('status', $request->status);
    //     }

    //     $borrowRecords = $query->orderBy('borrow_date', 'desc')->paginate(10);

    //     return Inertia::render('BorrowRecords/Index', [ // Adjusted path for admin view
    //         'borrowRecords' => $borrowRecords,
    //         'filters' => $request->only(['search', 'status']),
    //         'flash' => session('flash'),
    //     ]);
    // }

    /**
     * Admin action to approve a return request.
    */
    // public function approveReturn(BorrowBook $borrowBook) // Renamed from returnBook
    // {
    //     // Only allow approval if the status is 'Return Requested' (4) or 'Borrowed' (0) if admin can directly return
    //     if (!in_array($borrowBook->status, [0, 4])) {
    //         return redirect()->back()->with('flash', [
    //             'message' => 'This book is not in a state that can be approved for return.',
    //             'type' => 'error'
    //         ]);
    //     }

    //     DB::beginTransaction();
    //     try {
    //         $book = Book::lockForUpdate()->find($borrowBook->book_id);

    //         $borrowBook->update([
    //             'return_date' => Carbon::today(), // Set actual return date
    //             'status' => 1, // Change status to 'Returned'
    //         ]);

    //         // Increment available quantity of the book
    //         if ($book) {
    //             $book->increment('available_quantity');
    //         }

    //         DB::commit();
    //         return redirect()->back()->with('flash', [
    //             'message' => 'Book "' . $borrowBook->book->title . '" return approved successfully!',
    //             'type' => 'success'
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Admin approve return failed: ' . $e->getMessage());
    //         return redirect()->back()->with('flash', [
    //             'message' => 'An error occurred while approving the return. Please try again.',
    //             'type' => 'error'
    //         ]);
    //     }
    // }

    /**
     * Admin action to mark a borrowed book as lost.
    */
    public function markAsLost(BorrowBook $borrowBook)
    {
        // Only allow marking as lost if the status is 'Borrowed' (0) or 'Return Requested' (4)
        if (!in_array($borrowBook->status, [0, 4])) {
            return redirect()->back()->with('flash', [
                'message' => 'This book is not in a state to be marked as lost.',
                'type' => 'error'
            ]);
        }

        DB::beginTransaction();
        try {
            $book = Book::lockForUpdate()->find($borrowBook->book_id);

            $borrowBook->update([
                'status' => 3, // Change status to 'Lost'
                'return_date' => Carbon::today(), // Set return_date to today for lost books
            ]);

            // Decrement available quantity as the book is lost
            if ($book && $book->available_quantity > 0) {
                $book->decrement('available_quantity');
            }

            DB::commit();
            return redirect()->back()->with('flash', [
                'message' => 'Book "' . $borrowBook->book->title . '" marked as lost.',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin mark as lost failed: ' . $e->getMessage());
            return redirect()->back()->with('flash', [
                'message' => 'An error occurred while marking the book as lost.',
                'type' => 'error'
            ]);
        }
    }

    // Student Side Borrow Book

    /**
     * Display a listing of available books for students to borrow.
     */
    public function studentIndex()
    {
        // Fetch only active books with available quantity greater than 0
        $books = Book::where('status', 0) // 0 for active
            ->where('available_quantity', '>', 0)
            ->orderBy('title')
            ->paginate(10);

        return Inertia::render('StudentBooks/BookIndex', [
            'books' => $books,
            'flash' => session('flash'),
        ]);
    }


    /**
     * Store a newly created borrow record.
    */


    // public function borrow(Request $request)
    // {
    //     // Validate the incoming request data
    //     $validated = $request->validate([
    //         'book_id' => 'required|exists:books,id',
    //         'borrow_date' => 'required|date|before_or_equal:today',
    //         'return_date' => 'required|date|after_or_equal:borrow_date',
    //     ]);

    //     // Get the authenticated user's ID
    //     $userId = Auth::id();

    //     // Find the student record associated with the authenticated user
    //     $student = Student::where('user_id', $userId)->first();

    //     // If no student record is found for the authenticated user, prevent borrowing
    //     if (!$student) {
    //         return redirect()->back()->with('flash', [
    //             'message' => 'Your student profile could not be found. Please contact support.',
    //             'type' => 'error'
    //         ]);
    //     }

    //     // --- UPDATED LOGIC: Check for any outstanding library fee invoices ---
    //     // A student cannot borrow a book if they have an invoice that is pending, partially paid, pending_payment_approval, or overdue.
    //     $hasOutstandingFees = Invoice::where('student_id', $student->id)
    //                                  ->whereIn('status', ['pending_payment_approval', 'pending', 'partially_paid', 'overdue'])
    //                                  ->exists(); // `exists()` is more efficient than `first()`

    //     if ($hasOutstandingFees) {
    //         return redirect()->back()->with('flash', [
    //             'message' => 'Your library fees are pending. Please complete the payment so you can borrow this book.',
    //             'type'    => 'error',
    //         ]);
    //     }

    //     // --- END UPDATED LOGIC ---

    //     $studentId = $student->id; // Use the actual student ID

    //     DB::beginTransaction();

    //     try {
    //         $book = Book::lockForUpdate()->find($validated['book_id']); // Lock the book row for update

    //         // Check if the book is still available
    //         if (!$book || $book->available_quantity <= 0) {
    //             DB::rollBack();
    //             return redirect()->back()->with('flash', [
    //                 'message' => 'The selected book is no longer available for borrowing.',
    //                 'type' => 'error'
    //             ]);
    //         }

    //         // Check if the student has already borrowed this specific book and not yet returned it
    //         // Using integer status: 0 for 'borrowed', 4 for 'return requested'
    //         $existingBorrow = BorrowBook::where('book_id', $validated['book_id'])
    //             ->where('student_id', $studentId)
    //             ->whereIn('status', [0, 4]) // Check if status is 0 (borrowed) or 4 (return requested)
    //             ->first();

    //         if ($existingBorrow) {
    //             DB::rollBack();
    //             return redirect()->back()->with('flash', [
    //                 'message' => 'You have already borrowed this book or have a pending return request for it.',
    //                 'type' => 'error'
    //             ]);
    //         }

    //         // Create a new borrow record
    //         BorrowBook::create([
    //             'book_id' => $validated['book_id'],
    //             'student_id' => $studentId, // Use the correct student ID
    //             'borrow_date' => $validated['borrow_date'],
    //             'return_date' => $validated['return_date'], // Use the user-provided expected return date
    //             'status' => 0, // Set status to 0 for 'borrowed'
    //         ]);

    //         // Decrement the available quantity of the book
    //         $book->decrement('available_quantity');

    //         DB::commit();

    //         return redirect()->back()->with('flash', [
    //             'message' => 'Book "' . $book->title . '" borrowed successfully! Expected return by ' . Carbon::parse($validated['return_date'])->format('M d, Y') . '.',
    //             'type' => 'success'
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Book borrowing failed: ' . $e->getMessage()); // Log the error
    //         return redirect()->back()->with('flash', [
    //             'message' => 'An error occurred while trying to borrow the book. Please try again.',
    //             'type' => 'error'
    //         ]);
    //     }
    // }





    /**
     * Display a listing of books borrowed by the authenticated student.
     */
    // public function myBorrowedBooks()
    // {
    //     $userId = Auth::id();
    //     Log::info('myBorrowedBooks: Authenticated User ID: ' . $userId);

    //     $student = Student::where('user_id', $userId)->first();

    //     // Initialize borrowedBooks as an empty paginator-like object
    //     // This ensures the frontend always receives an object with 'data' and 'links' properties
    //     $borrowedBooks = (object)['data' => [], 'links' => (object)['first' => null, 'last' => null, 'prev' => null, 'next' => null]];


    //     if (!$student) {
    //         Log::warning('myBorrowedBooks: No student profile found for User ID: ' . $userId);
    //         return Inertia::render('StudentBooks/MyBorrowedBooks', [
    //             'borrowedBooks' => $borrowedBooks, // Pass the empty paginator-like object
    //             'flash' => ['message' => 'Your student profile could not be found.', 'type' => 'error'],
    //         ]);
    //     }

    //     $studentId = $student->id;
    //     Log::info('myBorrowedBooks: Found Student ID: ' . $studentId . ' for User ID: ' . $userId);


    //     // Fetch books borrowed by the current student that are still 'borrowed' or 'return requested'
    //     // Using integer status: 0 for 'borrowed', 4 for 'return requested'
    //     $borrowedBooks = BorrowBook::with('book') // Eager load the related book
    //         ->where('student_id', $studentId)
    //         ->whereIn('status', [0, 4]) // Filter by status 0 (borrowed) or 4 (return requested)
    //         ->orderBy('return_date') // Order by expected return date
    //         ->paginate(10);

    //     Log::info('myBorrowedBooks: Fetched ' . $borrowedBooks->count() . ' borrowed records for Student ID: ' . $studentId);

    //     return Inertia::render('StudentBooks/MyBorrowedBooks', [
    //         'borrowedBooks' => $borrowedBooks,
    //         'flash' => session('flash'),
    //     ]);
    // }

    /**
     * Handle a student's request to return a borrowed book.
    */
    // public function requestReturn(Request $request, BorrowBook $borrowBook)
    // {
    //     $userId = Auth::id();
    //     $student = Student::where('user_id', $userId)->first();

    //     if (!$student) {
    //         return redirect()->back()->with('flash', [
    //             'message' => 'Your student profile could not be found. Cannot process return request.',
    //             'type' => 'error'
    //         ]);
    //     }

    //     // Ensure the borrowed book belongs to the authenticated student
    //     if ($borrowBook->student_id !== $student->id) {
    //         return redirect()->back()->with('flash', [
    //             'message' => 'You are not authorized to request return for this book.',
    //             'type' => 'error'
    //         ]);
    //     }
 
    //     // Only allow requesting return if the book is currently borrowed (status 0)
    //     if ($borrowBook->status !== 0) {
    //         return redirect()->back()->with('flash', [
    //             'message' => 'This book is not currently in a state to be returned.',
    //             'type' => 'error'
    //         ]);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         // Update the borrow record status to 'Return Requested' (e.g., status 4)
    //         $borrowBook->update([
    //             'status' => 4, // Set status to 4 for 'Return Requested'
    //         ]);

    //         DB::commit();

    //         return redirect()->back()->with('flash', [
    //             'message' => 'Return request for "' . $borrowBook->book->title . '" submitted successfully! An admin will review it.',
    //             'type' => 'success'
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Return request failed: ' . $e->getMessage());
    //         return redirect()->back()->with('flash', [
    //             'message' => 'An error occurred while trying to submit the return request. Please try again.',
    //             'type' => 'error'
    //         ]);
    //     }
    // }
}
