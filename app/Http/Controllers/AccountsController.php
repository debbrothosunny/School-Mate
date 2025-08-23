<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Student; // Assuming Student model is needed for accounts data
use App\Models\Invoice; // Assuming Invoice model exists
use Illuminate\Support\Facades\DB; // Needed for database queries
use Carbon\Carbon; // Needed for date and time manipulation

class AccountsController extends Controller
{
    
    public function index(Request $request)
    {
        $currentMonth = Carbon::now()->month;
        $currentYear  = Carbon::now()->year;

        // --- Fetch amounts for the current month from the invoices table ---
        $totalAmountPaidThisMonth = Invoice::whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->sum('amount_paid');
        $totalBalanceDueThisMonth = Invoice::whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->sum('balance_due');

        // --- Fetch amounts for the current year from the invoices table ---
        $totalAmountPaidThisYear = Invoice::whereYear('created_at', $currentYear)
            ->sum('amount_paid');
        $totalBalanceDueThisYear = Invoice::whereYear('created_at', $currentYear)
            ->sum('balance_due');

        // --- Fetch overall amounts from the invoices table ---
        $overallTotalAmountPaid = Invoice::sum('amount_paid');
        $overallTotalBalanceDue = Invoice::sum('balance_due');

        $accountsDashboardData = [
            'message' => 'Welcome, Accounts Staff! This is your financial overview dashboard.',
            'totalStudents' => Student::count(),
            
            'pendingFeesCount' => Invoice::where('status', '!=', 'paid')
                                         ->where('status', '!=', 'cancelled')
                                         ->where('balance_due', '>', 0)
                                         ->count(),

            // Monthly data
            'totalAmountPaidThisMonth' => $totalAmountPaidThisMonth,
            'totalBalanceDueThisMonth' => $totalBalanceDueThisMonth,

            // Yearly data
            'totalAmountPaidThisYear' => $totalAmountPaidThisYear,
            'totalBalanceDueThisYear' => $totalBalanceDueThisYear,

            // Overall data
            'overallTotalAmountPaid' => $overallTotalAmountPaid,
            'overallTotalBalanceDue' => $overallTotalBalanceDue,

            // Quick Links to Reports
            'reportLinks' => [
                'incomeStatement' => route('reports.income_statement'),
            ],
        ];

        return Inertia::render('AccountsDashboard', $accountsDashboardData);
    }


    public function incomeStatement(Request $request)
    {
        // Get the current date for monthly and yearly totals (for reference)
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Get start and end dates from the request, falling back to the current month if not provided
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());
        
        // Use Carbon to parse the received dates
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Generate a date range for chart labels
        $dates = $this->generateDateRange($start, $end);
        
        // --- Fetch total income for the current month and year (for the top cards) ---
        $totalAmountPaidThisMonth = Invoice::whereYear('created_at', $currentYear)
                                        ->whereMonth('created_at', $currentMonth)
                                        ->sum('amount_paid');
                                        
        $totalAmountPaidThisYear = Invoice::whereYear('created_at', $currentYear)
                                          ->sum('amount_paid');
        
        // --- Fetch total income for the selected date range ---
        $totalAmountPaidSelectedRange = Invoice::whereBetween('created_at', [$start, $end])
                                                ->sum('amount_paid');
                                        
        // --- Fetch all-time total income ---
        $totalAmountPaidAllTime = Invoice::sum('amount_paid');

        // --- Fetch real income data for the chart based on the selected range ---
        $incomeData = Invoice::select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(amount_paid) as total_paid'))
                            ->whereBetween('created_at', [$start, $end])
                            ->groupBy(DB::raw('DATE(created_at)'))
                            ->get()
                            ->pluck('total_paid', 'date')
                            ->toArray();
                            
        // Initialize an array to hold the chart data, filling in missing dates with 0
        $chartData = [];
        foreach ($dates as $date) {
            // FIX: Explicitly create a Carbon instance from the 'j/m' format
            $formattedDate = Carbon::createFromFormat('j/m', $date)->format('Y-m-d');
            $chartData[] = $incomeData[$formattedDate] ?? 0;
        }

        $datasets = [
            [
                'label' => 'Total Income',
                'data' => $chartData,
                'backgroundColor' => '#34D399', // Green for income
                'stack' => 'stack1',
            ],
        ];

        return Inertia::render('Accountant/Reports/IncomeStatement', [
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'labels' => $dates,
            'datasets' => $datasets,
            'totalAmountPaidThisMonth' => $totalAmountPaidThisMonth,
            'totalAmountPaidThisYear' => $totalAmountPaidThisYear,
            'totalAmountPaidAllTime' => $totalAmountPaidAllTime,
            'totalAmountPaidSelectedRange' => $totalAmountPaidSelectedRange,
        ]);
    }

    /**
     * Helper function to generate a date range for chart labels.
     */
    private function generateDateRange($startDate, $endDate)
    {
        $dates = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $dates[] = $currentDate->format('j/m');
            $currentDate->addDay();
        }
        return $dates;
    }

}
