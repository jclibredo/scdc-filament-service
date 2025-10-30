<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherDeductionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'other_deduction_id',
        'employee_id',
        'date_period_id',
    ];

    // Relationships

    public function otherDeduction()
    {
        return $this->belongsTo(OtherDeduction::class);
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
