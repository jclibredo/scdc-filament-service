<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\Atlog;
use App\Models\DatePeriod;
use App\Models\Earnings;
use App\Models\Employee;
use App\Models\GovDeductionLog;
use App\Models\Holiday;
use App\Models\OtherDeductionLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayrollSummaryController extends Controller
{
    public function show($employee_id, $period_code)
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
