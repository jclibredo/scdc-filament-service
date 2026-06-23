<?php

namespace App\Models;

use DatePeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollReport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'dateperiod_id',
        'employee_id',
        'date_entry',
        'paytype',
        'overtime',
        'acquired_hours',
        'late_undertime',
        'cat_id',
        'status',
        'sched_id',
    ];

    protected function casts(): array
    {
        return [
            'date_entry' => 'date',
            'overtime' => 'float',
            'acquired_hours' => 'float',
            'late_undertime' => 'float',
            'status' => 'boolean'
        ];
    }


    public function holidayData()
    {
        return $this->belongsTo(Holiday::class, 'cat_id');
    }

    public function employeeData()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function dateperiodData()
    {
        return $this->belongsTo(DatePeriod::class, 'dateperiod_id');
    }
}
