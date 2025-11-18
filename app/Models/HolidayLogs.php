<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidayLogs extends Model
{
    use HasFactory;

    // Table name
    protected $table = 'holiday_logs';

    // Mass assignable fields
    protected $fillable = [
        'employeeid',
        'holidayid',
        'numberofhours',
        'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeid', 'employeeid');
    }
    public function holiday()
    {
        return $this->belongsTo(Holiday::class, 'holidayid', 'id');
    }
}