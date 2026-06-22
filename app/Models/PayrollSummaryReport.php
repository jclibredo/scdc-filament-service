<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollSummaryReport extends Model
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
        'totalhours',
        'totalovertime',
        'totalabsent',
        'lateundertime',
        'totaldeductionn',
        'totalearnings',
        'totaladjustment',
        'totalnetpay',
        'grosspay',
        'status',
        'required_hours',
        'required_income'
    ];


    protected function casts(): array
    {
        return [
            'totalhours' => 'float',
            'totalovertime' => 'float',
            'totalabsent' => 'float',
            'lateundertime' => 'float',
            'totaldeductionn' => 'float',
            'totalearnings' => 'float',
            'totaladjustment' => 'float',
            'totalnetpay' => 'float',
            'grosspay' => 'float',
            'status' => 'boolean',
            'required_hours' => 'float',
            'required_income' => 'float',
        ];
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
