<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use App\Models\PayrollRecord;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB; 
use Illuminate\Support\Facades\Log; 
use App\Models\SalaryStructure;
use Illuminate\Http\Request;

use Inertia\Inertia; // Assuming Inertia for frontend
use Illuminate\Validation\Rule;

class SalaryController extends Controller
{


    /**
     * Display a listing of the salary structures for all staff.
     */
    public function index()
    {
        // 1. Get all Teachers
        $teachers = Teacher::select([
            'teachers.id',
            'teachers.name',
            'teachers.designation as role',
            DB::raw("'" . addslashes(Teacher::class) . "' as salariable_type")
        ]);

        // 2. Get all Accountant/Front Desk/Admin Users
        $staff = User::query()
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('roles.name', ['accountant', 'front-desk', 'admin']) // Updated to include 'admin'
            ->select([
                'users.id',
                'users.name',
                'roles.name as role',
                DB::raw("'" . addslashes(User::class) . "' as salariable_type")
            ])
            ->union($teachers)
            ->orderBy('name')
            ->get();

        // Debug: Log staff list
        Log::info('Staff List:', $staff->toArray());

        // 3. Extract IDs for efficient query
        $salariableData = $staff->map(function ($item) {
            return [
                'id' => $item->id,
                'type' => $item->salariable_type,
            ];
        })->toArray();

        // 4. Find the latest salary structure for each staff member
        $currentDate = Carbon::now()->toDateString();
        $salariableKeys = array_map(function ($data) {
            return $data['id'] . '-' . $data['type'];
        }, $salariableData);

        // Fetch structures
        $currentSalaries = SalaryStructure::whereIn(DB::raw('CONCAT(salariable_id, "-", salariable_type)'), $salariableKeys)
            ->where('effective_date', '<=', $currentDate)
            ->orderBy('effective_date', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return $item->salariable_id . '-' . $item->salariable_type;
            })
            ->map(function ($groupedSalaries) {
                return $groupedSalaries->first();
            });

        // Debug: Log current salaries
        Log::info('Current Salaries:', $currentSalaries->toArray());

        // 5. Merge salary structures into staff list and perform calculations
        $staffWithSalaries = $staff->map(function ($person) use ($currentSalaries) {
            $key = $person->id . '-' . $person->salariable_type;
            $person->current_salary = $currentSalaries->get($key);
            $person->gross_pay = 0;
            $person->total_deduction = 0; // No deductions since deduction_percentage was removed
            $person->net_pay = 0; // Net pay equals gross pay

            if ($person->current_salary) {
                // Calculate Gross Pay (Total Earnings)
                $grossPay = $person->current_salary->basic_salary +
                            ($person->current_salary->house_rent_allowance ?? 0) +
                            ($person->current_salary->medical_allowance ?? 0) +
                            ($person->current_salary->academic_allowance ?? 0) +
                            ($person->current_salary->transport_allowance ?? 0);

                // No deduction percentage, so net pay equals gross pay
                $netPay = $grossPay;

                $person->gross_pay = $grossPay;
                $person->total_deduction = 0; // No deductions
                $person->net_pay = $netPay;
            }

            return $person;
        });

        return Inertia::render('Salary/Index', [
            'staffList' => $staffWithSalaries,
        ]);
    }

    /**
     * Show the form to create a new salary structure and list all staff.
    */
    public function create()
    {
        // 1. Get all Active Teachers
        $teachers = Teacher::select([
            'teachers.id',
            'teachers.name',
            'teachers.designation as role',
            DB::raw("'" . Teacher::class . "' as salariable_type")
        ])
        // Filter for active teachers where status = 0
        ->where('teachers.status', 0);

        // 2. Get all Active Accountant/Front Desk/Admin Users
        $adminStaff = User::query()
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('roles.name', ['accountant', 'front-desk', 'admin']) // Added 'admin'
            ->select([
                'users.id',
                'users.name',
                'roles.name as role',
                DB::raw("'" . User::class . "' as salariable_type")
            ])
            ->union($teachers)
            ->orderBy('name')
            ->get();

        return Inertia::render('Salary/Create', [
            'staffList' => $adminStaff,
        ]);
    }
    
    /**
     * Store a newly created salary structure in storage.
     */
    public function store(Request $request)
    {
        Log::info('Incoming salariable_type for check: ' . $request->salariable_type);
        Log::info('Expected FQCNs (via class constant): ' . Teacher::class . ' and ' . User::class);

        try {
            // 1. Validation
            $validated = $request->validate([
                'salariable_id' => 'required|integer',
                'salariable_type' => [
                    'required',
                    'string',
                    Rule::in([
                        Teacher::class, 
                        User::class,
                        str_replace('\\', '', Teacher::class), 
                        str_replace('\\', '', User::class), 
                    ]),
                ],
                'designation_name' => 'required|string|max:255',
                'effective_date' => 'required|date',
                'basic_salary' => 'required|integer|min:0',
                'house_rent_allowance' => 'nullable|integer|min:0',
                'medical_allowance' => 'nullable|integer|min:0',
                'academic_allowance' => 'nullable|integer|min:0',
                'transport_allowance' => 'nullable|integer|min:0',
                'festival_bonus' => 'nullable|integer|min:0',
            ]);

            // Ensure nullable fields are saved as zero
            $validated = array_merge($validated, [
                'house_rent_allowance' => $validated['house_rent_allowance'] ?? 0,
                'medical_allowance' => $validated['medical_allowance'] ?? 0,
                'academic_allowance' => $validated['academic_allowance'] ?? 0,
                'transport_allowance' => $validated['transport_allowance'] ?? 0,
                'festival_bonus' => $validated['festival_bonus'] ?? 0,
            ]);

            // 2. Create within transaction
            DB::transaction(function () use ($validated) {
                // CRITICAL: Remap the type to the correct FQCN string for the DB storage
                $salariableType = match ($validated['salariable_type']) {
                    str_replace('\\', '', Teacher::class) => Teacher::class,
                    str_replace('\\', '', User::class) => User::class,
                    default => $validated['salariable_type'],
                };
                
                // Override the validated type with the correct FQCN for DB storage
                $validated['salariable_type'] = $salariableType;

                // Remove future-dated salary structures for this staff member
                $deletedCount = SalaryStructure::where('salariable_id', $validated['salariable_id'])
                    ->where('salariable_type', $validated['salariable_type'])
                    ->where('effective_date', '>', $validated['effective_date'])
                    ->delete();

                Log::debug("Deleted $deletedCount future salary structures for {$validated['salariable_type']}:{$validated['salariable_id']}");

                // Create the new Salary Structure
                $salaryStructure = SalaryStructure::create($validated);
                
                if ($salaryStructure) {
                    Log::debug('Salary Structure created successfully with ID: ' . $salaryStructure->id);
                } else {
                    Log::error('SalaryStructure::create returned null/false.');
                }
            });

            Log::info('DB Transaction committed successfully. Redirecting.');

            // 3. Redirect with success
            return redirect()->route('salaries.create')->with('success', 'Salary structure saved successfully.');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation Failed.', $e->errors());
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to save salary structure: ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Failed to save salary structure. Please try again.');
        }
    }

    /**
     * Update an existing salary structure in storage.
    */
    public function update(Request $request, SalaryStructure $salaryStructure)
    {
        \Log::info('Updating Salary Structure ID: ' . $salaryStructure->id, $request->all());
        \Log::info('Incoming salariable_type for check: ' . $request->salariable_type);
        \Log::info('Expected FQCNs (via class constant): ' . Teacher::class . ' and ' . User::class);

        try {
            // 1. Validation
            $validated = $request->validate([
                'salariable_id' => 'required|integer',
                'salariable_type' => [
                    'required',
                    'string',
                    Rule::in([
                        Teacher::class,
                        User::class,
                        str_replace('\\', '', Teacher::class),
                        str_replace('\\', '', User::class),
                    ]),
                ],
                'designation_name' => 'required|string|max:255',
                'effective_date' => 'required|date',
                'basic_salary' => 'required|integer|min:0',
                'house_rent_allowance' => 'nullable|integer|min:0',
                'medical_allowance' => 'nullable|integer|min:0',
                'academic_allowance' => 'nullable|integer|min:0',
                'transport_allowance' => 'nullable|integer|min:0',
                'festival_bonus' => 'nullable|integer|min:0',
            ]);

            // Ensure nullable fields are saved as zero
            $validated = array_merge($validated, [
                'house_rent_allowance' => $validated['house_rent_allowance'] ?? 0,
                'medical_allowance' => $validated['medical_allowance'] ?? 0,
                'academic_allowance' => $validated['academic_allowance'] ?? 0,
                'transport_allowance' => $validated['transport_allowance'] ?? 0,
                'festival_bonus' => $validated['festival_bonus'] ?? 0,
            ]);

            // 2. Remap salariable_type to correct FQCN
            $salariableType = match ($validated['salariable_type']) {
                str_replace('\\', '', Teacher::class) => Teacher::class,
                str_replace('\\', '', User::class) => User::class,
                default => $validated['salariable_type'],
            };
            $validated['salariable_type'] = $salariableType;

            // 3. Update within transaction
            DB::transaction(function () use ($salaryStructure, $validated) {
                // Delete future-dated salary structures
                $deletedCount = SalaryStructure::where('salariable_id', $validated['salariable_id'])
                    ->where('salariable_type', $validated['salariable_type'])
                    ->where('effective_date', '>', $validated['effective_date'])
                    ->delete();

                \Log::debug("Deleted $deletedCount future salary structures for {$validated['salariable_type']}:{$validated['salariable_id']}");

                // Update the existing salary structure
                $updated = $salaryStructure->update($validated);

                if ($updated) {
                    \Log::debug('Salary Structure updated successfully with ID: ' . $salaryStructure->id);
                } else {
                    \Log::error('Failed to update SalaryStructure ID: ' . $salaryStructure->id);
                }
            });

            \Log::info('DB Transaction committed successfully for update.');

            // 4. Redirect with success
            return redirect()->route('salaries.index')->with('success', 'Salary structure updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('Validation Failed.', $e->errors());
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            \Log::error('Failed to update salary structure: ' . $e->getMessage(), ['exception' => $e]);
            return back()->with('error', 'Failed to update salary structure. Please try again.');
        }
    }


    /**
     * Displays the payroll records and the list of staff with their current structures.
    */
    public function staffSalaryIndex()
    {
        // --- 1. Payroll Records (paginated) ---
        $payrollRecords = PayrollRecord::with(['salaryStructure', 'salariable'])
            ->orderByDesc('pay_year')
            ->orderByDesc('pay_month')
            ->paginate(15);

        // --- 2. Current Staff + Salary Structure ---
        $teachers = Teacher::select([
            'teachers.id',
            'teachers.name',
            'teachers.designation as role',
            DB::raw("'" . addslashes(Teacher::class) . "' as salariable_type")
        ]);

        $admin = User::query()
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('roles.name', ['accountant', 'front-desk'])
            ->select([
                'users.id',
                'users.name',
                'roles.name as role',
                DB::raw("'" . addslashes(User::class) . "' as salariable_type")
            ]);

        $staff = $admin->union($teachers)->orderBy('name')->get();

        $currentDate = Carbon::now()->toDateString();
        $keys = $staff->map(fn($s) => $s->id . '-' . $s->salariable_type)->toArray();

        $currentSalaries = SalaryStructure::whereIn(DB::raw('CONCAT(salariable_id, "-", salariable_type)'), $keys)
            ->where('effective_date', '<=', $currentDate)
            ->orderByDesc('effective_date')
            ->get()
            ->groupBy(fn($item) => $item->salariable_id . '-' . $item->salariable_type)
            ->map(fn($group) => $group->first());

        $staffWithSalaries = $staff->map(function ($person) use ($currentSalaries) {
            $key = $person->id . '-' . $person->salariable_type;
            $salary = $currentSalaries->get($key);

            $person->current_salary = $salary;

            if ($salary) {
                $person->basic_salary          = $salary->basic_salary;
                $person->house_rent_allowance  = $salary->house_rent_allowance;
                $person->medical_allowance     = $salary->medical_allowance;
                $person->academic_allowance    = $salary->academic_allowance;
                $person->transport_allowance   = $salary->transport_allowance;
                $person->festival_bonus        = $salary->festival_bonus;

                $person->gross_pay_structure = $salary->basic_salary
                    + ($salary->house_rent_allowance ?? 0)
                    + ($salary->medical_allowance ?? 0)
                    + ($salary->academic_allowance ?? 0)
                    + ($salary->transport_allowance ?? 0);
            } else {
                $person->basic_salary = $person->house_rent_allowance = $person->medical_allowance =
                $person->academic_allowance = $person->transport_allowance = $person->festival_bonus =
                $person->gross_pay_structure = 0;
            }

            return $person;
        });

        return Inertia::render('Salary/SalaryIndex', [
            'payrollRecords'      => $payrollRecords,
            'staffWithStructures'=> $staffWithSalaries,
        ]);
    }




    /**
     * Generates a single payroll record for an individual staff member.
     */
    public function makeSalary(Request $request, $staffId)
    {
        $validated = $request->validate([
            'salariable_id' => 'required|integer',
            'salariable_type' => 'required|string',
            'pay_month' => 'required|integer|between:1,12',
            'pay_year' => 'required|integer',
            'deduction_percentage' => 'required|integer|in:0,10,20,25', // Added 0
            'absence_deduction_amount' => 'required|numeric|min:0',
            'absent_days' => 'nullable|integer|min:0|max:31',
        ]);

        $pay_month = $validated['pay_month'];
        $pay_year = $validated['pay_year'];
        $staffType = $validated['salariable_type'];
        $salariableId = $validated['salariable_id'];
        $deductionPercentage = $validated['deduction_percentage'];
        $absenceDeduction = $validated['absence_deduction_amount'];
        $absentDays = $validated['absent_days'] ?? 0;

        try {
            $staff = $staffType::findOrFail($salariableId);
        } catch (\Throwable $e) {
            return back()->withErrors(['staff' => 'Invalid staff.']);
        }

        $structure = $staff->currentSalaryStructure;
        if (!$structure) {
            return back()->withErrors(['structure' => 'No active salary structure.']);
        }

        $existingRecord = PayrollRecord::where('salariable_id', $salariableId)
            ->where('salariable_type', $staffType)
            ->where('pay_month', $pay_month)
            ->where('pay_year', $pay_year)
            ->exists();

        if ($existingRecord) {
            return back()->withErrors(['period' => 'Payroll already exists for this period.']);
        }

        // Gross Earning
        $grossEarning = $structure->basic_salary 
            + ($structure->house_rent_allowance ?? 0)
            + ($structure->medical_allowance ?? 0)
            + ($structure->academic_allowance ?? 0)
            + ($structure->transport_allowance ?? 0)
            + ($structure->festival_bonus ?? 0); // Include festival_bonus

        // General Deduction
        $deductionAmount = round(($grossEarning * $deductionPercentage) / 100);

        // Total Deductions
        $totalDeductions = $deductionAmount + $absenceDeduction;
        $netPayable = $grossEarning - $totalDeductions;

        PayrollRecord::create([
            'salariable_id' => $salariableId,
            'salariable_type' => $staffType,
            'salary_structure_id' => $structure->id,
            'pay_month' => $pay_month,
            'pay_year' => $pay_year,
            'gross_earning' => $grossEarning,
            'deduction_percentage_used' => $deductionPercentage,
            'deduction_amount' => $deductionAmount,
            'absent_days' => $absentDays,
            'absence_deduction_amount' => $absenceDeduction,
            'total_deductions' => $totalDeductions,
            'net_payable' => $netPayable,
        ]);

        return back()->with('success', "Payroll generated for {$staff->name}.");
    }

    /**
     * Generates payroll records for all staff.
    */
    public function processMonthlyPayroll(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer',
            'deduction_percentage' => 'required|integer|in:0,10,20,25', // Added 0
        ]);

        $pay_month = $validated['month'];
        $pay_year = $validated['year'];
        $deductionPercentage = $validated['deduction_percentage'];

        // Build staff list (teachers + admin)
        $teachersQuery = Teacher::select('id', DB::raw("'" . Teacher::class . "' as salariable_type"));

        $adminStaffQuery = User::query()
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->whereIn('roles.name', ['accountant', 'front-desk', 'admin']) // Added admin
            ->select('users.id', DB::raw("'" . User::class . "' as salariable_type"));

        $staffList = $adminStaffQuery->union($teachersQuery)->get();

        DB::beginTransaction();
        try {
            $processedCount = 0;

            foreach ($staffList as $info) {
                $salariableId = $info->id;
                $salariableType = $info->salariable_type;

                $staff = $salariableType::find($salariableId);
                if (!$staff) continue;

                // Skip if already processed
                $exists = PayrollRecord::where('salariable_id', $salariableId)
                    ->where('salariable_type', $salariableType)
                    ->where('pay_month', $pay_month)
                    ->where('pay_year', $pay_year)
                    ->exists();
                if ($exists) continue;

                $structure = $staff->currentSalaryStructure;
                if (!$structure) continue;

                // Gross Earning
                $grossEarning = $structure->basic_salary
                    + ($structure->house_rent_allowance ?? 0)
                    + ($structure->medical_allowance ?? 0)
                    + ($structure->academic_allowance ?? 0)
                    + ($structure->transport_allowance ?? 0)
                    + ($structure->festival_bonus ?? 0); // Include festival_bonus

                // General Deduction
                $deductionAmount = round(($grossEarning * $deductionPercentage) / 100);

                // Absence Deduction (manual, set to 0)
                $absenceDeduction = 0;
                $absentDays = 0;

                $totalDeductions = $deductionAmount + $absenceDeduction;
                $netPayable = $grossEarning - $totalDeductions;

                PayrollRecord::create([
                    'salariable_id' => $salariableId,
                    'salariable_type' => $salariableType,
                    'salary_structure_id' => $structure->id,
                    'pay_month' => $pay_month,
                    'pay_year' => $pay_year,
                    'gross_earning' => $grossEarning,
                    'deduction_percentage_used' => $deductionPercentage,
                    'deduction_amount' => $deductionAmount,
                    'absent_days' => $absentDays,
                    'absence_deduction_amount' => $absenceDeduction,
                    'total_deductions' => $totalDeductions,
                    'net_payable' => $netPayable,
                ]);

                $processedCount++;
            }

            DB::commit();

            return back()->with('success', "Payroll processed for {$processedCount} staff in {$pay_month}/{$pay_year}.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Payroll failed: ' . $e->getMessage());
        }
    }


    public function getPayrollRecordsForPrint(Request $request)
    {
        $validated = $request->validate([
            'month' => 'nullable|integer|between:1,12',
            'year' => 'nullable|integer',
        ]);

        // Fetch payroll records
        $query = PayrollRecord::with('salariable')
            ->select('id', 'salariable_id', 'salariable_type', 'gross_earning', 'deduction_percentage_used', 
                    'absence_deduction_amount', 'net_payable', 'pay_month', 'pay_year');

        if (isset($validated['month'])) {
            $query->where('pay_month', $validated['month']);
        }

        if (isset($validated['year'])) {
            $query->where('pay_year', $validated['year']);
        }

        $records = $query->get();

        // Fetch school settings (assuming a single settings record or taking the first one)
        $settings = DB::table('settings')->select('school_logo', 'school_name', 'address')->first();

        return response()->json([
            'records' => $records->map(function ($record) {
                return [
                    'id' => $record->id,
                    'salariable' => ['name' => $record->salariable?->name],
                    'gross_earning' => $record->gross_earning,
                    'deduction_percentage_used' => $record->deduction_percentage_used,
                    'absence_deduction_amount' => $record->absence_deduction_amount,
                    'net_payable' => $record->net_payable,
                    'pay_month' => $record->pay_month,
                    'pay_year' => $record->pay_year,
                ];
            }),
            'settings' => [
                'school_logo' => $settings->school_logo ? asset($settings->school_logo) : asset('assets/images/1762070391_logo_cMFQVFAUpo.jpg'),
                'school_name' => $settings->school_name ?? 'N/A',
                'address' => $settings->address ?? 'N/A',
            ],
        ]);
    }
}