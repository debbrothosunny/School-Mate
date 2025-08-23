<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\FeeType; // Import FeeType model
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ApplyLateFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:apply-late';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Applies daily late fees to overdue tuition invoices.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting late fee application...');
        $today = Carbon::today();

        // Get the FeeType for 'Late Fee'
        $lateFeeType = FeeType::where('name', 'Late Fee')->first();

        if (!$lateFeeType) {
            $this->error('Late Fee FeeType not found. Please create it first.');
            return Command::FAILURE;
        }

        // Get the FeeType for 'Tuition Fee' to identify relevant invoices
        $tuitionFeeType = FeeType::where('name', 'Tuition Fee')->first();

        if (!$tuitionFeeType) {
            $this->error('Tuition Fee FeeType not found. Cannot apply late fees.');
            return Command::FAILURE;
        }

        // Find overdue invoices that contain a Tuition Fee item and are not yet fully paid/cancelled
        $overdueInvoices = Invoice::whereIn('status', ['pending', 'partially_paid', 'overdue'])
            ->where('due_date', '<', $today) // Due date is in the past
            ->whereHas('invoiceItems', function ($query) use ($tuitionFeeType) {
                // Ensure the invoice contains a tuition fee item
                $query->where('fee_type_id', $tuitionFeeType->id);
            })
            ->with('invoiceItems') // Eager load invoice items to check for existing late fees
            ->get();

        $appliedCount = 0;
        $dailyLateFeeAmount = $lateFeeType->default_amount; // 1000 paisa (BDT 10)

        foreach ($overdueInvoices as $invoice) {
            DB::beginTransaction();
            try {
                // Determine the last day for which a late fee was applied to this invoice
                // Find the latest 'Late Fee' invoice item for this invoice
                $lastLateFeeItem = $invoice->invoiceItems()
                                            ->where('fee_type_id', $lateFeeType->id)
                                            ->orderBy('created_at', 'desc')
                                            ->first();

                $lastAppliedDate = $lastLateFeeItem ? Carbon::parse($lastLateFeeItem->created_at)->startOfDay() : $invoice->due_date->startOfDay();
                
                // Calculate days for which new late fees need to be applied
                // Start counting from the day *after* the last applied date/due date
                $startCountingFrom = $lastAppliedDate->addDay();
                $daysToApply = $startCountingFrom->diffInDaysFiltered(function (Carbon $date) use ($today) {
                    return $date->lessThanOrEqualTo($today); // Apply for each day from startCountingFrom up to today
                }, $today);


                if ($daysToApply > 0) {
                    for ($i = 0; $i < $daysToApply; $i++) {
                        $currentLateFeeDate = $startCountingFrom->copy()->addDays($i); // Date for which this specific daily late fee is applied

                        // Prevent duplicate late fees for the exact same day
                        $alreadyAppliedToday = $invoice->invoiceItems()
                            ->where('fee_type_id', $lateFeeType->id)
                            ->whereDate('created_at', $currentLateFeeDate) // Check if an item was created for this specific day
                            ->exists();

                        if (!$alreadyAppliedToday) {
                            InvoiceItem::create([
                                'invoice_id' => $invoice->id,
                                'fee_type_id' => $lateFeeType->id,
                                'description' => 'Late Fee for ' . $invoice->billing_period . ' (Day ' . ($i + 1) . ' overdue)',
                                'amount' => $dailyLateFeeAmount,
                            ]);
                            $invoice->total_amount_due += $dailyLateFeeAmount;
                            $invoice->balance_due += $dailyLateFeeAmount;
                            $appliedCount++;
                        }
                    }
                    $invoice->updateStatus(); // Update invoice status (e.g., to 'overdue') and save
                    $this->info("Applied late fees to invoice {$invoice->invoice_number} for {$invoice->student->name}.");
                }
                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Failed to apply late fees to invoice {$invoice->invoice_number}: " . $e->getMessage());
            }
        }

        $this->info("Late fee application completed. Total late fee items applied: {$appliedCount}.");
        return Command::SUCCESS;
    }
}