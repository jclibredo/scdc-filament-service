<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\Atlog;
use App\Models\Category;
use App\Models\DatePeriod;
use App\Models\Earnings;
use App\Models\Employee;
use App\Models\GovDeduction;
use App\Models\GovDeductionLog;
use App\Models\Holiday;
use App\Models\OtherDeductionLog;
use App\Models\PayrollReport;
use Carbon\Carbon;
use Exception;
// use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollSummaryController extends Controller
{

    public function showSheet(Request $request, String $periodcode)
    {
        $employeeids = $request->query('employees', []);
        $period = DatePeriod::where('code', $periodcode)->firstOrFail();
        $dateFrom = Carbon::parse($period->datefrom)->startOfDay();
        $dateTo = Carbon::parse($period->dateto)->endOfDay();
        // 1. Generate explicit array of dates within this period
        $periodDates = [];
        $currentDate = $dateFrom->copy();
        while ($currentDate->lte($dateTo)) {
            $periodDates[] = $currentDate->toDateString(); // ['2026-06-08', '2026-06-09', ...]
            $currentDate->addDay();
        }
        // 2. Fetch active dynamic earnings headers
        $earningsCategories = Category::where('status', true)
            ->where('cat', 'EARNINGS')
            ->get();
        // 3. Fetch active deductions to use as headers
        $deductionHeaders = GovDeductionLog::with('govDeduction')
            ->whereIn('employee_id', $employeeids)
            ->where('date_period_id', $period->id) // Use ->id here safely
            ->get()
            ->pluck('govDeduction.name') // Or your identifier column
            ->unique();
        // Inside your controller, update this section:   payrollReportsData
        $employees = Employee::whereIn('employeeid', $employeeids)
            ->with([
                'project',
                'empType',
                'empStat',
                'earningsData' => function ($query) {
                    $query->where('status', true);
                },
                'payrollReportsData' => function ($query) use ($period) {
                    $query->where('dateperiod_id', $period->id);
                },
                // Eager-load log records AND their parent titles/names contextually
                'adjustmentData' => function ($query) use ($period) {
                    $query->where('date_period_id', $period->code)->with('adjustmentName');
                },
                'otherdeductionData' => function ($query) use ($period) {
                    $query->where('date_period_id', $period->code)->with('otherDeduction');
                },
                'govdeductionData' => function ($query) use ($period) {
                    $query->where('date_period_id', $period->code)->with('govDeduction');
                }
            ])
            ->get();
        // 5. Fetch raw attendance logs for mapping
        $rawLogs = Atlog::whereIn('user_id', $employeeids)
            ->whereBetween('recorded_at', [$dateFrom, $dateTo])
            ->orderBy('recorded_at', 'asc')
            ->get()
            ->groupBy('user_id'); // Grouping by employee is CRITICAL
        $employeeTimesheets = [];
        // 6. Compute attendance per employee, per day
        foreach ($employees as $employee) {
            $empLogs = $rawLogs->get($employee->employeeid, collect())->groupBy(function ($log) {
                return Carbon::parse($log->recorded_at)->toDateString();
            });
            // Initialize total overtime for the current employee
            $totalPeriodOvertime = 0.00;
            foreach ($periodDates as $date) {
                $dayLogs = $empLogs->get($date, collect());
                $timeIn = null;
                $timeOut = null;
                if ($dayLogs->isNotEmpty()) {
                    // 1. Sort logs chronologically to accurately target the outer boundaries
                    $sortedLogs = $dayLogs->sortBy('recorded_at');

                    $firstLog = $sortedLogs->first();
                    $lastLog = $sortedLogs->last();

                    $firstTime = Carbon::parse($firstLog->recorded_at);
                    $lastTime = Carbon::parse($lastLog->recorded_at);

                    // 2. If they have only 1 punch, or first/last are the exact same database entry, skip timeout
                    if ($sortedLogs->count() === 1 || $firstLog->id === $lastLog->id) {
                        $timeIn = $firstTime->format('h:i A');
                        $timeOut = null;
                    } else {
                        // Otherwise, reliably extract the actual first and last logs
                        $timeIn = $firstTime->format('h:i A');
                        $timeOut = $lastTime->format('h:i A');
                    }
                }

                // Variable initialization for database recording
                $overtime = 0.00;
                $acquiredHours = 0.00;
                $lateUndertime = 0.00;
                $payType = 'REGULAR';

                // Scenario Evaluation
                if ($dayLogs->isEmpty()) {
                    $display = 'A'; // Scenario A: No logs at all
                    $class = 'bg-yellow-50 font-bold text-amber-700 text-center';
                    $payType = 'ABSENT';
                } elseif (!$timeIn || !$timeOut) {
                    $display = 'N/A'; // Scenario B: Missed punch / Single punch entry
                    $class = 'bg-red-100 font-bold text-red-600 text-center';
                    $payType = 'INCOMPLETE_LOGS';
                } else {
                    // Scenario C: Completed punches
                    $carbonIn = Carbon::parse("$date $timeIn");
                    $carbonOut = Carbon::parse("$date $timeOut");

                    $grossHours = $carbonIn->diffInMinutes($carbonOut) / 60;
                    if ($grossHours > 5) $grossHours -= 1; // standard lunch break deduction

                    $totalComputedHours = round($grossHours, 2);
                    $standardShiftHours = 8.00;

                    if ($totalComputedHours >= $standardShiftHours) {
                        $acquiredHours = $standardShiftHours;
                        $overtime = $totalComputedHours - $standardShiftHours;
                        $lateUndertime = 0.00;
                    } else {
                        $acquiredHours = $totalComputedHours;
                        $overtime = 0.00;
                        $lateUndertime = $standardShiftHours - $totalComputedHours;
                    }

                    $display = number_format(min($acquiredHours, 8), 1); // Cap regular hours display at 8
                    $class = 'text-center';

                    // Accumulate overtime only on valid, completed punch days
                    $totalPeriodOvertime += $overtime;
                }

                $employeeTimesheets[$employee->employeeid][$date] = [
                    'display'   => $display,
                    'class'     => $class,
                    'hours'     => $acquiredHours,
                    'time_in'   => $timeIn,
                    'time_out'  => $timeOut,
                    'break_out' => null,
                    'break_in'  => null,
                ];

                PayrollReport::updateOrCreate(
                    [
                        'dateperiod_id' => $period->id,
                        'employee_id'   => $employee->employeeid,
                        'date_entry'    => $date,
                    ],
                    [
                        'paytype'        => $payType,
                        'acquired_hours' => $acquiredHours,
                        'overtime'       => $overtime,
                        'late_undertime' => $lateUndertime,
                    ]
                );
            }
        }
        // Fetch flat modifications mapping
        $adjustments = Adjustment::whereIn('employee_id', $employeeids)
            ->where('date_period_id', $period->id)->get()->groupBy('employee_id');
        $deductions = GovDeduction::where('status', true)->get();
        $govDeductions = GovDeductionLog::whereIn('employee_id', $employeeids)
            ->where('date_period_id', $period->id)->get()->groupBy('employee_id');
        // 3. FIXED: Fetch middle section (Timesheet / Atlogs) using the date range
        $holidays = Holiday::orderBy('percentage', 'asc')->get();
        return view('payroll.process_matrix', compact(
            'employees',
            'period',
            'periodDates',
            'earningsCategories',
            'deductionHeaders',
            'employeeTimesheets',
            'adjustments',
            'govDeductions',
            'deductions',
            'holidays',
        ));
    }
    public function show(String $employee_id, String $period_code)
    {
        // 1. Fetch employee details
        $employee = Employee::where('employeeid', $employee_id)->firstOrFail();

        // 2. Fetch the DatePeriod to get the start and end cutoff dates
        $datePeriod = DatePeriod::where('code', $period_code)->firstOrFail();
        // Setup start and end limits covering the full days
        $dateFrom = Carbon::parse($datePeriod->datefrom)->startOfDay();
        $dateTo = Carbon::parse($datePeriod->dateto)->endOfDay();

        // 3. FIXED: Fetch middle section (Timesheet / Atlogs) using the date range
        $holidays = Holiday::orderBy('percentage', 'asc')->get();

        // 4. Fetch footer section items
        $adjustments = Adjustment::with('adjustmentName')
            ->where('employee_id', $employee_id)
            ->where('date_period_id', $period_code)
            ->get();

        $govDeductions = GovDeductionLog::with('govDeduction')
            ->where('employee_id', $employee_id)
            ->where('date_period_id', $period_code)
            ->get();

        $otherDeductions = OtherDeductionLog::with('otherDeduction') // 💡 Eager load relationship here
            ->where('employee_id', $employee_id)
            ->where('date_period_id', $period_code)
            ->get();

        $earnings = Earnings::where('employee_id', $employee_id)
            ->where('status', true)
            ->get();

        // Extract raw basic pay number (or fallback to 0 if not found)
        $basicPayAmount = $earnings->firstWhere('category.name', 'BASIC')?->amount
            ?? $earnings->firstWhere('category_id', 1)?->amount
            ?? 0.00;
        $companyName = "SCDC Construction & Development Corp.";

        // 1. Fetch raw logs as before
        $rawLogs = Atlog::where('user_id', $employee_id)
            ->whereBetween('recorded_at', [$dateFrom, $dateTo])
            ->orderBy('recorded_at', 'asc')
            ->get();

        // 2. Group logs by calendar date string (e.g., "2026-06-11")
        $groupedLogs = $rawLogs->groupBy(function ($log) {
            return Carbon::parse($log->recorded_at)->toDateString();
        });

        $timesheets = [];
        // 3. Process each day's collection manually to apply your rules
        foreach ($groupedLogs as $date => $dayLogs) {
            $timeIn = null;
            $breakOut = null;
            $breakIn = null;
            $timeOut = null;
            // 1. Assign logs to correct slots based on your time windows
            foreach ($dayLogs as $log) {
                $time = Carbon::parse($log->recorded_at);
                $timeStr = $time->format('h:i A');
                // Break Out / Break In (12:01 PM - 12:59 PM)
                if ($time->hour === 12 && $time->minute > 0) {
                    $breakOut = $breakOut ?? $timeStr;
                    $breakIn = $timeStr;
                    continue;
                }
                // Time Out PM (After 1:00 PM) -> LAST entry wins
                if ($time->hour >= 13) {
                    $timeOut = $timeStr;
                    continue;
                }
                // Time In AM (Before 12:00 PM) -> FIRST entry wins
                if ($time->hour < 12) {
                    $timeIn = $timeIn ?? $timeStr;
                }
            }
            // 2. Dynamic Calculations Engine
            $totalHours = 'N/A';
            $overtime = '0.00';
            $lateUndertime = '0.00';
            // Rule: Only compute if BOTH Time In and Time Out exist. Otherwise, keep 'N/A'
            if ($timeIn && $timeOut) {
                $carbonIn = Carbon::parse("$date $timeIn");
                $carbonOut = Carbon::parse("$date $timeOut");
                // Calculate total gross hours present at work
                $grossHours = $carbonIn->diffInMinutes($carbonOut) / 60;
                // Deduct 1 hour automatically for standard lunch break if they worked through midday
                if ($grossHours > 5) {
                    $grossHours -= 1;
                }
                // Round up or down to 2 decimal points safely
                $computedHours = round($grossHours, 2);
                // Define standard shift target hours (e.g., 8 hours)
                $standardShiftHours = 8.00;
                if ($computedHours >= $standardShiftHours) {
                    $totalHours = number_format($standardShiftHours, 2);
                    $overtime = number_format($computedHours - $standardShiftHours, 2);
                    $lateUndertime = '0.00';
                } else {
                    $totalHours = number_format($computedHours, 2);
                    $overtime = '0.00';
                    $lateUndertime = number_format($standardShiftHours - $computedHours, 2);
                }
            }
            $timesheets[$date] = [
                'date'            => Carbon::parse($date)->format('M d, Y'),
                'time_in'         => $timeIn ?? '---',
                'break_out'       => $breakOut ?? '---',
                'break_in'        => $breakIn ?? '---',
                'time_out'        => $timeOut ?? '---',
                'total_hours'     => $totalHours, // Displays calculated value or 'N/A'
                'total_overtime'  => $totalHours === 'N/A' ? 'N/A' : $overtime,
                'late_undertime'  => $totalHours === 'N/A' ? 'N/A' : $lateUndertime,
            ];
        }
        return view('payroll.summary', compact(
            'companyName',
            'employee',
            'period_code',
            'timesheets',
            'adjustments',
            'govDeductions',
            'otherDeductions',
            'earnings',
            'holidays',
            'basicPayAmount',
            'datePeriod',
        ));
    }

    public function updateBatch(Request $request)
    {
        // 1. Validate incoming data framework context requirements
        $request->validate([
            'employee_id' => 'required',
            'period_code' => 'required|string',
        ]);
        $employeeId = $request->input('employee_id');
        $periodCode = $request->input('period_code');
        // 2. Wrap operations inside a single transaction isolation database block
        DB::beginTransaction();
        try {
            // ==========================================
            // SUB-SECTION 1: SYNCHRONIZE ADJUSTMENTS
            // ==========================================
            DB::table('adjustments') // Double check if your table name is 'adjustment_logs' or 'employee_adjustments'
                ->where('employee_id', $employeeId)
                ->where('date_period_id', $periodCode)
                ->delete();
            // 2. Loop through and insert only the non-zero rows from the frontend dataset
            if ($request->has('adjustments')) {
                foreach ($request->input('adjustments') as $idKey => $data) {
                    $amount = (float)($data['amount'] ?? 0.0);
                    $adjustmentId = $data['adjustment_id'] ?? null;
                    // Skip rows that have a zero amount or rows where no adjustment type was selected
                    if ($amount === 0.0 || empty($adjustmentId)) {
                        continue;
                    }
                    // ACTION: Insert fresh records completely clean without handling update conditions
                    DB::table('adjustments')->insert([
                        'employee_id'     => $employeeId,
                        'date_period_id'  => $periodCode,
                        'adjustment_id'   => $adjustmentId, // Mapped correctly by the JavaScript update
                        'amount'          => $amount,
                        'created_at'      => now(),
                        'updated_at'      => now()
                    ]);
                }
            }
            // ==========================================
            // SUB-SECTION 2: SYNCHRONIZE MANDATORY DECS
            // ==========================================
            // 1. Wipe out all existing logs for this specific employee and payroll period first
            DB::table('gov_deduction_logs')
                ->where('employee_id', $employeeId)
                ->where('date_period_id', $periodCode)
                ->delete();
            // 2. Loop through and insert only the non-zero rows from the frontend layout matrix
            if ($request->has('gov_deductions')) {
                foreach ($request->input('gov_deductions') as $idKey => $data) {
                    $amount = (float)($data['amount'] ?? 0.0);
                    $govDeductionId = $data['gov_deduction_id'] ?? null;
                    // Skip rows that have a zero amount or rows where no deduction type was selected
                    if ($amount === 0.0 || empty($govDeductionId)) {
                        continue;
                    }
                    // ACTION: Insert fresh records completely clean without handling update loops
                    DB::table('gov_deduction_logs')->insert([
                        'employee_id'        => $employeeId,
                        'date_period_id'     => $periodCode,
                        'gov_deduction_id'   => $govDeductionId,
                        'amount'             => $amount,
                        'created_at'         => now(),
                        'updated_at'         => now()
                    ]);
                }
            }
            // ==========================================
            // SUB-SECTION 3: SYNCHRONIZE OTHER DECS
            // ==========================================
            // 1. Wipe out all existing other deductions for this specific employee and payroll period first
            DB::table('other_deduction_logs') // Double check if your table name is 'other_deduction_logs'
                ->where('employee_id', $employeeId)
                ->where('date_period_id', $periodCode)
                ->delete();
            // 2. Loop through and insert only the non-zero rows from the frontend dataset
            if ($request->has('other_deductions')) {
                foreach ($request->input('other_deductions') as $idKey => $data) {
                    $amount = (float)($data['amount'] ?? 0.0);
                    $otherDeductionId = $data['other_deduction_id'] ?? null;
                    // Skip rows that have a zero amount or rows where no deduction type was selected
                    if ($amount === 0.0 || empty($otherDeductionId)) {
                        continue;
                    }
                    // ACTION: Insert fresh records completely clean without handling update conditions
                    DB::table('other_deduction_logs')->insert([
                        'employee_id'        => $employeeId,
                        'date_period_id'     => $periodCode,
                        'other_deduction_id' => $otherDeductionId, // Mapped correctly by the JavaScript update
                        'amount'             => $amount,
                        'created_at'         => now(),
                        'updated_at'         => now()
                    ]);
                }
            }

            // ==========================================
            // SUB-SECTION 4: PROCESS DAILY TIMESHEETS
            // ==========================================
            // if ($request->has('timesheet')) {
            //     foreach ($request->input('timesheet') as $dateKey => $hoursData) {
            //         // Normalize standard nullable baseline structures or treat as zero string floats
            //         $regHours = !empty($hoursData['regular_hours']) ? parseFloat($hoursData['regular_hours']) : 0.00;
            //         $otHours  = !empty($hoursData['overtime_hours']) ? parseFloat($hoursData['overtime_hours']) : 0.00;
            //         $lateUt   = !empty($hoursData['late_undertime_hours']) ? parseFloat($hoursData['late_undertime_hours']) : 0.00;
            //         // Depending on how your timesheets/reports architecture is set up, update here:
            //         // Example mapping to standard daily tracking architecture components:
            //         DB::table('payroll_reports_data') // Or change to your production timesheet matrix key table name
            //             ->where('employee_id', $employeeId)
            //             ->where('date_entry', $dateKey)
            //             ->update([
            //                 'acquired_hours' => $regHours,
            //                 'overtime'       => $otHours,
            //                 'late_undertime' => $lateUt,
            //                 'updated_at'     => now()
            //             ]);
            //     }
            // }

            // Everything executed perfectly, commit work safely
            DB::commit();
            return redirect()->back()->with('success', 'Payroll ledger data matrix successfully synchronized.');
        } catch (Exception $e) {
            // Something broke down under load, discard changes instantly
            DB::rollBack();
            Log::error('Payroll Summary Synchronization Failure: ' . $e->getMessage(), [
                'employee_id' => $employeeId,
                'period_code' => $periodCode
            ]);

            return redirect()->back()->withInput()->with('error', 'Critical accounting processing mismatch error: ' . $e->getMessage());
        }
    }
}
