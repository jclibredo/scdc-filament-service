<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GovDeductionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'gov_deduction_id',
        'employee_id',
        'date_period_id',
    ];

    // Relationships

    public function govDeduction()
    {
        return $this->belongsTo(GovDeduction::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function datePeriod()
    {
        return $this->belongsTo(DatePeriod::class);
    }
}
