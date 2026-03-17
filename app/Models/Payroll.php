<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $table = 'payrolls';

    protected $fillable = [
        'employeeid',
        'period', // Change to 'periodid' if that is your actual column name
        'totalhours',
        'acquirehours',
        'status',

        // Days of the week
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',

        // Overtime and Metadata
        'RegularOT',
        'Project',
        'created_by',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeid', 'employeeid');
    }

    public function period()
    {
        return $this->belongsTo(DatePeriod::class, 'periodid', 'id');
    }
}
