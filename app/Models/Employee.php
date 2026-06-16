<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'employeeid',
        'firstname',
        'middlename',
        'lastname',
        'status',
        'empstatus',
        'mobile',
        'email',
        'birthdate',
        'sex',
        'address',
        'datehired',
        'employeetype',
        'dateseperated',
        'skill_id',
        'project_id',
    ];

    public function otherdeductionData()
    {
        return $this->hasMany(OtherDeductionLog::class, 'employee_id', 'employeeid');
    }

    public function adjustmentData()
    {
        return $this->hasMany(Adjustment::class, 'employee_id', 'employeeid');
    }

    public function payrollReportsData()
    {
        return $this->hasMany(PayrollReport::class, 'employee_id', 'employeeid');
    }


    public function payrollSummaryData()
    {
        return $this->hasMany(PayrollSummaryReport::class, 'employee_id', 'employeeid');
    }

    public function earningsData()
    {
        return $this->hasMany(Earnings::class, 'employee_id', 'employeeid')->where('status', true);
    }



    public function empStat()
    {
        return $this->belongsTo(Category::class, 'empstatus');
    }

    public function empType()
    {
        // Points employeetype (which holds the category ID) to the Category model
        return $this->belongsTo(Category::class, 'employeetype');
    }

    protected static function booted()
    {
        static::updated(function ($employee) {
            // Check if the employeeid attribute was actually changed
            if ($employee->isDirty('employeeid')) {
                $oldId = $employee->getOriginal('employeeid');
                $newId = $employee->employeeid;
                // Run everything inside a transaction to ensure database integrity
                DB::transaction(function () use ($oldId, $newId) {
                    // 1. Tables using 'employee_id'
                    DB::table('earnings')->where('employee_id', $oldId)->update(['employee_id' => $newId]);
                    DB::table('other_deduction_logs')->where('employee_id', $oldId)->update(['employee_id' => $newId]);
                    DB::table('gov_deduction_logs')->where('employee_id', $oldId)->update(['employee_id' => $newId]);
                    // 2. Tables using 'employeeid'
                    DB::table('employee_project_histories')->where('employeeid', $oldId)->update(['employeeid' => $newId]);
                    DB::table('thirteenth_months')->where('employeeid', $oldId)->update(['employeeid' => $newId]);
                    DB::table('payrolls')->where('employeeid', $oldId)->update(['employeeid' => $newId]);
                    DB::table('holiday_logs')->where('employeeid', $oldId)->update(['employeeid' => $newId]);
                    // 3. Table using 'user_id'
                    DB::table('attendance_logs')->where('user_id', $oldId)->update(['user_id' => $newId]);
                });
            }
        });
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_code');
    }

    public function projectHistories()
    {
        return $this->hasMany(EmployeeProjectHistory::class, 'employeeid', 'employeeid')
            ->where('status', true);
    }

    public function thirteenthMonth()
    {
        return $this->hasOne(ThirteenthMonth::class, 'employeeid', 'employeeid');
    }

    public function getFullNameAttribute(): string
    {
        return collect([$this->firstname, $this->middlename, $this->lastname])
            ->filter()
            ->implode(' ');
    }
}
