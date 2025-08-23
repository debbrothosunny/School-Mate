<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StudentFeeAssignment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Session; // Assuming you have a Session model to determine academic year
use Carbon\Carbon;
use Illuminate\Support\Facades\DB; // For database transactions

class GenerateInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates monthly, biannual, and annual invoices for students based on assignments.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting invoice generation...');
        $today = Carbon::today();
        $currentMonthYear = $today->format('Y-m'); // e.g., '2025-07'
        
        // Determine current active academic session/year
        // Adjust this logic based on how your Session model identifies the 'current' session
        $currentSession = Session::where('status', 0) // Assuming 0 is 'active'
                                ->where('start_date', '<=', $today)
                                ->where('end_date', '>=', $today)
                                ->first();

        if (!$currentSession) {
            $this->error('No active session found. Cannot generate invoices.');
            return Command::FAILURE;
        }

        // Fetch active student fee assignments with their fee types
        $assignments = StudentFeeAssignment::with('feeType', 'student')
                                ->where('status', 0) // Only active assignments
                                ->where('applies_from', '<=', $today)
                                ->where(function ($query) use ($today) {
                                    $query->whereNull('applies_to')
                                          ->orWhere('applies_to', '>=', $today);
                                })
                                ->get();

        $generatedCount = 0;

        foreach ($assignments as $assignment) {
            $feeType = $assignment->feeType;
            $student = $assignment->student;
            $billingPeriod = null; // To store a string like '2025-07' or '2025-2026 Annual'
            $invoiceDescription = $feeType->name; // Base description

            // Determine if an invoice needs to be generated based on frequency
            $shouldGenerate = false;
            $invoiceDueDate = null;

            switch ($feeType->frequency->value) { // Using ->value if you used the Enum
                case 'monthly':
                    $billingPeriod = $currentMonthYear;
                    $invoiceDescription = $feeType->name . ' - ' . Carbon::parse($currentMonthYear)->format('F Y');
                    // Due date: 10th of the current month
                    $invoiceDueDate = Carbon::parse($currentMonthYear)->day(10); 
                    
                    // Prevent duplicate monthly invoices for the same student/month/fee type
                    if (!Invoice::where('student_id', $student->id)
                                ->where('billing_period', $billingPeriod)
                                ->whereHas('invoiceItems', function ($query) use ($feeType) {
                                    $query->where('fee_type_id', $feeType->id);
                                })
                                ->exists()) {
                        $shouldGenerate = true;
                    }
                    break;

                case 'biannual':
                    // Assuming 'Exam Fee (Midterm)' and 'Exam Fee (Final)' are distinct fee types
                    // And Midterm is due in November, Final in April for current academic year
                    if ($feeType->name == 'Exam Fee (Midterm)' && $today->month == 11) { // November
                        $billingPeriod = $currentSession->name . ' Midterm';
                        $invoiceDescription = $feeType->name . ' - ' . $currentSession->name;
                        $invoiceDueDate = Carbon::create($today->year, 11, 15); // Nov 15th
                        if (!Invoice::where('student_id', $student->id)
                                    ->where('billing_period', $billingPeriod)
                                    ->whereHas('invoiceItems', function ($query) use ($feeType) {
                                        $query->where('fee_type_id', $feeType->id);
                                    })
                                    ->exists()) {
                            $shouldGenerate = true;
                        }
                    } elseif ($feeType->name == 'Exam Fee (Final)' && $today->month == 4) { // April
                        $billingPeriod = $currentSession->name . ' Final';
                        $invoiceDescription = $feeType->name . ' - ' . $currentSession->name;
                        $invoiceDueDate = Carbon::create($today->year, 4, 15); // Apr 15th
                        if (!Invoice::where('student_id', $student->id)
                                    ->where('billing_period', $billingPeriod)
                                    ->whereHas('invoiceItems', function ($query) use ($feeType) {
                                        $query->where('fee_type_id', $feeType->id);
                                    })
                                    ->exists()) {
                            $shouldGenerate = true;
                        }
                    }
                    break;

                case 'annual':
                    // Assuming annual fees are due at the start of the academic year, e.g., in July
                    if ($today->month == 7) { // July
                        $billingPeriod = $currentSession->name . ' Annual';
                        $invoiceDescription = $feeType->name . ' - ' . $currentSession->name;
                        $invoiceDueDate = Carbon::create($today->year, 7, 31); // July 31st
                        if (!Invoice::where('student_id', $student->id)
                                    ->where('billing_period', $billingPeriod)
                                    ->whereHas('invoiceItems', function ($query) use ($feeType) {
                                        $query->where('fee_type_id', $feeType->id);
                                    })
                                    ->exists()) {
                            $shouldGenerate = true;
                        }
                    }
                    break;
                
                case 'one_time':
                    // One-time fees (like Admission Fee) are typically invoiced at the time of admission,
                    // not through this automated recurring job. So, we do nothing here.
                    break;
            }

            if ($shouldGenerate) {
                DB::beginTransaction();
                try {
                    $invoiceNumber = $this->generateUniqueInvoiceNumber(); // Custom function below
                    $assignedAmount = $assignment->effective_amount; // Use the accessor

                    $invoice = Invoice::create([
                        'student_id' => $student->id,
                        'invoice_number' => $invoiceNumber,
                        'billing_period' => $billingPeriod,
                        'due_date' => $invoiceDueDate,
                        'total_amount_due' => $assignedAmount,
                        'balance_due' => $assignedAmount,
                        'status' => 'pending',
                        'issued_at' => Carbon::now(),
                    ]);

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'fee_type_id' => $feeType->id,
                        'description' => $invoiceDescription,
                        'amount' => $assignedAmount,
                    ]);

                    DB::commit();
                    $generatedCount++;
                    $this->info("Generated invoice {$invoice->invoice_number} for {$student->name} ({$feeType->name}).");

                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("Failed to generate invoice for student {$student->id} ({$feeType->name}): " . $e->getMessage());
                }
            }
        }

        $this->info("Invoice generation completed. Total invoices generated: {$generatedCount}.");
        return Command::SUCCESS;
    }

    /**
     * Generates a unique invoice number.
     * Basic example: INV-YYYYMMDD-SEQUENCE. You might need a more robust system.
     */
    private function generateUniqueInvoiceNumber(): string
    {
        $prefix = 'INV-' . Carbon::now()->format('Ymd');
        $lastInvoice = Invoice::where('invoice_number', 'like', $prefix . '%')
                              ->orderBy('invoice_number', 'desc')
                              ->first();

        $sequence = 1;
        if ($lastInvoice) {
            $parts = explode('-', $lastInvoice->invoice_number);
            if (count($parts) > 2 && is_numeric(end($parts))) {
                 $sequence = (int)end($parts) + 1;
            }
        }

        return $prefix . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}