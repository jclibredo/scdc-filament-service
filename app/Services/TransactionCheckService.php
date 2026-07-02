<?php

namespace App\Services;

use App\Models\Category;
use App\Models\DatePeriod;
use App\Models\Earnings;
use App\Models\Employee;
use App\Models\EmpSchedule;
use App\Models\GovDeduction;
use App\Models\Holiday;
use App\Models\OtherDeduction;
use App\Models\Project;
use App\Models\Skill;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class TransactionCheckService
{
    /**
     * Check if an Employee has existing transactional data.
     */
    public static function hasEmployeeTransactions(Employee $employee): bool
    {
        $id = $employee->employeeid;

        return DB::table('attendance_logs')->where('user_id', $id)->exists()
            || DB::table('adjustments')->where('employee_id', $id)->exists()
            || DB::table('earnings')->where('employee_id', $id)->exists()
            || DB::table('emp_schedule')->where('employeeid', $id)->exists()
            || DB::table('gov_deduction_logs')->where('employee_id', $id)->exists()
            || DB::table('holiday_logs')->where('employeeid', $id)->exists()
            || DB::table('other_deduction_logs')->where('employee_id', $id)->exists()
            || DB::table('payroll_reports')->where('employee_id', $id)->exists()
            || DB::table('payroll_summary_reports')->where('employee_id', $id)->exists();
    }

    public static function hasEarningTransactions(Earnings $earning): bool
    {
        $employeeId = $earning->employee_id;

        return DB::table('payroll_reports')->where('employee_id', $employeeId)->exists()
            || DB::table('attendance_logs')->where('user_id', $employeeId)->exists();
    }

    /**
     * Check if a Date Period has existing transactional data.
     */
    public static function hasDatePeriodTransactions(DatePeriod $datePeriod): bool
    {
        $code = $datePeriod->code;
        $id = $datePeriod->id;

        return DB::table('adjustments')->where('date_period_id', $code)->exists()
            || DB::table('gov_deduction_logs')->where('date_period_id', $code)->exists()
            || DB::table('holiday_logs')->where('dateperiod_id', $code)->exists()
            || DB::table('other_deduction_logs')->where('date_period_id', $code)->exists()
            || DB::table('payroll_reports')->where('dateperiod_id', $id)->exists()
            || DB::table('payroll_summary_reports')->where('dateperiod_id', $id)->exists();
    }

    /**
     * Check if an Employee Schedule is attached to payrolls.
     */
    public static function hasScheduleTransactions(EmpSchedule $schedule): bool
    {
        return DB::table('payroll_reports')->where('sched_id', $schedule->id)->exists();
    }

    /**
     * Check if a Category is utilized anywhere.
     */
    public static function hasCategoryTransactions(Category $category): bool
    {
        $id = $category->id;

        return DB::table('employees')
            ->where('employeetype', $id)
            ->orWhere('empstatus', $id)
            ->orWhere('partners', $id)
            ->exists()
            || DB::table('date_periods')
            ->where('category_id', $id)
            ->orWhere('employeetype', $id)
            ->exists()
            || DB::table('earnings')->where('title', $id)->exists();
    }

    /**
     * Check if other deductions have log tracks.
     */
    public static function hasOtherDeductionTransactions(OtherDeduction $deduction): bool
    {
        return DB::table('other_deduction_logs')->where('other_deduction_id', $deduction->id)->exists();
    }

    /**
     * Check if government deductions have logs.
     */
    public static function hasGovDeductionTransactions(GovDeduction $deduction): bool
    {
        return DB::table('gov_deduction_logs')->where('gov_deduction_id', $deduction->id)->exists();
    }

    /**
     * Check if a holiday has linked transaction logs.
     */
    public static function hasHolidayTransactions(Holiday $holiday): bool
    {
        $id = $holiday->id;

        return DB::table('holiday_logs')->where('holidayid', $id)->exists()
            || DB::table('payroll_reports')->where('cat_id', $id)->exists();
    }

    /**
     * Check if a project is linked to an employee or history profile.
     */
    public static function hasProjectTransactions(Project $project): bool
    {
        $code = $project->project_code;

        return DB::table('employees')->where('project_id', $code)->exists()
            || DB::table('employee_project_histories')->where('projectid', $code)->exists();
    }

    /**
     * Check if a skill is explicitly referenced by employees.
     */
    public static function hasSkillTransactions(Skill $skill): bool
    {
        return DB::table('employees')->where('skill_id', $skill->id)->exists();
    }
}
