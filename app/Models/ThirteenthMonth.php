<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThirteenthMonth extends Model
{
    use HasFactory;

    protected $table = 'thirteenth_months';

    // Fillable fields
    protected $fillable = [
        'periodid',
        'employeeid',
        'total_amount',
    ];

    /**
     * Relation: ThirteenthMonth belongs to an Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employeeid', 'employeeid');
    }

    /**
     * Relation: ThirteenthMonth belongs to a Period
     * (Assuming you have a Period model and table)
     */
    public function period()
    {
        return $this->belongsTo(DatePeriod::class, 'periodid', 'id');
    }
}