<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncentiveBonus extends Model
{
    use HasFactory;

    protected $table = 'incentive_bonuses';

    protected $fillable = [
        'employeeid',
        'yearendrepid',
        'status',
        'earnings',
    ];

    protected $casts = [
        'status' => 'boolean',
        'earnings' => 'decimal:2',
    ];

    public function otherdeductionData()
    {
        return $this->hasMany(OtherDeductionLog::class, 'date_period_id', 'yearendrepid')
            ->where('employee_id', $this->employeeid);
    }

    public function govdeductionData()
    {
        return $this->hasMany(GovDeductionLog::class, 'date_period_id', 'yearendrepid')
            ->where('employee_id', $this->employeeid);
    }

    public function adjustmentData()
    {
        return $this->hasMany(Adjustment::class, 'date_period_id', 'yearendrepid')
            ->where('employee_id', $this->employeeid);
    }
    /**
     * Relation: ThirteenthMonth belongs to an Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeid', 'employeeid');
    }
}
