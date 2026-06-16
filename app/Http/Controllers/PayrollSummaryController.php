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
use Filament\Notifications\Notification;
use Illuminate\Http\Request;

class PayrollSummaryController extends Controller
{

    // public function showSheet(Request $request, String $periodcode)
    // {
    //     // Retrieve the array from the query string
    //     $employeeids = $request->query('employees', []);
    //     // Fetch Date Range Details
    //     $period = DatePeriod::where('code', $periodcode)->firstOrFail();
    //     $dateFrom = Carbon::parse($period->datefrom)->startOfDay();
    //     $dateTo = Carbon::parse($period->dateto)->endOfDay();
    //     // Load employees along with related structural values
    //     $employees = Employee::whereIn('employeeid', $employeeids)
    //         ->with(['project', 'empType', 'empStat', 'activeEarningsData' => function ($query) {
    //             $query->where('status', true); // or 1, depending on your database column type
    //         }])
    //         ->get();
    //     $categoryData = Category::where('status', true)
    //         ->where('cat', 'EARNINGS')->get();
    //     // 4. Fetch footer section items
    //     $adjustments = Adjustment::with('adjustmentName')
    //         ->whereIn('employee_id', $employeeids)
    //         ->where('date_period_id', $period)
    //         ->get();
    //     $govDeductions = GovDeductionLog::with('govDeduction')
    //         ->whereIn('employee_id', $employeeids)
    //         ->where('date_period_id', $period)
    //         ->get();
    //     // 1. Fetch raw logs as before
    //     $rawLogs = Atlog::whereIn('user_id', $employeeids)
    //         ->whereBetween('recorded_at', [$dateFrom, $dateTo])
    //         ->orderBy('recorded_at', 'asc')
    //         ->get();
    //     // 2. Group logs by calendar date string (e.g., "2026-06-11")
    //     $groupedLogs = $rawLogs->groupBy(function ($log) {
    //         return Carbon::parse($log->recorded_at)->toDateString();
    //     });
    //     $timesheets = [];
    //     // 3. Process each day's collection manually to apply your rules
    //     foreach ($groupedLogs as $date => $dayLogs) {
    //         $timeIn = null;
    //         $timeOut = null;
    //         // 1. Assign logs to correct slots based on your time windows
    //         foreach ($dayLogs as $log) {
    //             $time = Carbon::parse($log->recorded_at);
    //             $timeStr = $time->format('h:i A');
    //             // Time Out PM (After 1:00 PM) -> LAST entry wins
    //             if ($time->hour >= 13) {
    //                 $timeOut = $timeStr;
    //                 continue;
    //             }
    //             // Time In AM (Before 12:00 PM) -> FIRST entry wins
    //             if ($time->hour < 12) {
    //                 $timeIn = $timeIn ?? $timeStr;
    //             }
    //         }
    //         // 2. Dynamic Calculations Engine
    //         $totalHours = 'N/A';
    //         $overtime = '0.00';
    //         $lateUndertime = '0.00';
    //         // Rule: Only compute if BOTH Time In and Time Out exist. Otherwise, keep 'N/A'
    //         if ($timeIn && $timeOut) {
    //             $carbonIn = Carbon::parse("$date $timeIn");
    //             $carbonOut = Carbon::parse("$date $timeOut");
    //             // Calculate total gross hours present at work
    //             $grossHours = $carbonIn->diffInMinutes($carbonOut) / 60;
    //             // Deduct 1 hour automatically for standard lunch break if they worked through midday
    //             if ($grossHours > 5) {
    //                 $grossHours -= 1;
    //             }
    //             // Round up or down to 2 decimal points safely
    //             $computedHours = round($grossHours, 2);
    //             // Define standard shift target hours (e.g., 8 hours)
    //             $standardShiftHours = 8.00;
    //             if ($computedHours >= $standardShiftHours) {
    //                 $totalHours = number_format($standardShiftHours, 2);
    //                 $overtime = number_format($computedHours - $standardShiftHours, 2);
    //                 $lateUndertime = '0.00';
    //             } else {
    //                 $totalHours = number_format($computedHours, 2);
    //                 $overtime = '0.00';
    //                 $lateUndertime = number_format($standardShiftHours - $computedHours, 2);
    //             }
    //         }

    //         $timesheets[$date] = [
    //             'date'            => Carbon::parse($date)->format('M d, Y'),
    //             'total_hours'     => $totalHours, // Displays calculated value or 'N/A'
    //             'total_overtime'  => $totalHours === 'N/A' ? 'N/A' : $overtime,
    //             'late_undertime'  => $totalHours === 'N/A' ? 'N/A' : $lateUndertime,
    //         ];
    //     }
    //     return view('payroll.process_matrix', compact(
    //         'employees',
    //         'period',
    //         'govDeductions',
    //         'timesheets'
    //     ));
    // }
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
        // Assuming GovDeduction handles types like SSS, Philhealth, etc.
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
                // Pass $query as the only argument, and inherit $period using 'use'
                'payrollReportsData' => function ($query) use ($period) {
                    $query->where('dateperiod_id', $period->id);
                },
                // Pass $query as the only argument, and inherit $period using 'use'   otherdeductionData
                'adjustmentData' => function ($query) use ($period) {
                    $query->where('date_period_id', $period->code);
                },
                'otherdeductionData' => function ($query) use ($period) {
                    $query->where('date_period_id', $period->code);
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

                foreach ($dayLogs as $log) {
                    $time = Carbon::parse($log->recorded_at);
                    $timeStr = $time->format('h:i A');

                    if ($time->hour >= 13) {
                        $timeOut = $timeStr;
                    }
                    if ($time->hour < 12) {
                        $timeIn = $timeIn ?? $timeStr;
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
                    // Overtime remains 0.00
                } elseif (!$timeIn || !$timeOut) {
                    $display = 'N/A'; // Scenario B: Missed punch
                    $class = 'bg-red-100 font-bold text-red-600 text-center';
                    $payType = 'INCOMPLETE_LOGS';
                    // Overtime remains 0.00
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
                    'display' => $display,
                    'class'   => $class,
                    'hours'   => $acquiredHours
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
}
