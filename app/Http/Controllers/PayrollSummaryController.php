<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\Atlog;
use App\Models\Category;
use App\Models\DatePeriod;
use App\Models\Earnings;
use App\Models\Employee;
use App\Models\EmpSchedule;
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

    public function showSheet(Request $request, String $periodcode, String $expartners)
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
        $earningsCategories = Category::where('status', true)
            ->orderBy('name', 'asc')
            ->where('cat', 'EARNINGS')
            ->get();
        $deductionHeaders = GovDeductionLog::with('govDeduction')
            ->whereIn('employee_id', $employeeids)
            ->where('date_period_id', $period->id) // Use ->id here safely
            ->get()
            ->pluck('govDeduction.name') // Or your identifier column
            ->unique();
        $employeeRelations = [
            'project',
            'empType',
            'empStat',
            'emplScheduleData' => fn($q) => $q->where('status', true),
            'earningsData' => fn($q) => $q->where('status', true),
            'payrollReportsData' => fn($q) => $q->where('dateperiod_id', $period->id),
            'payrollSummaryData' => fn($q) => $q->where('dateperiod_id', $period->id),
            'adjustmentData' => fn($q) => $q->where('date_period_id', $period->code),
            'adjustmentData.adjustmentName',
            'otherdeductionData' => fn($q) => $q->where('date_period_id', $period->code),
            'otherdeductionData.otherDeduction',
            'govdeductionData' => fn($q) => $q->where('date_period_id', $period->code),
            'govdeductionData.govDeduction'
        ];
        // 2. Fetch primary employees, ordered by project name, then by employee lastname
        $employees = Employee::whereIn('employees.employeeid', $employeeids)
            ->leftJoin('projects', 'employees.project_id', '=', 'projects.project_code')
            ->select('employees.*')
            ->orderBy('projects.name', 'asc')
            ->orderBy('employees.lastname', 'asc')
            ->with($employeeRelations)
            ->get();
        $subcon_query = collect();
        if (!empty($expartners) && $expartners !== '0') {
            // Re-map relation keys using dot notation for eager loading nested models cleanly
            $subconRelations = [];
            foreach ($employeeRelations as $key => $value) {
                if (is_callable($value)) {
                    $subconRelations["SubConEmployee." . $key] = $value;
                } else {
                    $subconRelations["SubConEmployee." . $value] = fn($q) => $q;
                }
            }
            // $subconRelations['SubConEmployee'] = fn($q) => $q->where('status', true);
            $subconRelations['SubConEmployee'] = fn($q) => $q->where('status', true)
                ->whereIn('employeeid', $employeeids);
            $subcon_query = Category::where('status', true)
                ->where('cat', 'SUBCON')
                ->when($expartners === 'ALL', fn($q) => $q->orderBy('name', 'asc'))
                ->when($expartners !== 'ALL', fn($q) => $q->where('id', $expartners))
                ->with($subconRelations)
                ->get();
            foreach ($subcon_query as $category) {
                if ($category->SubConEmployee) {
                    $category->SubConEmployee = $category->SubConEmployee->sortBy([
                        fn($a, $b) => ($a->project->name ?? '') <=> ($b->project->name ?? ''),
                        fn($a, $b) => ($a->lastname ?? '') <=> ($b->lastname ?? '')
                    ])->values(); // Reset array indexes
                }
            }
        }
        $rawLogs = Atlog::whereIn('user_id', $employeeids)
            ->whereBetween('recorded_at', [$dateFrom, $dateTo])
            ->orderBy('recorded_at', 'asc')
            ->get()
            ->groupBy('user_id'); // Grouping by employee is CRITICAL
        $employeeTimesheets = [];
        // 6. Compute attendance per employee, per day
        foreach ($employees as $employee) {
            $empLogs = $rawLogs
                ->get($employee->employeeid, collect())
                ->groupBy(function ($log) {
                    return Carbon::parse($log->recorded_at)->toDateString();
                });
            $empSched = $employee->emplScheduleData->first();
            // Initialize total overtime
            $totalPeriodOvertime = 0.00;
            foreach ($periodDates as $date) {
                $schedInBoundary  = Carbon::parse("$date {$empSched->timein}");
                $schedOutBoundary = Carbon::parse("$date {$empSched->timeout}");
                $dayLogs = $empLogs->get($date, collect());
                $timeIn = null;
                $timeOut = null;
                // Carbon versions for computation
                $carbonIn = null;
                $carbonOut = null;
                if ($dayLogs->isNotEmpty()) {
                    $sortedLogs = $dayLogs->sortBy('recorded_at');
                    $firstLog = $sortedLogs->first();
                    $lastLog = $sortedLogs->last();
                    $carbonIn = Carbon::parse($firstLog->recorded_at);
                    if ($sortedLogs->count() > 1 && $firstLog->id != $lastLog->id) {
                        $carbonOut = Carbon::parse($lastLog->recorded_at);
                    }
                    $timeIn = $carbonIn->format('h:i A');
                    $timeOut = $carbonOut ? $carbonOut->format('h:i A') : null;
                }
                $isSunday = Carbon::parse($date)->isSunday();
                $display = '';
                $class = '';
                $payType = 'R';
                $overtime = 0.00;
                $acquiredHours = 0.00;
                $lateUndertime = 0.00;
                $lateMinutes = 0;
                $undertimeMinutes = 0;
                $breakOut = null;
                $breakIn = null;
                if ($dayLogs->isNotEmpty()) {
                    $breakWindowLogs = $dayLogs->filter(function ($log) use ($date) {
                        $logTime = Carbon::parse($log->recorded_at);
                        return $logTime->between(
                            Carbon::parse("$date 12:01:00"),
                            Carbon::parse("$date 12:50:00")
                        );
                    })->sortBy('recorded_at');
                    if ($breakWindowLogs->isNotEmpty()) {

                        $breakOut = Carbon::parse(
                            $breakWindowLogs->first()->recorded_at
                        )->format('H:i:s');

                        if ($breakWindowLogs->count() > 1) {
                            $breakIn = Carbon::parse(
                                $breakWindowLogs->last()->recorded_at
                            )->format('H:i:s');
                        }
                    }
                }
                if ($dayLogs->isEmpty()) {
                    $display = 'A';
                    $payType = $isSunday ? 'N' : 'A';
                    $class = 'bg-yellow-50 font-bold text-amber-700 text-center';
                } elseif (!$carbonIn || !$carbonOut) {

                    $display = 'N/A';
                    $payType = $isSunday ? 'N' : 'A';
                    $class = 'bg-red-100 font-bold text-red-600 text-center';
                } elseif ($carbonIn->diffInSeconds($carbonOut) < 60) {

                    $display = 'N/A';
                    $payType = $isSunday ? 'N' : 'A';
                    $class = 'bg-red-100 font-bold text-red-600 text-center';
                } else {
                    // Compute Late
                    if ($carbonIn->gt($schedInBoundary)) {
                        $lateMinutes = $schedInBoundary->diffInMinutes($carbonIn);
                    }

                    // Compute Undertime
                    if ($carbonOut->lt($schedOutBoundary)) {
                        $undertimeMinutes = $carbonOut->diffInMinutes($schedOutBoundary);
                    }

                    // Total deduction
                    $totalDeficitMinutes = $lateMinutes + $undertimeMinutes;

                    // Acquired Hours = 8hrs - (Late + Undertime)
                    $acquiredMinutes = max(0, 480 - $totalDeficitMinutes);

                    $workedHoursPart = floor($acquiredMinutes / 60);
                    $workedMinutesPart = $acquiredMinutes % 60;

                    $acquiredHours = (float)(
                        $workedHoursPart . '.' .
                        str_pad($workedMinutesPart, 2, '0', STR_PAD_LEFT)
                    );

                    // Late + Undertime (display)
                    $deficitHoursPart = floor($totalDeficitMinutes / 60);
                    $deficitMinutesPart = $totalDeficitMinutes % 60;

                    $lateUndertime = (float)(
                        $deficitHoursPart . '.' .
                        str_pad($deficitMinutesPart, 2, '0', STR_PAD_LEFT)
                    );

                    // Compute actual worked minutes (only for OT)
                    $grossMinutes = $carbonIn->diffInMinutes($carbonOut);

                    if ($grossMinutes > 300) {
                        $grossMinutes -= 60; // deduct lunch
                    }

                    // Standard shift minutes
                    $standardShiftMinutes = $schedInBoundary->diffInMinutes($schedOutBoundary);

                    if ($standardShiftMinutes > 300) {
                        $standardShiftMinutes -= 60;
                    }

                    // Overtime is only allowed when there's no late/undertime
                    if ($totalDeficitMinutes == 0) {

                        $rawOvertimeMinutes = $grossMinutes - $standardShiftMinutes;

                        if ($rawOvertimeMinutes >= 60) {
                            // Every 30 mins = 0.5 hour
                            $overtime = floor($rawOvertimeMinutes / 30) * 0.5;
                        }
                    }

                    $display = number_format($acquiredHours, 2);
                    $class = 'text-center';
                    $totalPeriodOvertime += $overtime;
                }
                $employeeTimesheets[$employee->employeeid][$date] = [
                    'display'   => $display,
                    'class'     => $class,
                    'sched_id'  => $empSched->id,
                    'hours'     => $acquiredHours,
                    'time_in'   => $timeIn,
                    'time_out'  => $timeOut,
                    'break_out' => $breakOut,
                    'break_in'  => $breakIn,
                ];
                $employeeTimesheets[$employee->employeeid][$date] = [
                    'display'   => $display,
                    'class'     => $class,
                    'sched_id'  => $empSched->id,
                    'hours'     => $acquiredHours,
                    'time_in'   => $timeIn,
                    'time_out'  => $timeOut,
                    'break_out' => $breakOut,
                    'break_in'  => $breakIn,
                ];
                // Check if a report already exists for this employee, date, and period
                $payrollReportExists = PayrollReport::where('employee_id', $employee->employeeid)
                    ->where('dateperiod_id', $period->id)
                    ->where('date_entry', $date)
                    ->exists();
                // If it already exists, skip processing the rest of this loop cycle
                if ($payrollReportExists) {
                    continue;
                }
                PayrollReport::create([
                    'dateperiod_id'  => $period->id,
                    'employee_id'    => $employee->employeeid,
                    'date_entry'     => $date,
                    'cat_id'         => 0,
                    'paytype'        => $payType,
                    'sched_id'       => $empSched->id,
                    'acquired_hours' => $acquiredHours,
                    'overtime'       => $overtime,
                    'late_undertime' => $lateUndertime,
                ]);
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
        // 2. Fetch ONLY the unique schedules assigned to these specific employees
        // 2. Fetch the raw schedules including their matching employeeid properties
        $availableSchedules = EmpSchedule::where('status', true)
            ->whereIn('employeeid', $employeeids)
            ->orderBy('timein')
            ->get(); // No groupBy here so employeeid is preserved!

        return view('payroll.process_matrix', compact(
            'employees',
            'period',
            'periodDates',
            'earningsCategories',
            'deductionHeaders',
            'employeeTimesheets',
            'adjustments',
            'availableSchedules',
            'govDeductions',
            'deductions',
            'holidays',
            'subcon_query',
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

        // dd($request->input('timesheet'));
        $validationErrors = [];
        // 1. Validate incoming data framework context requirements
        $request->validate([
            'employee_id' => 'required',
            'period_code' => 'required|string',
        ]);
        $employeeId = $request->input('employee_id');
        $periodCode = $request->input('period_code');
        $datePeriod = DatePeriod::where('code', $periodCode)->first();
        if (!$datePeriod) {
            $validationErrors[] = "The selected payroll cutoff period code ({$periodCode}) is invalid or does not exist.";
        }
        $employeeData = Employee::where('employeeid', $employeeId)
            ->where('status', true)
            ->first();
        if (!$employeeData) {
            $validationErrors[] = "Select Employee has an ID: ({$employeeId}) , currently is in inactive status";
        }
        if (!empty($validationErrors)) {
            return redirect()->back()->withErrors($validationErrors);
        }
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
            $startRange = Carbon::parse($datePeriod->datefrom)->startOfDay();
            $endRange = Carbon::parse($datePeriod->dateto)->endOfDay();
            DB::table('attendance_logs')
                ->where('user_id', $employeeId)
                ->whereBetween('recorded_at', [$startRange, $endRange])
                ->delete();
            DB::table('payroll_reports')
                ->where('employee_id', $employeeId)
                ->where('dateperiod_id', $datePeriod->id)
                ->delete();
            DB::table('payroll_summary_reports')
                ->where('employee_id', $employeeId)
                ->where('dateperiod_id', $datePeriod->id)
                ->delete();
            // =========================================================================
            // PROCESS 2: Insert fresh rows based on the requested matrix payload
            // =========================================================================
            $EmpBasicPay = 0.0;
            $dailyearnings = 0.0;
            $cuttoffearnings = 0.0;
            // First calculate basic pay out of your database data loops
            foreach ($employeeData->earningsData as $datass) {
                // $earningsCat = Category::where('id', $datass->title)->first();
                if (strtoupper($datass->frequency) === 'DAILY' && strtoupper($datass->hierarchy) === 'PRIMARY') {
                    // if ($earningsCat && strtoupper($earningsCat->name) === 'BASIC') {
                    $EmpBasicPay += (float)($datass->amount ?? 0.0);
                } else {
                    if (strtoupper($datass->frequency) === 'DAILY' && strtoupper($datass->hierarchy) === 'SECONDARY') {
                        $dailyearnings += (float)($datass->amount ?? 0.0);
                    } else {
                        $cuttoffearnings += (float)($datass->amount ?? 0.0);
                    }
                }
            }
            $percentage = (float) ($datePeriod->overtime_rate / 100);
            // Move rate definitions below the final basic pay sum value to avoid 0 division errors
            $HourRate = (float) ($EmpBasicPay / 8);
            $overTimerate = (float) (($HourRate * $percentage) + $HourRate);
            $Finaltotalhours = 0.0;
            $Finaltotalovertime = 0.0;
            $Finaltotalabsent = 0;
            $Finallateundertime = 0.0;
            $Finaltotaldeductionn = 0.0;
            $Finaltotalearnings = 0.0;
            $Finaltotaladjustment = 0.0;
            $Finaltotalnetpay = 0.0;
            $computedamountPerDay = 0.0;
            $RequiredRegularHours = 0.0;
            if ($request->has('timesheet')) {
                foreach ($request->input('timesheet') as $dateKey => $data) {
                    $payCat = strtoupper($data['pay_cat']);
                    // RULE B: If category is Regular (R), enforce validation requirements
                    if ($payCat === 'R') {
                        if (empty($data['time_in']) || empty($data['time_out'])) {
                            $validationErrors[] = "Time In and Time Out fields are required for date: {$dateKey}.";
                        }
                    }
                    // Gather all prospective time logs into a tracking map array
                    $timeLogsMap = [
                        'time_in'   => $data['time_in']   ?? null,
                        'break_out' => $data['break_out'] ?? null,
                        'break_in'  => $data['break_in']  ?? null,
                        'time_out'  => $data['time_out']  ?? null,
                    ];
                    foreach ($timeLogsMap as $slotKey => $timeValue) {
                        if (empty($timeValue)) {
                            continue;
                        }
                        $verificationModeMap = [
                            'time_in'   => 0,
                            'time_out'  => 1,
                            'break_out' => 2,
                            'break_in'  => 3,
                        ];
                        // Fallback to 0 if a key is somehow completely unmatched
                        $verificationMode = $verificationModeMap[$slotKey] ?? 0;
                        $dateTimeString = Carbon::parse("{$dateKey} {$timeValue}")->toDateTimeString();
                        $isSavedA = DB::table('attendance_logs')->insert([
                            'user_id'           => $employeeId,
                            'recorded_at'       => $dateTimeString,
                            'status'            => 1, // default(1)
                            'verification_mode' => $verificationMode, // Dynamic assignment based on current slot context
                            'work_code'         => 1, // default(1)
                            'reserved'          => 0, // default(0)
                            'project_code'      => $employeeData->project_id,
                            'created_at'        => now(),
                            'updated_at'        => now()
                        ]);
                        if (!$isSavedA) {
                            $validationErrors[] = "Data not created successfully to attendance logs for date: {$dateKey}.";
                        }
                    }
                    $isWipedOut = ($payCat === 'A' || $payCat === 'N');
                    // =========================================================================
                    // DYNAMIC REGULAR HOURS CALCULATION
                    // =========================================================================
                    $passedRegHours = (float)($data['regular_hours'] ?? 0);
                    $rowRegHours = $isWipedOut ? 0.0 : $passedRegHours;

                    // Trigger only if category is Regular, it's not wiped out, and the hours are explicitly 0
                    if ($payCat === 'R' && !$isWipedOut && $rowRegHours === 0.0) {
                        if (!empty($data['time_in']) && !empty($data['time_out'])) {
                            $inTime  = Carbon::parse("{$dateKey} {$data['time_in']}");
                            $outTime = Carbon::parse("{$dateKey} {$data['time_out']}");
                            if ($outTime->greaterThan($inTime)) {
                                $totalMinutes = $inTime->diffInMinutes($outTime);
                                $computedRegHours = $totalMinutes / 60;
                                // Deduct 1 hour if a valid lunch break window sequence is present
                                if (!empty($data['break_out']) && !empty($data['break_in'])) {
                                    $computedRegHours = max(0, $computedRegHours - 1.0);
                                }
                                // Cap regular standard work hours to an 8-hour shift maximum
                                if ($computedRegHours > 8.0) {
                                    $computedRegHours = 8.0;
                                }
                                // Assign the dynamically calculated value
                                // $rowRegHours = (float)$computedRegHours;
                            }
                        }
                    }
                    // Read and sanitize single row entries
                    $rowOvertime  = $isWipedOut ? 0.0 : (float)max(0, $data['overtime_hours'] ?? 0);
                    $rowRegHours  = $isWipedOut ? 0.0 : (float)max(0, $data['regular_hours'] ?? 0);
                    $rowLateHours = $isWipedOut ? 0.0 : (float)max(0, $data['late_undertime_hours'] ?? 0);
                    // --- COMPUTE ACCUMULATORS FOR SUMMARY MATRIX ---
                    $Finaltotalovertime += $rowOvertime;
                    $Finallateundertime += $rowLateHours;
                    // --- IDENTIFY PER DAY RATE
                    if (!$isWipedOut) {
                        $dailyPayType = Holiday::where('id', $data['holiday_id'])->first();
                        $PercentVal = $dailyPayType ? (float)$dailyPayType->percentage : 0.0;
                        if ($PercentVal <= 0) {
                            $Finaltotalhours += $rowRegHours;
                            $RequiredRegularHours++;
                            $computedamountPerDay += ($rowRegHours * $HourRate) + $dailyearnings;
                        } else {
                            $Ratepercentage = (float) ($PercentVal / 100);
                            $RatePerHourWithPercentage = (float) (($HourRate * $Ratepercentage) + $HourRate);
                            $computedamountPerDay += ($rowRegHours * $RatePerHourWithPercentage) + $dailyearnings;
                        }
                    } else {
                        $computedamountPerDay += $rowRegHours * $HourRate;
                    }
                    if ($payCat === 'A') {
                        $Finaltotalabsent++;
                        $RequiredRegularHours++;
                    }
                    $isSavedB = DB::table('payroll_reports')->insert([
                        'dateperiod_id'  => $datePeriod->id,
                        'employee_id'    => $employeeId,
                        'date_entry'     => $dateKey,
                        'paytype'        => $payCat,
                        'sched_id'       => $data['sched_id'] ?? null,
                        'overtime'       => $rowOvertime,
                        'acquired_hours' => $rowRegHours,
                        'late_undertime' => $rowLateHours,
                        'cat_id'         => $isWipedOut ? 0 : ($data['holiday_id'] ?? 0),
                        'created_at'     => now(),
                        'updated_at'     => now()
                    ]);
                    if (!$isSavedB) {
                        $validationErrors[] = "Data not created successfully to payroll reports for date: {$dateKey}.";
                    }
                }
            }

            // ==========================================
            // EXTRACT EXTERNAL PAYLOAD ACCUMULATORS
            // ==========================================
            // 1. Calculate All Adjustments 
            if ($request->has('adjustments')) {
                foreach ($request->input('adjustments') as $adj) {
                    $Finaltotaladjustment += (float)($adj['amount'] ?? 0.0);
                }
            }
            // 2. Calculate All Deductions (Gov + Other logs)
            if ($request->has('gov_deductions')) {
                foreach ($request->input('gov_deductions') as $gov) {
                    $Finaltotaldeductionn += (float)($gov['amount'] ?? 0.0);
                }
            }
            if ($request->has('other_deductions')) {
                foreach ($request->input('other_deductions') as $oth) {
                    $Finaltotaldeductionn += (float)($oth['amount'] ?? 0.0);
                }
            }
            // 3. Compute Gross Earnings & Final Net Pays
            $calculatedOtValue = $Finaltotalovertime * $overTimerate;
            $Finaltotalearnings = $computedamountPerDay + $calculatedOtValue + $cuttoffearnings;
            $Finalgrosspay = $Finaltotalearnings + $Finaltotaladjustment;
            $Finaltotalnetpay   = $Finalgrosspay - $Finaltotaldeductionn;
            $FinalRequiredRegularHours = $RequiredRegularHours * 8;

            //required icome computation  
            $requireAmount =  $HourRate * $FinalRequiredRegularHours;
            $finalrequiredincome = $requireAmount + $cuttoffearnings + ($RequiredRegularHours * $dailyearnings);
            // ==========================================
            // SUB-SECTION 5: RECORD SUMMARY DATASET
            // ==========================================
            $isSavedC = DB::table('payroll_summary_reports')->insert([
                'dateperiod_id'   => $datePeriod->id,
                'employee_id'     => $employeeId,
                'totalhours'      => number_format($Finaltotalhours, 2, '.', ''),
                'totalovertime'   => number_format($Finaltotalovertime, 2, '.', ''),
                'totalabsent'     => $Finaltotalabsent,
                'lateundertime'   => number_format($Finallateundertime, 2, '.', ''),
                'totaldeductionn' => number_format($Finaltotaldeductionn, 2, '.', ''),
                'totalearnings'   => number_format($Finaltotalearnings, 2, '.', ''),
                'totaladjustment' => number_format($Finaltotaladjustment, 2, '.', ''),
                'totalnetpay'     => number_format($Finaltotalnetpay, 2, '.', ''),
                'grosspay'        => number_format($Finalgrosspay, 2, '.', ''),
                'required_hours'   => number_format($FinalRequiredRegularHours, 2, '.', ''),
                'required_income'   => number_format($finalrequiredincome, 2, '.', ''),
                'created_at'      => now(),
                'updated_at'      => now()
            ]);

            if (!$isSavedC) {
                $validationErrors[] = "Data not created successfully for payroll summary";
            }
            // =========================================================================
            // PROCESS 3: Return Universal Success Response
            // =========================================================================
            if (!empty($validationErrors)) {
                DB::rollBack();
                $validationErrors[] = "Payroll details for ({$employeeData->lastname} , {$employeeData->firstname} {$employeeData->middlename}), has and error";
                return redirect()->back()->withErrors($validationErrors);
            }
            DB::commit();
            return redirect()->back()->with('success', "Attendance matrix logs processed and saved cleanly. for {$employeeData->lastname} , {$employeeData->firstname} {$employeeData->middlename}");
        } catch (Exception $e) {
            // Something broke down under load, discard changes instantly
            DB::rollBack();
            Log::error('Payroll Summary Synchronization Failure: ' . $e->getMessage(), [
                'employee_id' => $employeeId,
                'period_code' => $periodCode
            ]);
            $validationErrors[] = 'Critical database error Sql Exception' . $e->getMessage() . "  ({ $periodCode})";
            return redirect()->back()->withErrors($validationErrors);
        }
    }

    public function printBulkPayslips(Request $request)
    {
        $ids = $request->input('ids', []);
        $periodcode = $request->input('periodcode');
        $expartners = $request->input('expartners', 0);
        $period = DatePeriod::where('code', $periodcode)->firstOrFail();
        $employeeRelations = [
            'project',
            'empType',
            'empStat',
            // Eager load the category relationship attached to your earnings data
            'earningsData' => fn($q) => $q->where('status', true)->with('category'),
            'payrollReportsData' => fn($q) => $q->where('dateperiod_id', $period->id),
            'payrollSummaryData' => fn($q) => $q->where('dateperiod_id', $period->id),
            'adjustmentData' => fn($q) => $q->where('date_period_id', $period->code),
            'adjustmentData.adjustmentName',
            'otherdeductionData' => fn($q) => $q->where('date_period_id', $period->code),
            'otherdeductionData.otherDeduction',
            'govdeductionData' => fn($q) => $q->where('date_period_id', $period->code),
            'govdeductionData.govDeduction'
        ];

        // 2. Fetch primary employees, ordered by project name, then by employee lastname
        $loopData = Employee::whereIn('employees.id', $ids)
            ->leftJoin('projects', 'employees.project_id', '=', 'projects.project_code')
            ->select('employees.*')
            ->orderBy('projects.name', 'asc')
            ->orderBy('employees.lastname', 'asc')
            ->with($employeeRelations)
            ->get();

        // 3. Loop through each employee to compute and append their individual rates
        foreach ($loopData as $employee) {
            $EmpBasicPay = 0.0;
            $EmpBasicPayDailyAllowance = 0.0;
            $EmpCuttoffearing = 0.0;

            foreach ($employee->earningsData as $datass) {
                // Use the eager-loaded relation instead of hitting the database inside the loop
                if ($datass->category && strtoupper($datass->category->name) === 'BASIC') {
                    $EmpBasicPay += (float)($datass->amount ?? 0.0);
                } else {
                    if (strtoupper($datass->frequency) === 'DAILY') {
                        $EmpBasicPayDailyAllowance += (float)($datass->amount ?? 0.0);
                    } else {
                        $EmpCuttoffearing += (float)($datass->amount ?? 0.0);
                    }
                }
            }
            // Attach the calculated properties dynamically to the employee instance
            $employee->basic_rate = $EmpBasicPay;
            $basichrrate = $EmpBasicPay / 8;

            $employee->dailyallowance = $EmpBasicPayDailyAllowance;
            $employee->dailyallowanceratehour = $EmpBasicPayDailyAllowance / 8;
            $employee->rate_per_hour =  $basichrrate;
            $overtime_rates = (float)($period->overtime_rate ?? 0.0);
            $employee->otratehour =  ($basichrrate  *  ($overtime_rates / 100)) + $basichrrate;
        }
        $subcon_query = collect();
        if (!empty($expartners) && $expartners !== '0') {
            // Re-map relation keys using dot notation for eager loading nested models cleanly
            $subconRelations = [];
            foreach ($employeeRelations as $key => $value) {
                if (is_callable($value)) {
                    $subconRelations["SubConEmployee." . $key] = $value;
                } else {
                    $subconRelations["SubConEmployee." . $value] = fn($q) => $q;
                }
            }
            $subconRelations['SubConEmployee'] = fn($q) => $q->where('status', true);
            $subcon_query = Category::where('status', true)
                ->where('cat', 'SUBCON')
                ->when($expartners === 'ALL', fn($q) => $q->orderBy('name', 'asc'))
                ->when($expartners !== 'ALL', fn($q) => $q->where('id', $expartners))
                ->with($subconRelations)
                ->get();
            foreach ($subcon_query as $category) {
                if ($category->SubConEmployee) {
                    $category->SubConEmployee = $category->SubConEmployee->sortBy([
                        fn($a, $b) => ($a->project->name ?? '') <=> ($b->project->name ?? ''),
                        fn($a, $b) => ($a->lastname ?? '') <=> ($b->lastname ?? '')
                    ])->values(); // Reset array indexes
                }
            }
        }
        return view('payroll.payslip', compact('loopData', 'period'));
    }
}
