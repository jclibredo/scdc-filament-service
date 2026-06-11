<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidayLogs extends Model
{
    use HasFactory;

    protected $table = 'holiday_logs';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'holidayid',
        'employeeid',
        'timein',         // 💡 Added
        'timeout',        // 💡 Added
        'dateperiod_id',  // 💡 Added
    ];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'timein'  => 'datetime', // 💡 Allows clean ->format() methods directly in your views
        'timeout' => 'datetime',
    ];

    // Relationships

    public function holiday()
    {
        return $this->belongsTo(Holiday::class, 'holidayid');
    }

    public function datePeriod()
    {
        return $this->belongsTo(DatePeriod::class, 'dateperiod_id', 'code');
    }

    public function employeeDetails()
    {
        return $this->belongsTo(Employee::class, 'employeeid', 'employeeid');
    }
}
